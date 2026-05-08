<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Attribute;

use Attribute;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\RbacAttribute;
use Sirix\Mezzio\Routing\Attributes\Contract\RouteAttributeModifierInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Can implements RouteAttributeModifierInterface
{
    /**
     * @param array<int|string, string> $context
     */
    public function __construct(public string $permission, public array $context = []) {}

    public function getMiddleware(): array
    {
        return [AuthorizeMiddleware::class];
    }

    public function getDefaults(): array
    {
        return [
            RbacAttribute::Permission->value => $this->permission,
            RbacAttribute::Context->value => $this->context,
        ];
    }
}
