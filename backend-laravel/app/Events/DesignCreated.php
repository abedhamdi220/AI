<?php

namespace App\Events;

use App\Models\Design;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DesignCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $design;

    /**
     * Create a new event instance.
     */
    public function __construct(Design $design)
    {
        $this->design = $design;
    }
}
