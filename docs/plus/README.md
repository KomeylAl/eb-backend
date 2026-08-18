# ابراز پلاس (شرکت‌کنندگان)

پنل شرکت‌کنندگان تأییدشدهٔ کارگاه/سمینار/وبینار.

**احراز هویت:** Sanctum روی مدل `Participant`  
**ورود:** شماره موبایل + کد ملی (به‌عنوان رمز)  
**شرط ورود:** حداقل یک کارگاه با `approved=true`

```
Base: /api/v1/plus
```

فرانت: اپ `ebraz-client` (پورت توسعه `3003`)

---

## ورود / خروج

```http
POST /plus/login
POST /plus/logout
GET  /plus/me
```

### Login body

```json
{
  "phone": "09121234567",
  "national_code": "0012345678"
}
```

**پاسخ `200`:** `{ participant, token, token_type }`

خطاهای رایج `422`:
- اطلاعات اشتباه
- هنوز در هیچ کارگاهی تأیید نشده

`logout` و بقیه endpointها نیاز به `Authorization: Bearer {token}` دارند.

---

## کارگاه‌ها

```http
GET /plus/workshops
GET /plus/workshops/{workshop}
```

فقط کارگاه‌هایی که شرکت‌کننده در آن‌ها **تأیید** شده است.

---

## منابع

```http
GET /plus/workshops/{workshop}/materials
GET /plus/workshops/{workshop}/materials/{material}/download
```

فایل‌ها خصوصی‌اند؛ دانلود فقط با توکن شرکت‌کنندهٔ تأییدشده. مسیر ذخیره‌سازی (`file_path`) در پاسخ لیست برنمی‌گردد.

---

## گواهی

```http
GET /plus/workshops/{workshop}/certificates
GET /plus/workshops/{workshop}/certificates/{certificate}/download
GET /plus/certificates
```

- اگر `has_file=true` باشد، دانلود از endpoint فایل آپلودشده
- در غیر این صورت `payload` برای ساخت PDF داینامیک در فرانت

---

## نکات

- ادمین توکن نمی‌تواند به `/plus/*` دسترسی بگیرد (`EnsureParticipant`).
- مدرک می‌تواند **داینامیک** یا **فایل آپلودشده** باشد.