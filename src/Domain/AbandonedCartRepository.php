<?php

declare(strict_types=1);

namespace FP\CartRecovery\Domain;

/**
 * Repository per i carrelli abbandonati.
 */
final class AbandonedCartRepository {

    private \wpdb $wpdb;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'fp_cartrecovery_carts';
    }

    /**
     * Salva o aggiorna un carrello abbandonato.
     *
     * @param array<string, mixed> $data
     * @return int|false ID inserito/aggiornato o false
     */
    public function upsert(array $data): int|false {
        $session_key = sanitize_text_field((string) ($data['session_key'] ?? ''));
        $user_id = absint($data['user_id'] ?? 0);
        $email = sanitize_email((string) ($data['email'] ?? '')) ?: '';
        $cart_content = is_string($data['cart_content'] ?? '') ? $data['cart_content'] : wp_json_encode($data['cart_content'] ?? []);
        $cart_total = (float) ($data['cart_total'] ?? 0);
        $currency = sanitize_text_field((string) ($data['currency'] ?? 'EUR'));
        $recovery_token = (string) ($data['recovery_token'] ?? '');
        $status = in_array($data['status'] ?? '', ['abandoned', 'recovered'], true) ? $data['status'] : 'abandoned';

        if ($session_key === '' && $user_id === 0) {
            return false;
        }

        $existing = $this->find_by_session_or_user($session_key, $user_id);

        if ($existing) {
            $this->wpdb->update(
                $this->table,
                [
                    'cart_content' => $cart_content,
                    'cart_total'   => $cart_total,
                    'currency'     => $currency,
                    'email'        => $email ?: $existing['email'],
                    'updated_at'   => current_time('mysql'),
                ],
                ['id' => $existing['id']],
                ['%s', '%f', '%s', '%s', '%s'],
                ['%d']
            );
            return (int) $existing['id'];
        }

        if ($recovery_token === '') {
            $recovery_token = bin2hex(random_bytes(16));
        }

        $this->wpdb->insert(
            $this->table,
            [
                'session_key'   => $session_key,
                'user_id'       => $user_id,
                'email'         => $email,
                'cart_content'  => $cart_content,
                'cart_total'    => $cart_total,
                'currency'      => $currency,
                'recovery_token'=> $recovery_token,
                'status'        => $status,
            ],
            ['%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s']
        );

        return $this->wpdb->insert_id ? (int) $this->wpdb->insert_id : false;
    }

    /**
     * Trova per session_key o user_id.
     *
     * @return array<string, mixed>|null
     */
    public function find_by_session_or_user(string $session_key, int $user_id): ?array {
        if ($session_key !== '') {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->table} WHERE session_key = %s AND status = 'abandoned' ORDER BY id DESC LIMIT 1",
                    $session_key
                ),
                ARRAY_A
            );
            if ($row) {
                return $row;
            }
        }

        if ($user_id > 0) {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->table} WHERE user_id = %d AND status = 'abandoned' ORDER BY id DESC LIMIT 1",
                    $user_id
                ),
                ARRAY_A
            );
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Trova il carrello abbandonato più recente per email (utenti guest).
     *
     * @return array<string, mixed>|null
     */
    public function find_abandoned_by_email(string $email): ?array {
        if ($email === '') {
            return null;
        }
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE email = %s AND status = 'abandoned' ORDER BY updated_at DESC LIMIT 1",
                $email
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * Trova per recovery token.
     *
     * @return array<string, mixed>|null
     */
    public function find_by_token(string $token): ?array {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE recovery_token = %s AND status = 'abandoned' LIMIT 1",
                $token
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * Elenco carrelli abbandonati con email (per invio reminder).
     *
     * @return array<int, array<string, mixed>>
     */
    public function find_abandoned_for_reminder(int $older_than_hours, int $reminder_number, int $limit = 50): array {
        $reminder_sent = $reminder_number - 1;
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$older_than_hours} hours"));

        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table}
            WHERE status = 'abandoned'
            AND email != ''
            AND reminder_sent = %d
            AND updated_at < %s
            ORDER BY updated_at ASC
            LIMIT %d",
            $reminder_sent,
            $cutoff,
            $limit
        );

        $rows = $this->wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Elenco paginato per admin.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function get_paginated(int $page = 1, int $per_page = 20, string $status = ''): array {
        $offset = max(0, ($page - 1) * $per_page);
        $where = '1=1';
        $where_values = [];

        if ($status !== '' && in_array($status, ['abandoned', 'recovered'], true)) {
            $where .= ' AND status = %s';
            $where_values[] = $status;
        }

        $limit_values = array_merge($where_values, [$per_page, $offset]);
        $placeholders = array_merge(
            array_fill(0, count($where_values), '%s'),
            ['%d', '%d']
        );
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY updated_at DESC LIMIT " . (int) $per_page . ' OFFSET ' . (int) $offset;
        if (!empty($where_values)) {
            $sql = $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE {$where} ORDER BY updated_at DESC LIMIT %d OFFSET %d",
                ...$limit_values
            );
        }

        $items = $this->wpdb->get_results($sql, ARRAY_A);

        $count_sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where}";
        $total = empty($where_values)
            ? (int) $this->wpdb->get_var($count_sql)
            : (int) $this->wpdb->get_var($this->wpdb->prepare($count_sql, ...$where_values));

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
        ];
    }

    public function mark_recovered(int $id): bool {
        return (bool) $this->wpdb->update(
            $this->table,
            ['status' => 'recovered', 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function increment_reminder_sent(int $id): bool {
        return (bool) $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table} SET reminder_sent = reminder_sent + 1, updated_at = %s WHERE id = %d",
                current_time('mysql'),
                $id
            )
        );
    }

    public function capture_email_for_session(string $session_key, string $email): bool {
        if ($email === '') {
            return false;
        }
        return (bool) $this->wpdb->update(
            $this->table,
            ['email' => sanitize_email($email), 'updated_at' => current_time('mysql')],
            ['session_key' => $session_key, 'status' => 'abandoned'],
            ['%s', '%s'],
            ['%s', '%s']
        );
    }

    public function delete(int $id): bool {
        return (bool) $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    /**
     * Statistiche per dashboard.
     *
     * @return array{abandoned: int, recovered: int, recovered_value: float}
     */
    public function get_stats(): array {
        $abandoned = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'abandoned'");
        $recovered = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'recovered'");
        $recovered_value = (float) $this->wpdb->get_var("SELECT COALESCE(SUM(cart_total), 0) FROM {$this->table} WHERE status = 'recovered'");

        return [
            'abandoned'       => $abandoned,
            'recovered'       => $recovered,
            'recovered_value' => $recovered_value,
        ];
    }
}
