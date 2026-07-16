# نظام إدارة طلبات العملاء (CRM) — TTS

منصّة عربية (RTL) لإدارة طلبات وتذاكر العملاء، مبنية بـ **Laravel + Inertia + Vue 3 + MySQL**.
إعادة بناء كاملة لمشروع Lovable الأصلي بنفس الميزات والتصميم، بخط **Tajawal**.

> النطاق التجريبي: <https://crm.moathhamad.space>

---

## التقنيات

| المجال | الأداة |
| --- | --- |
| الإطار الخلفي | Laravel 13 (PHP 8.4+) |
| الواجهة | Vue 3 + Inertia.js |
| البناء | Vite 8 + Tailwind CSS v4 |
| قاعدة البيانات | MySQL 8 |
| المصادقة | Laravel Breeze + Socialite (Google OAuth) |
| الخط | Tajawal (عربي، RTL) |
| الأيقونات | lucide-vue-next |

## أبرز الميزات

- بوابات حسب الدور: عميل / موظف / مشرف / مدير نظام
- دورة حياة التذكرة الكاملة (فرز → معالجة → اعتماد → إغلاق) مع حقول ديناميكية
- محرك SLA (ساعات العمل، العطل، الإيقاف/الاستئناف، التنبيهات 75%/90%، التصعيد التلقائي)
- لوحة مقترحات (تصويت، تعليقات، RICE، اعتماد، نشر)
- المواعيد + التقويم، قاعدة المعرفة، الإجازات، التقارير، التقييمات (CSAT/NPS)
- الإشعارات (داخل التطبيق + بريد + SMS)، التكاملات (Asana / Webhooks)، وواجهة API عامة

## التشغيل محليًا

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# اضبط اتصال MySQL في .env ثم:
php artisan migrate --seed
npm run build   # أو: npm run dev

php artisan serve
```

### حسابات تجريبية (بعد `--seed`)

| الدور | البريد | كلمة المرور |
| --- | --- | --- |
| مدير النظام | admin@altqniah.sa | password |
| مشرف دعم | sup@altqniah.sa | password |
| موظف دعم | staff@altqniah.sa | password |
| عميل | customer@example.com | password |

## المهام المجدولة (Cron)

يتضمّن المشروع أوامر مجدولة عبر `schedule:run` لمحرك SLA والإغلاق التلقائي والإشعارات:

```bash
* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
```

## النشر

يُرفع الكود تلقائيًا إلى <https://github.com/muathhamad111-lgtm/crm-system>، ومنه إلى البيئة التجريبية والنطاق `crm.moathhamad.space`.

---

بُني بمساعدة Claude Code.
