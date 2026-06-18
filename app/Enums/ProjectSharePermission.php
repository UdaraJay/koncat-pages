<?php

namespace App\Enums;

enum ProjectSharePermission: string
{
    case Read = 'read';
    case Write = 'write';

    public function label(): string
    {
        return match ($this) {
            self::Read => __('Read only'),
            self::Write => __('Can edit'),
        };
    }

    public function canWrite(): bool
    {
        return $this === self::Write;
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $permission) => [
                'value' => $permission->value,
                'label' => $permission->label(),
            ])
            ->values()
            ->toArray();
    }
}
