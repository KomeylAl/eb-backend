<?php

use App\Http\Controllers\Api\V1\AboutController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BackupController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ClassController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\DoctorResourceController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\FinancialAdjustmentController;
use App\Http\Controllers\Api\V1\HomeworkController;
use App\Http\Controllers\Api\V1\InitAssessmentController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\MediaFolderController;
use App\Http\Controllers\Api\V1\MedicalRecordController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentTransactionController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\RestoreController;
use App\Http\Controllers\Api\V1\ResumeController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\SmsController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TreatmentProgramController;
use App\Http\Controllers\Api\V1\WorkshopController;
use App\Http\Controllers\Api\V1\WorkshopParticipantController;
use App\Http\Controllers\Api\V1\WorkshopSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/otp/request', [AuthController::class, 'requestLoginOtp']);
    Route::post('auth/otp/verify', [AuthController::class, 'verifyLoginOtp']);

    Route::get('about', [AboutController::class, 'index']);
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);
    Route::get('doctors', [DoctorController::class, 'index']);
    Route::get('doctors/{doctor}', [DoctorController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{tag}', [TagController::class, 'show']);
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::get('workshops', [WorkshopController::class, 'index']);
    Route::get('workshops/{workshop}', [WorkshopController::class, 'show']);
    Route::post('assessments', [InitAssessmentController::class, 'store']);
    Route::get('comments', [CommentController::class, 'index']);
    Route::post('comments', [CommentController::class, 'store']);
    Route::get('media/{media}/file', [MediaController::class, 'file'])
        ->middleware('signed')
        ->name('media.file');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/password/otp', [AuthController::class, 'requestPasswordChangeOtp']);
        Route::post('auth/password', [AuthController::class, 'changePassword']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread', [NotificationController::class, 'unread']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::get('comments/mine', [CommentController::class, 'mine']);

        // Doctor panel
        Route::middleware('user.type:doctor')->prefix('doctor')->group(function () {
            Route::get('appointments', [AppointmentController::class, 'index']);
            Route::patch('appointments/{appointment}/session-notes', [AppointmentController::class, 'updateSessionNotes']);
            Route::get('resume', [ResumeController::class, 'showSelf']);
            Route::post('resume', [ResumeController::class, 'storeSelf']);
            Route::get('resources', [DoctorResourceController::class, 'indexSelf']);
            Route::post('resources', [DoctorResourceController::class, 'storeSelf']);
            Route::put('resources/{doctorResource}', [DoctorResourceController::class, 'updateSelf']);
            Route::patch('resources/{doctorResource}', [DoctorResourceController::class, 'updateSelf']);
            Route::delete('resources/{doctorResource}', [DoctorResourceController::class, 'destroySelf']);
            Route::get('assessments', [InitAssessmentController::class, 'index']);
            Route::get('notifications', [NotificationController::class, 'index']);
            Route::get('comments', [CommentController::class, 'indexForDoctor']);

            Route::get('treatment-programs', [TreatmentProgramController::class, 'index']);
            Route::get('treatment-programs/{treatment_program}', [TreatmentProgramController::class, 'show']);
            Route::get('treatment-programs/{treatment_program}/medical-record', [MedicalRecordController::class, 'showForProgram']);
            Route::post('treatment-programs/{treatment_program}/medical-record', [MedicalRecordController::class, 'updateClinicalForProgram']);
            Route::put('treatment-programs/{treatment_program}/medical-record', [MedicalRecordController::class, 'updateClinicalForProgram']);

            Route::get('appointments/{appointment}/homeworks', [HomeworkController::class, 'index']);
            Route::post('appointments/{appointment}/homeworks', [HomeworkController::class, 'store']);
            Route::patch('homeworks/{homework}', [HomeworkController::class, 'update']);
            Route::delete('homeworks/{homework}', [HomeworkController::class, 'destroy']);
        });

        // Admin
        Route::middleware('user.type:admin')->group(function () {
            Route::apiResource('admins', AdminController::class);
            Route::apiResource('clients', ClientController::class);

            Route::post('doctors', [DoctorController::class, 'store']);
            Route::put('doctors/reorder', [DoctorController::class, 'reorder']);
            Route::put('doctors/{doctor}', [DoctorController::class, 'update']);
            Route::patch('doctors/{doctor}', [DoctorController::class, 'update']);
            Route::delete('doctors/{doctor}', [DoctorController::class, 'destroy']);
            Route::post('doctors/{doctor}/password', [DoctorController::class, 'setPassword']);
            Route::get('doctors/{doctor}/appointments/today', [DoctorController::class, 'today']);
            Route::get('doctors/{doctor}/appointments/yesterday', [DoctorController::class, 'yesterday']);
            Route::get('doctors/{doctor}/appointments/tomorrow', [DoctorController::class, 'tomorrow']);
            Route::get('doctors/{doctor}/appointments/last-7-days', [DoctorController::class, 'last7']);
            Route::get('doctors/{doctor}/appointments/last-30-days', [DoctorController::class, 'last30']);
            Route::get('doctors/{doctor}/appointments/next-30-days', [DoctorController::class, 'next30']);
            Route::get('doctors/{doctor}/appointments/all', [DoctorController::class, 'all']);

            Route::get('doctors/{doctor}/resume', [ResumeController::class, 'show']);
            Route::post('doctors/{doctor}/resume', [ResumeController::class, 'store']);
            Route::apiResource('doctors.resources', DoctorResourceController::class)
                ->except(['show'])
                ->parameters(['resources' => 'doctorResource']);

            Route::apiResource('appointments', AppointmentController::class);
            Route::apiResource('departments', DepartmentController::class)->except(['index', 'show']);

            Route::get('treatment-programs', [TreatmentProgramController::class, 'index']);
            Route::post('treatment-programs', [TreatmentProgramController::class, 'store']);
            Route::get('treatment-programs/{treatment_program}', [TreatmentProgramController::class, 'show']);
            Route::patch('treatment-programs/{treatment_program}', [TreatmentProgramController::class, 'update']);
            Route::put('treatment-programs/{treatment_program}', [TreatmentProgramController::class, 'update']);
            Route::delete('treatment-programs/{treatment_program}', [TreatmentProgramController::class, 'destroy']);

            Route::get('treatment-programs/{treatment_program}/medical-record', [MedicalRecordController::class, 'showForProgram']);
            Route::post('treatment-programs/{treatment_program}/medical-record', [MedicalRecordController::class, 'upsertForProgram']);
            Route::put('treatment-programs/{treatment_program}/medical-record', [MedicalRecordController::class, 'upsertForProgram']);

            Route::get('appointments/{appointment}/homeworks', [HomeworkController::class, 'index']);
            Route::post('appointments/{appointment}/homeworks', [HomeworkController::class, 'store']);
            Route::patch('homeworks/{homework}', [HomeworkController::class, 'update']);
            Route::delete('homeworks/{homework}', [HomeworkController::class, 'destroy']);

            Route::get('rooms/availability', [RoomController::class, 'availability']);
            Route::apiResource('rooms', RoomController::class);

            Route::get('assessments', [InitAssessmentController::class, 'index']);
            Route::delete('assessments/{initAssessment}', [InitAssessmentController::class, 'destroy']);

            Route::apiResource('workshops', WorkshopController::class)->except(['index', 'show']);
            Route::apiResource('workshops.sessions', WorkshopSessionController::class);
            Route::get('workshops/{workshop}/participants', [WorkshopParticipantController::class, 'index']);
            Route::post('workshops/{workshop}/participants', [WorkshopParticipantController::class, 'store']);
            Route::delete('workshops/{workshop}/participants/{participant}', [WorkshopParticipantController::class, 'destroy']);
            Route::patch('workshops/{workshop}/participants/{participant}/approve', [WorkshopParticipantController::class, 'approve']);
            Route::patch('workshops/{workshop}/participants/{participant}/unapprove', [WorkshopParticipantController::class, 'unapprove']);

            Route::apiResource('classes', ClassController::class);

            Route::get('payments', [PaymentController::class, 'index']);
            Route::get('payments/{payment}', [PaymentController::class, 'show']);
            Route::get('payment-transactions', [PaymentTransactionController::class, 'index']);

            Route::post('invoices/suggest-items', [InvoiceController::class, 'suggestItems']);
            Route::apiResource('invoices', InvoiceController::class);
            Route::apiResource('financial-adjustments', FinancialAdjustmentController::class);

            Route::get('finance/summary', [FinanceController::class, 'summary']);
            Route::get('finance/reports/by-doctor', [FinanceController::class, 'byDoctor']);
            Route::get('finance/reports/by-day', [FinanceController::class, 'byDay']);
            Route::get('finance/reports/compare', [FinanceController::class, 'compare']);

            Route::post('about', [AboutController::class, 'upsert']);

            Route::post('notifications', [NotificationController::class, 'store']);

            Route::post('sms/single', [SmsController::class, 'single']);
            Route::post('sms/multi', [SmsController::class, 'multi']);

            Route::prefix('backup')->group(function () {
                Route::get('admins', [BackupController::class, 'admins']);
                Route::get('doctors', [BackupController::class, 'doctors']);
                Route::get('clients', [BackupController::class, 'clients']);
                Route::get('resumes', [BackupController::class, 'resumes']);
                Route::get('posts', [BackupController::class, 'posts']);
                Route::get('categories', [BackupController::class, 'categories']);
                Route::get('tags', [BackupController::class, 'tags']);
                Route::get('workshops', [BackupController::class, 'workshops']);
                Route::get('about', [BackupController::class, 'about']);
            });

            Route::prefix('restore')->group(function () {
                Route::post('admins', [RestoreController::class, 'admins']);
                Route::post('doctors', [RestoreController::class, 'doctors']);
                Route::post('clients', [RestoreController::class, 'clients']);
                Route::post('resumes', [RestoreController::class, 'resumes']);
                Route::post('posts', [RestoreController::class, 'posts']);
                Route::post('categories', [RestoreController::class, 'categories']);
                Route::post('tags', [RestoreController::class, 'tags']);
                Route::post('workshops', [RestoreController::class, 'workshops']);
                Route::post('about', [RestoreController::class, 'about']);
            });

            Route::get('comments/{comment}', [CommentController::class, 'show']);
            Route::patch('comments/{comment}', [CommentController::class, 'update']);
            Route::patch('comments/{comment}/approve', [CommentController::class, 'approve']);
            Route::patch('comments/{comment}/unapprove', [CommentController::class, 'unapprove']);
            Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
        });

        // Content authors
        Route::middleware(['user.type:admin', 'admin.role:author,boss,manager'])->group(function () {
            Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
            Route::apiResource('tags', TagController::class)->except(['index', 'show']);
            Route::apiResource('posts', PostController::class)->except(['index', 'show']);

            Route::get('media/collections', [MediaController::class, 'collections']);
            Route::get('media/folders', [MediaFolderController::class, 'index']);
            Route::post('media/folders', [MediaFolderController::class, 'store']);
            Route::patch('media/folders/{media_folder}', [MediaFolderController::class, 'update']);
            Route::delete('media/folders/{media_folder}', [MediaFolderController::class, 'destroy']);
            Route::get('media', [MediaController::class, 'index']);
            Route::post('media', [MediaController::class, 'store']);
            Route::patch('media/{media}', [MediaController::class, 'update']);
            Route::delete('media/{media}', [MediaController::class, 'destroy']);
        });
    });
});
