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
        $exception = new AuthorizationException('posts.read');

        self::assertSame('posts.read', $exception->getPermission());
        self::assertSame(403, $exception->getStatusCode());
        self::assertSame('Forbidden', $exception->getMessage());
        self::assertSame('Forbidden', $exception->getPublicMessage());
        self::assertSame([], $exception->getHeaders());
    }

    #[Test]
    public function customValues(): void
    {
        $exception = new AuthorizationException(
            'posts.delete',
            'Access denied',
            ['X-Custom' => 'value'],
            'You cannot delete this post',
        );

        self::assertSame('posts.delete', $exception->getPermission());
        self::assertSame(403, $exception->getStatusCode());
        self::assertSame('Access denied', $exception->getMessage());
        self::assertSame('You cannot delete this post', $exception->getPublicMessage());
        self::assertSame(['X-Custom' => 'value'], $exception->getHeaders());
    }

    #[Test]
    public function publicMessageFallsBackToMessage(): void
    {
        $exception = new AuthorizationException('posts.read', 'Custom message');

        self::assertSame('Custom message', $exception->getPublicMessage());
    }
}
