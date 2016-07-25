<?php

// Requests
const STATE_PENDING = 1;
const STATE_DENIED = 2;
const STATE_ACCEPTED = 3;

// Request types
const STATE_ACTIVE = 1;
const STATE_INACTIVE = 0;

// Pager
const NUM_ELEMS_TO_SHOW = 3;


/**
 * Actions executed during plugin activation
 */
function xmm_install() {

    // Add settings to database
    add_site_option( 'xmm_quota_percentage', 75 );
    add_site_option( 'xmm_send_email', 1 );
    add_site_option( 'xmm_email_addresses', '' );

    // Create tables
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . 'request_types';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                state tinyint(4) NOT NULL DEFAULT 1,
                name varchar(200) NOT NULL DEFAULT '',
                description text NOT NULL DEFAULT '',
                comments_text text NOT NULL DEFAULT '',
                PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . 'requests';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE {$table_name} (
                id int(11) NOT NULL AUTO_INCREMENT,
                state tinyint(4) NOT NULL DEFAULT 1,
                blog_id int(11) NOT NULL DEFAULT 0,
                request_type_id int(11) NOT NULL DEFAULT 0,
                user_id int(11) NOT NULL DEFAULT 0,
                user_login varchar(60) NOT NULL DEFAULT '',
                display_name varchar(250) NOT NULL DEFAULT '',
                user_email varchar(100) NOT NULL DEFAULT '',
                time_creation datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                time_edition datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                comments text NOT NULL DEFAULT '',
                response text NOT NULL DEFAULT '',
                priv_notes text NOT NULL DEFAULT '',
                PRIMARY KEY (id),
                KEY state (state),
                KEY blog_id (blog_id),
                KEY request_type_id (request_type_id),
                KEY user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        dbDelta($sql);
    }

    return ;
}

/**
 * Echoes a text sorrounded by header tags of a higher level than show_subtitle()
 *
 * @param $title Text to show
 */
function show_title( $title ) {
    echo "<h2 id=\"xmm-title\">$title</h2>";
}

/**
 * Echoes a text sorrounded by header tags of a lower level than show_title()
 *
 * @param $subtitle Text to show
 */
function show_subtitle( $subtitle ) {
    echo "<h3 id=\"xmm-sql-subtitle\">$subtitle</h3>";
}

/**
 * Adds a request link in blogs dashboards if quota usage exceeds a given percentage
 */
function xmm_add_quota_request_to_dashboard() {

    $quota = get_space_allowed();
    $used = get_space_used();
    $blog_usage = round(($used / $quota) * 100, 2);
    $xmm_quota_percentage = get_site_option( 'xmm_quota_percentage' );

    // $blog_usage contains a percentage
    if ($blog_usage >= $xmm_quota_percentage) {
        echo '<ul id="xmm-blog-quota-request">'
            . '<li class="warning">'
            . '<a href="' . add_query_arg('page', 'xmm-blog-requests', 'tools.php') . '">' . __('Request the extension of the quota', 'xmm') . '</a>'
            . '</li>'
            . '</ul>';
    }

    return;
}

/**
 * Shows a message to the user to inform about the result of a db operation
 *
 * @param $result Data returned by the db execution
 * @param $success_text Text to show if db execution was successful
 * @param $fail_text Text to show if db execution was not successful
 */
function xmm_log_to_user( $result, $success_text, $fail_text ) {

    if (( true === $result ) || ( is_numeric( $result ) && ( $result > 0 ))) {
        $message = $success_text;
        $class = 'notice-success';
    } else {
        $message = $fail_text;
        $class = 'notice-error';
    }

    // WordPress will put this div in the correct position :-)
    echo '<div class="notice ' . $class . '"><p>' . $message . '</p></div>';

}

/**
 * Prints the pager in the screen
 *
 * @param $current_page
 * @param $last_page
 */
