<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\RbacAttribute;

use function is_array;
use function is_int;
use function is_string;

final readonly class AuthorizeMiddleware implements MiddlewareInterface
{
    public function __construct(private GuardInterface $guard) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permission = $request->getAttribute(RbacAttribute::Permission->value);

        if (! is_string($permission) || '' === $permission) {
            return $handler->handle($request);
        }

        $context = $this->resolveContext(
            $request,
            $request->getAttribute(RbacAttribute::Context->value, []),
        );

        $this->guard->authorize($permission, $context);

        return $handler->handle($request);
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
