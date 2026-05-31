<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_submit_feedback(): void
    {
        $this->post(route('feedback.store'), [
            'subject' => 'x',
            'message' => 'y',
        ])->assertRedirect(route('login'));
    }

    public function test_signed_in_user_submits_feedback_with_auto_filled_identity(): void
    {
        $user = User::factory()->create(['name' => 'Napat', 'email' => 'n@test.com']);

        $this->actingAs($user)
            ->post(route('feedback.store'), [
                'subject' => 'Cannot scan QR on iPhone',
                'message' => 'Camera permission prompt never fires.',
                'category' => 'bug',
                'page_url' => 'https://example.com/scanner',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $lead = Lead::firstOrFail();
        $this->assertSame(Lead::SOURCE_FEEDBACK, $lead->source);
        $this->assertSame('Napat', $lead->name);
        $this->assertSame('n@test.com', $lead->email);
        $this->assertSame('Camera permission prompt never fires.', $lead->message);
        $this->assertSame('Cannot scan QR on iPhone', $lead->context['subject']);
        $this->assertSame('bug', $lead->context['category']);
        $this->assertSame('https://example.com/scanner', $lead->context['page_url']);
        $this->assertSame($user->id, $lead->context['user_id']);
    }

    public function test_feedback_captures_current_workspace_in_context(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Demo School', 'slug' => 'demo-'.uniqid()]);
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('feedback.store'), [
                'subject' => 'Test',
                'message' => 'Hi',
            ]);

        $lead = Lead::firstOrFail();
        $this->assertSame($workspace->id, $lead->context['workspace_id']);
        $this->assertSame('Demo School', $lead->context['workspace_name']);
        $this->assertSame('Demo School', $lead->company);
    }

    public function test_validation_rejects_missing_subject_or_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('feedback.store'), ['message' => 'no subject'])
            ->assertSessionHasErrors('subject');

        $this->actingAs($user)
            ->post(route('feedback.store'), ['subject' => 'no message'])
            ->assertSessionHasErrors('message');

        $this->assertSame(0, Lead::count());
    }

    public function test_validation_rejects_invalid_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('feedback.store'), [
                'subject' => 'x',
                'message' => 'y',
                'category' => 'rant',
            ])
            ->assertSessionHasErrors('category');
    }

    public function test_marketing_contact_form_still_defaults_to_contact_source(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Visitor',
            'email' => 'v@test.com',
            'message' => 'Pricing question',
        ])->assertRedirect();

        $lead = Lead::firstOrFail();
        $this->assertSame(Lead::SOURCE_CONTACT, $lead->source);
    }

    public function test_feedback_accepts_an_image_attachment_and_stores_it_privately(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('bug.png', 800, 600);

        $this->actingAs($user)
            ->post(route('feedback.store'), [
                'subject' => 'crash',
                'message' => 'looks like this',
                'attachment' => $file,
            ])
            ->assertRedirect();

        $lead = Lead::firstOrFail();
        $path = $lead->context['attachment_path'];
        $this->assertNotEmpty($path);
        $this->assertStringStartsWith('feedback/', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame('bug.png', $lead->context['attachment_original']);
        $this->assertStringStartsWith('image/', $lead->context['attachment_mime']);
    }

    public function test_feedback_rejects_non_image_attachments(): void
    {
        $user = User::factory()->create();
        $pdf = UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf');

        $this->actingAs($user)
            ->post(route('feedback.store'), [
                'subject' => 'x',
                'message' => 'y',
                'attachment' => $pdf,
            ])
            ->assertSessionHasErrors('attachment');

        $this->assertSame(0, Lead::count());
    }

    public function test_admin_can_stream_attachment_but_anonymous_cannot(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('shot.png');

        $this->actingAs($user)->post(route('feedback.store'), [
            'subject' => 'x', 'message' => 'y', 'attachment' => $file,
        ]);

        $lead = Lead::firstOrFail();

        // Guests can't peek at attachments.
        $this->get(route('admin.leads.attachment', $lead))->assertRedirect();

        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->role_id = \App\Models\Role::where('key', 'admin')->value('id');
        $admin->save();

        $this->actingAs($admin)
            ->get(route('admin.leads.attachment', $lead))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_deleting_the_lead_also_removes_the_attachment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('keep.png');

        $this->actingAs($user)->post(route('feedback.store'), [
            'subject' => 'x', 'message' => 'y', 'attachment' => $file,
        ]);

        $lead = Lead::firstOrFail();
        $path = $lead->context['attachment_path'];
        Storage::disk('local')->assertExists($path);

        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->role_id = \App\Models\Role::where('key', 'admin')->value('id');
        $admin->save();

        $this->actingAs($admin)
            ->delete(route('admin.leads.destroy', $lead))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($path);
    }
}
