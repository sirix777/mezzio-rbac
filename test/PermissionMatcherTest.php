<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\PermissionMatcher;

use function array_fill;
use function implode;

final class PermissionMatcherTest extends TestCase
{
    private PermissionMatcher $permissionMatcher;

    protected function setUp(): void
    {
        $this->permissionMatcher = new PermissionMatcher();
    }

    #[Test]
    public function matchesExactPermission(): void
    {
        self::assertTrue($this->permissionMatcher->matches('posts.read', 'posts.read'));
        self::assertFalse($this->permissionMatcher->matches('posts.read', 'posts.write'));
    }

    #[Test]
    public function matchesWildcardAtEnd(): void
    {
        self::assertTrue($this->permissionMatcher->matches('posts.*', 'posts.read'));
        self::assertTrue($this->permissionMatcher->matches('posts.*', 'posts.write'));
        self::assertTrue($this->permissionMatcher->matches('posts.*', 'posts.read.history'));
    }

    #[Test]
    public function matchesMultipleWildcards(): void
    {
        self::assertTrue($this->permissionMatcher->matches('*.*.*', 'admin.users.delete'));
        self::assertTrue($this->permissionMatcher->matches('admin.*.*', 'admin.users.delete'));
        self::assertFalse($this->permissionMatcher->matches('admin.*.*', 'posts.read'));
    }

    #[Test]
    public function differentSegmentCountDoesNotMatch(): void
    {
        self::assertFalse($this->permissionMatcher->matches('a.b.c', 'a.b'));
    }

    #[Test]
    public function specificityExactPermission(): void
    {
        self::assertSame([
            'exactSegments' => 2,
            'segmentCount'  => 2,
        ], $this->permissionMatcher->specificity('posts.read'));
    }

    #[Test]
    public function specificityWildcard(): void
    {
        self::assertSame([
            'exactSegments' => 1,
            'segmentCount'  => 2,
        ], $this->permissionMatcher->specificity('posts.*'));
    }

    #[Test]
    public function specificityMultipleWildcards(): void
    {
        self::assertSame([
            'exactSegments' => 0,
            'segmentCount'  => 3,
        ], $this->permissionMatcher->specificity('*.*.*'));
        self::assertSame([
            'exactSegments' => 2,
            'segmentCount'  => 3,
        ], $this->permissionMatcher->specificity('admin.users.*'));
    }

    #[Test]
    public function specificityDoesNotCollideForLongWildcardPatterns(): void
    {
        self::assertSame(
            [
                'exactSegments' => 1,
                'segmentCount'  => 2,
            ],
            $this->permissionMatcher->specificity('a.*'),
        );
        self::assertNotSame(
            $this->permissionMatcher->specificity('a.*'),
            $this->permissionMatcher->specificity(implode('.', array_fill(0, 1002, '*'))),
        );
    }

    #[Test]
    public function throwsOnEmptyPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->permissionMatcher->matches('', 'posts.read');
    }

    #[Test]
    public function throwsOnEmptyPermissionForSpecificity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->permissionMatcher->specificity('');
    }

    #[Test]
    public function throwsOnMalformedPermissionPatterns(): void
    {
        foreach (['.posts.read', 'posts.', 'posts..read', 'posts. read', 'posts.re ad', 'posts.foo*', 'posts.**'] as $pattern) {
            try {
                $this->permissionMatcher->specificity($pattern);
                self::fail("Expected '{$pattern}' to be rejected.");
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function throwsWhenRequestedPermissionContainsWildcard(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->permissionMatcher->matches('posts.*', 'posts.*');
    }

    #[Test]
    public function acceptsWildcardOnlyAsAnEntirePatternSegment(): void
    {
        self::assertTrue($this->permissionMatcher->matches('posts.*.history', 'posts.read.history'));
    }
}
