<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Database {

    public static function create_table(): void {
        global $wpdb;

        $table   = $wpdb->prefix . UPSN_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id  BIGINT(20) UNSIGNED NOT NULL,
            phone       VARCHAR(20)         NOT NULL,
            status      VARCHAR(20)         NOT NULL DEFAULT 'pending',
            requested_at DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
            notified_at  DATETIME                    DEFAULT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY status (status)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'upsn_db_version', UPSN_VERSION );
    }

    /** @return array<object> */
    public static function get_pending_for_product( int $product_id ): array {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE product_id = %d AND status = 'pending'",
                $product_id
            )
        );
    }

    public static function exists( int $product_id, string $phone ): bool {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND phone = %s AND status = 'pending'",
                $product_id,
                $phone
            )
        );

        return (int) $count > 0;
    }

    public static function insert( int $product_id, string $phone ): bool {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        $result = $wpdb->insert(
            $table,
            [
                'product_id'   => $product_id,
                'phone'        => $phone,
                'status'       => 'pending',
                'requested_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s' ]
        );

        return $result !== false;
    }

    public static function mark_notified( int $id ): void {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        $wpdb->update(
            $table,
            [
                'status'      => 'notified',
                'notified_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
    }

    public static function mark_failed( int $id ): void {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        $wpdb->update(
            $table,
            [ 'status' => 'failed' ],
            [ 'id'     => $id ],
            [ '%s' ],
            [ '%d' ]
        );
    }

    public static function mark_resend_pending( int $id ): void {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        $wpdb->update(
            $table,
            [ 'status' => 'pending', 'notified_at' => null ],
            [ 'id'     => $id ],
            [ '%s', null ],
            [ '%d' ]
        );
    }

    public static function delete_by_ids( array $ids ): int {
        if ( empty( $ids ) ) {
            return 0;
        }
        global $wpdb;
        $table        = $wpdb->prefix . UPSN_TABLE;
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids ) );
    }

    /** @return array<object> */
    public static function get_all( array $args = [] ): array {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        [ $where, $values ] = self::build_where( $args );

        $per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
        $page     = isset( $args['paged'] )    ? max( 1, (int) $args['paged'] ) : 1;
        $offset   = ( $page - 1 ) * $per_page;

        $sql      = "SELECT * FROM {$table} {$where} ORDER BY requested_at DESC LIMIT %d OFFSET %d";
        $values[] = $per_page;
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$values ) );
    }

    public static function count( string $status = '', array $filters = [] ): int {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        $args = $filters;
        if ( $status ) {
            $args['status'] = $status;
        }
        [ $where, $values ] = self::build_where( $args );

        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        return $values
            ? (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$values ) )
            : (int) $wpdb->get_var( $sql );
    }

    // ── Statistics ─────────────────────────────────────────────────────────────

    /** @return array<object{product_id,total,pending,notified,failed}> */
    public static function get_top_products( int $limit = 10 ): array {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT product_id,
                    COUNT(*)                            AS total,
                    SUM(status = 'pending')             AS pending,
                    SUM(status = 'notified')            AS notified,
                    SUM(status = 'failed')              AS failed
             FROM {$table}
             GROUP BY product_id
             ORDER BY total DESC
             LIMIT %d",
            $limit
        ) );
    }

    /** @return array<object{term_id,name,total}> */
    public static function get_top_categories( int $limit = 10 ): array {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT t.term_id, t.name, COUNT(r.id) AS total
             FROM {$table} r
             JOIN {$wpdb->term_relationships} tr  ON tr.object_id          = r.product_id
             JOIN {$wpdb->term_taxonomy}      tt  ON tt.term_taxonomy_id   = tr.term_taxonomy_id
                                                 AND tt.taxonomy           = 'product_cat'
             JOIN {$wpdb->terms}              t   ON t.term_id             = tt.term_id
             GROUP BY t.term_id
             ORDER BY total DESC
             LIMIT %d",
            $limit
        ) );
    }

    /** @return array<object{day,total}> */
    public static function get_daily_requests( int $days = 30 ): array {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(requested_at) AS day, COUNT(*) AS total
             FROM {$table}
             WHERE requested_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(requested_at)
             ORDER BY day ASC",
            $days
        ) );
    }

    /** @return array<object{status,cnt}> */
    public static function get_status_breakdown(): array {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;
        return $wpdb->get_results( "SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status" );
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** @return array{0: string, 1: array} */
    private static function build_where( array $args ): array {
        $clauses = [];
        $values  = [];

        if ( ! empty( $args['status'] ) ) {
            $clauses[] = 'status = %s';
            $values[]  = $args['status'];
        }
        if ( ! empty( $args['search_phone'] ) ) {
            $clauses[] = 'phone LIKE %s';
            $values[]  = '%' . $wpdb->esc_like( $args['search_phone'] ) . '%';
        }
        if ( ! empty( $args['date_from'] ) ) {
            $clauses[] = 'DATE(requested_at) >= %s';
            $values[]  = $args['date_from'];
        }
        if ( ! empty( $args['date_to'] ) ) {
            $clauses[] = 'DATE(requested_at) <= %s';
            $values[]  = $args['date_to'];
        }

        $where = $clauses ? 'WHERE ' . implode( ' AND ', $clauses ) : '';
        return [ $where, $values ];
    }
}
