<?php

namespace App\Http\Controllers;

use App\Models\LegalAcceptance;
use App\Models\LegalDocument;
use App\Models\PrivacyRequest;
use App\Services\LegalService;
use App\Support\LegalCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function index(Request $request, LegalService $legal): View
    {
        $legal->syncDrafts();
        $q = strtolower(trim((string) $request->query('q', '')));
        $docs = LegalDocument::library();
        if ($q !== '') {
            $docs = $docs->filter(function (LegalDocument $doc) use ($q) {
                return str_contains(strtolower($doc->title_en.' '.$doc->title_sw.' '.$doc->summary_en.' '.$doc->summary_sw), $q);
            })->values();
        }

        $groups = [
            'using' => __('loop.legal_group_using'),
            'privacy' => __('loop.legal_group_privacy'),
            'programmes' => __('loop.legal_group_programmes'),
            'businesses' => __('loop.legal_group_businesses'),
            'affiliates' => __('loop.legal_group_affiliates'),
        ];

        $byGroup = [];
        foreach ($docs as $doc) {
            $group = LegalCatalog::types()[$doc->slug]['group'] ?? 'using';
            $byGroup[$group][] = $doc;
        }

        return view('legal.index', [
            'groups' => $groups,
            'byGroup' => $byGroup,
            'search' => $q,
            'inApp' => (bool) $request->user(),
        ]);
    }

    public function show(string $slug, LegalService $legal): View
    {
        $legal->syncDrafts();
        abort_unless(isset(LegalCatalog::types()[$slug]), 404);
        $document = LegalDocument::current($slug);
        abort_unless($document, 404);

        return view('legal.show', [
            'document' => $document,
            'inApp' => (bool) request()->user(),
        ]);
    }

    public function account(Request $request, LegalService $legal): View
    {
        $user = $request->user();
        abort_unless($user, 403);
        $legal->syncDrafts();
        $role = $user->isCustomer() ? 'member' : ($user->isAffiliate() ? 'affiliate' : 'business');
        $slugs = LegalCatalog::slugsForRole($role);
        $docs = LegalDocument::library()->filter(fn (LegalDocument $doc) => in_array($doc->slug, $slugs, true))->values();
        $acceptances = LegalAcceptance::query()
            ->with('document')
            ->where('user_id', $user->id)
            ->latest('accepted_at')
            ->get();

        return view('legal.account', [
            'docs' => $docs,
            'acceptances' => $acceptances,
        ]);
    }

    public function privacyRequest(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);
        $data = $request->validate([
            'kind' => ['required', 'in:access,correction,export,erasure,restriction,objection,consent_withdraw,complaint,marketing'],
            'detail' => ['nullable', 'string', 'max:2000'],
        ]);

        PrivacyRequest::query()->create([
            'user_id' => $user->id,
            'kind' => $data['kind'],
            'status' => PrivacyRequest::STATUS_OPEN,
            'detail' => $data['detail'] ?? null,
        ]);

        return back()->with('status', __('loop.privacy_request_sent'));
    }
}
