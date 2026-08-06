<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Invoice\SuggestInvoiceItemsAction;
use App\Actions\Invoice\UpsertInvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\SuggestInvoiceItemsRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with(['client', 'admin', 'items']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->query('client_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('issue_date', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->query('to_date'));
        }

        $invoices = $query
            ->orderByDesc('issue_date')
            ->orderByDesc('created_at')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 15))));

        return ApiResponse::success([
            'items' => InvoiceResource::collection($invoices->items()),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function store(StoreInvoiceRequest $request, UpsertInvoiceAction $action): JsonResponse
    {
        $data = $request->validated();
        $data['admin_id'] = $request->user()->id;

        $invoice = $action->create($data);

        return ApiResponse::created(
            InvoiceResource::make($invoice),
            'Invoice created successfully.',
        );
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['client', 'admin', 'items']);

        return ApiResponse::success(InvoiceResource::make($invoice));
    }

    public function update(
        UpdateInvoiceRequest $request,
        Invoice $invoice,
        UpsertInvoiceAction $action,
    ): JsonResponse {
        $invoice = $action->update($invoice, $request->validated());

        return ApiResponse::success(
            InvoiceResource::make($invoice),
            'Invoice updated successfully.',
        );
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return ApiResponse::noContent();
    }

    public function suggestItems(
        SuggestInvoiceItemsRequest $request,
        SuggestInvoiceItemsAction $action,
    ): JsonResponse {
        $data = $request->validated();

        $items = $action->execute(
            $data['client_id'],
            $data['from_date'],
            $data['to_date'],
        );

        return ApiResponse::success([
            'client_id' => $data['client_id'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'items' => $items,
            'subtotal' => collect($items)->sum('line_total'),
        ]);
    }
}
