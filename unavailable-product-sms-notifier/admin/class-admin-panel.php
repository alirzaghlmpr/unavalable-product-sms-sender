<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Admin_Panel {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_post_upsn_bulk',  [ __CLASS__, 'handle_bulk_action' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
    }

    public static function register_menu(): void {
        add_submenu_page(
            'woocommerce',
            __( 'درخواست‌های اطلاع‌رسانی', 'upsn' ),
            __( 'درخواست‌های اطلاع‌رسانی', 'upsn' ),
            'manage_woocommerce',
            'upsn-requests',
            [ __CLASS__, 'render_page' ]
        );
        add_submenu_page(
            'woocommerce',
            __( 'آمار اطلاع‌رسانی', 'upsn' ),
            __( 'آمار اطلاع‌رسانی', 'upsn' ),
            'manage_woocommerce',
            'upsn-stats',
            [ __CLASS__, 'render_stats_page' ]
        );
    }

    public static function enqueue_styles( string $hook ): void {
        if ( strpos( $hook, 'upsn-requests' ) === false && strpos( $hook, 'upsn-stats' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'upsn-admin',
            UPSN_URL . 'assets/css/admin.css',
            [],
            UPSN_VERSION
        );
    }

    // ── Requests page ──────────────────────────────────────────────────────────

    public static function render_page(): void {
        // Handle single-row delete
        if (
            isset( $_GET['upsn_action'] ) && $_GET['upsn_action'] === 'delete_single' &&
            isset( $_GET['id'] ) && isset( $_GET['_wpnonce'] )
        ) {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_die( esc_html__( 'دسترسی غیرمجاز', 'upsn' ) );
            }
            check_admin_referer( 'upsn_delete_' . absint( $_GET['id'] ) );
            UPSN_Database::delete_by_ids( [ absint( $_GET['id'] ) ] );
            wp_safe_redirect( remove_query_arg( [ 'upsn_action', 'id', '_wpnonce' ] ) );
            exit;
        }

        $status_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( $_GET['status_filter'] ) : '';
        $paged         = isset( $_GET['paged'] )         ? max( 1, absint( $_GET['paged'] ) )            : 1;
        $search_phone  = isset( $_GET['search_phone'] )  ? sanitize_text_field( $_GET['search_phone'] )  : '';
        $date_from     = isset( $_GET['date_from'] )     ? sanitize_text_field( $_GET['date_from'] )     : '';
        $date_to       = isset( $_GET['date_to'] )       ? sanitize_text_field( $_GET['date_to'] )       : '';
        $per_page      = 20;

        $filters = [
            'search_phone' => $search_phone,
            'date_from'    => $date_from,
            'date_to'      => $date_to,
        ];

        $rows  = UPSN_Database::get_all( array_merge( [ 'status' => $status_filter, 'per_page' => $per_page, 'paged' => $paged ], $filters ) );
        $total = UPSN_Database::count( $status_filter, $filters );
        $pages = (int) ceil( $total / $per_page );

        $counts = [
            'all'      => UPSN_Database::count(),
            'pending'  => UPSN_Database::count( 'pending' ),
            'notified' => UPSN_Database::count( 'notified' ),
            'failed'   => UPSN_Database::count( 'failed' ),
        ];

        require UPSN_PATH . 'admin/views/requests-table.php';
    }

    public static function handle_bulk_action(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیرمجاز', 'upsn' ) );
        }

        check_admin_referer( 'upsn_bulk_action' );

        $action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( $_POST['bulk_action'] ) : '';
        $ids    = isset( $_POST['request_ids'] ) ? array_map( 'absint', (array) $_POST['request_ids'] ) : [];

        if ( $action === 'resend' && ! empty( $ids ) ) {
            foreach ( $ids as $id ) {
                UPSN_Database::mark_resend_pending( $id );
            }
            $notice = 'bulk_done';
        } elseif ( $action === 'delete' && ! empty( $ids ) ) {
            UPSN_Database::delete_by_ids( $ids );
            $notice = 'bulk_deleted';
        } else {
            $notice = '';
        }

        $redirect = admin_url( 'admin.php?page=upsn-requests' );
        if ( $notice ) {
            $redirect = add_query_arg( 'upsn_notice', $notice, $redirect );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    // ── Statistics page ────────────────────────────────────────────────────────

    public static function render_stats_page(): void {
        $breakdown     = UPSN_Database::get_status_breakdown();
        $top_products  = UPSN_Database::get_top_products( 10 );
        $top_cats      = UPSN_Database::get_top_categories( 10 );
        $daily         = UPSN_Database::get_daily_requests( 30 );

        $totals = [ 'all' => 0, 'pending' => 0, 'notified' => 0, 'failed' => 0 ];
        foreach ( $breakdown as $row ) {
            $totals[ $row->status ] = (int) $row->cnt;
            $totals['all']         += (int) $row->cnt;
        }

        require UPSN_PATH . 'admin/views/statistics.php';
    }

    // ── Date helpers ───────────────────────────────────────────────────────────

    public static function jalali_date( string $datetime ): string {
        $ts = strtotime( $datetime );
        if ( ! $ts ) {
            return '—';
        }
        [ $gy, $gm, $gd ] = explode( '-', date( 'Y-m-d', $ts ) );
        [ $jy, $jm, $jd ] = self::gregorian_to_jalali( (int) $gy, (int) $gm, (int) $gd );
        $time = date( 'H:i', $ts );
        return sprintf( '%04d/%02d/%02d %s', $jy, $jm, $jd, $time );
    }

    private static function gregorian_to_jalali( int $gy, int $gm, int $gd ): array {
        $g_y    = $gy - 1600;
        $g_m    = $gm - 1;
        $g_d    = $gd - 1;
        $leap   = ( $gy % 4 === 0 && $gy % 100 !== 0 ) || $gy % 400 === 0 ? 1 : 0;
        $g_d_no = 365 * $g_y + (int) ( ( $g_y + 3 ) / 4 ) - (int) ( ( $g_y + 99 ) / 100 ) + (int) ( ( $g_y + 399 ) / 400 );
        $months = [ 31, 28 + $leap, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];
        for ( $i = 0; $i < $g_m; $i++ ) {
            $g_d_no += $months[ $i ];
        }
        $g_d_no += $g_d;
        $j_d_no  = $g_d_no - 79;
        $j_np    = (int) ( $j_d_no / 12053 );
        $j_d_no %= 12053;
        $jy      = 979 + 33 * $j_np + 4 * (int) ( $j_d_no / 1461 );
        $j_d_no %= 1461;
        if ( $j_d_no >= 366 ) {
            $jy     += (int) ( ( $j_d_no - 1 ) / 365 );
            $j_d_no  = ( $j_d_no - 1 ) % 365;
        }
        $j_mi = [ 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29 ];
        for ( $i = 0; $i < 11 && $j_d_no >= $j_mi[ $i ]; $i++ ) {
            $j_d_no -= $j_mi[ $i ];
        }
        return [ $jy, $i + 1, $j_d_no + 1 ];
    }
}
