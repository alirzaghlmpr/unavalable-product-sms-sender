<?php
defined( 'ABSPATH' ) || exit;

$base_url    = admin_url( 'admin.php?page=upsn-requests' );
$has_filters = $search_phone || $date_from || $date_to;
$clear_url   = $status_filter ? add_query_arg( 'status_filter', $status_filter, $base_url ) : $base_url;

$status_labels = [
    'pending'  => __( 'در انتظار', 'upsn' ),
    'notified' => __( 'ارسال شد', 'upsn' ),
    'failed'   => __( 'ناموفق', 'upsn' ),
];

$notice   = isset( $_GET['upsn_notice'] ) ? sanitize_key( wp_unslash( $_GET['upsn_notice'] ) ) : '';
$notice_n = isset( $_GET['upsn_n'] ) ? absint( $_GET['upsn_n'] ) : 0;

$range_from = $total ? ( $paged - 1 ) * $per_page + 1 : 0;
$range_to   = min( $total, $paged * $per_page );
?>

<div class="wrap upsn-admin">
    <?php UPSN_Admin_Panel::render_header( 'upsn-requests', __( 'مشتری‌هایی که منتظر موجود شدن محصولات هستند.', 'upsn' ) ); ?>

    <?php if ( $notice === 'requeued' ) : ?>
        <div class="upsn-alert upsn-alert--success" role="status">
            <?php echo UPSN_Admin_Panel::icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span><?php printf( esc_html__( '%d درخواست تازه در صف انتظار ثبت شد. سابقه درخواست‌های قبلی حفظ شده است.', 'upsn' ), $notice_n ); ?></span>
        </div>
    <?php elseif ( $notice === 'already_waiting' ) : ?>
        <div class="upsn-alert upsn-alert--info" role="status">
            <?php echo UPSN_Admin_Panel::icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span><?php esc_html_e( 'برای این شماره و محصول همین حالا یک درخواست در انتظار وجود دارد.', 'upsn' ); ?></span>
        </div>
    <?php endif; ?>

    <!-- Status filter -->
    <div class="upsn-toolbar">
        <?php
        $carry_qs   = array_filter( [
            'search_phone' => $search_phone,
            'date_from'    => $date_from,
            'date_to'      => $date_to,
        ] );
        $segments = [
            ''         => [ __( 'همه', 'upsn' ),      $counts['all'] ],
            'pending'  => [ $status_labels['pending'],  $counts['pending'] ],
            'notified' => [ $status_labels['notified'], $counts['notified'] ],
            'failed'   => [ $status_labels['failed'],   $counts['failed'] ],
        ];
        ?>
        <nav class="upsn-segments" aria-label="<?php esc_attr_e( 'فیلتر وضعیت', 'upsn' ); ?>">
            <?php foreach ( $segments as $key => [ $label, $count ] ) :
                $url = $key ? add_query_arg( array_merge( $carry_qs, [ 'status_filter' => $key ] ), $base_url ) : add_query_arg( $carry_qs, $base_url );
            ?>
                <a href="<?php echo esc_url( $url ); ?>"
                   class="upsn-segment<?php echo $status_filter === $key ? ' is-active' : ''; ?>"
                   <?php echo $status_filter === $key ? 'aria-current="true"' : ''; ?>>
                    <?php if ( $key ) : ?><i class="upsn-dot upsn-dot--<?php echo esc_attr( $key ); ?>"></i><?php endif; ?>
                    <?php echo esc_html( $label ); ?>
                    <b><?php echo (int) $count; ?></b>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="upsn-filters">
            <input type="hidden" name="page" value="upsn-requests" />
            <?php if ( $status_filter ) : ?>
                <input type="hidden" name="status_filter" value="<?php echo esc_attr( $status_filter ); ?>" />
            <?php endif; ?>

            <label class="upsn-search">
                <?php echo UPSN_Admin_Panel::icon( 'search', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <input type="text" name="search_phone" inputmode="numeric" dir="ltr"
                       value="<?php echo esc_attr( $search_phone ); ?>"
                       placeholder="<?php esc_attr_e( 'جستجوی شماره موبایل', 'upsn' ); ?>"
                       aria-label="<?php esc_attr_e( 'جستجوی شماره موبایل', 'upsn' ); ?>" />
            </label>
            <label class="upsn-datefield">
                <span><?php esc_html_e( 'از', 'upsn' ); ?></span>
                <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
            </label>
            <label class="upsn-datefield">
                <span><?php esc_html_e( 'تا', 'upsn' ); ?></span>
                <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
            </label>
            <button type="submit" class="upsn-btn upsn-btn--primary"><?php esc_html_e( 'اعمال فیلتر', 'upsn' ); ?></button>
            <?php if ( $has_filters ) : ?>
                <a href="<?php echo esc_url( $clear_url ); ?>" class="upsn-btn upsn-btn--ghost"><?php esc_html_e( 'پاک کردن', 'upsn' ); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ( empty( $rows ) ) : ?>

        <section class="upsn-panel upsn-empty">
            <span class="upsn-empty__art"><?php echo UPSN_Admin_Panel::icon( 'inbox', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            <?php if ( $has_filters || $status_filter ) : ?>
                <h2><?php esc_html_e( 'درخواستی با این فیلترها پیدا نشد', 'upsn' ); ?></h2>
                <p><?php esc_html_e( 'بازه تاریخ یا شماره را تغییر دهید، یا فیلترها را پاک کنید.', 'upsn' ); ?></p>
                <a href="<?php echo esc_url( $base_url ); ?>" class="upsn-btn upsn-btn--primary"><?php esc_html_e( 'نمایش همه درخواست‌ها', 'upsn' ); ?></a>
            <?php else : ?>
                <h2><?php esc_html_e( 'هنوز درخواستی ثبت نشده', 'upsn' ); ?></h2>
                <p><?php esc_html_e( 'وقتی مشتری‌ها روی صفحه یک محصول ناموجود دکمه اطلاع‌رسانی را بزنند، درخواستشان اینجا نمایش داده می‌شود.', 'upsn' ); ?></p>
            <?php endif; ?>
        </section>

    <?php else : ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="upsn-panel upsn-table-panel" id="upsn-bulk-form">
            <?php wp_nonce_field( 'upsn_bulk_action' ); ?>
            <input type="hidden" name="action" value="upsn_bulk" />

            <div class="upsn-bulkbar" id="upsn-bulkbar" hidden>
                <span id="upsn-bulk-count"></span>
                <button type="submit" class="upsn-btn upsn-btn--primary upsn-btn--sm">
                    <?php echo UPSN_Admin_Panel::icon( 'refresh', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php esc_html_e( 'ارسال مجدد', 'upsn' ); ?>
                </button>
            </div>

            <div class="upsn-table-scroll">
                <table class="upsn-table">
                    <thead>
                        <tr>
                            <th class="upsn-table__check"><input type="checkbox" id="upsn-check-all" aria-label="<?php esc_attr_e( 'انتخاب همه', 'upsn' ); ?>" /></th>
                            <th><?php esc_html_e( 'محصول', 'upsn' ); ?></th>
                            <th><?php esc_html_e( 'شماره موبایل', 'upsn' ); ?></th>
                            <th><?php esc_html_e( 'وضعیت', 'upsn' ); ?></th>
                            <th><?php esc_html_e( 'تاریخ درخواست', 'upsn' ); ?></th>
                            <th><?php esc_html_e( 'تاریخ ارسال', 'upsn' ); ?></th>
                            <th class="upsn-table__actions"><span class="screen-reader-text"><?php esc_html_e( 'عملیات', 'upsn' ); ?></span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $rows as $row ) :
                        $product      = wc_get_product( $row->product_id );
                        $product_name = $product ? $product->get_name() : __( '(حذف شده)', 'upsn' );
                        $product_url  = $product ? get_edit_post_link( $row->product_id ) : '';
                        $thumb        = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
                        $status_label = $status_labels[ $row->status ] ?? $row->status;
                        $requested    = explode( ' ', UPSN_Admin_Panel::jalali_date( $row->requested_at ) );
                        $notified     = $row->notified_at ? explode( ' ', UPSN_Admin_Panel::jalali_date( $row->notified_at ) ) : [];
                        $requeue_url  = wp_nonce_url(
                            add_query_arg( [ 'action' => 'upsn_requeue', 'id' => $row->id ], admin_url( 'admin-post.php' ) ),
                            'upsn_requeue_' . $row->id
                        );
                    ?>
                        <tr>
                            <td class="upsn-table__check">
                                <input type="checkbox" name="request_ids[]" value="<?php echo esc_attr( $row->id ); ?>" aria-label="<?php echo esc_attr( $row->phone ); ?>" />
                            </td>
                            <td>
                                <div class="upsn-product">
                                    <span class="upsn-product__thumb">
                                        <?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" /><?php endif; ?>
                                    </span>
                                    <span class="upsn-product__text">
                                        <?php if ( $product_url ) : ?>
                                            <a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_name ); ?></a>
                                        <?php else : ?>
                                            <span><?php echo esc_html( $product_name ); ?></span>
                                        <?php endif; ?>
                                        <small>#<?php echo (int) $row->product_id; ?></small>
                                    </span>
                                </div>
                            </td>
                            <td><span class="upsn-phone" dir="ltr"><?php echo esc_html( $row->phone ); ?></span></td>
                            <td>
                                <span class="upsn-badge upsn-badge--<?php echo esc_attr( $row->status ); ?>">
                                    <i class="upsn-dot upsn-dot--<?php echo esc_attr( $row->status ); ?>"></i>
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </td>
                            <td>
                                <span class="upsn-when">
                                    <span dir="ltr"><?php echo esc_html( $requested[0] ); ?></span>
                                    <small dir="ltr"><?php echo esc_html( $requested[1] ?? '' ); ?></small>
                                </span>
                            </td>
                            <td>
                                <?php if ( $notified ) : ?>
                                    <span class="upsn-when">
                                        <span dir="ltr"><?php echo esc_html( $notified[0] ); ?></span>
                                        <small dir="ltr"><?php echo esc_html( $notified[1] ?? '' ); ?></small>
                                    </span>
                                <?php else : ?>
                                    <span class="upsn-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="upsn-table__actions">
                                <a href="<?php echo esc_url( $requeue_url ); ?>" class="upsn-iconbtn"
                                   title="<?php esc_attr_e( 'ثبت درخواست تازه برای همین شماره و محصول؛ سابقه فعلی حفظ می‌شود.', 'upsn' ); ?>">
                                    <?php echo UPSN_Admin_Panel::icon( 'refresh', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <span class="screen-reader-text"><?php esc_html_e( 'ارسال مجدد', 'upsn' ); ?></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <footer class="upsn-pager">
                <span class="upsn-pager__info">
                    <?php printf( esc_html__( 'نمایش %1$d تا %2$d از %3$d درخواست', 'upsn' ), $range_from, $range_to, $total ); ?>
                </span>
                <?php
                if ( $pages > 1 ) {
                    $links = paginate_links( [
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => $pages,
                        'mid_size'  => 1,
                        'prev_text' => '‹',
                        'next_text' => '›',
                        'type'      => 'array',
                    ] );
                    if ( $links ) {
                        echo '<span class="upsn-pager__links">' . implode( '', $links ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                }
                ?>
            </footer>
        </form>

    <?php endif; ?>

    <p class="upsn-retention">
        <?php echo UPSN_Admin_Panel::icon( 'shield', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php esc_html_e( 'سوابق درخواست‌ها برای گزارش‌گیری نگهداری می‌شوند و قابل حذف نیستند.', 'upsn' ); ?>
    </p>
</div>
