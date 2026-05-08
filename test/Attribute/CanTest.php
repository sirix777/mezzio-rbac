<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Attribute;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\Attribute\Can;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\RbacAttribute;
use Sirix\Mezzio\Routing\Attributes\Contract\RouteAttributeModifierInterface;

final class CanTest extends TestCase
{
    #[Test]
    public function implementsRouteAttributeModifierInterface(): void
    {
        $can = new Can('posts.read');
        self::assertInstanceOf(RouteAttributeModifierInterface::class, $can);
    }

    #[Test]
    public function returnsMiddleware(): void
    {
        $can = new Can('posts.read');
        self::assertSame([AuthorizeMiddleware::class], $can->getMiddleware());
    }

    #[Test]
    public function returnsDefaults(): void
    {
        $can = new Can('posts.update', ['post' => 'id']);
        $defaults = $can->getDefaults();

        self::assertArrayHasKey(RbacAttribute::Permission->value, $defaults);
        self::assertSame('posts.update', $defaults[RbacAttribute::Permission->value]);

        self::assertArrayHasKey(RbacAttribute::Context->value, $defaults);
        self::assertSame(['post' => 'id'], $defaults[RbacAttribute::Context->value]);
    }
}
