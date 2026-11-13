# Settings catalogue (generated)

`setting('key')` returns the typed value. Defaults shown. 649 keys.

## General (`general`)

### Identity

- `site_name` (text, default `ConnectHub`) - Site name
- `site_tagline` (text, default `Free dating site in Kenya: meet people near you`) - Tagline
- `site_description` (textarea, default `ConnectHub is a free dating site where you meet real peop...`) - Description
- `site_url` (url, default ``) - Site address
- `site_country` (text, default `Kenya`) - Country
- `timezone` (text, default `Africa/Nairobi`) - Time zone
- `currency` (text, default `KES`) - Currency code
- `currency_symbol` (text, default `KSh`) - Currency symbol
- `phone_prefix` (text, default `254`) - Phone country code

### Contact details

- `site_email` (email, default `hello@connecthub.co.ke`) - Contact email
- `support_email` (email, default `support@connecthub.co.ke`) - Support email
- `site_phone` (text, default ``) - Phone number
- `site_whatsapp` (text, default ``) - WhatsApp number
- `site_address` (text, default `Nairobi, Kenya`) - Address

### Registration

- `registration_open` (bool, default `1`) - Registration is open
- `email_verification_required` (bool, default `0`) - Require email verification
- `min_age` (number, default `18`) - Minimum age
- `max_age` (number, default `99`) - Maximum age shown in filters

### Maintenance

- `maintenance_mode` (bool, default `0`) - Maintenance mode
- `maintenance_message` (textarea, default `We are making ConnectHub better and will be back shortly.`) - Maintenance message

### Connection & security

- `force_https` (bool, default `0`) - Send visitors to the https:// version
- `hsts_enabled` (bool, default `0`) - Tell browsers to remember https (HSTS)
- `hsts_days` (number, default `180`) - Remember https for (days)
- `frame_allow_sites` (textarea, default ``) - Sites allowed to show this site inside a frame

### Footer

- `footer_tagline` (text, default `Real people. Real places. Real connections.`) - Footer tagline
- `footer_credit` (text, default `Made with care in Kenya`) - Footer credit line
- `newsletter_enabled` (bool, default `1`) - Show newsletter sign-up in footer

## Logo & branding (`branding`)

### Main logos

- `logo_full` (image, default ``) - Full logo
- `logo_compact` (image, default ``) - Compact logo
- `logo_icon` (image, default ``) - Icon only
- `favicon` (image, default ``) - Favicon
- `logo_show_wordmark` (bool, default `1`) - Show site name beside the default mark

### Header

- `logo_white` (image, default ``) - Header logo over the hero photo
- `logo_header_scrolled` (image, default ``) - Header logo after scrolling
- `logo_header_scrolled_dark` (image, default ``) - Header logo after scrolling, dark mode

### Mobile header

- `logo_mobile` (image, default ``) - Mobile header logo
- `logo_mobile_dark` (image, default ``) - Mobile header logo, dark mode
- `logo_mobile_white` (image, default ``) - Mobile logo over the hero photo

### Footer

- `logo_footer` (image, default ``) - Footer logo
- `logo_footer_dark` (image, default ``) - Footer logo, dark mode

### Signed-in app and menus

- `logo_app_header` (image, default ``) - Signed-in top bar logo
- `logo_app_header_dark` (image, default ``) - Signed-in top bar logo, dark mode
- `logo_sidepanel` (image, default ``) - Side panel logo (members)
- `logo_sidepanel_dark` (image, default ``) - Side panel logo (members), dark mode
- `logo_sidepanel_host` (image, default ``) - Side panel logo (Hosts)
- `logo_sidepanel_host_dark` (image, default ``) - Side panel logo (Hosts), dark mode
- `logo_auth` (image, default ``) - Sign-in and sign-up page logo
- `logo_auth_dark` (image, default ``) - Sign-in page logo, dark mode
- `logo_menu` (image, default ``) - Phone menu logo (landing and public pages)
- `logo_menu_dark` (image, default ``) - Phone menu logo, dark mode

### Public pages when signed in

- `logo_header_member` (image, default ``) - Top bar logo for signed-in members (large screens)
- `logo_header_member_dark` (image, default ``) - Top bar logo for signed-in members, dark mode

### Dashboard banner pictures

- `banners_show_images` (bool, default `1`) - Show pictures in the dashboard banners
- `banner_host_image` (image, default ``) - Host dashboard banner
- `banner_host_wallet_image` (image, default ``) - Host earnings and payouts banner
- `banner_wallet_image` (image, default ``) - Member wallet banner
- `banner_admin_image` (image, default ``) - Admin dashboard banner
- `banner_overlay` (select, default `medium` options: light|medium|strong) - How much colour over the pictures

### Dark mode

- `logo_dark` (image, default ``) - Dark mode logo

### Logo sizes

- `logo_h_header` (number, default `40`) - Header logo height over the photo (px)
- `logo_h_header_scrolled` (number, default `34`) - Header logo height when solid (px)
- `logo_h_header_member` (number, default `34`) - Signed-in members top bar logo height (px)
- `logo_h_header_mobile` (number, default `28`) - Mobile header logo height (px)
- `logo_h_app_header` (number, default `28`) - Signed-in top bar logo height (px)
- `logo_w_menu` (number, default `140`) - Phone menu logo width (px)
- `logo_w_auth` (number, default `200`) - Sign-in page logo width (px)
- `logo_w_sidepanel` (number, default `140`) - Side panel logo width (px)
- `logo_w_footer` (number, default `140`) - Footer logo width (px)
- `logo_h_admin` (number, default `32`) - Admin panel logo height (px)
- `logo_padding` (number, default `8`) - Space around the logo (px)
- `logo_bg` (select, default `none` options: none|light|dark) - Background behind the logo

## Appearance (`appearance`)

### Colours

