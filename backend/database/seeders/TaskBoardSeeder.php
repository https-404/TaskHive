<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskBoardSeeder extends Seeder
{
    /**
     * Create a demo project and tasks for the first user.
     */
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        if (! $user) {
            return;
        }

        $project = Project::query()->firstOrCreate(
            ['name' => 'Demo Project', 'manager_id' => $user->id],
            ['description' => 'Sample project for the task board']
        );

        $statuses = TaskStatus::all();
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $titles = [
            'Set up CI/CD pipeline',
            'Review auth flow',
            'Design API for tasks',
            'Implement drag and drop',
            'Write user documentation',
            'Fix login validation',
        ];

        foreach ($titles as $i => $title) {
            Task::query()->firstOrCreate(
                [
                    'title' => $title,
                    'project_id' => $project->id,
                ],
                [
                    'description' => null,
                    'status' => $statuses[$i % count($statuses)],
                    'priority' => $priorities[$i % count($priorities)],
                    'assigned_to' => $user->id,
                    'blocked' => false,
                    'due_date' => now()->addDays(rand(1, 14))->format('Y-m-d'),
                ]
            );
        }
    }
}
