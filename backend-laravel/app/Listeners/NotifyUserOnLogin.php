<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyUserOnLogin implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(UserLoggedIn $event): void
    {
        try {
            $title = 'تسجيل دخول ناجح';
            $message = "مرحباً بك مجدداً يا {$event->user->username}! يسعدنا تواجدك معنا.";

            Notification::createNotification(
                $event->user->id,
                $title,
                $message,
                'info'
            );
        } catch (\Exception $error) {
            Log::error('Listener Error (NotifyUserOnLogin): ' . $error->getMessage());
        }
    }
}
