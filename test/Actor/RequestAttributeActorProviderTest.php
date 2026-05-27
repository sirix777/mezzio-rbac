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

        $provider = new RequestAttributeActorProvider('actor', new GuestActor());

        self::assertSame($actor, $provider->getActor($request));
    }

    #[Test]
    public function returnsGuestActorWhenAttributeIsMissing(): void
    {
        $guest = new GuestActor();
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('actor')->willReturn(null);

        $provider = new RequestAttributeActorProvider('actor', $guest);

        self::assertSame($guest, $provider->getActor($request));
    }

    #[Test]
    public function returnsGuestActorWhenAttributeIsNotActorLike(): void
    {
        $guest = new GuestActor();
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('actor')->willReturn('not-an-actor');

        $provider = new RequestAttributeActorProvider('actor', $guest);

        self::assertSame($guest, $provider->getActor($request));
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

        $provider = new RequestAttributeActorProvider('actor', new GuestActor());
        $actor = $provider->getActor($request);

        self::assertInstanceOf(GenericActorAdapter::class, $actor);
        self::assertSame(['admin'], $actor->getRoles());
    }
}
