<?php

namespace App\Enums;

enum MenuLocation: string
{
    case Header = 'header';
    case Footer = 'footer';
    case FooterServices = 'footer_services';
    case Legal = 'legal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Header => 'Menu principal (cabeçalho)',
            self::Footer => 'Rodapé: navegação',
            self::FooterServices => 'Rodapé: serviços',
            self::Legal => 'Rodapé: ligações legais',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Header => 'Barra de menu e menu mobile. Aceita um nível de submenu.',
            self::Footer => 'Segunda coluna do rodapé.',
            self::FooterServices => 'Terceira coluna do rodapé.',
            self::Legal => 'Barra inferior do rodapé (privacidade, termos).',
        };
    }

    public function allowsChildren(): bool
    {
        return $this === self::Header;
    }
}
