<?php

namespace App\Support;

/**
 * Who trades on this site, from the SITE_* settings in config/site.php: the one place every page, email and the admin
 * checklist read it from. The law asks for the trader's name, the geographic address where it is established and, for
 * a company, its registered number, the part of the UK it is registered in and its registered office (CCR 2013 Sch. 2
 * (b)–(d); E-Commerce Regulations 2002 reg. 6(1); trading disclosure regulations). Nothing here is ever invented: an
 * unset value is reported as missing and the pages say "to be published".
 */
final class LegalIdentity
{
    public const ENTITY_TYPES = [
        'sole_trader' => 'sole trader',
        'partnership' => 'partnership',
        'limited_company' => 'private limited company',
        'llp' => 'limited liability partnership',
    ];

    /** The legal name once supplied, otherwise the trading name of the site. */
    public static function name(): string
    {
        return config('site.legal_name') ?: config('site.name');
    }

    public static function isRegisteredBody(): bool
    {
        return in_array(config('site.entity_type'), ['limited_company', 'llp'], true);
    }

    /** "Registered office" for a company or LLP, "Business address" otherwise (and while the structure is unknown). */
    public static function addressLabel(): string
    {
        return self::isRegisteredBody() ? 'Registered office' : 'Business address';
    }

    /**
     * One line naming the trader with every detail supplied so far, in a fixed order:
     * name, structure, registration, address, VAT. Used by the terms; Our status shows the address on its own line.
     */
    public static function summary(bool $withAddress = true): string
    {
        $registration = collect([
            config('site.company_number') ? 'company number '.config('site.company_number') : null,
            config('site.registered_in') ? 'registered in '.config('site.registered_in') : null,
        ])->filter()->implode(', ');

        return collect([
            self::name(),
            config('site.entity_type') ? 'a '.self::ENTITY_TYPES[config('site.entity_type')] : null,
            $registration ?: null,
            $withAddress && config('site.address') ? (self::isRegisteredBody() ? 'registered office: ' : '').config('site.address') : null,
            config('site.vat_number') ? 'VAT number '.config('site.vat_number') : null,
        ])->filter()->implode(', ');
    }

    /**
     * Settings still needed before taking payments, by the business structure. While the structure is unknown, the
     * company fields are not asked for (a sole trader has none), but the structure itself is.
     *
     * @return array<int, string> SITE_* names
     */
    public static function missing(): array
    {
        $need = ['SITE_LEGAL_NAME' => config('site.legal_name'), 'SITE_ENTITY_TYPE' => config('site.entity_type'), 'SITE_ADDRESS' => config('site.address')];
        if (self::isRegisteredBody()) {
            $need += ['SITE_COMPANY_NUMBER' => config('site.company_number'), 'SITE_REGISTERED_IN' => config('site.registered_in')];
        }
        $need['SITE_ICO_NUMBER'] = config('site.ico_number');

        return array_keys(array_filter($need, fn ($v) => $v === null));
    }

    public static function isPublished(): bool
    {
        return config('site.legal_name') !== null && config('site.address') !== null && config('site.entity_type') !== null
            && (! self::isRegisteredBody() || (config('site.company_number') !== null && config('site.registered_in') !== null));
    }
}
