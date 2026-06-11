<?php
defined( 'ABSPATH' ) || exit;

class UPSN_Gateway_Kavenegar extends UPSN_SMS_Gateway {

    public function send_sms( string $to, array $variables, string $pattern ): object {
        upsn_log( '======================================' );
        upsn_log( '📤 [UPSN] KAVENEGAR SEND STARTED' );
        upsn_log( '======================================' );

        try {
            $url = 'https://api.kavenegar.com/v1/' . $this->api_key . '/verify/lookup.json';

            // Kavenegar uses token, token2, token3 … as variable keys
            $params = array_merge( [ 'receptor' => $to, 'template' => $pattern ], $variables );

            $final_url = add_query_arg( $params, $url );

            upsn_log( "ℹ️ Kavenegar: To - {$to} | Template - {$pattern}" );
            upsn_log( "ℹ️ Kavenegar: Params - " . wp_json_encode( $params ) );

            $response = wp_remote_get( $final_url, [ 'timeout' => 15 ] );

            if ( is_wp_error( $response ) ) {
                $msg = $response->get_error_message();
                upsn_log( "❌ Kavenegar: wp_remote_get ERROR - {$msg}" );
                upsn_log( '======================================' );
                return (object) [ 'success' => false, 'code' => null, 'message' => $msg, 'raw_response' => null ];
            }

            $http_code = wp_remote_retrieve_response_code( $response );
            $raw_body  = wp_remote_retrieve_body( $response );
            $data      = json_decode( $raw_body, true );

            upsn_log( "ℹ️ Kavenegar: HTTP {$http_code} | Body: {$raw_body}" );

            if ( isset( $data['return']['status'] ) ) {
                $status = (int) $data['return']['status'];
                if ( $status === 200 ) {
                    $code = $data['entries'][0]['messageid'] ?? null;
                    upsn_log( "✅ Kavenegar: SUCCESS (ID: {$code})" );
                    upsn_log( '======================================' );
                    return (object) [ 'success' => true, 'code' => $code, 'message' => 'پیامک با موفقیت ارسال شد', 'raw_response' => $raw_body ];
                }
                $msg = $data['return']['message'] ?? 'خطای نامشخص';
                upsn_log( "❌ Kavenegar: FAILED - {$msg}" );
                upsn_log( '======================================' );
                return (object) [ 'success' => false, 'code' => $status, 'message' => $msg, 'raw_response' => $raw_body ];
            }

            upsn_log( '❌ Kavenegar: Invalid response structure' );
            upsn_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => 'پاسخ نامعتبر از سرور', 'raw_response' => $raw_body ];

        } catch ( Exception $e ) {
            upsn_log( '❌ Kavenegar: EXCEPTION - ' . $e->getMessage() );
            upsn_log( '======================================' );
            return (object) [ 'success' => false, 'code' => null, 'message' => $e->getMessage(), 'raw_response' => null ];
        }
    }
}
