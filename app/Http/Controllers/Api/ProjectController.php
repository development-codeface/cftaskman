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
        $list = Projects::select('id', 'title', 'status', 'category_id')->with('categoryName')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'category_name' => $item->categoryName?->name,
                'status' => $item->status,
            ];
        });

        return response()->json([
            'status' => true,
            'projects' => $list
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'title'    => 'required',
            'description'     => 'required',
            'start_date'     => 'required',
            'end_date'     => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($request->status == "") {
            $status = 'pending';
        } else {
            $status = $request->status;
        }

        $create = Projects::create([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description'     => $request->description,
            'start_date'   => $request->start_date,
            'end_date'  => $request->end_date,
            'created_by' => $request->created_by,
            'status'     => $status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Project created successfully',
            'project_id' => $create->id
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
        $list = ProjectAssignments::where('user_id', $id)->with('project:id,title,status')->get()->pluck('project');

        return response()->json([
            'status' => true,
            'projects' => $list
        ]);
    }
}
