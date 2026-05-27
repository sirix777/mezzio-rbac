<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\ConfigProvider;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionLookupInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionsInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Extractor\CanAttributeExtractor;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Permissions;
use Sirix\Mezzio\Rbac\RequestGuard;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;
use Sirix\Mezzio\Rbac\RuleResolver;

final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function returnsDependenciesArray(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey('factories', $config['dependencies']);
        self::assertArrayHasKey('invokables', $config['dependencies']);
    }

    #[Test]
    public function registersGuardFactory(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertArrayHasKey(GuardInterface::class, $dependencies['factories']);
        self::assertArrayHasKey(RequestGuardInterface::class, $dependencies['factories']);
        self::assertArrayHasKey(AuthorizationEvaluator::class, $dependencies['factories']);
    }

    #[Test]
    public function registersPermissionsFactory(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertArrayHasKey(PermissionsInterface::class, $dependencies['factories']);
        self::assertSame(PermissionsInterface::class, $dependencies['aliases'][PermissionLookupInterface::class]);
    }

    #[Test]
    public function registersPermissionStoreAsInvokableAlias(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertSame(InMemoryPermissionStore::class, $dependencies['aliases'][PermissionStoreInterface::class]);
        self::assertArrayHasKey(InMemoryPermissionStore::class, $dependencies['invokables']);
    }

    #[Test]
    public function registersInvokables(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertArrayHasKey(PermissionMatcher::class, $dependencies['invokables']);
        self::assertArrayHasKey(GuestActor::class, $dependencies['invokables']);
        self::assertArrayHasKey(AllowRule::class, $dependencies['invokables']);
        self::assertArrayHasKey(ForbidRule::class, $dependencies['invokables']);
        self::assertArrayHasKey(CanAttributeExtractor::class, $dependencies['invokables']);
    }

    #[Test]
    public function registersActorProvider(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertArrayHasKey(ActorProviderInterface::class, $dependencies['factories']);
        self::assertArrayHasKey(RequestActorProviderInterface::class, $dependencies['factories']);
    }

    #[Test]
    public function registersRules(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertArrayHasKey(AllowRule::class, $dependencies['invokables']);
        self::assertArrayHasKey(ForbidRule::class, $dependencies['invokables']);
    }

    #[Test]
    public function registersMiddleware(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertArrayHasKey(AuthorizeMiddleware::class, $dependencies['factories']);
    }

    #[Test]
    public function configuredServiceManagerResolvesCoreServices(): void
    {
        $container = new ServiceManager((new ConfigProvider())->getDependencies());

        self::assertInstanceOf(InMemoryPermissionStore::class, $container->get(PermissionStoreInterface::class));
        self::assertInstanceOf(Permissions::class, $container->get(PermissionsInterface::class));
        self::assertSame(
            $container->get(PermissionsInterface::class),
            $container->get(PermissionLookupInterface::class),
        );
        self::assertInstanceOf(RuleResolver::class, $container->get(RuleResolver::class));
        self::assertInstanceOf(AuthorizationEvaluator::class, $container->get(AuthorizationEvaluator::class));
        self::assertInstanceOf(RequestGuard::class, $container->get(RequestGuardInterface::class));
        self::assertInstanceOf(AuthorizeMiddleware::class, $container->get(AuthorizeMiddleware::class));
    }

    #[Test]
    public function configuredContainerSharesPermissionsBetweenWriteAndReadContracts(): void
    {
        $container = new ServiceManager((new ConfigProvider())->getDependencies());

        $permissions = $container->get(PermissionsInterface::class);
        self::assertInstanceOf(PermissionsInterface::class, $permissions);
        $permissions->addRole('admin');
        $permissions->associate('admin', 'posts.read');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null): mixed => match ($name) {
                'sirix.authentication.actor' => new Actor(['admin']),
                default => $default,
            })
        ;

        $guard = $container->get(RequestGuardInterface::class);
        self::assertInstanceOf(RequestGuardInterface::class, $guard);
        self::assertTrue($guard->allows($request, 'posts.read'));
    }
}
