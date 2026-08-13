<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Middleware;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;
use Sirix\Mezzio\Rbac\RbacAttribute;
use stdClass;

use function is_array;
use function is_int;
use function is_string;
use function trim;

final readonly class AuthorizeMiddleware implements MiddlewareInterface
{
    public function __construct(private RequestGuardInterface $requestGuard, private bool $strict = true) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permission = $this->resolvePermission($request);

        if (null === $permission) {
            if ($this->strict) {
                throw new AuthorizationException(
                    permission: '',
                    message: 'Authorization metadata is required.',
                    publicMessage: 'Forbidden',
                );
            }

            return $handler->handle($request);
        }

        $context = $this->resolveContext(
            $request,
            $this->resolveRawContext($request),
        );

        $this->requestGuard->authorize($request, $permission, $context);

        return $handler->handle($request);
    }

    private function resolvePermission(ServerRequestInterface $serverRequest): ?string
    {
        $missing    = new stdClass();
        $permission = $serverRequest->getAttribute(RbacAttribute::Permission->value, $missing);

        if ($permission !== $missing) {
            $permission = $this->nonEmptyPermission($permission);
            if (null !== $permission) {
                return $permission;
            }
        }

        $permission = $this->resolveRouteOption($serverRequest, RbacAttribute::Permission->value);

        return $this->nonEmptyPermission($permission);
    }

    private function resolveRawContext(ServerRequestInterface $serverRequest): mixed
    {
        $missing = new stdClass();
        $context = $serverRequest->getAttribute(RbacAttribute::Context->value, $missing);

        if ($context !== $missing) {
            return $context;
        }

        return $this->resolveRouteOption($serverRequest, RbacAttribute::Context->value) ?? [];
    }

    private function resolveRouteOption(ServerRequestInterface $serverRequest, string $key): mixed
    {
        $routeResult = $serverRequest->getAttribute(RouteResult::class);
        if (! $routeResult instanceof RouteResult || ! $routeResult->isSuccess()) {
            return null;
        }

        $route = $routeResult->getMatchedRoute();
        if (false === $route) {
            return null;
        }

        return $route->getOptions()[$key] ?? null;
    }

    private function nonEmptyPermission(mixed $permission): ?string
    {
        if (! is_string($permission)) {
            return null;
        }

        $permission = trim($permission);

        return '' === $permission ? null : $permission;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveContext(ServerRequestInterface $serverRequest, mixed $rawContext): array
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

            $contextKey           = is_int($key) ? $attribute : $key;
            $context[$contextKey] = $serverRequest->getAttribute($attribute);
        }

        return $context;
    }
}
