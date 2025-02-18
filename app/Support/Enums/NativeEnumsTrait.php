<?php

namespace App\Support\Enums;

trait NativeEnumsTrait
{
    public function getLabel(): string
    {
        return self::labels()[$this->value];
    }
    public static function getKeys(): array
    {
        return array_column(self::cases(), 'value');
    }

}
