<?php

namespace LaravelEnso\VueDatatable\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ExportDoneNotification extends Notification
{
    use Dispatchable, Queueable;

    public $filePath;
    public $filename;
    public $dataExport;

    public function __construct(string $filePath, string $filename, $dataExport)
    {
        $this->filePath = $filePath;
        $this->filename = $filename;
        $this->dataExport = $dataExport;
        $this->link = optional($this->dataExport)->temporaryLink();
        $this->queue = config('enso.datatable.queues.notifications');
    }

    public function via($notifiable)
    {
        return array_merge(['mail'], config('enso.datatable.export.notifications'));
    }

    public function toBroadcast($notifiable)
    {
        return (new BroadcastMessage([
            'level' => 'success',
            'title' => __('Export Done'),
            'body' => $this->link
                ? __('Export available for download').': '.__($this->filename)
                : __('Export emailed').': '.__($this->filename),
            'icon' => 'file-excel',
        ]))->onQueue(config('enso.datatable.queues.notifications'));
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage())
            ->subject(__(config('app.name')).': '.__('Table Export Notification'))
            ->markdown('laravel-enso/vuedatatable::emails.export', [
                'name' => $notifiable->person->appellative
                    ?: $notifiable->person->name,
                'filename' => __($this->filename),
                'entries' => optional($this->dataExport)->entries,
                'link' => $this->link,
            ]);

        if (! $this->link) {
            $mail->attach(\Storage::path($this->filePath));
        }

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'body' => $this->link
                ? __('Export available for download').': '.__($this->filename)
                : __('Export emailed').': '.__($this->filename),
            'icon' => 'file-excel',
            'path' => $this->link
                ? '/files'
                : null,
        ];
    }
}
