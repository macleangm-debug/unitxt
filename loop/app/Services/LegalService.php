<?php

namespace App\Services;

use App\Models\LegalAcceptance;
use App\Models\LegalDocument;
use App\Models\User;
use App\Support\LegalCatalog;
use App\Support\LegalDrafts;

class LegalService
{
    public function syncDrafts(): void
    {
        $types = LegalCatalog::types();
        foreach (LegalDrafts::all() as $slug => $draft) {
            $meta = $types[$slug] ?? null;
            if (! $meta) {
                continue;
            }
            LegalDocument::query()->firstOrCreate(
                ['slug' => $slug, 'version' => LegalDrafts::VERSION],
                [
                    'audience' => $meta['audience'][0] === 'affiliate' && count($meta['audience']) === 1 ? 'affiliate' : (in_array('business', $meta['audience'], true) && ! in_array('member', $meta['audience'], true) ? 'business' : 'all'),
                    'status' => LegalDocument::STATUS_PUBLISHED,
                    'requires_acceptance' => $meta['requires_acceptance'],
                    'counsel_reviewed' => false,
                    'effective_on' => now()->toDateString(),
                    'title_en' => $draft['title_en'],
                    'title_sw' => $draft['title_sw'],
                    'summary_en' => $draft['summary_en'],
                    'summary_sw' => $draft['summary_sw'],
                    'body_en' => $draft['body_en'],
                    'body_sw' => $draft['body_sw'],
                ]
            );
        }
    }

    public function recordSignup(User $user, string $role, string $method = 'signup'): void
    {
        $this->syncDrafts();
        $slugs = ['terms', 'privacy'];
        if ($role === 'business') {
            $slugs[] = 'business-terms';
        }
        if ($role === 'affiliate') {
            $slugs[] = 'affiliate-terms';
        }
        foreach ($slugs as $slug) {
            $doc = LegalDocument::current($slug);
            if (! $doc) {
                continue;
            }
            LegalAcceptance::query()->create([
                'user_id' => $user->id,
                'legal_document_id' => $doc->id,
                'role' => $role,
                'method' => $slug === 'privacy' ? 'acknowledge:'.$method : $method,
                'accepted_at' => now(),
            ]);
        }
    }
}
