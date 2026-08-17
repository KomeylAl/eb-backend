<?php

namespace App\Http\Requests\Media;

use App\Services\FileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('context') && is_string($this->input('context'))) {
            $decoded = json_decode($this->input('context'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['context' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        $collection = $this->input('collection', 'library');
        $maxKb = (int) (config("media.collections.{$collection}.max_kb") ?? 10240);

        return [
            'file' => ['required', 'file', 'max:'.$maxKb],
            'collection' => ['required', 'string'],
            'folder_id' => ['nullable', 'uuid', 'exists:media_folders,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'انتخاب فایل الزامی است.',
            'file.file' => 'آپلود فایل ناموفق بود. دوباره تلاش کنید.',
            'file.max' => 'حجم فایل بیشتر از حد مجاز این مجموعه است.',
            'collection.required' => 'انتخاب مجموعه الزامی است.',
            'folder_id.uuid' => 'شناسه پوشه نامعتبر است.',
            'folder_id.exists' => 'پوشه انتخاب‌شده پیدا نشد.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $key = (string) $this->input('collection', '');

            try {
                $config = app(FileService::class)->collection($key);
            } catch (\Throwable) {
                $validator->errors()->add('collection', 'مجموعه رسانه ناشناخته است.');

                return;
            }

            if (empty($config['library'])) {
                $validator->errors()->add('collection', 'این مجموعه از کتابخانه رسانه قابل آپلود نیست.');
            }
        });
    }
}
