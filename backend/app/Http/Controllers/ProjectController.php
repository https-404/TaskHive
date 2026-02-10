<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * List projects the current user manages or has tasks in.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $query = Project::query()
            ->where('manager_id', $user->id)
            ->orWhereHas('tasks', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            })
            ->orderBy('name')
            ->withCount('tasks');

        $projects = $query->get()->map(fn (Project $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'tasks_count' => $p->tasks_count,
        ]);

        return response()->json(['projects' => $projects]);
    }

    /**
     * Create a new project (current user becomes manager).
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project = Project::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'manager_id' => $user->id,
        ]);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'tasks_count' => 0,
            ],
        ], 201);
    }
}
