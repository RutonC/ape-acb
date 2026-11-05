<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Support\SeedMedia;
use Illuminate\Database\Seeder;

/**
 * Página Início, com os textos e as fotografias de ape.acb.co.mz. Não altera uma
 * página inicial que já exista: as edições feitas no painel prevalecem.
 */
class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        if (Page::withTrashed()->where('is_homepage', true)->orWhere('slug', 'inicio')->exists()) {
            $this->command?->info('Página inicial já existe, não foi alterada.');

            return;
        }

        Page::create([
            'title' => ['pt' => 'Início'],
            'slug' => 'inicio',
            'is_homepage' => true,
            'status' => PageStatus::Published,
            'meta_title' => ['pt' => 'APE, Agência Privada de Emprego | Vagas e recrutamento na Beira'],
            'meta_description' => ['pt' => 'Agência Privada de Emprego da Associação Comercial da Beira: vagas de emprego, recrutamento e selecção, centro de avaliação e estágio profissional.'],
            'content' => ['pt' => $this->blocks()],
        ]);
    }

    /**
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    private function blocks(): array
    {
        $route = fn (string $route, string $label): array => ['type' => 'route', 'route' => $route, 'label' => $label];
        $data = json_decode((string) file_get_contents(database_path('data/ape.json')), true, flags: JSON_THROW_ON_ERROR);

        return [
            $this->block('job_hero', 'light', [
                'title' => 'Está à procura de um emprego?',
                'highlight' => 'emprego',
                'text' => 'Encontre a solução certa para si. Envie já o seu CV.',
                'image' => SeedMedia::fromPublic('images/ape/geral/agencia-ape.jpg', 'Agência Privada de Emprego', 'Equipa à porta da Agência Privada de Emprego, na Beira')->id,
                'image_2' => SeedMedia::fromPublic('images/ape/noticias/a-acb-lanca-um-novo-servico-denominado-agencia-p-3.jpg', 'Lançamento da APE', 'Lançamento da Agência Privada de Emprego')->id,
            ]),
            $this->block('job_categories', 'white', [
                'title' => 'Procurar por categoria',
                'description' => 'Veja as vagas que temos para si.',
            ]),
            $this->block('hiring_banner', 'white', [
                'title' => 'Pretende recrutar?',
                'text' => 'Seleccionamos e identificamos o pessoal certo para as áreas de actividade da sua empresa.',
                'button' => $route('companies', 'Inscreva-se já'),
            ]),
            $this->block('job_list', 'white', [
                'title' => 'Vagas recentes',
                'description' => 'Candidate-se às vagas abertas ou envie-nos o seu currículo.',
                'limit' => 8,
                'button_label' => 'Ver todas as vagas',
            ]),
            $this->block('about', 'white', [
                'title' => 'Bem vindo à APE',
                'content' => '<p>A Agência Privada de Emprego, Lda (APE) é uma empresa privada moçambicana, criada em 2017, como parte integrante da Associação Comercial da Beira (ACB). Trabalhamos para o melhoramento do ambiente de emprego e desenvolvimento do mercado de trabalho, identificando pessoal apropriado para as empresas.</p>',
                'image' => SeedMedia::fromPublic('images/ape/geral/agencia-ape.jpg', 'Agência Privada de Emprego', 'Equipa à porta da Agência Privada de Emprego, na Beira')->id,
                'image_position' => 'left',
                'button' => $route('apply', 'Candidate-se já'),
                'secondary_button' => $route('about', 'Saber mais'),
            ]),
            $this->block('counters', 'white', [
                'items' => [
                    ['value' => 2017, 'label' => 'Ano de criação', 'text' => 'Parte integrante da Associação Comercial da Beira.'],
                    ['value' => 3, 'label' => 'Serviços', 'text' => 'Recrutamento e selecção, centro de avaliação e estágio profissional.'],
                    ['value' => 6, 'label' => 'Categorias de vagas', 'text' => 'Da administração à mecânica.'],
                    ['value' => 7, 'label' => 'Parceiros', 'text' => 'Instituições moçambicanas e internacionais.'],
                ],
            ]),
            $this->block('logos', 'white', [
                'title' => 'Nossos parceiros',
                'items' => $this->partners($data['partners']),
            ]),
            $this->block('job_locations', 'white', [
                'title' => 'Vagas por local',
                'description' => 'Encontre oportunidades perto de si.',
            ]),
            $this->block('blog', 'white', [
                'title' => 'Notícias',
                'description' => 'Actividades da APE e da Associação Comercial da Beira.',
                'limit' => 3,
                'button_label' => 'Ver todas as notícias',
            ]),
            $this->block('videos', 'white', [
                'title' => 'Testemunhos',
                'items' => $this->videos($data['video_covers']),
            ]),
            $this->block('cta', 'white', [
                'title' => 'Está à procura de um emprego?',
                'text' => 'Envie já o seu CV.',
                'button' => $route('apply', 'Candidate-se já'),
            ]),
        ];
    }

    /**
     * @param  array<int, array{name: string, image: string}>  $partners
     * @return array<int, array<string, mixed>>
     */
    public static function partners(array $partners): array
    {
        return array_map(fn (array $partner) => [
            'name' => $partner['name'],
            'image' => SeedMedia::fromPublic($partner['image'], $partner['name'], 'Logótipo de '.$partner['name'], 'parceiro-'.pathinfo($partner['image'], PATHINFO_FILENAME))->id,
        ], $partners);
    }

    /**
     * @param  array<string, string>  $covers
     * @return array<int, array<string, mixed>>
     */
    public static function videos(array $covers): array
    {
        $titles = [
            'contabilidade' => ['Contabilidade Automatizada', 'testemunho-contabilidade'],
            'excel-avancado' => ['Excel Avançado', 'testemunho-excel-avancado'],
            'hst' => ['H.S.T.', 'testemunho-hst'],
            'marketing-vendas' => ['Marketing & Vendas', 'testemunho-marketing-vendas'],
        ];

        $items = [];

        foreach ($titles as $key => [$title, $file]) {
            $items[] = [
                'title' => $title,
                'cover' => SeedMedia::fromPublic($covers[$key], $title, 'Testemunho: '.$title)->id,
                'video' => SeedMedia::fromPath(database_path("data/videos/{$file}.mp4"), $title, 'Testemunho: '.$title)->id,
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: string, data: array<string, mixed>}
     */
    private function block(string $type, string $background, array $data): array
    {
        return ['type' => $type, 'data' => [...$data, 'background' => $background, 'is_active' => true]];
    }
}
