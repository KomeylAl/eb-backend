# APIهای عمومی و مشترک

> مسیر پایه: `/api/v1`  
> [بازگشت به فهرست اصلی](../README.md)

این سند endpointهای بدون احراز هویت و endpointهای مشترک بین همه نقش‌های واردشده را پوشش می‌دهد.

---

## فهرست

- [احراز هویت](#احراز-هویت)
- [درباره ما](#درباره-ما)
- [دپارتمان‌ها](#دپارتمان‌ها)
- [پزشکان (نمایش عمومی)](#پزشکان-نمایش-عمومی)
- [دسته‌بندی‌ها](#دسته‌بندی‌ها)
- [تگ‌ها](#تگ‌ها)
- [پست‌ها](#پست‌ها)
- [کارگاه‌ها](#کارگاه‌ها)
- [ثبت ارزیابی اولیه](#ثبت-ارزیابی-اولیه)
- [ثبت کامنت](#ثبت-کامنت)
- [اعلان‌ها (مشترک)](#اعلان‌ها-مشترک)

---

## احراز هویت

### ورود

```http
POST /auth/login
Content-Type: application/json
```

**بدنه:**

| فیلد | نوع | الزامی | توضیح |
|------|-----|--------|--------|
| `phone` | string | بله | حداکثر ۲۰ کاراکتر |
| `password` | string | بله | — |
| `type` | string | خیر | یکی از `admin`, `doctor`, `client` |

**نمونه درخواست:**

```json
{
  "phone": "09123456789",
  "password": "password123",
  "type": "doctor"
}
```

**پاسخ `200`:**

```json
{
  "message": "Logged in successfully.",
  "data": {
    "user": {
      "id": "uuid",
      "name": "نام",
      "phone": "09123456789",
      "email": null,
      "birth_date": null,
      "address": null,
      "type": "doctor",
      "admin_role": null,
      "doctor_profile": {
        "id": "uuid",
        "national_code": "1234567890",
        "card_number": null,
        "medical_number": null,
        "avatar": null,
        "avatar_url": null,
        "days": [],
        "times": [],
        "profile_path": null,
        "sort_order": 0
      },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    },
    "token": "plain-text-token",
    "token_type": "Bearer"
  }
}
```

**خطای ورود `422`:**

```json
{
  "message": "اطلاعات ورود اشتباه است.",
  "errors": {
    "phone": ["اطلاعات ورود اشتباه است."]
  }
}
```

---

### پروفایل جاری

```http
GET /auth/me
Authorization: Bearer {token}
```

**پاسخ `200`:** همان ساختار `user` در پاسخ login، داخل `data`.

---

### خروج

```http
POST /auth/logout
Authorization: Bearer {token}
```

**پاسخ `200`:**

```json
{
  "message": "Logged out successfully.",
  "data": null
}
```

فقط توکن جاری حذف می‌شود.

---

## درباره ما

### دریافت اطلاعات

```http
GET /about
```

**پاسخ `200` — `data`:**

| فیلد | نوع | توضیح |
|------|-----|--------|
| `id` | uuid | — |
| `title` | string | — |
| `about` | string | متن درباره ما |
| `phones` | string | رشته، نه آرایه |
| `mobile_phones` | string | رشته، نه آرایه |
| `address` | string\|null | — |
| `logo` | string\|null | مسیر storage |
| `logo_url` | string\|null | URL عمومی |
| `latitude` | string\|null | — |
| `longitude` | string\|null | — |
| `created_at` | datetime | — |
| `updated_at` | datetime | — |

**اگر رکورد وجود نداشته باشد `404`:**

```json
{
  "message": "About information not found.",
  "errors": null
}
```

---

## دپارتمان‌ها

### فهرست

```http
GET /departments
```

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `search` | — | جستجو در `title`, `excerpt`, `slug` |
| `sort_by` | `created_at` | — |
| `sort_direction` | `desc` | فقط `asc` صعودی |
| `per_page` | `10` | ۱ تا ۱۰۰ |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "title": "عنوان",
  "slug": "slug",
  "excerpt": "خلاصه",
  "thumbnail": "path",
  "thumbnail_url": "https://...",
  "content": "محتوا",
  "created_at": "...",
  "updated_at": "..."
}
```

### جزئیات

```http
GET /departments/{department}
```

**پاسخ `200`:** یک `DepartmentResource`  
**UUID ناموجود:** `404`

---

## پزشکان (نمایش عمومی)

> مقدار فنی نقش: `doctor` (پزشک / تراپیست)

### فهرست

```http
GET /doctors
```

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `search` | — | نام، تلفن، ایمیل |
| `sort_by` | `sort_order` | — |
| `sort_direction` | `asc` برای sort_order، `desc` برای بقیه | — |
| `per_page` | `10` | ۱ تا ۱۰۰ |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "name": "نام",
  "phone": "0912...",
  "email": null,
  "birth_date": null,
  "type": "doctor",
  "doctor_profile": {
    "id": "uuid",
    "national_code": "...",
    "card_number": null,
    "medical_number": null,
    "avatar": null,
    "avatar_url": null,
    "days": [],
    "times": [],
    "profile_path": null,
    "sort_order": 0
  },
  "departments": [
    {
      "id": "uuid",
      "title": "دپارتمان",
      "slug": "slug",
      "excerpt": null,
      "thumbnail": null,
      "thumbnail_url": null,
      "content": "محتوا",
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "created_at": "...",
  "updated_at": "..."
}
```

> در فهرست، `resume` و `doctor_resources` بارگذاری نمی‌شوند.

### جزئیات

```http
GET /doctors/{doctor}
```

**پاسخ `200`:** علاوه بر فیلدهای فهرست:

- `resume`: رزومه پزشک (در صورت وجود)
- `doctor_resources[]`: منابع پزشک

**ساختار `resume`:**

```json
{
  "id": "uuid",
  "doctor_id": "uuid",
  "title": null,
  "bio": null,
  "specialization": null,
  "educations": [],
  "experiences": [],
  "skills": [],
  "certifications": [],
  "social_links": [],
  "content": null,
  "file_path": null,
  "file_url": null,
  "created_at": "...",
  "updated_at": "..."
}
```

**ساختار هر `doctor_resource`:**

```json
{
  "id": "uuid",
  "doctor_id": "uuid",
  "title": "عنوان",
  "type": "link|file",
  "description": null,
  "link": null,
  "file_path": null,
  "file_url": null,
  "created_at": "...",
  "updated_at": "..."
}
```

> برای نمایش عمومی کافی است `GET /doctors/{doctor}` را بگیرید و آرایه `doctor_resources` را رندر کنید.
> اگر `type === "link"` از فیلد `link` استفاده کنید؛ اگر `type === "file"` از `file_url` برای دانلود/باز کردن فایل استفاده کنید.
> مدیریت (ایجاد/ویرایش/حذف) فقط از پنل پزشک یا ادمین انجام می‌شود؛ endpoint عمومی فقط خواندنی است.

---

## دسته‌بندی‌ها

### فهرست

```http
GET /categories
```

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `search` | — | `name`, `slug`, `excerpt` |
| `sort_by` | `created_at` | — |
| `sort_direction` | `desc` | — |
| `per_page` | `15` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "name": "نام",
  "slug": "slug",
  "excerpt": null,
  "content": null,
  "image": null,
  "image_url": null,
  "posts_count": 5,
  "created_at": "...",
  "updated_at": "..."
}
```

### جزئیات

```http
GET /categories/{category}
```

---

## تگ‌ها

ساختار و query parameters مشابه [دسته‌بندی‌ها](#دسته‌بندی‌ها) است.

```http
GET /tags
GET /tags/{tag}
```

---

## پست‌ها

### فهرست

```http
GET /posts
```

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `search` | — | `title`, `excerpt` |
| `category_id` | — | UUID دسته‌بندی |
| `tag_ids[]` | — | فیلتر OR (حداقل یکی از تگ‌ها) |
| `status` | — | `draft`, `published`, `archived` |
| `sort_by` | `created_at` | — |
| `sort_direction` | `desc` | — |
| `per_page` | `15` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "author_id": "uuid",
  "category_id": "uuid",
  "title": "عنوان",
  "slug": "slug",
  "excerpt": "خلاصه",
  "content": "محتوا",
  "thumbnail": null,
  "thumbnail_url": null,
  "status": "published",
  "published_at": null,
  "author": { "id": "...", "name": "...", "phone": "...", "type": "admin", "..." : "..." },
  "category": { "id": "...", "name": "...", "..." : "..." },
  "tags": [],
  "comments_count": 3,
  "created_at": "...",
  "updated_at": "..."
}
```

> **توجه:** endpoint عمومی به‌طور پیش‌فرض فقط `published` را فیلتر نمی‌کند. برای نمایش سایت، `status=published` را در query ارسال کنید.

### جزئیات

```http
GET /posts/{post}
```

**پاسخ `200`:** علاوه بر فیلدهای فهرست، آرایه `comments[]`:

```json
{
  "id": "uuid",
  "post_id": "uuid",
  "user_id": null,
  "body": "متن کامنت",
  "author_name": "نام",
  "email": "email@example.com",
  "approved": false,
  "created_at": "...",
  "updated_at": "..."
}
```

> در نمایش عمومی، کامنت‌های تأییدنشده نیز برگردانده می‌شوند. برای UI سایت فقط `approved: true` را نمایش دهید.

---

## کارگاه‌ها

### فهرست

```http
GET /workshops
```

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `search` | — | `title`, `slug`, `excerpt` |
| `start_date` | — | کارگاه‌هایی با `start_date >= value` |
| `sort_by` | `created_at` | — |
| `sort_direction` | `desc` | — |
| `per_page` | `15` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "title": "عنوان",
  "slug": "slug",
  "excerpt": null,
  "content": null,
  "organizers": null,
  "start_date": "2026-01-01",
  "end_date": null,
  "week_day": null,
  "time": null,
  "img_path": null,
  "image_url": null,
  "created_at": "...",
  "updated_at": "..."
}
```

### جزئیات

```http
GET /workshops/{workshop}
```

**پاسخ `200`:** علاوه بر فیلدهای فهرست:

**`sessions[]`:**

```json
{
  "id": "uuid",
  "workshop_id": "uuid",
  "title": "جلسه",
  "description": null,
  "session_date": "2026-01-01",
  "start_time": "10:00",
  "end_time": "12:00",
  "location": null,
  "link": null,
  "created_at": "...",
  "updated_at": "..."
}
```

**`participants[]`:**

```json
{
  "id": "uuid",
  "name": "نام",
  "english_name": null,
  "phone": "0912...",
  "national_code": null,
  "gender": "male|female|other",
  "approved": false,
  "registered_at": "...",
  "joined_at": null,
  "created_at": "...",
  "updated_at": "..."
}
```

> اطلاعات شرکت‌کنندگان در endpoint عمومی قابل مشاهده است. در UI سایت فقط داده‌های لازم را نمایش دهید.

---

## ثبت ارزیابی اولیه

```http
POST /assessments
Content-Type: application/json
```

**بدنه:**

| فیلد | نوع | الزامی | توضیح |
|------|-----|--------|--------|
| `date` | date | خیر | `YYYY-MM-DD` |
| `time` | string | خیر | حداکثر ۵۰ کاراکتر |
| `status` | string | **بله** | `pending` یا `done` |
| `doctor_id` | uuid | خیر | UUID کاربر موجود |
| `client` | object | **بله** | اطلاعات مراجع |
| `client.name` | string | **بله** | حداکثر ۲۵۵ |
| `client.phone` | string | **بله** | حداکثر ۲۰ |
| `client.birth_date` | date | خیر | — |
| `client.address` | string | خیر | حداکثر ۵۰۰ |

**نمونه درخواست:**

```json
{
  "date": "2026-07-20",
  "time": "10:00",
  "status": "pending",
  "doctor_id": "uuid-optional",
  "client": {
    "name": "نام مراجع",
    "phone": "09121234567",
    "birth_date": "1990-01-01",
    "address": "آدرس"
  }
}
```

**پاسخ `201`:**

```json
{
  "message": "Assessment registered successfully.",
  "data": {
    "id": "uuid",
    "date": "2026-07-20",
    "time": "10:00",
    "status": "pending",
    "file_path": null,
    "file_url": null,
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
    "created_at": "...",
    "updated_at": "..."
  }
}
```

**نکات:**

- اگر مراجع با همان تلفن از قبل وجود داشته باشد، همان کاربر استفاده می‌شود.
- `doctor_id` فقط وجود user را بررسی می‌کند، نه نوع `doctor`.

---

## ثبت کامنت

```http
POST /comments
Content-Type: application/json
```

**بدنه:**

| فیلد | نوع | الزامی | توضیح |
|------|-----|--------|--------|
| `post_id` | uuid | **بله** | UUID پست موجود |
| `body` | string | **بله** | متن کامنت |
| `author_name` | string | خیر | حداکثر ۲۵۵ |
| `email` | email | خیر | حداکثر ۲۵۵ |
| `approved` | boolean | خیر | پیش‌فرض: `false` |

**نمونه درخواست:**

```json
{
  "post_id": "uuid",
  "body": "کامنت من",
  "author_name": "کاربر",
  "email": "user@example.com"
}
```

**پاسخ `201`:**

```json
{
  "message": "Comment created successfully.",
  "data": {
    "id": "uuid",
    "post_id": "uuid",
    "user_id": null,
    "body": "کامنت من",
    "author_name": "کاربر",
    "email": "user@example.com",
    "approved": false,
    "user": null,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

> اگر کاربر واردشده باشد، `user_id` از توکن گرفته می‌شود.

---

## اعلان‌ها (مشترک)

برای همه نقش‌های واردشده (`admin`, `doctor`, `client`).

```http
Authorization: Bearer {token}
```

### فهرست اعلان‌ها

```http
GET /notifications
```

**Query:**

| پارامتر | پیش‌فرض | توضیح |
|---------|---------|--------|
| `search` | — | `title`, `message` |
| `type` | — | — |
| `priority` | — | `low`, `normal`, `medium`, `high` |
| `sort_by` | `created_at` | — |
| `sort_direction` | `desc` | — |
| `per_page` | `20` | — |
| `page` | `1` | — |

**پاسخ `200`:** pagination — هر item:

```json
{
  "id": "uuid",
  "title": "عنوان",
  "message": "متن",
  "type": "system",
  "notifiable_type": null,
  "notifiable_id": null,
  "priority": "normal",
  "delivery_channels": ["in_app"],
  "meta": {},
  "status": "active",
  "scheduled_at": null,
  "created_at": "...",
  "updated_at": "..."
}
```

**دامنه اعلان‌های قابل مشاهده:**

1. اعلان مستقیم برای کاربر جاری
2. اعلان گروهی با `meta.user_type` برابر نقش کاربر
3. اعلان عمومی با `notifiable_type: null`

> پاسخ فیلد `is_read` ندارد. برای تشخیص خوانده‌شده از endpoint unread استفاده کنید.

### اعلان‌های خوانده‌نشده

```http
GET /notifications/unread
```

**Query:** `per_page` (پیش‌فرض ۲۰), `page`

**پاسخ `200`:** همان ساختار pagination.

### علامت‌گذاری به‌عنوان خوانده‌شده

```http
POST /notifications/{notification}/read
```

بدون body.

**پاسخ `200`:**

```json
{
  "message": "Notification marked as read.",
  "data": null
}
```

عملیات idempotent است (تکرار مشکلی ایجاد نمی‌کند).

---

## لینک‌های مرتبط

- [پنل پزشک / تراپیست](../doctor/README.md)
- [پنل ادمین](../admin/README.md)
- [فهرست اصلی](../README.md)
