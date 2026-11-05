<?php

namespace Tests\Feature;

use App\Enums\InboxStatus;
use App\Mail\CompanyRegistrationReceived;
use App\Mail\FormCopy;
use App\Models\CompanyRegistration;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApeContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_shows_the_old_site_content_in_order(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                'Está à procura de um', 'Procurar por categoria', 'Pretende recrutar?', 'Vagas recentes',
                'Bem vindo à APE', 'Nossos parceiros', 'Vagas por local', 'Notícias', 'Testemunhos',
            ], false)
            ->assertSee('Contabilidade e Auditoria')
            ->assertSee('Vaga de Chefe de Oficina')
            ->assertSee('BFZ')
            ->assertSee('Ministério da Baviera')
            ->assertSee('Marketing &amp; Vendas', false)
            ->assertSee('De momento não há vagas abertas');
    }

    public function test_seeded_home_has_no_invented_figures_or_template_text(): void
    {
        $html = $this->get('/')->getContent();

        foreach (['lorem', 'New York', 'We are hiring', 'Tornar Membro', '1893'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $html, "Encontrado: {$forbidden}");
        }
    }

    public function test_all_old_site_content_was_migrated(): void
    {
        $this->assertSame(4, Post::count());
        $this->assertSame(['recrutamento-e-seleccao', 'centro-de-avaliacao', 'estagio-profissional'], Service::orderBy('sort_order')->pluck('slug')->all());

        $this->get('/sobre-nos')
            ->assertSee('criada em 2017')
            ->assertSee('É uma empresa?')
            ->assertSee('É um candidato?')
            ->assertSee('Testemunhos');

        $this->get('/servicos/recrutamento-e-seleccao')->assertSee('Redução de custos administrativos');
        $this->get('/servicos/centro-de-avaliacao')->assertSee('Testes Psicotécnicos');
        $this->get('/servicos/estagio-profissional')->assertSee('De 3 a 12 meses');
        $this->get('/noticias/a-acb-lanca-um-novo-servico-denominado-agencia-privada-de-emprego')->assertOk()->assertSee('Agência Privada de Emprego');
        $this->get('/parceiros')->assertSee('SEQUA')->assertSee('Universidade Pedagógica');
        $this->get('/contactos')->assertSee('+258 84 373 2764');
    }

    public function test_seeded_pages_can_be_opened_and_saved_in_the_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (Page::all() as $page) {
            $data = $this->getJson("/api/admin/pages/{$page->id}")->assertOk()->json('data');

            $this->putJson("/api/admin/pages/{$page->id}", [
                'title' => $data['title'], 'slug' => $data['slug'], 'status' => $data['status'],
                'is_homepage' => $data['is_homepage'], 'content' => $data['content'],
                'meta_title' => $data['meta_title'], 'meta_description' => $data['meta_description'],
            ])->assertOk();
        }
    }

    public function test_company_registration_is_stored_and_notified(): void
    {
        $this->get('/empresas')->assertOk()->assertSee('Inscreva a sua empresa')->assertSee('Redução de custos administrativos');

        $data = ['company_name' => 'Porto Lda', 'name' => 'João Sitoe', 'phone' => '+258 84 000 0000', 'email' => 'joao@porto.co.mz'];

        $this->post('/empresas', $data)->assertRedirect(url('/empresas').'#inscricao')->assertSessionHas('company_sent');

        $registration = CompanyRegistration::sole();
        $this->assertSame(InboxStatus::New, $registration->status);
        Mail::assertSent(CompanyRegistrationReceived::class, fn ($mail) => $mail->hasTo('recrutamento.ape@acb.co.mz'));
        Mail::assertNotSent(FormCopy::class);

        $this->post('/empresas', [...$data, 'send_copy' => '1']);
        Mail::assertSent(FormCopy::class, fn ($mail) => $mail->hasTo('joao@porto.co.mz'));

        $this->post('/empresas', ['company_name' => '', 'name' => '', 'phone' => 'abc', 'email' => 'x'])->assertSessionHasErrors(['company_name', 'name', 'phone', 'email']);
        $this->post('/empresas', [...$data, 'website' => 'spam'])->assertSessionHas('company_sent');
        $this->assertSame(2, CompanyRegistration::count());
    }

    public function test_admin_manages_company_registrations_with_its_own_permission(): void
    {
        $registration = CompanyRegistration::create(['company_name' => 'Porto Lda', 'name' => 'João', 'phone' => '840000000', 'email' => 'j@p.co.mz', 'status' => InboxStatus::New]);

        $this->actingAs(User::factory()->manager()->create());

        $this->getJson('/api/admin/inbox/counts')->assertJsonPath('data.companies', 1);
        $this->getJson("/api/admin/company-registrations/{$registration->id}")->assertOk()->assertJsonPath('data.status', 'read');
        $this->putJson("/api/admin/company-registrations/{$registration->id}", ['status' => 'replied', 'notes' => 'Contactada.'])->assertOk()->assertJsonPath('data.notes', 'Contactada.');
        $this->deleteJson("/api/admin/company-registrations/{$registration->id}")->assertNoContent();
    }

    public function test_old_addresses_redirect_in_both_formats(): void
    {
        foreach (['/index.php', ''] as $prefix) {
            $this->get($prefix.'/vagas/20-mecanica/31-vaga-de-chefe-de-oficina')->assertRedirect(url('/vagas/vaga-de-chefe-de-oficina'));
            $this->get($prefix.'/vagas/20-mecanica')->assertRedirect(url('/vagas?categoria=mecanica'));
            $this->get($prefix.'/vagas/19-operador/99-vaga-que-nao-existe')->assertRedirect(url('/vagas'));
            $this->get($prefix.'/servicos/15-recrutamento-e-seleccao')->assertRedirect(url('/servicos/recrutamento-e-seleccao'));
            $this->get($prefix.'/servicos/14-centro-de-avaliacao')->assertRedirect(url('/servicos/centro-de-avaliacao'));
            $this->get($prefix.'/servicos/9-estagio-profissional')->assertRedirect(url('/servicos/estagio-profissional'));
            $this->get($prefix.'/noticias/12-parceria-sfp-com-essor')->assertRedirect(url('/noticias/parceria-sfp-com-essor'));
            $this->get($prefix.'/13-parceiros/19-bfz')->assertRedirect(url('/parceiros'));
            $this->get($prefix.'/21-testemunhos/42-contabilidade-automatizada')->assertRedirect(url('/sobre-nos#testemunhos'));
            $this->get($prefix.'/11-sem-categoria/1-home-page')->assertRedirect(url('/').'/');
        }

        foreach (['/index.php/vagas' => '/vagas', '/index.php/sobre-nos' => '/sobre-nos', '/index.php/contactos' => '/contactos', '/index.php/noticias' => '/noticias', '/index.php/servicos' => '/servicos', '/index.php' => '/'] as $old => $new) {
            $this->get($old)->assertRedirect($new === '/' ? url('/').'/' : url($new));
        }

        $this->get('/vagas')->assertOk();
        $this->get('/vagas/vaga-de-chefe-de-oficina')->assertOk();
    }
}
