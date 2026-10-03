<?php

namespace App\Support;

use App\Models\ReferenceFact;
use App\Models\Topic;

/**
 * Decides whether a fact-driven page may be indexed yet. A page is gated on the topics it renders:
 * it becomes indexable (and enters the sitemap) only when every non-archived fact of those topics
 * is VERIFIED on the official source or recorded as NOT_PUBLISHED. NOT_FOUND means research is
 * unfinished and keeps the page out of the index; the page itself still renders for readers.
 *
 * Gate syntax (route default 'sitemap.gate'): "topics-verified:slug-a,slug-b"
 */
final class PublishGate
{
    public static function passes(?string $gate): bool
    {
        if (! $gate) {
            return true;
        }
        [$type, $arg] = array_pad(explode(':', $gate, 2), 2, '');

        return match ($type) {
            'topics-verified' => self::topicsVerified(array_filter(explode(',', $arg))),
            default => false,
        };
    }

    /** @param  list<string>  $slugs */
    public static function topicsVerified(array $slugs): bool
    {
        if (! $slugs) {
            return false;
        }
        $topics = Topic::whereIn('slug', $slugs)->pluck('id');
        if ($topics->count() !== count($slugs)) {
            return false; // a topic is missing: never index a page whose data set does not exist
        }

        return ! ReferenceFact::where('subject_type', Topic::class)->whereIn('subject_id', $topics)
            ->whereNotIn('verification_status', [ReferenceFact::VERIFIED, ReferenceFact::NOT_PUBLISHED, ReferenceFact::ARCHIVED])
            ->exists();
    }
}
