<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- teams ----
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->char('manager_id', 36)->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ---- profiles (person identity; uuid PK; logically == users.id) ----
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_status')->default('active');
            $table->char('account_manager_id', 36)->nullable();
            $table->foreignUuid('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('tier')->nullable();
            $table->string('journey_stage')->nullable();
            $table->integer('activation_percent')->default(0);
            $table->string('risk_level')->nullable();
            $table->string('business_field')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('website')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->integer('staff_sort_order')->default(0);
            $table->boolean('suspended')->default(false);
            $table->timestamps();
        });

        // ---- products ----
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_ar');
            $table->string('type')->default('product');
            $table->text('description_ar')->nullable();
            $table->integer('sla_hours')->nullable();
            $table->integer('escalation_hours')->nullable();
            $table->string('escalation_to_role')->nullable();
            $table->boolean('restricted')->default(false);
            $table->boolean('active')->default(true);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->char('account_manager_id', 36)->nullable();
            $table->char('team_id', 36)->nullable();
            $table->timestamps();
        });

        // ---- categories ----
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('default_priority')->default('medium');
            $table->string('target_team')->nullable();
            $table->integer('sla_hours')->nullable();
            $table->integer('response_sla_hours')->nullable();
            $table->integer('auto_close_days')->nullable();
            $table->integer('reminder_interval_hours')->nullable();
            $table->integer('escalation_hours')->nullable();
            $table->string('escalation_l1_role')->nullable();
            $table->string('escalation_l2_role')->nullable();
            $table->string('escalation_l3_role')->nullable();
            $table->string('escalation_l4_role')->nullable();
            $table->string('escalation_to_role')->nullable();
            $table->json('escalation_channels')->nullable();
            $table->json('escalation_thresholds')->nullable();
            $table->json('allowed_channels')->nullable();
            $table->json('approval_steps')->nullable();
            $table->json('automation_rules')->nullable();
            $table->json('routing_rules')->nullable();
            $table->json('workflow_steps')->nullable();
            $table->json('transitions_matrix')->nullable();
            $table->json('required_fields_per_state')->nullable();
            $table->string('workflow_template')->nullable();
            $table->string('working_hours_mode')->nullable();
            $table->boolean('freeze_on_holidays')->default(false);
            $table->string('auto_assign_strategy')->nullable();
            $table->string('numbering_pattern')->nullable();
            $table->string('code_prefix')->nullable();
            $table->boolean('rating_enabled')->default(false);
            $table->json('rating_base_config')->nullable();
            $table->json('rating_channels')->nullable();
            $table->json('rating_questions')->nullable();
            $table->string('rating_scale_type')->nullable();
            $table->string('rating_timing')->nullable();
            $table->integer('negative_alert_threshold')->nullable();
            $table->json('negative_alert_recipients')->nullable();
            $table->text('open_question')->nullable();
            $table->boolean('open_question_enabled')->default(false);
            $table->boolean('is_suggestion')->default(false);
            $table->string('icon')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('color')->nullable();
            $table->string('visibility')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ---- business_calendars ----
        Schema::create('business_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('timezone')->default('Asia/Riyadh');
            $table->json('weekly_schedule')->nullable();
            $table->json('seasonal_schedule')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // ---- appointment_types ----
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name_ar');
            $table->string('mode');
            $table->integer('duration_minutes')->default(30);
            $table->integer('buffer_minutes')->default(0);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('requires_request_link')->default(false);
            $table->text('description')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('color')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ---- leave_types ----
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('label_ar');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->boolean('affects_assignment')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ---- capability_meta (PK capability) ----
        Schema::create('capability_meta', function (Blueprint $table) {
            $table->string('capability')->primary();
            $table->string('action_type')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ---- custom_roles ----
        Schema::create('custom_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('capability_actions')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ---- priority_multipliers (PK priority) ----
        Schema::create('priority_multipliers', function (Blueprint $table) {
            $table->string('priority')->primary();
            $table->decimal('multiplier', 6, 2)->default(1);
            $table->string('label_ar')->nullable();
            $table->char('updated_by', 36)->nullable();
            $table->timestamps();
        });

        // ---- field_definitions ----
        Schema::create('field_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('scope')->nullable();
            $table->string('stage_name')->nullable();
            $table->string('field_key');
            $table->string('field_type')->default('text');
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('visible_to_customer')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'field_definitions', 'priority_multipliers', 'custom_roles', 'capability_meta',
            'leave_types', 'appointment_types', 'business_calendars', 'categories',
            'products', 'profiles', 'teams',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
