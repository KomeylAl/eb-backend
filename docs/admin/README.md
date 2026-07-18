# پنل ادمین

> مسیر پایه: `/api/v1`  
> [بازگشت به فهرست اصلی](../README.md)

تمام endpointهای این سند نیازمند:

```http
Authorization: Bearer {token}
Accept: application/json
```

و `user.type === "admin"`.

**ورود:**

```json
POST /auth/login
{ "phone": "...", "password": "...", "type": "admin" }
```

---

## فهرست

- [مدیران](#مدیران)
- [مراجعان](#مراجعان)
- [پزشکان](#پزشکان)
- [نوبت‌ها](#نوبت‌ها)
- [دپارتمان‌ها](#دپارتمان‌ها)
- [رزومه پزشک](#رزومه-پزشک)
- [منابع پزشک](#منابع-پزشک)
- [پرونده پزشکی](#پرونده-پزشکی)
- [ارزیابی‌های اولیه](#ارزیابی‌های-اولیه)
- [پرداخت‌ها](#پرداخت‌ها)
- [فاکتورها](#فاکتورها)
- [درباره ما](#درباره-ما)
- [اعلان‌ها](#اعلان‌ها)
- [پیامک](#پیامک)
- [کارگاه‌ها](#کارگاه‌ها)
- [جلسات کارگاه](#جلسات-کارگاه)
- [شرکت‌کنندگان کارگاه](#شرکت‌کنندگان-کارگاه)
- [کلاس‌ها](#کلاس‌ها)
- [دسته‌بندی‌ها (محتوا)](#دسته‌بندی‌ها-محتوا)
- [تگ‌ها (محتوا)](#تگ‌ها-محتوا)
- [پست‌ها (محتوا)](#پست‌ها-محتوا)
- [مدیریت کامنت‌ها](#مدیریت-کامنت‌ها)
- [پشتیبان‌گیری](#پشتیبان‌گیری)
- [بازیابی](#بازیابی)

---

## محدودیت زیرنقش محتوا

ایجاد/ویرایش/حذف **دسته‌بندی، تگ و پست** فقط برای:

- `author`
- `boss`
- `manager`

سایر endpointهای ادمین برای همه زیرنقش‌ها (`boss`, `manager`, `author`, `receptionist`, `accountant`) باز است.

---

## مدیران

### فهرست

```http
GET /admins
```

| Query | پیش‌فرض | توضیح |
|-------|---------|--------|
| `search` | — | نام، تلفن |
| `sort_by` | `created_at` | — |
| `sort_direction` | `desc` | — |
| `per_page` | `10` | ۱ تا ۱۰۰ |
| `page` | `1` | — |

**پاسخ `200`:** pagination — `AdminResource`:

```json
{
  "id": "uuid",
  "name": "نام",
  "phone": "0912...",
  "birth_date": null,
  "type": "admin",
  "admin_role": "boss",
  "created_at": "...",
  "updated_at": "..."
}
```

### ایجاد

```http
POST /admins
Content-Type: application/json
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `name` | بله | حداکثر ۲۵۵ |
| `phone` | بله | حداکثر ۲۰، یکتا |
| `password` | بله | حداقل ۸ |
| `birth_date` | خیر | date |
| `admin_role` | بله | `boss`, `receptionist`, `manager`, `author`, `accountant` |

**پاسخ `201`:** `AdminResource`

### جزئیات / ویرایش / حذف

```http
GET    /admins/{admin}
PUT    /admins/{admin}
PATCH  /admins/{admin}
DELETE /admins/{admin}
```

**ویرایش:** همان فیلدها؛ `password` اختیاری. حتی در `PATCH`، `name`، `phone` و `admin_role` الزامی‌اند.

**حذف:** `204` بدون body.

---

## مراجعان

### فهرست

```http
GET /clients
```

Query مشابه مدیران (`search`, `sort_by`, `sort_direction`, `per_page`, `page`).

**پاسخ `200`:** pagination — `ClientResource`:

```json
{
  "id": "uuid",
  "name": "نام",
  "phone": "0912...",
  "birth_date": null,
  "address": null,
  "type": "client",
  "created_at": "...",
  "updated_at": "..."
}
```

### ایجاد

```http
POST /clients
Content-Type: application/json
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `name` | بله | حداکثر ۲۵۵ |
| `phone` | بله | حداکثر ۲۰، یکتا |
| `birth_date` | خیر | date |
| `address` | خیر | حداکثر ۵۰۰ |
| `password` | خیر | حداقل ۸ |

**پاسخ `201`:** `ClientResource`

### جزئیات / ویرایش / حذف

```http
GET    /clients/{client}
PUT    /clients/{client}
PATCH  /clients/{client}
DELETE /clients/{client}
```

**ویرایش:** `name` و `phone` حتی در PATCH الزامی‌اند.

---

## پزشکان

### ایجاد

```http
POST /doctors
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `name` | بله | حداکثر ۲۵۵ |
| `phone` | بله | حداکثر ۲۰، یکتا |
| `email` | خیر | email، یکتا |
| `password` | خیر | حداقل ۸ |
| `birth_date` | خیر | date |
| `national_code` | بله | دقیقاً ۱۰ کاراکتر، یکتا |
| `card_number` | خیر | حداکثر ۱۶، یکتا |
| `medical_number` | خیر | حداکثر ۱۶، یکتا |
| `avatar` | خیر | تصویر، حداکثر ۲ MB |
| `days` | خیر | array یا JSON string |
| `times` | خیر | array یا JSON string |
| `sort_order` | خیر | integer ≥ 0 |
| `department_ids` | خیر | array از UUID دپارتمان |

**پاسخ `201`:** `DoctorResource`

### ویرایش

```http
PUT   /doctors/{doctor}
PATCH /doctors/{doctor}
```

قواعد مشابه create با ignore unique. `name`، `phone`، `national_code` حتی در PATCH الزامی‌اند.

- `department_ids` ارسال نشود → اتصال‌ها حفظ می‌شوند
- `department_ids: []` → همه دپارتمان‌ها حذف می‌شوند
- avatar جدید → فایل قبلی حذف می‌شود

### حذف

```http
DELETE /doctors/{doctor}
```

`204` — avatar نیز حذف می‌شود.

### تنظیم رمز عبور

```http
POST /doctors/{doctor}/password
Content-Type: application/json
```

```json
{ "password": "newpassword123" }
```

**پاسخ `200`:** `DoctorResource`

### مرتب‌سازی پزشکان

```http
PUT /doctors/reorder
Content-Type: application/json
```

```json
{
  "ordered_ids": ["uuid-1", "uuid-2", "uuid-3"]
}
```

- حداقل یک UUID
- هر UUID باید user از نوع `doctor` باشد
- UUIDها باید متمایز باشند

**پاسخ `200`:** آرایه `DoctorResource` به همان ترتیب ورودی.

### نوبت‌های پزشک (بازه‌های زمانی)

بدون query و pagination؛ مرتب‌سازی بر اساس `date` سپس `time`:

```http
GET /doctors/{doctor}/appointments/today
GET /doctors/{doctor}/appointments/yesterday
GET /doctors/{doctor}/appointments/tomorrow
GET /doctors/{doctor}/appointments/last-7-days
GET /doctors/{doctor}/appointments/last-30-days
GET /doctors/{doctor}/appointments/next-30-days
GET /doctors/{doctor}/appointments/all
```

**پاسخ `200`:** آرایه `AppointmentResource`

> محاسبه «امروز» بر اساس timezone برنامه (`UTC`) است.

---

## نوبت‌ها

### فهرست

```http
GET /appointments
```

| Query | توضیح |
|-------|--------|
| `search` | نام/تلفن مراجع یا پزشک |
| `status` | `pending`, `done` |
| `date` | `YYYY-MM-DD` |
| `doctor_id` | UUID |
| `client_id` | UUID |
| `payment_status` | `pending`, `paid`, `unpaid` |
| `sort_by` | `date`, `time`, `amount`, `status`, `created_at` |
| `sort_direction` | `asc` / `desc` |
| `per_page` | پیش‌فرض ۱۰، ۱ تا ۱۰۰ |
| `page` | — |

**پاسخ `200`:** pagination — `AppointmentResource`

### ایجاد

```http
POST /appointments
Content-Type: application/json
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `doctor_id` | بله | UUID کاربر `doctor` |
| `client_id` | بله | UUID کاربر `client` |
| `date` | بله | date |
| `time` | بله | string، حداکثر ۲۰ |
| `amount` | بله | integer ≥ 0 |
| `status` | بله | `pending`, `done` |
| `payment_status` | بله | `pending`, `paid`, `unpaid` |

**پاسخ `201`:** `AppointmentResource`

**رفتار پرداخت:**

- `payment_status=paid` → مبلغ payment = amount نوبت
- `pending` یا `unpaid` → مبلغ payment = 0

### جزئیات / ویرایش / حذف

```http
GET    /appointments/{appointment}
PUT    /appointments/{appointment}
PATCH  /appointments/{appointment}
DELETE /appointments/{appointment}
```

**ویرایش:** تمام فیلدهای create حتی در PATCH الزامی‌اند.

---

## دپارتمان‌ها

### ایجاد

```http
POST /departments
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `slug` | بله | حداکثر ۲۵۵، یکتا |
| `excerpt` | خیر | — |
| `content` | بله | — |
| `thumbnail` | خیر | تصویر، حداکثر ۲ MB |

**پاسخ `201`:** `DepartmentResource`

### ویرایش / حذف

```http
PUT    /departments/{department}
PATCH  /departments/{department}
DELETE /departments/{department}
```

- `title`, `slug`, `content`: `sometimes` (در صورت ارسال الزامی)
- thumbnail جدید → فایل قبلی حذف می‌شود
- حذف: `204`

> خواندن عمومی: `GET /departments` و `GET /departments/{department}`

---

## رزومه پزشک

### مشاهده

```http
GET /doctors/{doctor}/resume
```

**موجود `200`:** `ResumeResource`  
**ناموجود `200`:** `{ "message": "Resume not found.", "data": null }`

### ایجاد/ویرایش (Upsert)

```http
POST /doctors/{doctor}/resume
Content-Type: multipart/form-data
```

| فیلد | توضیح |
|------|--------|
| `title`, `bio`, `specialization`, `content` | اختیاری |
| `educations`, `experiences`, `skills`, `certifications`, `social_links` | array یا JSON string |
| `file` | PDF، حداکثر ۴ MB |

**پاسخ `200`:** `{ "message": "Resume saved successfully.", "data": ResumeResource }`

---

## منابع پزشک

ادمین می‌تواند منابع هر پزشک را مدیریت کند. خود پزشک نیز از پنل خودش (`/doctor/resources`) همین قابلیت را دارد.

### فهرست

```http
GET /doctors/{doctor}/resources
```

Query: `search`, `type` (`link|file`), `sort_by` (`created_at|updated_at|title|type`), `sort_direction`, `per_page` (۱–۱۰۰), `page`

**پاسخ `200`:** pagination — `DoctorResourceItemResource`

### ایجاد

```http
POST /doctors/{doctor}/resources
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `type` | بله | `link` یا `file` |
| `description` | خیر | — |
| `link` | برای `type=link` | URL، حداکثر ۲۵۵ (برای `file` ممنوع) |
| `file` | برای `type=file` | حداکثر ۱۰ MB (برای `link` ممنوع) |

**پاسخ `201`**

### ویرایش / حذف

```http
PUT    /doctors/{doctor}/resources/{doctorResource}
PATCH  /doctors/{doctor}/resources/{doctorResource}
DELETE /doctors/{doctor}/resources/{doctorResource}
```

منبع باید متعلق به پزشک مسیر باشد؛ در غیر این صورت `404`.

---

## پرونده پزشکی

### مشاهده

```http
GET /clients/{client}/medical-record
```

**پاسخ `200`:** `MedicalRecordResource` یا `data: null`

### ایجاد / ویرایش (Upsert)

```http
POST /clients/{client}/medical-record
PUT  /clients/{client}/medical-record
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `record_number` | بله | حداکثر ۱۰۰، یکتا |
| `reference_source` | خیر | حداکثر ۲۵۵ |
| `admission_date`, `visit_date` | خیر | date |
| `doctor_id`, `supervisor_id`, `admin_id` | خیر | UUID user موجود |
| `chief_complaints`, `present_illness`, `past_history`, `family_history`, `personal_history`, `mse`, `diagnosis` | خیر | string |
| `companion_name` | خیر | حداکثر ۲۵۵ |
| `companion_phone` | خیر | حداکثر ۲۰ |
| `companion_address` | خیر | حداکثر ۵۰۰ |
| `companion_birth_date` | خیر | date |
| `images` | خیر | array از تصاویر (jpg/jpeg/png/webp، هر کدام ≤ ۵ MB) |

**پاسخ `201`** (هم POST و هم PUT)

**نکات:**

- برای هر client فقط یک پرونده
- تصاویر جدید **append** می‌شوند (جایگزین نمی‌شوند)
- API حذف تصویر/پرونده ندارد

**ساختار پاسخ:**

```json
{
  "id": "uuid",
  "record_number": "REC-001",
  "reference_source": null,
  "admission_date": null,
  "visit_date": null,
  "chief_complaints": null,
  "present_illness": null,
  "past_history": null,
  "family_history": null,
  "personal_history": null,
  "mse": null,
  "diagnosis": null,
  "client": { "...": "ClientResource" },
  "companion": { "name": null, "phone": null, "address": null, "birth_date": null },
  "doctor": { "...": "DoctorResource" },
  "supervisor": { "...": "DoctorResource" },
  "admin": { "...": "AdminResource" },
  "images": [
    { "id": "uuid", "medical_record_id": "uuid", "file_path": "...", "url": "https://...", "created_at": "...", "updated_at": "..." }
  ],
  "created_at": "...",
  "updated_at": "..."
}
```

---

## ارزیابی‌های اولیه

### فهرست

```http
GET /assessments
```

| Query | پیش‌فرض | توضیح |
|-------|---------|--------|
| `client_id` | — | UUID |
| `doctor_id` | — | UUID |
| `status` | — | `pending`, `done` |
| `search` | — | نام/تلفن مراجع |
| `sort_by` | `created_at` | `created_at`, `date`, `status`, `updated_at` |
| `sort_direction` | `desc` | — |
| `per_page` | `15` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — `InitAssessmentResource`

### حذف

```http
DELETE /assessments/{initAssessment}
```

**پاسخ `204`**

> ثبت عمومی: `POST /assessments` — جزئیات در [راهنمای عمومی](../public/README.md#ثبت-ارزیابی-اولیه)

---

## پرداخت‌ها

### فهرست

```http
GET /payments
```

| Query | پیش‌فرض | توضیح |
|-------|---------|--------|
| `client_id` | — | UUID |
| `status` | — | `pending`, `paid`, `unpaid` |
| `search` | — | نام/تلفن client |
| `sort_by` | `created_at` | `created_at`, `amount`, `status`, `updated_at` |
| `sort_direction` | `desc` | — |
| `per_page` | `15` | ۱ تا ۱۰۰ |
| `page` | `1` | — |

**پاسخ `200`:** pagination — `PaymentResource`:

```json
{
  "id": "uuid",
  "appointment_id": "uuid",
  "status": "pending",
  "amount": 0,
  "appointment": { "...": "AppointmentResource" },
  "created_at": "...",
  "updated_at": "..."
}
```

> وضعیت پرداخت از طریق ایجاد/ویرایش نوبت مدیریت می‌شود؛ endpoint مستقل create/update ندارد.

---

## فاکتورها

### فهرست

```http
GET /invoices
```

بدون query و pagination؛ همه فاکتورها به ترتیب نزولی ایجاد.

**پاسخ `200`:** آرایه `InvoiceResource`

### تولید فاکتور

```http
POST /invoices/generate
Content-Type: application/json
```

```json
{
  "client_id": "uuid",
  "from_date": "2026-01-01",
  "to_date": "2026-01-31",
  "admin_id": "uuid-optional"
}
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `client_id` | بله | UUID user موجود |
| `from_date` | بله | date |
| `to_date` | بله | date، ≥ from_date |
| `admin_id` | خیر | پیش‌فرض: ادمین فعلی |

**پاسخ `201`:** `InvoiceResource`

```json
{
  "id": "uuid",
  "client_id": "uuid",
  "admin_id": "uuid",
  "from_date": "2026-01-01",
  "to_date": "2026-01-31",
  "file_path": "invoices/...",
  "file_url": "https://...",
  "client": { "...": "ClientResource" },
  "admin": { "...": "AdminResource" },
  "created_at": "...",
  "updated_at": "..."
}
```

> خروجی فایل JSON در storage است، نه PDF.

---

## درباره ما

### ویرایش (Upsert)

```http
POST /about
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `about` | بله | — |
| `address` | خیر | حداکثر ۵۰۰ |
| `phones` | خیر | string، حداکثر ۲۵۵ |
| `mobile_phones` | خیر | string، حداکثر ۲۵۵ |
| `latitude` | خیر | string، حداکثر ۵۰ |
| `longitude` | خیر | string، حداکثر ۵۰ |
| `logo` | خیر | تصویر، حداکثر ۵ MB |

**پاسخ `200`:** `AboutResource`

> خواندن عمومی: `GET /about`

---

## اعلان‌ها

### ایجاد

```http
POST /notifications
Content-Type: application/json
```

| فیلد | الزامی | پیش‌فرض | توضیح |
|------|--------|---------|--------|
| `title` | بله | — | حداکثر ۲۵۵ |
| `message` | خیر | — | — |
| `type` | خیر | `system` | حداکثر ۱۰۰ |
| `notifiable_type` | خیر | — | string |
| `notifiable_id` | خیر | — | UUID |
| `priority` | خیر | `normal` | `low`, `normal`, `medium`, `high` |
| `delivery_channels` | خیر | خودکار | array |
| `meta` | خیر | — | object |
| `status` | خیر | `active` | حداکثر ۵۰ |
| `scheduled_at` | خیر | — | datetime |

**کانال‌های پیش‌فرض (اگر `delivery_channels` خالی باشد):**

- `high` → `in_app`, `email`, `sms`
- `medium` → `in_app`, `email`
- سایر → `in_app`

**پاسخ `201`:** `AppNotificationResource`

> ایجاد فقط رکورد می‌سازد؛ ارسال واقعی email/SMS در این endpoint انجام نمی‌شود.

### مشاهده (مشترک)

`GET /notifications`, `GET /notifications/unread`, `POST /notifications/{notification}/read` — جزئیات در [راهنمای عمومی](../public/README.md#اعلان‌ها-مشترک).

---

## پیامک

### ارسال تکی

```http
POST /sms/single
Content-Type: application/json
```

```json
{
  "phone": "09123456789",
  "message": "متن پیامک"
}
```

### ارسال گروهی

```http
POST /sms/multi
Content-Type: application/json
```

```json
{
  "phones": ["09121111111", "09122222222"],
  "message": "متن پیامک"
}
```

**پاسخ موفق `200`:** `{ "message": "...", "data": null }`  
**خطای سرویس `502`:** نبود تنظیمات SMS یا خطای provider

---

## کارگاه‌ها

### ایجاد

```http
POST /workshops
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `slug` | بله | حداکثر ۲۵۵، یکتا |
| `excerpt` | خیر | — |
| `content` | خیر | — |
| `organizers` | خیر | حداکثر ۲۵۵ |
| `start_date` | خیر | date |
| `end_date` | خیر | date، ≥ start_date |
| `week_day` | خیر | حداکثر ۵۰ |
| `time` | خیر | حداکثر ۵۰ |
| `image` | خیر | تصویر، حداکثر ۵ MB |

**پاسخ `201`:** `WorkshopResource`

### ویرایش / حذف

```http
PUT    /workshops/{workshop}
PATCH  /workshops/{workshop}
DELETE /workshops/{workshop}
```

- `title` و `slug` در صورت ارسال الزامی
- image جدید → تصویر قبلی حذف
- حذف workshop → sessions و اتصال participants حذف (participants مستقل باقی می‌مانند)

> خواندن عمومی: `GET /workshops`, `GET /workshops/{workshop}`

---

## جلسات کارگاه

```http
GET    /workshops/{workshop}/sessions
POST   /workshops/{workshop}/sessions
GET    /workshops/{workshop}/sessions/{session}
PUT    /workshops/{workshop}/sessions/{session}
PATCH  /workshops/{workshop}/sessions/{session}
DELETE /workshops/{workshop}/sessions/{session}
```

### ایجاد

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `description` | خیر | — |
| `session_date` | خیر | date |
| `start_time` | خیر | حداکثر ۵۰ |
| `end_time` | خیر | حداکثر ۵۰ |
| `location` | خیر | حداکثر ۲۵۵ |
| `link` | خیر | حداکثر ۵۰۰ |

**پاسخ `201`:** `WorkshopSessionResource`

**فهرست:** بدون pagination؛ مرتب صعودی `session_date`.

اگر session متعلق به workshop نباشد → `404`.

---

## شرکت‌کنندگان کارگاه

```http
GET    /workshops/{workshop}/participants
POST   /workshops/{workshop}/participants
PATCH  /workshops/{workshop}/participants/{participant}/approve
PATCH  /workshops/{workshop}/participants/{participant}/unapprove
DELETE /workshops/{workshop}/participants/{participant}
```

### ثبت شرکت‌کننده

```http
POST /workshops/{workshop}/participants
Content-Type: application/json
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `name` | بله | حداکثر ۲۵۵ |
| `english_name` | خیر | حداکثر ۲۵۵ |
| `phone` | بله | حداکثر ۲۰ |
| `national_code` | خیر | حداکثر ۲۰ |
| `gender` | خیر | `male`, `female`, `other` |
| `approved` | خیر | پیش‌فرض `false` |

**رفتار:**

1. جستجو با `national_code`، سپس `phone`
2. participant موجود به‌روزرسانی یا جدید ساخته می‌شود
3. اگر قبلاً در workshop ثبت شده → فقط `approved` pivot به‌روز می‌شود
4. ثبت جدید → `registered_at = now()`

**پاسخ `201`:** `ParticipantResource`

### تأیید / لغو تأیید

```http
PATCH /workshops/{workshop}/participants/{participant}/approve
```

- `approved = true`, `joined_at = now()`
- پاسخ `200`

```http
PATCH /workshops/{workshop}/participants/{participant}/unapprove
```

- `approved = false` (joined_at پاک نمی‌شود)

### حذف از کارگاه

```http
DELETE /workshops/{workshop}/participants/{participant}
```

فقط اتصال حذف می‌شود؛ participant اصلی باقی می‌ماند. پاسخ `204`.

**فهرست:** بدون pagination؛ مرتب بر اساس `name`. هر item شامل اطلاعات pivot (`approved`, `registered_at`, `joined_at`).

---

## کلاس‌ها

```http
GET    /classes
POST   /classes
GET    /classes/{class}
PUT    /classes/{class}
PATCH  /classes/{class}
DELETE /classes/{class}
```

### ایجاد

```http
POST /classes
Content-Type: application/json
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `description` | خیر | — |
| `start_date` | خیر | date |
| `end_date` | خیر | date، ≥ start_date |
| `week_day` | خیر | حداکثر ۵۰ |
| `time` | خیر | حداکثر ۵۰ |
| `teacher_id` | بله | UUID user موجود |
| `student_ids` | خیر | array از UUID |
| `dates` | خیر | array از date |

**پاسخ `201`:** `CourseClassResource`:

```json
{
  "id": "uuid",
  "title": "عنوان",
  "description": null,
  "start_date": null,
  "end_date": null,
  "week_day": null,
  "time": null,
  "teacher": { "id": "uuid", "name": "...", "phone": "..." },
  "students": [{ "id": "uuid", "name": "...", "phone": "..." }],
  "dates": [{ "id": "uuid", "class_id": "uuid", "date": "2026-01-01", "created_at": "...", "updated_at": "..." }],
  "created_at": "...",
  "updated_at": "..."
}
```

### ویرایش — نکات مهم Replace

- `dates` ارسال شود → تمام تاریخ‌های قبلی جایگزین می‌شوند
- `dates: []` → همه تاریخ‌ها حذف
- `teacher_id` یا `student_ids` ارسال شود → teacher/studentهای قبلی detach می‌شوند
- برای تغییر فقط دانشجویان، `teacher_id` فعلی را هم ارسال کنید
- برای تغییر فقط استاد، `student_ids` کامل را هم ارسال کنید

**فهرست:** بدون query و pagination.

---

## دسته‌بندی‌ها (محتوا)

> **نیازمند نقش:** `author`, `boss`, `manager`

```http
POST   /categories
PUT    /categories/{category}
PATCH  /categories/{category}
DELETE /categories/{category}
```

### ایجاد

```http
POST /categories
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `name` | بله | حداکثر ۱۰۰ |
| `slug` | بله | حداکثر ۲۵۵، یکتا |
| `excerpt` | خیر | — |
| `content` | خیر | — |
| `image` | خیر | تصویر، حداکثر ۲ MB |

**پاسخ `201`:** `CategoryResource`

### ویرایش / حذف

- `name` و `slug` در صورت ارسال الزامی
- image جدید → تصویر قبلی حذف
- حذف category → `category_id` پست‌ها null می‌شود

> خواندن عمومی: `GET /categories`, `GET /categories/{category}`

---

## تگ‌ها (محتوا)

> **نیازمند نقش:** `author`, `boss`, `manager`

ساختار کاملاً مشابه [دسته‌بندی‌ها](#دسته‌بندی‌ها-محتوا):

```http
POST   /tags
PUT    /tags/{tag}
PATCH  /tags/{tag}
DELETE /tags/{tag}
```

فیلد `name` حداکثر ۱۰۰ کاراکتر.

> خواندن عمومی: `GET /tags`, `GET /tags/{tag}`

---

## پست‌ها (محتوا)

> **نیازمند نقش:** `author`, `boss`, `manager`

### ایجاد

```http
POST /posts
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `slug` | بله | حداکثر ۲۵۵، یکتا |
| `excerpt` | خیر | — |
| `content` | بله | — |
| `thumbnail` | خیر | تصویر، حداکثر ۲ MB |
| `status` | خیر | `draft`, `published`, `archived`؛ پیش‌فرض `draft` |
| `published_at` | خیر | date |
| `category_id` | خیر | UUID |
| `tag_ids` | خیر | array از UUID |

`author_id` از کاربر واردشده گرفته می‌شود.

**پاسخ `201`:** `PostResource`

### ویرایش / حذف

```http
PUT    /posts/{post}
PATCH  /posts/{post}
DELETE /posts/{post}
```

- `title`, `slug`, `content` در صورت ارسال الزامی
- `tag_ids` ارسال شود → جایگزین کامل تگ‌ها
- `tag_ids: []` → همه تگ‌ها حذف
- عدم ارسال `tag_ids` → تگ‌های فعلی حفظ
- thumbnail جدید → فایل قبلی حذف
- محدودیت مالکیت ندارد؛ هر author/boss/manager می‌تواند هر پستی را ویرایش کند

> خواندن عمومی: `GET /posts`, `GET /posts/{post}`

---

## مدیریت کامنت‌ها

### فهرست

```http
GET /comments
```

| Query | پیش‌فرض | توضیح |
|-------|---------|--------|
| `post_id` | — | UUID |
| `approved` | — | boolean |
| `per_page` | `20` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — `CommentResource` (شامل `email`)

### جزئیات

```http
GET /comments/{comment}
```

### ویرایش

```http
PATCH /comments/{comment}
Content-Type: application/json
```

| فیلد | توضیح |
|------|--------|
| `body` | در صورت ارسال الزامی |
| `author_name` | nullable، حداکثر ۲۵۵ |
| `email` | nullable، email |
| `approved` | boolean |

**پاسخ `200`**

### حذف

```http
DELETE /comments/{comment}
```

**پاسخ `204`**

> ثبت عمومی: `POST /comments`

---

## پشتیبان‌گیری

> **هشدار:** این endpointها با متد `GET` فایل و رکورد backup می‌سازند. از prefetch/cache خودکار پرهیز کنید.

```http
GET /backup/admins
GET /backup/doctors
GET /backup/clients
GET /backup/resumes
GET /backup/posts
GET /backup/categories
GET /backup/tags
GET /backup/workshops
GET /backup/about
```

**پاسخ `200`:**

```json
{
  "message": "Backup created successfully.",
  "data": {
    "backup": {
      "id": "uuid",
      "type": "posts",
      "file_path": "backups/posts/...",
      "file_url": "https://...",
      "created_at": "...",
      "updated_at": "..."
    },
    "url": "https://..."
  }
}
```

**محدودیت‌ها:**

- password کاربران در backup نیست
- backup پست شامل tagها و comments نیست
- backup workshop شامل sessions و participants نیست
- فایل‌های media به‌صورت مستقل کپی نمی‌شوند

---

## بازیابی

```http
POST /restore/admins
POST /restore/doctors
POST /restore/clients
POST /restore/resumes
POST /restore/posts
POST /restore/categories
POST /restore/tags
POST /restore/workshops
POST /restore/about
Content-Type: application/json
```

### نحوه ارسال داده

سه روش پشتیبانی می‌شود:

**۱. JSON body (پیشنهادی):**

```json
{
  "data": [ { "...": "..." } ]
}
```

**۲. آرایه خام:**

```json
[ { "...": "..." } ]
```

**۳. آپلود فایل:**

```http
Content-Type: multipart/form-data
file: doctors_backup.json
```

> اگر هنگام آپلود فایل خطای `Unable to create temporary file` دیدید، به‌جای multipart، محتوای JSON را با `Content-Type: application/json` در body بفرستید.

### کلیدهای طبیعی (بدون وابستگی به ID)

بازیابی بر اساس ID قدیمی انجام **نمی‌شود**. تطبیق به این صورت است:

| نوع | کلید تطبیق |
|-----|------------|
| admins / doctors / clients | `phone` |
| resumes | `doctor_phone` (یا `phone` / `doctor.phone`) |
| categories / tags / workshops / posts | `slug` |
| posts → نویسنده | `author_phone` |
| posts → دسته‌بندی | `category_slug` |
| posts → تگ‌ها | `tag_slugs[]` یا `tags[].slug` |
| about | تک‌رکوردی (upsert) |

**نمونه پزشک (فرمت قدیمی یا جدید):**

```json
{
  "data": [
    {
      "name": "دکتر علی محرابی",
      "phone": "09131889355",
      "email": "ali@gmail.com",
      "birth_date": "1981-03-16",
      "national_code": "5110245123",
      "card_number": "6037697144523652",
      "medical_number": "51223654",
      "avatar": "doctor_avatars/xxx.jpg",
      "days": null,
      "times": null,
      "password": "$2y$10$...",
      "resume": "doctor_resumes/xxx.pdf"
    }
  ]
}
```

**نمونه رزومه:**

```json
{
  "data": [
    {
      "doctor_phone": "09131889355",
      "title": "رزومه دکتر علی محرابی",
      "bio": "...",
      "specialization": "...",
      "skills": ["CBT"],
      "file_path": null
    }
  ]
}
```

**نمونه پست:**

```json
{
  "data": [
    {
      "title": "عنوان",
      "slug": "post-slug",
      "content": "...",
      "status": "published",
      "author_phone": "09140379929",
      "category_slug": "general-mental-health",
      "tag_slugs": ["mental-health", "couples-therapy"]
    }
  ]
}
```

**پاسخ `200`:**

```json
{
  "message": "Data restored successfully.",
  "data": {
    "id": "restore-log-uuid",
    "type": "doctors",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### ترتیب پیشنهادی بازیابی

1. `admins` / `doctors` / `clients`
2. `resumes` (نیاز به `doctor_phone`)
3. `categories` / `tags`
4. `posts` (نیاز به `author_phone` و در صورت نیاز `category_slug` / `tag_slugs`)
5. `workshops` / `about`

### نکات

- `id` عددی قدیمی نادیده گرفته می‌شود؛ UUID جدید ساخته می‌شود.
- passwordهای bcrypt موجود بدون double-hash ذخیره می‌شوند.
- فیلدهای قدیمی مثل `role` → `admin_role`، `logo_path` → `logo`، `lat`/`long` → `latitude`/`longitude`، `admin_id` → `author_id` پشتیبانی می‌شوند.
- فایل‌های media بازیابی نمی‌شوند؛ فقط path ذخیره می‌شود.
- import تراکنشی است؛ خطای یک آیتم همه را rollback می‌کند.

---

## لینک‌های مرتبط

- [APIهای عمومی](../public/README.md)
- [پنل پزشک / تراپیست](../doctor/README.md)
- [فهرست اصلی](../README.md)
