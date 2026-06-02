<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Gateway_SMSIR extends UPSN_SMS_Gateway {

    public function send_sms( string $to, array $variables, string $pattern ): object {
        error_log( '======================================' );
        error_log( '📤 [UPSN] SMSIR SEND STARTED' );
        error_log( '======================================' );

        try {
            $base_url = $this->base_url ?: 'https://api.sms.ir/v1/send/verify';

            $parameters = [];
            foreach ( $variables as $key => $value ) {
                $parameters[] = [ 'name' => $key, 'value' => $value ];
            }

            $body = wp_json_encode( [
                'mobile'     => $to,
                'templateId' => (int) $pattern,
                'parameters' => $parameters,
            ] );

            error_log( "ℹ️ SMSIR: To - {$to} | Template - {$pattern}" );
            error_log( "ℹ️ SMSIR: Parameters - " . wp_json_encode( $parameters ) );

            $response = wp_remote_post( $base_url, [
                'timeout' => 15,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'X-API-KEY'    => $this->api_key,
                ],
                'body' => $body,
            ] );

            if ( is_wp_error( $response ) ) {
                $msg = $response->get_error_message();
                error_log( "❌ SMSIR: wp_remote_post ERROR - {$msg}" );
                error_log( '======================================' );
                return (object) [ 'success' => false, 'code' => null, 'message' => $msg, 'raw_response' => null ];
            }

            $http_code = wp_remote_retrieve_response_code( $response );
            $raw_body  = trim( wp_remote_retrieve_body( $response ) );
            $decoded   = json_decode( $raw_body );

            error_log( "ℹ️ SMSIR: HTTP {$http_code} | Body: {$raw_body}" );

            if ( $decoded && isset( $decoded->status ) ) {
                if ( $decoded->status === 1 ) {
                    $code = $decoded->data->messageId ?? null;
                    error_log( "✅ SMSIR: SUCCESS (ID: {$code})" );
                    error_log( '======================================' );
                    return (object) [ 'success' => true, 'code' => $code, 'message' => $decoded->message ?? '', 'raw_response' => $raw_body ];
                }
                $msg = $decoded->message ?? 'خطای نامشخص';
                error_log( "❌ SMSIR: FAILED - {$msg}" );
                error_log( '======================================' );
                return (object) [ 'success' => false, 'code' => $decoded->status, 'message' => $msg, 'raw_response' => $raw_body ];
            }

            error_log( '❌ SMSIR: Invalid response structure' );
            error_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => 'پاسخ نامعتبر: ' . $raw_body, 'raw_response' => $raw_body ];

        } catch ( Exception $e ) {
            error_log( '❌ SMSIR: EXCEPTION - ' . $e->getMessage() );
            error_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => $e->getMessage(), 'raw_response' => null ];
        }
    }
}
