<?php

namespace App\Console\Commands;
use App\Models\Attendance;
use Illuminate\Console\Command;

class AutoCheckoutAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-checkout-attendance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
   public function handle()
    {
        $records = Attendance::whereNull('clock_out')
            ->whereDate('attendance_date',today())
            ->get();

        foreach($records as $a){

            $a->clock_out = today()->setTime(23,59,0);
            $a->auto_checkout = 1;

            $minutes = $a->clock_in->diffInMinutes($a->clock_out);

            $a->work_minutes = $minutes;
            $a->is_absent = 1;

            $a->save();
        }
    }

}
