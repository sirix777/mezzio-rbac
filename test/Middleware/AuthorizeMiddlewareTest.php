<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Middleware;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\RbacAttribute;

final class AuthorizeMiddlewareTest extends TestCase
{
    #[Test]
    public function passesThroughWhenNoPermissionAttributeOrRouteOption(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::never())->method('authorize');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value => $default,
                RouteResult::class => null,
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
    public function authorizesWhenPermissionAttributePresent(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $guard->expects(self::once())->method('authorize')
            ->with($request, 'posts.read', [])
        ;

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
    public function authorizesWhenPermissionRouteOptionPresent(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $guard->expects(self::once())->method('authorize')
            ->with($request, 'admin.access', [])
        ;

        $routeResult = $this->routeResult([
            RbacAttribute::Permission->value => 'admin.access',
            RbacAttribute::Context->value => [],
        ]);

        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value,
                RbacAttribute::Context->value => $default,
                RouteResult::class => $routeResult,
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
    public function requestAttributePermissionWinsOverRouteOptionPermission(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $guard->expects(self::once())->method('authorize')
            ->with($request, 'posts.read', [])
        ;

        $routeResult = $this->routeResult([
            RbacAttribute::Permission->value => 'admin.access',
        ]);

        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value => 'posts.read',
                RbacAttribute::Context->value => [],
                RouteResult::class => $routeResult,
                default => $default,
            })
        ;

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware = new AuthorizeMiddleware($guard);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function throwsOnAuthorizationFailure(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
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
        $guard = $this->createMock(RequestGuardInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $guard->expects(self::once())->method('authorize')
            ->with($request, 'posts.update', ['postId' => '123'])
        ;

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
    public function resolvesContextFromRouteOptions(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $guard->expects(self::once())->method('authorize')
            ->with($request, 'posts.update', ['postId' => '123'])
        ;

        $routeResult = $this->routeResult([
            RbacAttribute::Permission->value => 'posts.update',
            RbacAttribute::Context->value => ['postId' => 'id'],
        ]);

        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value,
                RbacAttribute::Context->value => $default,
                RouteResult::class => $routeResult,
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
    public function requestAttributeContextWinsOverRouteOptionContext(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $guard->expects(self::once())->method('authorize')
            ->with($request, 'posts.update', ['postId' => 'request-id'])
        ;

        $routeResult = $this->routeResult([
            RbacAttribute::Permission->value => 'posts.update',
            RbacAttribute::Context->value => ['postId' => 'route_id'],
        ]);

        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value => $default,
                RbacAttribute::Context->value => ['postId' => 'request_id'],
                RouteResult::class => $routeResult,
                'request_id' => 'request-id',
                'route_id' => 'route-id',
                default => $default,
            })
        ;

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware = new AuthorizeMiddleware($guard);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function passesThroughOnEmptyPermissionString(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::never())->method('authorize');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null) => match ($name) {
                RbacAttribute::Permission->value => '',
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

    /**
     * @param array<string, mixed> $options
     */
    private function routeResult(array $options): RouteResult
    {
        $route = new Route(
            '/test',
            new class implements MiddlewareInterface {
                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    return $handler->handle($request);
                }
            },
            ['GET'],
            'test',
        );
        $route->setOptions($options);

        return RouteResult::fromRoute($route);
    }
}
