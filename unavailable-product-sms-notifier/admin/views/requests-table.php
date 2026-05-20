<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap upsn-admin">
    <h1><?php esc_html_e( 'SMS Notify Requests', 'upsn' ); ?></h1>

    <?php if ( isset( $_GET['upsn_notice'] ) && $_GET['upsn_notice'] === 'bulk_done' ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Bulk action applied. Selected requests have been reset to pending.', 'upsn' ); ?></p>
        </div>
    <?php endif; ?>

    <!-- Status filter tabs -->
    <ul class="subsubsub">
        <?php
        $base_url = admin_url( 'admin.php?page=upsn-requests' );
        $filters  = [
            ''         => __( 'All', 'upsn' ),
            'pending'  => __( 'Pending', 'upsn' ),
            'notified' => __( 'Notified', 'upsn' ),
            'failed'   => __( 'Failed', 'upsn' ),
        ];
        $count_map = [
            ''         => $counts['all'],
            'pending'  => $counts['pending'],
            'notified' => $counts['notified'],
            'failed'   => $counts['failed'],
        ];
        $items = [];
        foreach ( $filters as $key => $label ) {
            $active   = $status_filter === $key ? ' class="current"' : '';
            $url      = $key ? add_query_arg( 'status_filter', $key, $base_url ) : $base_url;
            $items[]  = sprintf(
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

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'upsn_bulk_action' ); ?>
        <input type="hidden" name="action" value="upsn_bulk" />

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option value=""><?php esc_html_e( '— Bulk Actions —', 'upsn' ); ?></option>
                    <option value="resend"><?php esc_html_e( 'Re-queue for SMS (reset to pending)', 'upsn' ); ?></option>
                </select>
                <button type="submit" class="button action"><?php esc_html_e( 'Apply', 'upsn' ); ?></button>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="upsn-check-all" /></th>
                    <th><?php esc_html_e( 'Product', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'Phone', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'Requested', 'upsn' ); ?></th>
                    <th><?php esc_html_e( 'Notified At', 'upsn' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $rows ) ) : ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e( 'No requests found.', 'upsn' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $rows as $row ) :
                        $product      = wc_get_product( $row->product_id );
                        $product_name = $product ? $product->get_name() : __( '(Deleted)', 'upsn' );
                        $product_url  = $product ? get_edit_post_link( $row->product_id ) : '#';
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
                                    <?php echo esc_html( ucfirst( $row->status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->requested_at ) ) ); ?></td>
                            <td><?php echo $row->notified_at
                                ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->notified_at ) ) )
                                : '—'; ?>
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
</script>
