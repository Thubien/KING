<?php

namespace App\Listeners;

use App\Models\UserLoginLog;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        UserLoginLog::createLog(
            userId: $event->user->id,
            ipAddress: request()->ip() ?? '0.0.0.0',
            userAgent: request()->userAgent() ?? 'Unknown',
            isSuccessful: true
        );
    }
}
