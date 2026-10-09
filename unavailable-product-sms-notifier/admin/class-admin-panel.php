<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Admin_Panel {

    /** Admin pages that share the plugin header, tabs and stylesheet. */
    const PAGES = [ 'upsn-requests', 'upsn-stats', 'upsn-settings' ];

    public static function init(): void {
        add_action( 'admin_menu',                [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_post_upsn_bulk',      [ __CLASS__, 'handle_bulk_action' ] );
        add_action( 'admin_post_upsn_requeue',   [ __CLASS__, 'handle_requeue_single' ] );
        add_action( 'admin_enqueue_scripts',     [ __CLASS__, 'enqueue_assets' ] );
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

    public static function enqueue_assets( string $hook ): void {
        $is_ours = false;
        foreach ( self::PAGES as $slug ) {
            if ( strpos( $hook, $slug ) !== false ) {
                $is_ours = true;
                break;
            }
        }
        if ( ! $is_ours ) {
            return;
        }

        $deps = [];

        // The settings page previews the real storefront button and popup,
        // so it needs the storefront stylesheet too.
        if ( strpos( $hook, 'upsn-settings' ) !== false ) {
            wp_enqueue_style( 'upsn-popup', UPSN_URL . 'assets/css/popup.css', [], UPSN_VERSION );
            $deps[] = 'upsn-popup';
        }

        wp_enqueue_style( 'upsn-admin', UPSN_URL . 'assets/css/admin.css', $deps, UPSN_VERSION );
        wp_enqueue_script( 'upsn-admin', UPSN_URL . 'assets/js/admin.js', [ 'jquery' ], UPSN_VERSION, true );

        wp_localize_script( 'upsn-admin', 'upsnAdmin', [
            'i18n' => [
                'selected' => __( '%d مورد انتخاب شده', 'upsn' ),
                'unsaved'  => __( 'تغییرات ذخیره نشده‌اند', 'upsn' ),
            ],
        ] );
    }

    // ── Shared page chrome ─────────────────────────────────────────────────────

    /**
     * Static inline SVG icons (stroke style, inherit currentColor).
     */
    public static function icon( string $name, int $size = 18 ): string {
        static $paths = [
            'bell'     => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
            'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
            'chart'    => '<path d="M18 20V10M12 20V4M6 20v-6"/>',
            'settings' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
            'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
            'refresh'  => '<path d="M21 12a9 9 0 0 1-15.5 6.3L3 16"/><path d="M3 21v-5h5"/><path d="M3 12A9 9 0 0 1 18.5 5.7L21 8"/><path d="M21 3v5h-5"/>',
            'inbox'    => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
            'info'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
            'check'    => '<path d="M20 6 9 17l-5-5"/>',
            'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
            'phone'    => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/>',
        ];

        return sprintf(
            '<svg class="upsn-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
            $size,
            $paths[ $name ] ?? ''
        );
    }

    /**
     * Branded header with tabs that link the three plugin pages together.
     */
    public static function render_header( string $active, string $subtitle ): void {
        $tabs = [
            'upsn-requests' => [ __( 'درخواست‌ها', 'upsn' ), 'list' ],
            'upsn-stats'    => [ __( 'آمار', 'upsn' ),       'chart' ],
            'upsn-settings' => [ __( 'تنظیمات', 'upsn' ),    'settings' ],
        ];
        ?>
        <header class="upsn-hero">
            <div class="upsn-hero__brand">
                <span class="upsn-mark">
                    <img src="<?php echo esc_url( UPSN_URL . 'assets/img/icon-128.png' ); ?>" alt="" width="60" height="60" />
                </span>
                <div>
                    <h1><?php esc_html_e( 'اطلاع‌رسانی موجود شدن', 'upsn' ); ?></h1>
                    <p><?php echo esc_html( $subtitle ); ?></p>
                </div>
            </div>
            <nav class="upsn-tabs" aria-label="<?php esc_attr_e( 'بخش‌های افزونه', 'upsn' ); ?>">
                <?php foreach ( $tabs as $slug => [ $label, $icon ] ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"
                       class="upsn-tab<?php echo $slug === $active ? ' is-active' : ''; ?>"
                       <?php echo $slug === $active ? 'aria-current="page"' : ''; ?>>
                        <?php echo self::icon( $icon, 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </header>
        <hr class="wp-header-end" />
        <?php
    }

    // ── Requests page ──────────────────────────────────────────────────────────

    public static function render_page(): void {
        $status_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['status_filter'] ) ) : '';
        $paged         = isset( $_GET['paged'] )         ? max( 1, absint( $_GET['paged'] ) )                          : 1;
        $search_phone  = isset( $_GET['search_phone'] )  ? sanitize_text_field( wp_unslash( $_GET['search_phone'] ) )  : '';
        $date_from     = isset( $_GET['date_from'] )     ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) )     : '';
        $date_to       = isset( $_GET['date_to'] )       ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) )       : '';
        $per_page      = 10;

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

    /**
     * Queue fresh pending requests for the selected rows.
     * Nothing is deleted or overwritten; the selected rows keep their history.
     */
    public static function handle_bulk_action(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیرمجاز', 'upsn' ) );
        }

        check_admin_referer( 'upsn_bulk_action' );

        $ids    = isset( $_POST['request_ids'] ) ? array_map( 'absint', (array) $_POST['request_ids'] ) : [];
        $queued = 0;
        foreach ( array_unique( $ids ) as $id ) {
            if ( UPSN_Database::requeue( $id ) ) {
                $queued++;
            }
        }

        self::redirect_back( $ids ? ( $queued ? 'requeued' : 'already_waiting' ) : '', $queued );
    }

    public static function handle_requeue_single(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیرمجاز', 'upsn' ) );
        }

        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        check_admin_referer( 'upsn_requeue_' . $id );

        $queued = UPSN_Database::requeue( $id ) ? 1 : 0;

        self::redirect_back( $queued ? 'requeued' : 'already_waiting', $queued );
    }

    private static function redirect_back( string $notice, int $count ): void {
        $back = wp_get_referer() ?: admin_url( 'admin.php?page=upsn-requests' );
        $back = remove_query_arg( [ 'upsn_notice', 'upsn_n' ], $back );

        if ( $notice ) {
            $back = add_query_arg( [ 'upsn_notice' => $notice, 'upsn_n' => $count ], $back );
        }

        wp_safe_redirect( $back );
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

        // Fill days without requests so the chart shows a true 30-day timeline.
        $by_day = [];
        foreach ( $daily as $d ) {
            $by_day[ $d->day ] = (int) $d->total;
        }
        $series = [];
        $now    = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
        for ( $i = 29; $i >= 0; $i-- ) {
            $day      = gmdate( 'Y-m-d', $now - $i * DAY_IN_SECONDS );
            $jalali   = self::jalali_date( $day . ' 00:00:00' );
            $series[] = [
                'day'   => $day,
                'total' => $by_day[ $day ] ?? 0,
                'label' => $jalali !== '—' ? substr( $jalali, 5, 5 ) : $day,
            ];
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
