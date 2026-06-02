<?php
defined( 'ABSPATH' ) || exit;

abstract class UPSN_SMS_Gateway {

    protected string $api_key;
    protected string $username;
    protected string $password;
    protected string $line_number;
    protected string $base_url;
    protected string $param_name;

    public function __construct( array $config = [] ) {
        $this->api_key     = $config['api_key']     ?? '';
        $this->username    = $config['username']    ?? '';
        $this->password    = $config['password']    ?? '';
        $this->line_number = $config['line_number'] ?? '';
        $this->base_url    = $config['base_url']    ?? '';
        $this->param_name  = $config['param_name']  ?? 'product';
    }

    /**
     * @param string $to       Mobile number
     * @param array  $variables Key-value pairs for the template parameters
     * @param string $pattern  Template ID / pattern code / template name
     * @return object { success: bool, code: string|null, message: string, raw_response: string|null }
     */
    abstract public function send_sms( string $to, array $variables, string $pattern ): object;
}
