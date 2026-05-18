<?php

namespace App\Events;

use App\Models\ShowcaseDesign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewShowcaseDesignCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $design;

    /**
     * Create a new event instance.
     */
    public function __construct(ShowcaseDesign $design)
    {
        $this->design = $design;
    }
}
