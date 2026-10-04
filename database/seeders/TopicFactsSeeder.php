<?php

namespace Database\Seeders;

use App\Models\ReferenceFact;
use App\Models\Topic;
use Illuminate\Database\Seeder;

/**
 * Topic facts from research docs 08, 09, 10 (researched 2026-10-03). Every fact enters as VERIFY-ON-PAGE
 * (or NOT_FOUND where the research did not reach it) and is promoted only in the admin verification queue.
 */
class TopicFactsSeeder extends Seeder
{
    public function run(): void
    {
        $V = ReferenceFact::VERIFY_ON_PAGE;
        $NF = ReferenceFact::NOT_FOUND;
        $ucas = 'https://www.ucas.com/undergraduate/applying-university/ucas-undergraduate-when-apply';
        $ucat = 'https://www.ucat.ac.uk/ucat/dates-and-fees/';
        $ucatFee = 'https://www.ucat.ac.uk/ucat/registration-booking/test-fees-bursaries/';
        $gov = 'https://www.gov.uk/student-visa';
        $govMoney = 'https://www.gov.uk/student-visa/money';
        $ihs = 'https://www.gov.uk/healthcare-immigration-application/how-much-pay';
        $grad = 'https://www.gov.uk/graduate-visa';
        $gmc = 'https://www.gmc-uk.org/registration-and-licensing/join-the-register/provisional-registration';
        $mla = 'https://www.gmc-uk.org/education/medical-licensing-assessment';
        $ukfpo = 'https://foundationprogramme.nhs.uk/';

        $topics = [
            ['ucas-2027', 'UCAS 2027 entry', '2027', [
                ['applications_open', 'text', '12 May 2026', $ucas, $V, 'Adviser portal opened; students could start their applications.'],
                ['submission_opens', 'text', '1 September 2026', $ucas, $V, null],
                ['deadline_medicine', 'text', '15 October 2026, 18:00 (UK time)', $ucas, $V, 'Equal-consideration deadline for medicine, dentistry, veterinary medicine/science, and Oxford and Cambridge.'],
                ['deadline_main', 'text', '13 January 2027, 18:00 (UK time)', $ucas, $V, 'Equal-consideration deadline for most other courses.'],
                ['extra_opens', 'text', '25 February 2027', $ucas, $V, null],
                ['clearing_opens', 'text', '2 July 2027', $ucas, $V, null],
                ['final_date', 'text', '23 September 2027', $ucas, $V, 'Last date to add Clearing choices.'],
                ['max_medicine_choices', 'text', '4 of 5 choices may be medicine; the fifth must be a different course', 'https://www.ucas.com/undergraduate/applying-university/ucas-undergraduate-filling-your-application', $V, null],
                ['personal_statement_format', 'text', 'Three structured questions, 4,000 characters in total (minimum 350 per answer)', 'https://www.ucas.com/undergraduate/applying-university/writing-your-personal-statement', $V, 'Format introduced for 2026 entry and continuing for 2027.'],
                ['document_upload', 'text', 'Applicants can upload supporting documents (up to 30 files, 5 MB each) such as certificates, transcripts, translations, English certificates and passport', 'https://www.ucas.com/', $V, 'New for 2027 entry.'],
                ['application_fee_gbp', 'number', 34.50, 'https://www.ucas.com/undergraduate/applying-university/how-apply-undergraduate-courses', $NF, 'Figure seen on secondary sources only; confirm on ucas.com.'],
                ['agent_statement', 'text', 'UCAS states applicants are not disadvantaged by applying without an agent; agents may register as UCAS centres (a UK university reference is required)', 'https://www.ucas.com/advisers/international-advisers', $V, null],
            ]],
            ['ucat-2026', 'UCAT 2026 (for 2027 entry)', '2027', [
                ['registration_window', 'text', '20 May – 16 September 2026', $ucat, $V, null],
                ['booking_opens', 'text', '23 June 2026', $ucat, $V, null],
                ['testing_window', 'text', '13 July – 24 September 2026', $ucat, $V, null],
                ['results_to_universities', 'text', 'Early November 2026', $ucat, $V, null],
                ['booking_deadline', 'text', '16 September 2026, 15:00 (UK time); registration closes at the same time, no exceptions', $ucat, $V, null],
                ['access_arrangements_deadline', 'text', '10 September 2026, 15:00 (UK time)', $ucat, $V, 'Extra time or other adjustments must be approved before booking the test.'],
                ['results_to_candidates', 'text', 'Candidates receive their score report shortly after the test (reported); official wording not yet located', $ucat, $NF, 'Confirm on ucat.ac.uk before showing.'],
                ['subtests', 'text', 'Verbal Reasoning 44 questions / 22 minutes; Decision Making 35 / 37; Quantitative Reasoning 36 / 26; Situational Judgement about 69 / 26. Each cognitive subtest scaled 300–900; SJT reported in Bands 1–4', 'https://www.ucat.ac.uk/ucat/test-format/', $NF, 'Reported by secondary sources; confirm counts and timings on the official test-format page.'],
                ['structure', 'text', 'Verbal Reasoning, Decision Making, Quantitative Reasoning, plus the Situational Judgement Test. Abstract Reasoning was removed from 2025. Cognitive total is scored out of 2700', 'https://www.ucat.ac.uk/ucat/test-format/', $V, 'Older benchmarks out of 3600 no longer apply.'],
                ['fee_uk_gbp', 'number', 70, $ucatFee, $NF, 'Seen on secondary sources; confirm on ucat.ac.uk.'],
                ['fee_international_gbp', 'number', 115, $ucatFee, $NF, 'Seen on secondary sources; confirm on ucat.ac.uk.'],
                ['test_centres_nigeria', 'text', 'Delivered at Pearson VUE test centres; Lagos and Abuja reported as the practical options, with limited capacity', 'https://www.ucat.ac.uk/ucat/registration-booking/test-centres/', $NF, 'Official centre list not reached in research; confirm.'],
            ]],
            ['student-visa', 'UK Student visa (Nigeria → UK)', '2026', [
                ['work_term_time', 'text', 'Up to 20 hours per week during term time for degree-level students; full-time in vacations; no self-employment', $gov, $V, null],
                ['dependants', 'text', 'Undergraduate students cannot bring dependants (rule in force since January 2024)', $gov, $V, null],
                ['maintenance_london_monthly_gbp', 'number', 1483, $govMoney, ReferenceFact::SOURCE_CHANGED, 'From January 2025. 2026-10-04: a search snippet of the GOV.UK money page showed £1,529 a month (London); the page appears to have changed. Read the page and record the current figure before verifying.'],
                ['maintenance_outside_london_monthly_gbp', 'number', 1136, $govMoney, ReferenceFact::SOURCE_CHANGED, 'From January 2025. 2026-10-04: a search snippet of the GOV.UK money page showed £1,171 a month (outside London); the page appears to have changed. Read the page and record the current figure before verifying.'],
                ['application_fee_gbp', 'number', null, $gov, $NF, 'Not reached in research; confirm on GOV.UK.'],
                ['ihs_per_year_gbp', 'number', 1035, $ihs, $V, null],
                ['tb_test', 'text', 'Applicants from Nigeria must take a tuberculosis test at an approved clinic before applying', 'https://www.gov.uk/tb-test-visa', $V, 'Clinic fee set by the clinic.'],
            ]],
            ['graduate-visa', 'Graduate visa (post-study work)', '2027', [
                ['length', 'text', '18 months for applications made on or after 1 January 2027 (previously 2 years)', $grad, $V, 'Statement of Changes 14 October 2025; applies to anyone applying from 1 January 2027, so to all 2027 entrants.'],
                ['fee_gbp', 'number', null, $grad, $NF, 'Figures of £880 and £937 seen on secondary sources — conflicting; confirm on GOV.UK.'],
            ]],
            ['gmc-registration', 'GMC registration and Foundation training', '2027', [
                ['mla', 'text', 'All UK medical graduates from the 2024/25 academic year onwards must pass the Medical Licensing Assessment (AKT and CPSA) as part of their degree before provisional registration', $mla, $V, 'Nationality is not a criterion.'],
                ['provisional_registration', 'text', 'UK graduates apply to the GMC for provisional registration to begin Foundation Year 1', $gmc, $V, null],
                ['foundation_eligibility', 'text', 'International graduates of UK medical schools apply to the UK Foundation Programme on the same basis as home graduates; right-to-work evidence is required after allocation and visa sponsorship is arranged for those who need it', $ukfpo, $V, null],
                ['f1_visa_route', 'text', 'Health and Care Worker visa (Skilled Worker route) with the employing trust as sponsor', 'https://www.gov.uk/health-care-worker-visa', $V, null],
                ['prioritisation_proposal', 'text', 'The Government\'s 10 Year Health Plan (July 2025) and a Medical Training (Prioritisation) Bill propose prioritising UK medical graduates for specialty training from 2026–2027. Whether international UK graduates are in the priority group is not yet confirmed', 'https://commonslibrary.parliament.uk/', ReferenceFact::ARCHIVED, 'Superseded 2026-10-04 by prioritisation_act (the Bill appears to have become an Act). Previously: proposed / in progress, not settled law.'],
                ['prioritisation_act', 'text', 'The Medical Training (Prioritisation) Act 2026 (2026 c. 7) defines a "UK medical graduate" as a holder of a primary UK qualification under the Medical Act 1983, excluding anyone who spent all or most of the training for it outside the British Islands', 'https://www.legislation.gov.uk/ukpga/2026/7', $V, 'From a 2026-10-04 search snippet of legislation.gov.uk; read the Act (definition section and commencement) before verifying. Royal Assent date (reported 5 March 2026) and application to Foundation Programme allocation come from BMA pages, not the Act: confirm separately.'],
            ]],
            ['costs-2026', 'Other costs of studying Medicine in the UK', '2026/27', [
                ['living_costs_note', 'text', 'University cost-of-living pages and UKCISA guidance were not reached in research', 'https://www.ukcisa.org.uk/', $NF, 'Gap list, research 08 §4.'],
                ['funding_note', 'text', 'Funding for international medicine students is limited: a few universities publish small partial scholarships (for example 5% of fees); most exclude Medicine from international fee discounts', 'https://www.hyms.ac.uk/', $V, 'Examples: HYMS International Excellence Scholarship (5%, 2025 entry). Confirm each year.'],
            ]],
        ];

        foreach ($topics as [$slug, $title, $cycle, $facts]) {
            $t = Topic::updateOrCreate(['slug' => $slug], ['title' => $title, 'cycle' => $cycle]);
            foreach ($facts as [$key, $type, $value, $src, $status, $notes]) {
                $existing = $t->facts()->where('key', $key)->first();
                $attrs = ['value_text' => $type === 'text' ? $value : null, 'value_number' => $type === 'number' ? $value : null, 'source_url' => $src, 'source_type' => 'official', 'applies_to' => 'international', 'academic_year' => $cycle, 'notes' => $notes];
                if ($existing) {
                    // A verified fact belongs to the reviewer (admin queue or smukn:facts-import): re-seeding must never
                    // replace its value or source, or a corrected value would be overwritten while still showing VERIFIED.
                    if ($existing->verification_status === ReferenceFact::VERIFIED) {
                        continue;
                    }
                    $existing->update($attrs + ['verification_status' => $status]);
                } else {
                    $t->facts()->create($attrs + ['key' => $key, 'verification_status' => $status]);
                }
            }
        }
    }
}
