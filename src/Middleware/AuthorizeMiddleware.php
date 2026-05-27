<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Middleware;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\RbacAttribute;
use stdClass;

use function is_array;
use function is_int;
use function is_string;

final readonly class AuthorizeMiddleware implements MiddlewareInterface
{
    public function __construct(private RequestGuardInterface $guard) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permission = $this->resolvePermission($request);

        if (null === $permission) {
            return $handler->handle($request);
        }

        $context = $this->resolveContext(
            $request,
            $this->resolveRawContext($request),
        );

        $this->guard->authorize($request, $permission, $context);

        return $handler->handle($request);
    }

    private function resolvePermission(ServerRequestInterface $request): ?string
    {
        $missing = new stdClass();
        $permission = $request->getAttribute(RbacAttribute::Permission->value, $missing);

        if ($permission !== $missing) {
            return is_string($permission) && '' !== $permission ? $permission : null;
        }

        $permission = $this->resolveRouteOption($request, RbacAttribute::Permission->value);

        return is_string($permission) && '' !== $permission ? $permission : null;
    }

    private function resolveRawContext(ServerRequestInterface $request): mixed
    {
        $missing = new stdClass();
        $context = $request->getAttribute(RbacAttribute::Context->value, $missing);

        if ($context !== $missing) {
            return $context;
        }

        return $this->resolveRouteOption($request, RbacAttribute::Context->value) ?? [];
    }

    private function resolveRouteOption(ServerRequestInterface $request, string $key): mixed
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult instanceof RouteResult || ! $routeResult->isSuccess()) {
            return null;
        }

        $route = $routeResult->getMatchedRoute();
        if (false === $route) {
            return null;
        }

        return $route->getOptions()[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveContext(ServerRequestInterface $request, mixed $rawContext): array
    {
        if (! is_array($rawContext)) {
            return [];
        }

        $context = [];

        foreach ($rawContext as $key => $attribute) {
            if (! is_string($attribute)) {
                continue;
            }

            if ('' === $attribute) {
                continue;
            }

            $contextKey = is_int($key) ? $attribute : $key;
            $context[$contextKey] = $request->getAttribute($attribute);
        }

        return $context;
    }
}
