<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\WorkshopType;
use App\Models\Participant;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkshopParticipantAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkshop(): Workshop
    {
        return Workshop::query()->create([
            'title' => 'Workshop',
            'slug' => 'workshop',
            'type' => WorkshopType::General,
            'excerpt' => 'e',
            'content' => 'c',
        ]);
    }

    public function test_admin_can_list_add_update_and_approve_participants(): void
    {
        $admin = User::factory()->admin(AdminRole::Author)->create();
        Sanctum::actingAs($admin);
        $workshop = $this->makeWorkshop();

        $createdId = $this->postJson('/api/v1/workshops/'.$workshop->id.'/participants', [
            'name' => 'علی',
            'name_en' => 'Ali',
            'phone' => '09120001122',
            'national_code' => '0011223344',
            'gender' => 'male',
            'approved' => false,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'علی')
            ->assertJsonPath('data.english_name', 'Ali')
            ->assertJsonPath('data.approved', false)
            ->json('data.id');

        $this->getJson('/api/v1/workshops/'.$workshop->id.'/participants')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->patchJson('/api/v1/workshops/'.$workshop->id.'/participants/'.$createdId, [
            'name' => 'علی رضایی',
            'english_name' => 'Ali Rezaei',
            'phone' => '09120001122',
            'national_code' => '0011223344',
            'gender' => 'male',
            'approved' => true,
        ])->assertOk()
            ->assertJsonPath('data.name', 'علی رضایی')
            ->assertJsonPath('data.english_name', 'Ali Rezaei')
            ->assertJsonPath('data.approved', true);

        $this->patchJson('/api/v1/workshops/'.$workshop->id.'/participants/'.$createdId.'/unapprove')
            ->assertOk()
            ->assertJsonPath('data.approved', false);

        $this->deleteJson('/api/v1/workshops/'.$workshop->id.'/participants/'.$createdId)
            ->assertNoContent();

        $this->assertDatabaseMissing('participant_workshop', [
            'workshop_id' => $workshop->id,
            'participant_id' => $createdId,
        ]);
    }

    public function test_guest_cannot_list_participants(): void
    {
        $workshop = $this->makeWorkshop();
        $this->getJson('/api/v1/workshops/'.$workshop->id.'/participants')
            ->assertUnauthorized();
    }
}
