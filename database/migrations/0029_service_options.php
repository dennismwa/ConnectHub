<?php
/**
 * Four more service options for the Hosts catalogue: Dinner Dates, Travel Companion, Massage (a therapeutic massage,
 * under a new "Wellness" group) and Meet at my place (under "How we meet").
 *
 * Only missing names are added. Services you already have, renamed, hidden or deleted are never touched, and the new
 * ones go after the existing ones so nothing moves. Safe to run twice.
 */
return function (): void {
    // name => [group, icon, description]
    $new = [
        'Dinner Dates' => ['Meals and conversation', 'utensils', 'A relaxed dinner for two at a restaurant of your choice.'],
        'Travel Companion' => ['Travel and outdoors', 'plane', 'Good company for a trip, a flight or a weekend away.'],
        'Massage' => ['Wellness', 'flower-2', 'A relaxing massage from a trained therapist. Therapeutic only.'],
        'Meet at my place' => ['How we meet', 'home', 'You meet at a place the host looks after. Details are agreed in the chat first.'],
    ];
    $order = (int)val('SELECT COALESCE(MAX(sort_order), 0) FROM host_services');
    foreach ($new as $name => [$group, $icon, $desc]) {
        if (val('SELECT id FROM host_services WHERE name = :n', ['n' => $name])) {
            continue;
        }
        insert('host_services', ['name' => $name, 'description' => $desc, 'group_name' => $group, 'icon' => $icon, 'sort_order' => ++$order, 'is_active' => 1]);
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
