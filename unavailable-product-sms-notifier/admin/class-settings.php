<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Settings {

    const OPTION_KEY = 'upsn_settings';

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init',            [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
    }

    public static function enqueue_scripts( string $hook ): void {
        if ( strpos( $hook, 'upsn-settings' ) === false ) {
            return;
        }
        wp_enqueue_script( 'wc-enhanced-select' );
        wp_enqueue_style( 'woocommerce_admin_styles' );
    }

    // ── Defaults ──────────────────────────────────────────────────────────────
    public static function defaults(): array {
        return [
            // Visibility
            'button_visibility'  => 'all',
            'button_categories'  => '',
            'button_products'    => '',
            // Button
            'button_label'      => 'اطلاع‌رسانی موجود شدن',
            'button_bg'         => '#2748e8',
            'button_color'      => '#ffffff',
            'button_radius'     => '12',
            // Modal content
            'modal_title'       => 'اطلاع‌رسانی موجود شدن محصول',
            'modal_subtitle'    => 'شماره موبایل خود را وارد کنید تا به محض موجود شدن این محصول از طریق پیامک به شما اطلاع دهیم.',
            'modal_text_dir'    => 'rtl',
            'phone_label'       => 'شماره موبایل',
            'privacy_note'      => 'شماره شما فقط برای ارسال همین پیامک استفاده می‌شود.',
            'sending_label'     => 'لطفاً صبر کنید...',
            'submit_label'      => 'ثبت درخواست',
            // Modal style
            'overlay_opacity'   => '55',
            'modal_bg'          => '#ffffff',
            'modal_radius'      => '20',
            // Form
            'input_border'      => '#d9dce8',
            'input_focus'       => '#2748e8',
            'submit_bg'         => '#2748e8',
            'submit_color'      => '#ffffff',
            'submit_radius'     => '12',
            // Messages
            'success_message'       => 'درخواست شما با موفقیت ثبت شد. به محض موجود شدن محصول از طریق پیامک به شما اطلاع می‌دهیم.',
            'success_color'         => '#0b7a53',
            'error_color'           => '#c62a3b',
            'invalid_phone_error'   => 'لطفاً یک شماره موبایل معتبر وارد کنید.',
            'already_registered_error' => 'این شماره موبایل قبلاً برای این محصول ثبت شده است.',
            'ip_limit_error'        => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً بعداً دوباره امتحان کنید.',
            'phone_limit_error'     => 'این شماره موبایل به حداکثر تعداد درخواست‌های مجاز امروز رسیده است.',
            'generic_error'         => 'خطایی رخ داد. لطفاً دوباره تلاش کنید.',
            // Anti-spam
            'spam_ip_limit'     => '5',
            'spam_phone_limit'  => '3',
            // SMS Provider
            'sms_gateway'    => 'smsir',
            'sms_api_key'    => '',
            'sms_username'   => '',
            'sms_password'   => '',
            'sms_line_number'=> '',
            'sms_pattern'    => '',
            'sms_param_name' => 'product',
        ];
    }

    public static function get( string $key, $fallback = null ) {
        $saved = get_option( self::OPTION_KEY, [] );
        $defs  = self::defaults();
        return $saved[ $key ] ?? $defs[ $key ] ?? $fallback;
    }

    // ── Menu ──────────────────────────────────────────────────────────────────
    public static function register_menu(): void {
        add_submenu_page(
            'woocommerce',
            __( 'تنظیمات اطلاع‌رسانی پیامکی', 'upsn' ),
            __( 'تنظیمات اطلاع‌رسانی پیامکی', 'upsn' ),
            'manage_woocommerce',
            'upsn-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    // ── Settings API ──────────────────────────────────────────────────────────
    public static function register_settings(): void {
        register_setting( 'upsn_settings_group', self::OPTION_KEY, [
            'sanitize_callback' => [ __CLASS__, 'sanitize' ],
        ] );

        // Visibility
        add_settings_section( 'upsn_visibility', __( 'محدوده نمایش دکمه', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_visibility', 'button_visibility', __( 'نمایش دکمه در', 'upsn' ), 'choice', [
            'all'        => __( 'همه محصولات ناموجود', 'upsn' ),
            'categories' => __( 'دسته‌بندی‌های انتخابی', 'upsn' ),
            'products'   => __( 'محصولات انتخابی', 'upsn' ),
        ] );
        self::field( 'upsn_visibility', 'button_categories', __( 'دسته‌بندی‌ها', 'upsn' ), 'product_cats', [],
            __( 'دسته‌بندی‌هایی که دکمه در آن‌ها نمایش داده می‌شود.', 'upsn' )
        );
        self::field( 'upsn_visibility', 'button_products', __( 'محصولات', 'upsn' ), 'product_search', [],
            __( 'محصولاتی که دکمه در آن‌ها نمایش داده می‌شود.', 'upsn' )
        );

        // Button
        add_settings_section( 'upsn_btn',  __( 'دکمه اطلاع‌رسانی', 'upsn' ),  '__return_false', 'upsn-settings' );
        self::field( 'upsn_btn', 'button_label',  __( 'متن دکمه', 'upsn' ),                    'text'   );
        self::field( 'upsn_btn', 'button_bg',     __( 'رنگ پس‌زمینه', 'upsn' ),               'color'  );
        self::field( 'upsn_btn', 'button_color',  __( 'رنگ متن', 'upsn' ),                     'color'  );
        self::field( 'upsn_btn', 'button_radius', __( 'گردی گوشه‌ها', 'upsn' ),               'range', [ 'min' => 0, 'max' => 32, 'unit' => 'px' ] );

        // Modal content
        add_settings_section( 'upsn_modal_content', __( 'محتوای پاپ‌آپ', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_modal_content', 'modal_title',    __( 'عنوان پاپ‌آپ', 'upsn' ),         'text'     );
        self::field( 'upsn_modal_content', 'modal_subtitle', __( 'زیرعنوان پاپ‌آپ', 'upsn' ),      'textarea' );
        self::field( 'upsn_modal_content', 'phone_label',    __( 'برچسب فیلد موبایل', 'upsn' ),    'text'     );
        self::field( 'upsn_modal_content', 'submit_label',   __( 'متن دکمه ثبت', 'upsn' ),         'text'     );
        self::field( 'upsn_modal_content', 'privacy_note',   __( 'یادداشت اطمینان', 'upsn' ),      'text', [],
            __( 'جمله کوتاهی زیر دکمه ثبت. برای نمایش ندادن، خالی بگذارید.', 'upsn' )
        );
        self::field( 'upsn_modal_content', 'sending_label',  __( 'متن دکمه در حال ارسال', 'upsn' ), 'text', [],
            __( 'متنی که هنگام ارسال درخواست روی دکمه نمایش داده می‌شود.', 'upsn' )
        );
        self::field( 'upsn_modal_content', 'modal_text_dir', __( 'جهت متن', 'upsn' ), 'choice',
            [ 'ltr' => __( 'چپ به راست (LTR)', 'upsn' ), 'rtl' => __( 'راست به چپ (RTL)', 'upsn' ) ]
        );

        // Modal style
        add_settings_section( 'upsn_modal', __( 'استایل پاپ‌آپ', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_modal', 'overlay_opacity', __( 'تیرگی پس‌زمینه صفحه', 'upsn' ),    'range', [ 'min' => 0, 'max' => 100, 'unit' => '%' ] );
        self::field( 'upsn_modal', 'modal_bg',        __( 'رنگ پس‌زمینه پنجره', 'upsn' ),     'color'  );
        self::field( 'upsn_modal', 'modal_radius',    __( 'گردی گوشه‌های پنجره', 'upsn' ),    'range', [ 'min' => 0, 'max' => 32, 'unit' => 'px' ] );

        // Form
        add_settings_section( 'upsn_form', __( 'استایل فرم', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_form', 'input_border',   __( 'رنگ حاشیه فیلد', 'upsn' ),          'color'  );
        self::field( 'upsn_form', 'input_focus',    __( 'رنگ فوکوس فیلد', 'upsn' ),          'color'  );
        self::field( 'upsn_form', 'submit_bg',      __( 'رنگ پس‌زمینه دکمه ثبت', 'upsn' ),   'color'  );
        self::field( 'upsn_form', 'submit_color',   __( 'رنگ متن دکمه ثبت', 'upsn' ),        'color'  );
        self::field( 'upsn_form', 'submit_radius',  __( 'گردی گوشه فیلد و دکمه ثبت', 'upsn' ), 'range', [ 'min' => 0, 'max' => 32, 'unit' => 'px' ] );

        // Messages
        add_settings_section( 'upsn_msg', __( 'پیام‌ها و خطاها', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_msg', 'success_message', __( 'پیام موفقیت', 'upsn' ), 'text' );
        self::field( 'upsn_msg', 'success_color',   __( 'رنگ متن موفقیت', 'upsn' ),   'color' );
        self::field( 'upsn_msg', 'error_color',     __( 'رنگ متن خطا', 'upsn' ),      'color' );

        self::field( 'upsn_msg', 'invalid_phone_error',
            __( 'خطای موبایل نامعتبر', 'upsn' ), 'text', [],
            __( 'هنگامی نمایش داده می‌شود که کاربر شماره موبایل نامعتبر وارد کند.', 'upsn' )
        );
        self::field( 'upsn_msg', 'already_registered_error',
            __( 'خطای ثبت تکراری', 'upsn' ), 'text', [],
            __( 'هنگامی نمایش داده می‌شود که شماره موبایل قبلاً برای این محصول ثبت شده باشد.', 'upsn' )
        );
        self::field( 'upsn_msg', 'ip_limit_error',
            __( 'خطای محدودیت IP', 'upsn' ), 'text', [],
            __( 'هنگامی نمایش داده می‌شود که IP از حد مجاز درخواست در ساعت بگذرد.', 'upsn' )
        );
        self::field( 'upsn_msg', 'phone_limit_error',
            __( 'خطای محدودیت موبایل', 'upsn' ), 'text', [],
            __( 'هنگامی نمایش داده می‌شود که شماره موبایل از حد مجاز روزانه بگذرد.', 'upsn' )
        );
        self::field( 'upsn_msg', 'generic_error',
            __( 'خطای عمومی', 'upsn' ), 'text', [],
            __( 'برای سایر خطاها (مشکلات سرور و غیره) نمایش داده می‌شود.', 'upsn' )
        );

        // Anti-spam
        add_settings_section( 'upsn_spam', __( 'ضدهرزنامه', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_spam', 'spam_ip_limit',
            __( 'حداکثر درخواست به ازای IP در ساعت', 'upsn' ), 'number', [],
            __( 'پس از این تعداد ارسال در ساعت، IP مسدود می‌شود. برای غیرفعال‌سازی ۰ وارد کنید.', 'upsn' )
        );
        self::field( 'upsn_spam', 'spam_phone_limit',
            __( 'حداکثر درخواست به ازای موبایل در روز', 'upsn' ), 'number', [],
            __( 'پس از این تعداد ارسال در روز برای تمام محصولات، شماره موبایل مسدود می‌شود. برای غیرفعال‌سازی ۰ وارد کنید.', 'upsn' )
        );

        // SMS Provider
        add_settings_section( 'upsn_sms', __( 'ارائه‌دهنده پیامک', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_sms', 'sms_gateway', __( 'درگاه پیامک', 'upsn' ), 'choice', [
            'smsir'       => 'SMS.ir',
            'kavenegar'   => 'Kavenegar',
            'farazsms'    => 'FarazSMS',
            'melipayamak' => 'MeliPayamak',
        ] );
        self::field( 'upsn_sms', 'sms_api_key',
            __( 'کلید API', 'upsn' ), 'password', [],
            __( 'SMS.ir → X-API-KEY | Kavenegar → API Key', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_username',
            __( 'نام کاربری', 'upsn' ), 'text', [],
            __( 'FarazSMS | MeliPayamak', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_password',
            __( 'رمز عبور', 'upsn' ), 'password', [],
            __( 'FarazSMS | MeliPayamak', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_line_number',
            __( 'شماره خط', 'upsn' ), 'text', [],
            __( 'فقط برای FarazSMS — شماره خط اختصاصی شما.', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_pattern',
            __( 'کد پترن / شناسه قالب', 'upsn' ), 'text', [],
            __( 'SMS.ir → شناسه عددی قالب | Kavenegar → نام قالب | FarazSMS → pattern_code | MeliPayamak → bodyId', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_param_name',
            __( 'نام پارامتر محصول', 'upsn' ), 'text', [],
            __( 'نام متغیر در قالب پیامک که نام محصول را دریافت می‌کند. SMS.ir/FarazSMS → نام پارامتر | Kavenegar → کلید توکن (مثلاً "token"). برای MeliPayamak استفاده نمی‌شود.', 'upsn' )
        );
    }

    private static function field( string $section, string $key, string $label, string $type, array $options = [], string $desc = '' ): void {
        add_settings_field(
            'upsn_' . $key,
            $label,
            [ __CLASS__, 'render_field' ],
            'upsn-settings',
            $section,
            [ 'key' => $key, 'type' => $type, 'options' => $options, 'desc' => $desc ]
        );
    }

    public static function render_field( array $args ): void {
        $key     = $args['key'];
        $type    = $args['type'];
        $value   = self::get( $key );
        $name    = self::OPTION_KEY . '[' . $key . ']';
        $options = $args['options'] ?? [];
        $desc    = $args['desc']    ?? '';

        $id = 'upsn-field-' . esc_attr( $key );

        switch ( $type ) {
            case 'color':
                printf(
                    '<label class="upsn-color"><input type="color" id="%1$s" name="%2$s" value="%3$s" /><code>%3$s</code></label>',
                    $id, esc_attr( $name ), esc_attr( $value )
                );
                break;

            case 'range':
                printf(
                    '<div class="upsn-range"><input type="range" id="%1$s" name="%2$s" value="%3$s" min="%4$d" max="%5$d" step="1" /><output for="%1$s" data-unit="%6$s">%3$s%6$s</output></div>',
                    $id, esc_attr( $name ), esc_attr( $value ),
                    (int) ( $options['min'] ?? 0 ), (int) ( $options['max'] ?? 100 ), esc_attr( $options['unit'] ?? '' )
                );
                break;

            case 'number':
                printf(
                    '<input type="number" class="upsn-text upsn-text--short" id="%s" name="%s" value="%s" min="0" max="100" />',
                    $id, esc_attr( $name ), esc_attr( $value )
                );
                break;

            case 'password':
                printf(
                    '<input type="password" class="upsn-text" id="%s" name="%s" value="%s" autocomplete="off" dir="ltr" />',
                    $id, esc_attr( $name ), esc_attr( $value )
                );
                break;

            case 'textarea':
                printf(
                    '<textarea class="upsn-text" id="%s" name="%s" rows="3">%s</textarea>',
                    $id, esc_attr( $name ), esc_textarea( $value )
                );
                break;

            case 'choice':
                $html = sprintf( '<div class="upsn-choice" id="%s" role="radiogroup" aria-labelledby="upsn-label-%s">', $id, esc_attr( $key ) );
                foreach ( $options as $opt_val => $opt_label ) {
                    $html .= sprintf(
                        '<label class="upsn-choice__opt"><input type="radio" name="%s" value="%s"%s /><span>%s</span></label>',
                        esc_attr( $name ),
                        esc_attr( $opt_val ),
                        checked( $value, $opt_val, false ),
                        esc_html( $opt_label )
                    );
                }
                $html .= '</div>';
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                break;

            case 'select':
                $html = sprintf( '<select id="%s" name="%s">', $id, esc_attr( $name ) );
                foreach ( $options as $opt_val => $opt_label ) {
                    $html .= sprintf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr( $opt_val ),
                        selected( $value, $opt_val, false ),
                        esc_html( $opt_label )
                    );
                }
                $html .= '</select>';
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                break;

            case 'product_cats':
                $cats     = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
                $selected = array_filter( array_map( 'absint', $value ? explode( ',', $value ) : [] ) );
                $html     = sprintf(
                    '<select id="%s" name="%s[]" multiple class="wc-enhanced-select upsn-wide" data-width="100%%">',
                    $id, esc_attr( $name )
                );
                if ( ! is_wp_error( $cats ) ) {
                    foreach ( $cats as $cat ) {
                        $html .= sprintf(
                            '<option value="%d"%s>%s</option>',
                            $cat->term_id,
                            in_array( $cat->term_id, $selected, true ) ? ' selected' : '',
                            esc_html( $cat->name )
                        );
                    }
                }
                $html .= '</select>';
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                break;

            case 'product_search':
                $selected_ids = array_filter( array_map( 'absint', $value ? explode( ',', $value ) : [] ) );
                $html = sprintf(
                    '<select id="%s" name="%s[]" multiple class="wc-product-search upsn-wide" data-width="100%%" data-placeholder="%s" data-action="woocommerce_json_search_products_and_variations">',
                    $id, esc_attr( $name ), esc_attr__( 'جستجوی محصول...', 'upsn' )
                );
                foreach ( $selected_ids as $pid ) {
                    $p = wc_get_product( $pid );
                    if ( $p ) {
                        $html .= sprintf(
                            '<option value="%d" selected>%s</option>',
                            $pid,
                            esc_html( $p->get_name() )
                        );
                    }
                }
                $html .= '</select>';
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                break;

            default:
                printf(
                    '<input type="text" class="upsn-text" id="%s" name="%s" value="%s" />',
                    $id, esc_attr( $name ), esc_attr( $value )
                );
        }

        if ( $desc ) {
            printf( '<p class="description">%s</p>', esc_html( $desc ) );
        }
    }

    public static function sanitize( $input ): array {
        $clean = [];
        $defs  = self::defaults();

        // Multi-select fields arrive as arrays — convert to comma-separated strings first
        foreach ( [ 'button_categories', 'button_products' ] as $arr_key ) {
            $raw_arr          = isset( $input[ $arr_key ] ) && is_array( $input[ $arr_key ] ) ? $input[ $arr_key ] : [];
            $input[ $arr_key ] = implode( ',', array_filter( array_map( 'absint', $raw_arr ) ) );
        }

        $color_keys    = [ 'button_bg', 'button_color', 'input_border', 'input_focus', 'submit_bg', 'submit_color', 'modal_bg', 'success_color', 'error_color' ];
        $number_keys   = [ 'button_radius', 'overlay_opacity', 'modal_radius', 'submit_radius', 'spam_ip_limit', 'spam_phone_limit' ];
        $textarea_keys = [ 'modal_subtitle', 'success_message' ];
        $select_keys   = [
            'button_visibility' => [ 'all', 'categories', 'products' ],
            'modal_text_dir'    => [ 'ltr', 'rtl' ],
            'sms_gateway'       => [ 'smsir', 'kavenegar', 'farazsms', 'melipayamak' ],
        ];
        // Allow empty: credentials, pattern, and visibility target lists
        $allow_empty_keys = [ 'privacy_note', 'sms_api_key', 'sms_username', 'sms_password', 'sms_line_number', 'sms_pattern', 'button_categories', 'button_products' ];

        foreach ( $defs as $key => $default ) {
            $raw = $input[ $key ] ?? '';

            if ( in_array( $key, $color_keys, true ) ) {
                $clean[ $key ] = sanitize_hex_color( $raw ) ?? $default;
            } elseif ( in_array( $key, $number_keys, true ) ) {
                $clean[ $key ] = (string) min( absint( $raw ), 100 );
            } elseif ( in_array( $key, $textarea_keys, true ) ) {
                $clean[ $key ] = sanitize_textarea_field( $raw ) ?: $default;
            } elseif ( isset( $select_keys[ $key ] ) ) {
                $clean[ $key ] = in_array( $raw, $select_keys[ $key ], true ) ? $raw : $default;
            } else {
                $sanitized     = sanitize_text_field( $raw );
                $allow_empty   = in_array( $key, $allow_empty_keys, true );
                $clean[ $key ] = ( $sanitized === '' && ! $allow_empty ) ? $default : $sanitized;
            }
        }

        return $clean;
    }

    // ── Render page ───────────────────────────────────────────────────────────

    /** Settings sections grouped into the tabs of the settings page. */
    private static function tabs(): array {
        return [
            'look'     => [ 'label' => __( 'ظاهر و متن‌ها', 'upsn' ),  'preview' => true,  'sections' => [ 'upsn_visibility', 'upsn_btn', 'upsn_modal_content', 'upsn_modal', 'upsn_form' ] ],
            'messages' => [ 'label' => __( 'پیام‌ها', 'upsn' ),         'preview' => true,  'sections' => [ 'upsn_msg' ] ],
            'spam'     => [ 'label' => __( 'ضدهرزنامه', 'upsn' ),       'preview' => false, 'sections' => [ 'upsn_spam' ] ],
            'sms'      => [ 'label' => __( 'درگاه پیامک', 'upsn' ),     'preview' => false, 'sections' => [ 'upsn_sms' ] ],
        ];
    }

    private static function section_hints(): array {
        return [
            'upsn_visibility'    => __( 'تعیین کنید دکمه روی کدام محصولات ناموجود نمایش داده شود.', 'upsn' ),
            'upsn_btn'           => __( 'دکمه‌ای که در صفحه محصولِ ناموجود دیده می‌شود.', 'upsn' ),
            'upsn_modal_content' => __( 'متن‌های پنجره‌ای که بعد از کلیک روی دکمه باز می‌شود.', 'upsn' ),
            'upsn_modal'         => __( 'ظاهر پنجره و پس‌زمینه تیره پشت آن.', 'upsn' ),
            'upsn_form'          => __( 'رنگ و گوشه‌های فیلد شماره و دکمه ثبت.', 'upsn' ),
            'upsn_msg'           => __( 'پیام‌هایی که مشتری بعد از ثبت درخواست یا هنگام خطا می‌بیند.', 'upsn' ),
            'upsn_spam'          => __( 'جلوی ثبت‌های پشت‌سرهم و ناخواسته را می‌گیرد.', 'upsn' ),
            'upsn_sms'           => __( 'سرویس‌دهنده‌ای که پیامک موجود شدن را می‌فرستد.', 'upsn' ),
        ];
    }

    public static function render_page(): void {
        global $wp_settings_sections, $wp_settings_fields;

        $tabs     = self::tabs();
        $hints    = self::section_hints();
        $sections = $wp_settings_sections['upsn-settings'] ?? [];
        $fields   = $wp_settings_fields['upsn-settings']   ?? [];
        $saved    = isset( $_GET['settings-updated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        // Values the preview starts from; admin.js keeps them in sync while editing.
        $preview_style = '';
        foreach ( self::css_vars() as $prop => $val ) {
            $preview_style .= $prop . ':' . $val . ';';
        }
        ?>
        <div class="wrap upsn-admin upsn-settings">
            <?php UPSN_Admin_Panel::render_header( 'upsn-settings', __( 'ظاهر دکمه، پیام‌ها و درگاه پیامک را تنظیم کنید.', 'upsn' ) ); ?>

            <?php if ( $saved ) : ?>
                <div class="upsn-toast" role="status">
                    <?php echo UPSN_Admin_Panel::icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php esc_html_e( 'تنظیمات ذخیره شد', 'upsn' ); ?>
                </div>
            <?php endif; ?>

            <div class="upsn-subtabs" role="tablist">
                    <?php foreach ( $tabs as $tab_id => $tab ) : ?>
                        <button type="button" role="tab" class="upsn-subtab" id="upsn-subtab-<?php echo esc_attr( $tab_id ); ?>"
                                data-tab="<?php echo esc_attr( $tab_id ); ?>"
                                data-preview="<?php echo $tab['preview'] ? '1' : '0'; ?>"
                                aria-controls="upsn-tab-<?php echo esc_attr( $tab_id ); ?>">
                            <?php echo esc_html( $tab['label'] ); ?>
                        </button>
                    <?php endforeach; ?>
            </div>

            <div class="upsn-settings__layout" id="upsn-layout">
                <?php // The preview holds a real <form>, so it must sit beside the settings form, never inside it. ?>
                <form method="post" action="options.php" id="upsn-settings-form" class="upsn-settings__main">
                    <?php settings_fields( 'upsn_settings_group' ); ?>
                        <?php foreach ( $tabs as $tab_id => $tab ) : ?>
                            <div class="upsn-tabpanel" role="tabpanel" id="upsn-tab-<?php echo esc_attr( $tab_id ); ?>"
                                 aria-labelledby="upsn-subtab-<?php echo esc_attr( $tab_id ); ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>" hidden>
                                <?php foreach ( $tab['sections'] as $section_id ) :
                                    if ( empty( $sections[ $section_id ] ) ) { continue; }
                                ?>
                                    <section class="upsn-panel upsn-section" data-section="<?php echo esc_attr( $section_id ); ?>">
                                        <header class="upsn-panel__head upsn-panel__head--stack">
                                            <h2><?php echo esc_html( $sections[ $section_id ]['title'] ); ?></h2>
                                            <?php if ( ! empty( $hints[ $section_id ] ) ) : ?>
                                                <p><?php echo esc_html( $hints[ $section_id ] ); ?></p>
                                            <?php endif; ?>
                                        </header>

                                        <?php foreach ( $fields[ $section_id ] ?? [] as $field ) :
                                            $fkey  = $field['args']['key'];
                                            $ftype = $field['args']['type'];
                                        ?>
                                            <div class="upsn-field" data-field="<?php echo esc_attr( $fkey ); ?>">
                                                <?php if ( $ftype === 'choice' ) : ?>
                                                    <span class="upsn-field__label" id="upsn-label-<?php echo esc_attr( $fkey ); ?>"><?php echo esc_html( $field['title'] ); ?></span>
                                                <?php else : ?>
                                                    <label class="upsn-field__label" id="upsn-label-<?php echo esc_attr( $fkey ); ?>" for="upsn-field-<?php echo esc_attr( $fkey ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
                                                <?php endif; ?>
                                                <div class="upsn-field__control">
                                                    <?php call_user_func( $field['callback'], $field['args'] ); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                    <div class="upsn-savebar">
                        <span class="upsn-savebar__status" id="upsn-dirty" hidden>
                            <i class="upsn-dot upsn-dot--pending"></i><?php esc_html_e( 'تغییرات ذخیره نشده‌اند', 'upsn' ); ?>
                        </span>
                        <button type="submit" class="upsn-btn upsn-btn--primary upsn-btn--lg"><?php esc_html_e( 'ذخیره تنظیمات', 'upsn' ); ?></button>
                    </div>
                </form>

                    <aside class="upsn-preview" id="upsn-preview" style="<?php echo esc_attr( $preview_style ); ?>" aria-label="<?php esc_attr_e( 'پیش‌نمایش زنده', 'upsn' ); ?>">
                        <div class="upsn-preview__head">
                            <strong><?php esc_html_e( 'پیش‌نمایش زنده', 'upsn' ); ?></strong>
                            <div class="upsn-preview__states" role="group" aria-label="<?php esc_attr_e( 'وضعیت پنجره', 'upsn' ); ?>">
                                <button type="button" class="is-active" data-state="form"><?php esc_html_e( 'فرم', 'upsn' ); ?></button>
                                <button type="button" data-state="invalid"><?php esc_html_e( 'شماره نامعتبر', 'upsn' ); ?></button>
                                <button type="button" data-state="error"><?php esc_html_e( 'خطای ثبت', 'upsn' ); ?></button>
                                <button type="button" data-state="success"><?php esc_html_e( 'موفق', 'upsn' ); ?></button>
                            </div>
                        </div>

                        <div class="upsn-preview__stage">
                            <div class="upsn-preview__product" aria-hidden="true">
                                <span class="upsn-skel upsn-skel--title"></span>
                                <span class="upsn-skel upsn-skel--line"></span>
                                <span class="upsn-skel upsn-skel--line upsn-skel--short"></span>
                                <?php UPSN_Frontend::render_button(); ?>
                            </div>
                            <?php UPSN_Frontend::render_modal( __( 'نام محصول نمونه', 'upsn' ), '', true ); ?>
                        </div>
                        <p class="upsn-preview__hint"><?php esc_html_e( 'نمایی از همان چیزی که مشتری می‌بیند؛ با هر تغییر به‌روز می‌شود.', 'upsn' ); ?></p>
                    </aside>
            </div>
        </div>
        <?php
    }

    // ── CSS variables from saved values ───────────────────────────────────────

    /** @return array<string,string|float> */
    public static function css_vars(): array {
        $opacity = round( (int) self::get( 'overlay_opacity' ) / 100, 2 );

        return [
            '--upsn-btn-bg'         => self::get( 'button_bg' ),
            '--upsn-btn-color'      => self::get( 'button_color' ),
            '--upsn-btn-radius'     => self::get( 'button_radius' ) . 'px',
            '--upsn-overlay-alpha'  => $opacity,
            '--upsn-modal-bg'       => self::get( 'modal_bg' ),
            '--upsn-modal-radius'   => self::get( 'modal_radius' ) . 'px',
            '--upsn-text-dir'       => self::get( 'modal_text_dir', 'ltr' ),
            '--upsn-input-border'   => self::get( 'input_border' ),
            '--upsn-input-focus'    => self::get( 'input_focus' ),
            '--upsn-submit-bg'      => self::get( 'submit_bg' ),
            '--upsn-submit-color'   => self::get( 'submit_color' ),
            '--upsn-submit-radius'  => self::get( 'submit_radius' ) . 'px',
            '--upsn-success-color'  => self::get( 'success_color' ),
            '--upsn-error-color'    => self::get( 'error_color' ),
        ];
    }

    public static function inline_css(): string {
        $lines = [];
        foreach ( self::css_vars() as $prop => $val ) {
            $lines[] = $prop . ':' . $val;
        }

        return ':root{' . implode( ';', $lines ) . '}';
    }
}
