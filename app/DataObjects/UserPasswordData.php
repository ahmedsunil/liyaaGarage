<?php

namespace App\DataObjects;

class UserPasswordData
{
    public function __construct(
        public string $password,
        public bool $require_password_update,
        public bool $send_password_reset_email,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            password: $data['password'],
            require_password_update: $data['require_password_update'] ?? false,
            send_password_reset_email: $data['send_password_reset_email'] ?? false,
        );
    }
}
