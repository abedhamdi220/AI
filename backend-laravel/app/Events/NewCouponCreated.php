<?php

namespace App\Events;

use App\Models\Coupon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCouponCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $coupon;

    /**
     * Create a new event instance.
     */
    public function __construct(Coupon $coupon)
    {
        $this->coupon = $coupon;
    }
}
