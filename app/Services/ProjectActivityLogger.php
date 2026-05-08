<?php

namespace App\Services;

use App\Models\ProjectActivityLog;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectActivityLogger
{
    private static array $sensitiveKeys = [
        'password', 'remember_token', 'token', 'api_key',
        'secret', 'email_verified_at', 'firebase_uid',
    ];

    public static function log(
        Proyecto $project,
        string $action,
        string $module,
        string $title,
        ?string $description = null,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            ProjectActivityLog::create([
                'project_id'  => $project->id,
                'user_id'     => Auth::id(),
                'action'      => $action,
                'module'      => $module,
                'entity_type' => $entity ? class_basename($entity) : null,
                'entity_id'   => $entity?->getKey(),
                'title'       => mb_substr($title, 0, 500),
                'description' => $description,
                'old_values'  => $oldValues !== null ? self::sanitize(self::castValues($oldValues)) : null,
                'new_values'  => $newValues !== null ? self::sanitize(self::castValues($newValues)) : null,
                'ip_address'  => request()->ip(),
                'user_agent'  => mb_substr(request()->userAgent() ?? '', 0, 500),
            ]);
        } catch (\Throwable) {
            // Never break main flow because of audit log failures
        }
    }

    private static function sanitize(array $values): array
    {
        return array_diff_key($values, array_flip(self::$sensitiveKeys));
    }

    private static function castValues(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if ($value instanceof \Carbon\Carbon || $value instanceof \DateTime) {
                $result[$key] = $value->format('Y-m-d');
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $result[$key] = (string) $value;
            } elseif (is_array($value) || is_string($value) || is_numeric($value) || is_bool($value) || is_null($value)) {
                $result[$key] = $value;
            } else {
                $result[$key] = (string) $value;
            }
        }
        return $result;
    }
}
