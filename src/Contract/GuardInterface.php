<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

use Sirix\Mezzio\Rbac\Exception\AuthorizationException;

interface GuardInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function allows(string $permission, array $context = []): bool;

    /**
     * @param array<string, mixed> $context
     */
    public function denies(string $permission, array $context = []): bool;

    /**
     * @param array<string, mixed> $context
     *
     * @throws AuthorizationException
     */
    public function authorize(string $permission, array $context = []): void;
}
