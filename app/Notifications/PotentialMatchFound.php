<?php

namespace App\Notifications;

use App\Models\ImageMatchResult;
use Illuminate\Notifications\Notification;

class PotentialMatchFound extends Notification
{
    public function __construct(public ImageMatchResult $match) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Possible match for your lost item',
            'body' => 'A published found-item report may resemble "'.$this->match->lostReport->item_name.'". Review it - this is not a confirmation of ownership.',
            'url' => route('reports.show', $this->match->lostReport),
        ];
    }
}
