<?php
/**
 * Plan pages: plain-language feature labels, the comparison grid and price helpers.
 * Plans themselves live in subscription_plans (edited by admin); nothing about them is hard-coded here
 * except the wording used to describe each feature key.
 */

declare(strict_types=1);

/** Feature keys in the order they appear, grouped, with a label and a one-line explanation. kind: limit | flag | count. */
function plans_feature_catalogue(): array
{
    return [
        'Meeting people' => [
            'daily_swipes' => ['Likes each day', 'How many profiles you can like or pass every day.', 'limit'],
            'daily_superlikes' => ['Super likes each day', 'Stand out by letting someone know you really like them.', 'limit'],
            'daily_rewinds' => ['Rewinds each day', 'Take back a pass you made by mistake.', 'limit'],
            'advanced_filters' => ['Advanced filters', 'Narrow people down by height, education, lifestyle and more.', 'flag'],
            'priority_likes' => ['Your likes are seen first', 'Your likes appear at the top of the other person\'s list.', 'flag'],
        ],
        'Conversations' => [
            'daily_messages' => ['Messages each day', 'How many new messages you can send every day.', 'limit'],
            'message_before_match' => ['Message before you match', 'Start a chat with someone before they like you back.', 'flag'],
            'read_receipts' => ['Read receipts', 'See when your messages have been read.', 'flag'],
        ],
        'Insights and privacy' => [
            'see_who_liked' => ['See who likes you', 'Open your Likes page and reply to people who already like you.', 'flag'],
            'profile_visitors' => ['See who viewed your profile', 'Know who has been looking at you.', 'flag'],
            'incognito' => ['Browse in incognito', 'Look at profiles without showing up in their visitors.', 'flag'],
        ],
        'Your profile' => [
            'max_photos' => ['Photos on your profile', 'Show more of your life.', 'count'],
            'dm_credits_monthly' => ['Message credits included', 'Message people you have not matched with yet. Credits arrive each time you pay for your plan.', 'count'],
            'boost_credits_monthly' => ['Free boosts included', 'Be shown first for a while. Credits arrive each time you pay for your plan.', 'count'],
            'no_ads' => ['No adverts', 'A calmer, ad-free app.', 'flag'],
        ],
    ];
}

/** Turn a feature value into a cell for the comparison grid: [kind, text]. kind is yes | no | text. */
function plan_feature_cell($value, string $kind): array
{
    if ($kind === 'flag') {
        $on = is_numeric($value) ? ((int)$value !== 0) : (bool)$value;
        return $on ? ['yes', 'Included'] : ['no', 'Not included'];
    }
    if (!is_numeric($value) && !is_bool($value)) {
        return ['no', 'Not included'];
    }
    $n = (int)$value;
    if ($n < 0) {
        return ['text', 'Unlimited'];
    }
    if ($n === 0) {
        return ['no', 'Not included'];
    }
    return ['text', (string)$n];
}

/**
 * The comparison grid. Returns [ [group, [ [key, label, hint, [planId => [kind, text]]] ]] ].
 * Feature keys an admin added that this file does not know yet are listed under "More" with a readable name.
 */
function plans_feature_matrix(array $plans): array
{
    $feats = [];
    foreach ($plans as $p) {
        $feats[(int)$p['id']] = plan_features_decoded($p);
    }
    $known = [];
    $out = [];
    foreach (plans_feature_catalogue() as $group => $items) {
        $rows = [];
        foreach ($items as $key => [$label, $hint, $kind]) {
            $known[$key] = true;
            $cells = [];
            foreach ($plans as $p) {
                $cells[(int)$p['id']] = plan_feature_cell($feats[(int)$p['id']][$key] ?? 0, $kind);
            }
            $rows[] = ['key' => $key, 'label' => $label, 'hint' => $hint, 'cells' => $cells];
        }
        $out[] = [$group, $rows];
    }
    $more = [];
    foreach ($feats as $f) {
        foreach ($f as $key => $v) {
            if (!isset($known[$key]) && $key !== 'rewind' && is_string($key) && (is_bool($v) || is_numeric($v))) {
                $more[$key] = is_bool($v) ? 'flag' : 'limit';
            }
        }
    }
    if ($more) {
        $rows = [];
        foreach ($more as $key => $kind) {
            $cells = [];
            foreach ($plans as $p) {
                $cells[(int)$p['id']] = plan_feature_cell($feats[(int)$p['id']][$key] ?? 0, $kind);
            }
            $rows[] = ['key' => $key, 'label' => ucfirst(str_replace('_', ' ', $key)), 'hint' => '', 'cells' => $cells];
        }
        $out[] = ['More', $rows];
    }
    return $out;
}

