<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lead;
use App\Models\ReferenceFact;
use App\Models\ServiceTier;
use App\Models\Topic;
use App\Models\University;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Support\Funnel;
use App\Support\PublishGate;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Release-1 public pages (docs/decision/page-asset-register.md, BUILD NOW rows). Every specific number, date or
 * university statement is a ReferenceFact rendered with its verification status; prose explains, facts prove.
 */
class ContentController extends Controller
{
    private const REVIEWED = '2026-10-03';

    private function seo(string $title, string $desc, string $route, array $crumbs, bool $article = true): Seo
    {
        $s = Seo::make($title, $desc)->canonical(route($route))->breadcrumbs($crumbs)->reviewed(self::REVIEWED, '2027');

        return $article ? $s->article() : $s;
    }

    private function statements(string $key): Collection
    {
        return ReferenceFact::with('subject')->where('subject_type', University::class)->where('key', $key)
            ->whereNotIn('verification_status', [ReferenceFact::NOT_FOUND, ReferenceFact::ARCHIVED])->get()
            ->sortBy(fn ($f) => $f->subject?->name)->values();
    }

    // ---------------- Medicine pillar ----------------
    public function medicine()
    {
        return view('content.medicine.index', ['seo' => $this->seo('Study Medicine in the UK: routes, requirements and costs', 'How UK medical degrees work for international applicants: standard, graduate and foundation routes, what schools require, costs and the calendar to plan around.', 'medicine.index', [['label' => 'Medicine']]),
            'schools' => University::whereIn('international_policy', ['accepts', 'international_only'])->count(), 'ucas' => Topic::bySlug('ucas-2027'), 'ucat' => Topic::bySlug('ucat-2026')]);
    }

