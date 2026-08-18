<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workshop\IssueWorkshopCertificatesAction;
use App\Actions\Workshop\UploadWorkshopCertificateAction;
use App\Enums\CertificateTemplateKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\IssueWorkshopCertificatesRequest;
use App\Http\Requests\Workshop\UploadWorkshopCertificateRequest;
use App\Http\Requests\Workshop\UpsertWorkshopCertificateTemplateRequest;
use App\Http\Resources\WorkshopCertificateResource;
use App\Http\Resources\WorkshopCertificateTemplateResource;
use App\Http\Responses\ApiResponse;
use App\Models\Workshop;
use App\Models\WorkshopCertificate;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkshopCertificateController extends Controller
{
    public function __construct(private FileService $files) {}

    public function templatePresets(): JsonResponse
    {
        return ApiResponse::success([
            'templates' => CertificateTemplateKey::options(),
            'default_body' => 'گواهی می‌شود که {{participant_name}} با کد ملی {{national_code}} در «{{workshop_title}}» با موفقیت شرکت نموده است.',
            'placeholders' => [
                'participant_name',
                'english_name',
                'national_code',
                'phone',
                'workshop_title',
                'workshop_type',
                'start_date',
                'end_date',
                'issue_date',
                'certificate_number',
                'clinic_name',
            ],
        ]);
    }

    public function showTemplate(Workshop $workshop): JsonResponse
    {
        $template = $workshop->certificateTemplate;

        if (! $template) {
            return ApiResponse::success(null, 'Certificate template not configured yet.');
        }

        return ApiResponse::success(WorkshopCertificateTemplateResource::make($template));
    }

    public function upsertTemplate(
        UpsertWorkshopCertificateTemplateRequest $request,
        Workshop $workshop,
    ): JsonResponse {
        $template = $workshop->certificateTemplate ?? $workshop->certificateTemplate()->make([
            'workshop_id' => $workshop->id,
        ]);

        $data = $request->safe()->only([
            'template_key',
            'clinic_name',
            'title',
            'body_text',
            'footer_text',
            'signer_name',
            'signer_title',
        ]);

        if ($request->boolean('remove_logo') && $template->logo_path) {
            $template->deleteAsset($template->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->boolean('remove_signature') && $template->signature_path) {
            $template->deleteAsset($template->signature_path);
            $data['signature_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($template->logo_path) {
                $template->deleteAsset($template->logo_path);
            }
            $media = $this->files->store(
                $request->file('logo'),
                'workshop_certificate_assets',
                context: ['workshop_slug' => $workshop->slug],
                uploadedBy: $request->user()?->id,
            );
            $data['logo_path'] = $media->path;
        }

        if ($request->hasFile('signature')) {
            if ($template->signature_path) {
                $template->deleteAsset($template->signature_path);
            }
            $media = $this->files->store(
                $request->file('signature'),
                'workshop_certificate_assets',
                context: ['workshop_slug' => $workshop->slug],
                uploadedBy: $request->user()?->id,
            );
            $data['signature_path'] = $media->path;
        }

        $template->fill($data);
        $template->workshop_id = $workshop->id;
        $template->save();

        return ApiResponse::success(
            WorkshopCertificateTemplateResource::make($template->fresh()),
            'Certificate template saved successfully.',
        );
    }

    public function index(Workshop $workshop): JsonResponse
    {
        $certificates = $workshop->certificates()
            ->with('participant')
            ->orderByDesc('issued_at')
            ->get();

        return ApiResponse::success(WorkshopCertificateResource::collection($certificates));
    }

    public function issue(
        IssueWorkshopCertificatesRequest $request,
        Workshop $workshop,
        IssueWorkshopCertificatesAction $action,
    ): JsonResponse {
        $issued = $action->execute($workshop, $request->validated('participant_ids'));

        return ApiResponse::success(
            WorkshopCertificateResource::collection(collect($issued)),
            'Certificates issued successfully.',
        );
    }

    public function upload(
        UploadWorkshopCertificateRequest $request,
        Workshop $workshop,
        UploadWorkshopCertificateAction $action,
    ): JsonResponse {
        $certificate = $action->execute(
            $workshop,
            $request->validated('participant_id'),
            $request->file('file'),
            $request->validated('certificate_number'),
            $request->user()?->id,
        );

        return ApiResponse::created(
            WorkshopCertificateResource::make($certificate),
            'Certificate file uploaded successfully.',
        );
    }

    public function download(
        Workshop $workshop,
        WorkshopCertificate $certificate,
    ): StreamedResponse|JsonResponse {
        abort_unless($certificate->workshop_id === $workshop->id, 404);

        if (! $certificate->hasFile()) {
            return ApiResponse::error('This certificate has no uploaded file.', 404);
        }

        $disk = $certificate->disk();
        if (! Storage::disk($disk)->exists($certificate->file_path)) {
            return ApiResponse::error('File not found.', 404);
        }

        return Storage::disk($disk)->download(
            $certificate->file_path,
            $certificate->original_name ?: basename($certificate->file_path),
        );
    }

    public function destroy(Workshop $workshop, WorkshopCertificate $certificate): JsonResponse
    {
        abort_unless($certificate->workshop_id === $workshop->id, 404);

        $certificate->deleteFile();
        $certificate->delete();

        return ApiResponse::noContent();
    }
}
