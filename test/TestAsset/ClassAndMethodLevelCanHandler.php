<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\TestAsset;

use Sirix\Mezzio\Rbac\Attribute\Can;

#[Can('posts.list')]
final class ClassAndMethodLevelCanHandler
{
    #[Can('posts.create')]
    public function create(): void {}

    public function list(): void {}
}
