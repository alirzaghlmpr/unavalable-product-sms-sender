<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Gateway_FarazSMS extends UPSN_SMS_Gateway {

    public function send_sms( string $to, array $variables, string $pattern ): object {
        upsn_log( '======================================' );
        upsn_log( '📤 [UPSN] FARAZSMS SEND STARTED' );
        upsn_log( '======================================' );

        try {
            $base_url = $this->base_url ?: 'https://ippanel.com/patterns/pattern';

            $url = $base_url
                . '?username='    . urlencode( $this->username )
                . '&password='    . urlencode( $this->password )
                . '&from='        . urlencode( $this->line_number )
                . '&to='          . urlencode( wp_json_encode( [ $to ] ) )
                . '&input_data='  . urlencode( wp_json_encode( $variables ) )
                . '&pattern_code=' . urlencode( $pattern );

            upsn_log( "ℹ️ FarazSMS: To - {$to} | Pattern - {$pattern}" );
            upsn_log( "ℹ️ FarazSMS: Variables - " . wp_json_encode( $variables ) );

            $response = wp_remote_post( $url, [
                'timeout' => 15,
                'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
                'body'    => $variables,
            ] );

            if ( is_wp_error( $response ) ) {
                $msg = $response->get_error_message();
                upsn_log( "❌ FarazSMS: wp_remote_post ERROR - {$msg}" );
                upsn_log( '======================================' );
                return (object) [ 'success' => false, 'code' => null, 'message' => $msg, 'raw_response' => null ];
            }

            $http_code = wp_remote_retrieve_response_code( $response );
            $raw_body  = trim( wp_remote_retrieve_body( $response ) );

            upsn_log( "ℹ️ FarazSMS: HTTP {$http_code} | Body: {$raw_body}" );

            // Long numeric body = message ID = success
            if ( is_numeric( $raw_body ) && strlen( $raw_body ) > 5 ) {
                upsn_log( "✅ FarazSMS: SUCCESS (ID: {$raw_body})" );
                upsn_log( '======================================' );
                return (object) [ 'success' => true, 'code' => $raw_body, 'message' => 'پیامک با موفقیت ارسال شد', 'raw_response' => $raw_body ];
            }

            upsn_log( "❌ FarazSMS: FAILED - {$raw_body}" );
            upsn_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => $raw_body, 'raw_response' => $raw_body ];

        } catch ( Exception $e ) {
            upsn_log( '❌ FarazSMS: EXCEPTION - ' . $e->getMessage() );
            upsn_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => $e->getMessage(), 'raw_response' => null ];
        }
    }
}
