<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

enum RbacAttribute: string
{
    case Permission = 'sirix.rbac.permission';
    case Context = 'sirix.rbac.context';
}
