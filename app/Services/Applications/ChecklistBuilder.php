<?php

namespace App\Services\Applications;

use App\Enums\DocumentStatus;
use App\Models\Application;
use App\Models\ChecklistRule;
use Illuminate\Support\Arr;

/** Builds / refreshes the personalised document checklist from checklist_rules (docs/architecture/14.3). */
class ChecklistBuilder
{
    public function refresh(Application $a): void
    {
        $form = $a->form ?? [];
        $required = []; // code => reason
        foreach (ChecklistRule::where('active', true)->orderBy('sort')->get() as $rule) {
            if ($this->matches($rule->predicate, $form)) {
                foreach ($rule->require_codes as $code) {
                    $required[$code] ??= $rule->reason_text;
                }
            }
        }
        $existing = $a->documents()->get()->keyBy('code');
        foreach ($required as $code => $reason) {
            if (! isset($existing[$code])) {
                $doc = $a->documents()->create(['code' => $code, 'title' => DocumentCatalogue::title($code), 'status' => DocumentStatus::REQUIRED, 'required_reason' => $reason]);
                $doc->events()->create(['from_status' => null, 'to_status' => 'REQUIRED', 'reason' => 'rule: '.$reason]);
            } elseif ($existing[$code]->status === DocumentStatus::NOT_REQUIRED) {
                $existing[$code]->transition(DocumentStatus::REQUIRED, null, $reason);
            }
        }
        // Rule-derived items no longer required (and never uploaded) are marked NOT_REQUIRED; staff requests are kept.
        foreach ($existing as $code => $doc) {
            if (! isset($required[$code]) && $doc->requested_by === null && $doc->current_version_id === null && $doc->status === DocumentStatus::REQUIRED) {
                $doc->transition(DocumentStatus::NOT_REQUIRED, null, 'no longer applies to your answers');
            }
        }
    }

    public function matches(array $p, array $form): bool
    {
        if (! empty($p['always'])) {
            return true;
        }
        if (isset($p['all'])) {
            return collect($p['all'])->every(fn ($q) => $this->matches($q, $form));
        }
        if (isset($p['any'])) {
            return collect($p['any'])->contains(fn ($q) => $this->matches($q, $form));
        }
        $value = Arr::get($form, $p['field'] ?? '');

        return match ($p['op'] ?? 'eq') {
            'eq' => $value == ($p['value'] ?? null),
            'in' => in_array($value, (array) ($p['value'] ?? []), true),
            'truthy' => (bool) $value,
            'contains' => is_array($value) && collect($value)->pluck($p['key'] ?? null)->filter()->contains($p['value'] ?? null)
                || (is_array($value) && in_array($p['value'] ?? null, $value, true)),
            'count_gte' => is_array($value) && count($value) >= ($p['value'] ?? 1),
            default => false,
        };
    }
}
