<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use InvalidArgumentException;

use function count;
use function explode;
use function preg_match;
use function str_contains;
use function trim;

final readonly class PermissionMatcher
{
    public function matches(string $pattern, string $permission): bool
    {
        $patternSegments    = $this->patternSegments($pattern);
        $permissionSegments = $this->permissionSegments($permission);

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

    /**
     * @return array{exactSegments: int, segmentCount: int}
     */
    public function specificity(string $pattern): array
    {
        $segments      = $this->patternSegments($pattern);
        $exactSegments = 0;

        foreach ($segments as $segment) {
            if ('*' !== $segment) {
                ++$exactSegments;
            }
        }

        return [
            'exactSegments' => $exactSegments,
            'segmentCount'  => count($segments),
        ];
    }

    /**
     * @return list<string>
     */
    private function patternSegments(string $pattern): array
    {
        return $this->segments($pattern, true, 'Permission pattern');
    }

    /**
     * @return list<string>
     */
    private function permissionSegments(string $permission): array
    {
        return $this->segments($permission, false, 'Permission');
    }

    /**
     * @return list<string>
     */
    private function segments(string $value, bool $wildcardsAllowed, string $label): array
    {
        $value = trim($value);
        if ('' === $value) {
            throw new InvalidArgumentException(
                "{$label} must be a non-empty string.",
            );
        }

        $segments = explode('.', $value);

        foreach ($segments as $segment) {
            if ('' === $segment || 1 === preg_match('/\s/u', $segment)) {
                throw new InvalidArgumentException(
                    "{$label} must contain non-empty, whitespace-free segments.",
                );
            }

            if ('*' === $segment) {
                if (! $wildcardsAllowed) {
                    throw new InvalidArgumentException('Permission must not contain wildcard segments.');
                }

                continue;
            }

            if (str_contains($segment, '*')) {
                throw new InvalidArgumentException(
                    "{$label} wildcard must occupy an entire segment.",
                );
            }
        }

        return $segments;
    }
}
