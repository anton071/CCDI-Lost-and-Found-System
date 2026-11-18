<?php

namespace App\Notifications;

use App\Models\ItemReport;
use Illuminate\Notifications\Notification;

class ReportStatusChanged extends Notification
{
    public function __construct(public ItemReport $report, public string $headline, public ?string $detail = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->headline, 'body' => $this->detail, 'url' => route('reports.show', $this->report)];
    }
}
