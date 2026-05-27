<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\PermissionMapInterface;
use Sirix\Mezzio\Rbac\RuleResolver;

final class AuthorizationEvaluatorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AuthorizationEvaluator
    {
        return new AuthorizationEvaluator(
            $container->get(PermissionMapInterface::class),
            $container->get(RuleResolver::class),
        );
    }
}
