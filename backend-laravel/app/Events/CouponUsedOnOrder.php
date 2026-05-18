<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CouponUsedOnOrder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $couponCode;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, $couponCode)
    {
        $this->order = $order;
        $this->couponCode = $couponCode;
    }
}
