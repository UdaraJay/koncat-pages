<?php

namespace App\Notifications\Projects;

use App\Models\ProjectShare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectShared extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProjectShare $share)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->share->project;
        $sharer = $this->share->sharer;

        return (new MailMessage)
            ->subject(__('A project was shared with you'))
            ->replyTo($sharer->email, $sharer->name)
            ->line(__(':sharerName shared the :projectName project with you.', [
                'sharerName' => $sharer->name,
                'projectName' => $project->name,
            ]))
            ->line(__('Sender email: :sharerEmail', [
                'sharerEmail' => $sharer->email,
            ]))
            ->line(__('Log in or create an account with this email address to access it.'))
            ->action(
                __('Open project'),
                route('login', ['project_share' => $this->share->code]),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_share_id' => $this->share->id,
            'project_id' => $this->share->project_id,
            'project_name' => $this->share->project->name,
            'permission' => $this->share->permission->value,
        ];
    }
}
