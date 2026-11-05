<?php

namespace Tests\Feature;

use App\Enums\InboxStatus;
use App\Mail\ContactMessageReceived;
use App\Mail\FormCopy;
use App\Models\ContactMessage;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('forms');
    }

    /**
     * @return array<string, string>
     */
    private function validData(array $overrides = []): array
    {
        return [
            'name' => 'Ana Macuácua',
            'email' => 'ana@exemplo.co.mz',
            'subject' => 'Pedido de informação',
            'message' => 'Gostaria de saber como inscrever a minha empresa.',
            ...$overrides,
        ];
    }

    public function test_contact_page_shows_contacts_form_and_consent_gated_map(): void
    {
        $this->get('/contactos')
            ->assertOk()
            ->assertSee('Envie-nos um e-mail')
            ->assertSee('+258 23 326 246')
            ->assertSee('recrutamento.ape@acb.co.mz')
            ->assertSee('info.ape@acb.co.mz')
            ->assertSee('name="subject"', false)
            ->assertSee('name="website"', false)
            ->assertSee('Mostrar mapa (Google Maps)')
            ->assertSee('<template x-if="show">', false);
    }

    public function test_valid_message_is_stored_emailed_and_confirmed(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->forms_recipient_email = 'recrutamento.ape@acb.co.mz';
        $settings->save();

        $this->post('/contactos', $this->validData())
            ->assertRedirect(url('/contactos').'#formulario')
            ->assertSessionHas('contact_sent');

        $message = ContactMessage::sole();
        $this->assertSame('Ana Macuácua', $message->name);
        $this->assertSame(InboxStatus::New, $message->status);
        $this->assertSame('pt', $message->locale);

        Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail) => $mail->hasTo('recrutamento.ape@acb.co.mz')
            && $mail->hasReplyTo('ana@exemplo.co.mz'));

        $this->followingRedirects()->post('/contactos', $this->validData())
            ->assertSee('Obrigado pelo seu contacto. A sua mensagem foi recebida e entraremos em contacto consigo.');
    }

    public function test_copy_only_goes_out_when_requested_and_has_fixed_text(): void
    {
        $this->post('/contactos', $this->validData());
        Mail::assertNotSent(FormCopy::class);

        $this->post('/contactos', $this->validData(['send_copy' => '1', 'message' => 'Compre remédios baratos em spam.example agora mesmo']));

        Mail::assertSent(FormCopy::class, function (FormCopy $mail) {
            $this->assertStringNotContainsString('spam.example', $mail->render());

            return $mail->hasTo('ana@exemplo.co.mz');
        });
    }

    public function test_english_submissions_keep_the_locale(): void
    {
        $this->post('/en/contact', $this->validData())->assertRedirect(url('/en/contact').'#formulario');

        $this->assertSame('en', ContactMessage::sole()->locale);
    }

    public function test_validation_errors_are_shown(): void
    {
        $this->from('/contactos')
            ->post('/contactos', $this->validData(['name' => '', 'email' => 'invalido', 'message' => 'curto', 'subject' => '']))
            ->assertRedirect('/contactos')
            ->assertSessionHasErrors(['name', 'email', 'message', 'subject']);

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_honeypot_submissions_are_silently_discarded(): void
    {
        $this->post('/contactos', $this->validData(['website' => 'http://spam.example']))
            ->assertRedirect()
            ->assertSessionHas('contact_sent');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_submissions_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/contactos', $this->validData())->assertRedirect();
        }

        $this->post('/contactos', $this->validData())->assertTooManyRequests();
        $this->assertSame(5, ContactMessage::count());
    }
}
