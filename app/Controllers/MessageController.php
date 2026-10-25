<?php
declare(strict_types=1);

final class MessageController
{
    private function conversations(int $uid): array
    {
        return DB::all(
            "SELECT c.id, c.last_message_at,
                    (SELECT m.body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT m.sender_id FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id <> ? AND m.read_at IS NULL) AS unread,
                    o.id AS o_id, o.first_name AS o_first, o.last_name AS o_last, o.avatar AS o_avatar, o.role AS o_role
             FROM conversations c
             JOIN users o ON o.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id)
             WHERE c.user_one_id = ? OR c.user_two_id = ?
             ORDER BY c.last_message_at IS NULL, c.last_message_at DESC, c.id DESC",
            [$uid, $uid, $uid, $uid]
        );
    }

    private function conversation(int $id, int $uid): array
    {
        $c = DB::one('SELECT * FROM conversations WHERE id = ? AND (user_one_id = ? OR user_two_id = ?)', [$id, $uid, $uid]);
        if (!$c) abort(404, 'Conversation not found.');
        $otherId = (int) $c['user_one_id'] === $uid ? (int) $c['user_two_id'] : (int) $c['user_one_id'];
        $c['other'] = DB::one(
            'SELECT u.id, u.first_name, u.last_name, u.avatar, u.role, u.city, pp.headline
             FROM users u LEFT JOIN provider_profiles pp ON pp.user_id = u.id WHERE u.id = ?',
            [$otherId]
        );
        return $c;
    }

    private function markRead(int $convId, int $uid): void
    {
        DB::query('UPDATE messages SET read_at = ? WHERE conversation_id = ? AND sender_id <> ? AND read_at IS NULL', [date('Y-m-d H:i:s'), $convId, $uid]);
    }

    private static function present(array $m, int $uid): array
    {
        return [
            'id' => (int) $m['id'],
            'body' => $m['body'],
            'mine' => (int) $m['sender_id'] === $uid,
            'time' => date('M j, g:i A', strtotime($m['created_at'])),
        ];
    }

    public function index(): void
    {
        $u = require_auth();
        $list = $this->conversations((int) $u['id']);
        view('messages/index', ['title' => 'Messages', 'list' => $list, 'c' => null, 'messages' => [], 'active' => 'messages'], 'layouts/shell');
    }

    public function show(string $id): void
    {
        $u = require_auth();
        $uid = (int) $u['id'];
        $c = $this->conversation((int) $id, $uid);
        $this->markRead((int) $id, $uid);
        DB::query('UPDATE notifications SET read_at = ? WHERE user_id = ? AND link = ? AND read_at IS NULL', [date('Y-m-d H:i:s'), $uid, '/messages/' . (int) $id]);
        $messages = array_reverse(DB::all('SELECT * FROM messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 200', [(int) $id]));
        $list = $this->conversations($uid);
        $shared = DB::all(
            "SELECT id, title, status FROM bookings WHERE (client_id = ? AND provider_id = ?) OR (client_id = ? AND provider_id = ?)
             ORDER BY created_at DESC LIMIT 3",
            [$uid, (int) $c['other']['id'], (int) $c['other']['id'], $uid]
        );
        view('messages/index', [
            'title' => 'Chat with ' . $c['other']['first_name'], 'list' => $list, 'c' => $c,
            'messages' => $messages, 'shared' => $shared, 'active' => 'messages', 'bodyClass' => 'is-chat-open',
        ], 'layouts/shell');
    }

    /** Start (or reopen) a conversation with another user. */
    public function start(): void
    {
        $u = require_auth();
        $uid = (int) $u['id'];
        $other = (int) ($_POST['user_id'] ?? 0);
        if ($other === $uid || !DB::value("SELECT 1 FROM users WHERE id = ? AND status = 'active'", [$other])) {
            flash('error', 'You can\'t message that user.');
            back('/messages');
        }
        [$one, $two] = $uid < $other ? [$uid, $other] : [$other, $uid];
        $id = DB::value('SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ?', [$one, $two]);
        if (!$id) $id = DB::insert('conversations', ['user_one_id' => $one, 'user_two_id' => $two]);

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body !== '') $this->storeMessage((int) $id, $u, $body);
        redirect('/messages/' . $id);
    }

    public function send(string $id): void
    {
        $u = require_auth();
        $c = $this->conversation((int) $id, (int) $u['id']);
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 2000) {
            if (wants_json()) json_out(['error' => 'Messages must be between 1 and 2000 characters.'], 422);
            flash('error', 'Messages must be between 1 and 2000 characters.');
            redirect('/messages/' . $id);
        }
        $m = $this->storeMessage((int) $c['id'], $u, $body, (int) $c['other']['id']);
        if (wants_json()) json_out(['message' => self::present($m, (int) $u['id'])]);
        redirect('/messages/' . $id);
    }

    private function storeMessage(int $convId, array $u, string $body, ?int $otherId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $mid = DB::insert('messages', ['conversation_id' => $convId, 'sender_id' => (int) $u['id'], 'body' => $body, 'created_at' => $now]);
        DB::update('conversations', ['last_message_at' => $now], 'id = :id', ['id' => $convId]);

        if ($otherId === null) {
            $c = DB::one('SELECT * FROM conversations WHERE id = ?', [$convId]);
            $otherId = (int) $c['user_one_id'] === (int) $u['id'] ? (int) $c['user_two_id'] : (int) $c['user_one_id'];
        }
        // One unread notification per conversation is enough — avoid flooding the bell.
        $link = '/messages/' . $convId;
        if (!DB::value('SELECT 1 FROM notifications WHERE user_id = ? AND link = ? AND read_at IS NULL', [$otherId, $link])) {
            notify($otherId, 'message', 'New message from ' . $u['first_name'], str_limit($body, 90), $link);
        }
        return DB::one('SELECT * FROM messages WHERE id = ?', [$mid]);
    }

    /** Lightweight polling endpoint used by the chat window. */
    public function poll(string $id): void
    {
        $u = user();
        if (!$u) json_out(['error' => 'login'], 401);
        $uid = (int) $u['id'];
        $this->conversation((int) $id, $uid);
        $after = max(0, (int) ($_GET['after'] ?? 0));
        $rows = DB::all('SELECT * FROM messages WHERE conversation_id = ? AND id > ? ORDER BY id LIMIT 100', [(int) $id, $after]);
        if ($rows) $this->markRead((int) $id, $uid);
        json_out(['messages' => array_map(fn($m) => self::present($m, $uid), $rows)]);
    }
}
