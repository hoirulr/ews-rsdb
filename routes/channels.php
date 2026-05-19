<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ews-alerts.rsud', function ($user): bool {
    return $user->hasRole(['admin_rsud', 'admin_sistem']);
});
