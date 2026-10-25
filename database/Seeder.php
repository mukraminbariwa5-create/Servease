<?php
declare(strict_types=1);

/**
 * Creates realistic demo data so the marketplace looks alive on first run.
 * All demo accounts use the password: servease123
 */
final class Seeder
{
    private const PASSWORD = 'servease123';
    private string $hash;
    private array $cats = [];

    public function run(): array
    {
        mt_srand(2026);
        $this->hash = password_hash(self::PASSWORD, PASSWORD_DEFAULT);

        $this->categories();
        $admin = $this->user('admin', 'Alex', 'Reyes', 'admin@servease.test', 'Makati', 120);

        // --- Demo client & provider (used on the sign-in page) -----------------
        $client = $this->user('client', 'Maria', 'Santos', 'client@servease.test', 'Quezon City', 60);
        $provider = $this->provider('Juan', 'Dela Cruz', 'provider@servease.test', 'plumbing', 'Quezon City', 9, 'verified', 75);

        // --- More providers --------------------------------------------------------
        $people = [
            ['Ramon', 'Villanueva', 'electrical', 'Pasig', 12], ['Liza', 'Mendoza', 'cleaning', 'Makati', 5],
            ['Paolo', 'Garcia', 'aircon', 'Taguig', 8], ['Carlo', 'Bautista', 'carpentry', 'Marikina', 15],
            ['Grace', 'Ramos', 'tutoring', 'Manila', 6], ['Mark', 'Aquino', 'painting', 'Mandaluyong', 7],
            ['Joy', 'Castillo', 'beauty', 'Quezon City', 4], ['Noel', 'Fernandez', 'handyman', 'Parañaque', 11],
            ['Rina', 'Torres', 'it-support', 'Makati', 6], ['Dennis', 'Navarro', 'moving', 'Caloocan', 9],
            ['Anna', 'Lim', 'gardening', 'Las Piñas', 5], ['Jerome', 'Salazar', 'plumbing', 'Pasig', 7],
            ['Kristine', 'Flores', 'cleaning', 'Taguig', 3], ['Arnel', 'Gonzales', 'electrical', 'Cebu City', 14],
            ['Bea', 'Morales', 'tutoring', 'Davao City', 4], ['Rodel', 'Cruz', 'aircon', 'Quezon City', 10],
            ['Faith', 'Domingo', 'beauty', 'Makati', 6], ['Jun', 'Pascual', 'carpentry', 'Cebu City', 18],
            ['Mika', 'Rivera', 'it-support', 'Quezon City', 5], ['Ernesto', 'Soriano', 'handyman', 'Manila', 20],
            ['Lea', 'Tan', 'painting', 'Pasig', 4], ['Hassan', 'Abubakar', 'electrical', 'Zamboanga City', 8],
            ['Nur', 'Jikiri', 'tutoring', 'Jolo', 3],
        ];
        $providers = [$provider];
        foreach ($people as $i => [$f, $l, $cat, $city, $yrs]) {
            $status = $i % 9 === 5 ? 'pending' : ($i % 11 === 7 ? 'unverified' : 'verified');
            $providers[] = $this->provider($f, $l, strtolower($f) . '.' . strtolower(str_replace(' ', '', $l)) . '@servease.test', $cat, $city, $yrs, $status, mt_rand(20, 110));
        }

        // --- Clients ---------------------------------------------------------------
        $clients = [$client];
        foreach ([['Patricia', 'Uy', 'Makati'], ['Miguel', 'Santiago', 'Pasig'], ['Clarisse', 'De Leon', 'Taguig'],
                  ['Raffy', 'Ocampo', 'Quezon City'], ['Jessa', 'Manalo', 'Manila'], ['Kevin', 'Sy', 'Cebu City'],
                  ['Andrea', 'Valdez', 'Mandaluyong'], ['Luis', 'Padilla', 'Parañaque'], ['Hannah', 'Chua', 'Davao City'],
                  ['Enzo', 'Marquez', 'Marikina'], ['Sofia', 'Aguilar', 'Las Piñas'], ['Tomas', 'Yap', 'Caloocan']] as [$f, $l, $city]) {
            $clients[] = $this->user('client', $f, $l, strtolower($f) . '.' . strtolower(str_replace(' ', '', $l)) . '@servease.test', $city, mt_rand(15, 100));
        }

        // --- Past completed + paid + reviewed bookings (history for ratings & charts) ---
        foreach ($providers as $p) {
            if ($p['verification_status'] === 'unverified') continue;
            // Spread over six months so the earnings chart has a real shape; the demo pro gets a steady stream of work
            $n = $p['id'] === $provider['id'] ? 24 : mt_rand(2, 8);
            for ($i = 0; $i < $n; $i++) {
                $c = $clients[mt_rand(1, count($clients) - 1)];
                $daysAgo = $p['id'] === $provider['id'] ? 3 + (int) round($i * 172 / $n) + mt_rand(0, 5) : mt_rand(3, 170);
                $this->historicBooking($c, $p, $daysAgo, mt_rand(1, 100) > 12);
            }
        }

        // --- Demo client's bookings in every state (to explore the full flow) -------
        $svc = DB::one('SELECT * FROM provider_services WHERE provider_id = ? ORDER BY id LIMIT 1', [$provider['id']]);
        $this->booking($client, $provider, 'Kitchen sink leak repair', 'requested', 2, 1500, $svc['id'] ?? null,
            'The pipe under the kitchen sink is leaking whenever we use the faucet. Please bring replacement fittings.');
        $this->booking($client, $providers[1], 'Install 3 ceiling lights in living room', 'accepted', 4, 2400, null,
            'Fixtures are already bought. Ceiling height is about 2.8m.');
        $this->booking($client, $providers[3], 'Aircon cleaning — 2 split-type units', 'in_progress', 1, 1800, null,
            '1.5HP and 1HP inverter units, last cleaned a year ago.');
        $this->booking($client, $providers[2], 'Deep cleaning — 2BR condo', 'completed', 6, 3200, null,
            'Move-out cleaning, includes kitchen and 2 bathrooms.', false, false);
        $this->booking($client, $providers[4], 'Custom floating shelves', 'completed', 20, 4500, null,
            'Three 90cm floating shelves in narra finish.', true, true);
        $this->booking($client, $providers[6], 'Repaint bedroom walls', 'cancelled', 12, 5200, null,
            'Accent wall + 3 walls in white.');

        // --- Demo provider incoming requests ------------------------------------------
        $this->booking($clients[2], $provider, 'Replace bathroom faucet and shower head', 'requested', 1, 1200, null,
            'Both are leaking. I already bought the replacements.');
        $this->booking($clients[4], $provider, 'Clogged drain in 2 bathrooms', 'accepted', 3, 1800, null,
            'Water drains very slowly in both showers.');

        // --- Job board --------------------------------------------------------------
        $this->jobs($clients, $providers, $client);

        // --- Conversations ----------------------------------------------------------
        $this->conversation($client, $provider, [
            [0, 'Hi Juan! Are you available this Saturday morning for a leaking sink?'],
            [1, 'Hello Maria! Yes, I have a slot at 9 AM. Can you send a photo of the leak?'],
            [0, 'Sure, it\'s coming from the joint under the sink. I sent the booking request already.'],
            [1, 'Got it. That looks like a worn slip-joint washer — should be a quick fix. I\'ll bring spares.'],
            [0, 'Great, thank you! See you Saturday.'],
        ], 1);
        $this->conversation($client, $providers[2], [
            [1, 'Hi Ma\'am Maria, thank you for booking! All done with the condo cleaning 😊'],
            [0, 'It looks amazing, thank you Liza! I\'ll pay via GCash today.'],
        ], 30);
        $this->conversation($clients[2], $provider, [
            [0, 'Good day! Do you also install water heaters?'],
            [1, 'Yes po, single-point heaters. Installation starts at ₱1,500 depending on the wiring.'],
        ], 200);

        // --- Recompute aggregates ----------------------------------------------------
        $this->recomputeRatings();

        // --- Welcome notifications -----------------------------------------------------
        notify($client['id'], 'welcome', 'Welcome to Servease, Maria!', 'Find trusted pros or post a job to get quotes.', '/dashboard');
        notify($provider['id'], 'booking', 'New booking request', 'Patricia requested “Replace bathroom faucet and shower head”.', '/bookings');
        notify($admin['id'], 'verification', 'Providers waiting for verification',
            'Review submitted documents in the verification queue.', '/admin/verifications');

        return ['users' => (int) DB::value('SELECT COUNT(*) FROM users'), 'password' => self::PASSWORD];
    }

