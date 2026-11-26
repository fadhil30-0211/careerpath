<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('age_range', 30)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('education_level', 50)->nullable();
            $table->string('field_of_study', 150)->nullable();
            $table->string('institution', 150)->nullable();
            $table->text('bio')->nullable();
            $table->json('interests')->nullable();
            $table->json('work_preferences')->nullable();
            $table->boolean('experiences_reviewed')->default(false);
            $table->boolean('certifications_reviewed')->default(false);
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamps();
        });

        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('source_type', 50)->default('internal');
            $table->string('url')->nullable();
            $table->string('publisher')->nullable();
            $table->date('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 40)->default('briefcase');
            $table->string('color', 20)->default('teal');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupation_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('occupation_categories')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained('occupation_subcategories')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('occupation_groups')->restrictOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('summary', 500);
            $table->text('description');
            $table->json('main_activities')->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('work_environment')->nullable();
            $table->text('alternative_careers')->nullable();
            $table->json('work_preferences')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->date('verified_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category', 100)->default('Umum');
            $table->text('description')->nullable();
            $table->string('level_framework', 50)->default('1-5');
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupation_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->unsignedTinyInteger('target_level');
            $table->enum('requirement_type', ['mandatory', 'recommended', 'optional'])->default('recommended');
            $table->unsignedTinyInteger('importance')->default(3);
            $table->text('notes')->nullable();
            $table->unique(['occupation_id', 'skill_id']);
        });

        Schema::create('education_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('education_level', 50);
            $table->string('program_type', 50)->default('formal');
            $table->text('description')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupation_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_id')->constrained('education_programs')->restrictOnDelete();
            $table->enum('relationship_type', ['mandatory', 'formal', 'relevant', 'alternative'])->default('relevant');
            $table->text('notes')->nullable();
            $table->unique(['occupation_id', 'education_id']);
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->enum('credential_type', ['license', 'certification'])->default('certification');
            $table->text('validity_notes')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupation_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certification_id')->constrained()->restrictOnDelete();
            $table->enum('requirement_type', ['mandatory', 'recommended', 'optional'])->default('recommended');
            $table->text('notes')->nullable();
            $table->unique(['occupation_id', 'certification_id']);
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('workplace_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained()->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('occupation_workplaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workplace_id')->constrained('workplace_types')->restrictOnDelete();
            $table->text('relevance_description')->nullable();
            $table->unique(['occupation_id', 'workplace_id']);
        });

        Schema::create('career_roadmaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('roadmap_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roadmap_id')->constrained('career_roadmaps')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->unsignedSmallInteger('sequence');
            $table->string('title');
            $table->text('objective');
            $table->text('action');
            $table->string('resource_title')->nullable();
            $table->string('resource_url')->nullable();
            $table->unsignedTinyInteger('estimated_weeks')->nullable();
            $table->unique(['roadmap_id', 'sequence']);
        });

        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('level')->nullable();
            $table->enum('possession_status', ['unknown', 'possessed', 'not_yet'])->default('unknown');
            $table->boolean('self_reported')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'skill_id']);
        });

        Schema::create('user_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['work', 'internship', 'project', 'organization', 'volunteer'])->default('work');
            $table->string('title');
            $table->string('organization')->nullable();
            $table->text('description')->nullable();
            $table->string('start_date', 20)->nullable();
            $table->string('end_date', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('user_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certification_id')->nullable()->constrained()->nullOnDelete();
            $table->string('custom_name')->nullable();
            $table->string('issuer')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('self_reported')->default(true);
            $table->timestamps();
        });

        Schema::create('matching_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['matching', 'goal'])->default('matching');
            $table->string('algorithm_version', 50);
            $table->json('policy_snapshot');
            $table->json('input_snapshot');
            $table->json('catalog_snapshot');
            $table->unsignedSmallInteger('result_count')->default(0);
            $table->timestamp('created_at');
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('matching_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('matching_runs')->cascadeOnDelete();
            $table->foreignId('occupation_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('rank');
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('coverage', 5, 2)->default(0);
            $table->json('profession_snapshot');
            $table->json('breakdown');
            $table->json('skill_gaps');
            $table->json('mandatory_requirements');
            $table->json('reasons');
            $table->json('unknown_fields');
            $table->unique(['run_id', 'occupation_id']);
        });

        Schema::create('saved_careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'occupation_id']);
        });

        Schema::create('user_roadmaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('roadmap_id')->constrained('career_roadmaps')->restrictOnDelete();
            $table->string('name');
            $table->enum('status', ['active', 'completed', 'archived'])->default('active');
            $table->timestamps();
            $table->unique(['user_id', 'roadmap_id']);
        });

        Schema::create('roadmap_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_roadmap_id')->constrained('user_roadmaps')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('roadmap_stages')->restrictOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_roadmap_id', 'stage_id']);
        });

        Schema::create('content_reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('decision', ['submitted', 'approved', 'returned', 'archived'])->default('submitted');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('content_reviews');
        Schema::dropIfExists('roadmap_progress');
        Schema::dropIfExists('user_roadmaps');
        Schema::dropIfExists('saved_careers');
        Schema::dropIfExists('matching_results');
        Schema::dropIfExists('matching_runs');
        Schema::dropIfExists('user_certifications');
        Schema::dropIfExists('user_experiences');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('roadmap_stages');
        Schema::dropIfExists('career_roadmaps');
        Schema::dropIfExists('occupation_workplaces');
        Schema::dropIfExists('workplace_types');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('occupation_certifications');
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('occupation_educations');
        Schema::dropIfExists('education_programs');
        Schema::dropIfExists('occupation_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('occupation_groups');
        Schema::dropIfExists('occupation_subcategories');
        Schema::dropIfExists('occupation_categories');
        Schema::dropIfExists('data_sources');
        Schema::dropIfExists('user_profiles');
    }
};
