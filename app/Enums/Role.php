<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Comercial = 'comercial';
    case Coach = 'coach';
    case Direccion = 'direccion';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administración',
            self::Comercial => 'Comercial',
            self::Coach => 'Coach',
            self::Direccion => 'Dirección',
        };
    }

    public function canManageSettings(): bool
    {
        return in_array($this, [self::Admin, self::Direccion], true);
    }
}
