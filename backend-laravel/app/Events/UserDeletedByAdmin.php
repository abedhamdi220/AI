<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDeletedByAdmin
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $username;
    public $email;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $username, $email)
    {
        $this->userId = $userId;
        $this->username = $username;
        $this->email = $email;
    }
}
