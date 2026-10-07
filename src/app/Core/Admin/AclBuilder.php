<?php

namespace App\Core\Admin;

use App\Core\Admin\ItemPermission\ItemAssertion;
use App\Core\Admin\ItemPermission\ItemRestrictionMap;
use Nette\Security\Permission;

/**
 * Sestaví ACL z čistých dat (bez DB), aby šla pravidla otestovat.
 */
final class AclBuilder
{
    /**
     * @param list<string>                                                $roles
     * @param list<string>                                                $modules
     * @param list<array{role: string, module: string, privilege: string}> $rules
     */
    public static function build(
        array $roles,
        array $modules,
        array $rules,
        string $superAdminRole,
        ItemRestrictionMap $restrictions,
    ): Permission {
        $acl = new Permission();
        foreach ($roles as $role) {
            $acl->addRole($role);
        }
        foreach ($modules as $module) {
            $acl->addResource($module);
        }

        foreach ($rules as $rule) {
            $allowedIds = $restrictions->getAllowedIds($rule['role'], $rule['module']);
            $acl->allow(
                $rule['role'],
                $rule['module'],
                $rule['privilege'],
                $allowedIds === null ? null : new ItemAssertion($allowedIds),
            );
        }

        $acl->allow($superAdminRole);

        return $acl;
    }
}
