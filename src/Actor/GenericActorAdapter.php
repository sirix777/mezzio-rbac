<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Actor;

use Sirix\Mezzio\Rbac\Contract\ActorInterface;

use function array_filter;
use function array_values;
use function is_array;
use function is_callable;
use function is_string;

final readonly class GenericActorAdapter implements ActorInterface
{
    public function __construct(private object $actor) {}

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        if (! is_callable([$this->actor, 'getRoles'])) {
            return [];
        }

        $roles = $this->actor->getRoles();

        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_filter(
            $roles,
            is_string(...),
        ));
    }
}
