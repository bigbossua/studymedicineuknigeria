<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case NOT_REQUIRED = 'NOT_REQUIRED';
    case REQUIRED = 'REQUIRED';
    case UPLOADED = 'UPLOADED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case REPLACEMENT_REQUIRED = 'REPLACEMENT_REQUIRED';

    public function label(): string
    {
        return match ($this) {
            self::NOT_REQUIRED => 'Not required',
            self::REQUIRED => 'Upload required',
            self::UPLOADED => 'Received — checking file',
            self::UNDER_REVIEW => 'Received — under review',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Action required',
            self::REPLACEMENT_REQUIRED => 'Replacement required',
        };
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::ACCEPTED => 'chip-verified',
            self::REQUIRED, self::REPLACEMENT_REQUIRED => 'chip-review',
            self::REJECTED => 'chip-danger',
            self::UPLOADED, self::UNDER_REVIEW => 'chip-info',
            self::NOT_REQUIRED => 'chip-notpublished',
        };
    }

    public function needsStudent(): bool
    {
        return in_array($this, [self::REQUIRED, self::REJECTED, self::REPLACEMENT_REQUIRED], true);
    }
}
