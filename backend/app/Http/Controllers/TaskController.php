<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * List tasks (optionally by project). Only projects user manages or has tasks in.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $query = Task::query()
            ->whereHas('project', function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhereHas('tasks', function ($t) use ($user) {
                        $t->where('assigned_to', $user->id);
                    });
            })
            ->with(['project:id,name', 'assignedTo:id,name'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        $tasks = $query->get()->map(fn (Task $t) => $this->taskToArray($t));

        return response()->json(['tasks' => $tasks]);
    }

    /**
     * Create a task in a project the current user can access.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => TaskStatus::rules(),
            'priority' => ['required', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'blocked' => ['sometimes', 'boolean'],
        ]);

        // Ensure user can add tasks to this project (is manager or has tasks in it).
        $canAccess = Project::query()
            ->where('id', $validated['project_id'])
            ->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhereHas('tasks', function ($t) use ($user) {
                        $t->where('assigned_to', $user->id);
                    });
            })
            ->exists();

        if (! $canAccess) {
            return response()->json(['message' => 'Project not found or access denied.'], 404);
        }

        $task = Task::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'project_id' => $validated['project_id'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'blocked' => $validated['blocked'] ?? false,
        ]);

        $task->load(['project:id,name', 'assignedTo:id,name']);

        return response()->json(['task' => $this->taskToArray($task)], 201);
    }

    /**
     * Update a task (e.g. status for drag-drop). Only if user can access the task's project.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $task = Task::query()
            ->whereHas('project', function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhereHas('tasks', function ($t) use ($user) {
                        $t->where('assigned_to', $user->id);
                    });
            })
            ->find($id);

        if (! $task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        $validated = $request->validate([
            'status' => TaskStatus::rules(),
            'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'blocked' => ['sometimes', 'boolean'],
        ]);

        $task->update($validated);
        $task->load(['project:id,name', 'assignedTo:id,name']);

        return response()->json(['task' => $this->taskToArray($task)]);
    }

    private function taskToArray(Task $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'priority' => $t->priority,
            'project_id' => $t->project_id,
            'project' => $t->relationLoaded('project') ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
            'assigned_to' => $t->assigned_to,
            'assignee' => $t->relationLoaded('assignedTo') && $t->assignedTo
                ? ['id' => $t->assignedTo->id, 'name' => $t->assignedTo->name]
                : null,
            'blocked' => $t->blocked,
            'due_date' => $t->due_date?->format('Y-m-d'),
            'created_at' => $t->created_at->toIso8601String(),
            'updated_at' => $t->updated_at->toIso8601String(),
        ];
    }
}
