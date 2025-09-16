<?php

namespace Bs\Traits;

trait EnumTrait
{

    public static function try(string $name): ?self
    {
        if (in_array($name, self::names())) {
            return self::{$name};
        }
        return null;
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function forArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'name'),
            array_column(self::cases(), 'value')
        );
    }
}