<?php
defined( 'ABSPATH' ) || exit;

/**
 * SMS Sender — Phase 1 stub.
 * Phase 2: replace send() internals with real provider HTTP call.
 */
class UPSN_SMS_Sender {

    public static function send( string $phone, int $product_id ): bool {
        $product = wc_get_product( $product_id );
        $name    = $product ? $product->get_name() : "#{$product_id}";

        // Phase 1: log only, no real SMS sent
        error_log( sprintf(
            '[UPSN] SMS stub — to: %s | product: %s (ID %d) | time: %s',
            $phone,
            $name,
            $product_id,
            current_time( 'mysql' )
        ) );

        // Phase 2: replace the block below with real HTTP request
        // $response = wp_remote_post( SMS_API_ENDPOINT, [ ... ] );
        // return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;

        return true;
    }
}
