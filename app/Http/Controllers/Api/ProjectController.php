<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Projects;
use App\Models\ProjectAssignments;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     $list = Projects::select('id', 'title', 'status', 'category_id')->with('categoryName')->get();

    //     return response()->json([
    //         'status' => true,
    //         'projects' => $list
    //     ]);
    // }

   public function index()
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
