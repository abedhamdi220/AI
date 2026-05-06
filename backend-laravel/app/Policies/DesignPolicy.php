<?php

namespace App\Policies;

use App\Models\Design;
use App\Models\User;

class DesignPolicy
{
    /**
     * هل يمكن للمستخدم عرض هذا التصميم؟
     */
    public function view(User $user, Design $design): bool
    {
        // يمكن للمستخدم رؤية تصميمه فقط، أو إذا كان مديراً
        return $user->id === $design->user_id || $user->is_admin;
    }

    /**
     * هل يمكن للمستخدم حذف هذا التصميم؟
     */
    public function delete(User $user, Design $design): bool
    {
        // المدير لا يحذف تصاميم العملاء مباشرة من حسابه، العميل يحذف تصميمه فقط
        return $user->id === $design->user_id;
    }
}
