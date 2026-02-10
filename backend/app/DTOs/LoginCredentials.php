<?php

namespace App\DTOs;

readonly class LoginCredentials
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /**
     * Create from validated request array.
     *
     * @param  array{email: string, password: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
        );
    }

    /**
     * As array for guard attempt().
     *
     * @return array{email: string, password: string}
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
