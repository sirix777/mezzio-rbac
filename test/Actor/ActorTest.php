<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Actor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\Actor\GuestActor;

final class ActorTest extends TestCase
{
    #[Test]
    public function actorReturnsRoles(): void
    {
        $actor = new Actor(['admin', 'editor']);
        self::assertSame(['admin', 'editor'], $actor->getRoles());
    }

    #[Test]
    public function actorWithEmptyRoles(): void
    {
        $actor = new Actor([]);
        self::assertSame([], $actor->getRoles());
    }

    #[Test]
    public function guestActorReturnsGuestRole(): void
    {
        $guestActor = new GuestActor();
        self::assertSame(['guest'], $guestActor->getRoles());
    }
}
