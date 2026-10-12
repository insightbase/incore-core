<?php

namespace App\UI\Admin\Role\Exception;

/** Uživatel (mimo super admina) se pokusil změnit oprávnění vlastní role. */
class OwnRoleAuthorizationException extends \Exception {}
