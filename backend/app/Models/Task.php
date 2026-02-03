<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'project_id',
        'assigned_to',
        'blocked',
        'blocked_by_task_id',
        'blocked_description',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'blocked' => 'boolean',
            'due_date' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function blockingTask()
    {
        return $this->belongsTo(Task::class, 'blocked_by_task_id');
    }

    public function blockedTasks()
    {
        return $this->hasMany(Task::class, 'blocked_by_task_id');
    }
}
