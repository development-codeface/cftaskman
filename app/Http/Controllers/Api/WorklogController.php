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
                'project_id'  => 'required|exists:projects,id',
                'task_id'     => 'required|exists:tasks,id',
                'work_date'   => 'required|date',
                'hours'       => 'required|numeric|min:0',
                'description' => 'nullable|string'
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
            ->join('projects', 'projects.id', '=', 'work_logs.project_id')
            ->join('tasks', 'tasks.id', '=', 'work_logs.task_id')
            ->select(
                'work_logs.id',
                'projects.title as project',
                'tasks.title as task',
                'work_logs.work_date',
                'work_logs.hours',
                'work_logs.description'
            )
            ->orderBy('work_date', 'desc')
            ->get();

        return response()->json([
            'status'   => true,
            'worklogs' => $worklogs
        ]);
    }
}
