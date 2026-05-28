<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;

final class AuthorizationExceptionTest extends TestCase
{
    #[Test]
    public function defaultValues(): void
    {
        $authorizationException = new AuthorizationException('posts.read');

        self::assertSame('posts.read', $authorizationException->getPermission());
        self::assertSame(403, $authorizationException->getStatusCode());
        self::assertSame('Forbidden', $authorizationException->getMessage());
        self::assertSame('Forbidden', $authorizationException->getPublicMessage());
        self::assertSame([], $authorizationException->getHeaders());
    }

    #[Test]
    public function customValues(): void
    {
        $authorizationException = new AuthorizationException(
            'posts.delete',
            'Access denied',
            ['X-Custom' => 'value'],
            'You cannot delete this post',
        );

        self::assertSame('posts.delete', $authorizationException->getPermission());
        self::assertSame(403, $authorizationException->getStatusCode());
        self::assertSame('Access denied', $authorizationException->getMessage());
        self::assertSame('You cannot delete this post', $authorizationException->getPublicMessage());
        self::assertSame(['X-Custom' => 'value'], $authorizationException->getHeaders());
    }

    #[Test]
    public function publicMessageFallsBackToMessage(): void
    {
        $authorizationException = new AuthorizationException('posts.read', 'Custom message');

        self::assertSame('Custom message', $authorizationException->getPublicMessage());
    }
}
