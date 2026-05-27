<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\ConfigProvider;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionMapInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionsInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Extractor\CanAttributeExtractor;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;

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
        self::assertArrayHasKey(PermissionMapInterface::class, $dependencies['factories']);
    }

    #[Test]
    public function registersPermissionStoreAsInvokable(): void
    {
        $provider = new ConfigProvider();
        $dependencies = $provider->getDependencies();

        self::assertArrayHasKey(PermissionStoreInterface::class, $dependencies['factories']);
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
}
