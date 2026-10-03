<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
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
        $data = $request->validate(['from_path' => 'required|string|max:255|starts_with:/', 'to_path' => 'required|string|max:255', 'reason' => 'nullable|string|max:255']);
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

    public function audit()
    {
        return view('admin.audit', ['seo' => Seo::make('Audit log')->noindex(), 'actions' => AdminAction::with('admin')->latest('created_at')->paginate(100)]);
    }
}
