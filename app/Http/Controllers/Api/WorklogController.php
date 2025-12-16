<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkLogs;
use App\Models\Project;
use App\Models\Task;

class WorklogController extends Controller
{
    // CREATE WORKLOG
    public function store(Request $request)
    {
        
       try {
            $validated = $request->validate([
                'user_id'     => 'required|exists:users,id',
                'task_id'     => 'required|exists:tasks,id',
                'hours'       => 'required',
                //'description' => 'nullable|string'
            ]);
            
        } catch (\Exception $e) {
            dd($e->getMessage(), $request->all());
        }

 
        WorkLogs::create($validated);
      
        return response()->json([
            'status'  => true,
            'message' => 'Worklog added'
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
