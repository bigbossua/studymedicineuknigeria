<?php

namespace App\Services\Applications;

/** Document types — docs/architecture/14.1 */
class DocumentCatalogue
{
    public const TYPES = [
        'PASSPORT' => ['title' => 'International passport (data page)', 'formats' => ['pdf', 'jpg', 'png'], 'why' => 'Identity and name as the university will record it.'],
        'WAEC' => ['title' => 'WASSCE certificate or statement of result', 'formats' => ['pdf', 'jpg', 'png'], 'why' => 'Your secondary-school qualification.'],
        'NECO' => ['title' => 'NECO SSCE certificate', 'formats' => ['pdf', 'jpg', 'png'], 'why' => 'Your secondary-school qualification.'],
        'ALEVEL' => ['title' => 'A-level certificates or statements of results', 'formats' => ['pdf', 'jpg', 'png'], 'why' => 'Most medical schools assess A-levels for standard entry.'],
        'IB' => ['title' => 'IB Diploma results', 'formats' => ['pdf'], 'why' => 'Most medical schools assess the IB for standard entry.'],
        'DEGREE_CERT' => ['title' => 'Degree certificate', 'formats' => ['pdf', 'jpg', 'png'], 'why' => 'Required for graduate entry and direct-application schools.'],
        'TRANSCRIPT' => ['title' => 'Academic transcript', 'formats' => ['pdf'], 'why' => 'Shows your modules and grades.'],
        'ENGLISH' => ['title' => 'English language test report (IELTS, TOEFL, PTE)', 'formats' => ['pdf', 'jpg', 'png'], 'why' => 'Evidence of English for the university and the visa.'],
        'UCAT' => ['title' => 'UCAT score report', 'formats' => ['pdf'], 'why' => 'Required by most medical schools using UCAT.'],
        'GAMSAT' => ['title' => 'GAMSAT result', 'formats' => ['pdf'], 'why' => 'Required by some graduate-entry courses.'],
        'STATEMENT' => ['title' => 'Personal statement', 'formats' => ['pdf', 'docx'], 'why' => 'Every medical school asks for one, in the UCAS three-question format for 2027 entry.'],
        'REFERENCE' => ['title' => 'Reference letter', 'formats' => ['pdf'], 'why' => 'Academic reference from your school or university.'],
        'CV' => ['title' => 'CV', 'formats' => ['pdf', 'docx'], 'why' => 'Graduate-entry and direct-application schools ask for one.'],
        'EXPERIENCE' => ['title' => 'Work or volunteering experience evidence', 'formats' => ['pdf', 'jpg', 'png'], 'why' => 'Optional; strengthens your application.'],
        'FINANCIAL' => ['title' => 'Proof of funds (for CAS and visa stage only)', 'formats' => ['pdf'], 'why' => 'Only needed after an offer, for the visa.'],
        'OTHER' => ['title' => 'Other document', 'formats' => ['pdf', 'jpg', 'png', 'docx'], 'why' => 'Requested by our team.'],
    ];

    public const MIME = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'png' => ['image/png'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    public const MAX_BYTES = ['pdf' => 10 * 1024 * 1024, 'docx' => 10 * 1024 * 1024, 'jpg' => 8 * 1024 * 1024, 'png' => 8 * 1024 * 1024];

    public static function title(string $code): string
    {
        return self::TYPES[$code]['title'] ?? $code;
    }

    public static function allowedMimes(string $code): array
    {
        $out = [];
        foreach (self::TYPES[$code]['formats'] ?? ['pdf'] as $ext) {
            $out = array_merge($out, self::MIME[$ext]);
        }

        return $out;
    }
}
