<?php

namespace App\Enums;

/**
 * Estado das candidaturas (Carreiras).
 */
enum ApplicationStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Interview = 'interview';
    case Rejected = 'rejected';
    case Hired = 'hired';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Nova',
            self::Reviewing => 'Em análise',
            self::Interview => 'Entrevista',
            self::Rejected => 'Não seleccionada',
            self::Hired => 'Admitida',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Reviewing => 'info',
            self::Interview => 'primary',
            self::Rejected => 'gray',
            self::Hired => 'success',
        };
    }
}
