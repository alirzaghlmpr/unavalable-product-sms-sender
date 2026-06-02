<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Gateway_MeliPayamak extends UPSN_SMS_Gateway {

    public function send_sms( string $to, array $variables, string $pattern ): object {
        error_log( '======================================' );
        error_log( '📤 [UPSN] MELIPAYAMAK SEND STARTED' );
        error_log( '======================================' );

        try {
            $endpoint = $this->base_url ?: 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';

            // MeliPayamak expects variable values joined with semicolons
            $text = implode( ';', $variables );

            $body = [
                'username' => $this->username,
                'password' => $this->password,
                'text'     => $text,
                'to'       => $to,
                'bodyId'   => $pattern,
            ];

            error_log( "ℹ️ MeliPayamak: To - {$to} | bodyId - {$pattern} | Text - {$text}" );

            $response = wp_remote_post( $endpoint, [
                'timeout' => 15,
                'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
                'body'    => $body,
            ] );

            if ( is_wp_error( $response ) ) {
                $msg = $response->get_error_message();
                error_log( "❌ MeliPayamak: wp_remote_post ERROR - {$msg}" );
                error_log( '======================================' );
                return (object) [ 'success' => false, 'code' => null, 'message' => $msg, 'raw_response' => null ];
            }

            $http_code = wp_remote_retrieve_response_code( $response );
            $raw_body  = trim( wp_remote_retrieve_body( $response ) );
            $data      = json_decode( $raw_body, true );

            error_log( "ℹ️ MeliPayamak: HTTP {$http_code} | Body: {$raw_body}" );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                error_log( '❌ MeliPayamak: Invalid JSON - ' . json_last_error_msg() );
                error_log( '======================================' );
                return (object) [ 'success' => false, 'code' => null, 'message' => 'Invalid JSON: ' . json_last_error_msg(), 'raw_response' => $raw_body ];
            }

            $code    = $data['Value']         ?? null;
            $message = $data['StrRetStatus']  ?? '';

            // Long numeric Value OR RetStatus === 1 = success
            $success = ( isset( $code ) && is_numeric( $code ) && strlen( (string) $code ) > 15 )
                    || ( ! empty( $data['RetStatus'] ) && (int) $data['RetStatus'] === 1 );

            if ( $success ) {
                error_log( "✅ MeliPayamak: SUCCESS" );
            } else {
                error_log( "❌ MeliPayamak: FAILED - {$message}" );
            }

            error_log( '======================================' );
            return (object) [ 'success' => $success, 'code' => $code, 'message' => $message, 'raw_response' => $raw_body ];

        } catch ( Exception $e ) {
            error_log( '❌ MeliPayamak: EXCEPTION - ' . $e->getMessage() );
            error_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => $e->getMessage(), 'raw_response' => null ];
        }
    }
}
