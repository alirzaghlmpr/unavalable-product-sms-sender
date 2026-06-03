<?php
defined( 'ABSPATH' ) || exit;

class UPSN_SMS_Sender {

    private static array $gateway_map = [
        'smsir'       => [ 'class' => 'UPSN_Gateway_SMSIR',       'file' => 'class-gateway-smsir.php' ],
        'kavenegar'   => [ 'class' => 'UPSN_Gateway_Kavenegar',   'file' => 'class-gateway-kavenegar.php' ],
        'farazsms'    => [ 'class' => 'UPSN_Gateway_FarazSMS',    'file' => 'class-gateway-farazsms.php' ],
        'melipayamak' => [ 'class' => 'UPSN_Gateway_MeliPayamak', 'file' => 'class-gateway-melipayamak.php' ],
    ];

    public static function send( string $phone, int $product_id ): bool {
        $gateway_key = UPSN_Settings::get( 'sms_gateway', 'smsir' );
        $pattern     = UPSN_Settings::get( 'sms_pattern' );
        $param_name  = UPSN_Settings::get( 'sms_param_name', 'product' );

        if ( ! $pattern ) {
            error_log( '[UPSN] SMS skipped: pattern / template ID not configured.' );
            return false;
        }

        $product      = wc_get_product( $product_id );
        $product_name = $product ? $product->get_name() : "#{$product_id}";
        if ( mb_strlen( $product_name ) > 24 ) {
            $product_name = mb_substr( $product_name, 0, 21 ) . '...';
        }

        // All gateways receive variables as a key-value array.
        // SMS.ir  → converts each pair to {name, value} objects
        // Kavenegar → appends each pair as query param (e.g. token=value)
        // FarazSMS → passes as input_data JSON
        // MeliPayamak → implodes values with ";"
        $variables = [ $param_name => $product_name ];

        $gateway = self::make_gateway( $gateway_key );
        if ( ! $gateway ) {
            error_log( "[UPSN] SMS skipped: unknown or misconfigured gateway '{$gateway_key}'." );
            return false;
        }

        $result = $gateway->send_sms( $phone, $variables, $pattern );
        return (bool) $result->success;
    }

    private static function make_gateway( string $key ): ?UPSN_SMS_Gateway {
        if ( ! isset( self::$gateway_map[ $key ] ) ) {
            return null;
        }

        $entry = self::$gateway_map[ $key ];
        $file  = UPSN_PATH . 'includes/gateways/' . $entry['file'];

        if ( ! file_exists( $file ) ) {
            error_log( "[UPSN] Gateway file not found: {$file}" );
            return null;
        }

        require_once $file;

        $config = [
            'api_key'     => UPSN_Settings::get( 'sms_api_key' ),
            'username'    => UPSN_Settings::get( 'sms_username' ),
            'password'    => UPSN_Settings::get( 'sms_password' ),
            'line_number' => UPSN_Settings::get( 'sms_line_number' ),
            'param_name'  => UPSN_Settings::get( 'sms_param_name', 'product' ),
        ];

        $class = $entry['class'];
        return new $class( $config );
    }
}
