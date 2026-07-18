# پنل پزشک / تراپیست

> مسیر پایه: `/api/v1`  
> [بازگشت به فهرست اصلی](../README.md)

## نکته مهم درباره نام‌گذاری

در رابط کاربری از «پزشک» یا «تراپیست» استفاده می‌شود، اما **مقدار فنی نقش در API برابر `doctor` است**. تمام endpointهای این پنل نیازمند:

```http
Authorization: Bearer {token}
Accept: application/json
```

و `user.type === "doctor"`.

در غیر این صورت:

- بدون توکن → `401`
- توکن معتبر ولی نقش غیرپزشک → `403`

```json
{
  "message": "Forbidden.",
  "errors": null
}
```

---

## فهرست

- [ورود](#ورود)
- [نوبت‌ها](#نوبت‌ها)
- [رزومه](#رزومه)
- [منابع](#منابع)
- [ارزیابی‌های اولیه](#ارزیابی‌های-اولیه)
- [اعلان‌ها](#اعلان‌ها)
- [Endpointهای مشترک](#endpointهای-مشترک)

---

## ورود

```http
POST /auth/login
Content-Type: application/json
```

```json
{
  "phone": "09123456789",
  "password": "password123",
  "type": "doctor"
}
```

**پاسخ `200`:** توکن + اطلاعات کاربر شامل `doctor_profile`.

جزئیات کامل در [راهنمای عمومی](../public/README.md#احراز-هویت).

---

## نوبت‌ها

### فهرست نوبت‌های پزشک

```http
GET /doctor/appointments
Authorization: Bearer {token}
```

فقط نوبت‌هایی که پزشک واردشده در آن‌ها حضور دارد برگردانده می‌شود.

**Query:**

| پارامتر | توضیح |
|---------|--------|
| `search` | جستجو در نام/تلفن مراجع و پزشک |
| `status` | `pending` یا `done` |
| `date` | `YYYY-MM-DD` |
| `doctor_id` | UUID (با scope پزشک جاری AND می‌شود) |
| `client_id` | UUID مراجع |
| `payment_status` | `pending`, `paid`, `unpaid` |
| `sort_by` | `date`, `time`, `amount`, `status`, `created_at` (نامعتبر → `date`) |
| `sort_direction` | `asc` یا `desc` |
| `per_page` | پیش‌فرض ۱۰، ۱ تا ۱۰۰ |
| `page` | — |

**پاسخ `200`:** pagination

```json
{
  "message": "Success",
  "data": {
    "items": [
      {
        "id": "uuid",
        "date": "2026-07-20",
        "time": "10:00",
        "amount": 500000,
        "status": "pending",
        "doctor": {
          "id": "uuid",
          "name": "نام پزشک",
          "phone": "0912..."
        },
        "client": {
          "id": "uuid",
          "name": "نام مراجع",
          "phone": "0912..."
        },
        "payment": {
          "id": "uuid",
          "appointment_id": "uuid",
          "status": "pending",
          "amount": 0,
          "created_at": "...",
          "updated_at": "..."
        },
        "created_at": "...",
        "updated_at": "..."
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 10,
      "total": 0
    }
  }
}
```

> پزشک endpoint ایجاد/ویرایش/حذف نوبت ندارد. مدیریت نوبت‌ها فقط از پنل ادمین انجام می‌شود.

---

## رزومه

### مشاهده رزومه

```http
GET /doctor/resume
Authorization: Bearer {token}
```

**پاسخ موجود `200`:**

```json
{
  "message": "Success",
  "data": {
    "id": "uuid",
    "doctor_id": "uuid",
    "title": "عنوان",
    "bio": "بیوگرافی",
    "specialization": "تخصص",
    "educations": [],
    "experiences": [],
    "skills": [],
    "certifications": [],
    "social_links": [],
    "content": null,
    "file_path": "doctor_resumes/file.pdf",
    "file_url": "https://.../storage/doctor_resumes/file.pdf",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

**رزومه وجود ندارد `200`:**

```json
{
  "message": "Resume not found.",
  "data": null
}
```

### ایجاد یا ویرایش رزومه (Upsert)

```http
POST /doctor/resume
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**فیلدها (همه اختیاری):**

| فیلد | نوع | توضیح |
|------|-----|--------|
| `title` | string | حداکثر ۲۵۵ |
| `bio` | string | — |
| `specialization` | string | حداکثر ۲۵۵ |
| `educations` | array | یا JSON string |
| `experiences` | array | یا JSON string |
| `skills` | array | یا JSON string |
| `certifications` | array | یا JSON string |
| `social_links` | array | یا JSON string |
| `content` | string | — |
| `file` | file | فقط PDF، حداکثر ۴ MB |

**پاسخ `200`:**

```json
{
  "message": "Resume saved successfully.",
  "data": { "...": "ساختار Resume" }
}
```

**نکات:**

- برای هر پزشک فقط یک رزومه وجود دارد (upsert).
- فایل PDF جدید، فایل قبلی را جایگزین می‌کند.
- فیلدهای ارسال‌نشده در update حفظ می‌شوند.
- ارسال صریح `null` مقدار قبلی را پاک می‌کند.

---

## منابع

پزشک می‌تواند منابع پیشنهادی خود (لینک یا فایل) را از پنل خودش مدیریت کند. همین منابع در سایت اصلی داخل جزئیات پزشک برای عموم نمایش داده می‌شوند.

### فهرست منابع پزشک

```http
GET /doctor/resources
Authorization: Bearer {token}
```

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `search` | — | `title`, `description` |
| `type` | — | `link` یا `file` |
| `sort_by` | `created_at` | `created_at`, `updated_at`, `title`, `type` |
| `sort_direction` | `desc` | — |
| `per_page` | `10` | ۱ تا ۱۰۰ |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "doctor_id": "uuid",
  "title": "عنوان منبع",
  "type": "link",
  "description": null,
  "link": "https://example.com",
  "file_path": null,
  "file_url": null,
  "created_at": "...",
  "updated_at": "..."
}
```

### ایجاد منبع

```http
POST /doctor/resources
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

| فیلد | الزامی | توضیح |
|------|--------|--------|
| `title` | بله | حداکثر ۲۵۵ |
| `type` | بله | `link` یا `file` |
| `description` | خیر | — |
| `link` | برای `type=link` | URL، حداکثر ۲۵۵ (برای `file` ممنوع) |
| `file` | برای `type=file` | حداکثر ۱۰ MB (برای `link` ممنوع) |

**پاسخ `201`:** `DoctorResourceItemResource`

### ویرایش منبع

```http
PUT /doctor/resources/{doctorResource}
PATCH /doctor/resources/{doctorResource}
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

همان فیلدهای ایجاد؛ همه اختیاری‌اند. هنگام تغییر `type` به `link` باید لینک داشته باشید (جدید یا قبلی). هنگام تغییر به `file` باید فایل داشته باشید (جدید یا قبلی).

منبع باید متعلق به پزشک واردشده باشد؛ در غیر این صورت `404`.

**پاسخ `200`:** `DoctorResourceItemResource`

### حذف منبع

```http
DELETE /doctor/resources/{doctorResource}
Authorization: Bearer {token}
```

**پاسخ `204`**

> ادمین نیز می‌تواند همین عملیات را از مسیر `/doctors/{doctor}/resources` انجام دهد.

---

## ارزیابی‌های اولیه

### فهرست ارزیابی‌ها

```http
GET /doctor/assessments
Authorization: Bearer {token}
```

فقط ارزیابی‌های مرتبط با پزشک واردشده.

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `client_id` | — | UUID مراجع |
| `doctor_id` | — | UUID (با scope پزشک جاری AND) |
| `status` | — | `pending` یا `done` |
| `search` | — | نام/تلفن مراجع |
| `sort_by` | `created_at` | `created_at`, `date`, `status`, `updated_at` |
| `sort_direction` | `desc` | — |
| `per_page` | `15` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "date": "2026-07-20",
  "time": "10:00",
  "status": "pending",
  "file_path": null,
  "file_url": null,
  "doctor": {
    "id": "uuid",
    "name": "نام",
    "phone": "0912..."
  },
  "client": {
    "id": "uuid",
    "name": "نام",
    "phone": "0912..."
  },
  "created_at": "...",
  "updated_at": "..."
}
```

> ثبت ارزیابی عمومی است (`POST /assessments`). حذف فقط از پنل ادمین.

---

## اعلان‌ها

### فهرست اعلان‌های پزشک

```http
GET /doctor/notifications
Authorization: Bearer {token}
```

همان منطق `GET /notifications` با middleware اضافی نقش پزشک.

**Query:** `search`, `type`, `priority`, `sort_by`, `sort_direction`, `per_page`, `page`

**پاسخ `200`:** pagination — ساختار `AppNotificationResource` (مشابه [راهنمای عمومی](../public/README.md#اعلان‌ها-مشترک)).

> این مسیر از نظر عملکردی مشابه `/notifications` است. برای unread و mark-as-read از endpointهای مشترک استفاده کنید.

---

## Endpointهای مشترک

پزشک علاوه بر endpointهای اختصاصی، به این موارد نیز دسترسی دارد:

| متد | مسیر | توضیح |
|-----|------|--------|
| `GET` | `/auth/me` | پروفایل جاری |
| `POST` | `/auth/logout` | خروج |
| `GET` | `/notifications` | همان فهرست اعلان‌ها |
| `GET` | `/notifications/unread` | اعلان‌های خوانده‌نشده |
| `POST` | `/notifications/{notification}/read` | علامت‌گذاری خوانده‌شده |

جزئیات در [راهنمای عمومی](../public/README.md).

---

## محدودیت‌های پنل پزشک

| عملیات | دسترسی پزشک |
|--------|-------------|
| مشاهده نوبت‌های خود | ✅ |
| ایجاد/ویرایش نوبت | ❌ (ادمین) |
| مشاهده/ویرایش رزومه خود | ✅ |
| مشاهده منابع خود | ✅ |
| ایجاد/ویرایش/حذف منابع | ✅ |
| مشاهده ارزیابی‌های خود | ✅ |
| ثبت ارزیابی | ✅ (endpoint عمومی) |
| حذف ارزیابی | ❌ (ادمین) |
| مشاهده اعلان‌ها | ✅ |
| ایجاد اعلان | ❌ (ادمین) |

---

## لینک‌های مرتبط

- [APIهای عمومی](../public/README.md)
- [پنل ادمین](../admin/README.md)
- [فهرست اصلی](../README.md)
