-- =============================================================
-- PowerSurge Gym — Seed Data
-- Run AFTER schema.sql
-- =============================================================

USE gym_powersurge;

-- ---- Roles ----------------------------------------------------
INSERT INTO roles (slug, name) VALUES
('super_admin', 'Super Admin'),
('admin', 'Admin'),
('receptionist', 'Receptionist'),
('trainer', 'Trainer'),
('store_manager', 'Store Manager'),
('member', 'Member');

-- ---- Default Super Admin ---------------------------------------
-- Default password: Admin@12345  (CHANGE IMMEDIATELY AFTER FIRST LOGIN)
-- Hash generated with PHP password_hash('Admin@12345', PASSWORD_DEFAULT)
INSERT INTO users (role_id, name, email, phone, password_hash, status)
VALUES (
    (SELECT id FROM roles WHERE slug = 'super_admin'),
    'PowerSurge Super Admin',
    'admin@powersurgegym.test',
    '+8801000000000',
    '$2y$12$WbfL9gQIVkeP472L0fGSiOPh2e8O9Jgl08wfeREuxeIib2wb5z0/O',
    'active'
);

-- ---- Membership Packages ----------------------------------------
-- Real current pricing (admin-editable via the Packages admin UI).
-- No offer_price/offer window is seeded — there is no real current promotion
-- to reflect. The 12-month package is marked "Best Value" because it's the
-- lowest real cost-per-month of the 3 (verifiable from the prices below), not
-- because of any claimed discount. Admin can set up a real offer via the UI.
INSERT INTO membership_packages (name, slug, category, duration_days, regular_price, badge, is_featured, description, includes_trainer, includes_locker, includes_steam, includes_sauna, includes_diet_plan, sort_order) VALUES
('2 Months', 'two-months', 'regular', 60, 1000.00, NULL, 0, 'Full gym access for 2 months — a great way to get started.', 0, 0, 0, 0, 0, 1),
('4 Months', 'four-months', 'regular', 120, 1500.00, NULL, 0, 'Full gym access for 4 months at a better monthly rate.', 0, 0, 0, 0, 0, 2),
('12 Months', 'twelve-months', 'regular', 365, 2500.00, 'BEST VALUE', 1, 'Best value — a full year of full gym access.', 0, 0, 0, 0, 0, 3);

-- ---- Membership Package Features ---------------------------------
-- Kept to what's verifiably true (no fabricated amenities like lockers/steam/diet
-- consultation — see the includes_* flags above, all left at 0 for the same reason).
INSERT INTO membership_package_features (package_id, feature_text, sort_order) VALUES
((SELECT id FROM membership_packages WHERE slug='two-months'), 'Full gym & equipment access', 1),
((SELECT id FROM membership_packages WHERE slug='two-months'), 'Open every operating day', 2),
((SELECT id FROM membership_packages WHERE slug='four-months'), 'Full gym & equipment access', 1),
((SELECT id FROM membership_packages WHERE slug='four-months'), 'Open every operating day', 2),
((SELECT id FROM membership_packages WHERE slug='twelve-months'), 'Full gym & equipment access', 1),
((SELECT id FROM membership_packages WHERE slug='twelve-months'), 'Open every operating day', 2);

-- ---- Trainers ---------------------------------------------------
-- Photo paths are relative to /assets/images (see views for asset() usage).
-- NOTE: names, certifications, PT prices, and schedules below are placeholders
-- (no real values were provided for these 3 trainers) — replace with real data
-- when available. gender is a reasonable inference from the photo, not confirmed.
INSERT INTO trainers (name, slug, job_title, gender, specialization, experience_years, certifications, bio, photo, monthly_pt_price, hourly_rate, max_members, availability_status, display_order, is_featured, is_active) VALUES
('Rakibul Hasan', 'rakibul-hasan', 'Strength Coach', 'male', 'Strength & Powerlifting', 8, 'Certified Strength & Conditioning Coach', 'Certified strength coach specializing in powerlifting and progressive overload programs.', 'trainer/trainer1.png', 6000.00, 800.00, 15, 'available', 1, 0, 1),
('Nusrat Jahan', 'nusrat-jahan', 'HIIT & Weight Loss Coach', 'female', 'Weight Loss & HIIT', 6, 'Certified Fitness Trainer, Certified Nutrition Coach', 'Helps members build sustainable fat-loss habits through HIIT and nutrition coaching.', 'trainer/trainer5.png', 5000.00, 700.00, 12, 'available', 2, 0, 1),
('Tanvir Ahmed', 'tanvir-ahmed', 'Bodybuilding Coach', 'male', 'Bodybuilding & Hypertrophy', 10, 'Certified Bodybuilding Coach', 'Former national bodybuilding competitor, now coaching hypertrophy-focused training.', 'trainer/trainer2.png', 7000.00, 900.00, 10, 'available', 3, 1, 1);

