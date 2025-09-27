<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class ChatBotSystemMessage
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $sessionId,
        public string $message
    ) {}
}
