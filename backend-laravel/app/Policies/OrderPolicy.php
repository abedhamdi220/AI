<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * هل يمكن للمستخدم عرض تفاصيل هذا الطلب؟
     */
    public function view(User $user, Order $order): bool
    {
        // العميل يرى طلبه فقط، بينما المدير يرى كل الطلبات
        return $user->id === $order->user_id || $user->is_admin;
    }
}
