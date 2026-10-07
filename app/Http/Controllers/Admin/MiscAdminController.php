<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\FunnelEvent;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Profession;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use App\Models\User;
use App\Services\Search\SearchConsole;
use App\Services\Search\SearchInsights;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MiscAdminController extends Controller
{
    public function leads(Request $request)
    {
        $q = Lead::latest();
        if ($s = $request->string('status')->toString()) {
            $q->where('status', $s);
        }

        return view('admin.leads', ['seo' => Seo::make('Leads')->noindex(), 'leads' => $q->paginate(50)->withQueryString()]);
    }

    public function leadStatus(Request $request, Lead $lead)
    {
        $lead->update($request->validate(['status' => 'required|in:new,contacted,qualified,converted,closed']));
        AdminAction::log('lead.status', $lead, $lead->only('status'));

        return back()->with('status', 'Lead updated.');
    }

    public function payments(Request $request)
    {
        $q = Payment::with('application.user', 'tierPrice.tier')->latest();
        if ($s = $request->string('status')->toString()) {
            $q->where('status', $s);
        }

        return view('admin.payments', ['seo' => Seo::make('Payments')->noindex(), 'payments' => $q->paginate(50)->withQueryString(), 'totals' => Payment::where('status', 'SUCCEEDED')->selectRaw('currency, sum(amount_minor) s')->groupBy('currency')->pluck('s', 'currency')]);
    }

    public function tiers()
    {
        return view('admin.tiers', ['seo' => Seo::make('Services and prices')->noindex(), 'tiers' => ServiceTier::with('prices')->orderBy('sort')->get()]);
    }

    public function priceUpdate(Request $request, TierPrice $price)
    {
        abort_unless($request->user()->isAdmin(), 403); // prices are what students pay: admin only, like roles and redirects
        $data = $request->validate(['amount' => 'nullable|numeric|min:0|max:100000']);
        $before = $price->amount_minor;
        $price->update(['amount_minor' => ($data['amount'] ?? null) === null || $data['amount'] === '' ? null : (int) round($data['amount'] * 100)]);
        if ($price->amount_minor !== $before) {
            // Stripe Prices are fixed: the next checkout creates one for the new amount and retires the old (StripeCatalog).
            $price->forceFill(['stripe_price_id' => null, 'stripe_price_amount' => null])->save();
        }
        AdminAction::log('price.update', $price, ['tier' => $price->tier?->code, 'before_minor' => $before, 'after_minor' => $price->amount_minor]);

        return back()->with('status', 'Price saved.');
    }

    public function redirects()
    {
        return view('admin.redirects', ['seo' => Seo::make('Redirects')->noindex(), 'redirects' => DB::table('redirects')->orderBy('from_path')->get()]);
    }

    public function redirectStore(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        // Normalise before validating: '//login' would pass the reserved-path rule and then be stored as '/login'.
        if (trim((string) $request->input('from_path'), '/ ') !== '') {
            $request->merge(['from_path' => '/'.trim((string) $request->input('from_path'), '/')]);
        }
        $data = $request->validate(['from_path' => ['required', 'string', 'max:255', 'starts_with:/', 'not_in:/', 'not_regex:#^/(portal|admin|login|register|password|email|two-factor|webhooks|up)(/|$)#i'], 'to_path' => ['required', 'string', 'max:255', 'regex:#^/[^\s]*$#', 'not_regex:#^//#'], 'reason' => 'nullable|string|max:255'], ['from_path.not_regex' => 'Redirects cannot be placed over portal, admin or sign-in paths.', 'to_path.regex' => 'The destination must be a relative path on this site, starting with /.']);
        DB::table('redirects')->updateOrInsert(['from_path' => '/'.trim($data['from_path'], '/')], ['to_path' => $data['to_path'], 'reason' => $data['reason'] ?? null, 'active' => true, 'status_code' => 301, 'created_at' => now(), 'updated_at' => now()]);
        Cache::forget('redirects.map');
        AdminAction::log('redirect.store', null, $data);

        return back()->with('status', 'Redirect saved.');
    }

    public function redirectDelete(Request $request, int $id)
    {
        abort_unless($request->user()->isAdmin(), 403);
        DB::table('redirects')->where('id', $id)->delete();
        Cache::forget('redirects.map');
        AdminAction::log('redirect.delete', null, ['id' => $id]);

        return back()->with('status', 'Redirect removed.');
    }

    public function users()
    {
        return view('admin.users', ['seo' => Seo::make('Users')->noindex(), 'users' => User::withCount('applications')->orderBy('role')->orderBy('name')->paginate(50)]);
    }

    public function userRole(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['role' => 'required|in:student,staff,admin']);
        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.');
        }
        $user->update($data);
        AdminAction::log('user.role', $user, $data);

        return back()->with('status', 'Role updated.');
    }

    public function userTwoFactorReset(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403);
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Reset your own authenticator from the server shell (smukn:two-factor-reset), not from here.');
        }
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        AdminAction::log('two_factor.reset', $user);

        return back()->with('status', "Two-step verification removed for {$user->email}. They will enrol again at next sign-in.");
    }

    public function funnel(Request $request)
    {
        $days = (int) $request->integer('days', 30);
        $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
        $since = now()->subDays($days);
        $counts = FunnelEvent::where('occurred_at', '>=', $since)->selectRaw('name, count(*) c, count(distinct coalesce(application_hash, visitor_hash, user_id)) u')->groupBy('name')->get()->keyBy('name');
        $rows = [];
        $prev = null;
        foreach (FunnelEvent::ORDER as $name) {
            $u = (int) ($counts[$name]->u ?? 0);
            $rows[] = ['name' => $name, 'events' => (int) ($counts[$name]->c ?? 0), 'unique' => $u, 'of_previous' => $prev ? ($prev > 0 ? round($u / $prev * 100) : null) : null];
            if ($u > 0) {
                $prev = $u;
            }
        }
        $other = $counts->except(FunnelEvent::ORDER)->sortByDesc('c');
        $byTier = FunnelEvent::where('occurred_at', '>=', $since)->whereIn('name', ['application_started', 'payment_completed', 'submitted'])->whereNotNull('tier')->selectRaw('tier, name, count(distinct application_hash) c')->groupBy('tier', 'name')->get()->groupBy('tier');
        $sources = FunnelEvent::where('occurred_at', '>=', $since)->where('name', 'lead_created')->selectRaw("coalesce(json_extract(utm, '$.utm_source'), '(direct / organic)') src, count(*) c")->groupBy('src')->orderByDesc('c')->take(10)->get();

        return view('admin.funnel', ['seo' => Seo::make('Funnel')->noindex(), 'days' => $days, 'rows' => $rows, 'other' => $other, 'byTier' => $byTier, 'sources' => $sources, 'total' => FunnelEvent::count()]);
    }

    /** Search Console data (smukn:gsc-sync): index status, totals and the opportunity lists the organic-growth loop acts on. */
    public function search(Request $request)
    {
        $days = (int) $request->integer('days', 28);
        $days = in_array($days, [7, 28, 90], true) ? $days : 28;
        $in = new SearchInsights($days);

        return view('admin.search', [
            'seo' => Seo::make('Search')->noindex(), 'days' => $days, 'connected' => SearchConsole::configured(), 'property' => SearchConsole::property(),
            'account' => SearchConsole::accountEmail(), 'sync' => Cache::get('gsc.last_sync'), 'hasData' => $in->hasData(), 'period' => $in->period(),
            'totals' => $in->hasData() ? $in->totals() : null, 'index' => $in->indexStatus(), 'nigeria' => $in->nigeriaQueries(50), 'gaining' => $in->pagesGaining(),
            'lowCtr' => $in->lowCtrPages(), 'split' => $in->cannibalisation(), 'new' => $in->newQueries(), 'schools' => $in->schoolDemand(),
        ]);
    }

    /** The SEO decision register (data/seo/decision-register.csv) with a status filter; read-only, edited in the repository. */
    public function seo(Request $request)
    {
        $rows = [];
        if (($h = @fopen(base_path('data/seo/decision-register.csv'), 'r')) !== false) {
            $header = fgetcsv($h);
            while (($r = fgetcsv($h)) !== false) {
                $rows[] = array_combine($header, $r);
            }
            fclose($h);
        }
        $rows = collect($rows);
        $status = strtoupper($request->string('status')->toString());
        $counts = $rows->countBy('status')->sortKeys();
        $shown = $status ? $rows->where('status', $status) : $rows;

        return view('admin.seo', ['seo' => Seo::make('SEO decisions')->noindex(), 'rows' => $shown->sortByDesc('relevance_score')->values(), 'counts' => $counts, 'status' => $status, 'total' => $rows->count()]);
    }

    /** The healthcare course universe: every subject with its evidence status (data/healthcare/subjects.json). */
    public function professions()
    {
        $items = Profession::withCount('courses')->orderByDesc('flagship')->orderBy('name')->get();

        return view('admin.professions', ['seo' => Seo::make('Subjects')->noindex(), 'items' => $items, 'counts' => $items->countBy('status')->sortKeys()]);
    }

    public function audit()
    {
        return view('admin.audit', ['seo' => Seo::make('Audit log')->noindex(), 'actions' => AdminAction::with('admin')->latest('created_at')->paginate(100)]);
    }
}
