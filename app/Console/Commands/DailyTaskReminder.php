<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tasks;
use App\Models\Notifications;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\DailyTaskSummaryMail;
use Illuminate\Support\Facades\Mail;

class DailyTaskReminder extends Command
{
    protected $signature = 'tasks:daily-reminder';
    protected $description = 'Send daily reminders for today & overdue tasks';

    public function handle_backup()
    {
        $today = Carbon::today();

        // 🔔 Get today tasks OR overdue pending tasks
        $tasks = Tasks::whereNotNull('assigned_to')
            ->whereIn('status', ['todo', 'pending', 'ongoing'])
            ->where(function ($q) use ($today) {
                $q->whereDate('start_date', '<=', $today)
                  ->whereDate('end_date', '>=', $today)
                  ->orWhereDate('end_date', '<', $today);
            })
            ->with('assignedUser:id,name')
            ->get();

        $firebase = new FirebaseNotificationService();

        foreach ($tasks as $task) {

            $isOverdue = $task->end_date && Carbon::parse($task->end_date)->lt($today);

            $title = $isOverdue ? 'Overdue Task Reminder' : 'Today Task Reminder';

            $message = $isOverdue
                ? "Task '{$task->title}' is overdue. Please update it."
                : "You have a task scheduled today: {$task->title}";

            // Save notification
            Notifications::create([
                'user_id' => $task->assigned_to,
                'title'   => $title,
                'message' => $message,
                'type'    => 'task',
                'is_read' => 0
            ]);

            // Firebase push
            $firebase->sendToUser(
                (string)$task->assigned_to,
                $title,
                $message,
                ['task_id' => (string)$task->id]
            );
        }

        $this->info('Daily task reminders sent successfully.');
    }


public function handle()
{
    $today = Carbon::today();

    $firebase = new FirebaseNotificationService();

    $users = User::whereHas('tasks', function ($q) use ($today) {
        $q->whereIn('status', ['todo', 'pending', 'ongoing'])
          ->where(function ($sub) use ($today) {
              $sub->whereDate('end_date', $today)
                  ->orWhereDate('end_date', '<', $today);
          });
    })->get();

    foreach ($users as $user) {

        // ✅ Today tasks
        $todayCount = Tasks::where('assigned_to', $user->id)
            ->whereIn('status', ['todo', 'pending', 'ongoing'])
            ->whereDate('end_date', $today)
            ->count();

        // ✅ Overdue tasks
        $overdueCount = Tasks::where('assigned_to', $user->id)
            ->whereIn('status', ['todo', 'pending', 'ongoing'])
            ->whereDate('end_date', '<', $today)
            ->count();

        if ($todayCount === 0 && $overdueCount === 0) {
            continue;
        }

        $message = "You have {$todayCount} task(s) today & {$overdueCount} overdue task(s).";

        // 🔔 Save notification
        Notifications::create([
            'user_id' => $user->id,
            'title'   => 'Daily Task Summary',
            'message' => $message,
            'type'    => 'task',
            'is_read' => 0
        ]);

        // 🔔 Firebase push
        $firebase->sendToUser(
            (string)$user->id,
            'Daily Task Summary',
            $message,
            [
                'today_tasks'   => (string)$todayCount,
                'overdue_tasks' => (string)$overdueCount
            ]
        );

        // 📧 Email
        if (!empty($user->email)) {
            Mail::to($user->email)->send(
                new DailyTaskSummaryMail(
                    $user->name,
                    $todayCount,
                    $overdueCount
                )
            );
        }
    }

    $this->info('Daily summary notifications sent.');
}

}
