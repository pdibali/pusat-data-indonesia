<?php

namespace App\Services;

use App\Models\User;

class SubscriptionAccessService
{
    public function hasActiveAccess(User $user): bool
    {
        $limits = app(SubscriptionLimitsService::class);

        if (! $limits->hasActiveSubscription($user)) {
            return false;
        }

        $maxConcurrentSessions = $limits->getMaxConcurrentSessions($user);
        $activeSessions = \App\Models\UserSession::where('user_id', $user->user_id)
            ->where('is_active', true)
            ->count();

        return $activeSessions <= $maxConcurrentSessions;
    }
}
