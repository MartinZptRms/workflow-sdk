<?php

namespace Workflow\SDK\Data;

class HelpdeskToken
{
    public function __construct(
        public readonly string $token,
        public readonly string $url,
        public readonly int    $expiresIn,
    ) {}

    public static function fromArray(array $data, string $helpdeskUrl): self
    {
        return new self(
            token    : $data['token'],
            url      : rtrim($helpdeskUrl, '/') . '?token=' . $data['token'],
            expiresIn: $data['expires_in'] ?? 86400,
        );
    }
}
