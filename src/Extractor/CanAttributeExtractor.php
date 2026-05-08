<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Extractor;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Sirix\Mezzio\Rbac\Attribute\Can;

final class CanAttributeExtractor
{
    /**
     * @var array<string, list<Can>>
     */
    private array $cache = [];

    /**
     * @param class-string $className
     *
     * @return list<Can>
     */
    public function extractForMethod(string $className, string $methodName): array
    {
        $cacheKey = $className . '::' . $methodName;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $reflection = new ReflectionMethod($className, $methodName);
        $attributes = $this->extractCanAttributes($reflection);

        if ([] === $attributes) {
            $classReflection = new ReflectionClass($className);
            $attributes = $this->extractCanAttributes($classReflection);
        }

        return $this->cache[$cacheKey] = $attributes;
    }

    /**
     * @param class-string $className
     *
     * @return list<Can>
     */
    public function extractForClass(string $className): array
    {
        $cacheKey = $className . '::*';
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $reflection = new ReflectionClass($className);

        return $this->cache[$cacheKey] = $this->extractCanAttributes($reflection);
    }

    /**
     * @param ReflectionClass<object>|ReflectionMethod $reflection
     *
     * @return list<Can>
     */
    private function extractCanAttributes(ReflectionClass|ReflectionMethod $reflection): array
    {
        $attributes = [];

        foreach ($reflection->getAttributes(Can::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }
}
