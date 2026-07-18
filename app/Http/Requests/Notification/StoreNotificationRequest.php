<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:100'],
            'notifiable_type' => ['nullable', 'string', 'max:255'],
            'notifiable_id' => ['nullable', 'uuid'],
            'priority' => ['nullable', 'string', 'in:low,normal,medium,high'],
            'delivery_channels' => ['nullable', 'array'],
            'delivery_channels.*' => ['string'],
            'meta' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:50'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
