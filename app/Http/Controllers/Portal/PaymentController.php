<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ServiceTier;
use App\Models\TierPrice;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Services\Applications\StageResolver;
use App\Services\Payments\StripeService;
use App\Support\Seo;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private StripeService $stripe, private StageResolver $stages) {}

    public function index(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $application->load('tier.prices', 'payments.tierPrice.tier');

        return view('portal.payments.index', ['seo' => Seo::make('Confirm your service and pay')->noindex(), 'application' => $application, 'stripeEnabled' => $this->stripe->enabled(),
            'prices' => $application->tier?->prices->filter(fn ($p) => $p->amount_minor !== null) ?? collect(),
            'tiers' => ServiceTier::where('active', true)->with('prices')->orderBy('sort')->get(),
            'canChange' => $this->canChangeService($application), 'cancelled' => $request->boolean('cancelled')]);
    }

    public function checkout(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        // Only the price record is taken from the form, never an amount; it must belong to this application's service.
        $data = $request->validate(['tier_price_id' => 'required|integer|exists:tier_prices,id', 'accept_terms' => 'required|accepted']);
        $price = TierPrice::with('tier')->where('active', true)->findOrFail($data['tier_price_id']);
        abort_unless($price->service_tier_id === $application->service_tier_id && $price->amount_minor !== null, 403);
        if ($application->isTerminal()) {
            return back()->with('error', 'This application is closed, so no payment can be taken for it.');
        }
        if ($application->payments()->where('tier_price_id', $price->id)->whereIn('status', ['SUCCEEDED', 'MANUAL_REVIEW'])->exists()) {
            return redirect()->route('portal.payments.index', $application)->with('status', 'This service fee is already paid or awaiting confirmation; nothing more is due.');
        }
        if (! $this->stripe->enabled()) {
            return back()->with('error', 'Online card payment is not enabled yet. Please use the bank transfer option below.');
        }
        $payment = $this->stripe->createCheckout($application, $price, $price->tier->terms_version);

        return redirect()->away($payment->checkout_url);
    }

    /** Before anything is paid, a student may switch to another service; open card checkouts are expired first. */
    public function changeService(Request $request, Application $application)
    {
        abort_unless($application->user_id === auth()->id(), 403);
        $data = $request->validate(['service_tier_id' => 'required|integer|exists:service_tiers,id']);
        if (! $this->canChangeService($application)) {
            return back()->with('error', 'The service can no longer be changed here because a payment has been made or is being confirmed. Message our team if you need a different service.');
        }
        $tier = ServiceTier::where('active', true)->findOrFail($data['service_tier_id']);
        if ($tier->id === $application->service_tier_id) {
            return back();
        }
        foreach ($application->payments()->where('status', 'INITIATED')->get() as $open) {
            if ($open->stripe_checkout_session_id && $this->stripe->enabled()) {
                try {
                    $this->stripe->client()->checkout->sessions->expire($open->stripe_checkout_session_id);
                } catch (\Throwable $e) {
                    // already expired or completed at Stripe: the webhook decides; a completed one is caught by the tier check
                }
            }
            $open->update(['status' => 'EXPIRED']);
        }
        $from = $application->tier?->name;
        $application->forceFill(['service_tier_id' => $tier->id])->save();
        $application->record('service.changed', ['from' => $from, 'to' => $tier->name], $request->user()->id);
        $this->stages->sync($application->refresh());

        return redirect()->route('portal.payments.index', $application)->with('status', 'Your service is now '.$tier->name.'.');
    }

    private function canChangeService(Application $application): bool
    {
        return ! $application->isTerminal() && ! $application->payments()->whereIn('status', ['SUCCEEDED', 'MANUAL_REVIEW', 'REFUNDED_PARTIAL', 'DISPUTED'])->exists();
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
        $price = TierPrice::where('active', true)->findOrFail($data['tier_price_id']);
        abort_unless($price->service_tier_id === $application->service_tier_id && $price->amount_minor !== null, 403);
        if ($application->isTerminal() || $application->payments()->where('tier_price_id', $price->id)->whereIn('status', ['SUCCEEDED', 'MANUAL_REVIEW'])->exists()) {
            return back()->with('status', 'Nothing more is due for this service fee.');
        }
        $application->payments()->create(['tier_price_id' => $price->id, 'status' => 'MANUAL_REVIEW', 'amount_minor' => $price->amount_minor, 'currency' => $price->currency, 'method' => 'MANUAL_TRANSFER', 'terms_version_accepted' => $price->tier->terms_version ?? 'v1', 'note' => $data['reference'] ?? null]);
        $application->record('payment.manual_requested', ['amount' => $price->formatted()], $request->user()->id);
        User::where('role', 'admin')->get()->each->notify(new StaffNotification('Bank transfer to confirm — '.$application->application_number, ['Amount '.$price->formatted().'. Reference: '.($data['reference'] ?? 'none').'.'], route('admin.applications.show', $application)));

        return back()->with('status', 'Thank you. We will confirm your transfer within one working day and update your application.');
    }
}
