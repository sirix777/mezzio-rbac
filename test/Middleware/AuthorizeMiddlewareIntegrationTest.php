<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Middleware;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\Actor\RequestAttributeActorProvider;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;
use Sirix\Mezzio\Rbac\InMemoryPermissionStore;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Permissions;
use Sirix\Mezzio\Rbac\RbacAttribute;
use Sirix\Mezzio\Rbac\RequestGuard;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\RuleResolver;

final class AuthorizeMiddlewareIntegrationTest extends TestCase
{
    #[Test]
    public function deniesNonAdminActorWhenPermissionExistsOnlyInMatchedRouteOptions(): void
    {
        $middleware = $this->middlewareWithAdminAccessPermission();
        $request = $this->requestWithRoutePermissionAndAuthenticationActor(['user']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Forbidden');

        $middleware->process($request, $handler);
    }

    #[Test]
    public function allowsAdminActorWhenPermissionExistsOnlyInMatchedRouteOptions(): void
    {
        $middleware = $this->middlewareWithAdminAccessPermission();
        $request = $this->requestWithRoutePermissionAndAuthenticationActor(['admin']);

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    #[Test]
    public function mapsRouteOptionContextFromRequestAttributesIntoAuthorizationRule(): void
    {
        $store = new InMemoryPermissionStore();
        $permissions = new Permissions(new PermissionMatcher(), $store);
        $permissions->addRole('admin');
        $permissions->associate('admin', 'posts.update', new class implements RuleInterface {
            public function allows(ActorInterface $actor, string $permission, array $context): bool
            {
                return '123' === ($context['postId'] ?? null);
            }
        });

        $middleware = $this->middleware($permissions);
        $request = $this->requestWithRoutePermissionAndAuthenticationActor(
            ['admin'],
            'posts.update',
            ['postId' => 'id'],
            ['id' => '123'],
        );

        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    private function middlewareWithAdminAccessPermission(): AuthorizeMiddleware
    {
        $store = new InMemoryPermissionStore();
        $permissions = new Permissions(new PermissionMatcher(), $store);
        $permissions->addRole('admin');
        $permissions->associate('admin', 'admin.access');

        return $this->middleware($permissions);
    }

    private function middleware(Permissions $permissions): AuthorizeMiddleware
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        return new AuthorizeMiddleware(new RequestGuard(
            new RequestAttributeActorProvider('sirix.authentication.actor', new GuestActor()),
            new AuthorizationEvaluator(
                $permissions,
                new RuleResolver($container, new AllowRule()),
            ),
        ));
    }

    /**
     * @param list<string>              $roles
     * @param array<int|string, string> $context
     * @param array<string, mixed>      $requestAttributes
     */
    private function requestWithRoutePermissionAndAuthenticationActor(
        array $roles,
        string $permission = 'admin.access',
        array $context = [],
        array $requestAttributes = [],
    ): ServerRequestInterface {
        $routeResult = $this->routeResult([
            RbacAttribute::Permission->value => $permission,
            RbacAttribute::Context->value => $context,
        ]);

        $actor = new class($roles) {
            /**
             * @param list<string> $roles
             */
            public function __construct(private readonly array $roles) {}

            /**
             * @return list<string>
             */
            public function getRoles(): array
            {
                return $this->roles;
            }
        };

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(static fn (string $name, $default = null): mixed => match ($name) {
                RbacAttribute::Permission->value,
                RbacAttribute::Context->value => $default,
                RouteResult::class => $routeResult,
                'sirix.authentication.actor' => $actor,
                default => $requestAttributes[$name] ?? $default,
            })
        ;

        return $request;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function routeResult(array $options): RouteResult
    {
        $route = new Route(
            '/admin',
            new class implements MiddlewareInterface {
                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    return $handler->handle($request);
                }
            },
            ['GET'],
            'admin',
        );
        $route->setOptions($options);

        return RouteResult::fromRoute($route);
    }
}
