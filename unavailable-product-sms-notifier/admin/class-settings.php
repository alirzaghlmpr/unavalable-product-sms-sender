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
            // Modal
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

        // Modal
        add_settings_section( 'upsn_modal', __( 'Popup Modal', 'upsn' ), '__return_false', 'upsn-settings' );
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
    }

    private static function field( string $section, string $key, string $label, string $type ): void {
        add_settings_field(
            'upsn_' . $key,
            $label,
            [ __CLASS__, 'render_field' ],
            'upsn-settings',
            $section,
            [ 'key' => $key, 'type' => $type ]
        );
    }

    public static function render_field( array $args ): void {
        $key   = $args['key'];
        $type  = $args['type'];
        $value = self::get( $key );
        $name  = self::OPTION_KEY . '[' . $key . ']';

        switch ( $type ) {
            case 'color':
                printf(
                    '<input type="color" name="%s" value="%s" style="height:36px;width:60px;cursor:pointer;border:1px solid #ccc;border-radius:4px;padding:2px;" />',
                    esc_attr( $name ),
                    esc_attr( $value )
                );
                break;
            case 'number':
                printf(
                    '<input type="number" name="%s" value="%s" min="0" max="100" style="width:72px;" />',
                    esc_attr( $name ),
                    esc_attr( $value )
                );
                break;
            default:
                printf(
                    '<input type="text" name="%s" value="%s" style="width:100%%;max-width:420px;" />',
                    esc_attr( $name ),
                    esc_attr( $value )
                );
        }
    }

    public static function sanitize( $input ): array {
        $clean = [];
        $defs  = self::defaults();

        $color_keys  = [ 'button_bg', 'button_color', 'input_border', 'input_focus', 'submit_bg', 'submit_color', 'modal_bg', 'success_color', 'error_color' ];
        $number_keys = [ 'button_radius', 'overlay_opacity', 'modal_radius', 'submit_radius' ];
        $text_keys   = [ 'button_label', 'success_message' ];

        foreach ( $defs as $key => $default ) {
            $raw = $input[ $key ] ?? '';

            if ( in_array( $key, $color_keys, true ) ) {
                $clean[ $key ] = sanitize_hex_color( $raw ) ?? $default;
            } elseif ( in_array( $key, $number_keys, true ) ) {
                $val = absint( $raw );
                $clean[ $key ] = (string) min( $val, 100 );
            } else {
                $clean[ $key ] = sanitize_text_field( $raw ) ?: $default;
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
