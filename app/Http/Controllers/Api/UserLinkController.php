<?php

namespace App\Http\Controllers\Api;
use App\Services\FirebaseNotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserLink;
use App\Models\User;
use App\Models\Notifications;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class UserLinkController extends Controller
{

 public function store(Request $request)
    {
        $request->validate([
           'title'=>'required',
       
        ]);

        $link = UserLink::create([
         
            'title'=>$request->title,
           
        ]);

        return response()->json([
            'status'=>true,
            'data'=>$link
        ]);
    }

    public function list()
    {
        $links = UserLink::select('id', 'title')->get();
        return response()->json($links);
    }


    public function update(Request $request,$id)
    {
        $link = UserLink::findOrFail($id);

        $link->update([
            'title'=>$request->title,
        ]);

        return response()->json([
            'status'=>true,
            'data'=>$link
        ]);
    }
}