- `color_primary` (color, default `#E8523F`) - Primary colour
- `color_secondary` (color, default `#F4A63A`) - Secondary colour
- `color_tertiary` (color, default `#6C5CE7`) - Accent colour
- `hero_gradient` (select, default `warm_dusk` options: warm_dusk|sunrise|ember|orchid|twilight|honey|plum|mono) - Gradient style
- `mesh_style` (select, default `sunset` options: sunset|dawn|ember|dusk|haze|orchid|amber|midnight) - Background mesh

### Theme

- `default_theme` (select, default `light` options: system|light|dark) - Default theme
- `allow_theme_toggle` (bool, default `1`) - Let members switch theme
- `dark_intensity` (select, default `medium` options: soft|medium|deep) - Dark mode depth
- `noise_intensity` (number, default `4`) - Grain texture (0–10)
- `floating_elements` (select, default `on` options: on|reduced|off) - Floating background shapes
- `radius_scale` (select, default `default` options: sharp|default|round) - Corner roundness
- `empty_art` (bool, default `1`) - Illustrations on empty pages

### Typography

- `font_heading` (select, default `plus-jakarta-sans` options: plus-jakarta-sans|outfit|sora|manrope) - Heading font
- `font_body` (select, default `inter` options: inter|dm-sans|manrope) - Body font
- `base_font_size` (select, default `16` options: 14|15|16) - Base text size
- `heading_tracking` (select, default `tight` options: normal|tight|ultra) - Heading letter spacing

### Motion

- `animations_level` (select, default `all` options: all|reduced|minimal|off) - Animations
- `page_reveals` (select, default `stagger` options: stagger|simultaneous|off) - Page load reveals
- `hover_effects` (select, default `full` options: full|basic|off) - Hover effects
- `auth_flip_style` (select, default `flip` options: flip|slide|fade|off) - Sign in ↔ Create account transition
- `confetti_on_match` (bool, default `1`) - Confetti on a new match
- `skeleton_style` (select, default `shimmer` options: shimmer|pulse|static) - Loading placeholders
- `respect_reduced_motion` (bool, default `1`) - Respect visitors’ reduced-motion setting

### Custom CSS

- `custom_css` (textarea, default ``) - Custom CSS

## Hero & header (`landing`)

### Hero layout & pictures

- `hero_layout` (select, default `collage` options: collage|simple|split) - Layout
- `hero_art_1` (image, default `@art/hero-1.webp`) - Collage picture 1 (large)
- `hero_art_2` (image, default `@art/hero-2.webp`) - Collage picture 2
- `hero_art_3` (image, default `@art/hero-3.webp`) - Collage picture 3 (small)
- `hero_chip_1` (text, default `Verified profiles`) - Floating label 1
- `hero_chip_2` (text, default `Shown by distance`) - Floating label 2

### Hero background

- `hero_bg_type` (select, default `slider` options: slider|image|video|gradient|parallax) - Background
- `hero_image` (image, default ``) - Single / parallax image
- `hero_video` (image, default ``) - Background video (MP4)
- `hero_height` (select, default `100` options: 100|85|70) - Hero height
- `hero_slider_speed` (number, default `6`) - Seconds per slide
- `hero_slider_transition` (select, default `kenburns` options: fade|slide|zoom|kenburns) - Slide transition
- `hero_slider_indicators` (bool, default `1`) - Show slide dots
- `hero_pause_hover` (bool, default `1`) - Pause when hovering

### Hero overlay & text

- `hero_overlay_type` (select, default `gradient` options: gradient|solid|none) - Overlay
- `hero_overlay_color` (color, default `#0F0E0D`) - Overlay colour
- `hero_overlay_start` (number, default `70`) - Overlay strength at the bottom (%)
- `hero_overlay_end` (number, default `25`) - Overlay strength at the top (%)
- `hero_text_color` (select, default `light` options: light|dark) - Text colour
- `hero_align` (select, default `left` options: left|center) - Text alignment
- `hero_badge` (text, default `Free to join · Verified profiles`) - Small badge above the headline
- `hero_headline` (text, default `Meet people who live where you do`) - Headline
- `hero_subheadline` (textarea, default `Date, make friends or find good company, in your own town...`) - Sub-headline
- `hero_cta_primary_text` (text, default `Create your profile`) - Primary button text
- `hero_cta_primary_url` (text, default `/register`) - Primary button link
- `hero_cta_member_mode` (select, default `discover` options: discover|hide) - Buttons for people who are already signed in
- `hero_cta_member_text` (text, default `Open Discover`) - Signed-in button text
- `hero_mobile` (select, default `compact` options: compact|full) - Hero on phones
- `hero_cta_secondary_text` (text, default `Look around first`) - Secondary button text
- `hero_cta_secondary_url` (text, default `/browse`) - Secondary button link

### Hero location search

- `hero_show_location` (bool, default `1`) - Show the location search
- `hero_location_mode` (select, default `both` options: pills|search|both|dropdown) - Location search style
- `hero_use_counties` (bool, default `1`) - Include all counties in search
- `hero_use_towns` (bool, default `1`) - Include major towns in search
- `hero_pill_count` (number, default `6`) - Quick buttons to show
- `hero_location_prompt` (text, default `Where are you?`) - Search box placeholder

### Header

- `header_sticky` (bool, default `1`) - Sticky header
- `public_dock` (bool, default `1`) - Bottom tab bar on phones for visitors
- `header_transparent_hero` (bool, default `1`) - Transparent header over the hero
- `header_cta_join` (text, default `Join free`) - Join button text
- `header_cta_signin` (text, default `Sign in`) - Sign-in button text

### Featured profiles on the landing page

- `landing_profiles_source` (select, default `newest` options: newest|random|active|featured) - Which profiles to show
- `landing_profiles_count` (number, default `12`) - How many
- `landing_show_distance` (bool, default `0`) - Show town on the cards

