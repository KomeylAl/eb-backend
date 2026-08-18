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
- [پنل حسابداری (راهنمای فرانت)](../accounting/README.md)
- [درباره ما](#درباره-ما)
- [اعلان‌ها](#اعلان‌ها)
- [پیامک](#پیامک)
- [کارگاه‌ها](#کارگاه‌ها)
- [منابع کارگاه (مواد آموزشی)](#منابع-کارگاه-مواد-آموزشی)
- [گواهی کارگاه](#گواهی-کارگاه)
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
| `payment_status` | فیلتر روی `payments.status`: `pending`, `paid`, `unpaid`, `partial`, `refunded` |
| `treatment_program_id` | UUID برنامه درمان |
| `room_id` | UUID اتاق |
| `from_date`, `to_date` | بازه روی `appointment.date` |
| `sort_by` | `date`, `time`, `amount`, `status`, `created_at` |
| `sort_direction` | `asc` / `desc` |
| `per_page` | پیش‌فرض ۱۰، ۱ تا ۱۰۰ |
| `page` | — |

**پاسخ `200`:** pagination — `AppointmentResource`

در پاسخ، وضعیت پرداخت داخل `payment` است (فیلد سطح‌بالای `payment_status` نیست). `doctor` و `client` به صورت آبجکت تکی برمی‌گردند. فیلد `service` نیز برمی‌گردد.

### ایجاد

```http
POST /appointments
Content-Type: application/json
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `treatment_program_id` | بله* | UUID برنامه درمان (`required_without:create_treatment_program`) |
| `create_treatment_program` | خیر | اگر `true` باشد برنامه جدید برای همان زوج مراجع/درمانگر ساخته می‌شود |
| `program_title` | خیر | عنوان برنامه هنگام ایجاد inline |
| `doctor_id` | بله | UUID کاربر `doctor` — باید با برنامه یکی باشد |
| `client_id` | بله | UUID کاربر `client` — باید با برنامه یکی باشد |
| `room_id` | خیر | UUID اتاق؛ تداخل همان `date`+`time` برای نوبت‌های `pending` رد می‌شود |
| `date` | بله | date |
| `time` | بله | string، حداکثر ۲۰ |
| `amount` | بله | integer ≥ 0 |
| `service` | خیر | نوع خدمت، حداکثر ۲۵۵ |
| `status` | بله | `pending`, `done` |
| `session_notes` | خیر | یادداشت جلسه (نوبت = جلسه) |
| `payment_status` | بله | `pending`, `paid`, `unpaid`, `partial`, `refunded` |
| `paid_amount` | برای `partial` بله | integer؛ `0 < paid_amount < amount` |
| `payment_method` | خیر | `cash`, `card`, `transfer`, `other` |

**پاسخ `201`:** `AppointmentResource`

**رفتار پرداخت:**

- `amount` روی payment همیشه برابر مبلغ نوبت است
- `paid` → `paid_amount = amount`
- `pending` / `unpaid` / `refunded` → `paid_amount = 0`
- `partial` → `0 < paid_amount < amount`
- هر تغییر در لاگ `payment_transactions` ثبت می‌شود

### جزئیات / ویرایش / حذف

```http
GET    /appointments/{appointment}
PUT    /appointments/{appointment}
PATCH  /appointments/{appointment}
DELETE /appointments/{appointment}
```

**ویرایش:** تمام فیلدهای create (به‌جز `create_treatment_program`) حتی در PATCH الزامی‌اند. `treatment_program_id` و `room_id` و `session_notes` قابل ویرایش‌اند.

---

## برنامه‌های درمان

هر برنامه درمان یک زوج مراجع+درمانگر است. پرونده پزشکی ۱:۱ به برنامه وصل می‌شود (نه به مراجع).

### فهرست / ایجاد

```http
GET  /treatment-programs
POST /treatment-programs
```

| Query (GET) | توضیح |
|-------------|--------|
| `client_id` | UUID |
| `doctor_id` | UUID |
| `status` | `active`, `completed`, `paused`, `cancelled` |
| `search` | عنوان |
| `per_page`, `page` | — |

| فیلد (POST) | الزامی | توضیح |
|-------------|--------|--------|
| `client_id` | بله | UUID |
| `doctor_id` | بله | UUID پزشک |
| `title` | خیر | حداکثر ۲۵۵ |
| `status` | خیر | پیش‌فرض `active` |
| `started_at`, `ended_at` | خیر | date |

### جزئیات / ویرایش

```http
GET   /treatment-programs/{treatment_program}
PATCH /treatment-programs/{treatment_program}
PUT   /treatment-programs/{treatment_program}
```

---

## تکالیف جلسه (Homework)

تکالیف زیر نوبت/جلسه مدیریت می‌شوند. پنل مراجع فعلاً وجود ندارد؛ وضعیت انجام توسط ادمین/درمانگر ثبت می‌شود.

```http
GET    /appointments/{appointment}/homeworks
POST   /appointments/{appointment}/homeworks
PATCH  /homeworks/{homework}
DELETE /homeworks/{homework}
```

| فیلد (POST/PATCH) | الزامی | توضیح |
|-------------------|--------|--------|
| `type` | بله (create) | `text`, `file`, `link`, `checklist` |
| `title` | بله (create) | — |
| `body` | خیر | — |
| `meta` | خیر | JSON (مثلاً آیتم‌های checklist) |
| `status` | خیر | `assigned`, `done`, `cancelled` |
| `due_at` | خیر | datetime |

---

## اتاق‌ها

```http
GET    /rooms
POST   /rooms
GET    /rooms/{room}
PATCH  /rooms/{room}
PUT    /rooms/{room}
DELETE /rooms/{room}
GET    /rooms/availability?date=YYYY-MM-DD
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `name` | بله | — |
| `code` | خیر | unique |
| `capacity` | خیر | integer |
| `is_active` | خیر | boolean |

`availability` لیست اتاق‌های فعال و اسلات‌های اشغال‌شده همان روز (نوبت‌های `pending` با `room_id`) را برمی‌گرداند.

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

پرونده پزشکی به **برنامه درمان** وصل است (۱ پرونده / برنامه). مسیرهای قدیمی `clients/{client}/medical-record` حذف شده‌اند.

### مشاهده

```http
GET /treatment-programs/{treatment_program}/medical-record
```

**پاسخ `200`:** `MedicalRecordResource` یا `data: null`

### ایجاد / ویرایش (Upsert)

```http
POST /treatment-programs/{treatment_program}/medical-record
PUT  /treatment-programs/{treatment_program}/medical-record
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `record_number` | بله | حداکثر ۱۰۰، یکتا |
| `reference_source` | خیر | حداکثر ۲۵۵ |
| `admission_date`, `visit_date` | خیر | date |
| `doctor_id`, `supervisor_id`, `admin_id` | خیر | UUID user موجود؛ در صورت خالی بودن از برنامه مشتق می‌شود |
| `chief_complaints`, `present_illness`, `past_history`, `family_history`, `personal_history`, `mse`, `diagnosis` | خیر | string |
| `companion_name` | خیر | حداکثر ۲۵۵ |
| `companion_phone` | خیر | حداکثر ۲۰ |
| `companion_address` | خیر | حداکثر ۵۰۰ |
| `companion_birth_date` | خیر | date |
| `images` | خیر | array از تصاویر (jpg/jpeg/png/webp، هر کدام ≤ ۵ MB) |

**پاسخ `201`** (هم POST و هم PUT)

**نکات:**

- برای هر برنامه درمان فقط یک پرونده (`treatment_program_id` unique)
- تصاویر جدید **append** می‌شوند (جایگزین نمی‌شوند)
- API حذف تصویر/پرونده ندارد

**ساختار پاسخ:** شامل `treatment_program_id` و در صورت load شدن `treatment_program`؛ `client`/`doctor` از طریق برنامه یا فیلدهای denormalized برمی‌گردند.
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

> راهنمای کامل پنل حسابداری: [docs/accounting](../accounting/README.md)

### فهرست / جزئیات

```http
GET /payments
GET /payments/{payment}
```

| Query | پیش‌فرض | توضیح |
|-------|---------|--------|
| `client_id` | — | UUID |
| `doctor_id` | — | UUID |
| `status` | — | `pending`, `paid`, `unpaid`, `partial`, `refunded` |
| `method` | — | `cash`, `card`, `transfer`, `other` |
| `from_date`, `to_date` | — | روی `created_at` |
| `search` | — | نام/تلفن client |
| `sort_by` | `created_at` | `created_at`, `amount`, `paid_amount`, `status`, `updated_at` |
| `sort_direction` | `desc` | — |
| `per_page` | `15` | ۱ تا ۱۰۰ |
| `page` | `1` | — |

**پاسخ `200`:** pagination — `PaymentResource`:

```json
{
  "id": "uuid",
  "appointment_id": "uuid",
  "status": "paid",
  "amount": 500000,
  "paid_amount": 500000,
  "method": "cash",
  "appointment": { "...": "AppointmentResource" },
  "created_at": "...",
  "updated_at": "..."
}
```

> وضعیت پرداخت فقط از مسیر ایجاد/ویرایش نوبت مدیریت می‌شود. لاگ تغییرات: `GET /payment-transactions`.

---

## فاکتورها

> مدل حسابداری: هدر + اقلام در دیتابیس. چاپ/PDF سمت فرانت است. جزئیات کامل: [راهنمای حسابداری](../accounting/README.md).

```http
GET    /invoices
POST   /invoices
GET    /invoices/{invoice}
PUT    /invoices/{invoice}
PATCH  /invoices/{invoice}
DELETE /invoices/{invoice}
POST   /invoices/suggest-items
```

**ایجاد — فیلدهای مهم:** `client_id`, `issue_date`, `items[]` با `description`, `unit`, `quantity`, `unit_price` (و اختیاری `appointment_id`).

**پیشنهاد اقلام از نوبت:** `POST /invoices/suggest-items` با `client_id` + بازه تاریخ؛ فاکتور ذخیره نمی‌شود.

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
| `type` | بله | `general`, `specialized`, `webinar`, `seminar` (پیش‌فرض در صورت خالی: `general`؛ `special` → `specialized`) |
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

## منابع کارگاه (مواد آموزشی)

منابع فقط توسط ادمین مدیریت می‌شوند و فایل‌ها روی دیسک خصوصی ذخیره می‌گردند. مشاهده/دانلود برای شرکت‌کنندگان تأییدشده از [ابراز پلاس](../plus/README.md) انجام می‌شود.

```http
GET    /workshops/{workshop}/materials
POST   /workshops/{workshop}/materials
PATCH  /workshops/{workshop}/materials/{material}
PUT    /workshops/{workshop}/materials/{material}
DELETE /workshops/{workshop}/materials/{material}
GET    /workshops/{workshop}/materials/{material}/download
```

### ایجاد

`Content-Type: multipart/form-data` (برای فایل) یا JSON (برای لینک)

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `type` | بله | `file` یا `link` |
| `description` | خیر | — |
| `link` | برای `link` | URL |
| `file` | برای `file` | pdf/pptx/docx/zip/تصویر ≤ ۲۰ MB |
| `sort_order` | خیر | عدد |

**پاسخ `201`:** `WorkshopMaterialResource`

---

## گواهی کارگاه

قالب و رکورد صدور روی سرور ذخیره می‌شود. صدور به دو صورت است:

1. **داینامیک:** قالب + `payload`؛ PDF در فرانت ساخته می‌شود  
2. **آپلود فایل:** PDF/تصویر خصوصی برای هر شرکت‌کننده؛ دانلود از endpoint احرازهویت‌شده

```http
GET    /certificate-template-presets
GET    /workshops/{workshop}/certificate-template
POST   /workshops/{workshop}/certificate-template
GET    /workshops/{workshop}/certificates
POST   /workshops/{workshop}/certificates
POST   /workshops/{workshop}/certificates/upload
GET    /workshops/{workshop}/certificates/{certificate}/download
DELETE /workshops/{workshop}/certificates/{certificate}
```

### آپلود فایل مدرک

`Content-Type: multipart/form-data`

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `participant_id` | بله | شرکت‌کننده تأییدشده |
| `file` | بله | pdf/jpg/png/webp ≤ ۲۰ MB |
| `certificate_number` | خیر | در غیر این صورت تولید می‌شود |

**پاسخ:** `WorkshopCertificateResource` با `source=uploaded` و `has_file=true`

### ذخیره قالب

`Content-Type: multipart/form-data`

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `template_key` | بله | `classic` \| `minimal` \| `formal` |
| `clinic_name` | خیر | — |
| `title` | خیر | عنوان مدرک |
| `body_text` | خیر | متن با placeholder مثل `{{participant_name}}` |
| `footer_text` | خیر | — |
| `signer_name` / `signer_title` | خیر | — |
| `logo` / `signature` | خیر | تصویر |
| `remove_logo` / `remove_signature` | خیر | boolean |

### صدور

```json
{ "participant_ids": ["uuid…"] }
```

فقط شرکت‌کنندگان **تأییدشده**. پاسخ شامل `payload` کامل برای رندر فرانت است (بدون `file_path`).

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

نظرات برای درمانگر (`doctor`)، مقاله (`post`) و کارگاه (`workshop`) ثبت می‌شوند و تا تأیید ادمین در سایت عمومی نمایش داده نمی‌شوند.

### فهرست (ادمین)

```http
GET /comments
Authorization: Bearer {token}
```

با توکن ادمین، فیلتر اجباری هدف لازم نیست.

| Query | پیش‌فرض | توضیح |
|-------|---------|--------|
| `commentable_type` | — | `doctor` \| `post` \| `workshop` |
| `commentable_id` | — | UUID هدف |
| `approved` | — | boolean |
| `phone` | — | جستجوی جزئی روی شماره |
| `search` | — | جستجو در نام، نام‌خانوادگی، متن، تلفن |
| `per_page` | `20` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — `CommentResource` **شامل `phone`**

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
| `first_name` | در صورت ارسال الزامی |
| `last_name` | در صورت ارسال الزامی |
| `phone` | nullable، حداکثر ۲۰ |
| `body` | در صورت ارسال الزامی |
| `rating` | ۱ تا ۵ |
| `approved` | boolean |

**پاسخ `200`**

### تأیید / لغو تأیید

```http
PATCH /comments/{comment}/approve
PATCH /comments/{comment}/unapprove
```

**پاسخ `200`** با `CommentResource` به‌روزشده.

### حذف

```http
DELETE /comments/{comment}
```

**پاسخ `204`**

> ثبت عمومی و فهرست عمومی تأییدشده‌ها: [نظرات و امتیازها](../public/README.md#نظرات-و-امتیازها)

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
- [پنل حسابداری](../accounting/README.md)
- [فهرست اصلی](../README.md)
