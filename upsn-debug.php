<?php
/**
 * UPSN Debug Tool
 * 1. آپلود این فایل در root وردپرس (کنار wp-config.php)
 * 2. در مرورگر باز کن: https://yoursite.com/upsn-debug.php
 * 3. بعد از دیباگ حتماً حذفش کن
 */

// ── Security: change this key and add it to the URL as ?key=YOUR_KEY ──────────
define( 'DEBUG_KEY', 'upsn_debug_2025' );
if ( ! isset( $_GET['key'] ) || $_GET['key'] !== DEBUG_KEY ) {
    die( 'Access denied. Add ?key=upsn_debug_2025 to the URL.' );
}

// ── Bootstrap WordPress ────────────────────────────────────────────────────────
define( 'ABSPATH_CHECK', true );
require_once __DIR__ . '/wp-load.php';

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8">
<title>UPSN Debug</title>
<style>
  body { font-family: Tahoma, sans-serif; background: #f1f1f1; margin: 0; padding: 20px; direction: rtl; }
  h1 { color: #1d2327; }
  .card { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin: 16px 0; padding: 20px; }
  h2 { margin: 0 0 12px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
  .ok   { color: #1a7b4b; font-weight: bold; }
  .fail { color: #cc1818; font-weight: bold; }
  .warn { color: #996800; font-weight: bold; }
  table { border-collapse: collapse; width: 100%; font-size: 13px; }
  td, th { border: 1px solid #ddd; padding: 6px 10px; text-align: right; }
  th { background: #f6f7f7; }
  pre { background: #f6f7f7; padding: 10px; border-radius: 3px; font-size: 12px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
  .section-num { display: inline-block; background: #2271b1; color: #fff; border-radius: 50%; width: 22px; height: 22px; text-align: center; line-height: 22px; margin-left: 6px; font-size: 12px; }
</style>
</head>
<body>
<h1>🔍 UPSN Debug Tool</h1>
<p style="color:#666">بعد از دیباگ این فایل را حذف کنید.</p>

<?php

// ═══════════════════════════════════════════════════════════════════
// 1. Environment
// ═══════════════════════════════════════════════════════════════════
echo '<div class="card"><h2><span class="section-num">۱</span> محیط سرور</h2><table>';
$rows = [
    'PHP Version'          => PHP_VERSION,
    'WordPress Version'    => get_bloginfo( 'version' ),
    'WooCommerce Active'   => class_exists( 'WooCommerce' ) ? '<span class="ok">✓ فعال</span>' : '<span class="fail">✗ غیرفعال</span>',
    'WooCommerce Version'  => defined( 'WC_VERSION' ) ? WC_VERSION : '—',
    'max_execution_time'   => ini_get( 'max_execution_time' ) . 's',
    'memory_limit'         => ini_get( 'memory_limit' ),
    'cURL extension'       => function_exists( 'curl_init' ) ? '<span class="ok">✓ فعال</span>' : '<span class="fail">✗ نصب نشده</span>',
    'allow_url_fopen'      => ini_get( 'allow_url_fopen' ) ? '<span class="ok">✓ فعال</span>' : '<span class="warn">غیرفعال</span>',
];
foreach ( $rows as $k => $v ) {
    echo "<tr><th>{$k}</th><td>{$v}</td></tr>";
}
echo '</table></div>';


// ═══════════════════════════════════════════════════════════════════
// 2. WordPress HTTP config
// ═══════════════════════════════════════════════════════════════════
echo '<div class="card"><h2><span class="section-num">۲</span> تنظیمات HTTP وردپرس</h2><table>';

$block_external = defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL;
$disable_cron   = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
$accessible     = defined( 'WP_ACCESSIBLE_HOSTS' ) ? WP_ACCESSIBLE_HOSTS : '—';

$rows2 = [
    'WP_HTTP_BLOCK_EXTERNAL' => $block_external
        ? '<span class="fail">✗ true — تمام HTTP های خروجی بلاک است!</span>'
        : '<span class="ok">✓ false (طبیعی)</span>',
    'WP_ACCESSIBLE_HOSTS'   => esc_html( $accessible ),
    'DISABLE_WP_CRON'       => $disable_cron
        ? '<span class="warn">true — WP-Cron غیرفعال است (اگر real cron داری اشکالی نیست)</span>'
        : '<span class="ok">false (WP-Cron فعال)</span>',
];
foreach ( $rows2 as $k => $v ) {
    echo "<tr><th>{$k}</th><td>{$v}</td></tr>";
}
echo '</table></div>';


// ═══════════════════════════════════════════════════════════════════
// 3. Outbound HTTP test
// ═══════════════════════════════════════════════════════════════════
echo '<div class="card"><h2><span class="section-num">۳</span> تست اتصال خروجی به api.sms.ir</h2>';

$test_url = 'https://api.sms.ir/v1/send/verify';

// Test with SSL verify
$r1 = wp_remote_post( $test_url, [
    'timeout'   => 15,
    'sslverify' => true,
    'headers'   => [ 'Content-Type' => 'application/json', 'X-API-KEY' => 'test' ],
    'body'      => '{}',
] );

echo '<table>';
if ( is_wp_error( $r1 ) ) {
    $msg = $r1->get_error_message();
    echo "<tr><th>با SSL verify=true</th><td><span class='fail'>✗ {$msg}</span></td></tr>";

    // Retry without SSL verify
    $r2 = wp_remote_post( $test_url, [
        'timeout'   => 15,
        'sslverify' => false,
        'headers'   => [ 'Content-Type' => 'application/json', 'X-API-KEY' => 'test' ],
        'body'      => '{}',
    ] );
    if ( is_wp_error( $r2 ) ) {
        echo "<tr><th>با SSL verify=false</th><td><span class='fail'>✗ " . esc_html( $r2->get_error_message() ) . " — هاست اتصال به sms.ir را بلاک کرده</span></td></tr>";
    } else {
        $code = wp_remote_retrieve_response_code( $r2 );
        echo "<tr><th>با SSL verify=false</th><td><span class='warn'>✓ HTTP {$code} — اتصال برقرار ولی SSL مشکل دارد</span></td></tr>";
    }
} else {
    $code = wp_remote_retrieve_response_code( $r1 );
    echo "<tr><th>با SSL verify=true</th><td><span class='ok'>✓ HTTP {$code} — اتصال برقرار است</span></td></tr>";
}
echo '</table></div>';


// ═══════════════════════════════════════════════════════════════════
// 4. UPSN Settings
// ═══════════════════════════════════════════════════════════════════
echo '<div class="card"><h2><span class="section-num">۴</span> تنظیمات UPSN</h2>';

if ( ! class_exists( 'UPSN_Settings' ) ) {
    echo '<p class="fail">✗ کلاس UPSN_Settings بارگذاری نشده — پلاگین فعال است؟</p>';
} else {
    echo '<table>';
    $settings = [
        'sms_gateway'    => UPSN_Settings::get( 'sms_gateway' ),
        'sms_api_key'    => UPSN_Settings::get( 'sms_api_key' )    ? '****' . substr( UPSN_Settings::get( 'sms_api_key' ), -4 ) : '<span class="fail">خالی!</span>',
        'sms_pattern'    => UPSN_Settings::get( 'sms_pattern' )    ?: '<span class="fail">خالی!</span>',
        'sms_param_name' => UPSN_Settings::get( 'sms_param_name' ) ?: '<span class="fail">خالی!</span>',
        'sms_username'   => UPSN_Settings::get( 'sms_username' )   ?: '—',
        'sms_line_number'=> UPSN_Settings::get( 'sms_line_number' )?: '—',
    ];
    foreach ( $settings as $k => $v ) {
        echo "<tr><th>{$k}</th><td>{$v}</td></tr>";
    }
    echo '</table>';
}
echo '</div>';


// ═══════════════════════════════════════════════════════════════════
// 5. Database - Pending requests
// ═══════════════════════════════════════════════════════════════════
echo '<div class="card"><h2><span class="section-num">۵</span> وضعیت درخواست‌های دیتابیس</h2>';

global $wpdb;
$table = $wpdb->prefix . 'upsn_notify_requests';

$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
if ( ! $table_exists ) {
    echo "<p class='fail'>✗ جدول {$table} وجود ندارد — پلاگین فعال‌سازی نشده؟</p>";
} else {
    $counts = $wpdb->get_results( "SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status" );
    echo '<table><tr><th>وضعیت</th><th>تعداد</th></tr>';
    foreach ( $counts as $row ) {
        echo "<tr><td>{$row->status}</td><td>{$row->cnt}</td></tr>";
    }

    $last5 = $wpdb->get_results( "SELECT id, product_id, phone, status, requested_at, notified_at FROM {$table} ORDER BY id DESC LIMIT 5" );
    echo '</table><br><strong>آخرین ۵ درخواست:</strong><table><tr><th>ID</th><th>محصول</th><th>موبایل</th><th>وضعیت</th><th>تاریخ درخواست</th><th>تاریخ ارسال</th></tr>';
    foreach ( $last5 as $r ) {
        echo "<tr><td>{$r->id}</td><td>#{$r->product_id}</td><td>{$r->phone}</td><td>{$r->status}</td><td>{$r->requested_at}</td><td>" . ( $r->notified_at ?: '—' ) . "</td></tr>";
    }
    echo '</table>';
}
echo '</div>';


// ═══════════════════════════════════════════════════════════════════
// 6. Action Scheduler
// ═══════════════════════════════════════════════════════════════════
echo '<div class="card"><h2><span class="section-num">۶</span> Action Scheduler</h2><table>';

$as_available = function_exists( 'as_schedule_single_action' );
echo '<tr><th>در دسترس</th><td>' . ( $as_available ? '<span class="ok">✓ بله</span>' : '<span class="fail">✗ خیر</span>' ) . '</td></tr>';

if ( $as_available ) {
    $as_table   = $wpdb->prefix . 'actionscheduler_actions';
    $as_exists  = $wpdb->get_var( "SHOW TABLES LIKE '{$as_table}'" ) === $as_table;
    if ( $as_exists ) {
        $pending  = $wpdb->get_var( "SELECT COUNT(*) FROM {$as_table} WHERE hook='upsn_send_notifications' AND status='pending'" );
        $complete = $wpdb->get_var( "SELECT COUNT(*) FROM {$as_table} WHERE hook='upsn_send_notifications' AND status='complete'" );
        $failed   = $wpdb->get_var( "SELECT COUNT(*) FROM {$as_table} WHERE hook='upsn_send_notifications' AND status='failed'" );
        echo "<tr><th>jobs در انتظار</th><td>{$pending}</td></tr>";
        echo "<tr><th>jobs اجرا شده</th><td>{$complete}</td></tr>";
        echo "<tr><th>jobs شکست خورده</th><td>" . ( $failed > 0 ? "<span class='fail'>{$failed}</span>" : $failed ) . "</td></tr>";

        $last = $wpdb->get_results( "SELECT hook, status, scheduled_date_gmt, last_attempt_gmt, extended_args FROM {$as_table} WHERE hook='upsn_send_notifications' ORDER BY scheduled_date_gmt DESC LIMIT 5" );
        if ( $last ) {
            echo '</table><br><strong>آخرین job‌های UPSN:</strong><table><tr><th>وضعیت</th><th>زمان زمان‌بندی</th><th>آخرین تلاش</th><th>args</th></tr>';
            foreach ( $last as $j ) {
                echo "<tr><td>{$j->status}</td><td>{$j->scheduled_date_gmt}</td><td>{$j->last_attempt_gmt}</td><td>" . esc_html( $j->extended_args ) . "</td></tr>";
            }
        }
    }
}
echo '</table></div>';


// ═══════════════════════════════════════════════════════════════════
// 7. Live SMS test (optional)
// ═══════════════════════════════════════════════════════════════════
if ( isset( $_GET['sms_test'] ) && $_GET['sms_test'] && isset( $_GET['phone'] ) ) {
    $test_phone = preg_replace( '/[^0-9+]/', '', $_GET['phone'] );
    echo '<div class="card"><h2><span class="section-num">۷</span> تست مستقیم ارسال SMS</h2>';

    if ( ! class_exists( 'UPSN_SMS_Sender' ) ) {
        echo '<p class="fail">✗ UPSN_SMS_Sender بارگذاری نشده</p>';
    } else {
        $product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
        ob_start();
        $result = UPSN_SMS_Sender::send( $test_phone, $product_id ?: 1 );
        $output = ob_get_clean();
        echo '<pre>' . esc_html( print_r( $result, true ) ) . '</pre>';
        echo '<p><strong>نتیجه:</strong> ' . ( $result ? '<span class="ok">✓ موفق</span>' : '<span class="fail">✗ شکست خورد</span>' ) . '</p>';
        echo '<p>لاگ کامل را در <code>wp-content/debug.log</code> ببین.</p>';
    }
    echo '</div>';
} else {
    echo '<div class="card"><h2><span class="section-num">۷</span> تست مستقیم ارسال SMS (اختیاری)</h2>';
    $current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    echo '<p>برای تست ارسال واقعی به URL زیر برو (شماره موبایل خودت را جایگزین کن):</p>';
    echo '<pre>' . esc_html( $current_url . '?key=' . DEBUG_KEY . '&sms_test=1&phone=09XXXXXXXXX&product_id=PRODUCT_ID' ) . '</pre>';
    echo '</div>';
}

?>

<div class="card" style="background:#fff3cd;border-color:#ffc107">
  <strong>⚠️ مهم:</strong> بعد از اتمام دیباگ این فایل را از سرور حذف کنید!
</div>

</body>
</html>
