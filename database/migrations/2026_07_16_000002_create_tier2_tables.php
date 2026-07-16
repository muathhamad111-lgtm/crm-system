<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- request_sub_categories (parent for kb_articles, overrides) ----
        Schema::create('request_sub_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name_ar');
            $table->integer('sla_hours')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->string('auto_assign_strategy')->nullable();
            $table->boolean('allow_tech_bypass')->default(false);
            $table->timestamps();
            $table->index('category_id');
        });

        // ---- request_number_seqs (PK category_id) ----
        Schema::create('request_number_seqs', function (Blueprint $table) {
            $table->char('category_id', 36)->primary();
            $table->bigInteger('last_value')->default(0);
            $table->timestamps();
        });

        // ---- priority_sla ----
        Schema::create('priority_sla', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('priority');
            $table->integer('response_minutes')->nullable();
            $table->integer('start_minutes')->nullable();
            $table->integer('resolution_hours')->nullable();
            $table->integer('internal_resolution_hours')->nullable();
            $table->integer('external_resolution_hours')->nullable();
            $table->timestamps();
            $table->unique(['category_id', 'priority']);
        });

        // ---- stage_sla_config ----
        Schema::create('stage_sla_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->integer('stage_index');
            $table->string('stage_name');
            $table->integer('base_minutes')->default(0);
            $table->integer('max_minutes')->nullable();
            $table->boolean('is_terminal')->default(false);
            $table->boolean('pauses_sla')->default(false);
            $table->boolean('respect_business_hours')->default(true);
            $table->char('business_calendar_id', 36)->nullable();
            $table->timestamps();
            $table->unique(['category_id', 'stage_index']);
        });

        // ---- category_sla_overrides ----
        Schema::create('category_sla_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('priority')->nullable();
            $table->integer('response_minutes')->nullable();
            $table->integer('resolution_minutes')->nullable();
            $table->boolean('business_hours_only')->default(true);
            $table->char('calendar_id', 36)->nullable();
            $table->timestamps();
        });

        // ---- sub_category_sla_overrides ----
        Schema::create('sub_category_sla_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sub_category_id')->constrained('request_sub_categories')->cascadeOnDelete();
            $table->integer('stage_index');
            $table->integer('base_minutes')->nullable();
            $table->integer('max_minutes')->nullable();
            $table->boolean('respect_business_hours')->default(true);
            $table->char('business_calendar_id', 36)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['sub_category_id', 'stage_index']);
        });

        // ---- user_roles ----
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36);
            $table->string('role');
            $table->timestamps();
            $table->unique(['user_id', 'role']);
            $table->index('role');
        });

        // ---- role_permissions ----
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role');
            $table->string('capability');
            $table->string('action_type')->nullable();
            $table->boolean('allowed')->default(true);
            $table->timestamps();
            $table->unique(['role', 'capability']);
        });

        // ---- staff_products (pivot) ----
        Schema::create('staff_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36);
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // ---- holidays ----
        Schema::create('holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('calendar_id')->nullable()->constrained('business_calendars')->cascadeOnDelete();
            $table->string('name');
            $table->date('holiday_date');
            $table->date('end_date')->nullable();
            $table->string('holiday_type')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->boolean('exclude_sla')->default(true);
            $table->boolean('block_intake')->default(false);
            $table->boolean('enabled')->default(true);
            $table->json('custom_hours')->nullable();
            $table->string('external_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['calendar_id', 'external_id']);
        });

        // ---- customer_subscriptions ----
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('customer_id', 36)->nullable();
            $table->string('product_name');
            $table->string('plan_name')->nullable();
            $table->string('status')->default('active');
            $table->string('source')->nullable();
            $table->string('external_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // ---- customer_contacts ----
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('customer_id', 36)->nullable();
            $table->string('full_name');
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('role_type')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('has_portal_access')->default(false);
            $table->char('linked_user_id', 36)->nullable();
            $table->json('communication_preferences')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- customer_activities ----
        Schema::create('customer_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('customer_id', 36)->nullable();
            $table->char('contact_id', 36)->nullable();
            $table->string('activity_type')->nullable();
            $table->string('subject')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->char('performed_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- customer_attachments ----
        Schema::create('customer_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('customer_id', 36)->nullable();
            $table->string('category')->nullable();
            $table->string('file_name');
            $table->string('storage_path');
            $table->string('mime_type')->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->text('description')->nullable();
            $table->char('uploaded_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- customer_activation_tasks ----
        Schema::create('customer_activation_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('customer_id', 36)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->char('assigned_to', 36)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- tts_customers (parent for tts children) ----
        Schema::create('tts_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable();
            $table->string('source')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable();
            $table->string('license_no')->nullable();
            $table->string('tax_no')->nullable();
            $table->string('business_field')->nullable();
            $table->date('foundation_date')->nullable();
            $table->string('status')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        // ---- tts_contacts ----
        Schema::create('tts_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->nullable()->constrained('tts_customers')->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('job_title')->nullable();
            $table->string('role_type')->nullable();
            $table->string('delegation_type')->nullable();
            $table->boolean('delegate_confirmed')->nullable();
            $table->string('status')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        // ---- tts_subscriptions ----
        Schema::create('tts_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->nullable()->constrained('tts_customers')->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->string('package_name')->nullable();
            $table->string('subscription_number')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_demo')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        // ---- tts_product_keys ----
        Schema::create('tts_product_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('product_code');
            $table->string('product_key')->nullable();
            $table->string('central_domain')->nullable();
            $table->string('kindly_product_id')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ---- tts_config (PK key) ----
        Schema::create('tts_config', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ---- tts_sync_log ----
        Schema::create('tts_sync_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type')->nullable();
            $table->string('external_id')->nullable();
            $table->string('action')->nullable();
            $table->string('result')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- appointment_availability ----
        Schema::create('appointment_availability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('assignee_id', 36)->nullable();
            $table->char('team_id', 36)->nullable();
            $table->integer('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ---- appointment_type_assignees ----
        Schema::create('appointment_type_assignees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('type_id')->constrained('appointment_types')->cascadeOnDelete();
            $table->char('assignee_id', 36);
            $table->integer('max_daily')->nullable();
            $table->timestamps();
            $table->unique(['type_id', 'assignee_id']);
        });

        // ---- appointment_blocked_slots ----
        Schema::create('appointment_blocked_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('assignee_id', 36)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_holiday')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // ---- suggestion_stage_role_assignments ----
        Schema::create('suggestion_stage_role_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stage');
            $table->string('role');
            $table->char('product_id', 36)->nullable();
            $table->char('team_id', 36)->nullable();
            $table->timestamps();
            $table->unique(['stage', 'product_id', 'role', 'team_id'], 'ssra_unique');
        });

        // ---- employee_leaves ----
        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('employee_id', 36);
            $table->foreignUuid('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days')->nullable();
            $table->string('status')->default('draft');
            $table->text('reason')->nullable();
            $table->char('requested_by', 36)->nullable();
            $table->char('approved_by', 36)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->char('substitute_id', 36)->nullable();
            $table->string('coverage_strategy')->nullable();
            $table->timestamp('coverage_applied_at')->nullable();
            $table->json('impact_snapshot')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->timestamps();
        });

        // ---- leave_activity_log ----
        Schema::create('leave_activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('leave_id')->constrained('employee_leaves')->cascadeOnDelete();
            $table->char('user_id', 36)->nullable();
            $table->string('action');
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- leave_impact_log ----
        Schema::create('leave_impact_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('leave_id')->constrained('employee_leaves')->cascadeOnDelete();
            $table->string('entity_type');
            $table->char('entity_id', 36)->nullable();
            $table->string('action');
            $table->char('from_user_id', 36)->nullable();
            $table->char('to_user_id', 36)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- calendar_events ----
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event_type')->default('other');
            $table->string('status')->default('scheduled');
            $table->string('visibility')->default('internal');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('rrule')->nullable();
            $table->integer('reminder_minutes_before')->nullable();
            $table->char('assigned_to', 36)->nullable();
            $table->char('team_id', 36)->nullable();
            $table->char('related_customer_id', 36)->nullable();
            $table->char('related_request_id', 36)->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- kb_articles ----
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->string('type')->nullable();
            $table->string('status')->default('draft');
            $table->string('complexity')->nullable();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->char('product_id', 36)->nullable();
            $table->foreignUuid('sub_category_id')->nullable()->constrained('request_sub_categories')->nullOnDelete();
            $table->boolean('is_general')->default(false);
            $table->json('keywords')->nullable();
            $table->json('steps')->nullable();
            $table->json('attachments')->nullable();
            $table->json('reference_links')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('warnings')->nullable();
            $table->char('author_id', 36)->nullable();
            $table->char('reviewer_id', 36)->nullable();
            $table->char('approver_id', 36)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('current_version')->default(1);
            $table->integer('views_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->integer('insert_solution_count')->default(0);
            $table->integer('sent_to_customer_count')->default(0);
            $table->integer('rating_count')->default(0);
            $table->decimal('avg_rating', 6, 2)->default(0);
            $table->timestamps();
            $table->index('status');
            $table->index('category_id');
        });

        // ---- kb_gap_reports ----
        Schema::create('kb_gap_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('topic');
            $table->json('keywords')->nullable();
            $table->integer('occurrences')->default(0);
            $table->json('related_request_ids')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'kb_gap_reports', 'kb_articles', 'calendar_events', 'leave_impact_log', 'leave_activity_log',
            'employee_leaves', 'suggestion_stage_role_assignments', 'appointment_blocked_slots',
            'appointment_type_assignees', 'appointment_availability', 'tts_sync_log', 'tts_config',
            'tts_product_keys', 'tts_subscriptions', 'tts_contacts', 'tts_customers',
            'customer_activation_tasks', 'customer_attachments', 'customer_activities', 'customer_contacts',
            'customer_subscriptions', 'holidays', 'staff_products', 'role_permissions', 'user_roles',
            'sub_category_sla_overrides', 'category_sla_overrides', 'stage_sla_config', 'priority_sla',
            'request_number_seqs', 'request_sub_categories',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