## Sign in & register (`auth`)

### Page content

- `auth_bg_image` (image, default `@art/auth-1.webp`) - Sign-up picture
- `auth_bg_image_login` (image, default `@art/auth-2.webp`) - Sign-in picture
- `auth_show_testimonials` (bool, default `1`) - Show member stories beside the form
- `auth_show_stats` (bool, default `1`) - Show the counters at the bottom
- `auth_stats_mode` (select, default `auto` options: auto|manual) - Counter values
- `auth_stat1_value` (text, default ``) - Counter 1 value (manual mode, leave blank to hide)
- `auth_stat1_label` (text, default `Connections made`) - Counter 1 label
- `auth_stat2_value` (text, default ``) - Counter 2 value (manual mode, leave blank to hide)
- `auth_stat2_label` (text, default `Active this week`) - Counter 2 label
- `auth_stat3_value` (text, default ``) - Counter 3 value (manual mode, leave blank to hide)
- `auth_stat3_label` (text, default `Average rating`) - Counter 3 label
- `auth_login_title` (text, default `Welcome back`) - Sign-in heading
- `auth_login_subtitle` (text, default `Your connections are waiting.`) - Sign-in sub-heading
- `auth_register_title` (text, default `Create your account`) - Register heading
- `auth_register_subtitle` (text, default `It takes about two minutes.`) - Register sub-heading

### Rules

- `password_min_length` (number, default `6`) - Shortest password allowed
- `password_min_strength` (select, default `none` options: none|fair|good|strong) - Password strength rule
- `password_block_common` (bool, default `0`) - Block the most common passwords
- `password_show_meter` (bool, default `0`) - Show the strength bar on sign-up
- `require_phone` (bool, default `0`) - Require a phone number
- `require_age_confirm` (bool, default `1`) - Require “I am 18 or older” tick box
- `remember_me_days` (number, default `180`) - Stay signed in for (days)
- `session_hours` (number, default `24`) - Sign out inactive sessions after (hours)
- `max_login_attempts` (number, default `5`) - Failed sign-ins before lock
- `lockout_minutes` (number, default `15`) - Lock duration (minutes)
- `passcode_enabled` (bool, default `1`) - Offer a passcode lock
- `passcode_idle_minutes` (number, default `30`) - Ask for the passcode again after (minutes)
- `totp_enabled_for_users` (bool, default `1`) - Offer two-step verification
- `admin_require_2fa` (bool, default `0`) - Require two-step verification for admins

## Onboarding & profiles (`onboarding`)

### Steps

- `onboarding_required` (multiselect, default `["basics","photos","interests","location"]` options: basics|photos|about|looking|interests|location) - Required steps
- `onboarding_disabled` (multiselect, default `[]` options: about|looking|interests|location) - Hidden steps
- `photos_min` (number, default `1`) - Minimum photos
- `photo_cap_all` (number, default `30`) - Most photos anyone can have
- `photo_uploads_per_day` (number, default `0`) - Photos a member can upload per day
- `photos_max_onboarding` (number, default `6`) - Photo slots during sign-up
- `interests_min` (number, default `5`) - Minimum interests
- `onboarding_show_height` (bool, default `1`) - Ask for height
- `onboarding_show_education` (bool, default `1`) - Ask for education
- `onboarding_show_occupation` (bool, default `1`) - Ask for occupation
- `bio_max_length` (number, default `500`) - Bio length limit
- `onboarding_celebration` (select, default `confetti` options: confetti|simple|none) - When finished
- `email_welcome` (bool, default `1`) - Send a welcome email

### Profile completeness weights

- `cw_photo` (number, default `25`) - Main photo (%)
- `cw_photos_extra` (number, default `10`) - More photos (%)
- `cw_bio` (number, default `15`) - Bio (%)
- `cw_interests` (number, default `10`) - Interests (%)
- `cw_location` (number, default `10`) - Location (%)
- `cw_looking` (number, default `10`) - What they want (%)
- `cw_height` (number, default `5`) - Height (%)
- `cw_education` (number, default `5`) - Education (%)
- `cw_work` (number, default `5`) - Occupation (%)
- `cw_verified` (number, default `5`) - Verified (%)

### Photos

- `photo_moderation` (select, default `auto` options: auto|manual) - New photos
- `photo_max_mb` (number, default `10`) - Largest upload (MB)
- `photo_quality` (number, default `82`) - Image quality (50–95)
- `verification_enabled` (bool, default `1`) - Offer profile verification
- `verification_type` (select, default `photo` options: photo|id|both) - What members must send

### Interests

- `interests_max` (number, default `15`) - Maximum interests

## Discovery & matching (`discovery`)

### Layout

- `discover_default_view` (select, default `swipe` options: swipe|grid|list) - Default view
- `discover_allow_view_switch` (bool, default `1`) - Let members switch view
- `discover_side_panels` (bool, default `1`) - Side panels on large screens
- `discover_quick_filters` (bool, default `1`) - Quick filters above the cards
- `discover_show_distance` (bool, default `1`) - Show distance
- `discover_show_compat` (bool, default `1`) - Show compatibility score
- `discover_show_online` (bool, default `1`) - Show online dot
- `discover_show_interests` (bool, default `1`) - Show interests on cards
- `distance_rounding_km` (select, default `1` options: 0|1|5|10) - Round distances to
- `online_window_minutes` (number, default `5`) - Count a member as online for (minutes)
- `discover_batch_size` (number, default `24`) - Profiles loaded at a time

### Sections on Discover

- `discover_mode_switch` (bool, default `1`) - Chip to switch experience
- `discover_mode_filters` (bool, default `1`) - The chosen experience decides the order
- `discover_focus_count` (number, default `24`) - People shown when someone picked Fun or Elite
- `discover_elite_enabled` (bool, default `1`) - Elite row on Discover
- `discover_elite_count` (number, default `12`) - Elite members in the row

