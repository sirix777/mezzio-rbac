<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use InvalidArgumentException;

use function array_map;
use function array_sum;
use function count;
use function explode;
use function trim;

final readonly class PermissionMatcher
{
    public function matches(string $pattern, string $permission): bool
    {
        $patternSegments    = $this->segments($pattern);
        $permissionSegments = $this->segments($permission);

        $patternCount    = count($patternSegments);
        $permissionCount = count($permissionSegments);

        if ('*' === $patternSegments[$patternCount - 1]) {
            if ($permissionCount < $patternCount) {
                return false;
            }
        } elseif ($patternCount !== $permissionCount) {
            return false;
        }

        foreach ($patternSegments as $index => $segment) {
            if ('*' === $segment) {
                if ($index === $patternCount - 1) {
                    return true;
                }

                continue;
            }

            if (! isset($permissionSegments[$index]) || $segment !== $permissionSegments[$index]) {
                return false;
            }
        }

        return true;
    }

    public function specificity(string $pattern): int
    {
        $segments      = $this->segments($pattern);
        $exactSegments = array_sum(array_map(
            static fn (string $segment): int => '*' === $segment ? 0 : 1,
            $segments,
        ));

        return ($exactSegments * 1000) + count($segments);
    }

    /**
     * @return list<string>
     */
    private function segments(string $permission): array
    {
        $permission = trim($permission);
        if ('' === $permission) {
            throw new InvalidArgumentException(
                'Permission pattern must be a non-empty string.',
            );
        }

        return explode('.', $permission);
    }
}
