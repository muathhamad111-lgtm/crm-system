<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- appointments ----
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('appointment_number')->unique();
            $table->foreignUuid('type_id')->nullable()->constrained('appointment_types')->nullOnDelete();
            $table->char('customer_id', 36)->nullable();
            $table->char('assignee_id', 36)->nullable();
            $table->char('team_id', 36)->nullable();
            $table->char('product_id', 36)->nullable();
            $table->char('related_request_id', 36)->nullable();
            $table->string('status')->default('pending_confirmation');
            $table->string('reason_code')->nullable();
            $table->text('reason_other')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->integer('duration_minutes')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamp('proposed_at')->nullable();
            $table->char('proposed_by', 36)->nullable();
            $table->timestamp('proposed_starts_at')->nullable();
            $table->timestamp('proposed_ends_at')->nullable();
            $table->integer('reschedule_count')->default(0);
            $table->char('rescheduled_from', 36)->nullable();
            $table->char('rescheduled_to', 36)->nullable();
            $table->text('last_reschedule_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->char('cancelled_by', 36)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('customer_id');
        });

        // ---- appointment_invites ----
        Schema::create('appointment_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- appointment_activity_log ----
        Schema::create('appointment_activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->char('actor_id', 36)->nullable();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- notifications ----
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable();
            $table->char('team_id', 36)->nullable();
            $table->char('tts_customer_id', 36)->nullable();
            $table->string('scope')->nullable();
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link_path')->nullable();
            $table->string('priority')->nullable();
            $table->char('request_id', 36)->nullable();
            $table->string('dedup_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('user_id');
        });

        // ---- notification_templates ----
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_key');
            $table->string('name_ar');
            $table->string('recipient_type')->nullable();
            $table->json('channels')->nullable();
            $table->text('title_template')->nullable();
            $table->text('body_template')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ---- announcements ----
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('audience')->nullable();
            $table->string('priority')->nullable();
            $table->string('link_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- email_send_log ----
        Schema::create('email_send_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('template_name')->nullable();
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('status')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('message_id')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- sms_customer_templates ----
        Schema::create('sms_customer_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type');
            $table->text('body_ar');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        // ---- sms_dispatch_log ----
        Schema::create('sms_dispatch_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('request_id', 36)->nullable();
            $table->string('event_type')->nullable();
            $table->char('transition_id', 36)->nullable();
            $table->string('to_phone')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->nullable();
            $table->string('provider')->nullable();
            $table->string('message_id')->nullable();
            $table->integer('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['request_id', 'event_type', 'transition_id'], 'sms_dispatch_unique');
        });

        // ---- integration_settings ----
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->json('config')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        // ---- system_integrations ----
        Schema::create('system_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name_ar')->nullable();
            $table->json('config')->nullable();
            $table->boolean('active')->default(true);
            $table->char('manager_id', 36)->nullable();
            $table->timestamps();
        });

        // ---- webhook_event_subscriptions ----
        Schema::create('webhook_event_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('integration_key');
            $table->string('event_type');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['integration_key', 'event_type']);
        });

        // ---- webhook_delivery_log ----
        Schema::create('webhook_delivery_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('integration_key')->nullable();
            $table->string('event_type')->nullable();
            $table->string('target_url')->nullable();
            $table->json('payload')->nullable();
            $table->string('payload_hash')->nullable();
            $table->string('status')->nullable();
            $table->integer('http_status')->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->text('response_snippet')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- api_keys ----
        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('prefix');
            $table->string('key_hash');
            $table->json('scopes')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->integer('rate_limit_per_min')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- api_request_log (bigint id) ----
        Schema::create('api_request_log', function (Blueprint $table) {
            $table->id();
            $table->char('api_key_id', 36)->nullable();
            $table->string('prefix')->nullable();
            $table->string('method')->nullable();
            $table->string('path')->nullable();
            $table->integer('status')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('ip')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- store_contact_messages ----
        Schema::create('store_contact_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('mobile');
            $table->string('company_name')->nullable();
            $table->string('org_type')->nullable();
            $table->string('service_type')->nullable();
            $table->string('product_code')->nullable();
            $table->char('product_id', 36)->nullable();
            $table->text('description');
            $table->string('source')->nullable();
            $table->string('status')->nullable();
            $table->char('handled_by', 36)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();
        });

        // ---- system_settings (PK key) ----
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // ---- system_log (bigint id) ----
        Schema::create('system_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_type')->nullable();
            $table->string('severity')->nullable();
            $table->text('message')->nullable();
            $table->string('entity_type')->nullable();
            $table->char('entity_id', 36)->nullable();
            $table->string('table_name')->nullable();
            $table->string('target_team')->nullable();
            $table->string('role')->nullable();
            $table->char('actor_id', 36)->nullable();
            $table->string('actor_email')->nullable();
            $table->string('actor_name')->nullable();
            $table->char('user_id', 36)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- admin_audit_log ----
        Schema::create('admin_audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->char('entity_id', 36)->nullable();
            $table->char('actor_id', 36)->nullable();
            $table->string('actor_email')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- assignment_audit_log ----
        Schema::create('assignment_audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('request_id', 36)->nullable();
            $table->char('actor_id', 36)->nullable();
            $table->string('action')->nullable();
            $table->string('previous_team')->nullable();
            $table->string('target_team')->nullable();
            $table->char('previous_user_id', 36)->nullable();
            $table->char('target_user_id', 36)->nullable();
            $table->text('override_reason')->nullable();
            $table->json('unavailability_reasons')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- auth_access_log ----
        Schema::create('auth_access_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable();
            $table->string('email')->nullable();
            $table->string('portal')->nullable();
            $table->string('outcome')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach ([
            'auth_access_log', 'assignment_audit_log', 'admin_audit_log', 'system_log', 'system_settings',
            'store_contact_messages', 'api_request_log', 'api_keys', 'webhook_delivery_log',
            'webhook_event_subscriptions', 'system_integrations', 'integration_settings', 'sms_dispatch_log',
            'sms_customer_templates', 'email_send_log', 'announcements', 'notification_templates',
            'notifications', 'appointment_activity_log', 'appointment_invites', 'appointments',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
