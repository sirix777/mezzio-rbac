<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Permissions;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\RuleResolver;

final class AuthorizationEvaluatorTest extends TestCase
{
    private Permissions $permissions;
    private AuthorizationEvaluator $authorizationEvaluator;

    protected function setUp(): void
    {
        $this->permissions = new Permissions(
            new PermissionMatcher(),
            new InMemoryPermissionStore(),
        );

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $this->authorizationEvaluator = new AuthorizationEvaluator(
            $this->permissions,
            new RuleResolver($container, new AllowRule()),
        );
    }

    #[Test]
    public function allowsWhenAnyActorRoleHasMatchingPermission(): void
    {
        $this->permissions->addRole('editor');
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.read');

        self::assertTrue($this->authorizationEvaluator->allows(
            new Actor(['editor', 'admin']),
            'posts.read',
        ));
    }

    #[Test]
    public function deniesWhenActorHasNoRoles(): void
    {
        self::assertFalse($this->authorizationEvaluator->allows(new Actor([]), 'posts.read'));
    }

    #[Test]
    public function continuesEvaluatingRolesAfterRuleDenies(): void
    {
        $this->permissions->addRole('editor');
        $this->permissions->addRole('admin');
        $this->permissions->associate('editor', 'posts.update', new class implements RuleInterface {
            public function allows(ActorInterface $actor, string $permission, array $context): bool
            {
                return false;
            }
        });
        $this->permissions->associate('admin', 'posts.update');

        self::assertTrue($this->authorizationEvaluator->allows(
            new Actor(['editor', 'admin']),
            'posts.update',
        ));
    }

    #[Test]
    public function passesContextIntoResolvedRule(): void
    {
        $this->permissions->addRole('admin');
        $this->permissions->associate('admin', 'posts.update', new class implements RuleInterface {
            public function allows(ActorInterface $actor, string $permission, array $context): bool
            {
                return '123' === ($context['postId'] ?? null);
            }
        });

        self::assertTrue($this->authorizationEvaluator->allows(
            new Actor(['admin']),
            'posts.update',
            ['postId' => '123'],
        ));
        self::assertFalse($this->authorizationEvaluator->allows(
            new Actor(['admin']),
            'posts.update',
            ['postId' => '456'],
        ));
    }
}