### Likes, swipes and extras

- `superlike_enabled` (bool, default `1`) - Super likes
- `rewind_enabled` (bool, default `1`) - Undo last swipe
- `daily_picks_enabled` (bool, default `1`) - Daily picks
- `daily_picks_count` (number, default `8`) - Daily picks per member
- `radius_default` (number, default `50`) - Default search distance (km)
- `radius_max` (number, default `200`) - Longest search distance (km)
- `search_page_size` (number, default `24`) - Results per page in Search

### Filters

- `premium_filters` (multiselect, default `["height","education","body_type","active_recently","has_...` options: height|education|body_type|active_recently|has_bio) - Filters reserved for premium plans

### Ranking weights

- `w_interests` (number, default `25`) - Shared interests (%)
- `w_location` (number, default `20`) - Distance (%)
- `w_demographics` (number, default `15`) - Age and preference fit (%)
- `w_dwell` (number, default `15`) - Time spent on similar profiles (%)
- `w_search` (number, default `10`) - Match with their searches (%)
- `w_activity` (number, default `10`) - Recent activity (%)
- `w_quality` (number, default `5`) - Profile quality (%)
- `min_match_score` (number, default `0`) - Hide profiles scoring below
- `new_member_boost_hours` (number, default `48`) - Give new members extra reach for (hours)
- `new_member_boost_points` (number, default `12`) - Extra points for new members
- `boost_multiplier` (number, default `3`) - Boosted profile ranking multiplier

### Visitors who are not signed in

- `guest_browse_enabled` (bool, default `1`) - Public Browse page
- `guest_browse_limit` (number, default `12`) - Profiles visitors can see

### Matching engine

- `match_title` (text, default `It is a match!`) - Match celebration title
- `matching_stale_hours` (number, default `12`) - Refresh match scores after (hours)
- `matching_candidates` (number, default `300`) - Profiles scored per member
- `match_liked_you_points` (number, default `8`) - Extra points for people who already like them
- `match_nope_penalty` (number, default `25`) - Points taken off people they passed on
- `daily_picks_featured` (bool, default `1`) - Put Featured profiles in Daily picks

### International connections

- `intl_enabled` (bool, default `1`) - International connections are on
- `intl_title` (text, default `Meet the world`) - Page heading
- `intl_intro` (textarea, default `Friendships, language exchange and romance with people in...`) - Short introduction
- `intl_mode` (select, default `free` options: free|join|access|both) - Who pays
- `intl_join_fee` (number, default `500`) - Price to be listed
- `intl_join_days` (number, default `30`) - A listing lasts (days)
- `intl_access_fee` (number, default `500`) - Price to browse and message
- `intl_access_days` (number, default `30`) - Access lasts (days)
- `intl_premium_free` (select, default `premium` options: none|premium|vip) - Plans that never pay
- `intl_approval` (bool, default `1`) - New listings wait for my approval
- `intl_require_verified` (bool, default `0`) - Only verified members can be listed
- `intl_preview` (bool, default `1`) - Show blurred previews to people who have not paid
- `intl_msg_included` (bool, default `1`) - Access includes messaging listed members
- `intl_contact_types` (multiselect, default `["whatsapp","mobile","telegram"]` options: whatsapp|mobile|telegram|signal|viber|landline) - Phone number types members can add

## Messaging (`messaging`)

### Access

- `messaging_enabled` (bool, default `1`) - Messaging is on
- `msg_access` (select, default `matched` options: public|logged_in|premium|matched) - Who can start a conversation
- `msg_hosts_access` (select, default `logged_in` options: logged_in|premium|vip) - Who can message Hosts
- `msg_free_can_reply` (bool, default `1`) - Free members can always reply
- `msg_max_length` (number, default `1000`) - Longest message (characters)

### Content

- `msg_images` (select, default `on` options: on|premium|off) - Photo sharing
- `msg_max_image_mb` (number, default `5`) - Largest photo (MB)
- `msg_mask_contacts` (bool, default `1`) - Hide phone numbers and emails in early messages
- `msg_unlock_after` (number, default `10`) - Show contact details after this many messages
- `msg_delete_own` (bool, default `1`) - Let members delete their own messages
- `msg_edit_own` (bool, default `1`) - Let members edit their own messages
- `msg_edit_window` (number, default `15`) - Editing allowed for (minutes after sending)
- `profanity_action` (select, default `mask` options: off|mask|block) - Banned words

### Live features

- `msg_read_receipts` (select, default `premium` options: always|premium|off) - Read receipts
- `msg_typing` (bool, default `1`) - Typing indicator
- `msg_poll_ms` (number, default `3000`) - Check for new messages every (ms)
- `msg_icebreakers` (bool, default `1`) - Conversation starters
- `msg_icebreaker_count` (number, default `3`) - Starters shown
- `msg_retention_days` (number, default `0`) - Delete messages older than (days)

### Safety limits

- `msg_rate_per_minute` (number, default `30`) - Most messages a member can send per minute

### Paid direct messages

- `dm_paid_enabled` (bool, default `1`) - Members can pay to message someone they have not matched with
- `dm_messages_per_unlock` (number, default `3`) - Messages one credit allows before they reply
- `dm_daily_unlocks` (number, default `10`) - New conversations a member can open per day
- `dm_welcome_credits` (number, default `0`) - Free credits for new members
- `dm_to_hosts` (bool, default `0`) - Credits also work for messaging Hosts

## Privacy & visibility (`privacy`)

### Photo blur for visitors who are not signed in

