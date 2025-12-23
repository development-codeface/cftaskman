<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkLogs;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Notifications;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;

class WorklogController extends Controller
{
    // CREATE WORKLOG

public function store(Request $request)
{
    $validated = $request->validate([
        'task_id' => 'required|exists:tasks,id',
        'hours'   => 'required|numeric|min:0.1',
    ]);

    $validated['user_id'] = auth()->id();

    DB::transaction(function () use ($validated) {

        $worklog = WorkLogs::create($validated);

        $task = Task::findOrFail($validated['task_id']);

        $adminIds = User::where('role', 'admin')->pluck('id');

        foreach ($adminIds as $adminId) {

            Notifications::create([
                'user_id' => $adminId,
                'title'   => 'New Worklog Added',
                'message' => "A worklog of {$validated['hours']} hours was added for task '{$task->title}'.",
                'type'    => 'worklog',
                'is_read' => 0
            ]);

            $firebase = new FirebaseNotificationService();
            $firebase->sendToUser(
                (string) $adminId,
                'New Worklog Added',
                "Worklog added for task: {$task->title}",
                ['task_id' => (string) $task->id]
            );
        }
    });

    return response()->json([
        'status'  => true,
        'message' => 'Worklog added successfully'
    ]);
}


    // GET WORKLOGS BY USER
    public function getByUser($user_id)
    {
        $worklogs = WorkLogs::where('user_id', $user_id)
             ->join('tasks', 'tasks.id', '=', 'work_logs.task_id')
            ->select(
                'work_logs.id',
                'tasks.title as task',
                'work_logs.hours',
            )
            ->get();

        return response()->json([
            'status'   => true,
            'worklogs' => $worklogs
        ]);
    }
}
