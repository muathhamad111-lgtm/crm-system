# دليل النشر — crm.moathhamad.space

نشر **Laravel 13 + Inertia + Vue 3 + MySQL** على خادم Ubuntu (VPS)، مع نشر تلقائي عند كل دفعة إلى `main` عبر GitHub Actions.

---

## نظرة عامة على المسار

```
git push → GitHub (main) → GitHub Actions → SSH إلى الخادم → deploy/deploy.sh
                                            (git pull + composer + build + migrate + cache)
```

## المتطلبات على الخادم
- Ubuntu 22.04/24.04، وصول root/sudo
- نطاق `crm.moathhamad.space` مُوجّه (سجل A) إلى IP الخادم

## أولاً — التهيئة لمرة واحدة

```bash
# على الخادم
sudo bash                     # ثم استنسخ المستودع مؤقتًا لتشغيل سكربت التهيئة، أو نفّذ يدويًا
git clone https://github.com/muathhamad111-lgtm/crm-system.git /var/www/crm-system
cd /var/www/crm-system
sudo bash deploy/setup-server.sh    # يثبّت PHP 8.4 / Composer / Node 22 / MySQL / Nginx / Certbot
```

### قاعدة البيانات
```sql
CREATE DATABASE crm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'كلمة-مرور-قوية';
GRANT ALL PRIVILEGES ON crm_system.* TO 'crm_user'@'localhost';
FLUSH PRIVILEGES;
```

### البيئة
```bash
cp .env.production.example .env
# عدّل DB_PASSWORD و CRON_SECRET و(اختياريًا) مفاتيح Google OAuth
php artisan key:generate
```

### أول نشر
```bash
bash deploy/deploy.sh          # composer + build + migrate + storage:link + caches
php artisan db:seed --force    # (أول مرة فقط) بيانات التصنيفات/الصلاحيات المرجعية
```

### Nginx + شهادة SSL
```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/crm.moathhamad.space
sudo ln -s /etc/nginx/sites-available/crm.moathhamad.space /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d crm.moathhamad.space     # يضيف كتلة 443 + التجديد التلقائي
```

### الطابور والمجدول (SLA / الإغلاق التلقائي / التصعيد / الإشعارات)
```bash
sudo cp deploy/crm-worker.service /etc/systemd/system/
sudo systemctl enable --now crm-worker           # queue:work
crontab -e   # أضف السطر التالي (المجدول يشغّل crm:sla-scan / crm:auto-close / crm:auto-escalate):
* * * * * cd /var/www/crm-system && php artisan schedule:run >> /dev/null 2>&1
```

### الصلاحيات
```bash
sudo chown -R www-data:www-data /var/www/crm-system
sudo chmod -R 775 storage bootstrap/cache
```

---

## ثانيًا — النشر التلقائي (GitHub Actions)

في المستودع: **Settings → Secrets and variables → Actions** أضف:

| السر | المثال |
| --- | --- |
| `SSH_HOST` | IP الخادم |
| `SSH_USER` | `deploy` (مستخدم له وصول لمجلد التطبيق) |
| `SSH_PORT` | `22` (اختياري) |
| `SSH_PRIVATE_KEY` | المفتاح الخاص (PEM) والعام منه في `~/.ssh/authorized_keys` على الخادم |
| `APP_DIR` | `/var/www/crm-system` |

بعدها كل `git push` إلى `main` يشغّل `.github/workflows/deploy.yml` تلقائيًا فيتصل بالخادم وينفّذ `deploy/deploy.sh` — دون طلب موافقات.

> **تنبيه**: يحتاج مستخدم النشر أن يكون قادرًا على تشغيل `composer`/`npm`/`php artisan migrate` وإعادة تشغيل الطابور (`php artisan queue:restart`). امنحه sudo محدودًا لإعادة تشغيل `crm-worker` عند الحاجة، أو استخدم `queue:restart` (المستخدم في السكربت) الذي لا يحتاج sudo.

---

## Google OAuth (اختياري)
في Google Cloud Console → OAuth Client، أضف:
- Authorized redirect URI: `https://crm.moathhamad.space/auth/google/callback`
ثم املأ `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` في `.env` وأعد `php artisan config:cache`.

## استكشاف الأخطاء
- صفحة 500 بعد النشر: `php artisan optimize:clear` ثم راجع `storage/logs/laravel.log`
- تعذّر رفع المرفقات: تأكّد من `php artisan storage:link` وصلاحيات `storage/`
- المجدول لا يعمل: تحقّق من `crontab -l` ومن `php artisan schedule:list`
