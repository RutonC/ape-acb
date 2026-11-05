<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Mail\FormCopy;
use App\Mail\JobApplicationReceived;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobOpening;
use App\Models\User;
use Database\Seeders\JobSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JobsPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function application(array $overrides = []): array
    {
        return [
            'name' => 'Ana Macuácua',
            'birth_date' => '1998-03-10',
            'gender' => 'Feminino',
            'document_type' => 'BI',
            'document_number' => '110100123456A',
            'qualifications' => '12.ª classe',
            'experience_years' => 3,
            'address' => 'Rua do Porto, Beira',
            'email' => 'ana@exemplo.co.mz',
            'phone' => '+258 84 123 4567',
            'cv' => UploadedFile::fake()->create('cv-ana.pdf', 200, 'application/pdf'),
            'consent' => '1',
            ...$overrides,
        ];
    }

    public function test_old_jobs_are_migrated_closed_and_cannot_receive_applications(): void
    {
        $this->seed(JobSeeder::class);

        $this->assertSame(5, JobOpening::count());
        $this->assertSame(0, JobOpening::open()->count());
        $this->assertSame(6, JobCategory::count());

        $this->get('/vagas/vaga-de-chefe-de-oficina')
            ->assertOk()
            ->assertSee('Vaga de Chefe de Oficina')
            ->assertSee('Encerrada')
            ->assertSee('Candidatura espontânea')
            ->assertDontSee('name="birth_date"', false)
            ->assertSee('noindex', false);

        $job = JobOpening::where('slug', 'vaga-de-chefe-de-oficina')->sole();

        $this->from('/candidatura')
            ->post('/candidatura', $this->application(['job_opening_id' => $job->id]))
            ->assertSessionHasErrors('job_opening_id');

        $this->assertSame(0, JobApplication::count());
    }

    public function test_listing_filters_by_category_location_state_and_keyword(): void
    {
        $mechanics = JobCategory::create(['slug' => 'mecanica', 'name' => ['pt' => 'Mecânica']]);
        $office = JobCategory::create(['slug' => 'gestao', 'name' => ['pt' => 'Gestão']]);

        JobOpening::factory()->create(['slug' => 'torneiro', 'title' => ['pt' => 'Torneiro Mecânico'], 'location' => ['pt' => 'Beira'], 'job_category_id' => $mechanics->id]);
        JobOpening::factory()->closed()->create(['slug' => 'gerente', 'title' => ['pt' => 'Gerente de Loja'], 'location' => ['pt' => 'Nampula'], 'job_category_id' => $office->id]);

        $this->get('/vagas')->assertSee('Torneiro Mecânico')->assertSee('Gerente de Loja');
        $this->get('/vagas?categoria=gestao')->assertDontSee('Torneiro Mecânico')->assertSee('Gerente de Loja');
        $this->get('/vagas?local=Beira')->assertSee('Torneiro Mecânico')->assertDontSee('Gerente de Loja');
        $this->get('/vagas?estado=abertas')->assertSee('Torneiro Mecânico')->assertDontSee('Gerente de Loja');
        $this->get('/vagas?estado=encerradas')->assertDontSee('Torneiro Mecânico')->assertSee('Gerente de Loja');
        $this->get('/vagas?q=mec%C3%A2nico')->assertSee('Torneiro Mecânico')->assertDontSee('Gerente de Loja');
        $this->get('/vagas?q=inexistente')->assertSee('Nenhuma vaga encontrada');
        $this->get('/vagas?q[]=x&local[]=y&categoria[]=z&estado[]=w&tipo[]=k')->assertOk()->assertSee('Torneiro Mecânico');
    }

    public function test_past_deadline_counts_as_closed(): void
    {
        $job = JobOpening::factory()->create(['closes_at' => today()->subDay()]);

        $this->assertFalse($job->isOpen());
        $this->assertSame(0, JobOpening::open()->count());
        $this->assertSame(1, JobOpening::closed()->count());
    }

    public function test_empty_optional_fields_do_not_appear(): void
    {
        $job = JobOpening::factory()->create();

        $this->get($job->url())->assertOk()->assertDontSee('Salário')->assertDontSee('Data limite')->assertDontSee('Experiência');

        $job->update(['salary' => '20 000 MT', 'experience' => '2 anos']);

        $this->get($job->url())->assertSee('Salário')->assertSee('20 000 MT')->assertSee('Experiência');
    }

    public function test_open_job_has_structured_data_and_form(): void
    {
        $job = JobOpening::factory()->create();

        $this->get($job->url())
            ->assertOk()
            ->assertSee('"@type":"JobPosting"', false)
            ->assertSee('name="birth_date"', false)
            ->assertSee('name="job_opening_id"', false);
    }

    public function test_application_keeps_cv_private_and_document_encrypted(): void
    {
        $job = JobOpening::factory()->create();

        $this->post('/candidatura', $this->application(['job_opening_id' => $job->id]))
            ->assertRedirect($job->url().'#candidatura')
            ->assertSessionHas('application_sent');

        $application = JobApplication::sole();

        $this->assertSame(ApplicationStatus::New, $application->status);
        $this->assertSame($job->id, $application->job_opening_id);
        $this->assertSame('cv-ana.pdf', $application->cv_original_name);
        Storage::disk('local')->assertExists($application->cv_path);
        Storage::disk('public')->assertMissing($application->cv_path);
        $this->assertStringStartsWith('candidaturas/', $application->cv_path);

        $this->assertSame('110100123456A', $application->document_number);
        $this->assertStringNotContainsString('110100123456A', (string) $application->getRawOriginal('document_number'));

        Mail::assertSent(JobApplicationReceived::class, fn (JobApplicationReceived $mail) => $mail->hasReplyTo('ana@exemplo.co.mz') && $mail->rawAttachments === [] && $mail->attachments === []);
        Mail::assertNotSent(FormCopy::class);
    }

    public function test_spontaneous_application_and_requested_copy(): void
    {
        $this->post('/candidatura', $this->application(['send_copy' => '1']))
            ->assertRedirect(url('/candidatura').'#candidatura');

        $this->assertNull(JobApplication::sole()->job_opening_id);
        $this->assertSame('Candidatura espontânea', JobApplication::sole()->positionLabel());
        Mail::assertSent(FormCopy::class, fn (FormCopy $mail) => $mail->hasTo('ana@exemplo.co.mz'));
    }

    public function test_application_validates_required_fields_and_file_type(): void
    {
        $this->from('/candidatura')
            ->post('/candidatura', $this->application([
                'name' => '', 'birth_date' => '', 'gender' => 'Outro', 'address' => '', 'phone' => '', 'consent' => '',
                'cv' => UploadedFile::fake()->create('virus.exe', 10),
            ]))
            ->assertSessionHasErrors(['name', 'birth_date', 'gender', 'address', 'phone', 'consent', 'cv']);

        $this->from('/candidatura')
            ->post('/candidatura', $this->application(['cv' => UploadedFile::fake()->create('grande.pdf', 20481, 'application/pdf')]))
            ->assertSessionHasErrors('cv');

        $this->assertSame(0, JobApplication::count());
    }

    public function test_honeypot_discards_application(): void
    {
        $this->post('/candidatura', $this->application(['website' => 'http://spam.example']))->assertSessionHas('application_sent');

        $this->assertSame(0, JobApplication::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_cv_download_needs_the_applications_permission(): void
    {
        $this->post('/candidatura', $this->application());
        $application = JobApplication::sole();

        // Sem sessão.
        $this->getJson("/api/admin/applications/{$application->id}/cv")->assertUnauthorized();
        // O CV não tem endereço público.
        $this->assertNotSame(200, $this->get('/storage/'.$application->cv_path)->getStatusCode());

        // O gestor de conteúdos não recebe esta área por omissão.
        $this->actingAs(User::factory()->manager()->create());
        $this->getJson("/api/admin/applications/{$application->id}/cv")->assertForbidden();
        $this->getJson('/api/admin/applications')->assertForbidden();
        auth()->forgetGuards();

        $this->actingAs(User::factory()->admin()->create());
        $this->get("/api/admin/applications/{$application->id}/cv")->assertOk()->assertDownload('cv-ana.pdf');
    }

    public function test_admin_sees_personal_data_only_in_detail_and_deleting_removes_cv(): void
    {
        $this->post('/candidatura', $this->application());
        $application = JobApplication::sole();
        $this->actingAs(User::factory()->admin()->create());

        $list = $this->getJson('/api/admin/applications')->assertOk()->json('data.0');
        $this->assertArrayNotHasKey('document_number', $list);
        $this->assertArrayNotHasKey('birth_date', $list);
        $this->assertArrayNotHasKey('cv_path', $list);

        $this->getJson("/api/admin/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.document_number', '110100123456A')
            ->assertJsonPath('data.status', ApplicationStatus::Reviewing->value);

        $this->deleteJson("/api/admin/applications/{$application->id}")->assertNoContent();
        Storage::disk('local')->assertMissing($application->cv_path);
    }

    public function test_admin_manages_jobs_and_categories(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $category = $this->postJson('/api/admin/job-categories', ['slug' => 'logistica', 'icon' => 'briefcase', 'name' => ['pt' => 'Logística']])
            ->assertCreated()
            ->json('data.id');

        $id = $this->postJson('/api/admin/jobs', [
            'slug' => 'motorista',
            'title' => ['pt' => 'Motorista'],
            'location' => ['pt' => 'Beira'],
            'description' => ['pt' => '<p>Carta de pesados.</p>'],
            'job_category_id' => $category,
            'positions' => 2,
            'salary' => '20 000 MT',
            'is_open' => true,
        ])->assertCreated()->assertJsonPath('data.category_text', 'Logística')->json('data.id');

        $this->assertSame(today()->toDateString(), JobOpening::find($id)->posted_at->toDateString());
        $this->get('/vagas/motorista')->assertOk()->assertSee('Motorista')->assertSee('20 000 MT');

        $this->putJson("/api/admin/jobs/{$id}", ['slug' => 'motorista', 'title' => ['pt' => 'Motorista'], 'is_open' => false])->assertOk();
        $this->get('/vagas/motorista')->assertSee('Encerrada');

        $this->deleteJson("/api/admin/job-categories/{$category}")->assertNoContent();
        $this->assertNull(JobOpening::find($id)->job_category_id);
    }
}
