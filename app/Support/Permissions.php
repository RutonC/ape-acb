<?php

namespace App\Support;

/**
 * Permissões do painel: uma por área de gestão. O perfil "Administrador"
 * tem acesso total (Gate::before); os restantes perfis recebem as áreas
 * escolhidas em Administração > Perfis.
 */
class Permissions
{
    public const ADMIN_ROLE = 'Administrador';

    public const MANAGER_ROLE = 'Gestor de conteúdos';

    /** Áreas que o gestor de conteúdos não recebe por omissão (as candidaturas têm dados pessoais). */
    public const ADMIN_ONLY = ['settings', 'users', 'applications'];

    /**
     * @var array<string, array{0: string, 1: string}> chave => [rótulo, grupo]
     */
    public const ALL = [
        'messages' => ['Mensagens de contacto', 'Caixa de entrada'],
        'applications' => ['Candidaturas e currículos (dados pessoais)', 'Caixa de entrada'],
        'companies' => ['Inscrições de empresas', 'Caixa de entrada'],
        'jobs' => ['Vagas e categorias', 'Vagas'],
        'pages' => ['Páginas', 'Conteúdo'],
        'menus' => ['Menus', 'Conteúdo'],
        'media' => ['Biblioteca de média', 'Conteúdo'],
        'services' => ['Serviços', 'Conteúdo'],
        'blog' => ['Notícias, categorias e etiquetas', 'Notícias'],
        'team' => ['Equipa', 'Institucional'],
        'settings' => ['Definições do site e SEO', 'Definições'],
        'users' => ['Utilizadores e perfis', 'Administração'],
    ];

    public static function label(string $permission): string
    {
        return self::ALL[$permission][0] ?? $permission;
    }

    /**
     * Opções agrupadas para o formulário de perfis.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::ALL as $key => [$label, $group]) {
            $groups[$group][$key] = $label;
        }

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    public static function managerDefaults(): array
    {
        return array_values(array_diff(array_keys(self::ALL), self::ADMIN_ONLY));
    }
}
