<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;

final readonly class RequestGuard implements RequestGuardInterface
{
    public function __construct(
        private RequestActorProviderInterface $requestActorProvider,
        private AuthorizationEvaluator $authorizationEvaluator
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function allows(ServerRequestInterface $serverRequest, string $permission, array $context = []): bool
    {
        return $this->authorizationEvaluator->allows(
            $this->requestActorProvider->getActor($serverRequest),
            $permission,
            $context,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denies(ServerRequestInterface $serverRequest, string $permission, array $context = []): bool
    {
        return ! $this->allows($serverRequest, $permission, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws AuthorizationException
     */
    public function authorize(ServerRequestInterface $serverRequest, string $permission, array $context = []): void
    {
        if ($this->denies($serverRequest, $permission, $context)) {
            throw new AuthorizationException($permission, 'Forbidden');
        }
    }
}
