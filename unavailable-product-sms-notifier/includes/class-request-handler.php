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

        $phone = self::normalize_phone( $phone_raw );

        if ( UPSN_Database::exists( $product_id, $phone ) ) {
            wp_send_json_error( [ 'code' => 'already_registered' ], 409 );
        }

        if ( ! UPSN_Database::insert( $product_id, $phone ) ) {
            wp_send_json_error( [ 'code' => 'db_error' ], 500 );
        }

        wp_send_json_success( [ 'code' => 'registered' ] );
    }

    /**
     * Phase 1: validates Iranian mobile numbers (09xxxxxxxxx or +989xxxxxxxxx).
     * Phase 2: swap regex for the pattern from the SMS provider docs.
     */
    private static function is_valid_phone( string $phone ): bool {
        // Accepts: 09xxxxxxxxx | 9xxxxxxxxx | +989xxxxxxxxx | 00989xxxxxxxxx
        return (bool) preg_match( '/^(\+98|0098|0)?9[0-9]{9}$/', $phone );
    }

    /** Normalize to 09xxxxxxxxx local format for storage */
    private static function normalize_phone( string $phone ): string {
        $phone = preg_replace( '/^\+98|^0098/', '0', $phone );
        if ( str_starts_with( $phone, '9' ) ) {
            $phone = '0' . $phone;
        }
        return $phone;
    }
}
