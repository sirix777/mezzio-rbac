<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Middleware;

use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\RbacAttribute;

use function array_key_exists;

final class AuthorizeMiddlewareTest extends TestCase
{
    use RouteResultFactoryTrait;

    #[Test]
    public function passesThroughWhenNoPermissionAttributeOrRouteOption(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::never())->method('authorize');

        $response            = $this->createMock(ResponseInterface::class);
        $request             = $this->request();
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    #[Test]
    public function authorizesWhenPermissionAttributePresent(): void
    {
        $request = $this->request([
            RbacAttribute::Permission->value => 'posts.read',
            RbacAttribute::Context->value    => [],
        ]);

        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::once())->method('authorize')->with($request, 'posts.read', []);

        $response            = $this->createMock(ResponseInterface::class);
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    #[Test]
    public function authorizesWhenPermissionRouteOptionPresent(): void
    {
        $request = $this->request([], $this->routeResult([
            RbacAttribute::Permission->value => 'admin.access',
            RbacAttribute::Context->value    => [],
        ]));

        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::once())->method('authorize')->with($request, 'admin.access', []);

        $response            = $this->createMock(ResponseInterface::class);
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    #[Test]
    public function requestAttributePermissionWinsOverRouteOptionPermission(): void
    {
        $request = $this->request([
            RbacAttribute::Permission->value => 'posts.read',
            RbacAttribute::Context->value    => [],
        ], $this->routeResult([
            RbacAttribute::Permission->value => 'admin.access',
        ]));

        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::once())->method('authorize')->with($request, 'posts.read', []);

        $response            = $this->createMock(ResponseInterface::class);
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    #[Test]
    public function throwsOnAuthorizationFailure(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->method('authorize')->willThrowException(new AuthorizationException('posts.delete'));

        $request = $this->request([
            RbacAttribute::Permission->value => 'posts.delete',
            RbacAttribute::Context->value    => [],
        ]);

        $this->expectException(AuthorizationException::class);

        (new AuthorizeMiddleware($guard))->process($request, $this->createMock(RequestHandlerInterface::class));
    }

    #[Test]
    public function resolvesContextFromRequestAttributes(): void
    {
        $request = $this->request([
            RbacAttribute::Permission->value => 'posts.update',
            RbacAttribute::Context->value    => [
                'postId' => 'id',
            ],
            'id'                             => '123',
        ]);

        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::once())->method('authorize')->with($request, 'posts.update', [
            'postId' => '123',
        ]);

        $response            = $this->createMock(ResponseInterface::class);
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    #[Test]
    public function resolvesContextFromRouteOptions(): void
    {
        $request = $this->request([
            'id' => '123',
        ], $this->routeResult([
            RbacAttribute::Permission->value => 'posts.update',
            RbacAttribute::Context->value    => [
                'postId' => 'id',
            ],
        ]));

        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::once())->method('authorize')->with($request, 'posts.update', [
            'postId' => '123',
        ]);

        $response            = $this->createMock(ResponseInterface::class);
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    #[Test]
    public function requestAttributeContextWinsOverRouteOptionContext(): void
    {
        $request = $this->request([
            RbacAttribute::Context->value => [
                'postId' => 'request_id',
            ],
            'request_id'                  => 'request-id',
            'route_id'                    => 'route-id',
        ], $this->routeResult([
            RbacAttribute::Permission->value => 'posts.update',
            RbacAttribute::Context->value    => [
                'postId' => 'route_id',
            ],
        ]));

        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::once())->method('authorize')->with($request, 'posts.update', [
            'postId' => 'request-id',
        ]);

        $response            = $this->createMock(ResponseInterface::class);
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    #[Test]
    public function passesThroughOnEmptyPermissionString(): void
    {
        $guard = $this->createMock(RequestGuardInterface::class);
        $guard->expects(self::never())->method('authorize');

        $request = $this->request([
            RbacAttribute::Permission->value => '',
        ]);

        $response            = $this->createMock(ResponseInterface::class);
        $authorizeMiddleware = new AuthorizeMiddleware($guard);

        self::assertSame($response, $authorizeMiddleware->process($request, $this->handlerReturning($response)));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function request(array $attributes = [], ?RouteResult $routeResult = null): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static function(string $name, mixed $default = null) use ($attributes, $routeResult): mixed {
                if (array_key_exists($name, $attributes)) {
                    return $attributes[$name];
                }

                if (RouteResult::class === $name) {
                    return $routeResult ?? $default;
                }

                return $default;
            })
        ;

        return $request;
    }

    private function handlerReturning(ResponseInterface $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }
}