- `blur_guests_landing` (bool, default `1`) - Blur photos on the landing page
- `blur_guests_browse` (bool, default `1`) - Blur photos on the public Browse page
- `blur_guests_profile` (bool, default `1`) - Blur photos on profile pages
- `blur_guests_hosts` (bool, default `0`) - Blur Host photos for visitors
- `blur_level` (select, default `medium` options: light|medium|heavy) - Blur strength
- `blur_gallery_free` (bool, default `0`) - Free members see only the main photo sharp

### Dating members

- `dating_requires_account` (bool, default `1`) - Visitors must create an account to see dating members

### What visitors see on a profile page

- `guest_profile_view` (select, default `limited` options: full|limited|login) - Profile page for visitors
- `guest_bio_chars` (number, default `120`) - Bio characters shown in the preview

### Contact details

- `host_contact_after_booking` (bool, default `1`) - Reveal a Host’s contact details once a booking is confirmed

### Phone visibility

- `contact_phone_free` (select, default `hidden` options: hidden|visible) - Free members
- `contact_phone_premium` (select, default `hidden` options: hidden|visible) - Premium members
- `contact_phone_vip` (select, default `visible` options: hidden|visible) - VIP members
- `contact_phone_matched` (select, default `visible` options: hidden|visible) - Matched members

### WhatsApp visibility

- `contact_whatsapp_free` (select, default `hidden` options: hidden|visible) - Free members
- `contact_whatsapp_premium` (select, default `hidden` options: hidden|visible) - Premium members
- `contact_whatsapp_vip` (select, default `visible` options: hidden|visible) - VIP members
- `contact_whatsapp_matched` (select, default `visible` options: hidden|visible) - Matched members

### Email visibility

- `contact_email_free` (select, default `hidden` options: hidden|visible) - Free members
- `contact_email_premium` (select, default `hidden` options: hidden|visible) - Premium members
- `contact_email_vip` (select, default `hidden` options: hidden|visible) - VIP members
- `contact_email_matched` (select, default `visible` options: hidden|visible) - Matched members

### Instagram visibility

- `contact_instagram_free` (select, default `hidden` options: hidden|visible) - Free members
- `contact_instagram_premium` (select, default `visible` options: hidden|visible) - Premium members
- `contact_instagram_vip` (select, default `visible` options: hidden|visible) - VIP members
- `contact_instagram_matched` (select, default `visible` options: hidden|visible) - Matched members

### Profile visitors

- `visit_notifications` (bool, default `1`) - Tell members when someone views their profile
- `visitors_free_teasers` (number, default `6`) - Blurred teasers on Visitors and Likes for Free members

## Hosts (`hosts`)

### Hosts on the platform

- `hosts_enabled` (bool, default `1`) - Host accounts are on
- `host_requires_approval` (bool, default `1`) - Host applications need approval
- `host_approval_sla_hours` (select, default `24` options: 12|24|48) - Review time shown to applicants
- `host_min_photos` (number, default `3`) - Minimum photos
- `host_max_photos` (number, default `12`) - Maximum photos
- `host_allow_video` (bool, default `1`) - Allow an intro video
- `host_video_max_seconds` (select, default `30` options: 15|30|60) - Longest video
- `host_video_max_mb` (select, default `50` options: 25|50|100) - Largest video file
- `host_review_moderation` (select, default `manual` options: auto|manual) - Reviews

### Connection profiles

- `host_connections_enabled` (bool, default `0`) - Enable connection profiles
- `host_connections_require_approval` (bool, default `1`) - Approve connection profiles before publishing
- `host_connections_show_contact` (bool, default `1`) - Allow contact details on approved profiles
- `host_connections_allow_gallery` (bool, default `1`) - Allow photo galleries
- `host_connections_max_photos` (number, default `6`) - Maximum photos per profile
- `host_connections_max_services` (number, default `6`) - Maximum services on each card
- `host_connections_page_title` (text, default `Connection profiles`) - Public page title
- `host_connections_page_subtitle` (textarea, default `Browse adult profiles for friendly conversations, public ...`) - Public page subtitle
- `host_connections_menu_label` (text, default `Connection profiles`) - Menu label
- `host_connections_card_style` (select, default `compact` options: compact|code|label) - Service card style
- `host_connections_show_on_homepage` (bool, default `0`) - Show a Connection profiles band on the landing page
- `host_connections_show_in_header` (bool, default `1`) - Show a link in the public header navigation
- `host_connections_show_in_footer` (bool, default `1`) - Show a link in the public footer

### How the Host module works

- `booking_enabled` (bool, default `1`) - Clients can book Hosts on the site

### Directory access

- `hosts_directory_access` (select, default `everyone` options: everyone|logged_in|premium|vip) - Who can browse Hosts
- `hosts_age_gate` (bool, default `1`) - Ask visitors to confirm they are 18 or older
- `host_show_rates` (bool, default `1`) - Show rates publicly
- `hosts_per_page` (number, default `12`) - Hosts per page
- `hosts_hero_image` (image, default `@art/hosts.webp`) - Directory banner picture

### Search and SEO

- `host_seo_keywords` (text, default ``) - Keywords for every page of this section
- `host_seo_profile_title` (text, default `{name}, {category} in {place}`) - Search title of a profile
- `host_seo_profile_desc` (text, default ``) - Search description of a profile
- `host_seo_profile_keywords` (bool, default `1`) - Add keywords to each profile
- `host_seo_faq_show` (bool, default `1`) - Show questions and answers
- `host_seo_faq` (textarea, default ``) - Questions and answers
- `host_seo_index_gate` (bool, default `1`) - Let search engines read the 18+ page
- `host_seo_schema` (bool, default `1`) - Add structured data

### Bookings & money

- `booking_deposit_pct` (number, default `50`) - Deposit paid at booking (%)
- `host_commission_pct` (number, default `15`) - Commission on each booking (%)
- `host_min_rate` (number, default `1000`) - Lowest hourly rate allowed
- `host_min_notice_hours` (number, default `12`) - Minimum notice for a booking (hours)
- `host_max_advance_days` (number, default `60`) - How far ahead clients can book (days)
- `host_payout_min` (number, default `2000`) - Smallest payout a host can request

