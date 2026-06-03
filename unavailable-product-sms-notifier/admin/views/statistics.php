<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap upsn-admin upsn-stats">
    <h1><?php esc_html_e( 'آمار اطلاع‌رسانی پیامکی', 'upsn' ); ?></h1>

    <!-- Summary cards -->
    <div class="upsn-cards">
        <div class="upsn-card upsn-card--total">
            <div class="upsn-card__num"><?php echo number_format( $totals['all'] ); ?></div>
            <div class="upsn-card__label"><?php esc_html_e( 'کل درخواست‌ها', 'upsn' ); ?></div>
        </div>
        <div class="upsn-card upsn-card--pending">
            <div class="upsn-card__num"><?php echo number_format( $totals['pending'] ); ?></div>
            <div class="upsn-card__label"><?php esc_html_e( 'در انتظار', 'upsn' ); ?></div>
        </div>
        <div class="upsn-card upsn-card--notified">
            <div class="upsn-card__num"><?php echo number_format( $totals['notified'] ); ?></div>
            <div class="upsn-card__label"><?php esc_html_e( 'ارسال شد', 'upsn' ); ?></div>
        </div>
        <div class="upsn-card upsn-card--failed">
            <div class="upsn-card__num"><?php echo number_format( $totals['failed'] ); ?></div>
            <div class="upsn-card__label"><?php esc_html_e( 'ناموفق', 'upsn' ); ?></div>
        </div>
    </div>

    <div class="upsn-stats-grid">

        <!-- Top products -->
        <div class="upsn-stats-box">
            <h2><?php esc_html_e( 'پرتقاضاترین محصولات', 'upsn' ); ?></h2>
            <?php if ( empty( $top_products ) ) : ?>
                <p><?php esc_html_e( 'داده‌ای موجود نیست.', 'upsn' ); ?></p>
            <?php else :
                $max_p = max( array_column( (array) $top_products, 'total' ) ?: [1] );
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'محصول', 'upsn' ); ?></th>
                        <th style="width:80px;text-align:center"><?php esc_html_e( 'در انتظار', 'upsn' ); ?></th>
                        <th style="width:80px;text-align:center"><?php esc_html_e( 'ارسال شد', 'upsn' ); ?></th>
                        <th style="width:80px;text-align:center"><?php esc_html_e( 'ناموفق', 'upsn' ); ?></th>
                        <th style="width:60px;text-align:center"><?php esc_html_e( 'کل', 'upsn' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $top_products as $row ) :
                    $p    = wc_get_product( $row->product_id );
                    $name = $p ? $p->get_name() : '#' . $row->product_id;
                    $url  = $p ? get_edit_post_link( $row->product_id ) : '#';
                    $pct  = $max_p > 0 ? round( ( $row->total / $max_p ) * 100 ) : 0;
                ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
                            <div class="upsn-bar-wrap">
                                <div class="upsn-bar upsn-bar--blue" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                        </td>
                        <td style="text-align:center"><span class="upsn-badge upsn-badge--pending"><?php echo (int) $row->pending; ?></span></td>
                        <td style="text-align:center"><span class="upsn-badge upsn-badge--notified"><?php echo (int) $row->notified; ?></span></td>
                        <td style="text-align:center"><span class="upsn-badge upsn-badge--failed"><?php echo (int) $row->failed; ?></span></td>
                        <td style="text-align:center;font-weight:700"><?php echo (int) $row->total; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Top categories -->
        <div class="upsn-stats-box">
            <h2><?php esc_html_e( 'پرتقاضاترین دسته‌بندی‌ها', 'upsn' ); ?></h2>
            <?php if ( empty( $top_cats ) ) : ?>
                <p><?php esc_html_e( 'داده‌ای موجود نیست.', 'upsn' ); ?></p>
            <?php else :
                $max_c = max( array_column( (array) $top_cats, 'total' ) ?: [1] );
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'دسته‌بندی', 'upsn' ); ?></th>
                        <th style="width:70px;text-align:center"><?php esc_html_e( 'کل', 'upsn' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $top_cats as $cat ) :
                    $pct = $max_c > 0 ? round( ( $cat->total / $max_c ) * 100 ) : 0;
                    $cat_url = get_term_link( (int) $cat->term_id, 'product_cat' );
                ?>
                    <tr>
                        <td>
                            <?php if ( ! is_wp_error( $cat_url ) ) : ?>
                                <a href="<?php echo esc_url( $cat_url ); ?>"><?php echo esc_html( $cat->name ); ?></a>
                            <?php else : ?>
                                <?php echo esc_html( $cat->name ); ?>
                            <?php endif; ?>
                            <div class="upsn-bar-wrap">
                                <div class="upsn-bar upsn-bar--green" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                        </td>
                        <td style="text-align:center;font-weight:700"><?php echo (int) $cat->total; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>

    <!-- Daily requests chart -->
    <div class="upsn-stats-box upsn-stats-box--full">
        <h2><?php esc_html_e( 'درخواست‌های ۳۰ روز اخیر', 'upsn' ); ?></h2>
        <?php if ( empty( $daily ) ) : ?>
            <p><?php esc_html_e( 'داده‌ای موجود نیست.', 'upsn' ); ?></p>
        <?php else :
            $max_d = max( array_column( (array) $daily, 'total' ) ?: [1] );
        ?>
        <div class="upsn-chart">
            <?php foreach ( $daily as $d ) :
                $pct        = $max_d > 0 ? round( ( $d->total / $max_d ) * 100 ) : 0;
                $jalali_str = UPSN_Admin_Panel::jalali_date( $d->day . ' 00:00:00' );
                $label      = $jalali_str !== '—' ? substr( $jalali_str, 5, 5 ) : $d->day; // MM/DD
            ?>
            <div class="upsn-chart__col" title="<?php echo esc_attr( $d->day . ' — ' . $d->total . ' درخواست' ); ?>">
                <div class="upsn-chart__bar" style="height:<?php echo $pct; ?>%">
                    <span class="upsn-chart__val"><?php echo (int) $d->total; ?></span>
                </div>
                <div class="upsn-chart__label"><?php echo esc_html( $label ); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
