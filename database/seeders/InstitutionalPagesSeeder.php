<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Support\SeedMedia;
use Illuminate\Database\Seeder;

/**
 * Páginas institucionais. Sobre Nós e Empresas usam os textos de ape.acb.co.mz.
 * Privacidade e Termos são propostas, a validar pela ACB (ver docs/TEXTOS-PROPOSTOS.md).
 * Só cria o que ainda não existe: as edições feitas no painel prevalecem.
 */
class InstitutionalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode((string) file_get_contents(database_path('data/ape.json')), true, flags: JSON_THROW_ON_ERROR);
        $photo = SeedMedia::fromPublic('images/ape/geral/agencia-ape.jpg', 'Agência Privada de Emprego', 'Equipa à porta da Agência Privada de Emprego, na Beira')->id;

        $this->create('sobre-nos', 'Sobre Nós', 'A Agência Privada de Emprego é uma empresa privada moçambicana, criada em 2017, como parte integrante da Associação Comercial da Beira.', [
            $this->block('about', 'white', [
                'title' => 'A Agência Privada de Emprego',
                'content' => '<p>A Agência Privada de Emprego, Lda (APE) é uma empresa privada moçambicana, criada em 2017, como parte integrante da Associação Comercial da Beira (ACB). A sua sede está localizada na cidade da Beira.</p><p>Trabalhamos para o melhoramento do ambiente de emprego e desenvolvimento do mercado de trabalho identificando pessoal apropriado para as empresas. Para o efeito, utilizamos técnicas e ferramentas actualizadas que garantem a identificação de talentos de acordo com o perfil profissional dos candidatos e os perfis estabelecidos pelas empresas.</p>',
                'image' => $photo,
                'image_position' => 'right',
            ]),
            $this->block('cards', 'light', [
                'heading_align' => 'center',
                'style' => 'service',
                'columns' => 2,
                'items' => [
                    [
                        'icon' => 'building-office-2',
                        'title' => 'É uma empresa?',
                        'text' => 'Oferecemos para si serviços de recrutamento e selecção de trabalhadores, estágio profissional e contamos com um centro de avaliação de competências que permite recrutar de forma rápida e eficaz profissionais de qualidade de acordo com a área de acção. Acreditamos que um pessoal qualificado é a chave de uma empresa de sucesso. Deste modo, asseguramos a selecção de pessoal qualificado com base em várias experiências e actividades capazes de avaliar as competências dos candidatos usando as melhores metodologias, ferramentas e tecnologias necessárias para garantir a eficácia do processo. A nossa meta é garantir que a sua empresa tenha pessoal qualificado e adequado para cada área de trabalho, aumentando a produtividade e qualidade dos serviços oferecidos por si.',
                        'link_label' => 'Inscrever minha empresa',
                        'link' => ['type' => 'route', 'route' => 'companies'],
                    ],
                    [
                        'icon' => 'user-group',
                        'title' => 'É um candidato?',
                        'text' => 'Nosso compromisso é encontrar vagas adequadas de acordo com o seu perfil pessoal e profissional. Efectuamos vários testes e actividades capazes de distinguir suas competências e descobrir as melhores áreas de acção para si com a finalidade de garantir o seu sucesso profissional.',
                        'link_label' => 'Candidate-se já',
                        'link' => ['type' => 'route', 'route' => 'apply'],
                    ],
                ],
            ]),
            $this->block('videos', 'white', [
                'anchor' => 'testemunhos',
                'title' => 'Testemunhos',
                'items' => HomePageSeeder::videos($data['video_covers']),
            ]),
            $this->block('logos', 'light', [
                'title' => 'Nossos parceiros',
                'items' => HomePageSeeder::partners($data['partners']),
            ]),
        ]);

        $this->create('parceiros', 'Parceiros', 'Instituições parceiras da Agência Privada de Emprego.', [
            $this->block('logos', 'white', [
                'title' => 'Nossos parceiros',
                'items' => HomePageSeeder::partners($data['partners']),
            ]),
        ]);

        $this->create('politica-de-privacidade', 'Política de Privacidade', 'Como a Agência Privada de Emprego trata os dados pessoais recolhidos neste site.', [
            $this->block('rich_text', 'white', ['width' => 'narrow', 'content' => $this->privacy()]),
        ]);

        $this->create('termos-de-utilizacao', 'Termos de Utilização', 'Condições de utilização do site da Agência Privada de Emprego.', [
            $this->block('rich_text', 'white', ['width' => 'narrow', 'content' => $this->terms()]),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function create(string $slug, string $title, string $description, array $blocks): Page
    {
        return Page::withTrashed()->firstOrCreate(['slug' => $slug], [
            'title' => ['pt' => $title],
            'status' => PageStatus::Published,
            'meta_description' => ['pt' => $description],
            'content' => ['pt' => $blocks],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: string, data: array<string, mixed>}
     */
    private function block(string $type, string $background, array $data): array
    {
        return ['type' => $type, 'data' => [...$data, 'background' => $background, 'is_active' => true]];
    }

    private function privacy(): string
    {
        return <<<'HTML'
            <h2>Quem trata os dados</h2>
            <p>A Agência Privada de Emprego, Lda (APE), parte integrante da Associação Comercial da Beira, com sede na cidade da Beira.</p>
            <h2>Que dados recolhemos</h2>
            <ul>
                <li><strong>Candidaturas:</strong> nome, data de nascimento, sexo, documento de registo, habilitações, experiência, endereço, e-mail, telefones, observações e currículo.</li>
                <li><strong>Inscrição de empresas:</strong> nome da empresa, nome, telefone e e-mail.</li>
                <li><strong>Contactos:</strong> nome, e-mail, assunto e mensagem.</li>
            </ul>
            <h2>Para que usamos os dados</h2>
            <p>Para os processos de recrutamento e selecção, para responder aos pedidos recebidos e para contactar as empresas inscritas.</p>
            <h2>Quem tem acesso</h2>
            <p>Os currículos e as candidaturas ficam num arquivo privado, sem acesso público, e só podem ser consultados por colaboradores autorizados da APE. Não são publicados no site.</p>
            <h2>Os seus direitos</h2>
            <p>Pode pedir para consultar, corrigir ou eliminar os seus dados através de <a href="mailto:info.ape@acb.co.mz">info.ape@acb.co.mz</a>.</p>
            <h2>Cookies</h2>
            <p>O site usa os cookies necessários ao seu funcionamento. O mapa e a medição de audiências só são activados com o seu consentimento.</p>
            HTML;
    }

    private function terms(): string
    {
        return <<<'HTML'
            <h2>Utilização do site</h2>
            <p>Este site apresenta os serviços e as vagas da Agência Privada de Emprego. A informação publicada pode ser alterada sem aviso prévio.</p>
            <h2>Candidaturas</h2>
            <p>O envio de uma candidatura não garante a contratação nem a inclusão num processo de selecção. O candidato é responsável pela veracidade dos dados e dos documentos enviados.</p>
            <h2>Propriedade</h2>
            <p>Os textos, logótipos e imagens deste site pertencem à APE, à Associação Comercial da Beira ou aos respectivos titulares.</p>
            <h2>Contactos</h2>
            <p>Para qualquer questão sobre estes termos, escreva para <a href="mailto:info.ape@acb.co.mz">info.ape@acb.co.mz</a>.</p>
            HTML;
    }
}
