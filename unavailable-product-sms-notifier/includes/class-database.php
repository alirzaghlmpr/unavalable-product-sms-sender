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

    /** @return array<object> */
    public static function get_all( array $args = [] ): array {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        $where  = '';
        $values = [];

        if ( ! empty( $args['status'] ) ) {
            $where    = 'WHERE status = %s';
            $values[] = $args['status'];
        }

        $per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
        $page     = isset( $args['paged'] )    ? max( 1, (int) $args['paged'] ) : 1;
        $offset   = ( $page - 1 ) * $per_page;

        $order_by = 'requested_at';
        $order    = 'DESC';

        $sql = "SELECT * FROM {$table} {$where} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";

        $values[] = $per_page;
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$values ) );
    }

    public static function count( string $status = '' ): int {
        global $wpdb;
        $table = $wpdb->prefix . UPSN_TABLE;

        if ( $status ) {
            return (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status )
            );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
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
}
