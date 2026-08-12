<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Permissions;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;

final class PermissionsTest extends TestCase
{
    private Permissions $permissions;

    protected function setUp(): void
    {
        $permissionMatcher       = new PermissionMatcher();
        $inMemoryPermissionStore = new InMemoryPermissionStore();
        $this->permissions       = new Permissions($permissionMatcher, $inMemoryPermissionStore);
    }

    #[Test]
    public function addRole(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.*');

        self::assertTrue($this->permissions->bestAssociationForRole('admin', 'posts.read') instanceof PermissionAssociationInterface);
    }

    #[Test]
    public function throwsOnEmptyRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->permissions->addRole('');
    }

    #[Test]
    public function throwsOnEmptyPermissionPattern(): void
    {
        $this->permissions->addRole('admin');
        $this->expectException(InvalidArgumentException::class);
        $this->permissions->associate('admin', '');
    }

    #[Test]
    public function throwsOnAssociateBeforeAddRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->permissions->associate('admin', 'posts.*');
    }

    #[Test]
    public function associateWithAllowRule(): void
    {
        $this->permissions->addRole('editor');
        $this->permissions->associate('editor', 'posts.*', AllowRule::class);

        $association = $this->permissions->bestAssociationForRole('editor', 'posts.read');
        self::assertNotNull($association);
        self::assertSame(AllowRule::class, $association->getRule());
    }

    #[Test]
    public function associateWithForbidRule(): void
    {
        $this->permissions->addRole('editor');
        $this->permissions->associate('editor', 'posts.*', AllowRule::class);
        $this->permissions->associate('editor', 'posts.delete', ForbidRule::class);

        $readAssociation = $this->permissions->bestAssociationForRole('editor', 'posts.read');
        self::assertNotNull($readAssociation);
        self::assertSame(AllowRule::class, $readAssociation->getRule());

        $deleteAssociation = $this->permissions->bestAssociationForRole('editor', 'posts.delete');
        self::assertNotNull($deleteAssociation);
        self::assertSame(ForbidRule::class, $deleteAssociation->getRule());
    }

    #[Test]
    public function bestAssociationReturnsMostSpecific(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', '*.*', AllowRule::class);
        $this->permissions->associate('admin', 'posts.*', AllowRule::class);
        $this->permissions->associate('admin', 'posts.read', AllowRule::class);

        $association = $this->permissions->bestAssociationForRole('admin', 'posts.read');
        self::assertNotNull($association);
        self::assertSame('posts.read', $association->getPattern());
    }

    #[Test]
    public function bestAssociationReturnsNullForNoMatch(): void
    {
        $this->permissions->addRole('guest');
        $this->permissions->associate('guest', 'posts.read');

        self::assertNull($this->permissions->bestAssociationForRole('guest', 'admin.delete'));
    }

    #[Test]
    public function trimRoleAndPermissionPattern(): void
    {
        $this->permissions->addRole('  admin  ');
        $this->permissions->associate('  admin  ', '  posts.*  ');

        self::assertNotNull($this->permissions->bestAssociationForRole('admin', 'posts.read'));
    }
}
