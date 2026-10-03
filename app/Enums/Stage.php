<?php

namespace App\Enums;

/** Application stages — docs/architecture/12. Order matters for progress display. */
enum Stage: string
{
    case LEAD = 'LEAD';
    case ACCOUNT_CREATED = 'ACCOUNT_CREATED';
    case APPLICATION_STARTED = 'APPLICATION_STARTED';
    case APPLICATION_INCOMPLETE = 'APPLICATION_INCOMPLETE';
    case APPLICATION_COMPLETE = 'APPLICATION_COMPLETE';
    case DOCUMENTS_INCOMPLETE = 'DOCUMENTS_INCOMPLETE';
    case DOCUMENTS_COMPLETE = 'DOCUMENTS_COMPLETE';
    case INTERNAL_REVIEW = 'INTERNAL_REVIEW';
    case ACTION_REQUIRED = 'ACTION_REQUIRED';
    case READY_FOR_STUDENT_APPROVAL = 'READY_FOR_STUDENT_APPROVAL';
    case STUDENT_APPROVED = 'STUDENT_APPROVED';
    case PAYMENT_REQUIRED = 'PAYMENT_REQUIRED';
    case PAYMENT_COMPLETE = 'PAYMENT_COMPLETE';
    case READY_FOR_UNIVERSITY_SUBMISSION = 'READY_FOR_UNIVERSITY_SUBMISSION';
    case SUBMITTED = 'SUBMITTED';
    case UNIVERSITY_ACKNOWLEDGED = 'UNIVERSITY_ACKNOWLEDGED';
    case UNIVERSITY_STAGE = 'UNIVERSITY_STAGE'; // interview / offer / other
    case COMPLETED = 'COMPLETED';
    case WITHDRAWN = 'WITHDRAWN';
    case ON_HOLD = 'ON_HOLD';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::LEAD => 'Enquiry received',
            self::ACCOUNT_CREATED => 'Account created',
            self::APPLICATION_STARTED => 'Application started',
            self::APPLICATION_INCOMPLETE => 'Application in progress',
            self::APPLICATION_COMPLETE => 'Application form complete',
            self::DOCUMENTS_INCOMPLETE => 'Documents outstanding',
            self::DOCUMENTS_COMPLETE => 'Documents complete',
            self::INTERNAL_REVIEW => 'Under review by our team',
            self::ACTION_REQUIRED => 'Action required from you',
            self::READY_FOR_STUDENT_APPROVAL => 'Ready for your approval',
            self::STUDENT_APPROVED => 'Approved by you',
            self::PAYMENT_REQUIRED => 'Payment required',
            self::PAYMENT_COMPLETE => 'Payment complete',
            self::READY_FOR_UNIVERSITY_SUBMISSION => 'Ready for submission',
            self::SUBMITTED => 'Submitted',
            self::UNIVERSITY_ACKNOWLEDGED => 'Acknowledged by the university',
            self::UNIVERSITY_STAGE => 'With the university',
            self::COMPLETED => 'Completed',
            self::WITHDRAWN => 'Withdrawn',
            self::ON_HOLD => 'On hold',
            self::CLOSED => 'Closed',
        };
    }

    /** Stages set only by staff judgement (not derived). */
    public static function staffStages(): array
    {
        return [self::INTERNAL_REVIEW, self::ACTION_REQUIRED, self::READY_FOR_STUDENT_APPROVAL, self::ON_HOLD, self::CLOSED];
    }
}