-- ---- Trainer Weekly Schedules -------------------------------------
-- day_of_week: 0=Sunday .. 6=Saturday (matches PHP date('w')).
-- Placeholder schedules within real gym hours (Sat-Thu 7AM-11PM, Fri 5-10PM).
INSERT INTO trainer_schedule (trainer_id, day_of_week, start_time, end_time, is_off) VALUES
-- Rakibul Hasan: evenings, Thursday off
((SELECT id FROM trainers WHERE slug='rakibul-hasan'), 0, '18:00:00', '21:00:00', 0),
((SELECT id FROM trainers WHERE slug='rakibul-hasan'), 1, '18:00:00', '21:00:00', 0),
((SELECT id FROM trainers WHERE slug='rakibul-hasan'), 2, '19:00:00', '22:00:00', 0),
((SELECT id FROM trainers WHERE slug='rakibul-hasan'), 3, '18:00:00', '21:00:00', 0),
((SELECT id FROM trainers WHERE slug='rakibul-hasan'), 4, NULL, NULL, 1),
((SELECT id FROM trainers WHERE slug='rakibul-hasan'), 5, '17:00:00', '20:00:00', 0),
((SELECT id FROM trainers WHERE slug='rakibul-hasan'), 6, '18:00:00', '21:00:00', 0),
-- Nusrat Jahan: mornings, Wednesday & Friday off
((SELECT id FROM trainers WHERE slug='nusrat-jahan'), 0, '08:00:00', '11:00:00', 0),
((SELECT id FROM trainers WHERE slug='nusrat-jahan'), 1, '08:00:00', '11:00:00', 0),
((SELECT id FROM trainers WHERE slug='nusrat-jahan'), 2, '08:00:00', '11:00:00', 0),
((SELECT id FROM trainers WHERE slug='nusrat-jahan'), 3, NULL, NULL, 1),
((SELECT id FROM trainers WHERE slug='nusrat-jahan'), 4, '08:00:00', '11:00:00', 0),
((SELECT id FROM trainers WHERE slug='nusrat-jahan'), 5, NULL, NULL, 1),
((SELECT id FROM trainers WHERE slug='nusrat-jahan'), 6, '08:00:00', '11:00:00', 0),
-- Tanvir Ahmed: afternoons, Monday off
((SELECT id FROM trainers WHERE slug='tanvir-ahmed'), 0, '16:00:00', '19:00:00', 0),
((SELECT id FROM trainers WHERE slug='tanvir-ahmed'), 1, NULL, NULL, 1),
((SELECT id FROM trainers WHERE slug='tanvir-ahmed'), 2, '16:00:00', '19:00:00', 0),
((SELECT id FROM trainers WHERE slug='tanvir-ahmed'), 3, '16:00:00', '19:00:00', 0),
((SELECT id FROM trainers WHERE slug='tanvir-ahmed'), 4, '16:00:00', '19:00:00', 0),
((SELECT id FROM trainers WHERE slug='tanvir-ahmed'), 5, '18:00:00', '21:00:00', 0),
((SELECT id FROM trainers WHERE slug='tanvir-ahmed'), 6, '16:00:00', '19:00:00', 0);

-- ---- Product Categories -------------------------------------------
INSERT INTO product_categories (name, slug, description) VALUES
('Supplements', 'supplements', 'Protein, gainers, pre-workout and vitamins'),
('Accessories', 'accessories', 'Bottles, shakers, bags and apparel'),
('Equipment', 'equipment', 'Gloves, belts, wraps and training gear');

