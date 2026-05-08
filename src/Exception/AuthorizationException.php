<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Exception;

use RuntimeException;

final class AuthorizationException extends RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $permission,
        string $message = 'Forbidden',
        private readonly array $headers = [],
        private readonly ?string $publicMessage = null,
    ) {
        parent::__construct($message);
    }

    public function getPermission(): string
    {
        return $this->permission;
    }

    public function getStatusCode(): int
    {
        return 403;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getPublicMessage(): string
    {
        return $this->publicMessage ?? $this->getMessage();
    }
}
