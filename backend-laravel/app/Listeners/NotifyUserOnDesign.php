<?php

namespace App\Listeners;

use App\Events\DesignCreated;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyUserOnDesign implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(DesignCreated $event): void
    {
        try {
            $title = 'تم إنشاء تصميمك الجديد 🎨';
            $message = "إبداع مذهل! تم حفظ تصميمك الجديد بنجاح في معرض تصاميمك. يمكنك الآن طلبه كمنتج.";

            Notification::createNotification(
                $event->design->user_id,
                $title,
                $message,
                'success'
            );
        } catch (\Exception $error) {
            Log::error('Listener Error (NotifyUserOnDesign): ' . $error->getMessage());
        }
    }
}
