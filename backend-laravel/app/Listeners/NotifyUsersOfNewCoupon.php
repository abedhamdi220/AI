<?php

namespace App\Listeners;

use App\Events\NewCouponCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyUsersOfNewCoupon implements ShouldQueue
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
    public function handle(NewCouponCreated $event): void
    {
        try {
            $discount = $event->coupon->discount_percentage;
            $code = $event->coupon->code;

            $title = '🎉 كوبون خصم جديد متاح الآن!';
            $message = "استخدم الكود ({$code}) للحصول على خصم بقيمة {$discount}% على طلباتك القادمة. لا تفوت الفرصة!";

            // جلب جميع المستخدمين وإرسال الإشعار
            User::chunk(100, function ($users) use ($title, $message) {
                foreach ($users as $user) {
                    Notification::createNotification(
                        $user->id,
                        $title,
                        $message,
                        'success' // نوع الإشعار نجاح/عرض
                    );
                }
            });

            Log::info("System Notification: Sent new coupon alert to all users. Code: {$code}");
        } catch (\Exception $error) {
            Log::error('Listener Error (NotifyUsersOfNewCoupon): ' . $error->getMessage());
        }
    }
}
