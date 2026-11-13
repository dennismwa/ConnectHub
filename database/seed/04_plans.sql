-- Plans. features: numeric limits use -1 for unlimited; the rest are on/off.
INSERT INTO subscription_plans (slug, name, tagline, tier, price_monthly, price_yearly, features, badge_color, badge_icon, is_featured, is_active, sort_order) VALUES
('free', 'Free', 'Everything you need to start meeting people.', 0, 0, 0,
 '{"daily_swipes":30,"daily_superlikes":0,"daily_messages":15,"daily_rewinds":0,"rewind":false,"see_who_liked":false,"advanced_filters":false,"incognito":false,"read_receipts":false,"message_before_match":false,"max_photos":6,"boost_credits_monthly":0,"profile_visitors":false,"no_ads":false,"priority_likes":false}',
 '#8A8095', 'user', 0, 1, 1),
('premium', 'Premium', 'More likes, more messages, and see who is interested.', 1, 799, 7190,
 '{"daily_swipes":-1,"daily_superlikes":5,"daily_messages":-1,"daily_rewinds":5,"rewind":true,"see_who_liked":true,"advanced_filters":true,"incognito":false,"read_receipts":true,"message_before_match":false,"max_photos":9,"boost_credits_monthly":1,"profile_visitors":true,"no_ads":true,"priority_likes":false}',
 '#F4A63A', 'sparkles', 1, 1, 2),
('vip', 'VIP', 'Be seen first, browse privately and message anyone.', 2, 1999, 17990,
 '{"daily_swipes":-1,"daily_superlikes":15,"daily_messages":-1,"daily_rewinds":-1,"rewind":true,"see_who_liked":true,"advanced_filters":true,"incognito":true,"read_receipts":true,"message_before_match":true,"max_photos":12,"boost_credits_monthly":4,"profile_visitors":true,"no_ads":true,"priority_likes":true}',
 '#6C5CE7', 'crown', 0, 1, 3);

INSERT INTO gift_types (name, icon, emoji, price, sort_order) VALUES
('Rose', 'flower-2', '🌹', 50, 1), ('Coffee', 'coffee', '☕', 100, 2), ('Chocolates', 'gift', '🍫', 150, 3), ('Teddy bear', 'gift', '🧸', 250, 4),
('Birthday cake', 'cake', '🎂', 300, 5), ('Champagne', 'wine', '🥂', 500, 6), ('Diamond', 'gem', '💎', 1000, 7), ('Crown', 'crown', '👑', 1500, 8);

INSERT INTO host_categories (name, icon, sort_order) VALUES
('Dinner and social companion', 'utensils', 1), ('Travel companion', 'plane', 2), ('Local guide', 'map-pin', 3),
('Event companion', 'party-popper', 4), ('Activity partner', 'footprints', 5), ('Conversation partner', 'message-circle', 6);

INSERT INTO host_services (name, description, sort_order) VALUES
('Dinner', 'Share a meal at a restaurant of your choice.', 1), ('Coffee or lunch', 'A relaxed daytime meet-up.', 2),
('City tour', 'See the best of the city with a local.', 3), ('Event plus-one', 'Company at weddings, parties and corporate events.', 4),
('Business dinner', 'Polished company for client dinners.', 5), ('Weekend getaway', 'Travel company for short trips.', 6),
('Beach day', 'Spend a day at the coast.', 7), ('Hiking buddy', 'Trails and nature walks.', 8),
('Gym or run partner', 'Keep each other on track.', 9), ('Shopping companion', 'Help choosing, carrying and chatting.', 10),
('Movie night', 'Cinema or a quiet night in.', 11), ('Language practice', 'Practise English, Kiswahili, French or others.', 12);

INSERT INTO ice_breakers (text, category) VALUES
('What is your favourite place to eat in town?', 'food'), ('If you could travel anywhere next month, where would you go?', 'travel'),
('What is the best thing that happened to you this week?', 'casual'), ('Coffee, tea or something stronger?', 'food'),
('What are you listening to on repeat right now?', 'music'), ('What is your ideal Sunday?', 'casual'),
('Which place in Kenya should everyone visit once?', 'travel'), ('What is a small thing that always makes your day better?', 'casual'),
('What is the last film or series you could not stop watching?', 'entertainment'), ('Early bird or night owl?', 'casual'),
('What is your go-to karaoke song?', 'music'), ('What would your perfect weekend look like?', 'casual');

INSERT INTO bio_suggestions (text, sort_order) VALUES
('Weekend hiker, weekday coffee addict.', 1), ('Looking for good conversation and someone who laughs easily.', 2),
('I will always say yes to nyama choma.', 3), ('New in town and keen to meet people who know the best spots.', 4),
('Big on family, faith and Sunday lunches.', 5), ('Part dreamer, part planner. Mostly hungry.', 6),
('Here for real connections, not endless chatting.', 7), ('Ask me about my last trip.', 8);

INSERT INTO blog_categories (name, slug, sort_order) VALUES ('Dating tips', 'dating-tips', 1), ('Safety', 'safety', 2), ('Community', 'community', 3);