    /* ------------------------------------------------------------------------ */

    private function categories(): void
    {
        $list = [
            ['Plumbing', 'plumbing', 'droplet', 'Leaks, clogs, pipes, fixtures and water heaters.'],
            ['Electrical', 'electrical', 'zap', 'Wiring, lighting, outlets and breaker repairs.'],
            ['Aircon & Appliances', 'aircon', 'snowflake', 'Aircon cleaning, repair and appliance servicing.'],
            ['House Cleaning', 'cleaning', 'sparkles', 'Regular, deep and move-in/move-out cleaning.'],
            ['Carpentry', 'carpentry', 'hammer', 'Furniture, cabinets, doors and custom woodwork.'],
            ['Painting', 'painting', 'paint-roller', 'Interior and exterior painting and finishing.'],
            ['Handyman', 'handyman', 'wrench', 'Small repairs, mounting, assembly and odd jobs.'],
            ['Moving & Hauling', 'moving', 'truck', 'House moves, deliveries and junk hauling.'],
            ['IT & Computer', 'it-support', 'monitor', 'Computer repair, networks, setup and support.'],
            ['Tutoring', 'tutoring', 'book-open', 'Academic tutoring, test prep and lessons.'],
            ['Beauty & Wellness', 'beauty', 'scissors', 'Home-service haircuts, nails, massage and makeup.'],
            ['Gardening', 'gardening', 'leaf', 'Lawn care, landscaping and plant maintenance.'],
        ];
        foreach ($list as $i => [$name, $slug, $icon, $desc]) {
            $this->cats[$slug] = DB::insert('categories', [
                'name' => $name, 'slug' => $slug, 'icon' => $icon, 'description' => $desc, 'sort_order' => $i + 1,
            ]);
        }
    }

