<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media collections
    |--------------------------------------------------------------------------
    |
    | Path templates are directories. Filename is generated separately.
    | Placeholders: {year} {month} {day} plus any context key (slug, client_id, …).
    |
    | library=true  → shown in the admin media picker; entity delete does not
    |                 remove the file from disk.
    | library=false → internal/private; not listed in the gallery.
    |
    */

    'collections' => [

        'library' => [
            'label' => 'کتابخانه عمومی',
            'disk' => 'public',
            'path' => 'library/{year}/{month}',
            'visibility' => 'public',
            'library' => true,
            'mimes' => [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'application/pdf',
            ],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'],
            'max_kb' => 10240,
        ],

        'posts' => [
            'label' => 'پست‌ها',
            'disk' => 'public',
            'path' => 'posts/{year}/{month}',
            'visibility' => 'public',
            'library' => true,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'max_kb' => 10240,
        ],

        'doctor_avatars' => [
            'label' => 'آواتار درمانگران',
            'disk' => 'public',
            'path' => 'doctor_avatars',
            'visibility' => 'public',
            'library' => true,
            'mimes' => [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'image/jpg', 'image/pjpeg', 'image/x-png', 'image/avif',
            ],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'jfif'],
            'max_kb' => 20480,
        ],

        'workshops' => [
            'label' => 'کارگاه‌ها',
            'disk' => 'public',
            'path' => 'workshops',
            'visibility' => 'public',
            'library' => true,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/pjpeg'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 10240,
        ],

        'categories' => [
            'label' => 'دسته‌بندی‌ها',
            'disk' => 'public',
            'path' => 'categories',
            'visibility' => 'public',
            'library' => true,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/pjpeg'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 10240,
        ],

        'tags' => [
            'label' => 'برچسب‌ها',
            'disk' => 'public',
            'path' => 'tags',
            'visibility' => 'public',
            'library' => true,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/pjpeg'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 10240,
        ],

        'departments' => [
            'label' => 'دپارتمان‌ها',
            'disk' => 'public',
            'path' => 'department_images',
            'visibility' => 'public',
            'library' => true,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/pjpeg'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 10240,
        ],

        'about' => [
            'label' => 'درباره',
            'disk' => 'public',
            'path' => 'about',
            'visibility' => 'public',
            'library' => true,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 10240,
        ],

        'doctor_resumes' => [
            'label' => 'رزومه درمانگران',
            'disk' => 'public',
            'path' => 'doctor_resumes',
            'visibility' => 'public',
            'library' => false,
            'mimes' => ['application/pdf'],
            'extensions' => ['pdf'],
            'max_kb' => 4096,
        ],

        'doctor_resources' => [
            'label' => 'منابع درمانگران',
            'disk' => 'public',
            'path' => 'doctor_resources',
            'visibility' => 'public',
            'library' => false,
            'mimes' => [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/webp',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'],
            'max_kb' => 10240,
        ],

        'medical_records' => [
            'label' => 'پرونده پزشکی',
            'disk' => 'local',
            'path' => 'medical_records/{client_id}',
            'visibility' => 'private',
            'library' => false,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 5120,
        ],

        // Future: therapist / staff identity documents (no UI in this phase).
        'therapist_documents' => [
            'label' => 'مدارک درمانگران',
            'disk' => 'local',
            'path' => 'documents/therapists/{therapist_slug}',
            'visibility' => 'private',
            'library' => false,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
            'max_kb' => 8192,
        ],

        // Future: Ebraz Plus workshop files (no UI in this phase).
        // library=false until workshop context is supplied on upload.
        'workshop_materials' => [
            'label' => 'فایل‌های کارگاه',
            'disk' => 'public',
            'path' => 'workshops/{workshop_slug}/materials',
            'visibility' => 'public',
            'library' => false,
            'mimes' => [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/webp',
                'application/zip',
                'application/x-zip-compressed',
            ],
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
            'max_kb' => 20480,
        ],

        'workshop_certificates' => [
            'label' => 'گواهی کارگاه',
            'disk' => 'local',
            'path' => 'workshops/{workshop_slug}/certificates/{participant_id}',
            'visibility' => 'private',
            'library' => false,
            'mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'max_kb' => 8192,
        ],

    ],

    /*
    | First path segment → collection key, used by `media:index`.
    */
    'prefix_map' => [
        'library' => 'library',
        'posts' => 'posts',
        'doctor_avatars' => 'doctor_avatars',
        'workshops' => 'workshops',
        'tags' => 'tags',
        'categories' => 'categories',
        'department_images' => 'departments',
        'about' => 'about',
        'doctor_resumes' => 'doctor_resumes',
        'doctor_resources' => 'doctor_resources',
        'medical_records' => 'medical_records',
        'documents' => 'therapist_documents',
    ],

];
