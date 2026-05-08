<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\TestAsset;

use Sirix\Mezzio\Rbac\Attribute\Can;

final class MethodLevelCanHandler
{
    #[Can('posts.read')]
    public function handle(): void
    {
    }

    #[Can('posts.update', ['post' => 'id'])]
    public function update(): void
    {
    }

    public function delete(): void
    {
    }
}
