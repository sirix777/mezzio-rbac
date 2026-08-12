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
        $configProvider = new ConfigProvider();
        $config         = $configProvider();

        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey('factories', $config['dependencies']);
        self::assertArrayHasKey('invokables', $config['dependencies']);
    }

    #[Test]
    public function registersGuardFactory(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey(GuardInterface::class, $dependencies['factories']);
        self::assertArrayHasKey(RequestGuardInterface::class, $dependencies['factories']);
        self::assertArrayHasKey(AuthorizationEvaluator::class, $dependencies['factories']);
    }

    #[Test]
    public function registersPermissionsFactory(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey(PermissionsInterface::class, $dependencies['factories']);
        self::assertSame(PermissionsInterface::class, $dependencies['aliases'][PermissionLookupInterface::class]);
    }

    #[Test]
    public function registersPermissionStoreAsInvokableAlias(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertSame(InMemoryPermissionStore::class, $dependencies['aliases'][PermissionStoreInterface::class]);
        self::assertArrayHasKey(InMemoryPermissionStore::class, $dependencies['invokables']);
    }

    #[Test]
    public function registersInvokables(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey(PermissionMatcher::class, $dependencies['invokables']);
        self::assertArrayHasKey(GuestActor::class, $dependencies['invokables']);
        self::assertArrayHasKey(AllowRule::class, $dependencies['invokables']);
        self::assertArrayHasKey(ForbidRule::class, $dependencies['invokables']);
        self::assertArrayHasKey(CanAttributeExtractor::class, $dependencies['invokables']);
    }

    #[Test]
    public function registersActorProvider(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey(ActorProviderInterface::class, $dependencies['factories']);
        self::assertArrayHasKey(RequestActorProviderInterface::class, $dependencies['factories']);
    }

    #[Test]
    public function registersRules(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey(AllowRule::class, $dependencies['invokables']);
        self::assertArrayHasKey(ForbidRule::class, $dependencies['invokables']);
    }

    #[Test]
    public function registersMiddleware(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey(AuthorizeMiddleware::class, $dependencies['factories']);
    }

    #[Test]
    public function configuredServiceManagerResolvesCoreServices(): void
    {
        $serviceManager = new ServiceManager((new ConfigProvider())->getDependencies());

        self::assertInstanceOf(InMemoryPermissionStore::class, $serviceManager->get(PermissionStoreInterface::class));
        self::assertInstanceOf(Permissions::class, $serviceManager->get(PermissionsInterface::class));
        self::assertSame(
            $serviceManager->get(PermissionsInterface::class),
            $serviceManager->get(PermissionLookupInterface::class),
        );
        self::assertInstanceOf(RuleResolver::class, $serviceManager->get(RuleResolver::class));
        self::assertInstanceOf(AuthorizationEvaluator::class, $serviceManager->get(AuthorizationEvaluator::class));
        self::assertInstanceOf(RequestGuard::class, $serviceManager->get(RequestGuardInterface::class));
        self::assertInstanceOf(AuthorizeMiddleware::class, $serviceManager->get(AuthorizeMiddleware::class));
    }

    #[Test]
    public function configuredContainerSharesPermissionsBetweenWriteAndReadContracts(): void
    {
        $serviceManager = new ServiceManager((new ConfigProvider())->getDependencies());

        $permissions = $serviceManager->get(PermissionsInterface::class);
        self::assertInstanceOf(PermissionsInterface::class, $permissions);
        $permissions->addRole('admin');
        $permissions->associate('admin', 'posts.read');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null): mixed => match ($name) {
                'sirix.authentication.actor' => new Actor(['admin']),
                default                      => $default,
            })
        ;

        $guard = $serviceManager->get(RequestGuardInterface::class);
        self::assertInstanceOf(RequestGuardInterface::class, $guard);
        self::assertTrue($guard->allows($request, 'posts.read'));
    }
}
