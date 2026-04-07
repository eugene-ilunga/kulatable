<?php

namespace Modules\MultiPOS\Notifications;

use App\Notifications\BaseNotification;
use App\Models\NotificationSetting;
use Modules\MultiPOS\Entities\PosMachine;
use Illuminate\Support\Facades\Log;

class PosMachineRegistrationRequest extends BaseNotification
{
    protected $posMachine;
    protected $notificationSetting;

    /**
     * Create a new notification instance.
     *
     * @param PosMachine $posMachine
     */
    public function __construct(PosMachine $posMachine)
    {
        // Ensure relationships are loaded
        $posMachine->loadMissing(['branch.restaurant', 'creator']);
        
        $this->posMachine = $posMachine;
        $this->restaurant = $posMachine->branch->restaurant;
        $this->notificationSetting = NotificationSetting::where('type', 'pos_machine_request')
            ->where('restaurant_id', $posMachine->branch->restaurant_id)
            ->first();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Always send database notification
        $channels = ['database'];
        
        // Check if email notification is enabled
        $sendEmail = false;
        
        if ($this->notificationSetting) {
            $sendEmail = $this->notificationSetting->send_email == 1;
        } else {
            // Default to true if setting doesn't exist (for new installations)
            $sendEmail = true;
        }
        
        // Add mail channel if email is enabled and user has email
        if ($sendEmail && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $build = parent::build($notifiable);
        $settingsRoute = route('settings.index') . '?tab=multipos';
        
        return $build
            ->subject(__('multipos::messages.notifications.pos_request.subject'))
            ->greeting(__('app.hello') . ' ' . $notifiable->name . ',')
            ->line(__('multipos::messages.notifications.pos_request.text1'))
            ->line(__('multipos::messages.notifications.pos_request.text2'))
            ->line(__('multipos::messages.table.alias') . ': ' . ($this->posMachine->alias ?? __('multipos::messages.table.no_alias')))
            ->line(__('multipos::messages.table.machine_id') . ': ' . $this->posMachine->public_id)
            ->line(__('multipos::messages.dashboard.branch_label') . ': ' . $this->posMachine->branch->name)
            ->line(__('modules.staff.name') . ': ' . ($this->posMachine->creator ? $this->posMachine->creator->name : __('app.n_a')))
            ->line(__('app.date') . ': ' . $this->posMachine->created_at->translatedFormat('d M, Y h:i A'))
            ->action(__('multipos::messages.notifications.pos_request.action'), $settingsRoute)
            ->line(__('multipos::messages.notifications.pos_request.text3'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'pos_machine_id' => $this->posMachine->id,
            'machine_alias' => $this->posMachine->alias,
            'machine_public_id' => $this->posMachine->public_id,
            'branch_id' => $this->posMachine->branch_id,
            'branch_name' => $this->posMachine->branch->name,
            'created_by' => $this->posMachine->created_by,
            'creator_name' => $this->posMachine->creator ? $this->posMachine->creator->name : __('app.n_a'),
            'status' => $this->posMachine->status,
            'created_at' => $this->posMachine->created_at->toDateTimeString(),
            'url' => route('settings.index') . '?tab=multipos',
        ];
    }
}
