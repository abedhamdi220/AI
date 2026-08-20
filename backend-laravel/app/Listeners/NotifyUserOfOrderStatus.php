<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyUserOfOrderStatus implements ShouldQueue
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
    public function handle(OrderStatusUpdated $event): void
    {
        try {
            // ترجمة حالة الطلب لعرضها للمستخدم بشكل مناسب
            $statusText = '';
            switch ($event->status) {
                case 'processing':
                    $statusText = 'قيد المعالجة والتجهيز';
                    break;
                case 'completed':
                    $statusText = 'مكتمل وجاهز';
                    break;
                case 'cancelled':
                    $statusText = 'ملغي';
                    break;
                case 'pending':
                default:
                    $statusText = 'قيد الانتظار';
                    break;
            }

            $title = 'تحديث حالة الطلب';
            $message = "تم تحديث حالة طلبك رقم {$event->order->id} ليصبح: {$statusText}.";

            // استخدام دالة الإشعارات الموجودة لديك في الموديل
            Notification::createNotification(
                $event->order->user_id,
                $title,
                $message,
                'info'
            );

        } catch (\Exception $error) {
            \Log::error('Listener Error (NotifyUserOfOrderStatus): ' . $error->getMessage());
        }
    }
}
