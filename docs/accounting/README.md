# پنل حسابداری — API مالی

> مسیر پایه: `/api/v1`  
> دسترسی: `user.type === "admin"` (شامل `accountant`)  
> [بازگشت به فهرست اصلی](../README.md) · [جزئیات ادمین](../admin/README.md)

فاکتورها مثل نرم‌افزار حسابداری تحت‌وب مدل شده‌اند: **هدر فاکتور + اقلام در دیتابیس**. بک‌اند فایل PDF/JSON نگه نمی‌دارد؛ چاپ و UI کاملاً سمت فرانت (Next.js/React) است.

---

## مدل داده پرداخت / نوبت

### نوبت (`appointments`)

| فیلد | توضیح |
|------|--------|
| `amount` | مبلغ صورتحساب نوبت |
| `service` | نوع خدمت (اختیاری) |
| `status` | `pending` \| `done` |

### پرداخت (`payments`)

| فیلد | توضیح |
|------|--------|
| `amount` | مبلغ صورتحساب (همگام با نوبت) |
| `paid_amount` | مبلغ پرداخت‌شده |
| `status` | `pending` \| `paid` \| `unpaid` \| `partial` \| `refunded` |
| `method` | `cash` \| `card` \| `transfer` \| `other` |

قواعد: `paid` → paid_amount=amount؛ `pending/unpaid/refunded` → 0؛ `partial` → 0 < paid_amount < amount.

---

## فاکتورها (مدل حسابداری)

### جداول

**`invoices` (هدر)**

| فیلد | توضیح |
|------|--------|
| `number` | شماره یکتا (اگر ندهید ساخته می‌شود) |
| `status` | `draft` \| `issued` \| `paid` \| `cancelled` |
| `issue_date` / `due_date` | تاریخ صدور / سررسید |
| `from_date` / `to_date` | اختیاری؛ متادیتای بازه نوبت‌ها |
| `notes` | یادداشت |
| `subtotal` / `total` | از جمع اقلام محاسبه می‌شود |

**`invoice_items` (اقلام)**

| فیلد | توضیح |
|------|--------|
| `description` | شرح خدمت / کالا |
| `unit` | واحد (مثلاً جلسه) |
| `quantity` | تعداد |
| `unit_price` | قیمت فی |
| `line_total` | قیمت کل ردیف (`quantity * unit_price`) |
| `appointment_id` | اختیاری؛ اگر از نوبت آمده باشد |
| `sort_order` | ترتیب نمایش |

### Endpointها

```http
GET    /invoices
POST   /invoices
GET    /invoices/{invoice}
PUT    /invoices/{invoice}
PATCH  /invoices/{invoice}
DELETE /invoices/{invoice}
POST   /invoices/suggest-items
```

#### پیشنهاد اقلام از نوبت‌ها (بدون ذخیره فاکتور)

```http
POST /invoices/suggest-items
```

```json
{
  "client_id": "uuid",
  "from_date": "2026-07-01",
  "to_date": "2026-07-31"
}
```

پاسخ: لیست پیشنهادی اقلام + `subtotal`. فرانت می‌تواند ویرایش/حذف/افزودن دستی کند و بعد با `POST /invoices` ذخیره کند.

#### ایجاد فاکتور

```json
{
  "client_id": "uuid",
  "issue_date": "2026-08-06",
  "due_date": "2026-08-20",
  "status": "issued",
  "from_date": "2026-07-01",
  "to_date": "2026-07-31",
  "notes": "اختیاری",
  "items": [
    {
      "description": "مشاوره فردی - دکتر محرابی",
      "unit": "جلسه",
      "quantity": 1,
      "unit_price": 500000,
      "appointment_id": "uuid-optional"
    },
    {
      "description": "هزینه ارزیابی",
      "unit": "عدد",
      "quantity": 1,
      "unit_price": 200000
    }
  ]
}
```

### جریان پیشنهادی فرانت

1. کاربر مراجع + بازه را انتخاب می‌کند  
2. `POST /invoices/suggest-items` → پر کردن جدول UI  
3. کاربر اقلام را ویرایش یا دستی اضافه می‌کند  
4. `POST /invoices` → ذخیره در دیتابیس  
5. چاپ / PDF فقط در Next.js/React از روی داده API

---

## سایر APIهای مالی

| مسیر | کار |
|------|-----|
| `GET /payments` | لیست پرداخت‌ها |
| `GET /payment-transactions` | لاگ تغییرات پرداخت |
| `GET /finance/summary` | خلاصه KPI |
| `GET /finance/reports/*` | گزارش پزشک / روز / مقایسه |
| `CRUD /financial-adjustments` | تخفیف / بستانکار / بدهکار (`invoice_id` اختیاری) |

---

## نگاشت سایدبار

| صفحه | API |
|------|-----|
| داشبورد | `/finance/summary` + reports |
| پرداخت‌ها | `/payments` |
| درآمد نوبت‌ها | `/appointments` |
| صورتحساب‌ها | `/invoices` + `/invoices/suggest-items` |
| تخفیف‌ها و تعدیلات | `/financial-adjustments` |
| لاگ تراکنش‌ها | `/payment-transactions` |
| خروجی / چاپ | سمت فرانت |
