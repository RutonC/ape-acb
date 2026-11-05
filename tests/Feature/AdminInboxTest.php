<?php

namespace Tests\Feature;

use App\Enums\InboxStatus;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\RolesSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_new_message_marks_it_read_and_updates_the_counter(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $message = ContactMessage::factory()->create();
        ContactMessage::factory()->create();

        $this->getJson('/api/admin/inbox/counts')->assertOk()->assertJsonPath('data.messages', 2);

        $this->getJson("/api/admin/messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'read')
            ->assertJsonPath('data.message', $message->message);

        $this->assertSame(InboxStatus::Read, $message->fresh()->status);
        $this->getJson('/api/admin/inbox/counts')->assertJsonPath('data.messages', 1);
    }

    public function test_replied_message_stays_replied_when_opened(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $message = ContactMessage::factory()->create(['status' => InboxStatus::Replied]);

        $this->getJson("/api/admin/messages/{$message->id}")->assertJsonPath('data.status', 'replied');
    }

    public function test_message_status_and_notes_can_be_updated_and_filtered(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $message = ContactMessage::factory()->create();
        ContactMessage::factory()->create(['status' => InboxStatus::Replied]);

        $this->putJson("/api/admin/messages/{$message->id}", ['status' => 'replied', 'notes' => 'Respondido por telefone'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Respondido por telefone');

        $this->putJson("/api/admin/messages/{$message->id}", ['status' => 'invalido'])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->getJson('/api/admin/messages?filter[status]=replied')->assertJsonCount(2, 'data');
        $this->getJson('/api/admin/messages?filter[status]=new')->assertJsonCount(0, 'data');
    }

    public function test_messages_can_be_searched_changed_in_bulk_and_deleted(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $ana = ContactMessage::factory()->create(['name' => 'Ana Machava']);
        $rui = ContactMessage::factory()->create(['name' => 'Rui Sitoe']);

        $this->getJson('/api/admin/messages?search=Machava')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ana->id);

        $this->postJson('/api/admin/messages/bulk-status', ['ids' => [$ana->id, $rui->id], 'status' => 'read'])->assertNoContent();
        $this->assertSame(0, ContactMessage::where('status', InboxStatus::New)->count());

        $this->postJson('/api/admin/messages/bulk-delete', ['ids' => [$ana->id]])->assertNoContent();
        $this->deleteJson("/api/admin/messages/{$rui->id}")->assertNoContent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_custom_role_only_reaches_its_inbox_areas(): void
    {
        $this->seed(RolesSeeder::class);
        $role = Role::findOrCreate('Recepção', 'web');
        $role->givePermissionTo('messages');
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        ContactMessage::factory()->create();

        $this->getJson('/api/admin/messages')->assertOk();
        $this->getJson('/api/admin/inbox/counts')
            ->assertOk()
            ->assertJsonPath('data.messages', 1);
    }

    public function test_guests_cannot_reach_the_inbox(): void
    {
        $this->getJson('/api/admin/messages')->assertUnauthorized();
        $this->getJson('/api/admin/inbox/counts')->assertUnauthorized();
    }
}
