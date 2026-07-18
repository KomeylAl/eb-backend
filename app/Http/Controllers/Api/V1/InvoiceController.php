<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Invoice\GenerateInvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\GenerateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $invoices = Invoice::query()
            ->with(['client', 'admin'])
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(InvoiceResource::collection($invoices));
    }

    public function generate(
        GenerateInvoiceRequest $request,
        GenerateInvoiceAction $action,
    ): JsonResponse {
        $data = $request->validated();
        $data['admin_id'] = $data['admin_id'] ?? $request->user()->id;

        $invoice = $action->execute($data);

        return ApiResponse::created(
            InvoiceResource::make($invoice),
            'Invoice generated successfully.',
        );
    }
}
