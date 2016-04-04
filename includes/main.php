<?php

function xmm_main() {
    echo '<div class="wrap">';
    show_title( __( 'XTEC Multisite Manager', 'xmm' ) );
    echo '<a href="' . network_admin_url( 'admin.php?page=xmm-sql' ) . '">' . __( 'SQL execution', 'xmm' ) . '</a>';
    echo '<br />';
    echo '<a href="' . network_admin_url( 'admin.php?page=xmm-requests' ) . '">' . __( 'Requests', 'xmm' ) . '</a>';
    echo '</div>';
}
