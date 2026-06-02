<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Admin_Panel {

    public static function init(): void {
        add_action( 'admin_menu',               [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_post_upsn_bulk',     [ __CLASS__, 'handle_bulk_action' ] );
        add_action( 'admin_enqueue_scripts',    [ __CLASS__, 'enqueue_styles' ] );
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
    }

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
        $g_d_no = 365 * $gy + (int) ( ( $gy + 3 ) / 4 ) - (int) ( ( $gy + 99 ) / 100 ) + (int) ( ( $gy + 399 ) / 400 );
        for ( $i = 0; $i < $gm - 1; $i++ ) {
            $g_d_no += [ 31, 28 + ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || $gy % 400 === 0 ? 1 : 0 ), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ][ $i ];
        }
        $g_d_no += $gd - 1;
        $j_d_no = $g_d_no - 79;
        $j_np   = (int) ( $j_d_no / 12053 );
        $j_d_no %= 12053;
        $jy     = 979 + 33 * $j_np + 4 * (int) ( $j_d_no / 1461 );
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

    public static function enqueue_styles( string $hook ): void {
        if ( strpos( $hook, 'upsn-requests' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'upsn-admin',
            UPSN_URL . 'assets/css/admin.css',
            [],
            UPSN_VERSION
        );
    }

    public static function render_page(): void {
        $status_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( $_GET['status_filter'] ) : '';
        $paged         = isset( $_GET['paged'] )         ? max( 1, absint( $_GET['paged'] ) )            : 1;
        $per_page      = 20;

        $rows  = UPSN_Database::get_all( [ 'status' => $status_filter, 'per_page' => $per_page, 'paged' => $paged ] );
        $total = UPSN_Database::count( $status_filter );
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
        }

        wp_safe_redirect( admin_url( 'admin.php?page=upsn-requests&upsn_notice=bulk_done' ) );
        exit;
    }
}
