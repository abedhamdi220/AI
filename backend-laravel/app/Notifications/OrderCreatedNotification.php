<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    // تحديد القنوات (في حالتنا نريد تخزينها في قاعدة البيانات فقط من أجل الـ API)
    public function via($notifiable)
    {
        return ['database'];
    }

    // شكل البيانات التي سيتم تخزينها وإرسالها للـ Frontend
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'order_created',
            'title' => 'تم استلام طلبك بنجاح!',
            'message' => 'جاري معالجة طلبك رقم #' . $this->order->id,
            'order_id' => $this->order->id,
            'icon' => 'shopping-bag' // يمكن للفرونت اند استخدامها لاختيار الأيقونة المناسبة
        ];
    }
}

