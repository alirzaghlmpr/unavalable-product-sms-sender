<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Settings {

    const OPTION_KEY = 'upsn_settings';

    public static function init(): void {
        add_action( 'admin_menu',  [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init',  [ __CLASS__, 'register_settings' ] );
    }

    // ── Defaults ──────────────────────────────────────────────────────────────
    public static function defaults(): array {
        return [
            // Button
            'button_label'      => 'Notify Me When Available',
            'button_bg'         => '#2271b1',
            'button_color'      => '#ffffff',
            'button_radius'     => '4',
            // Modal content
            'modal_title'       => 'Notify Me When Available',
            'modal_subtitle'    => 'Enter your phone number and we will send you an SMS as soon as this product is back in stock.',
            'modal_text_dir'    => 'ltr',
            'submit_label'      => 'Notify Me',
            // Modal style
            'overlay_opacity'   => '55',
            'modal_bg'          => '#ffffff',
            'modal_radius'      => '8',
            // Form
            'input_border'      => '#cccccc',
            'input_focus'       => '#2271b1',
            'submit_bg'         => '#2271b1',
            'submit_color'      => '#ffffff',
            'submit_radius'     => '4',
            // Messages
            'success_message'   => 'You will be notified via SMS when this product is back in stock.',
            'success_color'     => '#1a7b4b',
            'error_color'       => '#cc1818',
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
            __( 'SMS Notify Settings', 'upsn' ),
            __( 'SMS Notify Settings', 'upsn' ),
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

        // Button
        add_settings_section( 'upsn_btn',  __( 'Notify Button', 'upsn' ),  '__return_false', 'upsn-settings' );
        self::field( 'upsn_btn', 'button_label',  __( 'Button Label', 'upsn' ),              'text'   );
        self::field( 'upsn_btn', 'button_bg',     __( 'Background Color', 'upsn' ),          'color'  );
        self::field( 'upsn_btn', 'button_color',  __( 'Text Color', 'upsn' ),                'color'  );
        self::field( 'upsn_btn', 'button_radius', __( 'Border Radius (px)', 'upsn' ),        'number' );
        self::field( 'upsn_btn', 'submit_label',  __( 'Submit Button Text', 'upsn' ),        'text'   );

        // Modal content
        add_settings_section( 'upsn_modal_content', __( 'Popup Content', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_modal_content', 'modal_title',    __( 'Popup Title', 'upsn' ),       'text'     );
        self::field( 'upsn_modal_content', 'modal_subtitle', __( 'Popup Subtitle', 'upsn' ),    'textarea' );
        self::field( 'upsn_modal_content', 'modal_text_dir', __( 'Text Direction', 'upsn' ),    'select',
            [ 'ltr' => __( 'LTR (Left to Right)', 'upsn' ), 'rtl' => __( 'RTL (Right to Left)', 'upsn' ) ]
        );

        // Modal style
        add_settings_section( 'upsn_modal', __( 'Popup Style', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_modal', 'overlay_opacity', __( 'Overlay Opacity (0–100)', 'upsn' ), 'number' );
        self::field( 'upsn_modal', 'modal_bg',        __( 'Modal Background Color', 'upsn' ),  'color'  );
        self::field( 'upsn_modal', 'modal_radius',    __( 'Modal Border Radius (px)', 'upsn' ), 'number' );

        // Form
        add_settings_section( 'upsn_form', __( 'Form Styles', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_form', 'input_border',   __( 'Input Border Color', 'upsn' ),         'color'  );
        self::field( 'upsn_form', 'input_focus',    __( 'Input Focus Color', 'upsn' ),           'color'  );
        self::field( 'upsn_form', 'submit_bg',      __( 'Submit Button Background', 'upsn' ),    'color'  );
        self::field( 'upsn_form', 'submit_color',   __( 'Submit Button Text Color', 'upsn' ),    'color'  );
        self::field( 'upsn_form', 'submit_radius',  __( 'Submit Border Radius (px)', 'upsn' ),   'number' );

        // Messages
        add_settings_section( 'upsn_msg', __( 'Messages', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_msg', 'success_message', __( 'Success Message Text', 'upsn' ), 'text'  );
        self::field( 'upsn_msg', 'success_color',   __( 'Success Text Color', 'upsn' ),   'color' );
        self::field( 'upsn_msg', 'error_color',     __( 'Error Text Color', 'upsn' ),     'color' );

        // SMS Provider
        add_settings_section( 'upsn_sms', __( 'SMS Provider', 'upsn' ), '__return_false', 'upsn-settings' );
        self::field( 'upsn_sms', 'sms_gateway', __( 'Gateway', 'upsn' ), 'select', [
            'smsir'       => 'SMS.ir',
            'kavenegar'   => 'Kavenegar',
            'farazsms'    => 'FarazSMS',
            'melipayamak' => 'MeliPayamak',
        ] );
        self::field( 'upsn_sms', 'sms_api_key',
            __( 'API Key', 'upsn' ), 'password', [],
            __( 'SMS.ir → X-API-KEY | Kavenegar → API Key', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_username',
            __( 'Username', 'upsn' ), 'text', [],
            __( 'FarazSMS | MeliPayamak', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_password',
            __( 'Password', 'upsn' ), 'password', [],
            __( 'FarazSMS | MeliPayamak', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_line_number',
            __( 'Line Number', 'upsn' ), 'text', [],
            __( 'FarazSMS only — your dedicated sender line.', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_pattern',
            __( 'Pattern / Template ID', 'upsn' ), 'text', [],
            __( 'SMS.ir → numeric Template ID | Kavenegar → template name | FarazSMS → pattern_code | MeliPayamak → bodyId', 'upsn' )
        );
        self::field( 'upsn_sms', 'sms_param_name',
            __( 'Product Parameter Name', 'upsn' ), 'text', [],
            __( 'The variable name in your template that receives the product name. SMS.ir/FarazSMS → parameter name | Kavenegar → token key (e.g. "token"). Not used by MeliPayamak.', 'upsn' )
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
                    '<input type="color" id="%s" name="%s" value="%s" style="height:36px;width:60px;cursor:pointer;border:1px solid #ccc;border-radius:4px;padding:2px;" />',
                    $id, esc_attr( $name ), esc_attr( $value )
                );
                break;

            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" min="0" max="100" style="width:72px;" />',
                    $id, esc_attr( $name ), esc_attr( $value )
                );
                break;

            case 'password':
                printf(
                    '<input type="password" id="%s" name="%s" value="%s" style="width:100%%;max-width:420px;" autocomplete="off" />',
                    $id, esc_attr( $name ), esc_attr( $value )
                );
                break;

            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="3" style="width:100%%;max-width:420px;">%s</textarea>',
                    $id, esc_attr( $name ), esc_textarea( $value )
                );
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
                echo $html;
                break;

            default:
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" style="width:100%%;max-width:420px;" />',
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

        $color_keys    = [ 'button_bg', 'button_color', 'input_border', 'input_focus', 'submit_bg', 'submit_color', 'modal_bg', 'success_color', 'error_color' ];
        $number_keys   = [ 'button_radius', 'overlay_opacity', 'modal_radius', 'submit_radius' ];
        $textarea_keys = [ 'modal_subtitle', 'success_message' ];
        $select_keys   = [
            'modal_text_dir' => [ 'ltr', 'rtl' ],
            'sms_gateway'    => [ 'smsir', 'kavenegar', 'farazsms', 'melipayamak' ],
        ];
        // Credentials and pattern are intentionally allowed to be empty (not yet configured)
        $allow_empty_keys = [ 'sms_api_key', 'sms_username', 'sms_password', 'sms_line_number', 'sms_pattern' ];

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
    public static function render_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'SMS Notify Settings', 'upsn' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'upsn_settings_group' );
                do_settings_sections( 'upsn-settings' );
                submit_button( __( 'Save Settings', 'upsn' ) );
                ?>
            </form>
        </div>
        <script>
        jQuery(function ($) {
            // Fields visible per gateway: field_key → [gateways that need it]
            var visibility = {
                sms_api_key:     ['smsir', 'kavenegar'],
                sms_username:    ['farazsms', 'melipayamak'],
                sms_password:    ['farazsms', 'melipayamak'],
                sms_line_number: ['farazsms'],
                sms_param_name:  ['smsir', 'kavenegar', 'farazsms'],
            };

            function applyVisibility() {
                var gw = $('#upsn-field-sms_gateway').val();
                $.each(visibility, function (fieldKey, gateways) {
                    var $tr = $('#upsn-field-' + fieldKey).closest('tr');
                    $tr.toggle(gateways.indexOf(gw) !== -1);
                });
            }

            $('#upsn-field-sms_gateway').on('change', applyVisibility);
            applyVisibility();
        });
        </script>
        <?php
    }

    // ── Generate inline CSS with saved values ─────────────────────────────────
    public static function inline_css(): string {
        $opacity = round( (int) self::get( 'overlay_opacity' ) / 100, 2 );

        $vars = [
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

        $lines = [];
        foreach ( $vars as $prop => $val ) {
            $lines[] = $prop . ':' . $val;
        }

        return ':root{' . implode( ';', $lines ) . '}';
    }
}