### Booking rules

- `host_instant_booking_enabled` (bool, default `1`) - Hosts can turn on instant booking
- `host_accept_hours` (number, default `12`) - Time a Host has to answer a request (hours)
- `host_booking_buffer_minutes` (number, default `30`) - Gap kept between two bookings (minutes)
- `host_slot_step_minutes` (number, default `30`) - Start times are offered every (minutes)
- `host_booking_max_hours` (number, default `12`) - Longest single booking (hours)
- `host_payment_hold_minutes` (number, default `45`) - Hold a slot while the client pays (minutes)
- `host_max_unpaid_bookings` (number, default `3`) - Unpaid bookings one client can hold at once

### Cancellations, completion and payouts

- `host_cancel_full_hours` (number, default `48`) - Full deposit refund when the client cancels at least this many hours before the start
- `host_cancel_partial_hours` (number, default `24`) - Partial refund when the client cancels at least this many hours before the start
- `host_cancel_partial_pct` (number, default `50`) - Partial refund (% of the deposit)
- `host_auto_complete_hours` (number, default `12`) - Mark a booking completed automatically this many hours after it ends
- `host_reminder_hours` (number, default `24`) - Send a reminder this many hours before the start
- `host_payout_hold_hours` (number, default `24`) - Earnings become available for payout after this many hours

### Safety and verification

- `host_selfie_required` (bool, default `1`) - Hosts must upload a selfie holding their ID
- `host_min_about_chars` (number, default `80`) - Shortest "about me" text (characters)
- `host_rules_text` (textarea, default `ConnectHub Hosts offer friendly, professional company: di...`) - Host rules shown at setup and on every booking
- `host_rules_text_contact` (textarea, default `ConnectHub Hosts offer friendly, professional company: di...`) - Host rules shown while people get in touch directly
- `host_banned_terms` (textarea, default `escort, sex, sexual, erotic, nude, naked, fetish, hookup,...`) - Extra words blocked in Host profiles, packages and booking notes
- `host_private_place_terms` (textarea, default `bedroom, hotel room, my house, my place, your house, your...`) - Words that mark a private meeting place

### Services and prices

- `host_rates_hideable` (bool, default `1`) - Let Hosts choose whether their prices are shown
- `host_max_services` (number, default `12`) - Most services one Host can choose

### Listing plans

- `host_listing_fee_enabled` (bool, default `0`) - Hosts pay to be listed
- `host_listing_price_weekly` (number, default `300`) - Listing price, 1 week
- `host_listing_price_monthly` (number, default `1000`) - Listing price, 1 month
- `host_listing_price_yearly` (number, default `9000`) - Listing price, 1 year

### Age confirmation

- `hosts_age_gate_scope` (select, default `visitors` options: visitors|everyone) - Who must confirm they are 18 or older
- `hosts_age_title` (text, default `Are you 18 or older?`) - Age confirmation title
- `hosts_age_text` (textarea, default `Hosts are for adults. By continuing you confirm you are 1...`) - Age confirmation text

### Contact details

- `hosts_contact_visibility` (select, default `everyone` options: after_booking|members|premium|everyone|rules|hidden) - Who can see a Host’s contact details
- `hosts_contact_phone` (bool, default `1`) - Offer the phone number
- `hosts_contact_whatsapp` (bool, default `1`) - Offer WhatsApp
- `hosts_contact_email` (bool, default `0`) - Offer email
- `hosts_contact_instagram` (bool, default `1`) - Offer Instagram
- `hosts_contact_style` (select, default `buttons` options: buttons|list) - How contact details look
- `hosts_contact_note` (textarea, default ``) - Note shown with the contact details

### Agent showcase

- `agent_default_bio` (textarea, default `Meet {name} from {location}. {about}`) - Default bio template
- `agent_story_enabled` (bool, default `1`) - Show the round story strip
- `agent_story_pages` (text, default `landing,hosts`) - Pages with the story strip
- `agent_story_count` (number, default `12`) - Agents in the strip
- `agent_story_tiers` (text, default `vip,featured,premium,paid,boosted`) - Kinds of agent in the strip
- `agent_story_fill` (bool, default `1`) - Fill the strip with other agents
- `agent_join_place` (select, default `menu` options: menu|strip|both|off) - Where the round "Become an agent" button sits
- `agent_story_ads` (bool, default `1`) - Include sponsored story adverts
- `agent_story_viewer` (bool, default `1`) - Open stories full screen
- `agent_story_seconds` (number, default `5`) - Seconds per story
- `agent_story_drift` (bool, default `1`) - Strip scrolls by itself
- `agent_story_promo` (bool, default `1`) - Agents can write a promotion line

### Directory

- `hosts_featured_count` (number, default `4`) - Featured Hosts shown above the results

## Payments & pricing (`payments`)

### Test mode

- `payments_test_mode` (bool, default `0`) - Simulate payments (test mode)

### M-Pesa (Daraja STK Push)

- `mpesa_enabled` (bool, default `0`) - Accept M-Pesa
- `mpesa_env` (select, default `sandbox` options: sandbox|production) - Environment
- `mpesa_shortcode` (text, default `174379`) - Paybill or till number
- `mpesa_type` (select, default `CustomerPayBillOnline` options: CustomerPayBillOnline|CustomerBuyGoodsOnline) - Transaction type
- `mpesa_consumer_key` (secret, default ``) - Consumer key
- `mpesa_consumer_secret` (secret, default ``) - Consumer secret
- `mpesa_passkey` (secret, default ``) - Passkey
- `mpesa_callback_secret` (secret, default ``) - Callback secret

### Manual payments

