<?php
/**
 * Hosts module settings (merged into the "hosts" tab by includes/settings_schema.php).
 * Keys that already exist in the core catalogue (hosts_enabled, booking_deposit_pct, host_commission_pct, ...)
 * are NOT repeated here.
 */

return [
    'hosts' => [
        'How the module works' => [
            'host_module_mode' => ['type' => 'select', 'default' => 'contact', 'ui' => false, 'label' => 'How people reach a Host',
                'options' => ['booking' => 'Booking: clients pick a time and pay a deposit', 'contact' => 'Contact: people get in touch directly, no booking'],
                'help' => 'Switch any time. Nothing is deleted, so you can go back to booking and every rate, package and past booking is still there.'],
            'host_service_prices' => ['type' => 'bool', 'default' => 1, 'ui' => false, 'label' => 'Hosts may put a price on a service', 'help' => 'Contact way only. A Host can leave every price empty, and then their services are only named.'],
            'host_service_card_style' => ['type' => 'select', 'default' => 'tint', 'ui' => false, 'label' => 'How service cards look', 'options' => ['tint' => 'Soft colour', 'solid' => 'Full colour', 'outline' => 'Outlined'], 'help' => 'Contact way only.'],
            'host_contact_intro_enabled' => ['type' => 'bool', 'default' => 1, 'ui' => false, 'label' => 'Hosts may write a short line about getting in touch', 'help' => 'Shown above the contact buttons, for example “I answer fastest on WhatsApp”.'],
            'host_contact_headline' => ['type' => 'text', 'default' => '', 'max' => 70, 'ui' => false, 'label' => 'Heading above the contact buttons', 'help' => 'Leave empty for “Get in touch”.'],
        ],
        'Booking rules' => [
            'host_instant_booking_enabled' => ['type' => 'bool', 'default' => 1, 'label' => 'Hosts can turn on instant booking', 'help' => 'With instant booking a paid deposit confirms the booking straight away. Otherwise the Host accepts each request.'],
            'host_accept_hours' => ['type' => 'number', 'default' => 12, 'min' => 1, 'max' => 72, 'step' => 1, 'label' => 'Time a Host has to answer a request (hours)', 'help' => 'Requests that are not answered in time are declined automatically and the deposit is refunded.'],
            'host_booking_buffer_minutes' => ['type' => 'number', 'default' => 30, 'min' => 0, 'max' => 240, 'step' => 15, 'label' => 'Gap kept between two bookings (minutes)', 'help' => 'Gives the Host time to travel. Slots inside the gap are not offered.'],
            'host_slot_step_minutes' => ['type' => 'number', 'default' => 30, 'min' => 15, 'max' => 120, 'step' => 15, 'label' => 'Start times are offered every (minutes)', 'help' => ''],
            'host_booking_max_hours' => ['type' => 'number', 'default' => 12, 'min' => 1, 'max' => 24, 'step' => 1, 'label' => 'Longest single booking (hours)', 'help' => ''],
            'host_payment_hold_minutes' => ['type' => 'number', 'default' => 45, 'min' => 10, 'max' => 240, 'step' => 5, 'label' => 'Hold a slot while the client pays (minutes)', 'help' => 'An unpaid booking is cancelled after this time and the slot is released.'],
            'host_max_unpaid_bookings' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10, 'step' => 1, 'label' => 'Unpaid bookings one client can hold at once', 'help' => 'Stops people from reserving many slots without paying.'],
        ],
        'Cancellations, completion and payouts' => [
            'host_cancel_full_hours' => ['type' => 'number', 'default' => 48, 'min' => 0, 'max' => 720, 'step' => 1, 'label' => 'Full deposit refund when the client cancels at least this many hours before the start', 'help' => 'Requests the Host has not accepted yet can always be cancelled for a full refund.'],
            'host_cancel_partial_hours' => ['type' => 'number', 'default' => 24, 'min' => 0, 'max' => 720, 'step' => 1, 'label' => 'Partial refund when the client cancels at least this many hours before the start', 'help' => 'Later than this the deposit is kept.'],
            'host_cancel_partial_pct' => ['type' => 'number', 'default' => 50, 'min' => 0, 'max' => 100, 'step' => 5, 'label' => 'Partial refund (% of the deposit)', 'help' => ''],
            'host_auto_complete_hours' => ['type' => 'number', 'default' => 12, 'min' => 1, 'max' => 168, 'step' => 1, 'label' => 'Mark a booking completed automatically this many hours after it ends', 'help' => 'Applies when nobody has reported a problem or marked it completed.'],
            'host_reminder_hours' => ['type' => 'number', 'default' => 24, 'min' => 1, 'max' => 168, 'step' => 1, 'label' => 'Send a reminder this many hours before the start', 'help' => 'Needs the cron job to run.'],
            'host_payout_hold_hours' => ['type' => 'number', 'default' => 24, 'min' => 0, 'max' => 720, 'step' => 1, 'label' => 'Earnings become available for payout after this many hours', 'help' => 'Counted from the time the booking is completed.'],
        ],
        'Safety and verification' => [
            'host_selfie_required' => ['type' => 'bool', 'default' => 1, 'label' => 'Hosts must upload a selfie holding their ID', 'help' => ''],
            'host_min_about_chars' => ['type' => 'number', 'default' => 80, 'min' => 20, 'max' => 500, 'step' => 10, 'label' => 'Shortest "about me" text (characters)', 'help' => ''],
            'host_rules_text' => ['type' => 'textarea', 'default' => "ConnectHub Hosts offer friendly, professional company: dinners, city tours, events, travel and conversation. Sexual services are not allowed. Offering, asking for or hinting at them leads to removal and may be reported to the authorities.\n- Hosts must be 18 or over and ID-verified.\n- Meet in public places only and keep to the agreed plan.\n- Book, pay deposits and stay in touch through ConnectHub.\n- Respect each other's boundaries. Either person can end a booking if they feel unsafe.\n- Use the report button if anything feels wrong.", 'label' => 'Host rules shown at setup and on every booking', 'help' => 'One rule per line. Start a line with "-" to make it a bullet.', 'rows' => 9, 'max' => 2000],
            'host_rules_text_contact' => ['type' => 'textarea', 'default' => "ConnectHub Hosts offer friendly, professional company: dinners, city tours, events, travel and conversation. Sexual services are not allowed. Offering, asking for or hinting at them leads to removal and may be reported to the authorities.\n- Hosts must be 18 or over and ID-verified.\n- Meet in public places only and agree the plan before you go.\n- Nothing is booked or paid for on ConnectHub. Anything you agree is between the two of you.\n- Respect each other's boundaries. Either person can walk away at any point.\n- Use the report button if anything feels wrong.", 'label' => 'Host rules shown while people get in touch directly', 'help' => 'Used instead of the rules above while the module runs the contact way. One rule per line. Start a line with "-" to make it a bullet.', 'rows' => 9, 'max' => 2000],
            'host_banned_terms' => ['type' => 'textarea', 'default' => 'escort, sex, sexual, erotic, nude, naked, fetish, hookup, happy ending, full service, gfe, nsa, call girl, prostitute, xxx, onlyfans, sugar daddy, sugar mummy, sugar mama, adult services', 'label' => 'Extra words blocked in Host profiles, packages and booking notes', 'help' => 'Separated by commas. Added to the general banned words under Safety. A match is refused and the text is not saved.', 'rows' => 4, 'max' => 2000],
            'host_private_place_terms' => ['type' => 'textarea', 'default' => 'bedroom, hotel room, my house, my place, your house, your place, apartment, airbnb, private room', 'label' => 'Words that mark a private meeting place', 'help' => 'Separated by commas. Bookings must be at a public place, so a venue containing one of these is refused.', 'rows' => 3, 'max' => 1000],
        ],
        'Services and prices' => [
            'host_rates_hideable' => ['type' => 'bool', 'default' => 1, 'label' => 'Let Hosts choose whether their prices are shown', 'help' => 'On: each Host gets a “Show my prices” switch. When they turn it off, visitors see “Price shown when you pick a time” and the exact amount appears at booking. Off: prices are always shown (unless “Show rates publicly” is off).'],
            'host_max_services' => ['type' => 'number', 'default' => 12, 'min' => 1, 'max' => 40, 'step' => 1, 'label' => 'Most services one Host can choose', 'help' => 'Manage the list itself under Hosts > Host services.'],
        ],
        'Listing plans' => [
            'host_listing_fee_enabled' => ['type' => 'bool', 'default' => 0, 'label' => 'Hosts pay to be listed', 'help' => 'On: an approved Host only appears in the directory while a paid listing is running. Hosts buy it from their dashboard with the normal M-Pesa checkout. Existing Hosts disappear until they pay, so use “Give every Host free days” on the Hosts page first.'],
            'host_listing_price_weekly' => ['type' => 'number', 'default' => 300, 'min' => 0, 'max' => 1000000, 'step' => 50, 'label' => 'Listing price, 1 week', 'help' => '0 switches this option off.'],
            'host_listing_price_monthly' => ['type' => 'number', 'default' => 1000, 'min' => 0, 'max' => 1000000, 'step' => 50, 'label' => 'Listing price, 1 month', 'help' => '0 switches this option off.'],
            'host_listing_price_yearly' => ['type' => 'number', 'default' => 9000, 'min' => 0, 'max' => 1000000, 'step' => 100, 'label' => 'Listing price, 1 year', 'help' => '0 switches this option off.'],
        ],
        'Age confirmation' => [
            'hosts_age_gate_scope' => ['type' => 'select', 'default' => 'visitors', 'options' => ['visitors' => 'Visitors who are not signed in', 'everyone' => 'Everyone, including signed-in members'], 'label' => 'Who must confirm they are 18 or older', 'help' => 'Needs “Ask visitors to confirm they are 18 or older” to be on. The confirmation lasts for the browser session.'],
            'hosts_age_title' => ['type' => 'text', 'default' => 'Are you 18 or older?', 'max' => 80, 'label' => 'Age confirmation title', 'help' => ''],
            'hosts_age_text' => ['type' => 'textarea', 'default' => 'Hosts are for adults. By continuing you confirm you are 18 or older and agree to the Host rules below.', 'rows' => 3, 'max' => 400, 'label' => 'Age confirmation text', 'help' => 'Shown on the page visitors see before the Hosts directory opens.'],
        ],
        'Contact details' => [
            'hosts_contact_visibility' => ['type' => 'select', 'default' => 'everyone', 'label' => 'Who can see a Host’s contact details',
                'options' => ['after_booking' => 'Only clients with a confirmed booking (turns into signed-in members when bookings are off)', 'members' => 'Any signed-in member', 'premium' => 'Premium and VIP members', 'everyone' => 'Everyone, including visitors', 'rules' => 'Follow the table under Settings > Privacy > Contact details', 'hidden' => 'Nobody, people use Message only'],
                'help' => 'A Host only shares what they typed in and switched on. Visitors who are not signed in only see details when you choose Everyone.'],
            'hosts_contact_phone' => ['type' => 'bool', 'default' => 1, 'label' => 'Offer the phone number', 'help' => 'Shows a Call button.'],
            'hosts_contact_whatsapp' => ['type' => 'bool', 'default' => 1, 'label' => 'Offer WhatsApp', 'help' => 'Shows a WhatsApp button that opens a chat.'],
            'hosts_contact_email' => ['type' => 'bool', 'default' => 0, 'label' => 'Offer email', 'help' => ''],
            'hosts_contact_instagram' => ['type' => 'bool', 'default' => 1, 'label' => 'Offer Instagram', 'help' => ''],
            'hosts_contact_style' => ['type' => 'select', 'default' => 'buttons', 'label' => 'How contact details look', 'options' => ['buttons' => 'Tap buttons (Call, WhatsApp, Email, Instagram)', 'list' => 'A plain list'], 'help' => ''],
            'hosts_contact_note' => ['type' => 'textarea', 'default' => '', 'rows' => 2, 'max' => 240, 'label' => 'Note shown with the contact details', 'help' => 'For example: Please say you found them on our site. Leave empty for no note.'],
        ],
        'Agent showcase' => [
            'agent_default_bio' => ['type' => 'textarea', 'default' => 'Meet {name} from {location}. {about}', 'rows' => 2, 'max' => 300, 'label' => 'Default bio template', 'help' => 'Used on cards and profiles for a Host who has not written their own bio, and offered as a starting point in the Host editor. {name} is the Host name, {location} the town or county, {about} the Host\'s own short text (left out when empty).'],
            'agent_story_enabled' => ['type' => 'bool', 'default' => 1, 'label' => 'Show the Escort story showcase strip', 'help' => 'Round profile pictures at the top of the landing page and Hosts directory, like stories. VIP and featured Hosts come first. Fine controls: Admin > Agent showcase > Stories.'],
            'agent_media_stories_enabled' => ['type' => 'bool', 'default' => 1, 'ui' => false, 'label' => 'Allow real image and video stories', 'help' => 'Managed under Admin > Platform management.'],
            'agent_story_max_image_mb' => ['type' => 'number', 'default' => 8, 'min' => 1, 'max' => 20, 'ui' => false, 'label' => 'Maximum story image size (MB)', 'help' => 'Managed under Admin > Platform management.'],
            'agent_story_max_video_mb' => ['type' => 'number', 'default' => 20, 'min' => 1, 'max' => 100, 'ui' => false, 'label' => 'Maximum story video size (MB)', 'help' => 'Managed under Admin > Platform management.'],
            'agent_story_max_active' => ['type' => 'number', 'default' => 5, 'min' => 1, 'max' => 15, 'ui' => false, 'label' => 'Maximum active stories per profile', 'help' => 'Managed under Admin > Platform management.'],
            'agent_story_require_approval' => ['type' => 'bool', 'default' => 0, 'ui' => false, 'label' => 'Require story approval', 'help' => 'Managed under Admin > Platform management and Admin > Story moderation.'],
            'agent_story_pages' => ['type' => 'text', 'default' => 'landing,hosts', 'max' => 40, 'label' => 'Pages with the story strip', 'help' => 'Comma separated: landing, hosts, discover. Easier to change under Agent showcase > Stories.'],
            'agent_story_count' => ['type' => 'number', 'default' => 12, 'min' => 1, 'max' => 30, 'step' => 1, 'label' => 'Agents in the strip', 'help' => ''],
            'agent_story_tiers' => ['type' => 'text', 'default' => 'vip,featured,premium,paid,boosted', 'max' => 60, 'label' => 'Kinds of agent in the strip', 'help' => 'Comma separated: vip, featured, premium, paid, boosted.'],
            'agent_story_fill' => ['type' => 'bool', 'default' => 1, 'label' => 'Fill the strip with other agents', 'help' => 'When too few agents are in the ticked kinds, other approved agents follow so the strip is never empty.'],
            'agent_join_place' => ['type' => 'select', 'default' => 'menu', 'label' => 'Where the round "Become an agent" button sits',
                'options' => ['menu' => 'In the menu', 'strip' => 'First bubble in the story strip', 'both' => 'Both places', 'off' => 'Nowhere'],
                'help' => 'Shown to visitors and members who are not agents. The round design is the same in both places.'],
            'agent_story_join' => ['type' => 'bool', 'default' => 1, 'ui' => false, 'label' => 'First bubble: Become an agent', 'help' => 'Kept for sites that set this before the button could move. "Where the round button sits" decides now.'],
            'agent_story_ads' => ['type' => 'bool', 'default' => 1, 'label' => 'Include sponsored story adverts', 'help' => 'Adverts with the format Story (Admin > Adverts) appear after the VIP and featured agents. Needs “Show advertisements” to be on.'],
            'agent_story_viewer' => ['type' => 'bool', 'default' => 1, 'label' => 'Open stories full screen', 'help' => 'Off: tapping a bubble opens the agent profile directly.'],
            'agent_story_seconds' => ['type' => 'number', 'default' => 5, 'min' => 3, 'max' => 15, 'step' => 1, 'label' => 'Seconds per story', 'help' => ''],
            'agent_story_drift' => ['type' => 'bool', 'default' => 1, 'label' => 'Strip scrolls by itself', 'help' => 'A slow automatic drift that stops when someone touches the strip. Off for people who prefer less motion.'],
            'agent_story_promo' => ['type' => 'bool', 'default' => 1, 'label' => 'Agents can write a promotion line', 'help' => 'A short text (140 characters) that appears in their story. Screened with the same banned-word list as profiles.'],
        ],
        'Directory' => [
            'hosts_featured_count' => ['type' => 'number', 'default' => 4, 'min' => 0, 'max' => 12, 'step' => 1, 'label' => 'Featured Hosts shown above the results', 'help' => '0 hides the featured row.'],
        ],
    ],
];