-- ---- Products ----------------------------------------------------
-- image values are relative to /assets/images (see views for asset() usage).
-- Real product photos exist for the rows below where a real brand is shown
-- (Optimum Nutrition, Kevin Levrone, Applied Nutrition, Gymshark, the gym's
-- own Power Butter) — brand/description text was corrected to match what's
-- actually in the photo. Rows still on the fabricated "PowerSurge ..." brand
-- have no real photo yet and keep the dashed placeholder tile.
INSERT INTO products (category_id, sku, barcode, name, slug, brand, description, buying_price, selling_price, stock_qty, min_stock, image) VALUES
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-WP-001', '8901000000011', 'Performance Whey 1kg', 'whey-protein-2lb', 'Optimum Nutrition', 'Whey protein drink mix, Chocolate Milkshake flavour — 24g protein, 5g BCAAs per serving.', 1800.00, 2500.00, 40, 5, 'Sell/gym_protein_powder.webp'),
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-WP-014', '8901000000141', 'Gold Standard 100% Whey 5lb', 'gold-standard-whey-5lb', 'Optimum Nutrition', 'Double Rich Chocolate — 24g protein, 5.5g BCAAs per serving, banned-substance tested.', 6500.00, 8500.00, 12, 3, 'Sell/whey-protein-gold-standard.jpg'),
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-WP-015', '8901000000158', 'Gold Whey 2kg', 'gold-whey-2kg', 'Kevin Levrone Signature Series', 'Gold Line whey protein concentrate, Chocolate flavour — 23g protein, 6.2g BCAAs per serving.', 4200.00, 5800.00, 15, 3, 'Sell/Gold_w.jpg'),
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-CR-002', '8901000000028', 'Creatine Monohydrate 300g', 'creatine-monohydrate-300g', 'Various Brands', 'Micronized creatine monohydrate for strength and power output — multiple brands in stock, ask at the counter.', 900.00, 1400.00, 55, 5, 'Sell/creatine.jpg'),
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-MG-003', '8901000000035', 'Mass Gainer 6lb', 'mass-gainer-6lb', 'PowerSurge Nutrition', 'High-calorie gainer blend for lean muscle mass.', 2200.00, 3200.00, 25, 5, NULL),
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-PW-004', '8901000000042', 'Pre Workout 300g', 'pre-workout-300g', 'PowerSurge Nutrition', 'Explosive energy and focus formula for intense sessions.', 1200.00, 1900.00, 30, 5, NULL),
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-VT-005', '8901000000059', 'Daily Vitamins (60 caps)', 'daily-vitamins-60caps', 'PowerSurge Nutrition', 'Multivitamin complex to support recovery and immunity.', 500.00, 850.00, 60, 10, NULL),
((SELECT id FROM product_categories WHERE slug='supplements'), 'SUP-PB-016', '8901000000165', 'Power Butter (Regular & Creamy)', 'power-butter-regular-creamy', 'PowerSurge Gym', 'The gym''s own peanut butter — regular & creamy, no added nonsense.', 250.00, 450.00, 90, 15, 'Sell/Powersurge_gym_Butter.jpg'),
((SELECT id FROM product_categories WHERE slug='accessories'), 'ACC-SB-006', '8901000000066', 'Shaker Bottle 700ml', 'shaker-bottle-700ml', 'Applied Nutrition', 'Leak-proof shaker with mixing ball — steel or classic clear.', 150.00, 350.00, 100, 15, 'Sell/gymbollol.jpg'),
((SELECT id FROM product_categories WHERE slug='accessories'), 'ACC-WB-007', '8901000000073', 'Water Bottle 1L', 'water-bottle-1l', 'PowerSurge Gear', 'PowerSurge Gym branded steel water bottle.', 180.00, 400.00, 80, 15, 'Sell/logobolol.jpg'),
((SELECT id FROM product_categories WHERE slug='accessories'), 'ACC-TS-008', '8901000000080', 'Gym T-Shirt', 'gym-t-shirt', 'Gymshark', 'Breathable performance t-shirt, unisex.', 350.00, 750.00, 70, 10, 'Sell/GYMSHARK-tshirt.webp'),
((SELECT id FROM product_categories WHERE slug='accessories'), 'ACC-HD-009', '8901000000097', 'Hoodie', 'hoodie', 'PowerSurge Apparel', 'Warm fleece hoodie with embroidered logo.', 900.00, 1800.00, 35, 5, NULL),
((SELECT id FROM product_categories WHERE slug='equipment'), 'EQP-GL-010', '8901000000103', 'Gym Gloves', 'gym-gloves', 'PowerSurge Gear', 'Padded gloves with wrist support.', 350.00, 650.00, 45, 10, NULL),
((SELECT id FROM product_categories WHERE slug='equipment'), 'EQP-LB-011', '8901000000110', 'Lifting Belt', 'lifting-belt', 'PowerSurge Gear', 'Genuine leather lifting belt for heavy compound lifts.', 1200.00, 2200.00, 20, 5, NULL),
((SELECT id FROM product_categories WHERE slug='equipment'), 'EQP-WW-012', '8901000000127', 'Wrist Wrap', 'wrist-wrap', 'PowerSurge Gear', 'Elastic wrist wraps for joint support.', 250.00, 500.00, 50, 10, NULL),
((SELECT id FROM product_categories WHERE slug='equipment'), 'EQP-RB-013', '8901000000134', 'Resistance Band Set', 'resistance-band-set', 'PowerSurge Gear', 'Set of 5 resistance bands, light to heavy.', 600.00, 1100.00, 3, 10, NULL);

-- ---- Promotions (Latest Offers) -----------------------------------
-- No fabricated promo codes — the membership_packages prices above ARE
-- the gym's real current offer. Real seasonal promos (Eid, referral, etc.)
-- get added here by the admin once the Promotions UI ships in Phase 2.