- `manual_mpesa_enabled` (bool, default `0`) - Accept manual M-Pesa
- `manual_mpesa_details` (text, default ``) - Where to pay
- `manual_mpesa_type` (select, default `paybill` options: paybill|till|phone|pochi) - How members pay you
- `manual_mpesa_number` (text, default ``) - Paybill, till or phone number
- `manual_mpesa_account` (text, default ``) - Account number (Pay Bill only)
- `manual_mpesa_name` (text, default ``) - Name M-Pesa shows when paying
- `manual_mpesa_ussd` (bool, default `1`) - Show an "Open M-PESA menu" button on Android phones
- `bank_enabled` (bool, default `0`) - Accept bank transfer
- `bank_name` (text, default ``) - Bank
- `bank_account_name` (text, default ``) - Account name
- `bank_account_number` (text, default ``) - Account number
- `bank_branch` (text, default ``) - Branch
- `manual_instructions` (textarea, default `We confirm manual payments within a few hours. Keep the M...`) - Instructions shown to members

### Boosts and featured profiles

- `boost_enabled` (bool, default `1`) - Members can use boosts
- `featured_enabled` (bool, default `1`) - Members can buy a featured spot
- `boost_credit_minutes` (number, default `30`) - Length of one plan boost credit (minutes)
- `boost_daily_limit` (number, default `0`) - Most boosts a member can start in 24 hours
- `boost_stack` (bool, default `1`) - A new boost adds time to a running one

### Extras

- `gifts_enabled` (bool, default `1`) - Virtual gifts
- `show_weekly_option` (bool, default `1`) - Offer weekly plans
- `show_yearly_toggle` (bool, default `1`) - Offer yearly plans

### M-Pesa callback and timing

- `mpesa_callback_ips` (textarea, default ``) - Only accept M-Pesa results from these addresses
- `mpesa_stk_wait_seconds` (number, default `90`) - How long checkout waits for the phone prompt (seconds)

### Gift wallet and payouts

- `wallet_enabled` (bool, default `1`) - Members can claim gifts into a wallet
- `gift_receiver_percent` (number, default `70`) - Share of a gift that goes to the person who receives it (%)
- `gift_claim_mode` (select, default `auto` options: auto|verified) - When can a gift be claimed
- `wallet_payouts_enabled` (bool, default `1`) - Members can ask for a payout
- `wallet_min_payout` (number, default `500`) - Smallest payout
- `wallet_max_open_payouts` (number, default `2`) - Payout requests a member can have waiting at once
- `wallet_max_numbers` (number, default `4`) - Phone numbers a member can save

## Advertising (`ads`)

### Placement

- `ads_enabled` (bool, default `1`) - Show advertisements
- `ads_discover_every` (number, default `8`) - Ad card after every N profiles in Discover and Search
- `ads_banner_landing` (bool, default `1`) - Banner ad on the landing page
- `ads_popup_enabled` (bool, default `0`) - Pop-up ads
- `ads_label` (text, default `Sponsored`) - Label on ads

### Businesses buying adverts

- `promo_enabled` (bool, default `1`) - Let businesses buy adverts
- `promo_review` (bool, default `1`) - Check each paid advert before it goes live
- `promo_max_shown` (number, default `6`) - Adverts in one block
- `promo_rules` (textarea, default `No adult services, escorts or sexual content.
Nothing ill...`) - Rules shown to advertisers

## Email (`email`)

### Sending

- `mail_driver` (select, default `mail` options: mail|smtp|sendgrid_api|mailgun_api|brevo_api|postmark_api|resend_api|log) - Method
- `mail_from_name` (text, default `ConnectHub`) - From name
- `mail_from_email` (email, default `no-reply@connecthub.co.ke`) - From address
- `mail_fallback_server` (bool, default `1`) - If the provider fails, try the server default
- `smtp_preset` (select, default `custom` options: custom|gmail|outlook|zoho|brevo|sendgrid|mailgun|mailjet|postmark|ses) - SMTP provider
- `smtp_host` (text, default ``) - SMTP host
- `smtp_port` (number, default `587`) - SMTP port
- `smtp_secure` (select, default `tls` options: tls|ssl|none) - Encryption
- `smtp_user` (text, default ``) - SMTP username
- `smtp_pass` (secret, default ``) - SMTP password
- `mail_ses_region` (text, default `us-east-1`) - Amazon SES region
- `mail_api_key` (secret, default ``) - API key or token
- `mail_mailgun_domain` (text, default ``) - Mailgun sending domain
- `mail_mailgun_region` (select, default `us` options: us|eu) - Mailgun region

### Alerts to you

- `admin_alert_email` (text, default ``) - Send alerts to
- `admin_alert_gift` (bool, default `1`) - A gift was sent
- `admin_alert_payout` (bool, default `1`) - Someone asked for a payout
- `admin_alert_verification` (bool, default `1`) - A member sent a verification photo
- `admin_alert_payment` (bool, default `0`) - Any payment was completed
- `admin_alert_intl` (bool, default `1`) - Someone joined the international page or needs approval
- `admin_alert_advert` (bool, default `1`) - A paid advert needs approval
- `admin_alert_elite` (bool, default `1`) - Someone asked to join Elite or changed their Elite profile

### Messages sent to members

- `email_on_match` (bool, default `1`) - New match
- `email_on_message` (bool, default `1`) - New message (when they are offline)
- `email_on_like` (bool, default `0`) - Someone liked them
- `email_on_gift` (bool, default `1`) - Gift received
- `email_throttle_minutes` (number, default `30`) - At most one message email per conversation every (minutes)
- `email_digest` (bool, default `1`) - Weekly summary email

## Safety & moderation (`safety`)

### Reports and words

- `reports_auto_suspend` (number, default `5`) - Suspend a member for review after this many different people report them
- `banned_words` (textarea, default ``) - Banned words
- `report_reasons` (textarea, default `Fake profile
Inappropriate photos
Harassment or abuse
Spa...`) - Report reasons

