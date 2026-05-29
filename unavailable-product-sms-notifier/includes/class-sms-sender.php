<?php
defined( 'ABSPATH' ) || exit;

class UPSN_SMS_Sender {

    const API_ENDPOINT = 'https://api.sms.ir/v1/send/verify';

    public static function send( string $phone, int $product_id ): bool {
        $api_key     = UPSN_Settings::get( 'sms_api_key' );
        $template_id = UPSN_Settings::get( 'sms_template_id' );
        $param_name  = UPSN_Settings::get( 'sms_param_name' );

        if ( ! $api_key || ! $template_id ) {
            error_log( '[UPSN] SMS skipped: API key or template ID not configured.' );
            return false;
        }

        $product      = wc_get_product( $product_id );
        $product_name = $product ? $product->get_name() : "#{$product_id}";

        $response = wp_remote_post(
            self::API_ENDPOINT,
            [
                'timeout'     => 15,
                'redirection' => 5,
                'headers'     => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'text/plain',
                    'x-api-key'    => $api_key,
                ],
                'body' => wp_json_encode( [
                    'mobile'     => $phone,
                    'templateId' => (int) $template_id,
                    'parameters' => [
                        [ 'name' => $param_name, 'value' => $product_name ],
                    ],
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
