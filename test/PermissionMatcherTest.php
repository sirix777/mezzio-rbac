<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\PermissionMatcher;

final class PermissionMatcherTest extends TestCase
{
    private PermissionMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new PermissionMatcher();
    }

    #[Test]
    public function matchesExactPermission(): void
    {
        self::assertTrue($this->matcher->matches('posts.read', 'posts.read'));
        self::assertFalse($this->matcher->matches('posts.read', 'posts.write'));
    }

    #[Test]
    public function matchesWildcardAtEnd(): void
    {
        self::assertTrue($this->matcher->matches('posts.*', 'posts.read'));
        self::assertTrue($this->matcher->matches('posts.*', 'posts.write'));
        self::assertTrue($this->matcher->matches('posts.*', 'posts.read.history'));
    }

    #[Test]
    public function matchesMultipleWildcards(): void
    {
        self::assertTrue($this->matcher->matches('*.*.*', 'admin.users.delete'));
        self::assertTrue($this->matcher->matches('admin.*.*', 'admin.users.delete'));
        self::assertFalse($this->matcher->matches('admin.*.*', 'posts.read'));
    }

    #[Test]
    public function differentSegmentCountDoesNotMatch(): void
    {
        self::assertFalse($this->matcher->matches('a.b.c', 'a.b'));
    }

    #[Test]
    public function specificityExactPermission(): void
    {
        self::assertSame(2002, $this->matcher->specificity('posts.read'));
    }

    #[Test]
    public function specificityWildcard(): void
    {
        self::assertSame(1002, $this->matcher->specificity('posts.*'));
    }

    #[Test]
    public function specificityMultipleWildcards(): void
    {
        self::assertSame(3, $this->matcher->specificity('*.*.*'));
        self::assertSame(2003, $this->matcher->specificity('admin.users.*'));
    }

    #[Test]
    public function throwsOnEmptyPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->matcher->matches('', 'posts.read');
    }

    #[Test]
    public function throwsOnEmptyPermissionForSpecificity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->matcher->specificity('');
    }
}
