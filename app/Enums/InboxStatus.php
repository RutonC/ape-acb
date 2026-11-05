<?php

namespace App\Enums;

/**
 * Estado das mensagens de contacto na Caixa de entrada.
 */
enum InboxStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Nova',
            self::Read => 'Lida',
            self::Replied => 'Respondida',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Read => 'gray',
            self::Replied => 'success',
        };
    }
}