function xmm_print_pager( $current_page, $last_page ) {

    if ( $last_page == 1 ) {
        // No pages to show
        return ;
    }

    echo '<div class="tablenav">';
    echo '<div class="tablenav-pages">';

    // $elems_pre: number of items shown before selected page
    // $before: flag to know if $i has a lower value than $current_page (lower = true, higher = false)
    for ($i = 1, $elems_pre = 0; $i <= $last_page; $i++ ) {

        $current = ( $i == $current_page ) ? 'current' : ''; // Check if the item of the loop is the selected page
        $before = ( $i < $current_page ) ? true : false;

        // Quick access to previous page
        if (( 1 == $i && empty( $current ))) {
            echo '<a class="next page-numbers" href="' . add_query_arg( 'current_page', $current_page-1 ) . '">&laquo;</a>&nbsp;';
        }

        if ( !empty( $current )) {
            // Show the number of the current page
            echo '<span class="page-numbers current">' . $i . '</span>&nbsp';
        } else {
            // Three conditions where page number is shown:
            // 1.- The first NUM_ELEMS_TO_SHOW pages
            // 2.- The previous and following pages of the current page
            // 3.- The last NUM_ELEMS_TO_SHOW pages
            if ((( $elems_pre < NUM_ELEMS_TO_SHOW ) && $before ) || (( $last_page - $i ) < NUM_ELEMS_TO_SHOW ) || ( abs( $current_page - $i ) == 1 )) {
                echo '<a class="next page-numbers" href="' . add_query_arg('current_page', $i) . '">' . $i . '</a>&nbsp;';
            }

            if ( $before ) {
                $elems_pre++;
            }
        }

        // Quick access to next page
        if (( $last_page == $i && empty( $current ))) {
            echo '<a class="next page-numbers" href="' . add_query_arg( 'current_page', $current_page+1 ) . '">&raquo;</a>&nbsp;';
        }
    }

    echo '</div>';
    echo '</div>';

    return ;
}

/**
 * Sends an email when some especial events occur
 *
 * @param $type Type of email to send
 * @param $args
 */
function xmm_send_email( $type, $args ) {

    // TODO: Complete this function
    switch( $type ) {
        case 'new_request':
            $request_id = $args[ 'request_id' ];
            $request = xmm_get_request( $request_id );
            $request_type = xmm_get_request_type( $request[ 'request_type_id' ] );
            $request_state = xmm_get_request_state( $request[ 'request_type_id' ] );
            $blog_details = get_blog_details( $request[ 'blog_id'] );
            $blog_url = network_site_url( $blog_details->path );
            $comments = '</p>';

            if (!empty($request[ 'comments'] )) {
                $comments .= '
La resposta dels administradors del servei &eacute;s la seg&uuml;ent:
<p style="font-style:italic; margin:25px;">
' . $request[ 'commments' ] . '
</p>
';
            }

            $email_text = __( '
<p style="margin-left:10px;">Benvolgut Sr./Sra.,</p>
<p>
    L\'estat de la sol&middot;licitud <strong>' . $request_type ['name']. '</strong> realitzada al 
    blog <a href="$blog_url">' . $blog_url . '</a> ha canviat 
    a <strong>' . $request_state . '</strong>. ' . $comments . '
<p>
    En cas de disconformitat amb la resoluci&oacute; de la sol&middot;licitud, podeu fer 
    una nova sol&middot;licitud i fer-hi constar les circumst&agrave;ncies que considereu
    oportunes.
</p>
<br />
<p>
    Atentament,
</p>
<p>
    L\'equip del projecte XTECBlocs
</p>
<br />
<p style="font-weight:bold;">
    P.D.: Aquest missatge s\'envia autom&agrave;ticament. Si us plau, no el respongueu.
</p>
            ', 'xmm');

            wp_mail( $request[ 'user_email'], sprintf( __( 'State of requests at [%s]', 'xmm' ), wp_specialchars_decode( get_option( 'blogname' ) ) ), $email_text );

            break;
    }

}