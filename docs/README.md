# مستندات API — پروژه ابراز

راهنمای کامل API برای تیم فرانت‌اند. تمام endpointها تحت پیشوند زیر قرار دارند:

```
Base URL: /api/v1
```

مثال در محیط توسعه:

```
http://localhost:8000/api/v1
```

---

## فهرست راهنماها

| راهنما | مخاطب | توضیح |
|--------|--------|--------|
| [APIهای عمومی و مشترک](./public/README.md) | سایت عمومی، همه کاربران | ورود، محتوای عمومی، ثبت ارزیابی و کامنت، اعلان‌های مشترک |
| [پنل پزشک / تراپیست](./doctor/README.md) | پزشک (`type: doctor`) | نوبت‌ها، رزومه، منابع، ارزیابی‌ها |
| [پنل ادمین](./admin/README.md) | ادمین (`type: admin`) | مدیریت کاربران، نوبت‌ها، کارگاه‌ها، مالی، پشتیبان‌گیری و ... |

---

## نقش‌های کاربری

| نقش | مقدار فنی `type` | توضیح |
|-----|------------------|--------|
| ادمین | `admin` | پنل مدیریت؛ زیرنقش‌ها در فیلد `admin_role` |
| پزشک / تراپیست | `doctor` | پنل پزشک؛ در کد مقدار `doctor` است |
| مراجع | `client` | فقط endpointهای مشترک authenticated |

### زیرنقش‌های ادمین (`admin_role`)

| مقدار | توضیح |
|-------|--------|
| `boss` | مدیر کل |
| `manager` | مدیر |
| `author` | نویسنده محتوا |
| `receptionist` | پذیرش |
| `accountant` | حسابدار |

> **توجه:** ایجاد/ویرایش/حذف دسته‌بندی، تگ و پست فقط برای `author`، `boss` و `manager` مجاز است. سایر APIهای ادمین برای همه زیرنقش‌ها باز است.

---

## احراز هویت (Sanctum)

### ورود

```http
POST /api/v1/auth/login
Content-Type: application/json
Accept: application/json
```

```json
{
  "phone": "09123456789",
  "password": "password123",
  "type": "admin"
}
```

فیلد `type` اختیاری است؛ در صورت ارسال، کاربر با همان نقش جستجو می‌شود.

### استفاده از توکن

برای endpointهای محافظت‌شده:

```http
Authorization: Bearer {token}
Accept: application/json
```

### خروج و پروفایل

| متد | مسیر | دسترسی |
|-----|------|--------|
| `GET` | `/auth/me` | هر کاربر واردشده |
| `POST` | `/auth/logout` | هر کاربر واردشده |

جزئیات در [راهنمای عمومی](./public/README.md#احراز-هویت).

---

## قالب پاسخ‌ها

### پاسخ موفق

```json
{
  "message": "Success",
  "data": {}
}
```

### ایجاد موفق

وضعیت `201` با همان ساختار.

### حذف موفق

وضعیت `204` بدون body.

### خطای API

```json
{
  "message": "پیام خطا",
  "errors": null
}
```

### خطای اعتبارسنجی

وضعیت `422`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["پیام خطا"]
  }
}
```

### کدهای وضعیت رایج

| کد | معنی |
|----|------|
| `200` | موفق |
| `201` | ایجاد شد |
| `204` | حذف شد (بدون body) |
| `401` | بدون احراز هویت |
| `403` | دسترسی ممنوع |
| `404` | یافت نشد |
| `422` | خطای validation |
| `502` | خطای سرویس خارجی (مثلاً SMS) |

---

## صفحه‌بندی (Pagination)

لیست‌های صفحه‌بندی‌شده:

```json
{
  "message": "Success",
  "data": {
    "items": [],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 0
    }
  }
}
```

پارامتر `page` در query string پشتیبانی می‌شود.

---

## شناسه‌ها (UUID)

تمام شناسه‌های مدل‌ها از نوع UUID هستند. در مسیرها از `{id}` استفاده می‌شود، مثلاً:

```
GET /api/v1/posts/{post}
GET /api/v1/doctors/{doctor}
```

---

## آپلود فایل

برای endpointهای دارای فایل از `multipart/form-data` استفاده کنید.

- فیلدهای آرایه‌ای (`days[]`, `tag_ids[]`, ...) را می‌توان به‌صورت آرایه یا JSON string معتبر ارسال کرد.
- اگر `PUT`/`PATCH` با multipart در کلاینت مشکل داشت، از `POST` همراه `_method=PUT` یا `_method=PATCH` استفاده کنید.
- برای نمایش فایل‌ها از فیلدهای `*_url` (مثل `thumbnail_url`, `avatar_url`, `file_url`) استفاده کنید، نه مسیر داخلی storage.

---

## نکات مهم برای فرانت‌اند

1. همیشه `Accept: application/json` ارسال شود.
2. `PATCH` در برخی endpointها واقعاً partial نیست و فیلدهای الزامی کامل می‌خواهد.
3. برخی `GET`ها در نبود داده `200` با `data: null` برمی‌گردانند، نه `404`.
4. endpointهای backup با متد `GET` فایل می‌سازند؛ از prefetch/cache خودکار پرهیز کنید.
5. timezone برنامه `UTC` است؛ بازه‌های تاریخی نوبت بر همین اساس محاسبه می‌شوند.

---

## نگاشت سریع endpointها

### عمومی (بدون توکن)

`about`, `departments`, `doctors`, `categories`, `tags`, `posts`, `workshops`, `POST assessments`, `POST comments`, `POST auth/login`

### مشترک (با توکن — همه نقش‌ها)

`auth/me`, `auth/logout`, `notifications`, `notifications/unread`, `notifications/{id}/read`

### پزشک (`type: doctor`)

`doctor/appointments`, `doctor/resume`, `doctor/resources`, `doctor/assessments`, `doctor/notifications`

### ادمین (`type: admin`)

تمام endpointهای مدیریتی — جزئیات در [راهنمای ادمین](./admin/README.md).
