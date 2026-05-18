<?php

namespace App\Listeners;

use App\Events\UserDeletedByAdmin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogAdminUserDeletion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserDeletedByAdmin $event): void
    {
        // تسجيل عملية الحذف في السجلات للرجوع إليها مستقبلاً (Audit Trail)
        Log::info("Admin Action: User Deleted. ID: {$event->userId}, Username: {$event->username}, Email: {$event->email}");
    }
}
