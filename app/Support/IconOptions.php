<?php

namespace App\Support;

/**
 * Ícones (Heroicons, contorno) disponíveis nos blocos. A chave é o nome do
 * componente Blade sem o prefixo "heroicon-o-".
 */
class IconOptions
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'briefcase' => 'Emprego / negócios',
            'building-office-2' => 'Empresas',
            'megaphone' => 'Visibilidade',
            'calendar-days' => 'Eventos',
            'code-bracket-square' => 'Código',
            'globe-alt' => 'Websites / internet',
            'squares-2x2' => 'Sistemas / módulos',
            'device-phone-mobile' => 'Aplicações móveis',
            'user-group' => 'Consultoria / equipa',
            'lifebuoy' => 'Suporte',
            'wrench-screwdriver' => 'Manutenção',
            'academic-cap' => 'Académico / ensino',
            'building-library' => 'Instituições / microcrédito',
            'banknotes' => 'Finanças / pagamentos',
            'chat-bubble-left-right' => 'Chat / mensagens',
            'chat-bubble-bottom-center-text' => 'SMS',
            'shopping-cart' => 'Vendas',
            'server-stack' => 'Servidores',
            'cpu-chip' => 'Hardware',
            'computer-desktop' => 'Computadores',
            'at-symbol' => 'E-mail',
            'link' => 'Domínios',
            'cloud' => 'Cloud',
            'shield-check' => 'Segurança',
            'chart-bar' => 'Relatórios',
            'presentation-chart-line' => 'Crescimento',
            'light-bulb' => 'Ideias',
            'rocket-launch' => 'Projectos / lançamento',
            'trophy' => 'Experiência / prémio',
            'map-pin' => 'Localização',
            'puzzle-piece' => 'Sob medida',
            'clock' => 'Prazos',
            'check-badge' => 'Qualidade',
            'hand-thumb-up' => 'Satisfação',
            'cog-6-tooth' => 'Configuração',
            'document-text' => 'Documentos',
            'clipboard-document-check' => 'Lista / o que inclui',
            'magnifying-glass' => 'Análise',
            'phone' => 'Telefone',
            'envelope' => 'Envelope',
        ];
    }

    public static function component(?string $icon): string
    {
        return 'heroicon-o-'.(array_key_exists((string) $icon, static::all()) ? $icon : 'squares-2x2');
    }
}
