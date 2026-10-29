-- Brighteen Database Seeders
-- Pure SQL Seed Script for MySQL

-- 1. Seed Roles
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'management', 'Platform Director & Management', 'Full system access, user administration, editorial governance, platform metrics, and system configuration.'),
(2, 'editor', 'Senior Managing Editor', 'Editorial oversight, review and approval of article submissions, category management, content curation.'),
(3, 'journalist', 'Staff Journalist', 'Dedicated content creator with direct drafting capabilities and editorial review submission pipeline.'),
(4, 'contributor', 'Community Contributor', 'Guest and campus creator submitting freelance story pitches and community drafts.'),
(5, 'reader_registered', 'Verified Reader', 'Authenticated platform member with interactive capabilities including comments, bookmarking, and personalized feeds.'),
(6, 'reader_guest', 'Guest Reader', 'Unauthenticated public visitor with read access, social sharing, and IP-tracked likes.')
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- 2. Seed Users (Default password for all accounts is 'password123')
-- Password Hash: $2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `avatar`, `bio`, `created_at`, `updated_at`) VALUES
(1, 'Alex Rivera', 'admin@brighteen.com', '$2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.', 1, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80', 'Founder & Editorial Director at Brighteen. Passionate about youth journalism, decentralization, and digital subcultures.', NOW(), NOW()),
(2, 'Maya Lin', 'editor@brighteen.com', '$2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.', 2, 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=300&q=80', 'Managing Editor tracking internet lore, emerging aesthetics, and digital sociology. Keeping Brighteen sharp & fact-checked.', NOW(), NOW()),
(3, 'Kai Chen', 'journalist@brighteen.com', '$2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.', 3, 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=300&q=80', 'Staff Tech & Gaming Reporter. Covering neural interfaces, esports drama, and algorithmic speedrunning.', NOW(), NOW()),
(4, 'Zoe Parker', 'contributor@brighteen.com', '$2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.', 4, 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80', 'Gen-Z culture contributor and indie filmmaker. Obsessed with nostalgic Y2K tech and lo-fi storytelling.', NOW(), NOW()),
(5, 'Jordan Taylor', 'reader@brighteen.com', '$2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.', 5, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80', 'Voracious reader, student developer, and avid music collector from Toronto. Brighteen premium member.', NOW(), NOW()),
(6, 'Guest Explorer', 'guest@brighteen.com', '$2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.', 6, 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80', 'Anonymous guest session identifier for platform analytics and logging.', NOW(), NOW()),
(7, 'Aisyah Aulia', 'aisyah@brighteen.com', '$2y$10$TL.3bX0tYJADKHApVW06uuE4bJ4iKwajBil.MHfTOdtIxDsrTsvd.', 3, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80', 'Grade 9 Student Journalist & Tech Lead at Brighton Academy.', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 3. Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `color_code`, `icon`, `description`) VALUES
(1, 'Tech & Future', 'tech-future', '#2563eb', 'devices', 'AI revolutions, pocket supercomputers, spatial audio, and cybersecurity for the next generation.'),
(2, 'Pop Culture & Lore', 'pop-culture-lore', '#f43f5e', 'local_fire_department', 'Decoding viral TikTok phenomena, cinema masterclasses, music drops, and internet lore.'),
(3, 'Gaming & Esports', 'gaming-esports', '#8b5cf6', 'sports_esports', 'Competitive gaming metas, indie studio spotlights, modding culture, and tournament breakdowns.'),
(4, 'Creator Economy', 'creator-economy', '#10b981', 'campaign', 'How teen founders, Twitch streamers, and digital artists monetize their creativity without burning out.'),
(5, 'Vibes & Aesthetics', 'vibes-aesthetics', '#f59e0b', 'auto_awesome', 'Fashion revival, retro wave computing, room core setups, and micro-aesthetic design movements.'),
(6, 'Mind & Wellness', 'mind-wellness', '#06b6d4', 'spa', 'Digital detox strategies, academic burnout recovery, mental health tools, and sleep science.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 4. Seed Articles
INSERT INTO `articles` (`id`, `user_id`, `category_id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `status`, `views_count`, `published_at`, `editorial_feedback`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'Why Gen-Z Is Abandoning Modern Smartphones for $30 Feature Phones', 'why-gen-z-is-abandoning-smartphones-for-dumbphones', 
'<p class="text-lg leading-relaxed font-medium mb-4">In an era saturated with hyper-optimized algorithms, dopamine traps, and endless push notifications, a quiet revolution is unfolding across high school campuses and university dorms.</p>
<p class="mb-4">Meet the "Dumbphone Renaissance." Teens and young adults are deliberately packing away their $1,200 OLED flagships in favor of sub-$40 Nokia flip phones, tactile keypad gadgets, and refurbished candybar devices.</p>
<h3 class="text-2xl font-bold my-4 text-white">The Friction as a Luxury Feature</h3>
<p class="mb-4">"When I have to press the number 7 four times just to type the letter S, I suddenly realize I don''t actually need to send that 2:00 AM text," says 19-year-old computer science sophomore Liam Vance. "The physical friction forces mindfulness. My screen time plummeted from 9 hours to under 45 minutes a day."</p>
<p class="mb-4">Psychologists specializing in adolescent cognitive development point out that the endless scroll creates continuous low-grade cognitive fatigue. By stripping the device down to calls, SMS, and an analog alarm, students report immediate improvements in focus, circadian sleep rhythms, and spontaneous face-to-face banter.</p>
<blockquote class="border-l-4 border-blue-500 pl-4 py-2 my-6 italic text-gray-300 bg-gray-800/40 rounded-r-xl">"The modern smartphone was designed as a casino slot machine in your pocket. Opting out isn''t anti-technology—it is reclaiming your attention span."</blockquote>
<h3 class="text-2xl font-bold my-4 text-white">What Happens Next?</h3>
<p class="mb-4">Hardware startups like The Light Phone and Punkt are noticing the surge, reporting triple-digit sales growth specifically driven by buyers aged 16 to 24. Expect more hybrid gadgets in late 2026 that prioritize monochrome e-ink displays, privacy-first operating systems, and zero-algorithm utility.</p>',
'Tired of algorithmic dopamine traps, millions of teens are trading 120Hz flagships for nostalgic flip phones. Here is why the tactile revolution is booming.',
'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=1200&q=80',
'published', 0, '2026-10-12 10:30:00', NULL, NOW(), NOW()),

(2, 4, 3, 'The Unreal Engine 5 Indie Surge: How Two High Schoolers Built a Viral Roguelite', 'how-two-high-schoolers-built-a-viral-ue5-roguelite',
'<p class="text-lg leading-relaxed font-medium mb-4">Armed with free YouTube tutorials, community Discord servers, and a shared laptop GPU, two 17-year-olds from Bristol just clocked over 250,000 wishlist additions on Steam in under 72 hours.</p>
<p class="mb-4">Their game, <em>Neon Drift: Paradigm</em>, blends procedural synth-wave obstacle courses with intense cyberpunk physics. Built entirely after-hours between calculus homework and mock exams, the project proves that game development barriers have officially crumbled to dust.</p>
<h3 class="text-2xl font-bold my-4 text-white">Nanite and Lumen Without AAA Budgets</h3>
<p class="mb-4">"Back in 2018, rendering volumetric neon fog and dynamic reflections required a render farm," co-creator Chloe Evans explained during a Twitch dev stream. "With Unreal Engine 5.4, Nanite geometry scaling and Lumen global illumination ran smoothly straight out of the box."</p>
<p class="mb-4">The duo utilized algorithmic open-source audio plugins, community-contributed 3D low-poly kitbashing, and Reddit playtests to iterate faster than multi-million dollar studios.</p>
<h3 class="text-2xl font-bold my-4 text-white">The Lesson for Young Creators</h3>
<p class="mb-4">You do not need a publisher advance or venture capital seed money to create work that connects with millions. The internet rewards authenticity, snappy game loops, and genuine community engagement above all else.</p>',
'Two 17-year-olds turned after-school coding marathons into a Steam top-seller. Inside the democratization of AAA game engines.',
'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80',
'published', 0, '2026-10-13 14:15:00', NULL, NOW(), NOW()),

(3, 3, 2, 'Deconstructing "Brainrot": How Surrealist TikTok Humor Is Rewiring Global Slang', 'deconstructing-brainrot-tiktok-surrealism-slang',
'<p class="text-lg leading-relaxed font-medium mb-4">From "skibidi" to "fanum tax" and ironic sigma edits, older generations often dismiss current internet humor as senseless linguistic degeneration. But linguists say something much deeper is happening.</p>
<p class="mb-4">Digital anthropologists argue that Gen-Z and Gen-Alpha humor is directly descending from early 20th-century Dadaism—an artistic response to geopolitical absurdity and information overload through hyper-surrealism.</p>
<h3 class="text-2xl font-bold my-4 text-white">Speedrunning Cultural Memes</h3>
<p class="mb-4">A joke lifecycle that once lasted six months on cable television now mutates within 48 hours on algorithmic feeds. The absurdity serves as an in-group filter: if you can decode the layered irony of an eight-part TikTok stitch, you belong to the subculture.</p>
<p class="mb-4">Rather than signaling a decline in verbal eloquence, youth speech patterns exhibit sophisticated phonetic compression, emotional nuance, and rapid communal bonding.</p>',
'Beyond the cringe: why the hyper-fast, layered surrealism of Gen-Z meme language is actually the purest form of modern digital folklore.',
'https://images.unsplash.com/photo-1516251193007-45ef944ab0c6?auto=format&fit=crop&w=1200&q=80',
'published', 0, '2026-10-14 08:00:00', NULL, NOW(), NOW()),

(4, 4, 4, 'Stop Pitching Brands: How Teenage Micro-Creators Earn $5k/mo on Niche Newsletters', 'how-micro-creators-monetize-niche-newsletters',
'<p class="text-lg leading-relaxed font-medium mb-4">You do not need 100,000 TikTok followers to build a sustainable media empire from your bedroom desk.</p>
<p class="mb-4">Meet the micro-newsletter movement: high school and college writers cultivating 1,500 hyper-engaged readers around hyper-niche verticals like indie streetwear curation, localized thrifting maps, and mechanical keyboard group buys.</p>
<h3 class="text-2xl font-bold my-4 text-white">The Mathematics of Hyper-Relevance</h3>
<p class="mb-4">With open rates pushing past 60%, micro-curators charge $250 to $500 per sponsored shoutout from boutique brands who know every single reader is an eager buyer. Combined with premium subscriber tiers, young writers are earning steady revenue without dancing for short-form video algorithms.</p>',
'Forget chasing 100k followers on TikTok. Discover the silent cohort of teen curators monetizing high-retention micro-publications.',
'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=1200&q=80',
'published', 0, '2026-10-14 11:20:00', NULL, NOW(), NOW()),

(5, 3, 5, 'The "Frutiger Aero" Aesthetic Is Back: Why We Miss the Optimistic Glass Future', 'frutiger-aero-aesthetic-revival-optimistic-future',
'<p class="text-lg leading-relaxed font-medium mb-4">Gleaming water droplets on glossy touchscreens, tropical fish swimming inside Windows Vista wallpapers, and vibrant green hillscapes under crystal-clear blue skies.</p>
<p class="mb-4">Known as <em>Frutiger Aero</em>, the visual aesthetic that dominated tech and architecture from 2004 to 2013 is experiencing a massive nostalgic revival among youth who were barely toddlers when it was created.</p>
<h3 class="text-2xl font-bold my-4 text-white">Rejecting Flat, Dystopian Minimalism</h3>
<p class="mb-4">Today''s digital design language is overwhelmingly sterile: dark mode, monochrome gray cards, and muted corporate aesthetics. Frutiger Aero represents a lost era of tech optimism—where humanity envisioned clean solar energy, clear skies, and friendly, translucent technology.</p>',
'Glossy buttons, tropical fish screensavers, and sky-blue glass: why teens are ditching gloomy dark minimalism for early 2000s eco-optimism.',
'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80',
'published', 0, '2026-10-15 09:00:00', NULL, NOW(), NOW()),

(6, 4, 6, 'The 20-Minute Protocol: Restoring Deep REM Sleep in High-Stress Exam Weeks', 'the-20-minute-protocol-restoring-rem-sleep',
'<p class="text-lg leading-relaxed font-medium mb-4">Late-night cram sessions and blue light from dual monitors wreak havoc on teenage circadian biology. Here is a scientifically grounded 20-minute wind-down routine.</p>
<p class="mb-4">By pairing non-sleep deep rest (NSDR) audio pacing with amber ambient lighting and magnesium glycinate hydration, students can increase deep sleep percentages even when total resting hours are constrained.</p>',
'A neuroscience-backed evening routine that helps student creators decompress, lower cortisol, and wake up refreshed.',
'https://images.unsplash.com/photo-1541781774459-bb2af2f05b55?auto=format&fit=crop&w=1200&q=80',
'pending_review', 0, NULL, 'Pending fact-checking on the biochemical assertions in section 2.', NOW(), NOW()),

(7, 3, 1, 'Quantum Neural Chips: Will Your 2030 Earbuds Run Full LLMs Locally?', 'quantum-neural-chips-earbuds-local-llm',
'<p class="text-lg leading-relaxed font-medium mb-4">New sub-nanometer neuromorphic processors allow multi-billion parameter language models to run directly on wearable battery voltages without cloud latency.</p>
<p class="mb-4">This investigative breakdown explores upcoming silicon tape-outs, battery chemistry leaps, and what privacy looks like when your digital assistant never connects to an external server.</p>',
'No cloud, zero latency, total privacy. How next-gen silicon architecture brings true edge AI into standard wireless headphones.',
'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=80',
'pending_review', 0, NULL, 'Ready for final editorial sign-off. High technical accuracy.', NOW(), NOW()),

(8, 4, 2, 'The Underground Cassette Revival in Tokyo and Brooklyn', 'underground-cassette-revival-tokyo-brooklyn',
'<p class="text-lg leading-relaxed font-medium mb-4">Draft notes on indie DIY cassette labels, tape hiss aesthetics, and limited batch runs selling out in seconds on Bandcamp.</p>',
'Why physical magnetic tape with all its analog warmth and imperfections is outselling vinyl among underground bedroom pop producers.',
'https://images.unsplash.com/photo-1461360370896-922624d12aa1?auto=format&fit=crop&w=1200&q=80',
'draft', 0, NULL, NULL, NOW(), NOW()),

(9, 7, 1, 'Designing Our Future: Brighton Robotics Team Wins West Java STEM Championship', 'designing-our-future-brighton-robotics-team-wins-west-java-stem-championship',
'<p class="text-lg leading-relaxed font-medium mb-4">In a breathtaking 3-minute final round against Bandung Vocational Tech, Brighton Academy’s 9th-grade robotics team clutched the gold medal at the West Java Secondary STEM Olympiad.</p>
<p class="mb-4">Piloted by lead programmers Aisyah Aulia and Faris Pratama, their autonomous rover <em>Garuda-IX</em> executed flawless LIDAR spatial mapping and micro-pneumatic payload retrieval in record time.</p>
<h3 class="text-2xl font-bold my-4 text-white">400 Hours of Iteration</h3>
<p class="mb-4">"We had motor driver failures at 2:00 AM the night before," explained Aisyah during the post-match press conference. "Our CAD team 3D-printed reinforced chassis brackets in the school lab before sunrise. When Garuda-IX cleared the final obstacle course, our whole section exploded in tears of joy."</p>
<p class="mb-4">The team now qualifies to represent Indonesia at the ASEAN Youth Robotics Invitational in Singapore this December.</p>',
'Overcoming 42 regional secondary schools, Brighton\'s 9th-grade engineers deployed their autonomous \'Garuda-IX\' robot to seize 1st place in the Bandung Tech Olympiad.',
'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1600&q=80',
'published', 0, NOW() - INTERVAL 1 HOUR, NULL, NOW(), NOW()),

(10, 4, 6, 'Inside Brighton\'s Green Campus: Zero-Waste Canteen Experiment', 'inside-brightons-green-campus-zero-waste-canteen-experiment',
'<p class="text-lg leading-relaxed font-medium mb-4">Brighton High’s cafeteria just crossed day 30 of its total single-use plastic ban. And despite early skepticism, student satisfaction is at an all-time high.</p>
<p class="mb-4">By introducing RFID-tagged reusable stainless steel bento boxes and bamboo cutlery return stations, landfill waste has plummeted by an astonishing 86% across the campus food pavilion.</p>',
'Single-use plastics have been officially banned from food court vendors. Here\'s how peer pressure and eco-loyalty cards created a cleaner pavilion.',
'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=1000&q=80',
'published', 0, NOW() - INTERVAL 3 HOUR, NULL, NOW(), NOW()),

(11, 3, 1, 'AI Tools Every Junior High Student Is Secretly Using for Homework', 'ai-tools-every-junior-high-student-is-secretly-using-for-homework',
'<p class="text-lg leading-relaxed font-medium mb-4">Is AI a cognitive crutch or the modern scientific calculator? A confidential campus-wide poll of 250 students sheds light on how Gen-Z navigates study tools in 2026.</p>
<p class="mb-4">From conversational Socratic tutors to neural equation solvers, students share how smart prompting actually accelerates their comprehension when used intentionally.</p>',
'From math equation solvers to AI literature summarizers, we survey 250 classmates on the new boundary between smart study habits and digital shortcuts.',
'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=1000&q=80',
'published', 0, NOW() - INTERVAL 5 HOUR, NULL, NOW(), NOW()),

(12, 2, 3, 'Basketball Tigers Win Semifinals Against SMP 2 Depok', 'basketball-tigers-win-semifinals-against-smp-2-depok',
'<p class="text-lg leading-relaxed font-medium mb-4">With 4.2 seconds on the game clock and down by two points, point guard Kevin Chandra sank a contested baseline step-back three to send the Tigers to the finals.</p>
<p class="mb-4">The gymnasium roared as the buzzer sounded, capping off a thrilling 68-67 comeback triumph against rivals SMP 2 Depok.</p>',
'With a buzzer-beater 3-pointer in the fourth quarter, Brighton secured a dramatic victory to punch their championship ticket at the Youth League.',
'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1000&q=80',
'published', 0, NOW() - INTERVAL 12 HOUR, NULL, NOW(), NOW()),

(13, 4, 5, 'Manga & Digital Art Showcase: Best Student Sketches of Q3', 'manga-and-digital-art-showcase-best-student-sketches-q3',
'<p class="text-lg leading-relaxed font-medium mb-4">This quarter’s student gallery spotlights over sixty extraordinary digital illustrations created entirely on tablets during art lab sessions.</p>
<p class="mb-4">Featuring neon-soaked cyberpunk reimaginings of Indonesian street lore and cozy lo-fi character designs, the exhibition highlights the astonishing creative depth of Brighton’s visual creators.</p>',
'Cyberpunk Bandung to whimsical folklore: explore how sixty creative students transform simple sketchpads into stunning digital illustrations.',
'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=1000&q=80',
'published', 0, NOW() - INTERVAL 1 DAY, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 5. Seed Comments
INSERT INTO `comments` (`id`, `article_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 5, 'Switched to a flip phone three months ago for my university finals. My grades went up and my anxiety literally halved. Best decision ever!', NOW() - INTERVAL 2 DAY),
(2, 1, 4, 'The tactile click of a physical keypad is unmatched sensory joy. Great piece Kai!', NOW() - INTERVAL 1 DAY),
(3, 2, 5, 'Tried the Steam demo yesterday. The physics on the drift mechanics feel absurdly smooth. Unreal that high schoolers made this.', NOW() - INTERVAL 12 HOUR),
(4, 3, 5, 'The Dadaism comparison is 100% spot on. People forget art movements always look ridiculous to the establishment at first.', NOW() - INTERVAL 6 HOUR),
(5, 5, 2, 'Frutiger Aero aesthetic was so warm and full of hope. We definitely need more optimism in our digital product designs today.', NOW() - INTERVAL 2 HOUR)
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- 6. Seed Likes
INSERT INTO `likes` (`article_id`, `user_id`, `ip_address`, `created_at`) VALUES
(1, 5, '127.0.0.1', NOW() - INTERVAL 2 DAY),
(1, 4, '127.0.0.1', NOW() - INTERVAL 1 DAY),
(2, 5, '127.0.0.1', NOW() - INTERVAL 18 HOUR),
(3, 5, '127.0.0.1', NOW() - INTERVAL 10 HOUR),
(5, 5, '127.0.0.1', NOW() - INTERVAL 4 HOUR),
(5, 3, '127.0.0.1', NOW() - INTERVAL 3 HOUR)
ON DUPLICATE KEY UPDATE `created_at` = VALUES(`created_at`);

-- 7. Seed Saved Posts (Bookmarks)
INSERT INTO `saved_posts` (`article_id`, `user_id`, `created_at`) VALUES
(1, 5, NOW() - INTERVAL 2 DAY),
(3, 5, NOW() - INTERVAL 1 DAY),
(5, 5, NOW() - INTERVAL 5 HOUR)
ON DUPLICATE KEY UPDATE `created_at` = VALUES(`created_at`);

-- 8. Seed System Settings
INSERT INTO `system_settings` (`id`, `key`, `value`, `description`) VALUES
(1, 'platform_name', 'Brighteen', 'Platform display title across all headers and meta tags.'),
(2, 'tagline', 'The Youth Pulse of Culture, Tech & Creativity', 'Main platform tagline.'),
(3, 'gemini_model', 'gemini-1.5-flash', 'Google Gemini AI model utilized for content generation and editorial optimization.'),
(4, 'allow_registration', 'true', 'Enable or disable public user registration.'),
(5, 'ai_assistant_enabled', 'true', 'Enable Google Gemini AI writer tools in CKEditor.'),
(6, 'maintenance_mode', 'false', 'Global maintenance status flag.')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 9. Seed Assignments
INSERT INTO `assignments` (`id`, `assigned_by_user_id`, `assigned_to_user_id`, `category_id`, `topic`, `description`, `submission_deadline`, `publishing_schedule`, `status`, `article_id`, `turned_in_at`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 1, 'Generative AI in High School Classrooms: Cheating Tool or Creative Superpower?', 'Investigate how students and teachers are collaborating with generative tools for coding, creative writing, and science lab simulations. Include ethical ground rules and interview quotes.', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY), 'pending', NULL, NULL, NOW(), NOW()),
(2, 2, 3, 3, 'Inside the High School Esports Championship: Meta Shifts & Student MVPs', 'Break down the finals of the regional inter-school Valorant tournament. Highlight top clutches, team communication, and the rising culture of campus gaming clubs.', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY), 'pending', NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
(3, 1, 7, 2, 'Nostalgia Wave: Why 2000s Digicams Are Dominating School Yearbooks', 'Explore the massive resurgence of vintage CCD cameras, low-res flash aesthetics, and why teen creators are leaving smartphones behind for authentic campus memories.', DATE_ADD(NOW(), INTERVAL 6 HOUR), DATE_ADD(NOW(), INTERVAL 3 DAY), 'pending', NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(4, 2, 3, 4, 'How Teenage VTubers Are Building Global Micro-Communities', 'Case study on student 2D/3D avatar creators streaming digital art, lo-fi beats, and coding tutorials from their bedrooms.', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'turned_in', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), NOW())
ON DUPLICATE KEY UPDATE `topic` = VALUES(`topic`);

