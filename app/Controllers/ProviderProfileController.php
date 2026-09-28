<?php
declare(strict_types=1);

final class ProviderProfileController
{
    private function profile(array $u): array
    {
        $p = DB::one('SELECT * FROM provider_profiles WHERE user_id = ?', [(int) $u['id']]);
        if (!$p) {
            DB::insert('provider_profiles', ['user_id' => (int) $u['id'], 'city' => $u['city']]);
            $p = DB::one('SELECT * FROM provider_profiles WHERE user_id = ?', [(int) $u['id']]);
        }
        return $p;
    }

    public function edit(): void
    {
        $u = require_role('provider');
        $p = $this->profile($u);
        view('provider/profile', [
            'title' => 'Pro profile', 'u' => $u, 'p' => $p,
            'services' => DB::all('SELECT * FROM provider_services WHERE provider_id = ? ORDER BY price', [(int) $u['id']]),
            'portfolio' => DB::all('SELECT * FROM portfolio_items WHERE provider_id = ? ORDER BY created_at DESC', [(int) $u['id']]),
            'active' => 'profile',
        ], 'layouts/shell');
    }

    public function update(): void
    {
        $u = require_role('provider');
        $this->profile($u);
        $v = Validator::make($_POST, [
            'category_id'      => 'required|int|exists:categories,id',
            'headline'         => 'required|min:10|max:120|string',
            'bio'              => 'required|min:40|max:3000|string',
            'city'             => 'required|max:80',
            'service_area'     => 'nullable|max:160',
            'hourly_rate'      => 'required|numeric|min:50|max:100000',
            'years_experience' => 'required|int|min:0|max:60',
            'skills'           => 'nullable|max:255',
        ], ['category_id' => 'category', 'hourly_rate' => 'hourly rate', 'years_experience' => 'years of experience']);
        if ($v->fails()) back_with_errors($v->errors(), '/provider/profile');
        $d = $v->validated();
        $skills = implode(', ', array_slice(array_unique(array_filter(array_map('trim', explode(',', (string) $d['skills'])))), 0, 12));

        DB::update('provider_profiles', [
            'category_id' => (int) $d['category_id'], 'headline' => $d['headline'], 'bio' => $d['bio'],
            'city' => $d['city'], 'service_area' => $d['service_area'], 'hourly_rate' => (float) $d['hourly_rate'],
            'years_experience' => (int) $d['years_experience'], 'skills' => $skills ?: null,
        ], 'user_id = :id', ['id' => (int) $u['id']]);
        flash('success', 'Profile saved. Looking good!');
        redirect('/provider/profile');
    }

    public function toggleAvailability(): void
    {
        $u = require_role('provider');
        $p = $this->profile($u);
        $now = $p['is_available'] ? 0 : 1;
        DB::update('provider_profiles', ['is_available' => $now], 'user_id = :id', ['id' => (int) $u['id']]);
        flash('success', $now ? 'You\'re now shown as taking new jobs.' : 'You\'re now shown as fully booked.');
        back('/provider/profile');
    }

    public function addService(): void
    {
        $u = require_role('provider');
        if ((int) DB::value('SELECT COUNT(*) FROM provider_services WHERE provider_id = ?', [(int) $u['id']]) >= 15) {
            flash('error', 'You can list up to 15 services.');
            redirect('/provider/profile#services');
        }
        $v = Validator::make($_POST, [
            'title'       => 'required|min:3|max:120|string',
            'description' => 'nullable|max:255|string',
            'price'       => 'required|numeric|min:50|max:1000000',
            'price_unit'  => 'required|in:fixed,hour,day,starting',
        ]);
        if ($v->fails()) back_with_errors($v->errors(), '/provider/profile#services');
        $d = $v->validated();
        DB::insert('provider_services', [
            'provider_id' => (int) $u['id'], 'title' => $d['title'], 'description' => $d['description'],
            'price' => (float) $d['price'], 'price_unit' => $d['price_unit'],
        ]);
        flash('success', 'Service added.');
        redirect('/provider/profile#services');
    }

    public function deleteService(string $id): void
    {
        $u = require_role('provider');
        DB::query('DELETE FROM provider_services WHERE id = ? AND provider_id = ?', [(int) $id, (int) $u['id']]);
        flash('success', 'Service removed.');
        redirect('/provider/profile#services');
    }

    public function addPortfolio(): void
    {
        $u = require_role('provider');
        if ((int) DB::value('SELECT COUNT(*) FROM portfolio_items WHERE provider_id = ?', [(int) $u['id']]) >= 24) {
            flash('error', 'You can upload up to 24 portfolio photos.');
            redirect('/provider/profile#portfolio');
        }
        $res = Upload::store($_FILES['image'] ?? null, 'portfolio', Upload::IMAGES);
        if ($res['error']) back_with_errors(['image' => $res['error']], '/provider/profile#portfolio');
        DB::insert('portfolio_items', [
            'provider_id' => (int) $u['id'], 'image' => $res['path'],
            'caption' => mb_substr(trim((string) ($_POST['caption'] ?? '')), 0, 160) ?: null,
        ]);
        flash('success', 'Photo added to your portfolio.');
        redirect('/provider/profile#portfolio');
    }

    public function deletePortfolio(string $id): void
    {
        $u = require_role('provider');
        $item = DB::one('SELECT * FROM portfolio_items WHERE id = ? AND provider_id = ?', [(int) $id, (int) $u['id']]);
        if ($item) {
            Upload::delete($item['image']);
            DB::query('DELETE FROM portfolio_items WHERE id = ?', [(int) $id]);
        }
        flash('success', 'Photo removed.');
        redirect('/provider/profile#portfolio');
    }

    public function submitVerification(): void
    {
        $u = require_role('provider');
        $p = $this->profile($u);
        if ($p['verification_status'] === 'verified') redirect('/provider/profile#verification');
        $res = Upload::store($_FILES['document'] ?? null, 'verification', Upload::DOCS, (int) config('uploads.max_doc_mb', 8), true);
        if ($res['error']) back_with_errors(['document' => $res['error']], '/provider/profile#verification');
        if ($p['verification_doc'] && $p['verification_doc'] !== 'verification/sample-id.pdf') Upload::delete($p['verification_doc'], true);
        DB::update('provider_profiles', [
            'verification_doc' => $res['path'], 'verification_status' => 'pending', 'verification_note' => null,
        ], 'user_id = :id', ['id' => (int) $u['id']]);
        foreach (DB::all("SELECT id FROM users WHERE role = 'admin' AND status = 'active'") as $a) {
            notify((int) $a['id'], 'verification', 'Verification request', full_name($u) . ' submitted an ID for review.', '/admin/verifications');
        }
        flash('success', 'Thanks! Your document is being reviewed. This usually takes 1–2 business days.');
        redirect('/provider/profile#verification');
    }
}
