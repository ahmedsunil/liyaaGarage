<?php

namespace App\Support\Enums;

enum UserStatuses: string
{
    use NativeEnumsTrait;


    case Active = 'active';
    case Pending = 'pending';
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public static function labels(): array
    {
        return [
            self::Active->value => __('Active'),
            self::Pending->value => __('Pending'),
            self::Inactive->value => __('Inactive'),
            self::Blocked->value => __('Blocked'),
        ];
    }

    //colors
    public static function colors(): array
    {
        return [
            self::Active->value => 'success',
            self::Pending->value => 'gray',
            self::Inactive->value => 'warning',
            self::Blocked->value => 'danger',
        ];
    }

    public function getColor(): string
    {
        return self::colors()[$this->value];
    }
}
