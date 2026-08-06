<?php

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpsertInvoiceAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $this->normalizeItems($data['items'] ?? []);
            $subtotal = collect($items)->sum('line_total');

            $invoice = Invoice::query()->create([
                'client_id' => $data['client_id'],
                'admin_id' => $data['admin_id'],
                'number' => $data['number'] ?? $this->generateNumber(),
                'status' => InvoiceStatus::from($data['status'] ?? InvoiceStatus::Draft->value),
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'from_date' => $data['from_date'] ?? null,
                'to_date' => $data['to_date'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            $this->syncItems($invoice, $items);

            return $invoice->fresh(['client', 'admin', 'items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $items = array_key_exists('items', $data)
                ? $this->normalizeItems($data['items'] ?? [])
                : null;

            $subtotal = $items !== null
                ? (int) collect($items)->sum('line_total')
                : (int) $invoice->subtotal;

            $invoice->update([
                'client_id' => $data['client_id'] ?? $invoice->client_id,
                'status' => isset($data['status'])
                    ? InvoiceStatus::from($data['status'])
                    : $invoice->status,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $invoice->due_date,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $invoice->notes,
                'from_date' => array_key_exists('from_date', $data) ? $data['from_date'] : $invoice->from_date,
                'to_date' => array_key_exists('to_date', $data) ? $data['to_date'] : $invoice->to_date,
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            if ($items !== null) {
                $invoice->items()->delete();
                $this->syncItems($invoice, $items);
            }

            return $invoice->fresh(['client', 'admin', 'items']);
        });
    }

    /**
     * @param  list<array{description: string, unit?: ?string, quantity: int, unit_price: int, appointment_id?: ?string, sort_order?: int}>  $items
     * @return list<array{description: string, unit: ?string, quantity: int, unit_price: int, line_total: int, appointment_id: ?string, sort_order: int}>
     */
    private function normalizeItems(array $items): array
    {
        return collect($items)->values()->map(function (array $item, int $index) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = max(0, (int) ($item['unit_price'] ?? 0));

            return [
                'description' => $item['description'],
                'unit' => $item['unit'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
                'appointment_id' => $item['appointment_id'] ?? null,
                'sort_order' => (int) ($item['sort_order'] ?? $index),
            ];
        })->all();
    }

    /**
     * @param  list<array{description: string, unit: ?string, quantity: int, unit_price: int, line_total: int, appointment_id: ?string, sort_order: int}>  $items
     */
    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                ...$item,
            ]);
        }
    }

    private function generateNumber(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
