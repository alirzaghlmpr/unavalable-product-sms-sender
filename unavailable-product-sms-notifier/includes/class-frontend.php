<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Frontend {

    public static function init(): void {
        add_action( 'wp_enqueue_scripts',            [ __CLASS__, 'maybe_enqueue_assets' ] );
        add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'render_notify_button' ], 6 );
    }

    public static function maybe_enqueue_assets(): void {
        if ( ! is_product() ) {
            return;
        }

        // global $product is not populated yet at wp_enqueue_scripts time;
        // fetch via the queried object ID instead.
        $product = wc_get_product( get_queried_object_id() );
        if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
            return;
        }

        if ( ! self::is_visible_for_product( $product ) ) {
            return;
        }

        wp_enqueue_style(
            'upsn-popup',
            UPSN_URL . 'assets/css/popup.css',
            [],
            UPSN_VERSION
        );

        wp_add_inline_style( 'upsn-popup', UPSN_Settings::inline_css() );

        wp_enqueue_script(
            'upsn-popup',
            UPSN_URL . 'assets/js/popup.js',
            [ 'jquery' ],
            UPSN_VERSION,
            true
        );

        wp_localize_script( 'upsn-popup', 'upsnData', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'upsn_notify' ),
            'productId'  => get_the_ID(),
            'i18n'       => [
                'invalidPhone'  => UPSN_Settings::get( 'invalid_phone_error' ),
                'alreadyDone'   => UPSN_Settings::get( 'already_registered_error' ),
                'ipLimit'       => UPSN_Settings::get( 'ip_limit_error' ),
                'phoneLimit'    => UPSN_Settings::get( 'phone_limit_error' ),
                'success'       => UPSN_Settings::get( 'success_message' ),
                'error'         => UPSN_Settings::get( 'generic_error' ),
                'sending'       => UPSN_Settings::get( 'sending_label' ),
            ],
        ] );
    }

    public static function render_notify_button(): void {
        global $product;
        if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
            return;
        }

        if ( ! self::is_visible_for_product( $product ) ) {
            return;
        }
        ?>
        <div class="upsn-notify-wrap">
            <button type="button" id="upsn-open-btn" class="upsn-notify-btn">
                <?php echo esc_html( UPSN_Settings::get( 'button_label' ) ); ?>
            </button>
        </div>

        <div id="upsn-modal" class="upsn-modal" role="dialog" aria-modal="true" aria-labelledby="upsn-modal-title" hidden>
            <div class="upsn-modal__backdrop"></div>
            <div class="upsn-modal__box">
                <button type="button" class="upsn-modal__close" aria-label="<?php esc_attr_e( 'Close', 'upsn' ); ?>">&times;</button>
                <h2 id="upsn-modal-title"><?php echo esc_html( UPSN_Settings::get( 'modal_title' ) ); ?></h2>
                <p><?php echo esc_html( UPSN_Settings::get( 'modal_subtitle' ) ); ?></p>
                <form id="upsn-form" novalidate>
                    <label for="upsn-phone"><?php echo esc_html( UPSN_Settings::get( 'phone_label' ) ); ?></label>
                    <input
                        type="tel"
                        id="upsn-phone"
                        name="phone"
                        placeholder="09xxxxxxxxx"
                        maxlength="20"
                        autocomplete="tel"
                        required
                    />
                    <span id="upsn-phone-error" class="upsn-error" aria-live="polite"></span>
                    <button type="submit" id="upsn-submit-btn">
                        <?php echo esc_html( UPSN_Settings::get( 'submit_label' ) ); ?>
                    </button>
                    <div id="upsn-form-message" class="upsn-form-message" aria-live="polite"></div>
                </form>
            </div>
        </div>
        <?php
    }

    private static function is_visible_for_product( WC_Product $product ): bool {
        $mode = UPSN_Settings::get( 'button_visibility', 'all' );

        if ( $mode === 'all' ) {
            return true;
        }

        $product_id = $product instanceof WC_Product_Variation
            ? $product->get_parent_id()
            : $product->get_id();

        if ( $mode === 'products' ) {
            $ids = array_filter( array_map( 'absint', explode( ',', UPSN_Settings::get( 'button_products', '' ) ) ) );
            return in_array( $product_id, $ids, true );
        }

        if ( $mode === 'categories' ) {
            $cat_ids = array_filter( array_map( 'absint', explode( ',', UPSN_Settings::get( 'button_categories', '' ) ) ) );
            if ( empty( $cat_ids ) ) {
                return false;
            }
            $product_cats = wc_get_product_term_ids( $product_id, 'product_cat' );
            return ! empty( array_intersect( $cat_ids, $product_cats ) );
        }

        return true;
    }
}
