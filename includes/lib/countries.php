<?php
/** Countries, languages, purposes and contact types used by the International module. */

declare(strict_types=1);

/** code => [name, dial code]. A short, practical list; add more here when you need them. */
function countries_all(): array
{
    static $c = null;
    return $c ??= [
        'KE' => ['Kenya', '254'], 'UG' => ['Uganda', '256'], 'TZ' => ['Tanzania', '255'], 'RW' => ['Rwanda', '250'], 'BI' => ['Burundi', '257'],
        'ET' => ['Ethiopia', '251'], 'SO' => ['Somalia', '252'], 'SS' => ['South Sudan', '211'], 'CD' => ['DR Congo', '243'], 'ZA' => ['South Africa', '27'],
        'NG' => ['Nigeria', '234'], 'GH' => ['Ghana', '233'], 'EG' => ['Egypt', '20'], 'MA' => ['Morocco', '212'], 'ZM' => ['Zambia', '260'],
        'ZW' => ['Zimbabwe', '263'], 'BW' => ['Botswana', '267'], 'NA' => ['Namibia', '264'], 'MZ' => ['Mozambique', '258'], 'SN' => ['Senegal', '221'],
        'CI' => ['Côte d’Ivoire', '225'], 'CM' => ['Cameroon', '237'], 'MU' => ['Mauritius', '230'],
        'GB' => ['United Kingdom', '44'], 'IE' => ['Ireland', '353'], 'FR' => ['France', '33'], 'DE' => ['Germany', '49'], 'IT' => ['Italy', '39'],
        'ES' => ['Spain', '34'], 'PT' => ['Portugal', '351'], 'NL' => ['Netherlands', '31'], 'BE' => ['Belgium', '32'], 'CH' => ['Switzerland', '41'],
        'AT' => ['Austria', '43'], 'SE' => ['Sweden', '46'], 'NO' => ['Norway', '47'], 'DK' => ['Denmark', '45'], 'FI' => ['Finland', '358'],
        'PL' => ['Poland', '48'], 'GR' => ['Greece', '30'], 'TR' => ['Türkiye', '90'], 'RU' => ['Russia', '7'], 'UA' => ['Ukraine', '380'],
        'US' => ['United States', '1'], 'CA' => ['Canada', '1'], 'MX' => ['Mexico', '52'], 'BR' => ['Brazil', '55'], 'AR' => ['Argentina', '54'],
        'CO' => ['Colombia', '57'], 'JM' => ['Jamaica', '1'],
        'AE' => ['United Arab Emirates', '971'], 'SA' => ['Saudi Arabia', '966'], 'QA' => ['Qatar', '974'], 'KW' => ['Kuwait', '965'], 'OM' => ['Oman', '968'],
        'IL' => ['Israel', '972'], 'IN' => ['India', '91'], 'PK' => ['Pakistan', '92'], 'BD' => ['Bangladesh', '880'], 'LK' => ['Sri Lanka', '94'],
        'CN' => ['China', '86'], 'HK' => ['Hong Kong', '852'], 'JP' => ['Japan', '81'], 'KR' => ['South Korea', '82'], 'TH' => ['Thailand', '66'],
        'VN' => ['Vietnam', '84'], 'PH' => ['Philippines', '63'], 'ID' => ['Indonesia', '62'], 'MY' => ['Malaysia', '60'], 'SG' => ['Singapore', '65'],
        'AU' => ['Australia', '61'], 'NZ' => ['New Zealand', '64'],
    ];
}

function country_name(string $code): string
{
    return countries_all()[strtoupper($code)][0] ?? strtoupper($code);
}

function country_dial(string $code): string
{
    return countries_all()[strtoupper($code)][1] ?? '';
}

/** A flag emoji from a two-letter code. */
function country_flag(string $code): string
{
    $code = strtoupper($code);
    if (!preg_match('/^[A-Z]{2}$/', $code)) {
        return '';
    }
    return mb_chr(0x1F1E6 + ord($code[0]) - 65, 'UTF-8') . mb_chr(0x1F1E6 + ord($code[1]) - 65, 'UTF-8');
}

/** Countries sorted by name, for select boxes. */
function countries_sorted(): array
{
    $c = countries_all();
    uasort($c, fn($a, $b) => strcmp($a[0], $b[0]));
    return array_map(fn($x) => $x[0], $c);
}

function intl_languages(): array
{
    return ['en' => 'English', 'sw' => 'Swahili', 'fr' => 'French', 'ar' => 'Arabic', 'es' => 'Spanish', 'pt' => 'Portuguese', 'de' => 'German', 'it' => 'Italian',
        'zh' => 'Chinese', 'hi' => 'Hindi', 'ru' => 'Russian', 'ja' => 'Japanese', 'ko' => 'Korean', 'tr' => 'Turkish', 'nl' => 'Dutch', 'am' => 'Amharic',
        'so' => 'Somali', 'lg' => 'Luganda', 'rw' => 'Kinyarwanda', 'zu' => 'Zulu', 'yo' => 'Yoruba', 'ki' => 'Kikuyu', 'luo' => 'Dholuo', 'pl' => 'Polish', 'sv' => 'Swedish'];
}

function intl_purposes(): array
{
    return ['friendship' => 'Friendship', 'relationship' => 'Dating and romance', 'language' => 'Language exchange', 'travel' => 'Meeting when travelling', 'culture' => 'Culture and pen pals'];
}

/** What a phone number can be: key => [label, short hint]. The admin chooses which ones members may pick. */
function intl_contact_types_all(): array
{
    return [
        'whatsapp' => ['WhatsApp', 'Calls and chat over the internet'],
        'mobile' => ['Mobile phone', 'Normal calls and text messages'],
        'telegram' => ['Telegram', 'Chat app tied to your number'],
        'signal' => ['Signal', 'Private chat app tied to your number'],
        'viber' => ['Viber', 'Calls and chat over the internet'],
        'landline' => ['Landline', 'A home or office phone'],
    ];
}
