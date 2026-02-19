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
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
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
        // $firebase = new FirebaseNotificationService();
        // $firebase->sendToUser(
        //     $data['assigned_to'],
        //     "New Task Assigned",
        //     "You have been assigned: {$data['title']}",
        //     ['task_id' => $task->id]
        // );

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
            'status' => 'required|in:todo,pending,ongoing,completed'
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

            // $firebase = new FirebaseNotificationService();
            // $firebase->sendToUser(
            //     $project_manager_id,
            //     "Task Completed",
            //     "Task '{$task->title}' has been completed.",
            //     ['task_id' => $task->id]
            // );
        }

        return response()->json([
            'status'  => true,
            'message' => 'Status updated successfully'
        ]);
    }

    //  GET TASKS OF EMPLOYEE
    public function getUserTasks($userId)
    {
        $tasks = Tasks::with([
            'project:id,title',
            'assignedUser:id,name',
            'comments:id,task_id,user_id,comment,created_at',
            'comments.user:id,name',
            'worklogs.user:id,name'
        ])
        ->where('assigned_to', $userId) // ✅ FILTER BY USER
        ->orderByRaw("
            CASE status
                WHEN 'todo' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'ongoing' THEN 3
                WHEN 'completed' THEN 4
                ELSE 5
            END
        ")
        ->orderBy('end_date', 'asc')
        ->get();

        $response = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'startDate' => $task->start_date,
                'endDate' => $task->end_date,
                'projectid' => $task->project_id,
                'projectname' => $task->project?->title,
                'status' => $task->status,
                'assignedEmployee' => $task->assignedUser?->name,

                // ✅ Comments
                'comments' => $task->comments->map(function ($comment) {
                    return [
                        'user_id'   => $comment->user_id,
                        'user_name' => $comment->user?->name,
                        'message'   => $comment->comment,
                        'createdAt' => optional($comment->created_at)->format('Y-m-d H:i')
                    ];
                }),

                // ✅ Worklogs with username
                'worklogs' => $task->worklogs->map(function ($log) {
                    return [
                        'user_id'   => $log->user_id,
                        'user_name' => $log->user?->name,
                        'hours'     => $log->hours,
                        'createdAt' => optional($log->created_at)->format('Y-m-d H:i')
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'tasks'  => $response
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

        // $firebase = new FirebaseNotificationService();
        // $firebase->sendToUser(
        //     $task->created_by,
        //     "New Comment",
        //     "A new comment was added to your task: {$task->title}",
        //     ['task_id' => $task->id]
        // );

        return response()->json([
            'status'  => true,
            'message' => 'Comment added'
        ]);
    }



     public function taskList()
    {
            $tasks = Tasks::with([
            'project:id,title',
            'assignedUser:id,name',
            'comments:id,task_id,user_id,comment,created_at',
            'comments.user:id,name',
            'worklogs.user:id,name'
        ])
        ->orderByRaw("
            CASE status
                WHEN 'todo' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'ongoing' THEN 3
                WHEN 'completed' THEN 4
                ELSE 5
            END
        ")
        ->orderBy('end_date', 'asc')
        ->get();

        $response = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'startDate' => $task->start_date,
                'endDate' => $task->end_date,
                'projectid' => $task->project_id,
                'projectname' => $task->project?->title,
                'status' => $task->status,
                'assignedEmployee' => $task->assignedUser?->name,

                // ✅ Comments
                'comments' => $task->comments->map(function ($comment) {
                    return [
                        'user_id'   => $comment->user_id,
                        'user_name' => $comment->user?->name,
                        'message' => $comment->comment,
                        'createdAt' => $comment->created_at
                            ? $comment->created_at->format('Y-m-d H:i')
                            : null
                    ];
                }),

                // ✅ Worklogs with username
                'worklogs' => $task->worklogs->map(function ($log) {
                    return [
                        'user_id'   => $log->user_id,
                        'user_name' => $log->user?->name,
                        'hours'     => $log->hours,
                       'createdAt' => $log->created_at
                            ? $log->created_at->format('Y-m-d H:i')
                            : null
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'tasks'  => $response
        ]);
    }

    public function taskDetails($taskId)
    {
        $task = Tasks::with([
            'project:id,title',
            'assignedUser:id,name',
            'comments:id,user_id,task_id,comment,created_at',
            'comments.user:id,name',
            'worklogs.user:id,name'
        ])->find($taskId);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found'
            ], 404);
        }

        $response = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'startDate' => $task->start_date,
            'endDate' => $task->end_date,
            'projectid' => $task->project_id,
            'projectname' => $task->project?->title,
            'status' => $task->status,
            'assignedEmployee' => $task->assignedUser?->name,

            'comments' => $task->comments->map(function ($comment) {
                return [
                    'user_id'   => $comment->user_id,
                    'user_name' => $comment->user?->name,
                    'message' => $comment->comment,
                    'createdAt' => $comment->created_at->format('Y-m-d H:i')
                ];
            }),

            'worklogs' => $task->worklogs->map(function ($log) {
                return [
                    'user_id'   => $log->user_id,
                    'user_name' => $log->user?->name,
                    'hours'     => $log->hours,
                    'createdAt' => $log->created_at->format('Y-m-d H:i')
                ];
            })
        ];

        return response()->json([
            'status' => true,
            'task' => $response
        ]);
    }

    public function tasksByUserAndProject(Request $request)
    {
        
        $validated = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id'
        ]);

        $tasks = Tasks::with([
        'project:id,title',
        'assignedUser:id,name',
        'comments:id,task_id,user_id,comment,created_at',
        'comments.user:id,name',
        'worklogs.user:id,name'
        ])
        ->where('assigned_to', $validated['user_id'])
        ->where('project_id', $validated['project_id'])
        ->orderByRaw("
            CASE status
                WHEN 'todo' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'ongoing' THEN 3
                WHEN 'completed' THEN 4
                ELSE 5
            END
        ")
        ->orderBy('end_date', 'asc')
        ->get();

        $response = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'startDate' => $task->start_date,
                'endDate' => $task->end_date,
                'status' => $task->status,
                'projectname' => $task->project?->title,
                'assignedEmployee' => $task->assignedUser?->name,

                'comments' => $task->comments->map(function ($comment) {
                    return [
                        'user_id' => $comment->user_id,
                        'user_name' => $comment->user?->name,
                        'message' => $comment->comment,
                        'createdAt' => optional($comment->created_at)->format('Y-m-d H:i')
                    ];
                }),

                'worklogs' => $task->worklogs->map(function ($log) {
                    return [
                        'user_id' => $log->user_id,
                        'user_name' => $log->user?->name,
                        'hours' => $log->hours,
                        'createdAt' => optional($log->created_at)->format('Y-m-d H:i')
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'tasks' => $response
        ]);
    }

    public function tasksByProject($projectId)
    {
        $tasks = Tasks::with([
            'project:id,title',
            'assignedUser:id,name',
            'comments:id,task_id,user_id,comment,created_at',
            'comments.user:id,name',
            'worklogs.user:id,name'
        ])
        ->where('project_id', $projectId)
        ->orderByRaw("
            CASE status
                WHEN 'todo' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'ongoing' THEN 3
                WHEN 'completed' THEN 4
                ELSE 5
            END
        ")
        ->orderBy('end_date', 'asc')
        ->get();
        
        if ($tasks->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No tasks found for this project'
            ]);
        }

        $response = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'startDate' => $task->start_date,
                'endDate' => $task->end_date,
                'status' => $task->status,
                'assignedEmployee' => $task->assignedUser?->name,

                'comments' => $task->comments->map(function ($comment) {
                    return [
                        'user_id' => $comment->user_id,
                        'user_name' => $comment->user?->name,
                        'message' => $comment->comment,
                        'createdAt' => optional($comment->created_at)->format('Y-m-d H:i')
                    ];
                }),

                'worklogs' => $task->worklogs->map(function ($log) {
                    return [
                        'user_id' => $log->user_id,
                        'user_name' => $log->user?->name,
                        'hours' => $log->hours,
                        'createdAt' => optional($log->created_at)->format('Y-m-d H:i')
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'tasks' => $response
        ]);
    }




}
