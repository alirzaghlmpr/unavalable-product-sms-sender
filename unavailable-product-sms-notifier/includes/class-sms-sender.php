<?php
defined( 'ABSPATH' ) || exit;

class UPSN_SMS_Sender {

    const API_ENDPOINT = 'https://api.sms.ir/v1/send/bulk';

    public static function send( string $phone, int $product_id ): bool {
        $api_key     = UPSN_Settings::get( 'sms_api_key' );
        $line_number = UPSN_Settings::get( 'sms_line_number' );
        $template    = UPSN_Settings::get( 'sms_message_template' );

        if ( ! $api_key || ! $line_number ) {
            error_log( '[UPSN] SMS skipped: API key or line number not configured.' );
            return false;
        }

        $product      = wc_get_product( $product_id );
        $product_name = $product ? $product->get_name() : "#{$product_id}";
        $message      = str_replace( '{product_name}', $product_name, $template );

        $response = wp_remote_post(
            self::API_ENDPOINT,
            [
                'timeout'     => 15,
                'redirection' => 5,
                'headers'     => [
                    'X-API-KEY'    => $api_key,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'        => wp_json_encode( [
                    'lineNumber'   => (string) $line_number,
                    'messageText'  => $message,
                    'mobiles'      => [ $phone ],
                    'sendDateTime' => null,
                ] ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( '[UPSN] SMS request error: ' . $response->get_error_message() );
            return false;
        }

        $http_code = (int) wp_remote_retrieve_response_code( $response );
        $raw_body  = wp_remote_retrieve_body( $response );
        $body      = json_decode( $raw_body, true );

        // sms.ir returns status:1 on success
        if ( $http_code === 200 && isset( $body['status'] ) && (int) $body['status'] === 1 ) {
            return true;
        }

        error_log( sprintf(
            '[UPSN] SMS failed — HTTP %d | to: %s | product: %s | response: %s',
            $http_code,
            $phone,
            $product_name,
            $raw_body
        ) );

        return false;
    }
}
