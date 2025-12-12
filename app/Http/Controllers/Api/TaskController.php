<?php

namespace App\Http\Controllers\Api;
use App\Services\FirebaseNotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tasks;
use App\Models\TaskComments;
use App\Models\Notifications;
use App\Models\Projects;

class TaskController extends Controller
{
   
    // CREATE TASK
   
    public function create(Request $request)
    {
        $data = $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'title'       => 'required|string',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        $data['created_by'] = auth()->id() ?? 1; // or pass from request

        // ---------- SEND NOTIFICATION ----------
        if (!empty($data['assigned_to'])) {
            Notifications::create([
                'user_id' => $data['assigned_to'],
                'title'   => "New Task Assigned",
                'message' => "You have been assigned a new task: {$data['title']}",
                'type'    => "task",
                'is_read' => 0
            ]);
        }

        $task = Tasks::create($data);
        $firebase = new FirebaseNotificationService();
        $firebase->sendToUser(
            $data['assigned_to'],
            "New Task Assigned",
            "You have been assigned: {$data['title']}",
            ['task_id' => $task->id]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Task created',
            'task_id' => $task->id
        ]);
    }

    //  UPDATE STATUS
   
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'status' => 'required|in:todo,in_progress,done'
        ]);

        Tasks::where('id', $validated['task_id'])
            ->update(['status' => $validated['status']]);

        $task = Tasks::find($validated['task_id']);
        $task->update(['status' => $validated['status']]);
        // If task completed → notify project manager
        if ($validated['status'] == 'done') {

            $project = Projects::find($task->project_id);
            $project_manager_id = $project->created_by;  // adjust if your column name differs

            Notifications::create([
                'user_id' => $project_manager_id,
                'title'   => "Task Completed",
                'message' => "Task '{$task->title}' has been completed.",
                'type'    => "task",
                'is_read' => 0
            ]);

            $firebase = new FirebaseNotificationService();
            $firebase->sendToUser(
                $project_manager_id,
                "Task Completed",
                "Task '{$task->title}' has been completed.",
                ['task_id' => $task->id]
            );
        }

        return response()->json([
            'status'  => true,
            'message' => 'Status updated successfully'
        ]);
    }

    //  GET TASKS OF EMPLOYEE
   
    public function getUserTasks($user_id)
    {
        $tasks = Tasks::where('assigned_to', $user_id)
            ->select('id as task_id', 'title', 'deadline', 'status')
            ->orderBy('deadline', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'tasks'  => $tasks
        ]);
    }

    //  ADD COMMENT
     public function addComment(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'comment' => 'required|string',
            'user_id' => 'required|exists:users,id'
        ]);

        TaskComments::create($validated);
        $task = Tasks::find($validated['task_id']);

        // Notify task owner
        Notifications::create([
            'user_id' => $task->created_by,
            'title'   => "New Task Comment",
            'message' => "A new comment was added on your task: '{$task->title}'",
            'type'    => 'comment',
            'is_read' => 0
        ]);

        $firebase = new FirebaseNotificationService();
        $firebase->sendToUser(
            $task->created_by,
            "New Comment",
            "A new comment was added to your task: {$task->title}",
            ['task_id' => $task->id]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Comment added'
        ]);
    }
}
