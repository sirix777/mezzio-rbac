<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\Extractor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sirix\Mezzio\Rbac\Attribute\Can;
use Sirix\Mezzio\Rbac\Extractor\CanAttributeExtractor;
use SirixTest\Mezzio\Rbac\TestAsset\ClassAndMethodLevelCanHandler;
use SirixTest\Mezzio\Rbac\TestAsset\ClassLevelCanHandler;
use SirixTest\Mezzio\Rbac\TestAsset\MethodLevelCanHandler;

final class CanAttributeExtractorTest extends TestCase
{
    private CanAttributeExtractor $canAttributeExtractor;

    protected function setUp(): void
    {
        $this->canAttributeExtractor = new CanAttributeExtractor();
    }

    #[Test]
    public function extractsClassLevelCanAttribute(): void
    {
        $attributes = $this->canAttributeExtractor->extractForClass(ClassLevelCanHandler::class);

        self::assertCount(1, $attributes);
        self::assertInstanceOf(Can::class, $attributes[0]);
        self::assertSame('posts.list', $attributes[0]->permission);
    }

    #[Test]
    public function extractsMethodLevelCanAttribute(): void
    {
        $attributes = $this->canAttributeExtractor->extractForMethod(MethodLevelCanHandler::class, 'handle');

        self::assertCount(1, $attributes);
        self::assertSame('posts.read', $attributes[0]->permission);
    }

    #[Test]
    public function extractsMethodLevelCanWithContext(): void
    {
        $attributes = $this->canAttributeExtractor->extractForMethod(MethodLevelCanHandler::class, 'update');

        self::assertCount(1, $attributes);
        self::assertSame('posts.update', $attributes[0]->permission);
        self::assertSame([
            'post' => 'id',
        ], $attributes[0]->context);
    }

    #[Test]
    public function returnsEmptyArrayForMethodWithoutCan(): void
    {
        $attributes = $this->canAttributeExtractor->extractForMethod(MethodLevelCanHandler::class, 'delete');

        self::assertCount(0, $attributes);
    }

    #[Test]
    public function methodLevelOverridesClassLevel(): void
    {
        $methodAttributes = $this->canAttributeExtractor->extractForMethod(ClassAndMethodLevelCanHandler::class, 'create');

        self::assertCount(1, $methodAttributes);
        self::assertSame('posts.create', $methodAttributes[0]->permission);
    }

    #[Test]
    public function fallsBackToClassLevelWhenMethodHasNoCan(): void
    {
        $methodAttributes = $this->canAttributeExtractor->extractForMethod(ClassAndMethodLevelCanHandler::class, 'list');

        self::assertCount(1, $methodAttributes);
        self::assertSame('posts.list', $methodAttributes[0]->permission);
    }

    #[Test]
    public function cachingWorks(): void
    {
        $first  = $this->canAttributeExtractor->extractForClass(ClassLevelCanHandler::class);
        $second = $this->canAttributeExtractor->extractForClass(ClassLevelCanHandler::class);

        self::assertSame($first, $second);
    }
}
