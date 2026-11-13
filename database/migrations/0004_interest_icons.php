<?php
/** Every interest gets a proper icon (shown in a coloured tile) instead of an emoji. Icons an admin has already chosen are kept. */
return function (): void {
    $map = [
        'Reading' => 'book-open', 'Writing' => 'pen-line', 'Photography' => 'camera', 'Painting' => 'palette', 'Fashion' => 'shirt', 'Poetry' => 'feather',
        'Art galleries' => 'frame', 'Movies' => 'clapperboard', 'Series' => 'tv', 'Stand-up comedy' => 'mic', 'Gaming' => 'gamepad-2', 'Board games' => 'dices',
        'Theatre' => 'drama', 'Festivals' => 'tent', 'Podcasts' => 'podcast', 'Running' => 'footprints', 'Gym' => 'dumbbell', 'Yoga' => 'flower-2',
        'Cycling' => 'bike', 'Swimming' => 'waves', 'Cooking' => 'chef-hat', 'Baking' => 'cake-slice', 'Nyama choma' => 'flame', 'Coffee' => 'coffee',
        'Wine' => 'wine', 'Craft beer' => 'beer', 'Street food' => 'sandwich', 'Vegan food' => 'leaf', 'Tech' => 'cpu', 'Startups' => 'rocket',
        'Investing' => 'trending-up', 'Politics' => 'landmark', 'Volunteering' => 'hand-heart', 'Faith' => 'church', 'Meditation' => 'brain', 'Languages' => 'languages',
        'Travel' => 'plane', 'Gardening' => 'sprout', 'Pets' => 'paw-print', 'Road trips' => 'route', 'Motorbikes' => 'motorbike', 'Cars' => 'car-front',
        'Quiet evenings in' => 'sofa', 'Live music' => 'guitar', 'Afrobeats' => 'audio-lines', 'Gospel' => 'music-2', 'Benga' => 'drum', 'Hip hop' => 'headphones',
        'Jazz' => 'piano', 'Dancing' => 'party-popper', 'Karaoke' => 'mic-vocal', 'Hiking' => 'mountain-snow', 'Camping' => 'tent-tree', 'Safari' => 'binoculars',
        'Beach days' => 'umbrella', 'Fishing' => 'fish', 'Bird watching' => 'bird', 'Mountain biking' => 'mountain', 'Football' => 'football', 'Basketball' => 'basketball',
    ];
    foreach ($map as $name => $icon) {
        q("UPDATE interests SET icon = :i WHERE name = :n AND (icon IS NULL OR icon = '')", ['i' => $icon, 'n' => $name]);
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