-- ---- Gallery -------------------------------------------------------
-- image_path values are relative to /assets/images (see views for asset() usage).
INSERT INTO gallery (title, category, image_path) VALUES
('Main Training Floor', 'gym', 'gyminterior/gyminterior1.jpg'),
('Strength Equipment Zone', 'gym', 'gyminterior/gyminterior2.jpg'),
('Free Weights Area', 'gym', 'gyminterior/gyminterior3.jpg'),
('Squad Session', 'gym', 'pic/1stpic.jpg'),
('Leg Day Setup', 'gym', 'pic/anotherpic2.jpg'),
('Member Giveaway Day', 'events', 'award/giveaway-gift.jpg'),
('PowerSurge Family Meetup', 'events', 'family/ourfamily.jpg'),
('Gymsquad Flex-Off', 'competitions', 'pic/middlepic.jpg'),
('Members Posing With the Legacy Wall', 'transformation', 'pic/anotherpic.jpg'),
('Focused Before the Set', 'transformation', 'pic/lastpic.jpg'),
-- Community/team photos (not individual bookable trainer profiles — see trainers table for those).
('Grip and Grind', 'team', 'trainer/trainer3.png'),
('Champion Mindset', 'team', 'trainer/trainer4.png'),
('Outside the Gym', 'team', 'trainer/trainer6.png'),
('Post-Workout Pump', 'team', 'trainer/trainer7.png'),
('Progress Check', 'team', 'trainer/trainer8.png'),
('Team PowerSurge', 'team', 'trainer/femaletrainer.jpg');

-- ---- Blog Posts -------------------------------------------------------
INSERT INTO blog_posts (title, slug, category, excerpt, content, status, published_at) VALUES
('5 Warm-Up Mistakes That Are Killing Your Gains', '5-warm-up-mistakes', 'workout_tips', 'Skipping these steps could be limiting your progress every single session.', '<p>A proper warm-up primes your nervous system and joints for heavy work. Here are five mistakes we see every week at PowerSurge, and how to fix them.</p>', 'published', NOW()),
('How Much Protein Do You Actually Need?', 'how-much-protein-do-you-need', 'diet_tips', 'The science-backed answer for muscle growth and recovery.', '<p>Protein needs vary by goal, but most active lifters do well around 1.6–2.2g per kg of bodyweight per day. Here is how to plan your meals around that target.</p>', 'published', NOW()),
('PowerSurge Gym Extends Weekend Hours', 'powersurge-extends-weekend-hours', 'announcements', 'Starting this month, we are open longer on Fridays and Saturdays.', '<p>Based on member feedback, our weekend hours are now extended until 11 PM on Fridays and Saturdays.</p>', 'published', NOW());

-- ---- Testimonials -------------------------------------------------------
INSERT INTO testimonials (member_name, rating, message, is_featured, is_approved) VALUES
('Shafiul Islam', 5, 'PowerSurge completely changed my approach to training. Down 12kg in 5 months with the trainers here!', 1, 1),
('Farzana Akter', 5, 'The staff genuinely care about your progress. Clean equipment, great energy, highly recommended.', 1, 1),
('Imran Kabir', 4, 'Great value for the VIP package — steam room after leg day is unbeatable.', 1, 1);

-- ---- FAQs -------------------------------------------------------
INSERT INTO faqs (question, answer, category, sort_order) VALUES
('What are your operating hours?', 'We are open 6:00 AM – 11:00 PM Saturday to Thursday, and 6:00 AM – 11:00 PM on Friday with extended weekend hours.', 'general', 1),
('Do I need to book a slot before visiting?', 'No booking required for general gym access. Personal training sessions should be scheduled with your trainer.', 'membership', 2),
('Can I freeze my membership?', 'Yes, memberships of 3 months or longer can be frozen once for up to 30 days — contact reception to arrange it.', 'membership', 3),
('Do you offer a student discount?', 'Yes, our Student package offers 15–20% off with a valid student ID.', 'pricing', 4),
('Is personal training included in my package?', 'Six Months, One Year, VIP, and Premium packages include personal trainer sessions. Other plans can add it separately.', 'training', 5);

-- ---- Settings (key/value) -------------------------------------------------------
-- gym_address, gym_email, google_map_embed, and facebook_url are intentionally
-- blank — no real values were given for these yet. Fill them in via the
-- Settings screen once Phase 2 ships (or update this table directly for now).
INSERT INTO settings (setting_key, setting_value) VALUES
('gym_name', 'PowerSurge Gym'),
('gym_tagline', 'Train Hard. Surge Ahead.'),
('gym_phone', '01904-485009'),
('gym_email', ''),
('gym_address', ''),
('business_hours', 'Sat–Thu: 7:00 AM – 11:00 PM | Fri: 5:00 PM – 10:00 PM'),
('facebook_url', ''),
('instagram_url', 'https://instagram.com/powersurge_gym_01'),
('whatsapp_number', '+8801904485009'),
('currency', 'BDT'),
('tax_percent', '0'),
('google_map_embed', '');
