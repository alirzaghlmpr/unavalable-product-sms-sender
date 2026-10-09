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

        $image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';

        self::render_button();
        self::render_modal( $product->get_name(), (string) $image );
    }

    /**
     * The button shown on the product page. Public so the settings page can preview it.
     */
    public static function render_button(): void {
        ?>
        <div class="upsn-notify-wrap">
            <button type="button" id="upsn-open-btn" class="upsn-notify-btn" aria-haspopup="dialog">
                <svg class="upsn-notify-btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                <span class="upsn-btn__label"><?php echo esc_html( UPSN_Settings::get( 'button_label' ) ); ?></span>
            </button>
        </div>
        <?php
    }

    /**
     * The popup. In preview mode it is rendered inline (visible, non-modal) for the settings page.
     */
    public static function render_modal( string $product_name = '', string $product_image = '', bool $preview = false ): void {
        $note = UPSN_Settings::get( 'privacy_note' );
        ?>
        <div id="upsn-modal" class="upsn-modal<?php echo $preview ? ' upsn-modal--static' : ''; ?>"
             dir="<?php echo esc_attr( UPSN_Settings::get( 'modal_text_dir', 'rtl' ) ); ?>"
             <?php echo $preview ? '' : 'role="dialog" aria-modal="true" aria-labelledby="upsn-modal-title" hidden'; ?>>
            <div class="upsn-modal__backdrop"></div>
            <div class="upsn-modal__box">
                <button type="button" class="upsn-modal__close" aria-label="<?php esc_attr_e( 'بستن', 'upsn' ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>

                <div class="upsn-modal__view upsn-modal__view--form">
                    <div class="upsn-modal__art" aria-hidden="true">
                        <img src="<?php echo esc_url( UPSN_URL . 'assets/img/icon-128.png' ); ?>" alt="" width="80" height="80" />
                    </div>

                    <?php if ( $product_name !== '' ) : ?>
                        <div class="upsn-modal__product">
                            <?php if ( $product_image ) : ?>
                                <img src="<?php echo esc_url( $product_image ); ?>" alt="" width="44" height="44" />
                            <?php endif; ?>
                            <span class="upsn-modal__product-name"><?php echo esc_html( $product_name ); ?></span>
                            <span class="upsn-modal__tag"><?php esc_html_e( 'ناموجود', 'upsn' ); ?></span>
                        </div>
                    <?php endif; ?>

                    <h2 id="upsn-modal-title"><?php echo esc_html( UPSN_Settings::get( 'modal_title' ) ); ?></h2>
                    <p class="upsn-modal__subtitle"><?php echo esc_html( UPSN_Settings::get( 'modal_subtitle' ) ); ?></p>

                    <form id="upsn-form" novalidate>
                        <label for="upsn-phone" class="upsn-label"><?php echo esc_html( UPSN_Settings::get( 'phone_label' ) ); ?></label>
                        <div class="upsn-input">
                            <svg class="upsn-input__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg>
                            <input
                                type="tel"
                                id="upsn-phone"
                                name="phone"
                                placeholder="09123456789"
                                maxlength="20"
                                inputmode="numeric"
                                autocomplete="tel"
                                aria-describedby="upsn-phone-error"
                                required
                            />
                            <svg class="upsn-input__ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <span id="upsn-phone-error" class="upsn-error" aria-live="polite"></span>

                        <button type="submit" id="upsn-submit-btn">
                            <span class="upsn-btn__label"><?php echo esc_html( UPSN_Settings::get( 'submit_label' ) ); ?></span>
                            <span class="upsn-spinner" aria-hidden="true"></span>
                        </button>
                        <div id="upsn-form-message" class="upsn-form-message" aria-live="polite"></div>

                        <?php if ( $note !== '' || $preview ) : ?>
                            <p class="upsn-note"<?php echo $note === '' ? ' hidden' : ''; ?>>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                <span><?php echo esc_html( $note ); ?></span>
                            </p>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="upsn-modal__view upsn-modal__view--success" role="status">
                    <div class="upsn-success-state__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <p class="upsn-success-state__text"><?php echo esc_html( UPSN_Settings::get( 'success_message' ) ); ?></p>
                    <button type="button" class="upsn-success-state__done"><?php esc_html_e( 'بستن', 'upsn' ); ?></button>
                </div>
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
