<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\PermissionAssociation;
use Sirix\Mezzio\Rbac\Rule\AllowRule;

final class InMemoryPermissionStoreTest extends TestCase
{
    private InMemoryPermissionStore $store;

    protected function setUp(): void
    {
        $this->store = new InMemoryPermissionStore();
    }

    #[Test]
    public function addAndHasRole(): void
    {
        $this->store->addRole('admin');
        self::assertTrue($this->store->hasRole('admin'));
        self::assertFalse($this->store->hasRole('editor'));
    }

    #[Test]
    public function nextPriorityIncrements(): void
    {
        self::assertSame(1, $this->store->nextPriority());
        self::assertSame(2, $this->store->nextPriority());
        self::assertSame(3, $this->store->nextPriority());
    }

    #[Test]
    public function addAssociationForRole(): void
    {
        $this->store->addRole('admin');
        $association = new PermissionAssociation('admin', 'posts.*', AllowRule::class, 1, 1002);
        $this->store->addAssociation($association);

        $associations = $this->store->associationsForRole('admin');
        self::assertCount(1, $associations);
        self::assertSame($association, $associations[0]);
    }

    #[Test]
    public function associationsForUnknownRoleReturnsEmptyArray(): void
    {
        self::assertSame([], $this->store->associationsForRole('unknown'));
    }

    #[Test]
    public function multipleAssociationsForRole(): void
    {
        $this->store->addRole('admin');
        $this->store->addAssociation(new PermissionAssociation('admin', 'posts.*', AllowRule::class, 1, 1002));
        $this->store->addAssociation(new PermissionAssociation('admin', 'posts.read', AllowRule::class, 2, 2002));

        $associations = $this->store->associationsForRole('admin');
        self::assertCount(2, $associations);
    }
}
