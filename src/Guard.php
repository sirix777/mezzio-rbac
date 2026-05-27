<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;

final readonly class Guard implements GuardInterface
{
    public function __construct(private ActorProviderInterface $actorProvider, private AuthorizationEvaluator $evaluator) {}

    /**
     * @param array<string, mixed> $context
     */
    public function allows(string $permission, array $context = []): bool
    {
        return $this->evaluator->allows(
            $this->actorProvider->getActor(),
            $permission,
            $context,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denies(string $permission, array $context = []): bool
    {
        return ! $this->allows($permission, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws AuthorizationException
     */
    public function authorize(string $permission, array $context = []): void
    {
        if ($this->denies($permission, $context)) {
            throw new AuthorizationException($permission, 'Forbidden');
        }
    }
}
