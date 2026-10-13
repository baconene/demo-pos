<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /** Record a change to the existing audit_logs table. */
    public static function record(string $action, Model $model, ?array $old = null, ?array $new = null, ?string $description = null): void
    {
        try {
            AuditLog::create([
                'user_id'     => auth()->id(),
                'action'      => $action,
                'model_type'  => $model::class,
                'model_id'    => $model->getKey(),
                'old_values'  => $old,
                'new_values'  => $new,
                'ip_address'  => request()->ip(),
                'user_agent'  => substr((string) request()->userAgent(), 0, 255),
                'description' => $description,
            ]);
        } catch (\Throwable) {
            // auditing must never break the primary action
        }
    }
}
