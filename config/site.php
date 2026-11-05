<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idiomas do site
    |--------------------------------------------------------------------------
    |
    | O primeiro idioma é o predefinido (sem prefixo no URL). Os restantes
    | são servidos com prefixo, ex.: /en/services. Os campos traduzíveis dos
    | modelos (spatie/laravel-translatable) usam estas mesmas chaves.
    | A APE publica só em português; para activar o inglês: SITE_LOCALES=pt,en
    | e acrescentar o idioma em LOCALES (resources/admin/components/form/translatable.tsx).
    |
    */

    'locales' => array_intersect_key(
        ['pt' => 'Português', 'en' => 'English'],
        array_flip(explode(',', env('SITE_LOCALES', 'pt'))),
    ),

    'default_locale' => 'pt',

    /*
    | Segmentos fixos dos endereços traduzidos por idioma (o português é a
    | referência). Ex.: /sobre-nos -> /en/about-us, /noticias/categoria -> /en/news/category.
    | Os endereços de conteúdos (serviços, projectos, artigos) mantêm o mesmo slug.
    */
    'paths' => [
        'en' => [
            'sobre-nos' => 'about-us',
            'vagas' => 'jobs',
            'candidatura' => 'apply',
            'empresas' => 'companies',
            'parceiros' => 'partners',
            'noticias' => 'news',
            'servicos' => 'services',
            'categoria' => 'category',
            'etiqueta' => 'tag',
            'contactos' => 'contact',
            'politica-de-privacidade' => 'privacy-policy',
            'termos-de-utilizacao' => 'terms-of-use',
        ],
    ],

    // Assuntos do bloco "Pedido de contacto".
    'contact_subjects' => [],

    /*
    |--------------------------------------------------------------------------
    | Painel de administração
    |--------------------------------------------------------------------------
    |
    | Autenticação com dupla verificação (RNF06). Obrigatória por omissão;
    | pode ser desligada em desenvolvimento com ADMIN_MFA_REQUIRED=false.
    |
    */

    'admin_mfa_required' => env('ADMIN_MFA_REQUIRED', true),

    /*
    | Proxies de confiança (ngrok, Cloudflare, balanceador): o site passa a
    | usar o https e o domínio indicados pelo proxy nos endereços que gera.
    | "*" confia em qualquer proxy (só quando o servidor está atrás de um);
    | ou uma lista de IPs separados por vírgulas. Vazio: nenhum.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    // Conta criada pelo AdminUserSeeder (sem palavra-passe definida, é gerada uma aleatória).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL', 'info.ape@acb.co.mz'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
