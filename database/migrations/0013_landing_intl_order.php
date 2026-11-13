<?php
/**
 * Landing page: add the International band, and show people before places by default (only when the admin never
 * reordered the sections themselves).
 */
return function (): void {
    if ((int)val("SELECT COUNT(*) FROM landing_sections WHERE section_key = 'international'", [], 0) === 0) {
        $pos = (int)val("SELECT sort_order FROM landing_sections WHERE section_key = 'premium'", [], 0) ?: ((int)val('SELECT COALESCE(MAX(sort_order), 0) FROM landing_sections', [], 0) + 1);
        q('UPDATE landing_sections SET sort_order = sort_order + 1 WHERE sort_order >= :p', ['p' => $pos]);
        insert('landing_sections', [
            'section_key' => 'international', 'title' => 'International', 'is_enabled' => 1, 'sort_order' => $pos,
            'content' => json_encode(['title' => 'Meet someone beyond the border', 'subtitle' => 'Friendships, language exchange and romance with people in other countries. Everyone chose to be found.', 'cta_text' => 'Meet people abroad', 'cta_url' => '/international'], JSON_UNESCAPED_SLASHES),
        ]);
    }
    $loc = row("SELECT id, sort_order FROM landing_sections WHERE section_key = 'locations'");
    $pro = row("SELECT id, sort_order FROM landing_sections WHERE section_key = 'profiles'");
    if ($loc && $pro && (int)$loc['sort_order'] === 1 && (int)$pro['sort_order'] === 2) {
        update('landing_sections', ['sort_order' => 2], ['id' => $loc['id']]);
        update('landing_sections', ['sort_order' => 1], ['id' => $pro['id']]);
    }
    mig_setting_default('hero_mobile', 'compact');
    mig_setting_default('hero_cta_member_mode', 'discover');
    mig_setting_default('hero_cta_member_text', 'Open Discover');
};
