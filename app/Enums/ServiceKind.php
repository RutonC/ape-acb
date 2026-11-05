<?php

namespace App\Enums;

enum ServiceKind: string
{
    case Service = 'service';

    public function getLabel(): string
    {
        return match ($this) {
            self::Service => 'Serviço',
        };
    }

    /**
     * Nome da rota de listagem (sem prefixo de idioma).
     */
    public function indexRoute(): string
    {
        return match ($this) {
            self::Service => 'services',
        };
    }

    /**
     * Nome da rota de detalhe (sem prefixo de idioma).
     */
    public function showRoute(): string
    {
        return $this->indexRoute().'.show';
    }
}
