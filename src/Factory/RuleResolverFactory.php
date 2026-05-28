<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\RuleResolver;

final class RuleResolverFactory
{
    /**
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): RuleResolver
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new RuleResolver(
            $container,
            $containerResolver->get(AllowRule::class),
        );
    }
}
