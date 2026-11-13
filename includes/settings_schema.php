<?php
/**
 * Settings catalogue.
 *
 * Every option the admin can change lives here: key, type, default, label and
 * help text, grouped into tabs and sections. The admin settings screens are
 * rendered from this array, saved values are validated against it, and
 * setting('key') falls back to the default defined here when the admin has
 * never saved the option.
 *
 * Field types: text, textarea, number, bool, select, multiselect, color,
 *              image, secret, email, url
 */

declare(strict_types=1);

function settings_schema(): array
{
    static $schema = null;
    if ($schema !== null) {
        return $schema;
    }

    $tabs = [];
    $fields = [];

    // Helpers -----------------------------------------------------------------
    $tab = function (string $key, string $label, string $icon, string $desc) use (&$tabs) {
        $tabs[$key] = ['label' => $label, 'icon' => $icon, 'desc' => $desc, 'sections' => []];
    };
    $section = function (string $tabKey, string $title, array $defs, string $note = '') use (&$tabs, &$fields) {
        $keys = [];
        foreach ($defs as $key => $def) {
            $def['tab'] = $tabKey;
            $def['section'] = $title;
            $def += ['type' => 'text', 'default' => '', 'label' => $key, 'help' => ''];
            $fields[$key] = $def;
            $keys[] = $key;
        }
        $tabs[$tabKey]['sections'][] = ['title' => $title, 'note' => $note, 'keys' => $keys];
    };
    $f = fn(string $type, $default, string $label, string $help = '', array $extra = []) =>
        ['type' => $type, 'default' => $default, 'label' => $label, 'help' => $help] + $extra;
    $yesno = fn($default, string $label, string $help = '') => $f('bool', $default, $label, $help);
    $num = fn($default, string $label, string $help = '', $min = null, $max = null, $step = 1) =>
        $f('number', $default, $label, $help, ['min' => $min, 'max' => $max, 'step' => $step]);
    $opt = fn(string $default, string $label, array $options, string $help = '') =>
        $f('select', $default, $label, $help, ['options' => $options]);

    // ===========================================================================
    // GENERAL
    // ===========================================================================
    $tab('general', 'General', 'settings', 'Site identity, registration rules and contact details.');
    $section('general', 'Identity', [
        'site_name' => $f('text', 'ConnectHub', 'Site name', 'Shown in the header, emails and browser tab.', ['max' => 60]),
        'site_tagline' => $f('text', 'Free dating site in Kenya: meet people near you', 'Tagline', 'Short line used in the page title and social previews.', ['max' => 120]),
        'site_description' => $f('textarea', 'ConnectHub is a free dating site where you meet real people in your town, for dating, friendship and company. Verified profiles, private by default.', 'Description', 'Default meta description for search engines.', ['max' => 300]),
        'site_url' => $f('url', '', 'Site address', 'Full address including https://. Leave blank to detect it automatically.'),
        'site_country' => $f('text', 'Kenya', 'Country', 'Shown on profiles and used for location search.'),
        'timezone' => $f('text', 'Africa/Nairobi', 'Time zone', 'PHP time zone name, for example Africa/Nairobi.'),
        'currency' => $f('text', 'KES', 'Currency code', 'Three-letter code used on payments.', ['max' => 5]),
        'currency_symbol' => $f('text', 'KSh', 'Currency symbol', 'Shown next to prices.', ['max' => 6]),
        'phone_prefix' => $f('text', '254', 'Phone country code', 'Digits only. Used to normalise phone numbers, for example 254.', ['max' => 4]),
    ]);
    $section('general', 'Contact details', [
        'site_email' => $f('email', 'hello@connecthub.co.ke', 'Contact email', 'Shown in the footer and on the contact page.'),
        'support_email' => $f('email', 'support@connecthub.co.ke', 'Support email', 'Where contact-form messages are emailed.'),
        'site_phone' => $f('text', '', 'Phone number', 'Shown in the footer when filled in.'),
        'site_whatsapp' => $f('text', '', 'WhatsApp number', 'Digits only, with country code. Adds a WhatsApp button to the contact page.'),
        'site_address' => $f('text', 'Nairobi, Kenya', 'Address', 'Shown in the footer.'),
    ]);
    $section('general', 'Registration', [
        'registration_open' => $yesno(1, 'Registration is open', 'Turn off to stop new sign-ups. Existing members can still sign in.'),
        'email_verification_required' => $yesno(0, 'Require email verification', 'Members must confirm their email address before using the site.'),
        'min_age' => $num(18, 'Minimum age', 'Members younger than this cannot register.', 18, 99),
        'max_age' => $num(99, 'Maximum age shown in filters', '', 30, 120),
    ]);
    $section('general', 'Maintenance', [
        'maintenance_mode' => $yesno(0, 'Maintenance mode', 'Visitors see a maintenance page. Admins can still sign in at /login.'),
        'maintenance_message' => $f('textarea', 'We are making ConnectHub better and will be back shortly.', 'Maintenance message', '', ['max' => 300]),
    ]);
    $section('general', 'Connection & security', [
        'force_https' => $yesno(0, 'Send visitors to the https:// version', 'Turn on once your SSL certificate works for this address. If the site sits behind a proxy that does not pass the original protocol on, leave it off.'),
        'hsts_enabled' => $yesno(0, 'Tell browsers to remember https (HSTS)', 'Leave off while you are testing on a temporary address. Turn on after launch, on the final domain, once https works everywhere.'),
        'hsts_days' => $num(180, 'Remember https for (days)', 'Only used when the option above is on.', 1, 730),
        'frame_allow_sites' => $f('textarea', '', 'Sites allowed to show this site inside a frame', 'One address per line, for example https://example.com. Empty means only this site can frame its own pages, which protects members from click-jacking.', ['max' => 600]),
    ]);
    $section('general', 'Footer', [
        'footer_tagline' => $f('text', 'Real people. Real places. Real connections.', 'Footer tagline', '', ['max' => 140]),
        'footer_credit' => $f('text', 'Made with care in Kenya', 'Footer credit line', '', ['max' => 80]),
        'newsletter_enabled' => $yesno(1, 'Show newsletter sign-up in footer', ''),
    ]);

    // ===========================================================================
    // BRANDING / LOGOS
    // ===========================================================================
    $tab('branding', 'Logo & branding', 'badge-check', 'Upload your logos for each place they appear, in light and dark mode, and set their sizes.');
    $section('branding', 'Main logos', [
        'logo_full' => $f('image', '', 'Full logo', 'Used on the sign-in page and error pages. SVG or PNG with a transparent background.'),
        'logo_compact' => $f('image', '', 'Compact logo', 'Used wherever there is no more specific logo below. Falls back to the full logo.'),
        'logo_icon' => $f('image', '', 'Icon only', 'Square mark used in the mobile header and the app icon.'),
        'favicon' => $f('image', '', 'Favicon', 'Square image, at least 192 × 192 px. Also generates the app icons.'),
        'logo_show_wordmark' => $yesno(1, 'Show site name beside the default mark', 'Applies when no logo has been uploaded.'),
    ]);
    $section('branding', 'Header', [
        'logo_white' => $f('image', '', 'Header logo over the hero photo', 'Shown at the top of the landing page while the header floats over the photo, and on other dark photo areas such as the admin menu. Use a white or light version.'),
        'logo_header_scrolled' => $f('image', '', 'Header logo after scrolling', 'Shown once the header turns solid, and on every other page. Falls back to the compact logo.'),
        'logo_header_scrolled_dark' => $f('image', '', 'Header logo after scrolling, dark mode', 'Shown in dark mode once the header is solid. Falls back to the dark mode logo.'),
    ], 'The header has two looks on the landing page: floating over the photo, and solid after the visitor scrolls. Each can have its own logo.');
    $section('branding', 'Mobile header', [
        'logo_mobile' => $f('image', '', 'Mobile header logo', 'Shown in the top bar on phones, in light mode. Falls back to the icon, then the compact logo. A wide logo works as long as it is legible at the mobile height below.'),
        'logo_mobile_dark' => $f('image', '', 'Mobile header logo, dark mode', 'Shown on phones in dark mode. If empty, the mobile logo above is used in both modes, so upload this when your logo has dark lettering.'),
        'logo_mobile_white' => $f('image', '', 'Mobile logo over the hero photo', 'Shown on phones at the top of the landing page, while the header floats over the photo. Use a white or light version. Falls back to the header photo logo.'),
    ], 'Phones can have their own logos, with a light, dark and over-the-photo version.');
    $section('branding', 'Footer', [
        'logo_footer' => $f('image', '', 'Footer logo', 'Shown in the footer in light mode. Falls back to the compact logo.'),
        'logo_footer_dark' => $f('image', '', 'Footer logo, dark mode', 'Shown in the footer in dark mode. Falls back to the dark mode logo.'),
    ]);
    $section('branding', 'Signed-in app and menus', [
        'logo_app_header' => $f('image', '', 'Signed-in top bar logo', 'Shown in the top bar of the member and Host app on phones, in light mode. Falls back to the mobile header logo.'),
        'logo_app_header_dark' => $f('image', '', 'Signed-in top bar logo, dark mode', 'Falls back to the mobile dark logo, then the dark mode logo.'),
        'logo_sidepanel' => $f('image', '', 'Side panel logo (members)', 'Shown at the top of the left side panel when a member is signed in, on large screens. Falls back to the compact logo.'),
        'logo_sidepanel_dark' => $f('image', '', 'Side panel logo (members), dark mode', 'Falls back to the dark mode logo.'),
        'logo_sidepanel_host' => $f('image', '', 'Side panel logo (Hosts)', 'Leave empty to show the same logo as members.'),
        'logo_sidepanel_host_dark' => $f('image', '', 'Side panel logo (Hosts), dark mode', 'Leave empty to use the members dark logo.'),
        'logo_auth' => $f('image', '', 'Sign-in and sign-up page logo', 'Shown on the brand panel beside the sign-in card. That panel is dark, so a logo made for a dark background works best. Falls back to the main logo.'),
        'logo_auth_dark' => $f('image', '', 'Sign-in page logo, dark mode', 'Falls back to the sign-in logo above, then the dark mode logo.'),
        'logo_menu' => $f('image', '', 'Phone menu logo (landing and public pages)', 'Shown at the top of the menu that opens from the hamburger button on phones. Falls back to the side panel logo.'),
        'logo_menu_dark' => $f('image', '', 'Phone menu logo, dark mode', 'Falls back to the side panel dark logo, then the dark mode logo.'),
    ], 'Pick the logo for each signed-in screen and for the menu on phones. Anything left empty falls back to the logos above.');
    $section('branding', 'Public pages when signed in', [
        'logo_header_member' => $f('image', '', 'Top bar logo for signed-in members (large screens)', 'Shown in the top bar of pages that anyone can open, such as another member\'s profile or a Host profile, when a signed-in member views them on a laptop or desktop. Leave empty to use the header logo after scrolling.'),
        'logo_header_member_dark' => $f('image', '', 'Top bar logo for signed-in members, dark mode', 'Falls back to the header logo after scrolling in dark mode.'),
    ], 'Visitors who are not signed in keep seeing the header logos above. This only changes what signed-in members see.');
    $section('branding', 'Dashboard banner pictures', [
        'banners_show_images' => $yesno(1, 'Show pictures in the dashboard banners', 'Turn off to use plain colour banners everywhere.'),
        'banner_host_image' => $f('image', '', 'Host dashboard banner', 'The picture behind the welcome banner at the top of the Host dashboard. Leave empty to use the built-in picture. Wide pictures at least 1600 px across work best.'),
        'banner_host_wallet_image' => $f('image', '', 'Host earnings and payouts banner', 'The picture behind the balance banner on the Host earnings and payouts pages. Leave empty to use the built-in picture.'),
        'banner_wallet_image' => $f('image', '', 'Member wallet banner', 'The picture behind the balance banner on the member wallet. Leave empty to use the built-in picture.'),
        'banner_admin_image' => $f('image', '', 'Admin dashboard banner', 'The picture behind the welcome banner at the top of the admin dashboard. Leave empty to use the built-in picture.'),
        'banner_overlay' => $opt('medium', 'How much colour over the pictures', ['light' => 'Light, picture shows more', 'medium' => 'Medium', 'strong' => 'Strong, text is easiest to read']),
    ], 'These banners sit in rounded cards at the top of each dashboard. Upload your own pictures, or leave a slot empty to use the built-in one.');
    $section('branding', 'Dark mode', [
        'logo_dark' => $f('image', '', 'Dark mode logo', 'Used in dark mode wherever there is no more specific dark logo. Falls back to the light-on-dark header logo.'),
    ]);
    $section('branding', 'Logo sizes', [
        'logo_h_header' => $num(40, 'Header logo height over the photo (px)', 'Desktop, at the top of the landing page.', 16, 80),
        'logo_h_header_scrolled' => $num(34, 'Header logo height when solid (px)', 'Desktop, after scrolling and on other pages. A little smaller looks tidy.', 16, 80),
        'logo_h_header_member' => $num(34, 'Signed-in members top bar logo height (px)', 'Large screens, on public pages such as other profiles.', 16, 80),
        'logo_h_header_mobile' => $num(28, 'Mobile header logo height (px)', '', 16, 60),
        'logo_h_app_header' => $num(28, 'Signed-in top bar logo height (px)', 'The top bar of the member and Host app on phones.', 16, 60),
        'logo_w_menu' => $num(140, 'Phone menu logo width (px)', '', 60, 260),
        'logo_w_auth' => $num(200, 'Sign-in page logo width (px)', '', 80, 400),
        'logo_w_sidepanel' => $num(140, 'Side panel logo width (px)', '', 60, 260),
        'logo_w_footer' => $num(140, 'Footer logo width (px)', '', 60, 300),
        'logo_h_admin' => $num(32, 'Admin panel logo height (px)', '', 16, 64),
        'logo_padding' => $num(8, 'Space around the logo (px)', '', 0, 32),
        'logo_bg' => $opt('none', 'Background behind the logo', ['none' => 'None', 'light' => 'Soft light', 'dark' => 'Soft dark']),
    ], 'Changes apply everywhere straight away. Use the preview beside this page to check each placement.');

    // ===========================================================================
    // APPEARANCE
    // ===========================================================================
    $tab('appearance', 'Appearance', 'palette', 'Colours, theme, fonts, effects and motion.');
    $section('appearance', 'Colours', [
        'color_primary' => $f('color', '#E8523F', 'Primary colour', 'Buttons, links and highlights.'),
        'color_secondary' => $f('color', '#F4A63A', 'Secondary colour', 'Premium badges and gradients.'),
        'color_tertiary' => $f('color', '#6C5CE7', 'Accent colour', 'VIP and special features.'),
        'hero_gradient' => $opt('warm_dusk', 'Gradient style', [
            'warm_dusk' => 'Warm dusk', 'sunrise' => 'Sunrise', 'ember' => 'Ember', 'orchid' => 'Orchid',
            'twilight' => 'Twilight', 'honey' => 'Honey', 'plum' => 'Plum', 'mono' => 'Ink and coral',
        ], 'Used on primary buttons, the pricing highlight and gradient backgrounds.'),
        'mesh_style' => $opt('sunset', 'Background mesh', [
            'sunset' => 'Sunset', 'dawn' => 'Dawn', 'ember' => 'Ember', 'dusk' => 'Dusk',
            'haze' => 'Haze', 'orchid' => 'Orchid', 'amber' => 'Amber', 'midnight' => 'Midnight',
        ], 'Soft colour fields behind the hero, sign-in page and premium sections.'),
    ]);
    $section('appearance', 'Theme', [
        'default_theme' => $opt('light', 'Default theme', ['system' => 'Match visitor’s device', 'light' => 'Light', 'dark' => 'Dark'], 'What first-time visitors see. Members can switch themes if you allow it below.'),
        'allow_theme_toggle' => $yesno(1, 'Let members switch theme', ''),
        'dark_intensity' => $opt('medium', 'Dark mode depth', ['soft' => 'Soft', 'medium' => 'Medium', 'deep' => 'Deep']),
        'noise_intensity' => $num(4, 'Grain texture (0–10)', 'Fine grain over backgrounds that makes surfaces feel printed rather than flat.', 0, 10),
        'floating_elements' => $opt('on', 'Floating background shapes', ['on' => 'On', 'reduced' => 'Fewer', 'off' => 'Off']),
        'radius_scale' => $opt('default', 'Corner roundness', ['sharp' => 'Sharper', 'default' => 'Default', 'round' => 'Rounder']),
        'empty_art' => $yesno(1, 'Illustrations on empty pages', 'Shown on pages with nothing to list yet, such as no matches or no messages. Off shows a simple icon.'),
    ]);
    $section('appearance', 'Typography', [
        'font_heading' => $opt('plus-jakarta-sans', 'Heading font', [
            'plus-jakarta-sans' => 'Plus Jakarta Sans', 'outfit' => 'Outfit', 'sora' => 'Sora', 'manrope' => 'Manrope',
        ]),
        'font_body' => $opt('inter', 'Body font', ['inter' => 'Inter', 'dm-sans' => 'DM Sans', 'manrope' => 'Manrope']),
        'base_font_size' => $opt('16', 'Base text size', ['14' => '14 px', '15' => '15 px', '16' => '16 px']),
        'heading_tracking' => $opt('tight', 'Heading letter spacing', ['normal' => 'Normal', 'tight' => 'Tight', 'ultra' => 'Ultra tight']),
    ]);
    $section('appearance', 'Motion', [
        'animations_level' => $opt('all', 'Animations', ['all' => 'All', 'reduced' => 'Reduced', 'minimal' => 'Minimal', 'off' => 'Off']),
        'page_reveals' => $opt('stagger', 'Page load reveals', ['stagger' => 'Staggered', 'simultaneous' => 'All at once', 'off' => 'Off']),
        'hover_effects' => $opt('full', 'Hover effects', ['full' => 'Full', 'basic' => 'Basic', 'off' => 'Off']),
        'auth_flip_style' => $opt('flip', 'Sign in ↔ Create account transition', ['flip' => 'Card flip', 'slide' => 'Slide', 'fade' => 'Fade', 'off' => 'No animation']),
        'confetti_on_match' => $yesno(1, 'Confetti on a new match', ''),
        'skeleton_style' => $opt('shimmer', 'Loading placeholders', ['shimmer' => 'Shimmer', 'pulse' => 'Pulse', 'static' => 'Static']),
        'respect_reduced_motion' => $yesno(1, 'Respect visitors’ reduced-motion setting', 'Recommended for accessibility.'),
    ]);
    $section('appearance', 'Custom CSS', [
        'custom_css' => $f('textarea', '', 'Custom CSS', 'Added after the main stylesheet. Use with care.', ['rows' => 8, 'max' => 8000]),
    ]);

    // ===========================================================================
    // LANDING PAGE / HERO / HEADER
    // ===========================================================================
    $tab('landing', 'Hero & header', 'layout-template', 'The first screen visitors see. Edit the other landing sections under Landing page in the sidebar.');
    $section('landing', 'Hero layout & pictures', [
        'hero_layout' => $opt('collage', 'Layout', ['collage' => 'Headline, location search and a collage of pictures', 'simple' => 'Headline and location search over the background', 'split' => 'Headline with stacked profile cards'],
            'The collage sits on a clean page and follows the light or dark theme. The other two layouts use the background chosen below.'),
        'hero_art_1' => $f('image', '@art/hero-1.webp', 'Collage picture 1 (large)', 'Portrait picture, about 4:5. Replace it, remove it, or choose one from the media library.'),
        'hero_art_2' => $f('image', '@art/hero-2.webp', 'Collage picture 2', 'Portrait picture, about 3:4.'),
        'hero_art_3' => $f('image', '@art/hero-3.webp', 'Collage picture 3 (small)', 'Square-ish picture. It overlaps the others.'),
        'hero_chip_1' => $f('text', 'Verified profiles', 'Floating label 1', 'A small label on the collage. Leave blank to hide.', ['max' => 40]),
        'hero_chip_2' => $f('text', 'Shown by distance', 'Floating label 2', 'Leave blank to hide.', ['max' => 40]),
    ]);
    $section('landing', 'Hero background', [
        'hero_bg_type' => $opt('slider', 'Background', [
            'slider' => 'Image slider', 'image' => 'Single image', 'video' => 'Looping video', 'gradient' => 'Animated gradient only', 'parallax' => 'Parallax image',
        ], 'Used by the two layouts that sit over a background. Slider images are managed on the Hero slides page. With no images uploaded the animated gradient is shown.'),
        'hero_image' => $f('image', '', 'Single / parallax image', 'Used when the background is a single image or parallax.'),
        'hero_video' => $f('image', '', 'Background video (MP4)', 'Muted, looping, 15 seconds or less works best.', ['accept' => 'video']),
        'hero_height' => $opt('100', 'Hero height', ['100' => 'Full screen', '85' => '85% of screen', '70' => '70% of screen']),
        'hero_slider_speed' => $num(6, 'Seconds per slide', '', 2, 30),
        'hero_slider_transition' => $opt('kenburns', 'Slide transition', ['fade' => 'Fade', 'slide' => 'Slide', 'zoom' => 'Zoom', 'kenburns' => 'Slow pan and zoom']),
        'hero_slider_indicators' => $yesno(1, 'Show slide dots', ''),
        'hero_pause_hover' => $yesno(1, 'Pause when hovering', ''),
    ]);
    $section('landing', 'Hero overlay & text', [
        'hero_overlay_type' => $opt('gradient', 'Overlay', ['gradient' => 'Gradient from bottom', 'solid' => 'Solid colour', 'none' => 'None']),
        'hero_overlay_color' => $f('color', '#0F0E0D', 'Overlay colour'),
        'hero_overlay_start' => $num(70, 'Overlay strength at the bottom (%)', '', 0, 100),
        'hero_overlay_end' => $num(25, 'Overlay strength at the top (%)', '', 0, 100),
        'hero_text_color' => $opt('light', 'Text colour', ['light' => 'White', 'dark' => 'Dark']),
        'hero_align' => $opt('left', 'Text alignment', ['left' => 'Left', 'center' => 'Centre']),
        'hero_badge' => $f('text', 'Free to join · Verified profiles', 'Small badge above the headline', 'Leave blank to hide.', ['max' => 80]),
        'hero_headline' => $f('text', 'Meet people who live where you do', 'Headline', '', ['max' => 100]),
        'hero_subheadline' => $f('textarea', 'Date, make friends or find good company, in your own town. Real profiles, shown by distance.', 'Sub-headline', '', ['max' => 220]),
        'hero_cta_primary_text' => $f('text', 'Create your profile', 'Primary button text', '', ['max' => 30]),
        'hero_cta_primary_url' => $f('text', '/register', 'Primary button link'),
        'hero_cta_member_mode' => $opt('discover', 'Buttons for people who are already signed in', ['discover' => 'Show one button that opens Discover', 'hide' => 'Hide the buttons']),
        'hero_cta_member_text' => $f('text', 'Open Discover', 'Signed-in button text', 'Replaces the primary button for members, so it never says "Create your profile" to someone who already has one.', ['max' => 30]),
        'hero_mobile' => $opt('compact', 'Hero on phones', ['compact' => 'Compact: short headline and the location search first', 'full' => 'Full: same as on a computer']),
        'hero_cta_secondary_text' => $f('text', 'Look around first', 'Secondary button text', 'Leave blank to hide.', ['max' => 30]),
        'hero_cta_secondary_url' => $f('text', '/browse', 'Secondary button link'),
    ]);
    $section('landing', 'Hero location search', [
        'hero_show_location' => $yesno(1, 'Show the location search', 'Lets visitors pick a county or town to browse.'),
        'hero_location_mode' => $opt('both', 'Location search style', ['pills' => 'Quick buttons', 'search' => 'Search box', 'both' => 'Search box and quick buttons', 'dropdown' => 'Dropdown']),
        'hero_use_counties' => $yesno(1, 'Include all counties in search', ''),
        'hero_use_towns' => $yesno(1, 'Include major towns in search', ''),
        'hero_pill_count' => $num(6, 'Quick buttons to show', 'Uses the locations marked as popular on the Locations page.', 3, 12),
        'hero_location_prompt' => $f('text', 'Where are you?', 'Search box placeholder', '', ['max' => 50]),
    ]);
    $section('landing', 'Header', [
        'header_sticky' => $yesno(1, 'Sticky header', ''),
        'public_dock' => $yesno(1, 'Bottom tab bar on phones for visitors', 'A floating app-style bar on public pages on a phone and tablet. Choose its items under Admin > Navigation > Visitor tab bar. Signed-in members and Hosts get their own tab bar.'),
        'header_transparent_hero' => $yesno(1, 'Transparent header over the hero', 'Turns solid once visitors scroll.'),
        'header_cta_join' => $f('text', 'Join free', 'Join button text', '', ['max' => 24]),
        'header_cta_signin' => $f('text', 'Sign in', 'Sign-in button text', '', ['max' => 24]),
    ]);
    $section('landing', 'Featured profiles on the landing page', [
        'landing_profiles_source' => $opt('newest', 'Which profiles to show', ['newest' => 'Newest', 'random' => 'Random', 'active' => 'Most recently active', 'featured' => 'Featured (paid or admin-picked)']),
        'landing_profiles_count' => $num(12, 'How many', '', 4, 36),
        'landing_show_distance' => $yesno(0, 'Show town on the cards', ''),
    ]);

    // ===========================================================================
    // SIGN IN / REGISTER
    // ===========================================================================
    $tab('auth', 'Sign in & register', 'log-in', 'The flip-card sign-in and registration experience.');
    $section('auth', 'Page content', [
        'auth_bg_image' => $f('image', '@art/auth-1.webp', 'Sign-up picture', 'Shown beside the sign-up form on desktop. Replace it, remove it for an animated gradient, or choose one from the media library.'),
        'auth_bg_image_login' => $f('image', '@art/auth-2.webp', 'Sign-in picture', 'Shown beside the sign-in form. Falls back to the sign-up picture when removed.'),
        'auth_show_testimonials' => $yesno(1, 'Show member stories beside the form', 'Manage them under Testimonials.'),
        'auth_show_stats' => $yesno(1, 'Show the counters at the bottom', ''),
        'auth_stats_mode' => $opt('auto', 'Counter values', ['auto' => 'Real numbers from the database', 'manual' => 'Numbers I type below']),
        'auth_stat1_value' => $f('text', '', 'Counter 1 value (manual mode, leave blank to hide)'),
        'auth_stat1_label' => $f('text', 'Connections made', 'Counter 1 label'),
        'auth_stat2_value' => $f('text', '', 'Counter 2 value (manual mode, leave blank to hide)'),
        'auth_stat2_label' => $f('text', 'Active this week', 'Counter 2 label'),
        'auth_stat3_value' => $f('text', '', 'Counter 3 value (manual mode, leave blank to hide)'),
        'auth_stat3_label' => $f('text', 'Average rating', 'Counter 3 label'),
        'auth_login_title' => $f('text', 'Welcome back', 'Sign-in heading'),
        'auth_login_subtitle' => $f('text', 'Your connections are waiting.', 'Sign-in sub-heading'),
        'auth_register_title' => $f('text', 'Create your account', 'Register heading'),
        'auth_register_subtitle' => $f('text', 'It takes about two minutes.', 'Register sub-heading'),
    ]);
    $section('auth', 'Rules', [
        'password_min_length' => $num(6, 'Shortest password allowed', 'Applies to members and hosts. Administrators always need at least 8.', 4, 32),
        'password_min_strength' => $opt('none', 'Password strength rule', ['none' => 'No rule, length only', 'fair' => 'Fair (mixed case, or a number and a symbol)', 'good' => 'Good (long, mixed case or number)', 'strong' => 'Strong (12+, mixed)'], 'Members can pick a simple password unless you choose a stricter rule here.'),
        'password_block_common' => $yesno(0, 'Block the most common passwords', 'Refuses a short list such as “password” and “12345678”.'),
        'password_show_meter' => $yesno(0, 'Show the strength bar on sign-up', 'A visual hint only. It never stops anyone from signing up.'),
        'require_phone' => $yesno(0, 'Require a phone number', 'Hosts must always give one.'),
        'require_age_confirm' => $yesno(1, 'Require “I am 18 or older” tick box', ''),
        'remember_me_days' => $num(180, 'Stay signed in for (days)', 'When "Keep me signed in" is ticked. The clock is pushed back whenever the member uses the site, so somebody who comes back regularly is never asked to sign in again.', 1, 400),
        'session_hours' => $num(24, 'Sign out inactive sessions after (hours)', 'For members who do not tick “Remember me”.', 1, 720),
        'max_login_attempts' => $num(5, 'Failed sign-ins before lock', '', 3, 20),
        'lockout_minutes' => $num(15, 'Lock duration (minutes)', '', 1, 1440),
        'passcode_enabled' => $yesno(1, 'Offer a passcode lock', 'Members can put a 4-number passcode on their own account under Settings → Privacy. The app then asks for it when they open it again. Their account password always opens it too, so nobody is ever locked out.'),
        'passcode_idle_minutes' => $num(30, 'Ask for the passcode again after (minutes)', 'Minutes of being left alone before the app locks itself again. Set 0 to ask only when the app is opened fresh.', 0, 1440),
        'totp_enabled_for_users' => $yesno(1, 'Offer two-step verification', 'Members can turn on authenticator-app codes under Account → Security.'),
        'admin_require_2fa' => $yesno(0, 'Require two-step verification for admins', 'Admins without it are sent to set it up.'),
    ]);

    // ===========================================================================
    // ONBOARDING & PROFILES
    // ===========================================================================
    $tab('onboarding', 'Onboarding & profiles', 'user-plus', 'The seven-step set-up members complete after registering.');
    $section('onboarding', 'Steps', [
        'onboarding_required' => $f('multiselect', ['basics', 'photos', 'interests', 'location'], 'Required steps', 'Members cannot continue past a required step until it is complete.', ['options' => [
            'basics' => 'The basics', 'photos' => 'Photos', 'about' => 'About you', 'looking' => 'What you want',
            'interests' => 'Interests', 'location' => 'Location',
        ]]),
        'onboarding_disabled' => $f('multiselect', [], 'Hidden steps', 'Steps you do not want to ask for at all.', ['options' => [
            'about' => 'About you', 'looking' => 'What you want', 'interests' => 'Interests', 'location' => 'Location',
        ]]),
        'photos_min' => $num(1, 'Minimum photos', '', 1, 3),
        'photo_cap_all' => $num(30, 'Most photos anyone can have', 'A ceiling for everybody, whatever their plan. Limits per plan are set on the Photo limits page.', 1, 60),
        'photo_uploads_per_day' => $num(0, 'Photos a member can upload per day', '0 means no daily limit. Helps stop abuse.', 0, 100),
        'photos_max_onboarding' => $num(6, 'Photo slots during sign-up', '', 3, 9),
        'interests_min' => $num(5, 'Minimum interests', '', 3, 8),
        'onboarding_show_height' => $yesno(1, 'Ask for height', ''),
        'onboarding_show_education' => $yesno(1, 'Ask for education', ''),
        'onboarding_show_occupation' => $yesno(1, 'Ask for occupation', ''),
        'bio_max_length' => $num(500, 'Bio length limit', '', 100, 2000),
        'onboarding_celebration' => $opt('confetti', 'When finished', ['confetti' => 'Confetti', 'simple' => 'Simple message', 'none' => 'Go straight to Discover']),
        'email_welcome' => $yesno(1, 'Send a welcome email', ''),
    ]);
    $section('onboarding', 'Profile completeness weights', [
        'cw_photo' => $num(25, 'Main photo (%)', 'The weights below should add up to 100.', 0, 100),
        'cw_photos_extra' => $num(10, 'More photos (%)', '', 0, 100),
        'cw_bio' => $num(15, 'Bio (%)', '', 0, 100),
        'cw_interests' => $num(10, 'Interests (%)', '', 0, 100),
        'cw_location' => $num(10, 'Location (%)', '', 0, 100),
        'cw_looking' => $num(10, 'What they want (%)', '', 0, 100),
        'cw_height' => $num(5, 'Height (%)', '', 0, 100),
        'cw_education' => $num(5, 'Education (%)', '', 0, 100),
        'cw_work' => $num(5, 'Occupation (%)', '', 0, 100),
        'cw_verified' => $num(5, 'Verified (%)', '', 0, 100),
    ]);
    $section('onboarding', 'Photos', [
        'photo_moderation' => $opt('auto', 'New photos', ['auto' => 'Appear straight away (review afterwards)', 'manual' => 'Wait for admin approval']),
        'photo_max_mb' => $num(10, 'Largest upload (MB)', '', 1, 25),
        'photo_quality' => $num(82, 'Image quality (50–95)', 'Lower is smaller and faster, higher is sharper.', 50, 95),
        'verification_enabled' => $yesno(1, 'Offer profile verification', 'Members can send a selfie for a verified badge.'),
        'verification_type' => $opt('photo', 'What members must send', ['photo' => 'Selfie only', 'id' => 'ID document only', 'both' => 'Selfie and ID document']),
    ]);

    // ===========================================================================
    // DISCOVERY & MATCHING
    // ===========================================================================
    $tab('discovery', 'Discovery & matching', 'compass', 'How members find each other.');
    $section('discovery', 'Layout', [
        'discover_default_view' => $opt('swipe', 'Default view', ['swipe' => 'Swipe cards', 'grid' => 'Photo grid', 'list' => 'Detailed list'], 'What members see until they choose a view themselves. Swipe is the usual choice; the grid and the list stay one tap away.'),
        'discover_allow_view_switch' => $yesno(1, 'Let members switch view', ''),
        'discover_side_panels' => $yesno(1, 'Side panels on large screens', 'Next to the swipe cards: who likes you, your newest matches and who is online now. Phones and small screens show them below the cards.'),
        'discover_quick_filters' => $yesno(1, 'Quick filters above the cards', 'One-tap choices for what someone is looking for, who is online, verified members and distance.'),
        'discover_show_distance' => $yesno(1, 'Show distance', ''),
        'discover_show_compat' => $yesno(1, 'Show compatibility score', ''),
        'discover_show_online' => $yesno(1, 'Show online dot', ''),
        'discover_show_interests' => $yesno(1, 'Show interests on cards', ''),
        'distance_rounding_km' => $opt('1', 'Round distances to', ['0' => 'Exact', '1' => '1 km', '5' => '5 km', '10' => '10 km'], 'Rounding protects members’ privacy.'),
        'online_window_minutes' => $num(5, 'Count a member as online for (minutes)', '', 1, 60),
        'discover_batch_size' => $num(24, 'Profiles loaded at a time', 'How many people the feed brings in at once, and again each time someone taps Show more.', 5, 60),
    ]);
    $section('discovery', 'Sections on Discover', [
        'discover_mode_switch' => $yesno(1, 'Chip to switch experience', 'Sits next to Filters on Discover, so a member can move between Dating, Escorts and the rest in one tap. Which ones appear is set under Visitor choices.'),
        'discover_mode_filters' => $yesno(1, 'The chosen experience decides the order', 'Dating puts people first and the Escort row at the bottom, Escorts puts the Escort row first, Elite leads with the Elite row. Off: your own order below is always used. This only matters when Visitor choices is not set to show members only what they chose.'),
        'discover_focus_count' => $num(24, 'People shown when someone picked Escorts or Elite', 'With "Show only what they chose" on (Admin > Visitor choices), these members get the whole page, so it should hold plenty.', 6, 60),
        'discover_elite_enabled' => $yesno(1, 'Elite row on Discover', 'A row of Elite members. Only appears while the Elite module is on and profiles are live.'),
        'discover_elite_count' => $num(12, 'Elite members in the row', '', 2, 24),
    ]);
    $section('discovery', 'Likes, swipes and extras', [
        'superlike_enabled' => $yesno(1, 'Super likes', 'Daily amounts are set per plan under Plans.'),
        'rewind_enabled' => $yesno(1, 'Undo last swipe', 'Only plans that include it can use it.'),
        'daily_picks_enabled' => $yesno(1, 'Daily picks', 'A few hand-ranked suggestions refreshed every day.'),
        'daily_picks_count' => $num(8, 'Daily picks per member', '', 1, 24),
        'radius_default' => $num(50, 'Default search distance (km)', '', 1, 500),
        'radius_max' => $num(200, 'Longest search distance (km)', '', 5, 1000),
        'search_page_size' => $num(24, 'Results per page in Search', '', 8, 60),
    ]);
    $section('discovery', 'Filters', [
        'premium_filters' => $f('multiselect', ['height', 'education', 'body_type', 'active_recently', 'has_bio'], 'Filters reserved for premium plans', 'Free members see these locked.', ['options' => [
            'height' => 'Height range', 'education' => 'Education', 'body_type' => 'Body type', 'active_recently' => 'Recently active', 'has_bio' => 'Has a bio',
        ]]),
    ]);
    $section('discovery', 'Ranking weights', [
        'w_interests' => $num(25, 'Shared interests (%)', 'The seven weights must total 100.', 0, 100),
        'w_location' => $num(20, 'Distance (%)', '', 0, 100),
        'w_demographics' => $num(15, 'Age and preference fit (%)', '', 0, 100),
        'w_dwell' => $num(15, 'Time spent on similar profiles (%)', 'Learned from what each member lingers on.', 0, 100),
        'w_search' => $num(10, 'Match with their searches (%)', '', 0, 100),
        'w_activity' => $num(10, 'Recent activity (%)', '', 0, 100),
        'w_quality' => $num(5, 'Profile quality (%)', '', 0, 100),
        'min_match_score' => $num(0, 'Hide profiles scoring below', '0 shows everyone.', 0, 90),
        'new_member_boost_hours' => $num(48, 'Give new members extra reach for (hours)', '', 0, 336),
        'new_member_boost_points' => $num(12, 'Extra points for new members', '', 0, 50),
        'boost_multiplier' => $num(3, 'Boosted profile ranking multiplier', 'Applies to active Boosts and Featured profiles.', 1, 10, 0.5),
    ]);
    $section('discovery', 'Visitors who are not signed in', [
        'guest_browse_enabled' => $yesno(1, 'Public Browse page', 'Lets visitors look at profiles before signing up.'),
        'guest_browse_limit' => $num(12, 'Profiles visitors can see', '', 4, 60),
    ]);

    // ===========================================================================
    // MESSAGING
    // ===========================================================================
    $tab('messaging', 'Messaging', 'message-circle', 'Who can message whom, and what they can send.');
    $section('messaging', 'Access', [
        'messaging_enabled' => $yesno(1, 'Messaging is on', 'Turn off to hide messaging everywhere.'),
        'msg_access' => $opt('matched', 'Who can start a conversation', [
            'public' => 'Anyone (visitors are asked to sign in first)',
            'logged_in' => 'Any signed-in member',
            'premium' => 'Premium and VIP members only',
            'matched' => 'Matched members only',
        ], 'Members who cannot start a conversation can still reply if the option below is on.'),
        'msg_hosts_access' => $opt('logged_in', 'Who can message Hosts', ['logged_in' => 'Any signed-in member', 'premium' => 'Premium and VIP members', 'vip' => 'VIP members only']),
        'msg_free_can_reply' => $yesno(1, 'Free members can always reply', ''),
        'msg_max_length' => $num(1000, 'Longest message (characters)', '', 100, 5000),
    ]);
    $section('messaging', 'Content', [
        'msg_images' => $opt('on', 'Photo sharing', ['on' => 'Everyone who can message', 'premium' => 'Premium and VIP only', 'off' => 'Off']),
        'msg_max_image_mb' => $num(5, 'Largest photo (MB)', '', 1, 15),
        'msg_mask_contacts' => $yesno(1, 'Hide phone numbers and emails in early messages', 'Keeps people on the platform until they have properly started talking.'),
        'msg_unlock_after' => $num(10, 'Show contact details after this many messages', 'Counts all messages in the conversation. Premium members are never masked.', 0, 200),
        'msg_delete_own' => $yesno(1, 'Let members delete their own messages', 'Tap a sent message, then Delete. It disappears for both people.'),
        'msg_edit_own' => $yesno(1, 'Let members edit their own messages', 'Tap a sent text message, then Edit. The chat shows "edited" beside the time. Photos cannot be edited.'),
        'msg_edit_window' => $num(15, 'Editing allowed for (minutes after sending)', '0 means no time limit.', 0, 1440),
        'profanity_action' => $opt('mask', 'Banned words', ['off' => 'Allow', 'mask' => 'Replace with ***', 'block' => 'Block the message'], 'List the words under Safety.'),
    ]);
    $section('messaging', 'Live features', [
        'msg_read_receipts' => $opt('premium', 'Read receipts', ['always' => 'Always shown', 'premium' => 'Only to premium members', 'off' => 'Off']),
        'msg_typing' => $yesno(1, 'Typing indicator', ''),
        'msg_poll_ms' => $num(3000, 'Check for new messages every (ms)', '', 1500, 15000, 500),
        'msg_icebreakers' => $yesno(1, 'Conversation starters', 'Suggested openers shown in new chats. Manage them under Conversation starters.'),
        'msg_icebreaker_count' => $num(3, 'Starters shown', '', 1, 6),
        'msg_retention_days' => $num(0, 'Delete messages older than (days)', '0 keeps them forever. Runs from the daily cron job.', 0, 3650),
    ]);

    // ===========================================================================
    // PRIVACY & VISIBILITY
    // ===========================================================================
    $tab('privacy', 'Privacy & visibility', 'eye-off', 'Photo blurring and who can see contact details.');
    $section('privacy', 'Photo blur for visitors who are not signed in', [
        'blur_guests_landing' => $yesno(1, 'Blur photos on the landing page', 'Visitors receive a blurred copy of the image, never the sharp one, so it cannot be un-blurred in the browser.'),
        'blur_guests_browse' => $yesno(1, 'Blur photos on the public Browse page', ''),
        'blur_guests_profile' => $yesno(1, 'Blur photos on profile pages', ''),
        'blur_guests_hosts' => $yesno(0, 'Blur Host photos for visitors', 'Applies to the Host cards on the landing page, the directory and Host profiles. Manage all of this in one place under Admin > Agent visibility.'),
        'blur_level' => $opt('medium', 'Blur strength', ['light' => 'Light (shape visible)', 'medium' => 'Medium', 'heavy' => 'Heavy (silhouette only)']),
        'blur_gallery_free' => $yesno(0, 'Free members see only the main photo sharp', 'Their other photos stay blurred until they upgrade or match.'),
    ]);
    $section('privacy', 'Dating members', [
        'dating_requires_account' => $yesno(1, 'Visitors must create an account to see dating members', 'Browse, member profiles and the member blocks on the home page show blurred previews and a sign-up prompt. Hosts and Elite follow their own settings.'),
    ]);
    $section('privacy', 'What visitors see on a profile page', [
        'guest_profile_view' => $opt('limited', 'Profile page for visitors', ['full' => 'Everything a member sees', 'limited' => 'Limited preview with a sign-up prompt', 'login' => 'Ask them to sign in first']),
        'guest_bio_chars' => $num(120, 'Bio characters shown in the preview', '', 0, 500),
    ]);
    $section('privacy', 'Contact details', [
        'host_contact_after_booking' => $yesno(1, 'Reveal a Host’s contact details once a booking is confirmed', 'Overrides the table below for that client only.'),
    ], 'A member’s contact detail is shown only when they have chosen to share it AND the table below allows it for the viewer. Visitors who are not signed in never see contact details regardless of this table.');
    foreach (['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'instagram' => 'Instagram'] as $ck => $cl) {
        $defaults = ['free' => 'hidden', 'premium' => 'hidden', 'vip' => 'visible', 'matched' => 'visible'];
        if ($ck === 'email') { $defaults['vip'] = 'hidden'; }
        if ($ck === 'instagram') { $defaults = ['free' => 'hidden', 'premium' => 'visible', 'vip' => 'visible', 'matched' => 'visible']; }
        $defs = [];
        foreach (['free' => 'Free members', 'premium' => 'Premium members', 'vip' => 'VIP members', 'matched' => 'Matched members'] as $tk => $tl) {
            $defs["contact_{$ck}_{$tk}"] = $opt($defaults[$tk], $tl, ['hidden' => 'Hidden', 'visible' => 'Visible']);
        }
        $section('privacy', "$cl visibility", $defs);
    }

    // ===========================================================================
    // HOSTS
    // ===========================================================================
    $tab('hosts', 'Hosts', 'star', 'Companion hosts: sign-up, approval, directory access and bookings.');
    $section('hosts', 'Hosts on the platform', [
        'hosts_enabled' => $yesno(1, 'Host accounts are on', 'Controls the Host tab at sign-up, the directory and bookings.'),
        'host_requires_approval' => $yesno(1, 'Host applications need approval', 'If off, applications go live as soon as they are submitted.'),
        'host_approval_sla_hours' => $opt('24', 'Review time shown to applicants', ['12' => '12 hours', '24' => '24 hours', '48' => '48 hours']),
        'host_min_photos' => $num(3, 'Minimum photos', '', 1, 6),
        'host_max_photos' => $num(12, 'Maximum photos', '', 3, 30),
        'host_allow_video' => $yesno(1, 'Allow an intro video', ''),
        'host_video_max_seconds' => $opt('30', 'Longest video', ['15' => '15 seconds', '30' => '30 seconds', '60' => '60 seconds']),
        'host_video_max_mb' => $opt('50', 'Largest video file', ['25' => '25 MB', '50' => '50 MB', '100' => '100 MB']),
        'host_review_moderation' => $opt('manual', 'Reviews', ['auto' => 'Publish straight away', 'manual' => 'Admin approves first']),
    ]);
    $section('hosts', 'Connection profiles', [
        'host_connections_enabled' => $yesno(0, 'Enable connection profiles', 'Adds an optional adult-only social connection profile to Host accounts. This is separate from the existing Host booking directory.'),
        'host_connections_require_approval' => $yesno(1, 'Approve connection profiles before publishing', 'When enabled, each connection profile must be approved by an administrator before it appears publicly.'),
        'host_connections_show_contact' => $yesno(1, 'Allow contact details on approved profiles', 'Hosts can choose which permitted contact method to display. Visitors should use the platform safely.'),
        'host_connections_allow_gallery' => $yesno(1, 'Allow photo galleries', 'Hosts choose approved photos to show on their public listing. Turn off to keep listings text-only.'),
        'host_connections_max_photos' => $num(6, 'Maximum photos per profile', '', 1, 20),
        'host_connections_max_services' => $num(6, 'Maximum services on each card', 'Extra services collapse behind a "+ more" marker.', 2, 12),
        'host_connections_page_title' => $f('text', 'Connection profiles', 'Public page title', 'The visible heading on /connections.', ['max' => 90]),
        'host_connections_page_subtitle' => $f('textarea', 'Browse adult profiles for friendly conversations, public events, shared interests and non-sexual companionship. Take your time, be respectful and keep safety in mind.', 'Public page subtitle', 'Shown below the heading.', ['max' => 300]),
        'host_connections_menu_label' => $f('text', 'Connection profiles', 'Menu label', 'Shown in the Host side menu and in links to this module.', ['max' => 40]),
        'host_connections_card_style' => $opt('compact', 'Service card style', ['compact' => 'Compact chips (code + label)', 'code' => 'Code only (smallest)', 'label' => 'Label only (text pills)'], 'How selected services look on public cards and profiles.'),
        'host_connections_show_on_homepage' => $yesno(0, 'Show a Connection profiles band on the landing page', 'Not yet implemented in all themes; the setting is recorded for future use.'),
        'host_connections_show_in_header' => $yesno(1, 'Show a link in the public header navigation', 'Adds "Connection profiles" to the public navigation menu.'),
        'host_connections_show_in_footer' => $yesno(1, 'Show a link in the public footer', ''),
    ], 'Everything the Connection Profiles module needs. Settings on this page are also reachable from Admin > Connection profiles > Settings.');
    $section('hosts', 'How the Host module works', [
        'booking_enabled' => $yesno(1, 'Clients can book Hosts on the site', 'Managed together with the Booking / Contact switch under Admin > Hosts. Contact mode disables new booking and deposit flows; existing bookings remain available for completion.') + ['ui' => false],
    ], 'Choose whether Hosts work like a booking page or like a directory where people simply get in touch. You can change this at any time.');
    $section('hosts', 'Directory access', [
        'hosts_directory_access' => $opt('everyone', 'Who can browse Hosts', ['everyone' => 'Everyone (photos blur for visitors only when blurring is on)', 'logged_in' => 'Signed-in members', 'premium' => 'Premium and VIP members', 'vip' => 'VIP members only']),
        'hosts_age_gate' => $yesno(1, 'Ask visitors to confirm they are 18 or older', 'Shown once per session before the directory opens.'),
        'host_show_rates' => $yesno(1, 'Show rates publicly', ''),
        'hosts_per_page' => $num(12, 'Hosts per page', '', 6, 48),
        'hosts_hero_image' => $f('image', '@art/hosts.webp', 'Directory banner picture', 'Fills the background of the top section of the Hosts page. Replace it, remove it, or choose one from the media library.'),
    ]);
    $section('hosts', 'Search and SEO', [
        'host_seo_keywords' => $f('text', '', 'Keywords for every page of this section', 'Phrases people search for, separated by commas. Easier to manage under Admin > Host search and SEO.', ['max' => 500]),
        'host_seo_profile_title' => $f('text', '{name}, {category} in {place}', 'Search title of a profile', 'Parts you can use: {name} {first_name} {category} {town} {county} {place} {concierge} {site}.', ['max' => 120]),
        'host_seo_profile_desc' => $f('text', '', 'Search description of a profile', 'Leave empty to use the profile\'s own headline and about text. Same parts as above, plus {tagline} and {about}.', ['max' => 300]),
        'host_seo_profile_keywords' => $yesno(1, 'Add keywords to each profile', 'Its name, its category and place, and your section keywords.'),
        'host_seo_faq_show' => $yesno(1, 'Show questions and answers', 'Under the directory and on the 18+ page. Also tells Google about them.'),
        'host_seo_faq' => $f('textarea', '', 'Questions and answers', 'A question on one line, the answer under it, a blank line between items. Empty shows a helpful starter set.', ['max' => 4000]),
        'host_seo_index_gate' => $yesno(1, 'Let search engines read the 18+ page', 'Visitors confirm their age first, so this is the page Google sees. It carries your title, description and text.'),
        'host_seo_schema' => $yesno(1, 'Add structured data', 'Helps Google understand the directory and each profile.'),
    ], 'How this section appears in Google and when shared. The full editor with previews and a checklist is under Admin > Host search and SEO.');
    $section('hosts', 'Bookings & money', [
        'booking_deposit_pct' => $num(50, 'Deposit paid at booking (%)', '100 means the full amount up front.', 0, 100),
        'host_commission_pct' => $num(15, 'Commission on each booking (%)', 'Kept by the platform from the host’s earning.', 0, 50, 0.5),
        'host_min_rate' => $num(1000, 'Lowest hourly rate allowed', '', 0, 1000000),
        'host_min_notice_hours' => $num(12, 'Minimum notice for a booking (hours)', '', 0, 168),
        'host_max_advance_days' => $num(60, 'How far ahead clients can book (days)', '', 1, 365),
        'host_payout_min' => $num(2000, 'Smallest payout a host can request', '', 0, 1000000),
    ]);

    // ===========================================================================
    // PAYMENTS & MONETISATION
    // ===========================================================================
    $tab('payments', 'Payments & pricing', 'wallet', 'How members pay and what the extras cost.');
    $section('payments', 'Test mode', [
        'payments_test_mode' => $yesno(0, 'Simulate payments (test mode)', 'Checkout shows a “Simulate successful payment” button instead of charging anyone. Turn this OFF before going live.'),
    ]);
    $section('payments', 'M-Pesa (Daraja STK Push)', [
        'mpesa_enabled' => $yesno(0, 'Accept M-Pesa', 'Needs Daraja credentials below. Safaricom sends the result to the callback address shown under the form.'),
        'mpesa_env' => $opt('sandbox', 'Environment', ['sandbox' => 'Sandbox (testing)', 'production' => 'Production (live)']),
        'mpesa_shortcode' => $f('text', '174379', 'Paybill or till number'),
        'mpesa_type' => $opt('CustomerPayBillOnline', 'Transaction type', ['CustomerPayBillOnline' => 'Paybill', 'CustomerBuyGoodsOnline' => 'Buy Goods (till)']),
        'mpesa_consumer_key' => $f('secret', '', 'Consumer key'),
        'mpesa_consumer_secret' => $f('secret', '', 'Consumer secret'),
        'mpesa_passkey' => $f('secret', '', 'Passkey'),
        'mpesa_callback_secret' => $f('secret', '', 'Callback secret', 'Generated automatically. It is part of the callback address so only Safaricom’s request for your payments is accepted.'),
    ]);
    $section('payments', 'Manual payments', [
        'manual_mpesa_enabled' => $yesno(0, 'Accept manual M-Pesa', 'Members pay to your Paybill or till themselves, then enter the M-Pesa code for you to confirm.'),
        'manual_mpesa_details' => $f('text', '', 'Where to pay', 'For example: Paybill 123456, account CONNECTHUB.', ['max' => 120]),
        'manual_mpesa_type' => $opt('paybill', 'How members pay you', ['paybill' => 'Pay Bill (business number and account)', 'till' => 'Buy Goods and Services (till number)', 'phone' => 'Send Money (to a phone number)', 'pochi' => 'Pochi la Biashara (phone number)']),
        'manual_mpesa_number' => $f('text', '', 'Paybill, till or phone number', 'Shown in big digits with a Copy button. Leave empty to keep using the "Where to pay" text.', ['max' => 15]),
        'manual_mpesa_account' => $f('text', '', 'Account number (Pay Bill only)', 'Leave empty to use each order\'s own reference, which helps you match payments.', ['max' => 20]),
        'manual_mpesa_name' => $f('text', '', 'Name M-Pesa shows when paying', 'For example your business name. Members check it before entering their PIN.', ['max' => 60]),
        'manual_mpesa_ussd' => $yesno(1, 'Show an "Open M-PESA menu" button on Android phones', 'It opens the phone dialler with *334#, the Safaricom M-PESA menu. iPhones get a short hint instead.'),
        'bank_enabled' => $yesno(0, 'Accept bank transfer', ''),
        'bank_name' => $f('text', '', 'Bank'),
        'bank_account_name' => $f('text', '', 'Account name'),
        'bank_account_number' => $f('text', '', 'Account number'),
        'bank_branch' => $f('text', '', 'Branch'),
        'manual_instructions' => $f('textarea', 'We confirm manual payments within a few hours. Keep the M-PESA message until your order is on.', 'Instructions shown to members', '', ['max' => 400]),
    ]);
    $section('payments', 'Boosts and featured profiles', [
        'boost_enabled' => $yesno(1, 'Members can use boosts', 'Lengths and prices are set on the Boosts page in the admin menu.'),
        'featured_enabled' => $yesno(1, 'Members can buy a featured spot', ''),
        'boost_credit_minutes' => $num(30, 'Length of one plan boost credit (minutes)', 'Free boosts that come with a plan last this long.', 5, 10080),
        'boost_daily_limit' => $num(0, 'Most boosts a member can start in 24 hours', '0 means no limit. Counts purchases and plan credits.', 0, 50),
        'boost_stack' => $yesno(1, 'A new boost adds time to a running one', 'Off means members must wait for the boost to end before starting another.'),
    ]);
    $section('payments', 'Extras', [
        'gifts_enabled' => $yesno(1, 'Virtual gifts', 'Gift prices are set under Gifts.'),
        'show_weekly_option' => $yesno(1, 'Offer weekly plans', 'Shows a weekly choice next to monthly and yearly for plans that have a weekly price.'),
        'show_yearly_toggle' => $yesno(1, 'Offer yearly plans', 'Shows a yearly choice for plans that have a yearly price.'),
    ]);

    // ===========================================================================
    // ADVERTISING
    // ===========================================================================
    $tab('ads', 'Advertising', 'megaphone', 'Where sponsored cards appear.');
    $section('ads', 'Placement', [
        'ads_enabled' => $yesno(1, 'Show advertisements', 'Plans that include “ad free” never see ads.'),
        'ads_discover_every' => $num(8, 'Ad card after every N profiles in Discover and Search', '0 turns it off.', 0, 50),
        'ads_banner_landing' => $yesno(1, 'Banner ad on the landing page', ''),
        'ads_popup_enabled' => $yesno(0, 'Pop-up ads', 'At most once per visit.'),
        'ads_label' => $f('text', 'Sponsored', 'Label on ads', '', ['max' => 20]),
    ]);
    $section('ads', 'Businesses buying adverts', [
        'promo_enabled' => $yesno(1, 'Let businesses buy adverts', 'Shows the Advertise page and its footer link. Packages and prices are under Adverts > Packages.'),
        'promo_review' => $yesno(1, 'Check each paid advert before it goes live', 'Recommended. The days of the package start counting when you approve it.'),
        'promo_max_shown' => $num(6, 'Adverts in one block', 'The most that show together on the home page or in a list. One or two adverts get a roomier layout on their own.', 1, 12),
        'promo_rules' => $f('textarea', "No adult services, escorts or sexual content.\nNothing illegal, misleading or unsafe.\nThe picture must be yours to use, and sharp enough to look good small.\nThe link must go to a real website, shop or social page.\nWe may decline or remove an advert that breaks these rules, and refund it if it never ran.", 'Rules shown to advertisers', 'One rule per line. They appear on the Advertise page.', ['max' => 1200]),
    ]);

    // ===========================================================================
    // EMAIL
    // ===========================================================================
    $tab('email', 'Email', 'mail', 'How the site sends email, and which messages it sends.');
    $section('email', 'Sending', [
        'mail_driver' => $opt('mail', 'Method', mail_driver_options(), 'Server default needs nothing but some hosts block it. SMTP and the API providers are the reliable choices for a busy site.'),
        'mail_from_name' => $f('text', 'ConnectHub', 'From name'),
        'mail_from_email' => $f('email', 'no-reply@connecthub.co.ke', 'From address', 'Use an address on your own domain so messages are not marked as spam. API providers only accept addresses you verified with them.'),
        'mail_fallback_server' => $yesno(1, 'If the provider fails, try the server default', 'A failed message is retried once with PHP mail so important emails still have a chance to arrive.'),
        'smtp_preset' => $opt('custom', 'SMTP provider', array_map(fn($p) => $p[0], mail_smtp_presets()), 'Picking a provider fills in its server and port for you. Choose "Another SMTP server" to type your own.'),
        'smtp_host' => $f('text', '', 'SMTP host', 'Only used for "Another SMTP server".'),
        'smtp_port' => $num(587, 'SMTP port', 'Only used for "Another SMTP server".', 1, 65535),
        'smtp_secure' => $opt('tls', 'Encryption', ['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None'], 'Only used for "Another SMTP server".'),
        'smtp_user' => $f('text', '', 'SMTP username'),
        'smtp_pass' => $f('secret', '', 'SMTP password', 'Stored on the server and never shown again.'),
        'mail_ses_region' => $f('text', 'us-east-1', 'Amazon SES region', 'For example us-east-1 or eu-west-1.'),
        'mail_api_key' => $f('secret', '', 'API key or token', 'Stored on the server and never shown again.'),
        'mail_mailgun_domain' => $f('text', '', 'Mailgun sending domain', 'For example mg.yoursite.com.'),
        'mail_mailgun_region' => $opt('us', 'Mailgun region', ['us' => 'United States', 'eu' => 'Europe']),
    ]);
    $section('email', 'Alerts to you', [
        'admin_alert_email' => $f('text', '', 'Send alerts to', 'One or more email addresses, separated by commas. Leave empty to use the support email.'),
        'admin_alert_gift' => $yesno(1, 'A gift was sent', 'So you can check it in Wallet and gifts.'),
        'admin_alert_payout' => $yesno(1, 'Someone asked for a payout', 'Gift wallet and Host payout requests.'),
        'admin_alert_verification' => $yesno(1, 'A member sent a verification photo', ''),
        'admin_alert_payment' => $yesno(0, 'Any payment was completed', 'Can be many emails on a busy site.'),
        'admin_alert_intl' => $yesno(1, 'Someone joined the international page or needs approval', ''),
        'admin_alert_advert' => $yesno(1, 'A paid advert needs approval', 'Sent when a business has paid and the advert waits for your check.'),
        'admin_alert_elite' => $yesno(1, 'Someone asked to join Elite or changed their Elite profile', ''),
    ]);
    $section('email', 'Messages sent to members', [
        'email_on_match' => $yesno(1, 'New match', ''),
        'email_on_message' => $yesno(1, 'New message (when they are offline)', ''),
        'email_on_like' => $yesno(0, 'Someone liked them', ''),
        'email_on_gift' => $yesno(1, 'Gift received', ''),
        'email_throttle_minutes' => $num(30, 'At most one message email per conversation every (minutes)', '', 1, 1440),
        'email_digest' => $yesno(1, 'Weekly summary email', 'Sent by the weekly cron job to members who have not opted out.'),
    ]);

    // ===========================================================================
    // SAFETY & MODERATION
    // ===========================================================================
    $tab('safety', 'Safety & moderation', 'shield-check', 'Keep the community safe.');
    $section('safety', 'Reports and words', [
        'reports_auto_suspend' => $num(5, 'Suspend a member for review after this many different people report them', '0 turns it off.', 0, 50),
        'banned_words' => $f('textarea', '', 'Banned words', 'Separated by commas. Used in messages and bios according to the “Banned words” option under Messaging.', ['max' => 2000]),
        'report_reasons' => $f('textarea', "Fake profile\nInappropriate photos\nHarassment or abuse\nSpam or scam\nUnder 18\nSomething else", 'Report reasons', 'One per line, shown in the report form.', ['rows' => 6, 'max' => 800]),
    ]);
    $section('safety', 'Admin access', [
        'admin_ip_whitelist' => $f('textarea', '', 'Admin IP allow-list', 'One address per line. Leave empty to allow admins from anywhere. A wrong entry can lock you out.', ['max' => 800]),
        'trust_proxy_headers' => $yesno(0, 'Trust proxy headers', 'Turn on only if the site is behind Cloudflare or another proxy, so visitor addresses are read correctly.'),
    ]);

    // ===========================================================================
    // SEO & SOCIAL
    // ===========================================================================
    $tab('seo', 'SEO & social', 'search', 'Search engines, social sharing and analytics.');
    $section('seo', 'Search and sharing', [
        'meta_keywords' => $f('text', 'free dating site Kenya, dating Kenya, meet people Nairobi, singles near me, friends, companionship', 'Keywords', '', ['max' => 250]),
        'og_image' => $f('image', '', 'Share image', '1200 × 630 px. Shown when links are shared on social media.'),
        'twitter_handle' => $f('text', '', 'X (Twitter) handle', 'Without the @.'),
        'robots_extra' => $f('textarea', '', 'Extra robots.txt lines', 'Added to the generated robots.txt.', ['max' => 800]),
        'google_analytics_id' => $f('text', '', 'Google Analytics 4 ID', 'For example G-XXXXXXXXXX. Loaded only after visitors accept cookies.'),
    ]);
    $section('seo', 'Social profiles', [
        'social_facebook' => $f('url', '', 'Facebook'),
        'social_instagram' => $f('url', '', 'Instagram'),
        'social_twitter' => $f('url', '', 'X (Twitter)'),
        'social_tiktok' => $f('url', '', 'TikTok'),
        'social_youtube' => $f('url', '', 'YouTube'),
    ]);
    $section('seo', 'Legal and cookies', [
        'terms_slug' => $f('text', 'terms-of-service', 'Terms page', 'The slug of the page under Pages.'),
        'privacy_slug' => $f('text', 'privacy-policy', 'Privacy page', 'The slug of the page under Pages.'),
        'cookie_banner_enabled' => $yesno(1, 'Cookie notice', ''),
        'cookie_banner_text' => $f('text', 'We use cookies to keep you signed in and to understand how the site is used.', 'Cookie notice text', '', ['max' => 200]),
    ]);

    // ===========================================================================
    // PWA
    // ===========================================================================
    $tab('pwa', 'Mobile app (PWA)', 'smartphone', 'Let members add ConnectHub to their home screen.');
    $section('pwa', 'Install', [
        'pwa_enabled' => $yesno(1, 'Installable app', 'Adds the manifest and service worker.'),
        'pwa_install_prompt' => $yesno(1, 'Show our own install banner', ''),
        'pwa_prompt_after_visits' => $num(3, 'Show the banner from visit number', '', 1, 20),
        'pwa_short_name' => $f('text', '', 'Name under the app icon', 'Leave blank to use the site name.', ['max' => 14]),
        'pwa_offline_message' => $f('text', 'You are offline. Reconnect to keep connecting.', 'Offline message', 'Shown on the offline page.', ['max' => 140]),
    ]);
    $section('pwa', 'App identity', [
        'pwa_name' => $f('text', '', 'Full app name', 'Leave blank to use the site name and tagline.', ['max' => 45]),
        'pwa_description' => $f('textarea', '', 'App description', 'Leave blank to use the site description.', ['max' => 200]),
        'pwa_icon' => $f('image', '', 'App icon', 'Square PNG, at least 512 x 512 px. Leave empty to use the favicon or icon logo. Phones may take a day to show a new icon.'),
        'pwa_theme_color' => $f('color', '', 'Theme colour', 'Colours the phone status bar and the app window. Empty uses the primary colour.'),
        'pwa_background_color' => $f('color', '#FBFAF8', 'Splash background', 'The colour behind the icon while the app opens.'),
    ]);
    $section('pwa', 'How the app opens', [
        'pwa_display' => $opt('standalone', 'Window style', ['standalone' => 'App window, no browser bar', 'minimal-ui' => 'App window with a small back and reload bar', 'fullscreen' => 'Full screen', 'browser' => 'Normal browser tab'], 'Standalone feels most like an app.'),
        'pwa_orientation' => $opt('portrait-primary', 'Screen direction', ['any' => 'Any direction', 'portrait-primary' => 'Upright', 'landscape-primary' => 'Sideways'], ''),
        'pwa_start_path' => $f('text', '/', 'Opens on', 'A page on this site, for example /discover or /. Members who are signed in are taken on from there.', ['max' => 80]),
        'pwa_shortcuts' => $f('textarea', "Discover | /discover\nMessages | /messages", 'Shortcuts', 'Shown when someone long-presses the app icon. One per line as Label | /page, up to 4.', ['rows' => 4, 'max' => 400]),
    ]);
    $section('pwa', 'Offline and updates', [
        'pwa_cache_assets' => $yesno(1, 'Keep styles and scripts on the phone', 'Makes the app open faster and work with weak signal. Turn off while you are changing the site design.'),
        'pwa_cache_version' => $num(1, 'Cache version', 'Raise this number to make every installed app fetch fresh files. The PWA page has a button that does it for you.', 1, 99999),
    ]);

    $schema = ['tabs' => $tabs, 'fields' => $fields];

    // Modules may add settings by dropping a file in includes/settings/ that
    // returns [tabKey => [sectionTitle => [key => field, ...]]].
    foreach (glob(__DIR__ . '/settings/*.php') ?: [] as $extra) {
        $extraDefs = require $extra;
        foreach ($extraDefs as $tabKey => $sections) {
            if (!isset($schema['tabs'][$tabKey])) {
                continue;
            }
            foreach ($sections as $title => $defs) {
                $keys = [];
                foreach ($defs as $key => $def) {
                    $def += ['type' => 'text', 'default' => '', 'label' => $key, 'help' => '', 'tab' => $tabKey, 'section' => $title];
                    $schema['fields'][$key] = $def;
                    if (($def['ui'] ?? true) !== false) {
                        $keys[] = $key; // 'ui' => false: a real setting with a default, edited on its own admin page
                    }
                }
                if ($keys) {
                    $schema['tabs'][$tabKey]['sections'][] = ['title' => $title, 'note' => '', 'keys' => $keys];
                }
            }
        }
    }

    return $schema;
}
