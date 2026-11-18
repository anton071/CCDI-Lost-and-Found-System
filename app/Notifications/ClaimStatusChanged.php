<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Notifications\Notification;

class ClaimStatusChanged extends Notification
{
    public function __construct(public Claim $claim, public string $headline, public ?string $detail = null, public bool $forAdmin = false) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline,
            'body' => $this->detail,
            'url' => $this->forAdmin ? route('admin.claims.show', $this->claim) : route('claims.show', $this->claim),
        ];
    }
}
