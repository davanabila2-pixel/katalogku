<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(
        string $action,
        ?Model $entity = null,
        ?string $description = null,
        ?array $metadata = null,
        ?User $user = null,
        ?int $storeId = null,
    ): void {
        if ($metadata !== null) {
            $metadata = self::scrub($metadata);
        }

        \App\Models\AuditLog::create([
            'user_id' => $user?->id ?? auth()->id(),
            'store_id' => $storeId,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /** Buang kunci sensitif agar tidak pernah tersimpan di audit log. */
    protected static function scrub(array $metadata): array
    {
        $forbidden = ['password', 'token', 'secret', 'client_secret', 'refresh_token', 'access_token'];
        foreach ($metadata as $key => $value) {
            foreach ($forbidden as $bad) {
                if (is_string($key) && str_contains(strtolower($key), $bad)) {
                    unset($metadata[$key]);
                    continue 2;
                }
            }
            if (is_array($value)) {
                $metadata[$key] = self::scrub($value);
            }
        }

        return $metadata;
    }
}
