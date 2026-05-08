<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Rule;

use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;

final readonly class AllowRule implements RuleInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function allows(ActorInterface $actor, string $permission, array $context): bool
    {
        return true;
    }
}
