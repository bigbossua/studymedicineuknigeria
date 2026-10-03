<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\TierPrice;
use App\Services\Applications\StageResolver;
use App\Services\Payments\StripeService;
use App\Support\Seo;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private StripeService $stripe, private StageResolver $stages) {}

    public function index(Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $application->load('tier.prices', 'payments.tierPrice');

        return view('portal.payments.index', ['seo' => Seo::make('Payments')->noindex(), 'application' => $application, 'stripeEnabled' => $this->stripe->enabled(),
            'prices' => $application->tier?->prices->filter(fn ($p) => $p->amount_minor !== null) ?? collect()]);
    }

    public function checkout(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $data = $request->validate(['tier_price_id' => 'required|exists:tier_prices,id', 'accept_terms' => 'required|accepted']);
        $price = TierPrice::with('tier')->findOrFail($data['tier_price_id']);
        abort_unless($price->service_tier_id === $application->service_tier_id && $price->amount_minor !== null, 403);
        if (! $this->stripe->enabled()) return back()->with('error', 'Online card payment is not enabled yet. Please use the bank transfer option below.');
        $payment = $this->stripe->createCheckout($application, $price, $price->tier->terms_version);

        return redirect()->away($payment->checkout_url);
    }

    public function return(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        // Webhook is the source of truth; the return page only reflects current state.
        $payment = $application->payments()->where('stripe_checkout_session_id', $request->query('session_id'))->first();

        return view('portal.payments.return', ['seo' => Seo::make('Payment status')->noindex(), 'application' => $application, 'payment' => $payment]);
    }

    public function manualTransfer(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $data = $request->validate(['tier_price_id' => 'required|exists:tier_prices,id', 'accept_terms' => 'required|accepted', 'reference' => 'nullable|string|max:120']);
        $price = TierPrice::findOrFail($data['tier_price_id']);
        abort_unless($price->service_tier_id === $application->service_tier_id && $price->amount_minor !== null, 403);
        $application->payments()->create(['tier_price_id' => $price->id, 'status' => 'MANUAL_REVIEW', 'amount_minor' => $price->amount_minor, 'currency' => $price->currency, 'method' => 'MANUAL_TRANSFER', 'terms_version_accepted' => $price->tier->terms_version ?? 'v1', 'note' => $data['reference'] ?? null]);
        $application->record('payment.manual_requested', ['amount' => $price->formatted()], $request->user()->id);
        \App\Models\User::where('role', 'admin')->get()->each->notify(new \App\Notifications\StaffNotification('Bank transfer to confirm — '.$application->application_number, ['Amount '.$price->formatted().'. Reference: '.($data['reference'] ?? 'none').'.'], route('admin.applications.show', $application)));

        return back()->with('status', 'Thank you. We will confirm your transfer within one working day and update your application.');
    }
}
