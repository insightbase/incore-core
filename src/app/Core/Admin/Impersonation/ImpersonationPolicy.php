<?php

namespace App\Core\Admin\Impersonation;

use App\Model\Enum\RoleEnum;

/**
 * Kdo se smí přihlásit jako kdo: jen super admin, ne sám za sebe a ne za jiného super admina.
 */
final class ImpersonationPolicy
{
    /**
     * @param list<string> $actorRoles role přihlášeného uživatele
     */
    public static function canImpersonate(array $actorRoles, int $actorId, int $targetId, string $targetRole): bool
    {
        return in_array(RoleEnum::SUPER_ADMIN->value, $actorRoles, true)
            && $targetId !== $actorId
            && $targetRole !== RoleEnum::SUPER_ADMIN->value;
    }
}