/** Billing periods a plan can be sold in: key => [label, noun, days, price column, months]. */
function plan_period_defs(): array
{
    return [
        'weekly' => ['label' => 'Weekly', 'noun' => 'week', 'days' => 7, 'col' => 'price_weekly', 'text' => '1 week'],
        'monthly' => ['label' => 'Monthly', 'noun' => 'month', 'days' => 30, 'col' => 'price_monthly', 'text' => '1 month'],
        'yearly' => ['label' => 'Yearly', 'noun' => 'year', 'days' => 365, 'col' => 'price_yearly', 'text' => '12 months'],
    ];
}

/** Clean period key from user input, 'monthly' when unknown. */
function plan_period_clean(?string $period): string
{
    return isset(plan_period_defs()[(string)$period]) ? (string)$period : 'monthly';
}

function plan_period_days(string $period): int
{
    return plan_period_defs()[$period]['days'] ?? 30;
}

function plan_period_price(array $plan, string $period): float
{
    $col = plan_period_defs()[$period]['col'] ?? 'price_monthly';
    return (float)($plan[$col] ?? 0);
}

/** Periods offered for one plan: the price is above zero and the admin has not switched the option off. */
function plan_periods_for(array $plan): array
{
    $out = [];
    foreach (plan_period_defs() as $key => $d) {
        if ($key === 'weekly' && !setting('show_weekly_option')) {
            continue;
        }
        if ($key === 'yearly' && !setting('show_yearly_toggle')) {
            continue;
        }
        if (plan_period_price($plan, $key) > 0) {
            $out[$key] = $d;
        }
    }
    return $out;
}

/** Periods offered across a list of plans (for the switch above the cards), in weekly, monthly, yearly order. */
function plan_periods_offered(array $plans): array
{
    $out = [];
    foreach (plan_period_defs() as $key => $d) {
        foreach ($plans as $p) {
            if (isset(plan_periods_for($p)[$key])) {
                $out[$key] = $d;
                break;
            }
        }
    }
    return $out;
}

/** Whole-number percentage a longer period saves against paying monthly for the same time, or 0. */
function plan_period_saving(array $plan, string $period): int
{
    $m = (float)$plan['price_monthly'];
    $price = plan_period_price($plan, $period);
    $days = plan_period_days($period);
    if ($m <= 0 || $price <= 0 || $period === 'monthly') {
        return 0;
    }
    $monthlyForSameTime = $m * ($days / 30);
    return $price >= $monthlyForSameTime ? 0 : (int)round((1 - $price / $monthlyForSameTime) * 100);
}

/** Whole-number percentage the yearly price saves against twelve months, or 0. */
function plan_yearly_saving(array $plan): int
{
    $m = (float)$plan['price_monthly'];
    $y = (float)$plan['price_yearly'];
    if ($m <= 0 || $y <= 0 || $y >= $m * 12) {
        return 0;
    }
    return (int)round((1 - $y / ($m * 12)) * 100);
}

/** Yearly price shown per month, rounded up to a whole shilling. */
function plan_yearly_per_month(array $plan): float
{
    return ceil((float)$plan['price_yearly'] / 12);
}

/** Period shown first: monthly when it is on offer, otherwise the first one on offer. */
function plan_default_period(array $offered): string
{
    if (isset($offered['monthly'])) {
        return 'monthly';
    }
    $k = array_key_first($offered);
    return $k !== null ? (string)$k : 'monthly';
}

/** Short price line under the amount for one period: what is charged, and what it saves. */
function plan_period_note(array $plan, string $period): string
{
    $defs = plan_period_defs();
    $d = $defs[$period] ?? $defs['monthly'];
    $price = plan_period_price($plan, $period);
    $note = 'Pay ' . money($price) . ' for ' . $d['text'];
    if ($period === 'yearly') {
        $per = plan_yearly_per_month($plan);
        $note .= ' (' . money($per) . ' a month)';
    }
    return $note;
}

/** Whole-number percentage this period saves compared with buying the shortest period again and again. */
function plan_period_vs_weekly(array $plan, string $period): int
{
    $w = (float)($plan['price_weekly'] ?? 0);
    $price = plan_period_price($plan, $period);
    $days = plan_period_days($period);
    if ($period === 'weekly' || $w <= 0 || $price <= 0) {
        return 0;
    }
    $weeklyForSameTime = $w * ($days / 7);
    return $price >= $weeklyForSameTime ? 0 : (int)round((1 - $price / $weeklyForSameTime) * 100);
}

/** Biggest saving a period offers across plans, used for the badge on the switch. */
function plan_period_best_saving(array $plans, string $period): int
{
    $best = 0;
    foreach ($plans as $p) {
        if ((int)($p['tier'] ?? 0) < 1 || plan_period_price($p, $period) <= 0) {
            continue;
        }
        $best = max($best, plan_period_vs_weekly($p, $period), $period === 'yearly' ? plan_yearly_saving($p) : 0);
    }
    return $best;
}

/** FAQ entries for the plans page. */
function plans_faqs(int $limit = 8): array
{
    return rows("SELECT id, question, answer FROM faqs WHERE is_active = 1 AND category = 'Premium and payments' ORDER BY sort_order, id LIMIT " . max(1, min(20, $limit)));
}
