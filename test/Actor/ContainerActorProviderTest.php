<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Actor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Mezzio\Rbac\Actor\ContainerActorProvider;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;

final class ContainerActorProviderTest extends TestCase
{
    #[Test]
    public function returnsGuestActorWhenNoActorInContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(ActorInterface::class)->willReturn(false);
        $guestActor = new GuestActor();

        $provider = new ContainerActorProvider($container, $guestActor);
        $actor = $provider->getActor();

        self::assertSame(['guest'], $actor->getRoles());
    }

    #[Test]
    public function returnsActorFromContainer(): void
    {
        $actor = $this->createMock(ActorInterface::class);
        $actor->method('getRoles')->willReturn(['admin']);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(ActorInterface::class)->willReturn(true);
        $container->method('get')->with(ActorInterface::class)->willReturn($actor);

        $provider = new ContainerActorProvider($container, new GuestActor());
        $result = $provider->getActor();

        self::assertSame(['admin'], $result->getRoles());
    }

    #[Test]
    public function returnsGuestActorWhenContainerReturnsNonActor(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(ActorInterface::class)->willReturn(true);
        $container->method('get')->with(ActorInterface::class)->willReturn('not-an-actor');

        $provider = new ContainerActorProvider($container, new GuestActor());
        $actor = $provider->getActor();

        self::assertSame(['guest'], $actor->getRoles());
    }
}
