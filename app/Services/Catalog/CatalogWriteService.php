<?php

namespace App\Services\Catalog;

use App\Models\AuditLog;
use App\Models\ContentReview;
use App\Models\Occupation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CatalogWriteService
{
    public function transition(Model $record, string $action, User $actor, ?string $notes = null): Model
    {
        $transitions = [
            'submit' => ['from' => 'draft', 'status' => 'review', 'decision' => 'submitted'],
            'approve' => ['from' => 'review', 'status' => 'published', 'decision' => 'approved'],
            'return' => ['from' => 'review', 'status' => 'draft', 'decision' => 'returned'],
            'archive' => ['from' => 'published', 'status' => 'archived', 'decision' => 'archived'],
        ];

        if (! isset($transitions[$action])) {
            abort(422, 'Aksi review tidak dikenali.');
        }

        $transition = $transitions[$action];
        $targetStatus = $transition['status'];
        $decision = $transition['decision'];

        if ($record->status !== $transition['from']) {
            abort(422, 'Perpindahan status tidak diizinkan untuk kondisi data saat ini.');
        }

        if ($action === 'approve') {
            $this->assertPublishable($record);
        }

        return DB::transaction(function () use ($record, $action, $targetStatus, $decision, $actor, $notes) {
            $before = $record->status;
            $record->status = $targetStatus;

            if ($targetStatus === 'published' && array_key_exists('published_at', $record->getAttributes())) {
                $record->published_at = now();
                $record->version = (int) $record->version + 1;
            }

            $record->save();
            ContentReview::create([
                'reviewable_type' => $record::class,
                'reviewable_id' => $record->id,
                'reviewer_id' => $actor->id,
                'decision' => $decision,
                'notes' => $notes,
            ]);
            AuditLog::record($actor->id, "content.{$action}", $record, [
                'before' => ['status' => $before],
                'after' => ['status' => $targetStatus],
            ]);

            return $record->refresh();
        });
    }

    private function assertPublishable(Model $record): void
    {
        if (! $record instanceof Occupation) {
            return;
        }

        $record->loadMissing(['group.subcategory.category', 'source', 'skills', 'educations', 'certifications']);
        $parents = [
            $record->group,
            $record->group?->subcategory,
            $record->group?->subcategory?->category,
        ];

        if (collect($parents)->contains(fn ($parent) => ! $parent || $parent->status !== 'published')) {
            abort(422, 'Kategori, subkategori, dan kelompok profesi harus Published sebelum profesi dipublikasikan.');
        }
        if (! $record->source || $record->source->status !== 'published' || ! $record->verified_at) {
            abort(422, 'Sumber referensi dan tanggal verifikasi wajib tersedia sebelum publikasi.');
        }
        if ($record->skills->contains(fn ($skill) => $skill->status !== 'published')
            || $record->educations->contains(fn ($education) => $education->status !== 'published')
            || $record->certifications->contains(fn ($certification) => $certification->status !== 'published')) {
            abort(422, 'Semua data skill, pendidikan, dan sertifikasi terkait harus Published.');
        }
        if ($record->skills->where('pivot.requirement_type', 'mandatory')->contains(fn ($skill) => ! $skill->pivot->source_id)) {
            abort(422, 'Setiap skill wajib harus memiliki sumber referensi.');
        }
        if ($record->educations->where('pivot.relationship_type', 'mandatory')->contains(fn ($education) => ! $education->source || $education->source->status !== 'published')) {
            abort(422, 'Setiap pendidikan wajib harus memiliki sumber Published.');
        }
        if ($record->certifications->where('pivot.requirement_type', 'mandatory')->contains(fn ($certification) => ! $certification->source || $certification->source->status !== 'published' || ! $certification->issuer)) {
            abort(422, 'Setiap sertifikasi atau lisensi wajib harus memiliki sumber Published dan penerbit.');
        }
    }
}
