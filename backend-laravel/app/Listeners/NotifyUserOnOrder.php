<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyUserOnOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(OrderPlaced $event): void
    {
        try {
            $title = 'تم استلام طلبك بنجاح';
            // نستخدم جزء من الـ ID ليكون مقروءاً للمستخدم
            $shortId = substr($event->order->id, 0, 8);
            $message = "شكراً لك! تم استلام طلبك رقم #{$shortId} بنجاح. نحن نعمل على تجهيزه الآن.";

            Notification::createNotification(
                $event->order->user_id,
                $title,
                $message,
                'success'
            );
        } catch (\Exception $error) {
            Log::error('Listener Error (NotifyUserOnOrder): ' . $error->getMessage());
        }
    }
}
