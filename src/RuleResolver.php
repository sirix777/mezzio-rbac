<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;

use function class_exists;

final readonly class RuleResolver
{
    public function __construct(private ContainerInterface $container, private RuleInterface $defaultRule) {}

    /**
     * @param null|class-string<RuleInterface>|RuleInterface $rule
     */
    public function resolve(RuleInterface|string|null $rule): RuleInterface
    {
        if ($rule instanceof RuleInterface) {
            return $rule;
        }

        if (null === $rule) {
            return $this->defaultRule;
        }

        if ($this->container->has($rule)) {
            $resolved = $this->container->get($rule);
            if ($resolved instanceof RuleInterface) {
                return $resolved;
            }
        }

        if (class_exists($rule)) {
            return new $rule();
        }

        throw new InvalidArgumentException("Rule '{$rule}' is not resolvable.");
    }
}
