<?php
defined( 'ABSPATH' ) || exit;

$all       = $totals['all'];
$finished  = $totals['notified'] + $totals['failed'];
$success   = $finished > 0 ? (int) round( $totals['notified'] / $finished * 100 ) : null;
$pipeline  = [
    'pending'  => [ __( 'در انتظار', 'upsn' ), $totals['pending'] ],
    'notified' => [ __( 'ارسال شد', 'upsn' ),  $totals['notified'] ],
    'failed'   => [ __( 'ناموفق', 'upsn' ),    $totals['failed'] ],
];

$peak      = max( array_column( $series, 'total' ) ?: [ 0 ] );
$last_30   = array_sum( array_column( $series, 'total' ) );
$max_p     = $top_products ? max( array_map( 'intval', array_column( (array) $top_products, 'total' ) ) ) : 0;
$max_c     = $top_cats     ? max( array_map( 'intval', array_column( (array) $top_cats, 'total' ) ) )     : 0;
?>

<div class="wrap upsn-admin upsn-stats">
    <?php UPSN_Admin_Panel::render_header( 'upsn-stats', __( 'نگاهی به تقاضا و نتیجه ارسال پیامک‌ها.', 'upsn' ) ); ?>

    <!-- Pipeline: where every request stands -->
    <section class="upsn-panel upsn-pipeline">
        <div class="upsn-pipeline__lead">
            <span class="upsn-pipeline__total"><?php echo esc_html( number_format_i18n( $all ) ); ?></span>
            <span class="upsn-pipeline__caption"><?php esc_html_e( 'درخواست ثبت‌شده', 'upsn' ); ?></span>
        </div>

        <div class="upsn-pipeline__body">
            <div class="upsn-flow<?php echo $all ? '' : ' is-empty'; ?>" role="img"
                 aria-label="<?php echo esc_attr( sprintf( __( 'در انتظار %1$d، ارسال شد %2$d، ناموفق %3$d', 'upsn' ), $totals['pending'], $totals['notified'], $totals['failed'] ) ); ?>">
                <?php foreach ( $pipeline as $key => [ $label, $count ] ) :
                    if ( ! $count ) { continue; }
                ?>
                    <span class="upsn-flow__seg upsn-flow__seg--<?php echo esc_attr( $key ); ?>" style="flex-grow:<?php echo (int) $count; ?>"></span>
                <?php endforeach; ?>
            </div>

            <ul class="upsn-legend">
                <?php foreach ( $pipeline as $key => [ $label, $count ] ) : ?>
                    <li>
                        <i class="upsn-dot upsn-dot--<?php echo esc_attr( $key ); ?>"></i>
                        <span><?php echo esc_html( $label ); ?></span>
                        <b><?php echo esc_html( number_format_i18n( $count ) ); ?></b>
                        <?php if ( $all ) : ?><em><?php echo (int) round( $count / $all * 100 ); ?>٪</em><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="upsn-pipeline__aside">
            <span class="upsn-pipeline__rate"><?php echo null === $success ? '—' : (int) $success . '٪'; ?></span>
            <span class="upsn-pipeline__caption"><?php esc_html_e( 'موفقیت ارسال پیامک', 'upsn' ); ?></span>
        </div>
    </section>

    <div class="upsn-grid">

        <!-- Top products -->
        <section class="upsn-panel">
            <header class="upsn-panel__head">
                <h2><?php esc_html_e( 'پرتقاضاترین محصولات', 'upsn' ); ?></h2>
                <ul class="upsn-legend upsn-legend--inline">
                    <?php foreach ( $pipeline as $key => [ $label ] ) : ?>
                        <li><i class="upsn-dot upsn-dot--<?php echo esc_attr( $key ); ?>"></i><?php echo esc_html( $label ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </header>

            <?php if ( empty( $top_products ) ) : ?>
                <p class="upsn-panel__empty"><?php esc_html_e( 'هنوز داده‌ای برای نمایش نیست.', 'upsn' ); ?></p>
            <?php else : ?>
                <ul class="upsn-rank">
                <?php foreach ( $top_products as $row ) :
                    $p     = wc_get_product( $row->product_id );
                    $name  = $p ? $p->get_name() : '#' . $row->product_id;
                    $url   = $p ? get_edit_post_link( $row->product_id ) : '';
                    $thumb = $p ? wp_get_attachment_image_url( $p->get_image_id(), 'thumbnail' ) : '';
                    $pct   = $max_p > 0 ? max( 4, (int) round( $row->total / $max_p * 100 ) ) : 0;
                    $tip   = sprintf(
                        /* translators: 1: pending count, 2: notified count, 3: failed count */
                        __( 'در انتظار %1$d · ارسال شد %2$d · ناموفق %3$d', 'upsn' ),
                        $row->pending, $row->notified, $row->failed
                    );
                ?>
                    <li class="upsn-rank__row">
                        <span class="upsn-product__thumb">
                            <?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" /><?php endif; ?>
                        </span>
                        <div class="upsn-rank__main">
                            <div class="upsn-rank__top">
                                <?php if ( $url ) : ?>
                                    <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
                                <?php else : ?>
                                    <span><?php echo esc_html( $name ); ?></span>
                                <?php endif; ?>
                                <b><?php echo (int) $row->total; ?></b>
                            </div>
                            <div class="upsn-stack" style="width:<?php echo (int) $pct; ?>%" title="<?php echo esc_attr( $tip ); ?>">
                                <?php foreach ( [ 'pending', 'notified', 'failed' ] as $st ) :
                                    if ( ! (int) $row->$st ) { continue; }
                                ?>
                                    <i class="upsn-stack__seg upsn-stack__seg--<?php echo esc_attr( $st ); ?>" style="flex-grow:<?php echo (int) $row->$st; ?>"></i>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- Top categories -->
        <section class="upsn-panel">
            <header class="upsn-panel__head">
                <h2><?php esc_html_e( 'پرتقاضاترین دسته‌بندی‌ها', 'upsn' ); ?></h2>
            </header>

            <?php if ( empty( $top_cats ) ) : ?>
                <p class="upsn-panel__empty"><?php esc_html_e( 'هنوز داده‌ای برای نمایش نیست.', 'upsn' ); ?></p>
            <?php else : ?>
                <ul class="upsn-rank">
                <?php foreach ( $top_cats as $cat ) :
                    $pct     = $max_c > 0 ? max( 4, (int) round( $cat->total / $max_c * 100 ) ) : 0;
                    $cat_url = get_term_link( (int) $cat->term_id, 'product_cat' );
                ?>
                    <li class="upsn-rank__row">
                        <div class="upsn-rank__main">
                            <div class="upsn-rank__top">
                                <?php if ( ! is_wp_error( $cat_url ) ) : ?>
                                    <a href="<?php echo esc_url( $cat_url ); ?>"><?php echo esc_html( $cat->name ); ?></a>
                                <?php else : ?>
                                    <span><?php echo esc_html( $cat->name ); ?></span>
                                <?php endif; ?>
                                <b><?php echo (int) $cat->total; ?></b>
                            </div>
                            <div class="upsn-stack" style="width:<?php echo (int) $pct; ?>%">
                                <i class="upsn-stack__seg upsn-stack__seg--brand" style="flex-grow:1"></i>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <!-- Daily requests -->
    <section class="upsn-panel">
        <header class="upsn-panel__head">
            <h2><?php esc_html_e( 'درخواست‌های ۳۰ روز اخیر', 'upsn' ); ?></h2>
            <span class="upsn-panel__meta">
                <?php printf( esc_html__( '%1$d درخواست · بیشترین در یک روز: %2$d', 'upsn' ), $last_30, $peak ); ?>
            </span>
        </header>

        <?php if ( ! $last_30 ) : ?>
            <p class="upsn-panel__empty"><?php esc_html_e( 'در ۳۰ روز گذشته درخواستی ثبت نشده است.', 'upsn' ); ?></p>
        <?php else : ?>
            <div class="upsn-chart" role="img"
                 aria-label="<?php echo esc_attr( sprintf( __( 'نمودار درخواست‌های ۳۰ روز اخیر؛ مجموع %d', 'upsn' ), $last_30 ) ); ?>">
                <?php foreach ( $series as $i => $d ) :
                    $pct = $peak > 0 ? (int) round( $d['total'] / $peak * 100 ) : 0;
                    $show_label = $i % 5 === 4;
                ?>
                    <div class="upsn-chart__col<?php echo $d['total'] ? '' : ' is-zero'; ?>">
                        <span class="upsn-chart__tip" dir="ltr"><?php echo esc_html( $d['label'] . ' · ' . $d['total'] ); ?></span>
                        <span class="upsn-chart__bar" style="height:<?php echo $d['total'] ? max( 3, $pct ) : 0; ?>%"></span>
                        <span class="upsn-chart__label" dir="ltr"><?php echo $show_label ? esc_html( $d['label'] ) : ''; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <p class="upsn-retention">
        <?php echo UPSN_Admin_Panel::icon( 'shield', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php esc_html_e( 'این آمار از تمام سوابق ثبت‌شده ساخته می‌شود و هیچ درخواستی حذف نمی‌شود.', 'upsn' ); ?>
    </p>
</div>
