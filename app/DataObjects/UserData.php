<?php

namespace App\DataObjects;

use Illuminate\Support\Carbon;

class UserData
{
    public function __construct(
        public string  $name,
        public string  $email,
        public ?int    $role,
        public ?string $contact_number,
        public ?Carbon $email_verified_at,
        public ?string $status,
    ) {
    }


    public static function fromArray(array $request_data): self
    {
        $email_verified_at = $request_data['email_verified_at'] ?? null;
        if ($email_verified_at) {
            $email_verified_at = Carbon::parse($email_verified_at);
        }

        return new self(
            name: $request_data['name'],
            email: $request_data['email'],
            role: $request_data['role'] ?? null,
            contact_number: $request_data['contact_number'] ?? null,
            email_verified_at: $email_verified_at,
            status: $request_data['status'] ?? null,
        );
    }
}
