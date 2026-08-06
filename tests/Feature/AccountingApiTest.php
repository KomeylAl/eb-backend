<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\AppointmentStatus;
use App\Enums\FinancialAdjustmentType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{admin: User, doctor: User, client: User}
     */
    private function actingAdminWithDoctorAndClient(): array
    {
        $admin = User::factory()->admin(AdminRole::Accountant)->create();
        Sanctum::actingAs($admin);

        $doctor = User::factory()->doctor()->create(['password' => 'password']);
        DoctorProfile::query()->create([
            'user_id' => $doctor->id,
            'national_code' => '1234567890',
        ]);

        $client = User::factory()->client()->create();

        return compact('admin', 'doctor', 'client');
    }

    public function test_admin_can_create_partial_payment_appointment_and_log_transaction(): void
    {
        ['doctor' => $doctor, 'client' => $client] = $this->actingAdminWithDoctorAndClient();

        $response = $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'time' => '10:00',
            'amount' => 500000,
            'service' => 'CBT',
            'status' => AppointmentStatus::Pending->value,
            'payment_status' => PaymentStatus::Partial->value,
            'paid_amount' => 200000,
            'payment_method' => PaymentMethod::Cash->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.service', 'CBT')
            ->assertJsonPath('data.payment.status', PaymentStatus::Partial->value)
            ->assertJsonPath('data.payment.amount', 500000)
            ->assertJsonPath('data.payment.paid_amount', 200000)
            ->assertJsonPath('data.payment.method', PaymentMethod::Cash->value);

        $this->assertDatabaseHas('payment_transactions', [
            'event' => 'created',
            'new_status' => PaymentStatus::Partial->value,
            'new_paid_amount' => 200000,
        ]);
    }

    public function test_payments_support_date_and_doctor_filters(): void
    {
        ['doctor' => $doctor, 'client' => $client] = $this->actingAdminWithDoctorAndClient();

        $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'time' => '11:00',
            'amount' => 100000,
            'status' => AppointmentStatus::Done->value,
            'payment_status' => PaymentStatus::Paid->value,
            'payment_method' => PaymentMethod::Card->value,
        ])->assertCreated();

        $this->getJson('/api/v1/payments?doctor_id='.$doctor->id.'&method=card&from_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_finance_summary_and_reports(): void
    {
        ['doctor' => $doctor, 'client' => $client] = $this->actingAdminWithDoctorAndClient();

        $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => '2026-07-01',
            'time' => '09:00',
            'amount' => 300000,
            'status' => AppointmentStatus::Done->value,
            'payment_status' => PaymentStatus::Paid->value,
        ])->assertCreated();

        $this->getJson('/api/v1/finance/summary?from=2026-07-01&to=2026-07-31')
            ->assertOk()
            ->assertJsonPath('data.totals.billed', 300000)
            ->assertJsonPath('data.totals.paid', 300000);

        $this->getJson('/api/v1/finance/reports/by-doctor?from=2026-07-01&to=2026-07-31')
            ->assertOk()
            ->assertJsonPath('data.0.doctor_id', $doctor->id);

        $this->getJson('/api/v1/finance/reports/by-day?from=2026-07-01&to=2026-07-31')
            ->assertOk()
            ->assertJsonPath('data.0.date', '2026-07-01');

        $this->getJson('/api/v1/finance/reports/compare?from=2026-07-01&to=2026-07-31&compare_from=2026-06-01&compare_to=2026-06-30')
            ->assertOk()
            ->assertJsonStructure(['data' => ['current', 'previous', 'growth']]);
    }

    public function test_invoice_crud_with_line_items(): void
    {
        ['client' => $client] = $this->actingAdminWithDoctorAndClient();

        $create = $this->postJson('/api/v1/invoices', [
            'client_id' => $client->id,
            'status' => InvoiceStatus::Issued->value,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => 'تست',
            'items' => [
                [
                    'description' => 'جلسه مشاوره',
                    'unit' => 'جلسه',
                    'quantity' => 2,
                    'unit_price' => 150000,
                ],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.subtotal', 300000)
            ->assertJsonPath('data.total', 300000)
            ->assertJsonPath('data.items.0.line_total', 300000)
            ->assertJsonPath('data.items.0.unit', 'جلسه');

        $id = $create->json('data.id');

        $this->getJson('/api/v1/invoices/'.$id)
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        $this->putJson('/api/v1/invoices/'.$id, [
            'status' => InvoiceStatus::Paid->value,
            'issue_date' => now()->toDateString(),
            'items' => [
                [
                    'description' => 'جلسه مشاوره',
                    'unit' => 'جلسه',
                    'quantity' => 1,
                    'unit_price' => 200000,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.total', 200000)
            ->assertJsonPath('data.status', InvoiceStatus::Paid->value);

        $this->deleteJson('/api/v1/invoices/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('invoices', ['id' => $id]);
    }

    public function test_suggest_invoice_items_from_appointments(): void
    {
        ['doctor' => $doctor, 'client' => $client] = $this->actingAdminWithDoctorAndClient();

        $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => '2026-07-10',
            'time' => '10:00',
            'amount' => 400000,
            'service' => 'مشاوره فردی',
            'status' => AppointmentStatus::Done->value,
            'payment_status' => PaymentStatus::Paid->value,
        ])->assertCreated();

        $suggest = $this->postJson('/api/v1/invoices/suggest-items', [
            'client_id' => $client->id,
            'from_date' => '2026-07-01',
            'to_date' => '2026-07-31',
        ]);

        $suggest->assertOk()
            ->assertJsonPath('data.subtotal', 400000)
            ->assertJsonPath('data.items.0.unit', 'جلسه')
            ->assertJsonPath('data.items.0.unit_price', 400000);

        $items = $suggest->json('data.items');

        $this->postJson('/api/v1/invoices', [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'from_date' => '2026-07-01',
            'to_date' => '2026-07-31',
            'items' => array_map(fn (array $item) => [
                'description' => $item['description'],
                'unit' => $item['unit'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'appointment_id' => $item['appointment_id'],
            ], $items),
        ])->assertCreated()
            ->assertJsonPath('data.total', 400000)
            ->assertJsonPath('data.items.0.appointment_id', $items[0]['appointment_id']);
    }

    public function test_financial_adjustment_crud(): void
    {
        ['client' => $client] = $this->actingAdminWithDoctorAndClient();

        $create = $this->postJson('/api/v1/financial-adjustments', [
            'client_id' => $client->id,
            'type' => FinancialAdjustmentType::Discount->value,
            'amount' => 50000,
            'reason' => 'تخفیف ویژه',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.type', 'discount')
            ->assertJsonPath('data.amount', 50000);

        $id = $create->json('data.id');

        $this->patchJson('/api/v1/financial-adjustments/'.$id, [
            'status' => 'void',
        ])->assertOk()
            ->assertJsonPath('data.status', 'void');

        $this->deleteJson('/api/v1/financial-adjustments/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('financial_adjustments', ['id' => $id]);
    }

    public function test_payment_transactions_endpoint(): void
    {
        ['doctor' => $doctor, 'client' => $client] = $this->actingAdminWithDoctorAndClient();

        $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'time' => '12:00',
            'amount' => 100000,
            'status' => AppointmentStatus::Pending->value,
            'payment_status' => PaymentStatus::Unpaid->value,
        ])->assertCreated();

        $this->assertGreaterThan(0, PaymentTransaction::query()->count());

        $this->getJson('/api/v1/payment-transactions')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_appointments_support_date_range_filter(): void
    {
        ['doctor' => $doctor, 'client' => $client] = $this->actingAdminWithDoctorAndClient();

        $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'client_id' => $client->id,
            'date' => '2026-07-15',
            'time' => '13:00',
            'amount' => 100000,
            'status' => AppointmentStatus::Pending->value,
            'payment_status' => PaymentStatus::Pending->value,
        ])->assertCreated();

        $this->getJson('/api/v1/appointments?from_date=2026-07-01&to_date=2026-07-31')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->getJson('/api/v1/appointments?from_date=2026-08-01&to_date=2026-08-31')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }
}
