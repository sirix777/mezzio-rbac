<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Middleware;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait RouteResultFactoryTrait
{
    /**
     * @param array<string, mixed> $options
     * @param non-empty-string     $path
     * @param non-empty-string     $name
     */
    private function routeResult(array $options, string $path = '/test', string $name = 'test'): RouteResult
    {
        $route = new Route(
            $path,
            new class implements MiddlewareInterface {
                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    return $handler->handle($request);
                }
            },
            ['GET'],
            $name,
        );
        $route->setOptions($options);

        return RouteResult::fromRoute($route);
    }
}
