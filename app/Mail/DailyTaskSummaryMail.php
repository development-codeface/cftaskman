<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class DailyTaskSummaryMail extends Mailable
{
    public $todayCount;
    public $overdueCount;
    public $userName;

    public function __construct($userName, $todayCount, $overdueCount)
    {
        $this->userName = $userName;
        $this->todayCount = $todayCount;
        $this->overdueCount = $overdueCount;
    }

    public function build()
    {
        return $this->subject('Daily Task Summary')
            ->view('emails.daily_task_summary');
    }
}
