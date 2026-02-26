<?php

namespace App\Http\Controllers\Api;
use App\Services\FirebaseNotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserLink;
use App\Models\Notifications;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AttendanceController extends Controller
{

 public function clockIn(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // prevent multiple clockins same day
        $already = Attendance::where('user_id',$request->user_id)
            ->whereDate('attendance_date',today())
            ->first();

        if($already){
            return response()->json([
                'status'=>false,
                'message'=>'Already clocked in today'
            ]);
        }

        $attendance = Attendance::create([
            'user_id' => $request->user_id,
            'clock_in' => now(),
            'attendance_date' => today(),
            'clockin_description' => $request->description ?? null
        ]);

        // get user for notification
        $user = User::find($request->user_id);

        $this->notifyAdmin($user,"Clocked IN");

        return response()->json([
            'status'=>true,
            'message'=>'Clock in saved',
            'data'=>$attendance
        ]);
    }

public function clockOut(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'link_ids' => 'nullable|array'
    ]);

    $attendance = Attendance::where('user_id',$request->user_id)
        ->whereDate('attendance_date',today())
        ->first();

    if(!$attendance){
        return response()->json([
            'status'=>false,
            'message'=>'No clockin found'
        ]);
    }

    $attendance->clock_out = now();
    $attendance->clockout_description = $request->description ?? null;
    $attendance->link_ids = $request->link_ids ?? [];

    $minutes = \Carbon\Carbon::parse($attendance->clock_in)
                ->diffInMinutes(now());

    $attendance->work_minutes = $minutes;

    if($minutes < 480){
        $attendance->is_absent = 1;
    }

    $attendance->save();

    // 🔥 GET PLATFORM NAMES
    $platformNames = [];

    if(!empty($attendance->link_ids)){
        $platformNames = UserLink::whereIn('id', $attendance->link_ids)
            ->pluck('title')
            ->toArray();
    }

    $platformText = !empty($platformNames)
        ? ' | Platforms: ' . implode(', ', $platformNames)
        : '';

    $user = User::find($request->user_id);

    // 🔥 SEND NOTIFICATION WITH PLATFORM NAMES
    $this->notifyAdmin($user, "Clocked OUT{$platformText}");

    return response()->json([
        'status'=>true,
        'message'=>'Clockout successful'
    ]);
}
private function notifyAdmin($user,$action)
{
    //echo "Notifying admins about: {$user->name} {$action}\n";exit;
    $notifyUsers = User::where('role','admin')->pluck('id');

    $firebase = new FirebaseNotificationService();

    foreach ($notifyUsers as $notifyUserId) {

        Notifications::create([
            'user_id' => $notifyUserId,
            'title'   => 'Attendance Update',
            'message' => "{$user->name} {$action}",
            'type'    => 'attendance',
            'is_read' => 0
        ]);

        $firebase->sendToUser(
            (string)$notifyUserId,
            'Attendance Update',
            "{$user->name} {$action}",
            ['user_id' => (string)$user->id]
        );
    }
}

public function report(Request $request)
{
    $userId = $request->user_id;

    $data = Attendance::where('user_id',$userId)
        ->orderBy('attendance_date','desc')
        ->get()
        ->map(function($a){

            return [
                'date'=>$a->attendance_date,
                'clock_in'=>$a->clock_in,
                'clock_out'=>$a->clock_out,
                'clockin_desc'=>$a->clockin_description,
                'clockout_desc'=>$a->clockout_description,
                'worked_hours'=>round($a->work_minutes/60,2),
                'status'=>$a->is_absent ? 'Absent':'Present'
            ];
        });

    return response()->json($data);
}

public function todayStatus($user_id)
{
    $attendance = Attendance::where('user_id',$user_id)
        ->whereDate('attendance_date',today())
        ->first();

    if(!$attendance){
        return response()->json([
            'status'=>'not_started',
            'message'=>'User not clocked in today'
        ]);
    }

    // if clocked in but not out
    if($attendance->clock_in && !$attendance->clock_out){
        return response()->json([
            'status'=>'clocked_in',
            'clock_in'=>$attendance->clock_in,
            'description'=>$attendance->clockin_description
        ]);
    }

    // finished day
    return response()->json([
        'status'=>$attendance->is_absent ? 'absent':'present',
        'clock_in'=>$attendance->clock_in,
        'clock_out'=>$attendance->clock_out,
        'worked_hours'=>round($attendance->work_minutes/60,2),
        'clockin_desc'=>$attendance->clockin_description,
        'clockout_desc'=>$attendance->clockout_description
    ]);
}


}
