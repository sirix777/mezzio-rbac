<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Permissions;
use Sirix\Mezzio\Rbac\RequestGuard;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;
use Sirix\Mezzio\Rbac\RuleResolver;

final class RequestGuardTest extends TestCase
{
    private RequestGuard $guard;
    private Permissions $permissions;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $matcher = new PermissionMatcher();
        $store = new InMemoryPermissionStore();
        $this->permissions = new Permissions($matcher, $store);
        $this->request = $this->createMock(ServerRequestInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturnCallback(static fn (string $id) => match ($id) {
            AllowRule::class => new AllowRule(),
            ForbidRule::class => new ForbidRule(),
            default => null,
        });

        $ruleResolver = new RuleResolver($container, new AllowRule());
        $actorProvider = $this->createMock(RequestActorProviderInterface::class);
        $actorProvider->method('getActor')->with($this->request)->willReturn(new Actor(['admin']));

        $this->guard = new RequestGuard(
            $actorProvider,
            new AuthorizationEvaluator($this->permissions, $ruleResolver),
        );
    }

    #[Test]
    public function allowsWhenPermissionGranted(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.*');

        self::assertTrue($this->guard->allows($this->request, 'posts.read'));
    }

    #[Test]
    public function deniesWhenPermissionNotGranted(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.read');

        self::assertFalse($this->guard->allows($this->request, 'posts.delete'));
        self::assertTrue($this->guard->denies($this->request, 'posts.delete'));
    }

    #[Test]
    public function authorizeThrowsOnDeniedPermission(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.read');

        $this->expectException(AuthorizationException::class);
        $this->guard->authorize($this->request, 'posts.delete');
    }

    #[Test]
    public function forbidRuleDeniesPermission(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.*', AllowRule::class);
        $this->permissions->associate('admin', 'posts.delete', ForbidRule::class);

        self::assertTrue($this->guard->allows($this->request, 'posts.read'));
        self::assertFalse($this->guard->allows($this->request, 'posts.delete'));
    }

    #[Test]
    public function passesContextIntoRules(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.update', new class implements RuleInterface {
            public function allows(ActorInterface $actor, string $permission, array $context): bool
            {
                return '123' === ($context['postId'] ?? null);
            }
        });

        self::assertTrue($this->guard->allows($this->request, 'posts.update', ['postId' => '123']));
        self::assertFalse($this->guard->allows($this->request, 'posts.update', ['postId' => '456']));
    }
}
