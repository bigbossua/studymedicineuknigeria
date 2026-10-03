<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\FunnelEvent;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use App\Models\User;
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
        $data = $request->validate(['amount' => 'nullable|numeric|min:0', 'stripe_price_id' => 'nullable|string|max:64']);
        $price->update(['amount_minor' => $data['amount'] === null || $data['amount'] === '' ? null : (int) round($data['amount'] * 100), 'stripe_price_id' => $data['stripe_price_id'] ?: null]);
        AdminAction::log('price.update', $price, $data);

        return back()->with('status', 'Price saved.');
    }

    public function redirects()
    {
        return view('admin.redirects', ['seo' => Seo::make('Redirects')->noindex(), 'redirects' => DB::table('redirects')->orderBy('from_path')->get()]);
    }

    public function redirectStore(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['from_path' => ['required', 'string', 'max:255', 'starts_with:/', 'not_regex:#^/(portal|admin|login|register|password|email|two-factor|webhooks|up)(/|$)#i'], 'to_path' => ['required', 'string', 'max:255', 'regex:#^/[^\s]*$#', 'not_regex:#^//#'], 'reason' => 'nullable|string|max:255'], ['from_path.not_regex' => 'Redirects cannot be placed over portal, admin or sign-in paths.', 'to_path.regex' => 'The destination must be a relative path on this site, starting with /.']);
        DB::table('redirects')->updateOrInsert(['from_path' => '/'.trim($data['from_path'], '/')], ['to_path' => $data['to_path'], 'reason' => $data['reason'] ?? null, 'active' => true, 'status_code' => 301, 'created_at' => now(), 'updated_at' => now()]);
        Cache::forget('redirects.map');
        AdminAction::log('redirect.store', null, $data);

        return back()->with('status', 'Redirect saved.');
    }

    public function redirectDelete(int $id)
    {
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

    public function audit()
    {
        return view('admin.audit', ['seo' => Seo::make('Audit log')->noindex(), 'actions' => AdminAction::with('admin')->latest('created_at')->paginate(100)]);
    }
}
