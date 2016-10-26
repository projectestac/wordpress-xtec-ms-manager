<?php

/**
 * Get parameter used in the process and put it into the session
 *
 * @param mixed $param
 * @return mixed
 */
function get_xmm_param( $param ) {

    $value = isset( $_POST[ $param ] ) ? stripslashes_deep( trim( $_POST[ $param ] ) ) : '';

    if ( empty( $value ) && ! empty( $_SESSION[ $param ] ) ) {
        $value = $_SESSION[ $param ];
    } else {
        $_SESSION[ $param ] = $value;
    }

    return $value;
}

/**
 * Substitute template values in SQL by it's actual values
 *
 * @param string $sql
 * @return mixed
 */
function render_sql( $sql ) {
    global $wpdb;

    $render_templates = array(
        '[prefix]'      => $wpdb->prefix,
        '[base_prefix]' => $wpdb->base_prefix
    );

    $sql_rendered = $sql;

    foreach ( $render_templates as $search => $replace ) {
        $sql_rendered = str_replace( $search, $replace, $sql_rendered );
    }

    return $sql_rendered;
}

/**
 * Convert ID ranges in a list of ID's
 *
 * @param string $xmm_blogid_list
 * @return string
 */
function xmm_expand_blogid_list($xmm_blogid_list)
{
    // Remove all white spaces
    $clean_list = preg_replace('/\s+/', '', $xmm_blogid_list);

    // Expand ranges. Ex: convert 1-5 in 1,2,3,4,5
    $result = preg_replace_callback(
        '/(\d+)-(\d+)/',
        function ($value) {
            return implode(',', range($value[1], $value[2]));
        },
        $clean_list
    );

    return $result;
}

/**
 * Get the list of all the sites, include only the blog_id and the blog path to save memory
 *
 * @return array
 */
function xmm_get_sites() {
    $selected_sites = array();

    // Deactivate large network restriction, which affects wp_get_sites()
    add_filter( 'wp_is_large_network', '__return_false' );
    $sites = wp_get_sites(array('limit' => XMM_MAX_BLOG_NUMBER)); // 'Limit' is the maximum number of blog to be returned. We want them all :-)

    // Make the list of selected sites using both lists
    foreach ($sites as $site) {
        $selected_sites[$site['blog_id']] = array ('blog_id' => $site['blog_id'], 'path' => $site['path']);
    }

    return $selected_sites;
}

/**
 * Build an array with the results of the SQL execution ready to be printed. Also build the summary if apply
 *
 * @param array $results
 * @param boolean $summarize
 * @param array $summary
 * @return array
 */
function process_results( $results, $summarize, $summary ) {
    $is_first = true;
    $num_row  = 0;
    $headers  = $data = array();

    if ( ( count( $results ) == 1 ) && ( count( $results[0] ) == 1 ) ) {
        $summarize = true;
    }

    foreach ( $results as $row ) {
        foreach ( $row as $key => $value ) {
            if ( $is_first ) {
                $headers[] = $key;
            }
            $data[ $num_row ][] = $value;
            if ( $summarize ) {
                if ( isset( $summary[ $value ] ) ) {
                    $summary[ $value ] ++;
                } else {
                    $summary[ $value ] = 1;
                }
            }
        }
        $is_first = false;
        $num_row ++;
    }

    return array( $headers, $data, $summary, $summarize );
}

/**
 * Show the latest SQL error
 */
function print_error() {
    global $wpdb;

    echo '<div style="color:red;">';
    echo __( 'An error has ocurred in sentence', 'xmm' ) . ': <code>' . $wpdb->last_query . '</code><br />';
    echo __( 'Error description', 'xmm' ) . ': <code>' . $wpdb->last_error . '</code><br>';
    echo '</div>';
}

/**
 * Print the results of an SQL execution for a blog
 *
 * @param array $headers
 * @param array $data
 */
function print_data( $headers, $data ) {
    echo '
        <table class="xmm-table" style="">
            <tbody>
                <tr valign="top">
        ';
    foreach ( $headers as $value ) {
        echo "<th>$value</th>";
    }
    echo '</tr>';
    foreach ( $data as $row ) {
        echo '<tr>';
        foreach ( $row as $value ) {
            echo "<td>$value</td>";
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Print the summary results for all the blogs, when available
 *
 * @param array $summary
 */
function print_summary( $summary ) {

    // Sort values with descending order
    arsort( $summary );

    // $sum accumulates the sum of all the results of the query in all the sites. $sum_product is used when the result
    //  of the query is a number an accumulates the product of the result by the times it happens.
    $sum = 0;
    $sum_product = 0;

    echo '<h3>' . __( 'Summary of results', 'xmm' ) . '</h3>';
    echo '<table class="xmm-table">';
    echo '<tbody>';

    echo '<tr style="border-bottom:5px solid;">';
    echo '<th>' . __( 'Value', 'xmm' ) . '</th>';
    echo '<th>' . __( 'Number of ocurrences', 'xmm' ) . '</th>';
    echo '</tr>';

    foreach ( $summary as $value => $number ) {
        echo '<tr>';
        echo '<td>' . $value . '</td>';
        echo '<td align="right">' . $number . '</td>';
        echo '</tr>';

        $sum += $number;
        if ( is_numeric( $value )) {
            $sum_product += $value * $number;
        }
    }

    // Print the sum of all the numbers in the table
    echo '<tr style="border-top:5px solid;"><td><strong>' . __( 'Sum of ocurrences', 'xmm' ) . '</strong></td><td align="right"><strong>' . $sum . '</strong></td></tr>';

    if ( $sum_product > 0 ) {
        // Print the sum products of all the numbers in the table
        echo '<tr><td><strong>' . __( 'Sum of ocurrences x value', 'xmm' ) . '</strong></td><td align="right"><strong>' . $sum_product . '</strong></td></tr>';
    }

    echo '</tbody>';
    echo '</table>';

    return;
}