### Admin access

- `admin_ip_whitelist` (textarea, default ``) - Admin IP allow-list
- `trust_proxy_headers` (bool, default `0`) - Trust proxy headers

### Profile verification

- `verification_instruction` (textarea, default `Take a clear selfie in good light, facing the camera.
Hol...`) - Selfie instructions
- `verification_assign` (select, default `choose` options: choose|random) - When several methods are on
- `verification_reapply_days` (number, default `0`) - Wait before trying again after a rejection (days)

## SEO & social (`seo`)

### Search and sharing

- `meta_keywords` (text, default `free dating site Kenya, dating Kenya, meet people Nairobi...`) - Keywords
- `og_image` (image, default ``) - Share image
- `twitter_handle` (text, default ``) - X (Twitter) handle
- `robots_extra` (textarea, default ``) - Extra robots.txt lines
- `google_analytics_id` (text, default ``) - Google Analytics 4 ID

### Social profiles

- `social_facebook` (url, default ``) - Facebook
- `social_instagram` (url, default ``) - Instagram
- `social_twitter` (url, default ``) - X (Twitter)
- `social_tiktok` (url, default ``) - TikTok
- `social_youtube` (url, default ``) - YouTube

### Legal and cookies

- `terms_slug` (text, default `terms-of-service`) - Terms page
- `privacy_slug` (text, default `privacy-policy`) - Privacy page
- `cookie_banner_enabled` (bool, default `1`) - Cookie notice
- `cookie_banner_text` (text, default `We use cookies to keep you signed in and to understand ho...`) - Cookie notice text

### Search visibility

- `seo_noindex_site` (bool, default `0`) - Ask search engines not to index the site

### Search results

- `seo_title_template` (text, default `%page% · %site%`) - Page title pattern
- `seo_default_description` (textarea, default ``) - Default search description
- `seo_locale` (select, default `en_KE` options: en_KE|en_US|en_GB|en_UG|en_TZ|sw_KE) - Language and country
- `seo_blog_author` (text, default ``) - Author name on articles

### Business details (shown to search engines)

- `seo_org_legal_name` (text, default ``) - Registered business name
- `seo_org_phone` (text, default ``) - Business phone
- `seo_org_email` (text, default ``) - Business email
- `seo_org_city` (text, default `Nairobi`) - City
- `seo_org_region` (text, default `Nairobi County`) - County or region
- `seo_org_country` (text, default `KE`) - Country code

### Verify your site

- `seo_google_verification` (text, default ``) - Google Search Console code
- `seo_bing_verification` (text, default ``) - Bing Webmaster code
- `seo_yandex_verification` (text, default ``) - Yandex code
- `seo_pinterest_verification` (text, default ``) - Pinterest code
- `seo_facebook_verification` (text, default ``) - Facebook domain code
- `seo_gtm_id` (text, default ``) - Google Tag Manager ID

### Crawling and indexing

- `seo_force_https` (bool, default `0`) - Send everyone to the https:// address
- `seo_www` (select, default `keep` options: keep|add|remove) - www or no www
- `seo_noindex_members` (bool, default `1`) - Keep member areas out of search
- `seo_ai_crawlers` (select, default `allow` options: allow|block) - AI crawlers
- `seo_indexnow_enabled` (bool, default `1`) - Tell Bing and others when content changes (IndexNow)
- `seo_indexnow_key` (text, default ``) - IndexNow key
- `seo_log_404` (bool, default `1`) - Keep a list of broken links people hit

### Sitemap and feeds

- `seo_sitemap_images` (bool, default `1`) - Include pictures in the sitemap
- `seo_sitemap_locations` (bool, default `1`) - Include county and town pages
- `seo_sitemap_hosts` (bool, default `1`) - Include the directory page
- `seo_rss_enabled` (bool, default `1`) - Blog feed (RSS)

### Structured data

- `seo_schema_org` (bool, default `1`) - Organisation card
- `seo_schema_sitelinks` (bool, default `1`) - Site search box
- `seo_schema_breadcrumbs` (bool, default `1`) - Breadcrumbs

## Mobile app (PWA) (`pwa`)

### Install

- `pwa_enabled` (bool, default `1`) - Installable app
- `pwa_install_prompt` (bool, default `1`) - Show our own install banner
- `pwa_prompt_after_visits` (number, default `3`) - Show the banner from visit number
- `pwa_short_name` (text, default ``) - Name under the app icon
- `pwa_offline_message` (text, default `You are offline. Reconnect to keep connecting.`) - Offline message

### App identity

- `pwa_name` (text, default ``) - Full app name
- `pwa_description` (textarea, default ``) - App description
- `pwa_icon` (image, default ``) - App icon
- `pwa_theme_color` (color, default ``) - Theme colour
- `pwa_background_color` (color, default `#FBFAF8`) - Splash background

### How the app opens

- `pwa_display` (select, default `standalone` options: standalone|minimal-ui|fullscreen|browser) - Window style
- `pwa_orientation` (select, default `portrait-primary` options: any|portrait-primary|landscape-primary) - Screen direction
- `pwa_start_path` (text, default `/`) - Opens on
- `pwa_shortcuts` (textarea, default `Discover | /discover
Messages | /messages`) - Shortcuts

### Offline and updates

- `pwa_cache_assets` (bool, default `1`) - Keep styles and scripts on the phone
- `pwa_cache_version` (number, default `1`) - Cache version



## Escort operating mode

The Booking / Contact selector under Admin > Hosts is the single control for the Escort operating model. It updates both `host_module_mode` and the legacy `booking_enabled` flag together. Do not change `booking_enabled` independently. In Contact mode, public pages show profile contact options and do not promise bookings or deposits.
