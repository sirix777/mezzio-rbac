<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;
use Sirix\Mezzio\Rbac\RuleResolver;

final class RuleResolverTest extends TestCase
{
    #[Test]
    public function resolvesRuleInstance(): void
    {
        $container    = $this->createMock(ContainerInterface::class);
        $ruleResolver = new RuleResolver($container, new AllowRule());

        $forbidRule = new ForbidRule();
        self::assertSame($forbidRule, $ruleResolver->resolve($forbidRule));
    }

    #[Test]
    public function resolvesNullToDefaultRule(): void
    {
        $container    = $this->createMock(ContainerInterface::class);
        $allowRule    = new AllowRule();
        $ruleResolver = new RuleResolver($container, $allowRule);

        self::assertSame($allowRule, $ruleResolver->resolve(null));
    }

    #[Test]
    public function resolvesClassStringFromContainer(): void
    {
        $allowRule = new AllowRule();
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(AllowRule::class)->willReturn(true);
        $container->method('get')->with(AllowRule::class)->willReturn($allowRule);

        $ruleResolver = new RuleResolver($container, new ForbidRule());
        self::assertSame($allowRule, $ruleResolver->resolve(AllowRule::class));
    }

    #[Test]
    public function resolvesClassStringByInstantiation(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $ruleResolver = new RuleResolver($container, new ForbidRule());
        $rule         = $ruleResolver->resolve(AllowRule::class);

        self::assertInstanceOf(AllowRule::class, $rule);
    }

    #[Test]
    public function throwsOnUnresolvableRule(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $ruleResolver = new RuleResolver($container, new AllowRule());

        $this->expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type
        $ruleResolver->resolve('NonExistentRule');
    }

    #[Test]
    public function throwsWhenContainerReturnsNonRuleAndClassDoesNotExist(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('NonExistentRule')->willReturn(true);
        $container->method('get')->with('NonExistentRule')->willReturn('not-a-rule');

        $ruleResolver = new RuleResolver($container, new ForbidRule());

        $this->expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type
        $ruleResolver->resolve('NonExistentRule');
    }
}
