<?php
/**
 * Hosts: service catalogue grouped for an easy picker, a per-host "show my prices" switch, and listing plans (a Host can pay to be listed).
 * Existing services keep their ids, so profiles that already picked them are untouched. Nothing sexual is, or can be, added by default.
 */
return function (): void {
    mig_add_column('host_services', 'group_name', "VARCHAR(50) NOT NULL DEFAULT 'More' AFTER description");
    mig_add_column('host_services', 'icon', 'VARCHAR(40) NULL AFTER group_name');
    mig_add_column('host_profile_ext', 'show_prices', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER bookings_paused');
    mig_add_column('host_profile_ext', 'listing_until', 'DATETIME NULL AFTER show_prices');
    mig_add_column('host_profile_ext', 'listing_period', 'VARCHAR(10) NULL AFTER listing_until');

    // name => [group, icon, description]
    $catalog = [
        'Dinner' => ['Meals and conversation', 'utensils', 'Share a meal at a restaurant of your choice.'],
        'Coffee or lunch' => ['Meals and conversation', 'coffee', 'A relaxed daytime meet-up.'],
        'Business dinner' => ['Meals and conversation', 'briefcase', 'Polished company for client dinners.'],
        'Rooftop drinks' => ['Meals and conversation', 'wine', 'Drinks and good conversation at a public venue.'],
        'Conversation partner' => ['Meals and conversation', 'message-circle', 'Talk about anything over a coffee or a walk.'],
        'Language practice' => ['Meals and conversation', 'languages', 'Practise English, Kiswahili, French or others.'],
        'Video call company' => ['Meals and conversation', 'video', 'A friendly chat or dinner by video call.'],
        'Event plus-one' => ['Events and nights out', 'party-popper', 'Company at weddings, parties and corporate events.'],
        'Wedding guest company' => ['Events and nights out', 'heart-handshake', 'Someone easy to be around at a wedding.'],
        'Concert or festival' => ['Events and nights out', 'music', 'Live music, festivals and shows.'],
        'Sports match' => ['Events and nights out', 'trophy', 'Football, rugby or any match day.'],
        'Gallery or museum' => ['Events and nights out', 'frame', 'Art, culture and a slow afternoon.'],
        'Movie night' => ['Events and nights out', 'clapperboard', 'Cinema or a quiet night in.'],
        'City tour' => ['Travel and outdoors', 'map', 'See the best of the city with a local.'],
        'Weekend getaway' => ['Travel and outdoors', 'plane', 'Travel company for short trips.'],
        'Beach day' => ['Travel and outdoors', 'umbrella', 'Spend a day at the coast.'],
        'Hiking buddy' => ['Travel and outdoors', 'mountain-snow', 'Trails and nature walks.'],
        'Safari companion' => ['Travel and outdoors', 'binoculars', 'Game drives and park visits.'],
        'Road trip company' => ['Travel and outdoors', 'car-front', 'Someone good company on the road.'],
        'Gym or run partner' => ['Active and lifestyle', 'dumbbell', 'Keep each other on track.'],
        'Shopping companion' => ['Active and lifestyle', 'shopping-bag', 'Help choosing, carrying and chatting.'],
        'Photography walk' => ['Active and lifestyle', 'camera', 'Explore the city with a camera.'],
        'Cooking together' => ['Active and lifestyle', 'chef-hat', 'Cook a meal side by side.'],
        'Dance practice' => ['Active and lifestyle', 'audio-lines', 'Practise steps for a wedding or a night out.'],
        'Public venue meet-ups' => ['How we meet', 'map-pin', 'Always at a public, well-lit place.'],
        'Can travel to you' => ['How we meet', 'car', 'Meets you at a public place in your area.'],
    ];
    $order = 1;
    foreach ($catalog as $name => [$group, $icon, $desc]) {
        $existing = val('SELECT id FROM host_services WHERE name = :n', ['n' => $name]);
        if ($existing) {
            q("UPDATE host_services SET group_name = :g, icon = COALESCE(NULLIF(icon, ''), :i), sort_order = :o WHERE id = :id AND group_name = 'More'", ['g' => $group, 'i' => $icon, 'o' => $order, 'id' => $existing]);
        } else {
            insert('host_services', ['name' => $name, 'description' => $desc, 'group_name' => $group, 'icon' => $icon, 'sort_order' => $order, 'is_active' => 1]);
        }
        $order++;
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
