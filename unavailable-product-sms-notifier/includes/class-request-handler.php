<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Request_Handler {

    public static function init(): void {
        add_action( 'wp_ajax_upsn_save_request',        [ __CLASS__, 'handle' ] );
        add_action( 'wp_ajax_nopriv_upsn_save_request', [ __CLASS__, 'handle' ] );
    }

    public static function handle(): void {
        check_ajax_referer( 'upsn_notify', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $phone_raw  = isset( $_POST['phone'] )      ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

        if ( ! $product_id || ! self::is_valid_phone( $phone_raw ) ) {
            wp_send_json_error( [ 'code' => 'invalid_input' ], 422 );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product || $product->is_in_stock() ) {
            wp_send_json_error( [ 'code' => 'product_unavailable' ], 400 );
        }

        // ── Anti-spam: IP check ────────────────────────────────────────────
        if ( self::is_ip_over_limit() ) {
            wp_send_json_error( [ 'code' => 'ip_limit' ], 429 );
        }

        $phone = self::normalize_phone( $phone_raw );

        // ── Anti-spam: phone check ─────────────────────────────────────────
        if ( self::is_phone_over_limit( $phone ) ) {
            wp_send_json_error( [ 'code' => 'phone_limit' ], 429 );
        }

        // ── Duplicate check ────────────────────────────────────────────────
        if ( UPSN_Database::exists( $product_id, $phone ) ) {
            wp_send_json_error( [ 'code' => 'already_registered' ], 409 );
        }

        if ( ! UPSN_Database::insert( $product_id, $phone ) ) {
            wp_send_json_error( [ 'code' => 'db_error' ], 500 );
        }

        // Increment counters only after a successful insert
        self::increment_ip_counter();
        self::increment_phone_counter( $phone );

        wp_send_json_success( [ 'code' => 'registered' ] );
    }

    // ── Phone validation ───────────────────────────────────────────────────────
    private static function is_valid_phone( string $phone ): bool {
        return (bool) preg_match( '/^(\+98|0098|0)?9[0-9]{9}$/', $phone );
    }

    private static function normalize_phone( string $phone ): string {
        $phone = preg_replace( '/^\+98|^0098/', '0', $phone );
        if ( str_starts_with( $phone, '9' ) ) {
            $phone = '0' . $phone;
        }
        return $phone;
    }

    // ── Rate limiting ──────────────────────────────────────────────────────────

    private static function get_client_ip(): string {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ( $candidates as $key ) {
            if ( empty( $_SERVER[ $key ] ) ) {
                continue;
            }
            // X-Forwarded-For can be a comma-separated list; take the first
            $ip = trim( explode( ',', $_SERVER[ $key ] )[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                return $ip;
            }
        }

        // Fallback: accept any valid IP including private (dev environments)
        foreach ( $candidates as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', $_SERVER[ $key ] )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    private static function ip_transient_key(): string {
        return 'upsn_rl_ip_' . md5( self::get_client_ip() );
    }

    private static function phone_transient_key( string $phone ): string {
        return 'upsn_rl_ph_' . md5( $phone );
    }

    private static function is_ip_over_limit(): bool {
        $limit = (int) UPSN_Settings::get( 'spam_ip_limit', 5 );
        if ( $limit <= 0 ) {
            return false;
        }
        return (int) get_transient( self::ip_transient_key() ) >= $limit;
    }

    private static function is_phone_over_limit( string $phone ): bool {
        $limit = (int) UPSN_Settings::get( 'spam_phone_limit', 3 );
        if ( $limit <= 0 ) {
            return false;
        }
        return (int) get_transient( self::phone_transient_key( $phone ) ) >= $limit;
    }

    private static function increment_ip_counter(): void {
        $limit = (int) UPSN_Settings::get( 'spam_ip_limit', 5 );
        if ( $limit <= 0 ) {
            return;
        }
        self::increment_counter( self::ip_transient_key(), HOUR_IN_SECONDS );
    }

    private static function increment_phone_counter( string $phone ): void {
        $limit = (int) UPSN_Settings::get( 'spam_phone_limit', 3 );
        if ( $limit <= 0 ) {
            return;
        }
        self::increment_counter( self::phone_transient_key( $phone ), DAY_IN_SECONDS );
    }

    /**
     * Fixed-window counter built on the transient API so it works regardless of
     * the backend (DB or a persistent object cache such as Redis/Memcached).
     *
     * The window TTL is anchored on first hit via a sibling "_window" transient,
     * so subsequent increments don't slide the window forward.
     */
    private static function increment_counter( string $key, int $window ): void {
        $window_key = $key . '_window';
        $expires    = (int) get_transient( $window_key );

        if ( $expires <= 0 ) {
            // First hit in this window: anchor the expiry timestamp.
            $expires = time() + $window;
            set_transient( $window_key, $expires, $window );
            set_transient( $key, 1, $window );
            return;
        }

        // Subsequent hits: bump the count but keep the remaining window length.
        $remaining = max( 1, $expires - time() );
        $count     = (int) get_transient( $key );
        set_transient( $key, $count + 1, $remaining );
    }
}
