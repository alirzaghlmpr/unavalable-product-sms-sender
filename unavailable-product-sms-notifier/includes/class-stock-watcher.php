<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Stock_Watcher {

    public static function init(): void {
        // Fires after WC updates a simple product's stock status
        add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'on_stock_status_change' ], 10, 3 );

        // Fires after WC updates a product variation's stock status
        add_action( 'woocommerce_variation_set_stock_status', [ __CLASS__, 'on_stock_status_change' ], 10, 3 );
    }

    /**
     * @param int    $product_id
     * @param string $stock_status  New status: 'instock' | 'outofstock' | 'onbackorder'
     * @param WC_Product $product
     */
    public static function on_stock_status_change( int $product_id, string $stock_status, WC_Product $product ): void {
        if ( $stock_status !== 'instock' ) {
            return;
        }

        // For a variation, notify on the parent product ID so requests match
        $notify_id = $product instanceof WC_Product_Variation
            ? $product->get_parent_id()
            : $product_id;

        self::dispatch_notifications( $notify_id );
    }

    private static function dispatch_notifications( int $product_id ): void {
        $requests = UPSN_Database::get_pending_for_product( $product_id );

        if ( empty( $requests ) ) {
            return;
        }

        foreach ( $requests as $row ) {
            $sent = UPSN_SMS_Sender::send( $row->phone, $product_id );

            if ( $sent ) {
                UPSN_Database::mark_notified( (int) $row->id );
            } else {
                UPSN_Database::mark_failed( (int) $row->id );
            }
        }
    }
}
