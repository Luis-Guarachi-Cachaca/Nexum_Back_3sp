<?php

namespace App\Enums;

enum PortfolioTheme: string
{
    case LightMode = 'light_mode';
    case DarkMode  = 'dark_mode';

    public function metadata(): array
    {
        return match($this) {
            self::LightMode => [
                'id'          => self::LightMode->value,
                'name'        => 'Modo Claro',
                'description' => 'Diseño con fondo claro, ideal para ambientes bien iluminados.',
                'palette'     => null,
            ],
            self::DarkMode => [
                'id'          => self::DarkMode->value,
                'name'        => 'Modo Oscuro',
                'description' => 'Diseño con fondo oscuro, reduce la fatiga visual.',
                'palette'     => null,
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function allMetadata(): array
    {
        return array_map(
            fn(self $theme) => $theme->metadata(),
            self::cases()
        );
    }
}