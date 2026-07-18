# Ebraz New Backend

بازنویسی تمیز پروژه کلینیک/آموزشی Ebraz روی **Laravel 13 + PostgreSQL + Sanctum + UUID**.

منبع رفتار کسب‌وکار: پوشه خواهر `ebraz-backend` (نسخه قدیمی). هدف، parity رفتاری است نه کپی ۱:۱ مسیرها.

## معماری

```
app/
  Enums/           UserType, AdminRole, ...
  Models/          User, DoctorProfile, Appointment, ...
  Http/
    Controllers/Api/V1/
    Requests/
    Resources/
    Middleware/
  Actions/         منطق use-case
  Services/        SmsService و یکپارچه‌سازی‌های خارجی
routes/api.php     نسخه /api/v1
```

### اصول ثابت

- UUID برای همه primary/foreign keyهای دامنه
- PostgreSQL
- احراز هویت: Laravel Sanctum (Bearer token)
- یک جدول `users` با `type` = `admin|doctor|client`
- زیر‌نقش ادمین: `admin_role` = `boss|receptionist|manager|author|accountant`
- پروفایل پزشک: جدول `doctor_profiles`
- Form Request + API Resource + Action
- پاسخ JSON یکدست: `{ message, data, errors? }`

### Auth سریع

```http
POST /api/v1/auth/login
{ "phone": "09000000000", "password": "password", "type": "admin" }

Authorization: Bearer {token}
```

Seeder پیش‌فرض: تلفن `09000000000` / رمز `password` / نقش `boss`.

## راه‌اندازی

```bash
composer install
cp .env.example .env
php artisan key:generate
# تنظیم DB_* برای PostgreSQL
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

متغیرهای SMS (اختیاری):

```
SMS_IR_API_KEY=
SMS_IR_LINE_NUMBER=
```

## نقشه فازها

بعد از اتمام هر فاز، تیک بزنید.

### فاز 0 — پی‌ریزی پروژه

- [x] نصب Sanctum و ثبت `routes/api.php`
- [x] Enumهای پایه (`UserType`, `AdminRole`, ...)
- [x] مهاجرت `users` (UUID) + `doctor_profiles` + `personal_access_tokens`
- [x] اسکلت پوشه‌ها، middleware، `ApiResponse`
- [x] README و قوانین معماری

### فاز 1 — Auth و مدیریت کاربران

- [x] Login / Logout / Me (Sanctum)
- [x] CRUD ادمین‌ها با `admin_role`
- [x] Middleware `user.type` و `admin.role`
- [x] Seeder ادمین اولیه

### فاز 2 — هسته کلینیک

- [x] Doctors (+ profile، departments، resume، resources)
- [x] Clients
- [x] Appointments (+ payment pivot)
- [x] Departments
- [x] پنل پزشک: بازه‌های نوبت

### فاز 3 — پرونده پزشکی و ارزیابی

- [x] Medical records + companions + record images
- [x] Init assessments (عمومی + ادمین)

### فاز 4 — کارگاه‌ها و کلاس‌ها

- [x] Workshops / sessions / participants (approve)
- [x] Classes (CourseClass)

### فاز 5 — وبلاگ / CMS

- [x] Categories / Tags / Posts / Comments
- [x] دسترسی نوشتن برای `author|boss|manager`

### فاز 6 — اعلان، پیامک، مالی

- [x] Notifications + mark as read
- [x] SmsService + endpoints
- [x] Payments / Invoices

### فاز 7 — ابزارها و جمع‌بندی

- [x] About
- [x] Backup / Restore
- [x] تست‌های Feature پایه
- [x] مستند API خلاصه در همین README

## API خلاصه (`/api/v1`)

| حوزه | مسیرهای مهم |
|------|-------------|
| Auth | `POST auth/login`, `POST auth/logout`, `GET auth/me` |
| Admins | `CRUD /admins` |
| Clients | `CRUD /clients` |
| Doctors | `CRUD /doctors`, `POST /doctors/{id}/password`, appointment ranges |
| Appointments | `CRUD /appointments` |
| Departments | public index/show + admin mutate |
| Medical | `/clients/{id}/medical-record` |
| Assessments | `POST /assessments`, admin index/delete |
| Workshops | CRUD + nested sessions/participants |
| Classes | `CRUD /classes` |
| Blog | public read + author mutate |
| Notifications | index/unread/read + admin store |
| SMS | `POST /sms/single`, `POST /sms/multi` |
| Finance | `/payments`, `/invoices`, `POST /invoices/generate` |
| About | `GET /about`, `POST /about` |
| Backup | `GET /backup/{type}` |
| Restore | `POST /restore/{type}` |

## تست

```bash
php artisan test
```
