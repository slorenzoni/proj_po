<?php

namespace App\Concerns;

/**
 * Para enums com label(): devolve os casos no formato usado pelos campos de seleção das telas.
 */
trait HasOpcoes
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function opcoes(): array
    {
        return array_map(
            fn (self $caso): array => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
