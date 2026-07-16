<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- requests (central ticket + suggestion entity) ----
        Schema::create('requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('sub_category_id')->nullable()->constrained('request_sub_categories')->nullOnDelete();
            $table->char('customer_id', 36)->nullable();
            $table->char('contact_id', 36)->nullable();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->char('tts_customer_id', 36)->nullable();
            $table->char('owner_manager_id', 36)->nullable();
            $table->char('product_owner_id', 36)->nullable();
            $table->string('status')->default('new');
            $table->string('priority')->default('medium');
            $table->string('channel')->default('portal');
            $table->string('assigned_team')->nullable();
            $table->char('assigned_to', 36)->nullable();
            $table->string('source')->nullable();
            $table->string('source_customer_url')->nullable();
            $table->string('source_external_id')->nullable();
            $table->string('source_product_code')->nullable();
            $table->json('source_metadata')->nullable();

            // Stage / SLA
            $table->integer('current_stage_index')->nullable();
            $table->string('current_stage_name')->nullable();
            $table->timestamp('current_stage_started_at')->nullable();
            $table->timestamp('current_stage_due_at')->nullable();
            $table->integer('current_stage_base_minutes')->nullable();
            $table->integer('current_stage_final_minutes')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('stage_alert_75_sent_at')->nullable();
            $table->timestamp('stage_alert_90_sent_at')->nullable();
            $table->timestamp('sla_paused_at')->nullable();
            $table->integer('sla_paused_total_seconds')->default(0);
            $table->string('sla_pause_reason')->nullable();

            // Escalation
            $table->integer('escalation_level')->default(0);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('escalation_at')->nullable();
            $table->timestamp('escalation_l1_at')->nullable();
            $table->timestamp('escalation_l2_at')->nullable();
            $table->timestamp('escalation_l3_at')->nullable();

            // Waiting
            $table->string('external_wait_party')->nullable();
            $table->text('external_wait_reason')->nullable();
            $table->timestamp('external_wait_started_at')->nullable();
            $table->string('customer_action')->nullable();
            $table->integer('customer_reminder_count')->default(0);
            $table->timestamp('last_customer_reminder_at')->nullable();
            $table->timestamp('returned_to_customer_at')->nullable();
            $table->text('return_reason')->nullable();
            $table->timestamp('auto_close_due_at')->nullable();

            // Closure
            $table->timestamp('closed_at')->nullable();
            $table->char('closed_by', 36)->nullable();
            $table->timestamp('first_closed_at')->nullable();
            $table->char('first_closed_by', 36)->nullable();
            $table->timestamp('previous_closed_at')->nullable();
            $table->char('previous_closed_by', 36)->nullable();
            $table->string('closure_channel')->nullable();
            $table->string('closure_reason_code')->nullable();
            $table->text('closure_reason_public')->nullable();
            $table->text('closure_note_internal')->nullable();

            // Reopen
            $table->integer('reopened_count')->default(0);
            $table->timestamp('last_reopened_at')->nullable();
            $table->text('last_reopen_reason')->nullable();
            $table->timestamp('reopen_started_at')->nullable();
            $table->timestamp('reopen_due_at')->nullable();
            $table->timestamp('reopen_deadline_at')->nullable();
            $table->timestamp('reopen_closed_at')->nullable();
            $table->integer('reopen_final_minutes')->nullable();
            $table->boolean('reopen_breached')->nullable();
            $table->char('reopen_handler_id', 36)->nullable();
            $table->integer('days_since_last_close')->nullable();

            // Comments lock
            $table->boolean('comments_locked')->default(false);
            $table->timestamp('comments_locked_at')->nullable();
            $table->char('comments_locked_by', 36)->nullable();

            // Suggestion / idea
            $table->string('idea_stage')->default('received');
            $table->string('decision')->default('pending');
            $table->timestamp('decision_at')->nullable();
            $table->char('decision_by', 36)->nullable();
            $table->text('decision_reason')->nullable();
            $table->decimal('reach', 12, 2)->nullable();
            $table->decimal('effort', 12, 2)->nullable();
            $table->decimal('confidence', 12, 2)->nullable();
            $table->decimal('value_score', 12, 2)->nullable();
            $table->string('impact_level')->nullable();
            $table->integer('affected_users_count')->nullable();
            $table->integer('affected_orgs_count')->nullable();
            $table->integer('progress')->default(0);
            $table->text('root_cause')->nullable();
            $table->string('target_release')->nullable();
            $table->boolean('published_to_customers')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->char('published_by', 36)->nullable();
            $table->char('duplicate_of_id', 36)->nullable();
            $table->char('merged_into_id', 36)->nullable();
            $table->json('customer_form_schema')->nullable();
            $table->json('customer_form_response')->nullable();
            $table->timestamp('tech_bypass_at')->nullable();
            $table->char('tech_bypass_by', 36)->nullable();
            $table->char('tech_bypass_approved_by', 36)->nullable();
            $table->string('tech_bypass_reason_code')->nullable();
            $table->text('tech_bypass_description')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('category_id');
            $table->index('customer_id');
            $table->index('assigned_to');
            $table->index('idea_stage');
        });

        // ---- request_activity_log ----
        Schema::create('request_activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('user_id', 36)->nullable();
            $table->string('action');
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- request_comments ----
        Schema::create('request_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('user_id', 36)->nullable();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->string('author_name')->nullable();
            $table->char('author_team_id', 36)->nullable();
            $table->string('author_team_name')->nullable();
            $table->char('assigned_to_user_id', 36)->nullable();
            $table->char('mentioned_team_id', 36)->nullable();
            $table->json('mentioned_user_ids')->nullable();
            $table->char('reply_to_id', 36)->nullable();
            $table->json('read_by')->nullable();
            $table->timestamp('pinned_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->char('edited_by', 36)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- comment_reactions ----
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('comment_id')->constrained('request_comments')->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->string('emoji');
            $table->timestamp('created_at')->nullable();
            $table->unique(['comment_id', 'user_id', 'emoji']);
        });

        // ---- comment_templates ----
        Schema::create('comment_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_ar');
            $table->text('body');
            $table->string('scope')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ---- request_attachments ----
        Schema::create('request_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('comment_id', 36)->nullable();
            $table->string('file_name');
            $table->string('file_url');
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->char('uploaded_by', 36)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- request_field_values ----
        Schema::create('request_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('field_id', 36);
            $table->json('value')->nullable();
            $table->char('updated_by', 36)->nullable();
            $table->timestamps();
            $table->unique(['request_id', 'field_id']);
        });

        // ---- request_ratings ----
        Schema::create('request_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('customer_id', 36)->nullable();
            $table->char('tts_customer_id', 36)->nullable();
            $table->integer('stars')->nullable();
            $table->json('dissatisfaction_reasons')->nullable();
            $table->json('extra_answers')->nullable();
            $table->boolean('needs_supervisor_review')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique('request_id');
        });

        // ---- request_extended_feedback ----
        Schema::create('request_extended_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('customer_id', 36)->nullable();
            $table->integer('nps')->nullable();
            $table->integer('ces')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->boolean('resolved')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ---- request_reopen_log ----
        Schema::create('request_reopen_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->char('opened_by', 36)->nullable();
            $table->string('opened_by_role')->nullable();
            $table->char('handler_id', 36)->nullable();
            $table->string('from_status')->nullable();
            $table->integer('to_stage_index')->nullable();
            $table->timestamp('previous_closed_at')->nullable();
            $table->char('previous_closed_by', 36)->nullable();
            $table->integer('days_since_close')->nullable();
            $table->boolean('escalation_triggered')->default(false);
            $table->string('escalation_action')->nullable();
            $table->timestamp('sla_started_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('sla_closed_at')->nullable();
            $table->integer('sla_final_minutes')->nullable();
            $table->boolean('sla_breached')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- request_stage_checklist_state ----
        Schema::create('request_stage_checklist_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->integer('stage_index');
            $table->string('stage_name')->nullable();
            $table->string('item_id');
            $table->string('item_label')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->char('checked_by', 36)->nullable();
            $table->timestamps();
            $table->unique(['request_id', 'stage_index', 'item_id'], 'rscs_unique');
        });

        // ---- request_stage_sla_log ----
        Schema::create('request_stage_sla_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->integer('stage_index');
            $table->string('stage_name');
            $table->string('assigned_team')->nullable();
            $table->char('assigned_to', 36)->nullable();
            $table->string('priority')->nullable();
            $table->decimal('multiplier', 6, 2)->nullable();
            $table->integer('base_minutes')->nullable();
            $table->integer('max_minutes')->nullable();
            $table->integer('final_minutes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->integer('paused_total_seconds')->default(0);
            $table->boolean('breached')->nullable();
            $table->integer('escalation_level')->default(0);
            $table->string('closure_reason')->nullable();
            $table->timestamps();
        });

        // ---- request_tasks ----
        Schema::create('request_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('parent_task_id', 36)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('task_type')->default('main');
            $table->string('status')->default('todo');
            $table->string('priority')->default('medium');
            $table->string('sla_impact')->default('none');
            $table->boolean('sla_extension_applied')->default(false);
            $table->integer('sla_extension_minutes')->default(0);
            $table->string('assigned_team')->nullable();
            $table->char('assigned_to', 36)->nullable();
            $table->char('created_by', 36)->nullable();
            $table->string('creation_reason')->nullable();
            $table->string('template_key')->nullable();
            $table->string('stage_snapshot')->nullable();
            $table->integer('position')->default(0);
            $table->integer('progress_pct')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamps();
            $table->index('request_id');
            $table->index('status');
        });

        // ---- request_task_checklist ----
        Schema::create('request_task_checklist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('request_tasks')->cascadeOnDelete();
            $table->string('label');
            $table->integer('position')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->char('done_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- request_task_comments ----
        Schema::create('request_task_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('request_tasks')->cascadeOnDelete();
            $table->char('author_id', 36)->nullable();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        // ---- request_task_attachments ----
        Schema::create('request_task_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('request_tasks')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->char('uploaded_by', 36)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- request_task_activity ----
        Schema::create('request_task_activity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('task_id', 36)->nullable();
            $table->char('request_id', 36)->nullable();
            $table->char('actor_id', 36)->nullable();
            $table->string('event_type');
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->string('task_title_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- suggestion_votes ----
        Schema::create('suggestion_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->string('vote');
            $table->timestamps();
            $table->unique(['request_id', 'user_id']);
        });

        // ---- suggestion_priority_votes ----
        Schema::create('suggestion_priority_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->string('priority');
            $table->timestamps();
            $table->unique(['request_id', 'user_id']);
        });

        // ---- suggestion_comments ----
        Schema::create('suggestion_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('user_id', 36)->nullable();
            $table->text('body');
            $table->string('kind')->nullable();
            $table->char('parent_id', 36)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->char('deleted_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- suggestion_ratings ----
        Schema::create('suggestion_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('customer_id', 36);
            $table->integer('stars')->nullable();
            $table->timestamps();
            $table->unique(['request_id', 'customer_id']);
        });

        // ---- suggestion_post_impl_feedback ----
        Schema::create('suggestion_post_impl_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('user_id', 36)->nullable();
            $table->string('satisfaction')->nullable();
            $table->text('improvements')->nullable();
            $table->timestamps();
        });

        // ---- suggestion_decisions_log ----
        Schema::create('suggestion_decisions_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->char('actor_id', 36)->nullable();
            $table->string('action');
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- kb_article_versions ----
        Schema::create('kb_article_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('title');
            $table->longText('body');
            $table->text('summary')->nullable();
            $table->text('change_note')->nullable();
            $table->json('snapshot')->nullable();
            $table->char('changed_by', 36)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['article_id', 'version_number']);
        });

        // ---- kb_article_products (pivot) ----
        Schema::create('kb_article_products', function (Blueprint $table) {
            $table->foreignUuid('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->char('product_id', 36);
            $table->primary(['article_id', 'product_id']);
        });

        // ---- kb_article_ratings ----
        Schema::create('kb_article_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'user_id']);
        });

        // ---- kb_article_feedback ----
        Schema::create('kb_article_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->boolean('was_helpful')->nullable();
            $table->text('note')->nullable();
            $table->char('request_id', 36)->nullable();
            $table->char('user_id', 36)->nullable();
            $table->integer('resolution_time_seconds')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- kb_article_usage_log ----
        Schema::create('kb_article_usage_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->string('action')->nullable();
            $table->char('request_id', 36)->nullable();
            $table->char('user_id', 36)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // ---- kb_ticket_suggestions ----
        Schema::create('kb_ticket_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('request_id', 36);
            $table->foreignUuid('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->decimal('score', 6, 2)->nullable();
            $table->json('matched_on')->nullable();
            $table->boolean('dismissed')->default(false);
            $table->timestamp('suggested_at')->nullable();
            $table->timestamps();
            $table->unique(['request_id', 'article_id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'kb_ticket_suggestions', 'kb_article_usage_log', 'kb_article_feedback', 'kb_article_ratings',
            'kb_article_products', 'kb_article_versions', 'suggestion_decisions_log',
            'suggestion_post_impl_feedback', 'suggestion_ratings', 'suggestion_comments',
            'suggestion_priority_votes', 'suggestion_votes', 'request_task_activity',
            'request_task_attachments', 'request_task_comments', 'request_task_checklist', 'request_tasks',
            'request_stage_sla_log', 'request_stage_checklist_state', 'request_reopen_log',
            'request_extended_feedback', 'request_ratings', 'request_field_values', 'request_attachments',
            'comment_templates', 'comment_reactions', 'request_comments', 'request_activity_log', 'requests',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
