<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;

interface RequestGuardInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function allows(ServerRequestInterface $request, string $permission, array $context = []): bool;

    /**
     * @param array<string, mixed> $context
     */
    public function denies(ServerRequestInterface $request, string $permission, array $context = []): bool;

    /**
     * @param array<string, mixed> $context
     *
     * @throws AuthorizationException
     */
    public function authorize(ServerRequestInterface $request, string $permission, array $context = []): void;
}
