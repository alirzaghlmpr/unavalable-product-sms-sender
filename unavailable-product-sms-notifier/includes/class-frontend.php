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
                'invalidPhone'  => __( 'Please enter a valid phone number.', 'upsn' ),
                'alreadyDone'   => __( 'You have already registered for this product.', 'upsn' ),
                'success'       => UPSN_Settings::get( 'success_message' ),
                'error'         => __( 'Something went wrong. Please try again.', 'upsn' ),
                'sending'       => __( 'Please wait…', 'upsn' ),
            ],
        ] );
    }

    public static function render_notify_button(): void {
        global $product;
        if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
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
                    <label for="upsn-phone"><?php esc_html_e( 'Phone Number', 'upsn' ); ?></label>
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
                        <?php esc_html_e( 'Notify Me', 'upsn' ); ?>
                    </button>
                    <div id="upsn-form-message" class="upsn-form-message" aria-live="polite"></div>
                </form>
            </div>
        </div>
        <?php
    }
}
