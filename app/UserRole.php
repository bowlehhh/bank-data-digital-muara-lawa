<?php

namespace App;

enum UserRole: string
{
    case Admin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Mengelola, menyimpan, mencari, dan mengunduh dokumen.',
        };
    }
}
