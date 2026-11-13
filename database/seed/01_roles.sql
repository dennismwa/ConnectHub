-- Admin roles. Permissions: module => none | view | edit | full  ("*" = every module)
INSERT INTO admin_roles (slug, name, description, permissions, is_system) VALUES
('super_admin', 'Super admin', 'Full access to everything, including settings and roles.', '{"*":"full"}', 1),
('moderator', 'Moderator', 'Reviews photos, reports, verifications and members.', '{"dashboard":"view","users":"edit","hosts":"edit","moderation":"full","reports":"full","verifications":"full","messages":"view","logs":"view"}', 1),
('support', 'Support agent', 'Answers members and handles contact messages.', '{"dashboard":"view","users":"view","reports":"view","messages":"full","payments":"view","newsletter":"view"}', 1),
('finance', 'Finance', 'Payments, subscriptions, payouts and bookings.', '{"dashboard":"view","payments":"full","subscriptions":"full","plans":"view","payouts":"full","bookings":"full","analytics":"view","users":"view"}', 1),
('editor', 'Content editor', 'Pages, blog, FAQs, landing page and navigation.', '{"dashboard":"view","content":"full","landing":"full","nav":"full","newsletter":"edit","messages":"view"}', 1);
