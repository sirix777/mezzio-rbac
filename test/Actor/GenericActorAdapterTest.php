<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Actor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\Actor\GenericActorAdapter;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;

final class GenericActorAdapterTest extends TestCase
{
    #[Test]
    public function implementsActorInterface(): void
    {
        $genericActorAdapter = new GenericActorAdapter(new class {
            /**
             * @return list<string>
             */
            public function getRoles(): array
            {
                return [];
            }
        });

        self::assertInstanceOf(ActorInterface::class, $genericActorAdapter);
    }

    #[Test]
    public function exposesStringRolesFromGenericActor(): void
    {
        $genericActorAdapter = new GenericActorAdapter(new class {
            /**
             * @return list<string>
             */
            public function getRoles(): array
            {
                return ['admin', 'editor'];
            }
        });

        self::assertSame(['admin', 'editor'], $genericActorAdapter->getRoles());
    }

    #[Test]
    public function filtersInvalidRoleValuesAndNormalizesKeys(): void
    {
        $genericActorAdapter = new GenericActorAdapter(new class {
            /**
             * @return array<int, mixed>
             */
            public function getRoles(): array
            {
                return [10 => 'admin', 11 => 123, 12 => 'editor', 13 => null];
            }
        });

        self::assertSame(['admin', 'editor'], $genericActorAdapter->getRoles());
    }

    #[Test]
    public function returnsEmptyRolesForInvalidRolePayload(): void
    {
        $genericActorAdapter = new GenericActorAdapter(new class {
            public function getRoles(): string
            {
                return 'admin';
            }
        });

        self::assertSame([], $genericActorAdapter->getRoles());
    }
}
