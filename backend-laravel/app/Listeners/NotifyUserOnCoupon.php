<?php

namespace App\Listeners;

use App\Events\CouponUsedOnOrder;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyUserOnCoupon implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(CouponUsedOnOrder $event): void
    {
        try {
            $title = 'توفير رائع! 🎉';
            $message = "لقد قمت باستخدام الكوبون ({$event->couponCode}) بنجاح في طلبك الأخير. استمتع بالخصم!";

            Notification::createNotification(
                $event->order->user_id,
                $title,
                $message,
                'success'
            );
        } catch (\Exception $error) {
            Log::error('Listener Error (NotifyUserOnCoupon): ' . $error->getMessage());
        }
    }
}
