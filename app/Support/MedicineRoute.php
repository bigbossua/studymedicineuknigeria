<?php

namespace App\Support;

/**
 * The flagship knowledge-graph chain (docs/seo/KNOWLEDGE-GRAPH.md): the order a Nigerian applicant moves through
 * the Medicine guide, from the course itself to Apply Online. Rendered by <x-route-map> on every Medicine page so each
 * spoke links up to the Medicine hub and forward to its next step. Registration and Career join the chain only when
 * the working-in-the-UK page passes its publish gate, so the chain never sends readers to a noindex draft.
 */
class MedicineRoute
{
    public const WORKING_GATE = 'topics-verified:student-visa,graduate-visa,gmc-registration';

    /** Route name → step key, for the journey stepper in each page head. */
    private const BY_ROUTE = [
        'medicine.index' => 'course', 'medicine.nigeria' => 'course', 'medicine.foundation' => 'qualifications',
        'requirements.index' => 'requirements', 'requirements.alevels' => 'requirements', 'requirements.english' => 'requirements',
        'requirements.waec' => 'qualifications', 'requirements.neco' => 'qualifications', 'requirements.gem' => 'qualifications',
        'schools.index' => 'universities', 'schools.show' => 'universities', 'fees.index' => 'fees', 'fees.total' => 'fees',
        'admissions.index' => 'application', 'admissions.ucat' => 'application', 'admissions.ucas2027' => 'application', 'admissions.howto' => 'application',
        'working.index' => 'career', 'apply.eligibility' => 'eligibility', 'apply.index' => 'apply', 'apply.services' => 'apply',
    ];

    /** The step a page belongs to, or null for pages outside the journey (organisation, legal, FAQ). */
    public static function currentKey(?string $routeName): ?string
    {
        return self::BY_ROUTE[$routeName ?? ''] ?? null;
    }

    /** @return list<array{key: string, label: string, route: string}> */
    public static function steps(): array
    {
        $steps = [
            ['key' => 'course', 'label' => 'The course', 'route' => 'medicine.index'],
            ['key' => 'requirements', 'label' => 'Entry requirements', 'route' => 'requirements.index'],
            ['key' => 'qualifications', 'label' => 'Your Nigerian qualifications', 'route' => 'requirements.waec'],
            ['key' => 'universities', 'label' => 'UK medical schools', 'route' => 'schools.index'],
            ['key' => 'fees', 'label' => 'Fees and costs', 'route' => 'fees.index'],
            ['key' => 'application', 'label' => 'How to apply', 'route' => 'admissions.howto'],
        ];
        if (PublishGate::passes(self::WORKING_GATE)) {
            $steps[] = ['key' => 'career', 'label' => 'Registration and work', 'route' => 'working.index'];
        }
        $steps[] = ['key' => 'eligibility', 'label' => 'Check your eligibility', 'route' => 'apply.eligibility'];
        $steps[] = ['key' => 'apply', 'label' => 'Apply Online', 'route' => 'apply.index'];

        return $steps;
    }
}