    public function nigeria()
    {
        $faqs = collect($this->faqItems())->whereIn('id', [1, 12, 18, 22, 26, 33])->values();
        $seo = $this->seo('Study Medicine in the UK from Nigeria: 2027 and 2028 entry', 'What a Nigerian student with WAEC, NECO, A-levels or a degree must know before applying to UK medicine: open routes, what schools publish, costs and key dates.', 'medicine.nigeria', [['label' => 'Medicine', 'url' => route('medicine.index')], ['label' => 'From Nigeria']]);
        $seo->jsonLd(['@type' => 'FAQPage', 'mainEntity' => $faqs->map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['a'])]])->all()]);

        return view('content.medicine.nigeria', ['seo' => $seo, 'waec' => $this->statements('waec_neco_statement'), 'ucas' => Topic::bySlug('ucas-2027'), 'ucat' => Topic::bySlug('ucat-2026'), 'fees' => $this->feeRows(), 'faqs' => $faqs,
            'accepting' => University::whereIn('international_policy', ['accepts', 'international_only'])->count(), 'homeOnly' => University::where('international_policy', 'home_only')->count()]);
    }

    public function foundation()
    {
        return view('content.medicine.foundation', ['seo' => $this->seo('Foundation and gateway routes to UK Medicine', 'Which foundation years publish Medicine as a destination, which are home-only, and how a WASSCE or NECO holder can use them, each statement with its source.', 'medicine.foundation', [['label' => 'Medicine', 'url' => route('medicine.index')], ['label' => 'Foundation routes']]),
            'routes' => $this->statements('foundation_route'), 'foundationCourses' => Course::with('university')->where('entry_type', 'foundation')->get()]);
    }

    // ---------------- Requirements ----------------
    public function requirements()
    {
        return view('content.requirements.index', ['seo' => $this->seo('Requirements to study Medicine in the UK from Nigeria', 'What every UK medical school looks at: qualifications, admissions tests, English, references and deadlines, and what Nigerian applicants specifically must check.', 'requirements.index', [['label' => 'Requirements']]),
            'ucas' => Topic::bySlug('ucas-2027'), 'ucat' => Topic::bySlug('ucat-2026'), 'english' => $this->statements('english_requirement')->count()]);
    }

    public function waec()
    {
        $st = $this->statements('waec_neco_statement');
        $eng = $this->statements('english_requirement')->filter(fn ($f) => preg_match('/WAEC|WASSCE|NECO/i', $f->value_text ?? ''));

        return view('content.requirements.waec', ['seo' => $this->seo('WAEC (WASSCE) and UK Medicine: what schools publish', 'Can you study Medicine in the UK with WAEC? School by school, what UK medical schools publish about WASSCE, where WAEC English counts, and which routes are open.', 'requirements.waec', [['label' => 'Requirements', 'url' => route('requirements.index')], ['label' => 'WAEC']]),
            'specific' => $st->filter(fn ($f) => str_contains((string) $f->notes, 'Medicine-specific')), 'general' => $st->reject(fn ($f) => str_contains((string) $f->notes, 'Medicine-specific')), 'english' => $eng, 'qualification' => 'WAEC']);
    }

    public function neco()
    {
        $st = $this->statements('waec_neco_statement');

        return view('content.requirements.neco', ['seo' => $this->seo('NECO and UK Medicine: what medical schools say', 'Whether UK medical schools accept NECO for Medicine, how NECO is treated compared with WASSCE, where NECO English counts, and what route a NECO holder can take.', 'requirements.neco', [['label' => 'Requirements', 'url' => route('requirements.index')], ['label' => 'NECO']]),
            'mentionsNeco' => $st->filter(fn ($f) => stripos($f->value_text ?? '', 'NECO') !== false), 'all' => $st]);
    }

    public function alevels()
    {
        return view('content.requirements.alevels', ['seo' => $this->seo('A-levels for UK Medicine from Nigeria: grades and subjects', 'Typical A-level and IB requirements for UK medicine (A100), the compulsory subjects, and how Cambridge International A-levels taken in Nigeria are treated.', 'requirements.alevels', [['label' => 'Requirements', 'url' => route('requirements.index')], ['label' => 'A-levels']]),
            'reqs' => $this->statements('a_level_requirement')]);
    }

    public function gem()
    {
        return view('content.requirements.gem', ['seo' => $this->seo('Graduate Entry Medicine in the UK with a Nigerian degree', 'Which UK graduate-entry medicine programmes accept international applicants, the degree class and GAMSAT or UCAT they ask for, and the standard-entry alternative.', 'requirements.gem', [['label' => 'Requirements', 'url' => route('requirements.index')], ['label' => 'Nigerian degree']]),
            'gem' => $this->statements('gem_international'), 'gemCourses' => Course::with('university')->where('entry_type', 'graduate')->get()]);
    }

    public function english()
    {
        return view('content.requirements.english', ['seo' => $this->seo('English requirements for UK Medicine: IELTS and WAEC English', 'English evidence UK medical schools publish for international applicants: typical IELTS bands for Medicine, where WAEC or NECO English counts, and the visa rule.', 'requirements.english', [['label' => 'Requirements', 'url' => route('requirements.index')], ['label' => 'English language']]),
            'nigeriaPage' => $this->statements('english_requirement'), 'courseLevel' => $this->statements('english_language_requirement')]);
    }

    // ---------------- Fees ----------------
    private function feeRows(): Collection
    {
        return ReferenceFact::with('subject.university')->where('subject_type', Course::class)->where('key', 'international_fee_gbp')->get()
            ->groupBy('subject_id')->map(fn ($g) => $g->sortByDesc('academic_year')->first())->values()
            ->filter(fn ($f) => $f->subject && $f->subject->university)->sortBy(fn ($f) => $f->subject->university->name)->values();
    }

    public function fees()
    {
        $rows = $this->feeRows();
        $pub = $rows->filter(fn ($f) => $f->isPublishable() && $f->value_number);

        return view('content.fees.index', ['seo' => $this->seo('International fees at UK medical schools, 2026/27 to 2027/28', 'International tuition fees for Medicine at every UK medical school, with fee year, clinical-year differences and an official source for each figure.', 'fees.index', [['label' => 'Fees']]),
            'rows' => $rows, 'min' => $pub->min('value_number'), 'max' => $pub->max('value_number'), 'count' => $pub->count(), 'visa' => Topic::bySlug('student-visa')]);
    }

    public function totalCost()
    {
        return view('content.fees.total', ['seo' => $this->seo('Total cost of studying Medicine in the UK from Nigeria', 'Tuition, visa, Immigration Health Surcharge, maintenance funds, tests and living costs added up for a five- or six-year UK medical degree, with every figure sourced.', 'fees.total', [['label' => 'Fees', 'url' => route('fees.index')], ['label' => 'Total cost']])->noindex(! PublishGate::passes('topics-verified:student-visa,costs-2026,ucat-2026,ucas-2027')),
            'visa' => Topic::bySlug('student-visa'), 'costs' => Topic::bySlug('costs-2026'), 'ucat' => Topic::bySlug('ucat-2026'), 'ucas' => Topic::bySlug('ucas-2027'), 'fees' => $this->feeRows()->filter(fn ($f) => $f->isPublishable() && $f->value_number)]);
    }

    // ---------------- Working in the UK ----------------
    public function working()
    {
        $gate = 'topics-verified:student-visa,graduate-visa,gmc-registration';
        $seo = $this->seo('Working in the UK during and after a medical degree', 'Student visa work rules, the MLA and GMC provisional registration, the Foundation Programme and visa sponsorship, the Graduate visa change from 2027, and what is still proposed.', 'working.index', [['label' => 'Working in the UK']])
            ->noindex(! PublishGate::passes($gate));

        return view('content.working.index', ['seo' => $seo, 'visa' => Topic::bySlug('student-visa'), 'grad' => Topic::bySlug('graduate-visa'), 'gmc' => Topic::bySlug('gmc-registration'), 'gated' => ! PublishGate::passes($gate)]);
    }

    // ---------------- Admissions ----------------
    public function admissions()
    {
        return view('content.admissions.index', ['seo' => $this->seo('UK Medicine admissions: UCAS, UCAT, interviews, how to apply', 'The UK medicine admissions process for international applicants in one place: the UCAS calendar, the UCAT, interviews, and the schools that use a different route.', 'admissions.index', [['label' => 'Admissions']]), 'ucas' => Topic::bySlug('ucas-2027'), 'ucat' => Topic::bySlug('ucat-2026')]);
    }

    public function ucat()
    {
        $courses = Course::with('university')->whereHas('university', fn ($q) => $q->whereIn('international_policy', ['accepts', 'international_only']))->get();

        return view('content.admissions.ucat', ['seo' => $this->seo('UCAT for Nigerian students: dates, fees and test centres', 'The UCAT for applicants in Nigeria: 2026 cycle dates, the three-section structure scored out of 2700, fees, Pearson VUE centres in Nigeria and who requires it.', 'admissions.ucat', [['label' => 'Admissions', 'url' => route('admissions.index')], ['label' => 'UCAT']]),
            'ucat' => Topic::bySlug('ucat-2026'), 'ucas' => Topic::bySlug('ucas-2027'), 'byTest' => $courses->groupBy(fn ($c) => $c->admissions_test ?? 'NOT_PUBLISHED')]);
    }

    public function ucas2027()
    {
        return view('content.admissions.ucas2027', ['seo' => $this->seo('UCAS deadlines for Medicine, 2027 entry (and 2028 planning)', 'Every UCAS date that matters for medicine for 2027 entry, the UCAT window before it, the interview and offer season, and the steps to visa and arrival.', 'admissions.ucas2027', [['label' => 'Admissions', 'url' => route('admissions.index')], ['label' => 'UCAS 2027']]),
            'ucas' => Topic::bySlug('ucas-2027'), 'ucat' => Topic::bySlug('ucat-2026'), 'visa' => Topic::bySlug('student-visa')]);
    }

    public function howToApply()
    {
        $direct = Course::with('university')->whereIn('application_route', ['DIRECT', 'BOTH'])->get();

        return view('content.admissions.howto', ['seo' => $this->seo('How to apply to UK Medicine from Nigeria: UCAS and direct', 'Step by step: applying through UCAS as an individual, the four-choice rule, personal statement, references, documents, and the schools that take direct applications.', 'admissions.howto', [['label' => 'Admissions', 'url' => route('admissions.index')], ['label' => 'How to apply']]),
            'ucas' => Topic::bySlug('ucas-2027'), 'direct' => $direct]);
    }

    // ---------------- Apply Online ----------------
    public function apply()
    {
        Funnel::track('apply_viewed');

        return view('content.apply.index', ['seo' => $this->seo('Apply Online: application support for Nigerian applicants', 'Create your account, enter your qualifications once, upload the documents that apply to you, approve your package and track submission. No guarantees claimed.', 'apply.index', [['label' => 'Apply Online']], false), 'tiers' => ServiceTier::where('active', true)->with('prices')->orderBy('sort')->get()]);
    }

    public function services()
    {
        $tiers = ServiceTier::where('active', true)->with('prices')->orderBy('sort')->get();
        $seo = $this->seo('Services and pricing for UK Medicine application support', 'What each level of application support includes and excludes, when work begins and our refund terms. Our fee is separate from university tuition and fees.', 'apply.services', [['label' => 'Apply Online', 'url' => route('apply.index')], ['label' => 'Services']], false);
        foreach ($tiers as $t) {
            $offer = $t->hasPrices() ? ['@type' => 'Offer', 'priceCurrency' => 'GBP', 'price' => number_format($t->prices->whereNotNull('amount_minor')->sum('amount_minor') / 100, 2, '.', '')] : null;
            $seo->jsonLd(array_filter(['@type' => 'Service', 'name' => $t->name, 'description' => $t->summary, 'provider' => ['@type' => 'Organization', 'name' => config('site.name')], 'offers' => $offer]));
        }

        return view('content.apply.services', ['seo' => $seo, 'tiers' => $tiers]);
    }

    public function eligibility()
    {
        return view('content.apply.eligibility', ['seo' => $this->seo('Check your eligibility for UK Medicine', 'Seven questions, no account needed. See which routes to UK medicine appear open on published requirements for your qualifications, and what to read next.', 'apply.eligibility', [['label' => 'Apply Online', 'url' => route('apply.index')], ['label' => 'Eligibility']], false), 'result' => session('eligibility_result')]);
    }

    public function eligibilitySubmit(Request $request)
    {
        $d = $request->validate([
            'qualification' => 'required|in:waec_only,alevels_ib,foundation,nigerian_degree,other',
            'sciences' => 'required|in:yes,partial,no', 'english' => 'required|in:ielts,waec_english,none',
            'ucat' => 'required|in:taken,planned,none', 'intake_year' => 'required|integer|min:'.(now()->year + 1).'|max:'.(now()->year + 4),
            'name' => 'required|string|max:160', 'email' => 'required|email|max:255', 'whatsapp' => 'nullable|string|max:32', 'consent' => 'required|accepted',
        ]);
        $r = $this->assess($d);
        Lead::create(['email' => strtolower($d['email']), 'name' => $d['name'], 'whatsapp' => $d['whatsapp'] ?? null, 'source_page' => route('apply.eligibility'), 'utm' => $request->only('utm_source', 'utm_medium', 'utm_campaign'), 'eligibility_answers' => collect($d)->except(['name', 'email', 'whatsapp', 'consent'])->all(), 'eligibility_result' => $r, 'status' => 'new']);
        Funnel::track('lead_created', ['qualification' => $d['qualification'], 'intake_year' => (int) $d['intake_year'], 'tier' => $r['tier'] ?? null]);
        $request->session()->put('lead', ['name' => $d['name'], 'email' => $d['email']]);
        User::where('role', 'admin')->get()->each->notify(new StaffNotification('New lead: '.$d['name'], [$d['email'].' · '.$r['summary']], route('admin.leads')));

        return redirect()->route('apply.eligibility')->with('eligibility_result', $r)->withInput();
    }

    /** Cautious, rule-based route-category assessment (docs/decision/conversion-funnel-strategy.md §3). Never an eligibility verdict. */
    private function assess(array $d): array
    {
        $routes = [];
        $reads = [];
        $tier = 'T1';
        switch ($d['qualification']) {
            case 'waec_only':
                $routes[] = ['Standard-entry Medicine (A100) directly on WASSCE/NECO', 'closed', 'None of the UK medical schools we reviewed publishes direct entry on WASSCE or NECO alone; universities that address Nigeria route applicants through A-levels, the IB or a recognised foundation year.'];
                $routes[] = ['Foundation year leading to Medicine', 'possible', 'A small number of foundation programmes publish Medicine as a destination and are open to international students. Progression is competitive and conditional.'];
                $routes[] = ['A-levels or IB first, then standard entry', 'possible', 'The most common route; typical offers are AAA to A*AA including Chemistry and Biology, plus UCAT.'];
                $reads = ['requirements.waec', 'medicine.foundation', 'requirements.alevels'];
                break;
            case 'alevels_ib':
                $routes[] = ['Standard-entry Medicine (A100)', $d['sciences'] === 'yes' ? 'open' : 'conditional', $d['sciences'] === 'yes' ? 'Appears open subject to grades (typically AAA to A*AA including Chemistry and Biology), the UCAT and English evidence.' : 'Most schools require Chemistry and Biology (or another science) at A-level; check the subject rules of each school.'];
                $reads = ['requirements.alevels', 'admissions.ucat', 'schools.index'];
                $tier = 'T2';
                break;
            case 'foundation':
                $routes[] = ['Progression from your foundation programme', 'conditional', 'Depends entirely on your provider\'s published progression agreement with named medical schools; confirm the exact conditions in writing.'];
                $reads = ['medicine.foundation', 'schools.index'];
                break;
            case 'nigerian_degree':
                $routes[] = ['Graduate Entry Medicine (A101/A102)', 'conditional', 'Only some graduate-entry programmes accept international applicants; our research confirmed few. Degree class and GAMSAT or UCAT requirements apply.'];
                $routes[] = ['Standard-entry Medicine as a graduate', 'possible', 'Many schools accept graduates onto the five-year course, often on degree class plus UCAT.'];
                $reads = ['requirements.gem', 'admissions.ucat', 'schools.index'];
                break;
            default:
                $routes[] = ['Assessment needed', 'conditional', 'Tell us more about your qualifications and we will map them to published requirements.'];
                $reads = ['requirements.index'];
        }
        if ($d['english'] !== 'ielts') {
            $routes[] = ['English language evidence', 'conditional', $d['english'] === 'waec_english' ? 'A few medical schools publish acceptance of WAEC/NECO English for Medicine; most ask for IELTS 7.0–7.5. Check the school.' : 'You will need recognised English evidence; Medicine typically requires IELTS 7.0–7.5 overall.'];
        }
        if ($d['ucat'] !== 'taken' && (int) $d['intake_year'] === 2027) {
            // Dates come from the verified topic facts when publishable; otherwise the sentence stays generic rather than quoting an unverified date.
            $window = Topic::bySlug('ucat-2026')?->fact('testing_window');
            $deadline = Topic::bySlug('ucas-2027')?->fact('deadline_medicine');
            $dates = ($window?->isPublishable() && $deadline?->isPublishable())
                ? "The UCAT 2026 testing window ran {$window->displayValue()} and the UCAS medicine deadline is {$deadline->displayValue()}."
                : 'The UCAT for 2027 entry is sat in the summer of 2026 and the UCAS medicine deadline falls in mid-October 2026.';
            $routes[] = ['2027 entry via UCAT schools', 'closed', $dates.' Without a UCAT result, only schools that do not require the UCAT remain realistic for 2027; most applicants in your position plan for 2028.'];
        }
        if ($d['ucat'] === 'none' && (int) $d['intake_year'] >= 2028) {
            $routes[] = ['UCAT', 'conditional', 'Most medical schools require the UCAT, sat in July–September of the year before entry. Plan to register in May/June '.((int) $d['intake_year'] - 1).'.'];
        }
        $open = collect($routes)->pluck(1);
        $summary = $open->contains('open') ? 'Standard entry appears open subject to grades, UCAT and English.' : ($open->contains('possible') ? 'Standard entry is not open directly; foundation or A-level/IB routes appear possible.' : 'Routes depend on conditions that need checking.');

        return ['routes' => $routes, 'reads' => $reads, 'tier' => $tier, 'summary' => $summary, 'intake_year' => (int) $d['intake_year']];
    }

    // ---------------- FAQ ----------------
    public function faqItems(): array
    {
        $r = fn ($n) => route($n);

        return [
            ['id' => 1, 'q' => 'Can I study Medicine in the UK with WAEC?', 'a' => 'Not directly onto the standard five-year degree at any medical school we reviewed. UK universities that publish a Nigeria page treat WASSCE as the equivalent of GCSEs and ask for A-levels, the IB or a recognised foundation year before Medicine. See what each school says on our <a href="'.$r('requirements.waec').'">WAEC page</a>.'],
            ['id' => 2, 'q' => 'Is WAEC accepted as a GCSE equivalent?', 'a' => 'Several universities publish exactly that: WASSCE with strong grades (often C6 or above, with B grades in English and Mathematics for some schools) covers the GCSE layer of a Medicine offer, while the main offer is made on A-levels or IB. Each school\'s wording is on our <a href="'.$r('requirements.waec').'">WAEC page</a>.'],
            ['id' => 3, 'q' => 'Does NECO count the same as WAEC?', 'a' => 'Most UK medical school pages name WASSCE and are silent on NECO. Where NECO is mentioned it is treated like WASSCE. Where a school is silent, confirm directly. Details on our <a href="'.$r('requirements.neco').'">NECO page</a>.'],
            ['id' => 6, 'q' => 'Does my JAMB or UTME score count for a UCAS application?', 'a' => 'No UK medical school we reviewed lists JAMB/UTME as an entry qualification. UCAS applications are assessed on school-leaving qualifications (A-levels, IB or equivalents), the UCAT and your statement and reference.'],
            ['id' => 8, 'q' => 'Which foundation years actually lead to Medicine?', 'a' => 'Only a few foundation programmes publish Medicine as a destination for international students, and progression is competitive and conditional. We list the ones we found, with their published terms, on our <a href="'.$r('medicine.foundation').'">foundation routes page</a>. Treat any "guaranteed progression to medicine" claim with caution unless the provider publishes it.'],
            ['id' => 12, 'q' => 'How do I study Medicine in the UK, step by step?', 'a' => 'Get the right qualifications (A-levels/IB or an approved foundation), sit the UCAT in the summer before entry, apply through UCAS by 15 October with up to four medicine choices, interview between December and March, meet offer conditions, then pay the deposit, receive your CAS and apply for the Student visa. Our <a href="'.$r('admissions.ucas2027').'">timeline page</a> has the dates.'],
            ['id' => 18, 'q' => 'Where can I sit the UCAT in Nigeria?', 'a' => 'The UCAT is delivered at Pearson VUE test centres; Lagos and Abuja are the practical options and capacity is limited, so book as soon as booking opens in June. See our <a href="'.$r('admissions.ucat').'">UCAT page</a>.'],
            ['id' => 20, 'q' => 'What UCAT score do I need?', 'a' => 'Since 2025 the cognitive total is out of 2700 (three sections) plus a Situational Judgement band. Thresholds vary by school and year; many schools rank international applicants separately. We do not publish cut-offs we cannot source.'],
            ['id' => 21, 'q' => 'Do I need IELTS as a Nigerian applicant?', 'a' => 'For most medical schools, yes: published requirements are typically IELTS 7.0 to 7.5 overall. A few schools accept WAEC or NECO English for specific courses. Our <a href="'.$r('requirements.english').'">English page</a> lists what each school publishes, and the visa rule.'],
            ['id' => 22, 'q' => 'How much does it cost to study Medicine in the UK as an international student?', 'a' => 'International Medicine fees vary widely by school and often rise in clinical years. Our <a href="'.$r('fees.index').'">fee guide</a> lists each school\'s published fee with its fee year and source, and shows the overall range separately.'],
            ['id' => 24, 'q' => 'Which is the cheapest UK medical school for international students?', 'a' => 'We do not rank schools. The <a href="'.$r('fees.index').'">fee guide</a> lets you sort by published fee, but check whether the school accepts international applicants, whether clinical years cost more, and whether NHS levies apply.'],
            ['id' => 26, 'q' => 'Which UK medical schools accept international students?', 'a' => 'Most do, but places are capped and small (often 10 to 30 per school). A few are home-only and one or two are international-only. Use the <a href="'.$r('schools.index').'">directory</a> filter "International applicants: accepted".'],
            ['id' => 28, 'q' => 'Are there scholarships for Nigerian students to study Medicine in the UK?', 'a' => 'Very few. Most universities exclude Medicine from international scholarships; where awards exist they are small partial fee reductions. Plan on full self-funding and treat scholarship listicles with caution.'],
            ['id' => 30, 'q' => 'Can I get medical work experience in Nigeria that UK schools accept?', 'a' => 'Yes. Medical schools value what you learned about care and about yourself, not the setting. Hospital, clinic, pharmacy, care-home, community and caring-for-family experience all count if you can reflect on it.'],
            ['id' => 31, 'q' => 'Can I do Medicine as a second degree?', 'a' => 'Graduate-entry programmes exist, but only some accept international applicants. Many graduates apply to the standard five-year course instead. See our <a href="'.$r('requirements.gem').'">graduate entry page</a>.'],
            ['id' => 33, 'q' => 'Can I work in the UK after studying Medicine?', 'a' => 'UK medical graduates, including international students, apply to the UK Foundation Programme on the same basis as home graduates and need the Medical Licensing Assessment and GMC provisional registration; visa sponsorship for Foundation Year 1 is through the Health and Care Worker route. Rules on post-study work and training prioritisation are changing; nothing is guaranteed. Our content on this is kept deliberately factual and dated.'],
            ['id' => 32, 'q' => 'Will my UK degree be recognised if I return to Nigeria?', 'a' => 'Recognition for practice in Nigeria is decided by the Medical and Dental Council of Nigeria, not by us or by the UK university. Check the MDCN\'s current requirements before you choose a course.'],
            ['id' => 40, 'q' => 'Are you an agent for UK universities?', 'a' => 'No. We are an independent application-support service. We are not an agent of, or affiliated with, any university, UCAS, the British Council or the GMC, and we receive no commission from universities. See <a href="'.$r('status').'">Our status</a>.'],
        ];
    }

    public function faq()
    {
        $items = collect($this->faqItems());
        $seo = $this->seo('Questions Nigerian applicants ask about UK Medicine', 'Straight, sourced answers to what Nigerian students ask about UK Medicine: WAEC, NECO, UCAT, costs, which schools accept international students, working after.', 'faq.index', [['label' => 'FAQ']], false);
        $seo->jsonLd(['@type' => 'FAQPage', 'mainEntity' => $items->map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['a'])]])->all()]);

        return view('content.faq.index', ['seo' => $seo, 'items' => $items]);
    }

    // ---------------- Organisation ----------------
    public function about()
    {
        return view('content.org.about', ['seo' => $this->seo('About Study Medicine UK Nigeria', 'An independent, evidence-led application-support service for Nigerian students applying to study Medicine in the UK: who we are, how we work and what we never claim.', 'about', [['label' => 'About']], false)]);
    }

    public function status()
    {
        return view('content.org.status', ['seo' => $this->seo('Our status: independence, registrations, agreements', 'A dated statement of our legal status, registrations, training and agreements. We hold no agreements with any university.', 'status', [['label' => 'Our status']], false)]);
    }

    public function contact()
    {
        return view('content.org.contact', ['seo' => $this->seo('Contact Study Medicine UK Nigeria', 'How to reach Study Medicine UK Nigeria: email, the student portal messages that keep everything on your record, response times and what to include.', 'contact', [['label' => 'Contact']], false)]);
    }

    public function legal(string $page)
    {
        $titles = [
            'privacy' => ['Privacy notice', 'legal.privacy', 'How Study Medicine UK Nigeria collects, uses, stores and protects personal data and application documents under UK GDPR and the Nigeria Data Protection Act.'],
            'terms' => ['Terms of use', 'legal.terms', 'The terms on which you may use this website and its information: accuracy and verification dates, no guarantee of admission, and your responsibilities as a reader.'],
            'application-terms' => ['Application service terms', 'legal.application-terms', 'The terms of our paid application-support service: what is and is not included, when work begins, your approval before any submission, and how we handle documents.'],
            'refunds' => ['Refund policy', 'legal.refunds', 'When fees for application support are refundable, how to ask for a refund, the timings, and what happens if you withdraw before or after we begin work on your file.'],
        ];
        abort_unless(isset($titles[$page]), 404);
        [$title, $route, $description] = $titles[$page];

        return view('content.org.legal-'.$page, ['seo' => $this->seo($title, $description, $route, [['label' => $title]], false)]);
    }
}
