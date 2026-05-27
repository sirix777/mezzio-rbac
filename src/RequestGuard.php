<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;

final readonly class RequestGuard implements RequestGuardInterface
{
    public function __construct(private RequestActorProviderInterface $actorProvider, private AuthorizationEvaluator $evaluator) {}

    /**
     * @param array<string, mixed> $context
     */
    public function allows(ServerRequestInterface $request, string $permission, array $context = []): bool
    {
        return $this->evaluator->allows(
            $this->actorProvider->getActor($request),
            $permission,
            $context,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denies(ServerRequestInterface $request, string $permission, array $context = []): bool
    {
        return ! $this->allows($request, $permission, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws AuthorizationException
     */
    public function authorize(ServerRequestInterface $request, string $permission, array $context = []): void
    {
        if ($this->denies($request, $permission, $context)) {
            throw new AuthorizationException($permission, 'Forbidden');
        }
    }
}
