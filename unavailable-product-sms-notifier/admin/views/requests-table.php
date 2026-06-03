<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap upsn-admin">
    <h1><?php esc_html_e( 'درخواست‌های اطلاع‌رسانی پیامکی', 'upsn' ); ?></h1>

    <?php if ( isset( $_GET['upsn_notice'] ) ) : ?>
        <?php if ( $_GET['upsn_notice'] === 'bulk_done' ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'درخواست‌های انتخاب‌شده به وضعیت در انتظار بازگشتند.', 'upsn' ); ?></p>
            </div>
        <?php elseif ( $_GET['upsn_notice'] === 'bulk_deleted' ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'درخواست‌های انتخاب‌شده حذف شدند.', 'upsn' ); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Status filter tabs -->
    <ul class="subsubsub">
        <?php
        $base_url  = admin_url( 'admin.php?page=upsn-requests' );
        $carry_qs  = array_filter( [
            'search_phone' => $search_phone,
            'date_from'    => $date_from,
            'date_to'      => $date_to,
        ] );
        $filters_ui = [
            ''         => __( 'همه', 'upsn' ),
            'pending'  => __( 'در انتظار', 'upsn' ),
            'notified' => __( 'ارسال شد', 'upsn' ),
            'failed'   => __( 'ناموفق', 'upsn' ),
        ];
        $count_map = [
            ''         => $counts['all'],
            'pending'  => $counts['pending'],
            'notified' => $counts['notified'],
            'failed'   => $counts['failed'],
        ];
        $items = [];
        foreach ( $filters_ui as $key => $label ) {
            $active  = $status_filter === $key ? ' class="current"' : '';
            $url     = $key
                ? add_query_arg( array_merge( $carry_qs, [ 'status_filter' => $key ] ), $base_url )
                : add_query_arg( $carry_qs, $base_url );
            $items[] = sprintf(
                '<li><a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url( $url ),
                $active,
                esc_html( $label ),
                (int) $count_map[ $key ]
            );
        }
        echo implode( ' | ', $items );
        ?>
    </ul>

    <!-- Search / date filter bar -->
    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="upsn-filter-bar">
        <input type="hidden" name="page" value="upsn-requests" />
        <?php if ( $status_filter ) : ?>
            <input type="hidden" name="status_filter" value="<?php echo esc_attr( $status_filter ); ?>" />
        <?php endif; ?>

        <input
            type="text"
            name="search_phone"
            value="<?php echo esc_attr( $search_phone ); ?>"
            placeholder="<?php esc_attr_e( 'جستجو شماره موبایل...', 'upsn' ); ?>"
            class="upsn-filter-input"
        />
        <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="upsn-filter-input" title="<?php esc_attr_e( 'از تاریخ', 'upsn' ); ?>" />
        <input type="date" name="date_to"   value="<?php echo esc_attr( $date_to ); ?>"   class="upsn-filter-input" title="<?php esc_attr_e( 'تا تاریخ', 'upsn' ); ?>" />
        <button type="submit" class="button"><?php esc_html_e( 'فیلتر', 'upsn' ); ?></button>
        <?php if ( $search_phone || $date_from || $date_to ) : ?>
            <a href="<?php echo esc_url( $status_filter ? add_query_arg( 'status_filter', $status_filter, $base_url ) : $base_url ); ?>" class="button">
                <?php esc_html_e( 'پاک کردن فیلتر', 'upsn' ); ?>
            </a>
        <?php endif; ?>
    </form>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'upsn_bulk_action' ); ?>
        <input type="hidden" name="action" value="upsn_bulk" />

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option value=""><?php esc_html_e( '— عملیات انبوه —', 'upsn' ); ?></option>
                    <option value="resend"><?php esc_html_e( 'ارسال مجدد (بازگشت به در انتظار)', 'upsn' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'حذف', 'upsn' ); ?></option>
                </select>
                <button type="submit" class="button action"><?php esc_html_e( 'اعمال', 'upsn' ); ?></button>
            </div>
            <div class="tablenav-pages alignright" style="line-height:28px">
                <span class="displaying-num">
                    <?php printf( esc_html__( '%d درخواست', 'upsn' ), $total ); ?>
                </span>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="upsn-check-all" /></th>
                    <th><?php esc_html_e( 'محصول', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'شماره موبایل', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'وضعیت', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'تاریخ درخواست', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'تاریخ ارسال', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'عملیات', 'upsn' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $rows ) ) : ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e( 'درخواستی یافت نشد.', 'upsn' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php
                    $status_labels = [
                        'pending'  => 'در انتظار',
                        'notified' => 'ارسال شد',
                        'failed'   => 'ناموفق',
                    ];
                    foreach ( $rows as $row ) :
                        $product      = wc_get_product( $row->product_id );
                        $product_name = $product ? $product->get_name() : __( '(حذف شده)', 'upsn' );
                        $product_url  = $product ? get_edit_post_link( $row->product_id ) : '#';
                        $status_label = $status_labels[ $row->status ] ?? $row->status;
                        $delete_url   = wp_nonce_url(
                            add_query_arg( [ 'upsn_action' => 'delete_single', 'id' => $row->id ], admin_url( 'admin.php?page=upsn-requests' ) ),
                            'upsn_delete_' . $row->id
                        );
                    ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="request_ids[]" value="<?php echo esc_attr( $row->id ); ?>" />
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $product_url ); ?>">
                                    <?php echo esc_html( $product_name ); ?>
                                </a>
                                <br /><small><?php echo esc_html( '#' . $row->product_id ); ?></small>
                            </td>
                            <td><?php echo esc_html( $row->phone ); ?></td>
                            <td>
                                <span class="upsn-badge upsn-badge--<?php echo esc_attr( $row->status ); ?>">
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( UPSN_Admin_Panel::jalali_date( $row->requested_at ) ); ?></td>
                            <td><?php echo $row->notified_at
                                ? esc_html( UPSN_Admin_Panel::jalali_date( $row->notified_at ) )
                                : '—'; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $delete_url ); ?>"
                                   class="upsn-delete-link"
                                   onclick="return confirm('<?php esc_attr_e( 'آیا از حذف این درخواست مطمئن هستید؟', 'upsn' ); ?>')">
                                    <?php esc_html_e( 'حذف', 'upsn' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links( [
                        'base'    => add_query_arg( 'paged', '%#%' ),
                        'format'  => '',
                        'current' => $paged,
                        'total'   => $pages,
                    ] );
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.getElementById('upsn-check-all').addEventListener('change', function () {
    document.querySelectorAll('input[name="request_ids[]"]').forEach(function (cb) {
        cb.checked = this.checked;
    }, this);
});

// Confirm before bulk delete
document.querySelector('form').addEventListener('submit', function (e) {
    var action = this.querySelector('[name="bulk_action"]').value;
    if ( action === 'delete' ) {
        var checked = document.querySelectorAll('input[name="request_ids[]"]:checked').length;
        if ( checked === 0 ) { e.preventDefault(); return; }
        if ( ! confirm('حذف ' + checked + ' درخواست انتخاب‌شده؟ این عمل قابل بازگشت نیست.') ) {
            e.preventDefault();
        }
    }
});
</script>
