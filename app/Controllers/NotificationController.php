<?php
declare(strict_types=1);

final class NotificationController
{
    public function index(): void
    {
        $u = require_auth();
        [$offset, $perPage, $page] = page_params(20);
        $total = (int) DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [(int) $u['id']]);
        $rows = DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?', [(int) $u['id'], $perPage, $offset]);
        view('account/notifications', ['title' => 'Notifications', 'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'active' => 'notifications'], 'layouts/shell');
    }

    public function open(string $id): void
    {
        $u = require_auth();
        $n = DB::one('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [(int) $id, (int) $u['id']]);
        if (!$n) redirect('/notifications');
        if (!$n['read_at']) DB::update('notifications', ['read_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $id]);
        $link = (string) $n['link'];
        redirect($link !== '' && str_starts_with($link, '/') && !str_starts_with($link, '//') ? $link : '/notifications');
    }

    public function readAll(): void
    {
        $u = require_auth();
        DB::query('UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL', [date('Y-m-d H:i:s'), (int) $u['id']]);
        flash('success', 'All notifications marked as read.');
        back('/notifications');
    }
}
