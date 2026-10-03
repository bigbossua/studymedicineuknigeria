<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Authorisation extends Model
{
    public const DECLARATION_VERSION = 'v1';

    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['approved_at' => 'datetime', 'revoked_at' => 'datetime', 'snapshot' => 'array'];

    public static function declarationText(string $university, string $course, string $intake): string
    {
        return "I have reviewed the information and documents shown above. I confirm they are accurate and complete to the best of my knowledge. I authorise StudyMedicineUKNigeria to prepare and submit, or to guide me to submit, this application to {$university} — {$course} ({$intake}) by the route described. I understand that admission decisions are made solely by the university, that no admission, scholarship or visa outcome is guaranteed, and that university fees are separate from any service fee I have paid.";
    }
}
