<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Stock_Watcher {

    // Prevent double-dispatch when both status + quantity hooks fire for the same product
    private static array $dispatched = [];

    public static function init(): void {
        // Fires after WC updates a simple product's stock status
        add_action( 'woocommerce_product_set_stock_status',   [ __CLASS__, 'on_stock_status_change' ], 10, 3 );
        // Fires after WC updates a product variation's stock status
        add_action( 'woocommerce_variation_set_stock_status', [ __CLASS__, 'on_stock_status_change' ], 10, 3 );
        // Fires when stock quantity changes (covers manual quantity edits in admin)
        add_action( 'woocommerce_product_set_stock',          [ __CLASS__, 'on_stock_quantity_change' ] );
        // Action Scheduler handler (runs in background via WP-Cron)
        add_action( 'upsn_send_notifications',                [ __CLASS__, 'handle_scheduled_notifications' ] );
    }

    public static function on_stock_status_change( int $product_id, string $stock_status, WC_Product $product ): void {
        if ( $stock_status !== 'instock' ) {
            return;
        }
        $notify_id = $product instanceof WC_Product_Variation
            ? $product->get_parent_id()
            : $product_id;
        self::schedule_or_dispatch( $notify_id );
    }

    public static function on_stock_quantity_change( WC_Product $product ): void {
        if ( ! $product->is_in_stock() ) {
            return;
        }
        $notify_id = $product instanceof WC_Product_Variation
            ? $product->get_parent_id()
            : $product->get_id();
        self::schedule_or_dispatch( $notify_id );
    }

    public static function handle_scheduled_notifications( int $product_id ): void {
        self::dispatch_notifications( $product_id );
    }

    private static function schedule_or_dispatch( int $product_id ): void {
        // Deduplicate within the same request
        if ( in_array( $product_id, self::$dispatched, true ) ) {
            return;
        }
        self::$dispatched[] = $product_id;

        if ( function_exists( 'as_schedule_single_action' ) ) {
            // Skip if already queued and not yet processed
            if ( ! as_has_scheduled_action( 'upsn_send_notifications', [ 'product_id' => $product_id ], 'upsn' ) ) {
                as_schedule_single_action(
                    time() + 5,
                    'upsn_send_notifications',
                    [ 'product_id' => $product_id ],
                    'upsn'
                );
                upsn_log( "[UPSN] Notifications scheduled via Action Scheduler for product #{$product_id}." );
            }
        } else {
            // Fallback: send synchronously (less reliable on shared hosting)
            upsn_log( '[UPSN] Action Scheduler not available — sending synchronously.' );
            self::dispatch_notifications( $product_id );
        }
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
