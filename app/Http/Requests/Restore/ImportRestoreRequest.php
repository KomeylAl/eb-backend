<?php

namespace App\Http\Requests\Restore;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JsonException;

class ImportRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->input('data');

        if ($this->hasFile('file')) {
            $contents = file_get_contents($this->file('file')->getRealPath());
            $decoded = $this->decodeJsonPayload($contents);
            $data = $decoded;
        } elseif (is_string($data)) {
            $data = $this->decodeJsonPayload($data);
        }

        // Allow raw JSON array body: [{...}, {...}]
        if ($data === null && is_array($this->json()?->all()) && array_is_list($this->json()->all())) {
            $data = $this->json()->all();
        }

        if (is_array($data)) {
            $this->merge(['data' => $data]);
        }
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array', 'min:1'],
            'data.*' => ['required', 'array'],
            'file' => ['nullable', 'file', 'max:10240'],
            'type' => [
                'nullable',
                'string',
                Rule::in([
                    'admins',
                    'doctors',
                    'clients',
                    'resumes',
                    'posts',
                    'categories',
                    'tags',
                    'workshops',
                    'about',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => 'Restore payload is required. Send JSON body { "data": [...] } or upload a JSON file.',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeJsonPayload(?string $contents): array
    {
        if ($contents === null || trim($contents) === '') {
            return [];
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        // Some backups wrap payload as { "data": [...] }
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            return $decoded['data'];
        }

        return $decoded;
    }
}
