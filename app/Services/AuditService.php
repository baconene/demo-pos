<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(
        string $action,
        string $modelType = null,
        int $modelId = null,
        array $oldValues = null,
        array $newValues = null,
        string $description = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }

    public function logModelChange(
        string $action,
        object $model,
        array $originalData,
        array $newData
    ): AuditLog {
        return $this->log(
            $action,
            get_class($model),
            $model->id,
            $originalData,
            $newData
        );
    }
}
