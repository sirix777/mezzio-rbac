<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\PermissionLookupInterface;
use Sirix\Mezzio\Rbac\RuleResolver;

final class AuthorizationEvaluatorFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AuthorizationEvaluator
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new AuthorizationEvaluator(
            $containerResolver->get(PermissionLookupInterface::class),
            $containerResolver->get(RuleResolver::class),
        );
    }
}
