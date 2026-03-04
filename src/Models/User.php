<?php

namespace App\Models;

class User
{
    public int $id;
    public string $name;
    public string $email;
    public int $role_id;
    public ?string $role_slug;
    public ?string $role_name;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->name = $data['name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->role_id = (int)($data['role_id'] ?? 0);
        $this->role_slug = $data['role_slug'] ?? null;
        $this->role_name = $data['role_name'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => [
                'id' => $this->role_id,
                'name' => $this->role_name,
                'slug' => $this->role_slug
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
