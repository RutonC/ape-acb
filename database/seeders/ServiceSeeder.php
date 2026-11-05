<?php

namespace Database\Seeders;

use App\Enums\ServiceKind;
use App\Models\Service;
use App\Support\SeedMedia;
use Illuminate\Database\Seeder;

/**
 * Serviços da APE, com os textos de ape.acb.co.mz. Só cria o que ainda não
 * existe: as edições feitas no painel prevalecem.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $position => $item) {
            if (Service::where('kind', ServiceKind::Service)->where('slug', $item['slug'])->exists()) {
                continue;
            }

            Service::create([
                ...collect($item)->except('photo')->all(),
                'kind' => ServiceKind::Service,
                'image_id' => SeedMedia::fromPublic($item['photo'], $item['title']['pt'], $item['title']['pt'])->id,
                'sort_order' => $position + 1,
                'is_published' => true,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            [
                'slug' => 'recrutamento-e-seleccao',
                'icon' => 'user-group',
                'photo' => 'images/ape/servicos/recrutamento-e-seleccao.png',
                'title' => ['pt' => 'Recrutamento e selecção'],
                'tagline' => ['pt' => 'Os melhores profissionais para a sua empresa'],
                'summary' => ['pt' => 'Oferecemos soluções especializadas para identificação e escolha dos melhores profissionais para várias áreas da sua empresa.'],
                'intro' => ['pt' => 'Oferecemos soluções especializadas para identificação e escolha dos melhores profissionais para várias áreas da sua empresa. O processo é acompanhado por várias actividades e testes capazes de identificar os talentos mais adequados para as vagas disponibilizadas pelas empresas.'],
                'audience' => ['pt' => 'Empresas'],
                'details' => ['pt' => [[
                    'title' => 'Porquê recrutar com a Agência Privada de Emprego?',
                    'icon' => 'check-badge',
                    'content' => '<ul><li>Reduz o tempo gasto no processo de recrutamento e selecção</li><li>Aumento da produtividade da sua empresa</li><li>Aumento da qualidade dos serviços oferecidos pela sua empresa</li><li>Destaque da visibilidade da sua empresa</li><li>Cumprimento de aspectos legais relacionados ao recrutamento e selecção</li><li>Redução de custos administrativos</li></ul>',
                ]]],
            ],
            [
                'slug' => 'centro-de-avaliacao',
                'icon' => 'clipboard-document-check',
                'photo' => 'images/ape/servicos/centro-de-avaliacao.jpg',
                'title' => ['pt' => 'Centro de Avaliação'],
                'tagline' => ['pt' => 'Avaliação de competências'],
                'summary' => ['pt' => 'Temos à disposição as mais modernas e eficazes metodologias e ferramentas de avaliação de competências.'],
                'intro' => ['pt' => 'Temos à disposição as mais modernas e eficazes metodologias e ferramentas de avaliação de competências. Essas técnicas visam estabelecer um perfil real do candidato de acordo com as vagas disponibilizadas pelas empresas, de modo que o candidato seja o mais apropriado possível para a mesma.'],
                'audience' => ['pt' => 'Empresas e candidatos'],
                'details' => ['pt' => [[
                    'title' => 'Metodologias e ferramentas',
                    'icon' => 'clipboard-document-check',
                    'content' => '<ul><li>Exercícios de dinâmica de grupos</li><li>Exercícios de apresentação</li><li>Entrevistas por competências</li><li>Testes de Simulação</li><li>Provas ou Testes de Conhecimentos e/ou Capacidades</li><li>Testes Psicotécnicos</li><li>Testes de Personalidade</li><li>Visita Domiciliar e Profiling</li></ul>',
                ]]],
            ],
            [
                'slug' => 'estagio-profissional',
                'icon' => 'academic-cap',
                'photo' => 'images/ape/servicos/estagio-profissional.png',
                'title' => ['pt' => 'Estágio Profissional'],
                'tagline' => ['pt' => 'Jovens recém-formados nas empresas'],
                'summary' => ['pt' => 'Identificamos e seleccionamos jovens recém-formados para a realização de estágios profissionais nas empresas.'],
                'intro' => ['pt' => 'Identificamos e seleccionamos jovens recém-formados para a realização de estágios profissionais nas empresas no período mínimo de 3 meses até um máximo de 12 meses.'],
                'audience' => ['pt' => 'Empresas e jovens recém-formados'],
                'timeline' => ['pt' => 'De 3 a 12 meses'],
                'details' => ['pt' => [[
                    'title' => 'Vantagens',
                    'icon' => 'check-badge',
                    'content' => '<ul><li>Aumento da capacidade de produção nas empresas</li><li>Aquisição de diferentes experiências na dinâmica de trabalho</li></ul>',
                ]]],
            ],
        ];
    }
}
