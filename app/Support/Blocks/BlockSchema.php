<?php

namespace App\Support\Blocks;

use App\Models\PostCategory;
use App\Support\PageBlocks;

/**
 * Blocos do construtor de páginas: rótulo, descrição, fundo e visibilidade
 * por omissão e campos. É a fonte única para o formulário do painel e para
 * a validação no servidor; o desenho no site é feito por
 * resources/views/blocks/{tipo}.blade.php.
 */
class BlockSchema
{
    public const BACKGROUNDS = ['white' => 'Branco', 'light' => 'Cinzento claro', 'dark' => 'Escuro'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'job_hero' => [
                'label' => 'Topo com pesquisa de vagas',
                'description' => 'Título, texto, pesquisa de vagas e duas fotografias.',
                'background' => 'light',
                'fields' => [
                    Field::text('title', 'Título', 160, required: true, extra: ['width' => 'half']),
                    Field::text('highlight', 'Parte do título em destaque', 60, extra: ['width' => 'half', 'help' => 'Texto igual a uma parte do título.']),
                    Field::textarea('text', 'Texto'),
                    Field::media('image', 'Fotografia principal', extra: ['width' => 'half']),
                    Field::media('image_2', 'Segunda fotografia', extra: ['width' => 'half']),
                ],
            ],
            'job_categories' => [
                'label' => 'Categorias de vagas',
                'description' => 'Cartões com as categorias e o número de vagas.',
                'fields' => static::heading(),
            ],
            'hiring_banner' => [
                'label' => 'Faixa para empresas',
                'description' => 'Pergunta, texto e botão para as empresas.',
                'fields' => [
                    Field::text('title', 'Título', 160, required: true),
                    Field::textarea('text', 'Texto', rows: 2),
                    Field::button('button', 'Botão'),
                ],
            ],
            'job_list' => [
                'label' => 'Vagas',
                'description' => 'Últimas vagas, com separadores por categoria. As abertas aparecem primeiro.',
                'fields' => [
                    ...static::heading(),
                    Field::select('limit', 'Número de vagas', [4 => '4', 8 => '8', 12 => '12'], 8, ['width' => 'half']),
                    Field::text('button_label', 'Texto do botão para as vagas', 60, extra: ['width' => 'half', 'help' => 'Vazio esconde o botão.']),
                ],
            ],
            'job_locations' => [
                'label' => 'Vagas por local',
                'description' => 'Locais que existem nas vagas, com o número de vagas.',
                'fields' => static::heading(),
            ],
            'hero' => [
                'label' => 'Destaque (hero)',
                'description' => 'Topo da página com título grande, botões e imagem.',
                'background' => 'white',
                'fields' => [
                    Field::text('subtitle', 'Subtítulo', 60, extra: ['width' => 'half']),
                    Field::text('title', 'Título', 160, required: true, extra: ['width' => 'half']),
                    Field::textarea('text', 'Texto'),
                    Field::button('primary_button', 'Botão principal'),
                    Field::button('secondary_button', 'Botão secundário'),
                    Field::media('image', 'Imagem (à direita)', extra: ['help' => 'Fotografia ou ilustração.', 'width' => 'half']),
                    Field::media('background_image', 'Imagem de fundo', extra: ['help' => 'Opcional. Use com o fundo escuro.', 'width' => 'half']),
                    Field::text('video_url', 'Vídeo (YouTube)', 255, extra: ['input' => 'url', 'help' => 'Mostra o botão "play" e abre o vídeo numa janela.']),
                    Field::group('Destaque sobre a imagem', [
                        Field::text('badge_value', 'Valor', 20, extra: ['width' => 'half', 'placeholder' => '800+']),
                        Field::text('badge_label', 'Legenda', 60, extra: ['width' => 'half', 'placeholder' => 'sócios inscritos']),
                    ]),
                    Field::repeater('highlights', 'Pontos em destaque', [
                        Field::icon('icon', 'Ícone', ['width' => 'half']),
                        Field::text('title', 'Título', 80, required: true, extra: ['width' => 'half']),
                        Field::text('text', 'Texto curto', 120),
                    ], 'title', 'Adicionar ponto', 3),
                ],
            ],
            'cards' => [
                'label' => 'Cartões com ícone',
                'description' => 'Serviços ou diferenciais em grelha.',
                'background' => 'light',
                'fields' => [
                    ...static::heading(),
                    Field::buttons('style', 'Estilo', [
                        'service' => 'Serviço (ícone em cima)',
                        'feature' => 'Diferencial (ícone ao lado)',
                        'product' => 'Com "Saber mais"',
                    ], 'service'),
                    Field::select('columns', 'Colunas (computador)', [2 => '2', 3 => '3', 4 => '4', 6 => '6'], 3, ['width' => 'half']),
                    Field::repeater('items', 'Cartões', [
                        Field::icon('icon', 'Ícone', ['width' => 'half']),
                        Field::media('image', 'Ou imagem própria (ícone)', extra: ['width' => 'half']),
                        Field::text('title', 'Título', 160, required: true, extra: ['width' => 'half']),
                        Field::text('link_label', 'Texto da ligação', 60, extra: ['width' => 'half', 'placeholder' => 'Saber mais']),
                        Field::textarea('text', 'Texto', rows: 2),
                        Field::link('link', 'Ligação', withNewTab: false),
                    ], 'title', 'Adicionar cartão'),
                ],
            ],
            'about' => [
                'label' => 'Imagem e texto',
                'description' => 'Texto com lista de pontos, imagem ao lado e destaque numérico.',
                'fields' => [
                    Field::text('subtitle', 'Subtítulo', 60, extra: ['width' => 'half']),
                    Field::text('title', 'Título', 160, extra: ['width' => 'half']),
                    Field::richtext('content', 'Texto'),
                    Field::list('bullets', 'Lista de pontos', 'Adicionar ponto'),
                    Field::media('image', 'Imagem', extra: ['width' => 'half']),
                    Field::buttons('image_position', 'Posição da imagem', ['left' => 'Esquerda', 'right' => 'Direita'], 'left', ['width' => 'half']),
                    Field::group('Destaque sobre a imagem', [
                        Field::text('badge_value', 'Valor', 20, extra: ['width' => 'half', 'placeholder' => '+5']),
                        Field::text('badge_label', 'Legenda', 60, extra: ['width' => 'half', 'placeholder' => 'anos de experiência']),
                    ]),
                    Field::button('button', 'Botão'),
                    Field::button('secondary_button', 'Segundo botão'),
                ],
            ],
            'counters' => [
                'label' => 'Números (contadores)',
                'description' => 'Números em destaque que contam até ao valor.',
                'fields' => [
                    ...static::heading(),
                    Field::repeater('items', 'Números', [
                        Field::text('prefix', 'Prefixo', 3, extra: ['width' => 'quarter', 'placeholder' => '+']),
                        Field::number('value', 'Valor', required: true, extra: ['width' => 'quarter']),
                        Field::text('suffix', 'Sufixo', 3, extra: ['width' => 'quarter', 'placeholder' => 'k, %, +']),
                        Field::icon('icon', 'Ícone', ['width' => 'quarter']),
                        Field::text('label', 'Legenda', 80, required: true, extra: ['width' => 'half']),
                        Field::text('text', 'Texto curto', 160, extra: ['width' => 'half']),
                    ], ['prefix', 'value', 'suffix', 'label'], 'Adicionar número', 6),
                ],
            ],
            'blog' => [
                'label' => 'Últimas notícias',
                'description' => 'Notícias publicadas mais recentes.',
                'background' => 'light',
                'fields' => [
                    ...static::heading(),
                    Field::select('limit', 'Número de notícias', [3 => '3', 6 => '6'], 3, ['width' => 'third']),
                    Field::select('post_category_id', 'Só da categoria', 'post_categories', null, ['width' => 'third', 'placeholder' => 'Todas']),
                    Field::text('button_label', 'Texto do botão para as notícias', 60, extra: ['width' => 'third', 'placeholder' => 'Ver todas as notícias', 'help' => 'Vazio esconde o botão.']),
                ],
            ],
            'team' => [
                'label' => 'Equipa',
                'description' => 'Membros da equipa publicados (geridos em Institucional, Equipa).',
                'fields' => [
                    ...static::heading(),
                    Field::select('columns', 'Colunas (computador)', [3 => '3', 4 => '4'], 4, ['width' => 'half']),
                    Field::note('Os membros são geridos em Institucional, Equipa. Sem membros publicados, a secção não aparece.'),
                ],
            ],
            'company_info' => [
                'label' => 'Dados institucionais',
                'description' => 'Designação, morada, NUIT, registo e contactos das Definições.',
                'background' => 'light',
                'fields' => [
                    ...static::heading(),
                    Field::note('Mostra a designação, morada, NUIT, registo comercial e contactos definidos em Definições.'),
                ],
            ],
            'process' => [
                'label' => 'Processo em passos',
                'description' => 'Passos numerados (como trabalhamos).',
                'fields' => [
                    ...static::heading(),
                    Field::repeater('steps', 'Passos', [
                        Field::text('title', 'Título', 120, required: true),
                        Field::textarea('text', 'Texto', rows: 2),
                    ], 'title', 'Adicionar passo', 8, ['help' => 'Numerados automaticamente (1., 2., 3.…).']),
                ],
            ],
            'cta' => [
                'label' => 'Chamada para acção',
                'description' => 'Faixa com título e botão, ou contacto telefónico.',
                'background' => 'dark',
                'fields' => [
                    Field::buttons('style', 'Estilo', ['band' => 'Faixa (título e botão)', 'call' => 'Contacto telefónico ("Ligue-nos")'], 'band'),
                    Field::text('subtitle', 'Subtítulo', 60, extra: ['width' => 'half']),
                    Field::text('title', 'Título', 160, required: true, extra: ['width' => 'half']),
                    Field::textarea('text', 'Texto', rows: 2),
                    Field::text('phone', 'Telefone em destaque', 40, extra: ['input' => 'tel', 'help' => 'Vazio usa o primeiro telefone das Definições.']),
                    Field::button('button', 'Botão'),
                ],
            ],
            'testimonials' => [
                'label' => 'Testemunhos',
                'description' => 'Citações de clientes com nome, cargo e classificação.',
                'background' => 'light',
                'fields' => [
                    ...static::heading(),
                    Field::repeater('items', 'Testemunhos', [
                        Field::textarea('quote', 'Testemunho', required: true),
                        Field::text('name', 'Nome', 120, required: true, extra: ['width' => 'half']),
                        Field::text('role', 'Cargo e entidade', 160, extra: ['width' => 'half']),
                        Field::media('photo', 'Fotografia', extra: ['width' => 'half']),
                        Field::select('rating', 'Classificação', [5 => '★★★★★', 4 => '★★★★', 3 => '★★★'], 5, ['width' => 'half']),
                    ], 'name', 'Adicionar testemunho'),
                ],
            ],
            'videos' => [
                'label' => 'Vídeos',
                'description' => 'Grelha de vídeos com capa, reproduzidos numa janela.',
                'background' => 'light',
                'fields' => [
                    ...static::heading(),
                    Field::repeater('items', 'Vídeos', [
                        Field::text('title', 'Título', 120, required: true, extra: ['width' => 'half']),
                        Field::media('cover', 'Imagem de capa', required: true, extra: ['width' => 'half']),
                        Field::media('video', 'Vídeo', extra: ['accept' => 'video', 'help' => 'Carregue um MP4 ou WebM (até 100 MB).']),
                        Field::text('video_url', 'Ou endereço de um vídeo (MP4)', 255, extra: ['input' => 'url']),
                    ], 'title', 'Adicionar vídeo', 8),
                ],
            ],
            'contact_form' => [
                'label' => 'Pedido de contacto',
                'description' => 'Formulário com nome, telefone, e-mail e assunto. Os pedidos ficam na Caixa de entrada.',
                'background' => 'light',
                'fields' => [
                    ...static::heading(),
                    Field::note('Os assuntos são: Aluguer de Salas, Requisição de Quotação, Requisitos para se tornar membro e Diversos.'),
                ],
            ],
            'logos' => [
                'label' => 'Logótipos de parceiros',
                'description' => 'Faixa de logótipos de parceiros ou clientes.',
                'fields' => [
                    ...static::heading(),
                    Field::repeater('items', 'Logótipos', [
                        Field::media('image', 'Logótipo', required: true),
                        Field::text('name', 'Nome', 120, required: true, extra: ['width' => 'half']),
                        Field::text('url', 'Site', 255, extra: ['width' => 'half', 'input' => 'url']),
                    ], 'name', 'Adicionar logótipo'),
                ],
            ],
            'gallery' => [
                'label' => 'Galeria de imagens',
                'description' => 'Grelha de imagens com legenda e filtros por categoria.',
                'fields' => [
                    ...static::heading(),
                    Field::select('columns', 'Colunas (computador)', [2 => '2', 3 => '3', 4 => '4'], 3, ['width' => 'half']),
                    Field::toggle('show_filters', 'Mostrar filtros por categoria', true, ['width' => 'half', 'help' => 'Aparecem quando há pelo menos duas categorias.']),
                    Field::repeater('items', 'Imagens', [
                        Field::media('image', 'Imagem', required: true),
                        Field::text('caption', 'Legenda', 160, extra: ['width' => 'half']),
                        Field::text('category', 'Categoria', 60, extra: ['width' => 'half', 'help' => 'Ex.: Formação, Evento. Usada nos filtros.']),
                    ], 'caption', 'Adicionar imagem'),
                ],
            ],
            'pricing' => [
                'label' => 'Tabela de preços',
                'description' => 'Planos com preço e lista do que incluem.',
                'active' => false,
                'fields' => [
                    ...static::heading(),
                    Field::repeater('plans', 'Planos', [
                        Field::text('name', 'Nome', 80, required: true, extra: ['width' => 'quarter']),
                        Field::text('currency', 'Moeda', 5, extra: ['width' => 'quarter', 'default' => 'MT']),
                        Field::text('price', 'Preço', 40, required: true, extra: ['width' => 'quarter']),
                        Field::text('period', 'Período', 40, extra: ['width' => 'quarter', 'placeholder' => 'por mês']),
                        Field::list('features', 'Inclui', 'Adicionar item'),
                        Field::toggle('highlighted', 'Plano em destaque'),
                        Field::button('button', 'Botão', 'Fale connosco', withNewTab: false),
                    ], 'name', 'Adicionar plano', 4),
                ],
            ],
            'faq' => [
                'label' => 'Perguntas frequentes',
                'description' => 'Perguntas e respostas em acordeão.',
                'fields' => [
                    ...static::heading(),
                    Field::repeater('items', 'Perguntas', [
                        Field::text('question', 'Pergunta', 255, required: true),
                        Field::textarea('answer', 'Resposta', required: true),
                    ], 'question', 'Adicionar pergunta'),
                ],
            ],
            'rich_text' => [
                'label' => 'Texto livre',
                'description' => 'Texto formatado (políticas, termos, artigos).',
                'fields' => [
                    Field::text('title', 'Título', 160, extra: ['width' => 'half']),
                    Field::buttons('width', 'Largura', ['narrow' => 'Estreita (leitura)', 'wide' => 'Larga'], 'narrow', ['width' => 'half']),
                    Field::richtext('content', 'Conteúdo', required: true),
                ],
            ],
        ];
    }

    /**
     * Definições com as listas dinâmicas resolvidas e os valores por omissão
     * da aparência, para o painel.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forPanel(): array
    {
        $lists = static::dynamicOptions();

        return collect(static::definitions())
            ->only(PageBlocks::TYPES)
            ->map(fn (array $definition, string $type) => [
                'type' => $type,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'background' => $definition['background'] ?? 'white',
                'active' => $definition['active'] ?? true,
                'fields' => static::resolve($definition['fields'], $lists),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, array{value: int, label: string}>>
     */
    public static function dynamicOptions(): array
    {
        $locale = config('site.default_locale');

        return [
            'post_categories' => PostCategory::query()->orderBy('sort_order')->get()
                ->map(fn (PostCategory $c) => ['value' => $c->id, 'label' => $c->getTranslation('name', $locale)])->values()->all(),
        ];
    }

    /**
     * Cabeçalho de secção comum (subtítulo, título, descrição e alinhamento).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function heading(): array
    {
        return [
            Field::group('Cabeçalho da secção', [
                Field::text('subtitle', 'Subtítulo', 60, extra: ['width' => 'half', 'help' => 'Texto pequeno em maiúsculas acima do título.']),
                Field::text('title', 'Título', 160, extra: ['width' => 'half']),
                Field::textarea('description', 'Descrição', rows: 2),
                Field::buttons('heading_align', 'Alinhamento', ['center' => 'Centro', 'left' => 'Esquerda'], 'center'),
            ]),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, array<int, array{value: int, label: string}>>  $lists
     * @return array<int, array<string, mixed>>
     */
    private static function resolve(array $fields, array $lists): array
    {
        return array_map(function (array $field) use ($lists) {
            if (isset($field['options']) && is_string($field['options'])) {
                $field['options'] = $lists[$field['options']] ?? [];
            }

            if (isset($field['fields'])) {
                $field['fields'] = static::resolve($field['fields'], $lists);
            }

            return $field;
        }, $fields);
    }
}
