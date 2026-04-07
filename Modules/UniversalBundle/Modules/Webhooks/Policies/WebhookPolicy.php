<?php

namespace Modules\Webhooks\Policies;

use App\Models\User;
use Modules\Webhooks\Entities\Webhook;

class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user) || $user->can('View Webhook Logs');
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $this->belongsToTenant($user, $webhook) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $this->belongsToTenant($user, $webhook) && $this->canManage($user);
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $this->belongsToTenant($user, $webhook) && $this->canManage($user);
    }

    public function test(User $user, Webhook $webhook): bool
    {
        return $this->belongsToTenant($user, $webhook) && ($this->canManage($user) || $user->can('Send Webhook Test'));
    }

    private function canManage(User $user): bool
    {
        return $user->can('Manage Webhooks');
    }

    private function belongsToTenant(User $user, Webhook $webhook): bool
    {
        if (is_null($user->restaurant_id)) {
            // Super admin can manage everything
            return true;
        }

        return $webhook->restaurant_id === $user->restaurant_id;
    }
}
