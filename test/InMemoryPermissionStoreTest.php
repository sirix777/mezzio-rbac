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
    private InMemoryPermissionStore $inMemoryPermissionStore;

    protected function setUp(): void
    {
        $this->inMemoryPermissionStore = new InMemoryPermissionStore();
    }

    #[Test]
    public function addAndHasRole(): void
    {
        $this->inMemoryPermissionStore->addRole('admin');
        self::assertTrue($this->inMemoryPermissionStore->hasRole('admin'));
        self::assertFalse($this->inMemoryPermissionStore->hasRole('editor'));
    }

    #[Test]
    public function nextPriorityIncrements(): void
    {
        self::assertSame(1, $this->inMemoryPermissionStore->nextPriority());
        self::assertSame(2, $this->inMemoryPermissionStore->nextPriority());
        self::assertSame(3, $this->inMemoryPermissionStore->nextPriority());
    }

    #[Test]
    public function addAssociationForRole(): void
    {
        $this->inMemoryPermissionStore->addRole('admin');
        $permissionAssociation = new PermissionAssociation('admin', 'posts.*', AllowRule::class, 1, 1002);
        $this->inMemoryPermissionStore->addAssociation($permissionAssociation);

        $associations = $this->inMemoryPermissionStore->associationsForRole('admin');
        self::assertCount(1, $associations);
        self::assertSame($permissionAssociation, $associations[0]);
    }

    #[Test]
    public function associationsForUnknownRoleReturnsEmptyArray(): void
    {
        self::assertSame([], $this->inMemoryPermissionStore->associationsForRole('unknown'));
    }

    #[Test]
    public function multipleAssociationsForRole(): void
    {
        $this->inMemoryPermissionStore->addRole('admin');
        $this->inMemoryPermissionStore->addAssociation(new PermissionAssociation('admin', 'posts.*', AllowRule::class, 1, 1002));
        $this->inMemoryPermissionStore->addAssociation(new PermissionAssociation('admin', 'posts.read', AllowRule::class, 2, 2002));

        $associations = $this->inMemoryPermissionStore->associationsForRole('admin');
        self::assertCount(2, $associations);
    }
}
