<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;
use Sirix\Mezzio\Rbac\Guard;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Permissions;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;
use Sirix\Mezzio\Rbac\RuleResolver;

final class GuardTest extends TestCase
{
    private Guard $guard;
    private Permissions $permissions;

    protected function setUp(): void
    {
        $permissionMatcher = new PermissionMatcher();
        $inMemoryPermissionStore = new InMemoryPermissionStore();
        $this->permissions = new Permissions($permissionMatcher, $inMemoryPermissionStore);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturnCallback(static fn (string $id) => match ($id) {
            AllowRule::class => new AllowRule(),
            ForbidRule::class => new ForbidRule(),
            default => null,
        });

        $ruleResolver = new RuleResolver($container, new AllowRule());
        $actorProvider = $this->createMock(ActorProviderInterface::class);
        $actorProvider->method('getActor')->willReturn(new Actor(['admin']));

        $this->guard = new Guard(
            $actorProvider,
            new AuthorizationEvaluator($this->permissions, $ruleResolver),
        );
    }

    #[Test]
    public function allowsWhenPermissionGranted(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.*');

        self::assertTrue($this->guard->allows('posts.read'));
    }

    #[Test]
    public function deniesWhenPermissionNotGranted(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.read');

        self::assertFalse($this->guard->allows('posts.delete'));
        self::assertTrue($this->guard->denies('posts.delete'));
    }

    #[Test]
    public function authorizeThrowsOnDeniedPermission(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.read');

        $this->expectException(AuthorizationException::class);
        $this->guard->authorize('posts.delete');
    }

    #[Test]
    public function authorizeDoesNotThrowOnAllowedPermission(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.*');

        $this->guard->authorize('posts.read');
        self::assertTrue($this->guard->allows('posts.read'));
    }

    #[Test]
    public function forbidRuleDeniesPermission(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.*', AllowRule::class);
        $this->permissions->associate('admin', 'posts.delete', ForbidRule::class);

        self::assertTrue($this->guard->allows('posts.read'));
        self::assertFalse($this->guard->allows('posts.delete'));
    }

    #[Test]
    public function deniesReturnsOppositeOfAllows(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.read');

        self::assertFalse($this->guard->denies('posts.read'));
        self::assertTrue($this->guard->denies('posts.write'));
    }
}
