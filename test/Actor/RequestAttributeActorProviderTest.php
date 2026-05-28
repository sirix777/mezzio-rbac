<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Actor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\Actor\GenericActorAdapter;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\Actor\RequestAttributeActorProvider;

final class RequestAttributeActorProviderTest extends TestCase
{
    #[Test]
    public function returnsRbacActorFromConfiguredRequestAttribute(): void
    {
        $actor = new Actor(['admin']);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('actor')->willReturn($actor);

        $requestAttributeActorProvider = new RequestAttributeActorProvider('actor', new GuestActor());

        self::assertSame($actor, $requestAttributeActorProvider->getActor($request));
    }

    #[Test]
    public function returnsGuestActorWhenAttributeIsMissing(): void
    {
        $guestActor = new GuestActor();
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('actor')->willReturn(null);

        $requestAttributeActorProvider = new RequestAttributeActorProvider('actor', $guestActor);

        self::assertSame($guestActor, $requestAttributeActorProvider->getActor($request));
    }

    #[Test]
    public function returnsGuestActorWhenAttributeIsNotActorLike(): void
    {
        $guestActor = new GuestActor();
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('actor')->willReturn('not-an-actor');

        $requestAttributeActorProvider = new RequestAttributeActorProvider('actor', $guestActor);

        self::assertSame($guestActor, $requestAttributeActorProvider->getActor($request));
    }

    #[Test]
    public function adaptsGenericActorWithGetRolesMethod(): void
    {
        $genericActor = new class {
            /**
             * @return list<string>
             */
            public function getRoles(): array
            {
                return ['admin'];
            }
        };

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('actor')->willReturn($genericActor);

        $requestAttributeActorProvider = new RequestAttributeActorProvider('actor', new GuestActor());
        $actor = $requestAttributeActorProvider->getActor($request);

        self::assertInstanceOf(GenericActorAdapter::class, $actor);
        self::assertSame(['admin'], $actor->getRoles());
    }
}