    private function user(string $role, string $first, string $last, string $email, string $city, int $daysAgo): array
    {
        $created = date('Y-m-d H:i:s', strtotime("-$daysAgo days") - mt_rand(0, 40000));
        $id = DB::insert('users', [
            'role' => $role, 'first_name' => $first, 'last_name' => $last, 'email' => $email,
            'phone' => '09' . mt_rand(10, 99) . ' ' . mt_rand(100, 999) . ' ' . mt_rand(1000, 9999),
            'password_hash' => $this->hash, 'city' => $city,
            'email_verified_at' => $created, 'created_at' => $created,
        ]);
        return DB::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    private function provider(string $first, string $last, string $email, string $cat, string $city, int $years, string $verification, int $daysAgo): array
    {
        $u = $this->user('provider', $first, $last, $email, $city, $daysAgo);
        $info = self::CATALOG[$cat];
        $rate = $info['rate'][0] + 50 * mt_rand(0, (int) (($info['rate'][1] - $info['rate'][0]) / 50));
        DB::insert('provider_profiles', [
            'user_id' => $u['id'], 'category_id' => $this->cats[$cat],
            'headline' => $info['headlines'][mt_rand(0, count($info['headlines']) - 1)],
            'bio' => sprintf($info['bio'], $years, $city),
            'city' => $city, 'service_area' => $city . ' and nearby areas',
            'hourly_rate' => $rate, 'years_experience' => $years, 'skills' => $info['skills'],
            'is_available' => mt_rand(1, 10) > 2 ? 1 : 0,
            // Most pros work Mon–Sat; some take Saturdays off or also work Sundays
            // (derived from the id so the rest of the demo data stays the same)
            'work_days' => ['1,2,3,4,5,6', '1,2,3,4,5,6', '1,2,3,4,5', '1,2,3,4,5,6,7', '2,3,4,5,6,7'][$u['id'] % 5],
            'work_start' => ['07:00', '08:00', '08:00', '09:00'][$u['id'] % 4],
            'work_end' => ['17:00', '18:00', '18:00', '19:00'][($u['id'] >> 1) % 4],
            'verification_status' => $verification,
            'verification_doc' => $verification === 'unverified' ? null : 'verification/sample-id.pdf',
            'verified_at' => $verification === 'verified' ? $u['created_at'] : null,
            'created_at' => $u['created_at'],
        ]);
        foreach ($info['services'] as [$title, $desc, $price, $unit]) {
            DB::insert('provider_services', [
                'provider_id' => $u['id'], 'title' => $title, 'description' => $desc,
                'price' => $price + 50 * mt_rand(-2, 4), 'price_unit' => $unit,
                // Bigger jobs take longer; hourly and daily work is timed by the booking itself
                'duration_hours' => in_array($unit, ['fixed', 'starting'], true) ? max(1, min(4, (int) round($price / 700))) : 1,
            ]);
        }
        $u['verification_status'] = $verification;
        $u['category'] = $cat;
        return $u;
    }

    /** A realistic receipt number for transfers (cash has none). */
    private static function reference(string $method): ?string
    {
        return match ($method) {
            'gcash' => (string) mt_rand(1000, 9999) . ' ' . mt_rand(100, 999) . ' ' . mt_rand(100000, 999999),
            'maya'  => 'MY' . mt_rand(10000000, 99999999),
            'bank_transfer' => 'FT' . mt_rand(100000000, 999999999),
            default => null,
        };
    }

    private function historicBooking(array $client, array $provider, int $daysAgo, bool $review): void
    {
        $info = self::CATALOG[$provider['category']];
        [$title] = $info['services'][mt_rand(0, count($info['services']) - 1)];
        $amount = $info['rate'][0] * mt_rand(2, 6);
        $created = date('Y-m-d H:i:s', strtotime("-$daysAgo days") + mt_rand(0, 30000));
        $completed = date('Y-m-d H:i:s', strtotime($created) + 86400 * mt_rand(1, 3));
        if (strtotime($completed) > time()) $completed = date('Y-m-d H:i:s', time() - 3600);
        $id = DB::insert('bookings', [
            'client_id' => $client['id'], 'provider_id' => $provider['id'], 'title' => $title,
            'details' => 'Booked through Servease.', 'scheduled_date' => substr($completed, 0, 10), 'scheduled_time' => '09:00',
            'city' => $client['city'], 'amount' => $amount, 'status' => 'completed', 'payment_status' => 'paid',
            'accepted_at' => $created, 'started_at' => $completed, 'completed_at' => $completed,
            'created_at' => $created, 'updated_at' => $completed,
        ]);
        $methods = ['cash', 'gcash', 'gcash', 'maya', 'bank_transfer'];
        $method = $methods[mt_rand(0, 4)];
        DB::insert('payments', [
            'booking_id' => $id, 'payer_id' => $client['id'], 'payee_id' => $provider['id'], 'amount' => $amount,
            'method' => $method, 'channel' => 'direct', 'status' => 'paid', 'reference' => self::reference($method),
            'paid_at' => $completed, 'created_at' => $completed,
        ]);
        $this->events($id, $client['id'], $provider['id'], $created, $completed, 'completed');
        if ($review) {
            $r = mt_rand(1, 100);
            $rating = $r > 30 ? 5 : ($r > 8 ? 4 : 3);
            $pool = $rating === 5 ? self::REVIEWS_5 : ($rating === 4 ? self::REVIEWS_4 : self::REVIEWS_3);
            DB::insert('reviews', [
                'booking_id' => $id, 'provider_id' => $provider['id'], 'client_id' => $client['id'],
                'rating' => $rating, 'comment' => $pool[mt_rand(0, count($pool) - 1)],
                'created_at' => date('Y-m-d H:i:s', strtotime($completed) + 7200),
            ]);
        }
    }

    private function booking(array $client, array $provider, string $title, string $status, int $inDays, float $amount,
                             ?int $serviceId, string $details, bool $paid = false, bool $review = false): int
    {
        $past = in_array($status, ['completed', 'cancelled'], true);
        $date = date('Y-m-d', strtotime(($past ? '-' : '+') . $inDays . ' days'));
        $created = date('Y-m-d H:i:s', strtotime($past ? "-" . ($inDays + 3) . " days" : '-' . mt_rand(1, 20) . ' hours'));
        $now = date('Y-m-d H:i:s');
        $id = DB::insert('bookings', [
            'client_id' => $client['id'], 'provider_id' => $provider['id'], 'service_id' => $serviceId,
            'title' => $title, 'details' => $details, 'scheduled_date' => $date, 'scheduled_time' => '10:00',
            'address' => 'Unit 12B, Sample Residences', 'city' => $client['city'], 'amount' => $amount,
            'status' => $status, 'payment_status' => $paid ? 'paid' : 'unpaid',
            'accepted_at' => in_array($status, ['accepted', 'in_progress', 'completed'], true) ? $created : null,
            'started_at' => in_array($status, ['in_progress', 'completed'], true) ? $created : null,
            'completed_at' => $status === 'completed' ? date('Y-m-d H:i:s', strtotime($date . ' 15:00')) : null,
            'cancel_reason' => $status === 'cancelled' ? 'Schedule changed — will rebook next month.' : null,
            'created_at' => $created,
        ]);
        $this->events($id, $client['id'], $provider['id'], $created, $now, $status);
        if ($paid) {
            DB::insert('payments', [
                'booking_id' => $id, 'payer_id' => $client['id'], 'payee_id' => $provider['id'], 'amount' => $amount,
                'method' => 'gcash', 'channel' => 'direct', 'status' => 'paid', 'reference' => 'GC-' . mt_rand(100000, 999999),
                'paid_at' => date('Y-m-d H:i:s', strtotime($date . ' 16:00')),
            ]);
        }
        if ($review) {
            DB::insert('reviews', [
                'booking_id' => $id, 'provider_id' => $provider['id'], 'client_id' => $client['id'], 'rating' => 5,
                'comment' => 'Beautiful work and very neat installation. The shelves are solid and exactly the size we wanted.',
                'provider_reply' => 'Thank you Ma\'am Maria! Enjoy your new shelves.',
            ]);
        }
        return $id;
    }

    private function events(int $bookingId, int $clientId, int $providerId, string $from, string $to, string $status): void
    {
        $flow = ['requested' => $clientId, 'accepted' => $providerId, 'in_progress' => $providerId, 'completed' => $providerId];
        $t0 = strtotime($from);
        $t1 = max($t0 + 60, strtotime($to));
        if ($status === 'cancelled') {
            DB::insert('booking_events', ['booking_id' => $bookingId, 'actor_id' => $clientId, 'event' => 'requested', 'created_at' => date('Y-m-d H:i:s', $t0)]);
            DB::insert('booking_events', ['booking_id' => $bookingId, 'actor_id' => $clientId, 'event' => 'cancelled', 'created_at' => date('Y-m-d H:i:s', $t0 + 7200)]);
            return;
        }
        $keys = array_keys($flow);
        $upto = array_search($status, $keys, true);
        for ($i = 0; $i <= $upto; $i++) {
            $ts = $t0 + (int) (($t1 - $t0) * ($i / max(1, $upto)));
            DB::insert('booking_events', ['booking_id' => $bookingId, 'actor_id' => $flow[$keys[$i]], 'event' => $keys[$i], 'created_at' => date('Y-m-d H:i:s', $ts)]);
        }
    }

    private function jobs(array $clients, array $providers, array $demoClient): void
    {
        $jobs = [
            ['plumbing', 'Water heater installation in bathroom', 'Need a single-point water heater installed. Unit is already purchased, outlet is near the shower.', 1500, 2500, 'this_week'],
            ['electrical', 'Rewire old apartment unit (studio)', 'The outlets keep tripping. Looking for a licensed electrician to inspect and replace old wiring.', 8000, 15000, 'flexible'],
            ['cleaning', 'Weekly cleaning for 3BR house', 'Looking for a reliable cleaner every Saturday. Includes laundry folding.', 1200, 1800, 'flexible'],
            ['aircon', 'Window-type aircon not cooling', '1HP window type, fan works but air is not cold. Probably needs freon or cleaning.', 800, 2000, 'urgent'],
            ['carpentry', 'Build a study desk with shelves', 'Custom desk 140cm wide with 2 overhead shelves. Prefer plywood with laminate.', 6000, 9000, 'flexible'],
            ['painting', 'Paint exterior gate and grills', 'Steel gate (3m) and window grills need repainting, some rust removal.', 3000, 5000, 'this_week'],
            ['it-support', 'Set up home Wi-Fi mesh + CCTV', 'Two-storey house. Need mesh Wi-Fi and 4 CCTV cameras with phone viewing.', 4000, 7000, 'this_week'],
            ['tutoring', 'Grade 10 Math tutor (2x a week)', 'Online or home tutoring for algebra and geometry, 1.5 hours per session.', 400, 600, 'flexible'],
            ['moving', 'Move from Pasig to Taguig (1BR)', 'Small 1-bedroom move, has a ref, washing machine, bed and ~15 boxes.', 3500, 6000, 'urgent'],
            ['handyman', 'Mount TV and install curtain rods', '55" TV on concrete wall and 4 curtain rods. I have the brackets.', 1000, 1800, 'this_week'],
            ['beauty', 'Home service hair and makeup for wedding', 'For the bride + 3 bridesmaids on a Saturday morning in Makati.', 8000, 12000, 'flexible'],
            ['gardening', 'Garden cleanup and lawn trimming', 'Front and back lawn, about 60 sqm total. Also trim bougainvillea.', 1500, 2500, 'flexible'],
            ['plumbing', 'Low water pressure on second floor', 'Pressure is fine downstairs but weak upstairs. May need a booster pump.', 2000, 6000, 'this_week'],
            ['electrical', 'Install outdoor lights and motion sensor', '3 outdoor wall lights and 1 motion sensor floodlight for the garage.', 2500, 4000, 'flexible'],
        ];
        foreach ($jobs as $i => [$cat, $title, $desc, $min, $max, $urgency]) {
            $c = $i === 0 ? $demoClient : $clients[mt_rand(1, count($clients) - 1)];
            $created = date('Y-m-d H:i:s', time() - mt_rand(2, 240) * 3600 - $i * 5000);
            $jobId = DB::insert('jobs', [
                'client_id' => $c['id'], 'category_id' => $this->cats[$cat], 'title' => $title, 'description' => $desc,
                'city' => $c['city'], 'budget_min' => $min, 'budget_max' => $max,
                'preferred_date' => date('Y-m-d', strtotime('+' . mt_rand(2, 14) . ' days')),
                'urgency' => $urgency, 'created_at' => $created,
            ]);
            // quotes from providers in the same category
            $count = 0;
            foreach ($providers as $p) {
                if ($p['category'] !== $cat || $p['verification_status'] !== 'verified' || $p['id'] === $c['id']) continue;
                if ($count >= 3 || mt_rand(1, 10) > 7) continue;
                DB::insert('quotes', [
                    'job_id' => $jobId, 'provider_id' => $p['id'], 'amount' => (int) (($min + $max) / 2 / 50) * 50 + 50 * mt_rand(-4, 4),
                    'message' => self::QUOTE_MSGS[mt_rand(0, count(self::QUOTE_MSGS) - 1)],
                    'days_needed' => mt_rand(1, 3),
                    'created_at' => date('Y-m-d H:i:s', strtotime($created) + mt_rand(1800, 20000)),
                ]);
                $count++;
            }
            DB::update('jobs', ['quotes_count' => $count], 'id = :id', ['id' => $jobId]);
        }
    }

    private function conversation(array $a, array $b, array $lines, int $minutesAgo): void
    {
        [$one, $two] = $a['id'] < $b['id'] ? [$a, $b] : [$b, $a];
        $convId = DB::insert('conversations', ['user_one_id' => $one['id'], 'user_two_id' => $two['id']]);
        $t = time() - $minutesAgo * 60 - count($lines) * 420;
        foreach ($lines as $i => [$who, $text]) {
            $t += 420;
            $sender = $who === 0 ? $a : $b;
            $isLast = $i === count($lines) - 1;
            DB::insert('messages', [
                'conversation_id' => $convId, 'sender_id' => $sender['id'], 'body' => $text,
                'read_at' => $isLast ? null : date('Y-m-d H:i:s', $t + 60), 'created_at' => date('Y-m-d H:i:s', $t),
            ]);
        }
        DB::update('conversations', ['last_message_at' => date('Y-m-d H:i:s', $t)], 'id = :id', ['id' => $convId]);
    }

    private function recomputeRatings(): void
    {
        DB::query('UPDATE provider_profiles pp SET
            rating_avg = COALESCE((SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.provider_id = pp.user_id AND r.is_hidden = 0), 0),
            rating_count = (SELECT COUNT(*) FROM reviews r2 WHERE r2.provider_id = pp.user_id AND r2.is_hidden = 0),
            jobs_completed = (SELECT COUNT(*) FROM bookings b WHERE b.provider_id = pp.user_id AND b.status = \'completed\')');
    }

    /* ---- Content -------------------------------------------------------------- */

    private const QUOTE_MSGS = [
        'Hi! I can do this job. Price includes labor and basic materials. I can start on your preferred date.',
        'Good day po! I have done many similar jobs in your area. I will inspect first then finish within the day if no major issue.',
        'Hello! I\'m available this week. Quote covers labor; I\'ll send a list of materials before starting so there are no surprises.',
        'I can take this. I bring my own tools and clean up after. 30-day workmanship warranty included.',
    ];

    private const REVIEWS_5 = [
        'Very professional and on time. Explained everything clearly and cleaned up after. Highly recommended!',
        'Super bait and maayos magtrabaho. Fixed the problem in less than an hour. Will book again.',
        'Excellent work! Fair price and very honest about what really needed to be done.',
        'Arrived early, worked fast and the result is perfect. 10/10.',
        'Great communication from booking until the job was finished. Sulit!',
        'Very neat work and respectful in our home. My go-to from now on.',
    ];
    private const REVIEWS_4 = [
        'Good job overall. Arrived a bit late but the work quality is great.',
        'Solid work and reasonable rate. Just had to buy one extra part the next day.',
        'Friendly and skilled. Would recommend, communication could be a little faster.',
    ];
    private const REVIEWS_3 = [
        'Job got done but took longer than expected.',
        'Okay service. Price was a bit higher than quoted because of extra materials.',
    ];

    private const CATALOG = [
        'plumbing' => [
            'headlines' => ['Licensed plumber — leaks, clogs & installations', 'Fast, clean plumbing repairs', 'Residential plumbing specialist'],
            'bio' => "I've been fixing leaks, clogs and water lines for %d years around %s. I bring my own tools, explain the problem before I start, and give a 30-day workmanship warranty.",
            'skills' => 'Leak repair, Pipe installation, Drain unclogging, Water heaters, Fixtures',
            'rate' => [350, 600],
            'services' => [['Leak repair', 'Faucets, pipes, joints and fittings', 800, 'starting'], ['Drain unclogging', 'Sinks, showers and floor drains', 1000, 'fixed'], ['Water heater installation', 'Single-point heaters, includes testing', 1500, 'starting']],
        ],
        'electrical' => [
            'headlines' => ['Licensed electrician — safe wiring & lighting', 'Residential & commercial electrical works', 'Electrical troubleshooting expert'],
            'bio' => "Registered electrician with %d years of experience in %s. I handle rewiring, lighting, outlets and breaker panels — always to code and always safe.",
            'skills' => 'Wiring, Lighting installation, Breaker panels, Troubleshooting, Outlets',
            'rate' => [400, 700],
            'services' => [['Lighting installation', 'Per fixture, includes testing', 450, 'fixed'], ['Outlet & switch repair', 'Replacement and rewiring', 500, 'starting'], ['Electrical inspection', 'Full home safety check', 1500, 'fixed']],
        ],
        'aircon' => [
            'headlines' => ['Aircon cleaning, repair & installation', 'Certified aircon & appliance technician', 'Split-type and window aircon specialist'],
            'bio' => "Aircon and appliance technician for %d years serving %s. Cleaning, freon recharge, repair and installation of window and split-type units.",
            'skills' => 'Aircon cleaning, Freon recharge, Split-type installation, Refrigerator repair, Washing machines',
            'rate' => [400, 650],
            'services' => [['Aircon cleaning (window)', 'Full chemical wash', 700, 'fixed'], ['Aircon cleaning (split-type)', 'Indoor + outdoor unit', 1100, 'fixed'], ['Freon recharge', 'Includes leak check', 1800, 'starting']],
        ],
        'cleaning' => [
            'headlines' => ['Detailed home & condo cleaning', 'Deep cleaning and move-out specialist', 'Reliable weekly house cleaning'],
            'bio' => "Professional cleaner with %d years of experience in %s. I bring eco-friendly supplies and pay attention to the details other cleaners miss.",
            'skills' => 'Deep cleaning, Condo cleaning, Move-in/out, Laundry, Organizing',
            'rate' => [250, 450],
            'services' => [['Regular cleaning', 'Up to 4 hours', 1000, 'fixed'], ['Deep cleaning', 'Kitchen, bathrooms, behind appliances', 2800, 'starting'], ['Move-out cleaning', 'For condo and apartment turnovers', 3200, 'starting']],
        ],
        'carpentry' => [
            'headlines' => ['Custom furniture & cabinet maker', 'Carpentry and woodwork — built to last', 'Kitchen cabinets and built-ins'],
            'bio' => "Carpenter for %d years based in %s. I build custom cabinets, shelves and furniture, and repair doors, frames and flooring.",
            'skills' => 'Cabinets, Custom furniture, Door repair, Shelving, Flooring',
            'rate' => [450, 800],
            'services' => [['Door repair & installation', 'Hinges, locks, planing', 900, 'starting'], ['Custom shelves', 'Per linear meter', 2500, 'starting'], ['Kitchen cabinet build', 'Design + build', 15000, 'starting']],
        ],
        'painting' => [
            'headlines' => ['Interior & exterior painting', 'Clean, sharp painting and finishing', 'Wall painting and waterproofing'],
            'bio' => "Painter with %d years of experience around %s. Proper surface prep, clean lines and no mess left behind.",
            'skills' => 'Interior painting, Exterior painting, Waterproofing, Wall repair, Accent walls',
            'rate' => [350, 600],
            'services' => [['Room repaint', 'Walls and ceiling, per room', 3500, 'starting'], ['Accent wall', 'Includes prep and 2 coats', 1800, 'fixed'], ['Gate & grill painting', 'Rust removal + enamel', 2500, 'starting']],
        ],
        'handyman' => [
            'headlines' => ['Handyman for all the little things', 'Mounting, assembly & small repairs', 'Your reliable fix-it person'],
            'bio' => "Jack-of-all-trades handyman with %d years of experience in %s. TV mounting, furniture assembly, curtain rods, minor repairs — one visit, many fixes.",
            'skills' => 'TV mounting, Furniture assembly, Curtain rods, Minor repairs, Drilling',
            'rate' => [300, 550],
            'services' => [['TV wall mounting', 'Up to 65", bracket provided by client', 1200, 'fixed'], ['Furniture assembly', 'Per item', 600, 'starting'], ['Handyman hour', 'Any small tasks', 450, 'hour']],
        ],
        'moving' => [
            'headlines' => ['Moving & hauling with closed van', 'Stress-free house moving', 'Same-day delivery and junk removal'],
            'bio' => "Moving and hauling crew operating for %d years out of %s. Closed van, trained helpers and careful handling of your things.",
            'skills' => 'House moving, Furniture delivery, Junk removal, Packing, Closed van',
            'rate' => [500, 900],
            'services' => [['Small move (studio/1BR)', 'Van + 2 helpers, within Metro', 4500, 'starting'], ['Furniture delivery', 'Single item pickup and drop', 1500, 'starting'], ['Junk hauling', 'Per van load', 2500, 'fixed']],
        ],
        'it-support' => [
            'headlines' => ['Computer repair & home network setup', 'IT support for homes and small offices', 'Laptop, Wi-Fi and CCTV technician'],
            'bio' => "IT technician with %d years of experience in %s. Laptop and PC repair, virus cleanup, Wi-Fi mesh and CCTV setup for homes and small offices.",
            'skills' => 'PC repair, Networking, CCTV, Data recovery, Software setup',
            'rate' => [400, 800],
            'services' => [['PC / laptop repair', 'Diagnosis + repair', 800, 'starting'], ['Wi-Fi mesh setup', 'Configuration and coverage test', 1500, 'fixed'], ['CCTV installation', 'Up to 4 cameras', 4500, 'starting']],
        ],
        'tutoring' => [
            'headlines' => ['Math & Science tutor (Grades 4–12)', 'Patient, results-focused tutoring', 'College entrance exam coach'],
            'bio' => "Licensed teacher with %d years of tutoring experience in %s. I focus on building understanding, not memorisation, with lessons tailored to each student.",
            'skills' => 'Mathematics, Science, English, Exam prep, Online tutoring',
            'rate' => [300, 600],
            'services' => [['1-hour tutoring session', 'Home or online', 400, 'hour'], ['Exam prep package', '8 sessions', 3000, 'fixed'], ['Homework help', '30-minute sessions', 200, 'fixed']],
        ],
        'beauty' => [
            'headlines' => ['Home-service hair & makeup artist', 'Nails, lashes and spa at home', 'Bridal and event makeup'],
            'bio' => "Beauty professional with %d years of experience in %s. I bring salon-quality service to your home, using clean tools and quality products.",
            'skills' => 'Haircut, Makeup, Nails, Massage, Bridal',
            'rate' => [300, 800],
            'services' => [['Home-service haircut', 'Men and women', 350, 'fixed'], ['Event makeup', 'Includes lashes', 1500, 'starting'], ['Mani-pedi', 'Classic, at home', 500, 'fixed']],
        ],
        'gardening' => [
            'headlines' => ['Garden care and landscaping', 'Lawn mowing and plant care', 'Landscape design for small spaces'],
            'bio' => "Gardener and landscaper for %d years around %s. Lawn care, trimming, planting and small landscape makeovers.",
            'skills' => 'Lawn mowing, Trimming, Landscaping, Planting, Garden cleanup',
            'rate' => [250, 500],
            'services' => [['Lawn mowing', 'Up to 50 sqm', 800, 'starting'], ['Garden cleanup', 'Trimming, weeding and hauling', 1500, 'starting'], ['Landscape makeover', 'Design + planting', 6000, 'starting']],
        ],
    ];
}
