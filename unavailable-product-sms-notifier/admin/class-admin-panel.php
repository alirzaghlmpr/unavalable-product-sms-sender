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
            __( 'SMS Notify Requests', 'upsn' ),
            __( 'SMS Notify Requests', 'upsn' ),
            'manage_woocommerce',
            'upsn-requests',
            [ __CLASS__, 'render_page' ]
        );
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
            wp_die( esc_html__( 'Unauthorized', 'upsn' ) );
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
