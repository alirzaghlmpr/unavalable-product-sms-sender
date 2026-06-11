<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Gateway_MeliPayamak extends UPSN_SMS_Gateway {

    public function send_sms( string $to, array $variables, string $pattern ): object {
        upsn_log( '======================================' );
        upsn_log( '📤 [UPSN] MELIPAYAMAK SEND STARTED' );
        upsn_log( '======================================' );

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

            upsn_log( "ℹ️ MeliPayamak: To - {$to} | bodyId - {$pattern} | Text - {$text}" );

            $response = wp_remote_post( $endpoint, [
                'timeout' => 15,
                'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
                'body'    => $body,
            ] );

            if ( is_wp_error( $response ) ) {
                $msg = $response->get_error_message();
                upsn_log( "❌ MeliPayamak: wp_remote_post ERROR - {$msg}" );
                upsn_log( '======================================' );
                return (object) [ 'success' => false, 'code' => null, 'message' => $msg, 'raw_response' => null ];
            }

            $http_code = wp_remote_retrieve_response_code( $response );
            $raw_body  = trim( wp_remote_retrieve_body( $response ) );
            $data      = json_decode( $raw_body, true );

            upsn_log( "ℹ️ MeliPayamak: HTTP {$http_code} | Body: {$raw_body}" );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                upsn_log( '❌ MeliPayamak: Invalid JSON - ' . json_last_error_msg() );
                upsn_log( '======================================' );
                return (object) [ 'success' => false, 'code' => null, 'message' => 'Invalid JSON: ' . json_last_error_msg(), 'raw_response' => $raw_body ];
            }

            $code    = $data['Value']         ?? null;
            $message = $data['StrRetStatus']  ?? '';

            // Long numeric Value OR RetStatus === 1 = success
            $success = ( isset( $code ) && is_numeric( $code ) && strlen( (string) $code ) > 15 )
                    || ( ! empty( $data['RetStatus'] ) && (int) $data['RetStatus'] === 1 );

            if ( $success ) {
                upsn_log( "✅ MeliPayamak: SUCCESS" );
            } else {
                upsn_log( "❌ MeliPayamak: FAILED - {$message}" );
            }

            upsn_log( '======================================' );
            return (object) [ 'success' => $success, 'code' => $code, 'message' => $message, 'raw_response' => $raw_body ];

        } catch ( Exception $e ) {
            upsn_log( '❌ MeliPayamak: EXCEPTION - ' . $e->getMessage() );
            upsn_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => $e->getMessage(), 'raw_response' => null ];
        }
    }
}
