<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Projects;
use App\Models\Tasks;
use App\Models\ProjectAssignments;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   
    public function index()
    {
        $projects = Projects::select('id','title','start_date','end_date','description','status')
            ->with(['assignments.user:id,name'])
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

        // Get task counts grouped by project_id + assigned_to
        $taskCounts = Tasks::selectRaw('project_id, assigned_to, COUNT(*) as total')
            ->groupBy('project_id', 'assigned_to')
            ->get()
            ->groupBy(fn($row) => $row->project_id . '_' . $row->assigned_to);

        $projects = $projects->map(function ($project) use ($taskCounts) {

            $assignedUsers = $project->assignments->map(function ($assign) use ($project, $taskCounts) {

                $key = $project->id . '_' . $assign->user_id;
                $count = $taskCounts->get($key)?->first()?->total ?? 0;

                return [
                    'user_id'    => $assign->user_id,
                    'user_name'  => $assign->user?->name,
                    'task_count' => $count
                ];
            });

            return [
                'id'            => $project->id,
                'title'         => $project->title,
                'start_date'    => $project->start_date,
                'end_date'      => $project->end_date,
                'description'   => $project->description,
                'status'        => $project->status,
                'assigned_users'=> $assignedUsers
            ];
        });

        return response()->json([
            'status'   => true,
            'projects' => $projects
        ]);
    }


   public function index_backup()
    {
        $list = Projects::select(
                'id',
                'title',
                'start_date',
                'end_date',
                'description',
                'status'
            )
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
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'start_date'  => $item->start_date,
                    'end_date'    => $item->end_date,
                    'description' => $item->description,
                    'status'      => $item->status,
                ];
            });

        return response()->json([
            'status'   => true,
            'projects' => $list
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        

    // ✅ VALIDATION
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string',
            'description' => 'required|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'created_by'  => 'required|exists:users,id',
            'status'      => 'nullable|in:todo,pending,ongoing,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        // ✅ MAP DATE FIELDS (support both formats)
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        $project = Projects::create([
            'title'       => $request->title,
            'description' => $request->description,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'created_by'  => $request->created_by,
            'status'      => $request->status ?? 'to_do'
        ]);

        return response()->json([
            'status'     => true,
            'message'    => 'Project created successfully',
            'project_id' => $project->id
        ]);
    }


     /**
     * function for update project status
     */

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'projectId' => 'required|exists:projects,id',
            'status'    => 'required|in:todo,pending,ongoing,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        Projects::where('id', $request->projectId)
            ->update(['status' => $request->status]);

        return response()->json([
            'status'  => true,
            'message' => 'Project status updated successfully'
        ]);
    }


    /**
     * .function for project assign user
     */
    public function projectAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required',
            'user_id'    => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        ProjectAssignments::create([
            'project_id' => $request->project_id,
            'user_id'    => $request->user_id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Project assigned successfully',
        ]);
    }

    /**
     * .function for project assign user list
     */
    public function projectAssignList($id)
    {
        $list = ProjectAssignments::where('user_id', $id)->with('project:id,title,start_date,end_date,description,status')->get()->pluck('project');

        return response()->json([
            'status' => true,
            'projects' => $list
        ]);
    }
}
