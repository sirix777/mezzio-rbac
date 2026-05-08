<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\RbacAttribute;

final class AuthorizeMiddlewareTest extends TestCase
{
    #[Test]
    public function passesThroughWhenNoPermissionAttribute(): void
    {
        $guard = $this->createMock(GuardInterface::class);
        $guard->expects(self::never())->method('authorize');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with(RbacAttribute::Permission->value)->willReturn(null);

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware = new AuthorizeMiddleware($guard);
        $result = $middleware->process($request, $handler);

        self::assertSame($response, $result);
    }

    #[Test]
    public function authorizesWhenPermissionAttributePresent(): void
    {
        $guard = $this->createMock(GuardInterface::class);
        $guard->expects(self::once())->method('authorize')
            ->with('posts.read', [])
        ;

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value => 'posts.read',
                RbacAttribute::Context->value => [],
                default => $default,
            })
        ;

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware = new AuthorizeMiddleware($guard);
        $result = $middleware->process($request, $handler);

        self::assertSame($response, $result);
    }

    #[Test]
    public function throwsOnAuthorizationFailure(): void
    {
        $guard = $this->createMock(GuardInterface::class);
        $guard->method('authorize')->willThrowException(new AuthorizationException('posts.delete'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value => 'posts.delete',
                RbacAttribute::Context->value => [],
                default => $default,
            })
        ;

        $handler = $this->createMock(RequestHandlerInterface::class);

        $middleware = new AuthorizeMiddleware($guard);

        $this->expectException(AuthorizationException::class);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function resolvesContextFromRequestAttributes(): void
    {
        $guard = $this->createMock(GuardInterface::class);
        $guard->expects(self::once())->method('authorize')
            ->with('posts.update', ['postId' => '123'])
        ;

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value => 'posts.update',
                RbacAttribute::Context->value => ['postId' => 'id'],
                'id' => '123',
                default => $default,
            })
        ;

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware = new AuthorizeMiddleware($guard);
        $result = $middleware->process($request, $handler);

        self::assertSame($response, $result);
    }

    #[Test]
    public function passesThroughOnEmptyPermissionString(): void
    {
        $guard = $this->createMock(GuardInterface::class);
        $guard->expects(self::never())->method('authorize');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with(RbacAttribute::Permission->value)->willReturn('');

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware = new AuthorizeMiddleware($guard);
        $result = $middleware->process($request, $handler);

        self::assertSame($response, $result);
    }
}
