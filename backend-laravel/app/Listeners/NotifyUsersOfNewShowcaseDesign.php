<?php

namespace App\Listeners;

use App\Events\NewShowcaseDesignCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyUsersOfNewShowcaseDesign implements ShouldQueue
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
    public function handle(NewShowcaseDesignCreated $event): void
    {
        try {
            $designTitle = $event->design->title ?? 'تصميم جديد';
            $title = '✨ تصميم ملهم جديد أضيف للتو!';
            $message = "اكتشف إبداعاً جديداً في قسم التصاميم الملهمة: {$designTitle}. تصفحه الآن واستلهم أفكارك!";

            // جلب جميع المستخدمين (تم استخدام chunk لتفادي استهلاك الذاكرة إذا كان عدد المستخدمين كبيراً)
            User::chunk(100, function ($users) use ($title, $message) {
                foreach ($users as $user) {
                    Notification::createNotification(
                        $user->id,
                        $title,
                        $message,
                        'info'
                    );
                }
            });

            Log::info("System Notification: Sent new showcase design alert to all users.");
        } catch (\Exception $error) {
            Log::error('Listener Error (NotifyUsersOfNewShowcaseDesign): ' . $error->getMessage());
        }
    }
}
