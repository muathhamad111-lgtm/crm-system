<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Request as CrmRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo tickets so the inbox/dashboard/reports look populated for review.
 * Idempotent: skips if demo requests already exist. Safe to delete later:
 *   DELETE FROM requests WHERE source = 'demo';
 */
class DemoRequestsSeeder extends Seeder
{
    public function run(): void
    {
        if (CrmRequest::where('source', 'demo')->exists()) {
            $this->command?->info('Demo requests already present — skipping.');

            return;
        }

        $now = Carbon::now();

        // A few demo customers.
        $customers = [
            ['name' => 'مؤسسة رِكرام التجارية', 'email' => 'rekram@example.com'],
            ['name' => 'جمعية الدعوة بيدمة', 'email' => 'daawa@example.com'],
            ['name' => 'شركة آفاق التقنية', 'email' => 'afaq@example.com'],
            ['name' => 'مركز ثقة الأعمال', 'email' => 'thouq@example.com'],
        ];
        $customerIds = [];
        foreach ($customers as $c) {
            $id = DB::table('users')->where('email', $c['email'])->value('id') ?? (string) Str::uuid();
            DB::table('users')->updateOrInsert(['email' => $c['email']], [
                'id' => $id, 'name' => $c['name'], 'password' => Hash::make(Str::random(40)),
                'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('profiles')->updateOrInsert(['email' => $c['email']], [
                'id' => $id, 'full_name' => $c['name'], 'account_status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('user_roles')->updateOrInsert(['user_id' => $id, 'role' => 'customer'],
                ['id' => (string) Str::uuid(), 'created_at' => $now]);
            $customerIds[] = $id;
        }

        $staffIds = DB::table('users')->whereIn('email', ['staff@altqniah.sa', 'sup@altqniah.sa'])->pluck('id')->all();
        $categories = Category::where('active', true)->where('is_suggestion', false)->get();
        $products = DB::table('products')->where('active', true)->pluck('id')->all();

        $titles = [
            'ربط الايميل الخاص بالمنصة', 'اريد عمل استضافة جديدة', 'كيف اربط الموقع بالنطاق',
            'اضافة رابط لصفحة الدفع', 'عدم عمل زر الإرسال', 'الاطلاع على القوائم المالية',
            'مشكلة في ادخال رقم الجوال', 'طلب تقرير تبرعات شهري', 'بطء في تحميل لوحة التحكم',
            'تعديل بيانات الحساب البنكي', 'استفسار عن باقات الاشتراك', 'طلب تدريب لمدير النظام',
            'خطأ عند رفع المرفقات', 'دمج حسابين لنفس العميل', 'تصدير بيانات العملاء',
        ];
        $statuses = ['new', 'under_review', 'in_progress', 'awaiting_customer', 'escalated', 'completed', 'closed', 'reopened'];
        $priorities = ['urgent', 'high', 'medium', 'low'];

        foreach ($titles as $i => $title) {
            $cat = $categories[$i % $categories->count()];
            $status = $statuses[$i % count($statuses)];
            $createdAt = $now->copy()->subDays(rand(0, 20))->subHours(rand(0, 20));

            $req = CrmRequest::create([
                'request_number' => $this->number($cat, 1001 + $i),
                'title' => $title,
                'description' => 'وصف تجريبي للطلب لغرض عرض التصميم والمراجعة. يحتوي على تفاصيل كافية لتوضيح شكل التذكرة.',
                'category_id' => $cat->id,
                'product_id' => $products ? $products[$i % count($products)] : null,
                'customer_id' => $customerIds[$i % count($customerIds)],
                'status' => $status,
                'priority' => $priorities[$i % count($priorities)],
                'channel' => 'portal',
                'source' => 'demo',
                'assigned_to' => $staffIds ? $staffIds[$i % count($staffIds)] : null,
                'assigned_team' => $cat->target_team?->value ?? $cat->target_team,
                'progress' => in_array($status, ['completed', 'closed'], true) ? 100 : rand(0, 80),
                'reopened_count' => $status === 'reopened' ? 1 : 0,
                'closed_at' => in_array($status, ['completed', 'closed'], true) ? $createdAt->copy()->addDays(rand(1, 6)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addHours(rand(1, 40)),
            ]);

            // Some ratings on closed tickets.
            if (in_array($status, ['completed', 'closed'], true) && $i % 2 === 0) {
                DB::table('request_ratings')->insert([
                    'id' => (string) Str::uuid(), 'request_id' => $req->id,
                    'customer_id' => $req->customer_id, 'stars' => rand(3, 5),
                    'extra_answers' => '{}', 'needs_supervisor_review' => false, 'created_at' => $now,
                ]);
            }

            DB::table('request_activity_log')->insert([
                'id' => (string) Str::uuid(), 'request_id' => $req->id,
                'action' => 'created', 'to_value' => 'new', 'created_at' => $createdAt,
            ]);
        }

        $this->command?->info('Seeded '.count($titles).' demo requests.');
    }

    private function number(Category $cat, int $seq): string
    {
        $code = $cat->code_prefix ?: Str::upper(Str::substr(preg_replace('/[^a-z]/i', '', (string) $cat->key), 0, 3));
        $code = $code ?: 'GEN';

        return 'REQ-'.now()->format('y')."-{$code}-{$seq}";
    }
}
