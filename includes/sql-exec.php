<?php

require_once 'sql-exec-lib.php'; // SQL execution lib

function xmm_sql() {

    set_time_limit(3600); // Limit of execution time: 1 hour

    $action = isset( $_GET['action'] ) ? $_GET['action'] : 'step1';

    echo '<div class="wrap">';
    show_title( __( 'SQL execution', 'xmm' ) );

    switch ( $action ) {
        case 'step1':
            show_subtitle( __( 'Step 1 - SQL sentence', 'xmm' ) );

            $xmm_sql = get_xmm_param( 'xmm-sql' );

            unset( $_SESSION['selected_sites'] );

            ?>
            <form method="post" action="<?php echo add_query_arg( 'action', 'step2' ); ?>">
                <table class="form-table">
                    <tbody>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'SQL sentence', 'xmm' ); ?></th>
                        <td>
                            <textarea rows="10" name="xmm-sql"><?php echo $xmm_sql; ?></textarea>
                            <p id="xmm-sql-desc" class="description">
                                <?php _e( 'Use the keyword <code>[prefix]</code> to get the proper blog prefix (wp_, wp_1_, wp_2_, ...)', 'xmm' ); ?>
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php
                submit_button( __( 'Step 2', 'xmm' ), 'primary', 'xmm-submit', true );
                ?>
            </form>
            </div>

            <?php
            break;

        case 'step2':
            show_subtitle( __( 'Step 2 - Select Blogs', 'xmm' ) );

            $sql               = get_xmm_param( 'xmm-sql' );
            $xmm_select_blogs  = get_xmm_param( 'xmm-select-blogs' );
            $xmm_match_query   = get_xmm_param( 'xmm-match-query' );
            $xmm_blogid_list   = get_xmm_param( 'xmm-blogid-list' );
            $xmm_blogname_list = get_xmm_param( 'xmm-blogname-list' );

            echo '<div id="xmm-sql" class="info"><strong>' . __( 'SQL sentence', 'xmm' ) . '</strong>: ' . $sql . '</div>';

            ?>

            <form method="post" action="<?php echo add_query_arg('action', 'step3'); ?>">
                <ul id="xmm-step2-list">
                    <li class="depth1">
                        <input id="option1" type="radio" name="xmm-select-blogs" value="all" <?php if ('all' == $xmm_select_blogs) { echo 'checked="checked"'; } ?>/>
                        <label for="option1">
                            <?php _e('All blogs', 'xmm'); ?>
                        </label>
                    </li>
                    <li class="depth1">
                        <input id="option3" type="radio" name="xmm-select-blogs" value="query" <?php if ('query' == $xmm_select_blogs) { echo 'checked="checked"'; } ?>/>
                        <label for="option3">
                            <?php _e('Blogs that match this query', 'xmm'); ?>
                            <br/>
                            <textarea id="xmm-match-query" name="xmm-match-query" rows="4"><?php echo $xmm_match_query; ?></textarea>
                            <p id="xmm-match-query-desc" class="description desc_textarea">
                                <?php _e('Use the keyword <code>[prefix]</code> to get the proper blog prefix (wp_, wp_1_, wp_2_, ...)', 'xmm'); ?>
                            </p>
                        </label>
                    </li>
                    <li class="depth1">
                        <input id="option2" type="radio" name="xmm-select-blogs" value="some" <?php if ('some' == $xmm_select_blogs) { echo 'checked="checked"'; } ?>/>
                        <label for="option2">
                            <?php _e('Some blogs selected manually', 'xmm') ?>
                            <div class="xmm-textarea">
                                <div class="xmm-textarea-title">
                                    <?php _e('List of blogs\' IDs', 'xmm');
                                    echo '&nbsp;';
                                    _e('(Comma separated. Ranges allowed in the form 1-5)', 'xmm'); ?>
                                </div>
                                <textarea id="xmm-blogid-list" name="xmm-blogid-list" rows="4"><?php echo $xmm_blogid_list; ?></textarea>
                            </div>
                            <div class="xmm-textarea">
                                <div class="xmm-textarea-title">
                                    <?php _e('List of blogs\' names', 'xmm');
                                    echo '&nbsp;';
                                    _e('(Comma separated)', 'xmm'); ?>
                                </div>
                                <textarea id="xmm-blogname-list" name="xmm-blogname-list" rows="4"><?php echo $xmm_blogname_list; ?></textarea>
                            </div>
                        </label>
                    </li>
                </ul>

                <?php
                submit_button( __( 'Step 3', 'xmm' ), 'primary', 'xmm-submit', true );
                ?>
                <span class="button">
                    <a href="javascript:history.go(-1)" onMouseOver="self.status=document.referrer;return true">
                        <?php _e( 'Go Back', 'xmm' ); ?>
                    </a>
                </span>

            </form>
            </div>

            <?php

            break;

        case 'step3':
            show_subtitle( __( 'Step 3 - Confirm execution', 'xmm' ) );

            $xmm_select_blogs  = get_xmm_param( 'xmm-select-blogs' );
            $xmm_match_query   = get_xmm_param( 'xmm-match-query' );
            $xmm_blogid_list   = get_xmm_param( 'xmm-blogid-list' );
            $xmm_blogname_list = get_xmm_param( 'xmm-blogname-list' );
            $xmm_sql           = $_SESSION['xmm-sql'];
            
            // Deactivate large network restriction, which affects wp_get_sites()
            add_filter( 'wp_is_large_network', '__return_false' );
            $sites = wp_get_sites(array('limit' => 1000000)); // 'Limit' is the maximum number of blog to be returned. We want them all :-)

            // Update value of 'selected sites' in accordance with the user selection
            unset($_SESSION['selected_sites']);
            if (('query' == $xmm_select_blogs) || ('all' == $xmm_select_blogs)) {
                $_SESSION['selected_sites'] = $sites;
            }

            if (('some' == $xmm_select_blogs)) {
                // Convert ranges in a list of ID's
                $xmm_blogid_list_exp = xmm_expand_blogid_list($xmm_blogid_list);

                // Get the path of the main blog (blog_id == 1)
                global $current_blog;
                $base_path = $current_blog->path;

                // Get the blog lists. Both list will be combined
                $xmm_blogid_list_array = explode (',', $xmm_blogid_list_exp); // Any possible white space was removed when expanding the list
                $xmm_blogname_list_array = array_map(
                    function ($string) use ($base_path) { return "$base_path$string/"; },
                    array_map(
                        'trim',
                        explode (',', $xmm_blogname_list)
                    )
                );

                // Make the list of selected sites using both lists
                foreach ($sites as $site) {
                    if (in_array($site['blog_id'], $xmm_blogid_list_array) ||
                        in_array($site['path'], $xmm_blogname_list_array)) {
                        $_SESSION['selected_sites'][$site['blog_id']] = array ('blog_id' => $site['blog_id'], 'path' => $site['path']);
                    }
                }
            }

            ?>

            <div id="xmm-sql" class="info">
                <p><strong><?php _e('SQL sentence', 'xmm'); ?></strong>: <code><?php echo $xmm_sql ?></code></p>
                <?php if (('query' == $xmm_select_blogs) && !empty($xmm_match_query)) { ?>
                    <p><strong><?php _e('SQL match sentence', 'xmm'); ?></strong>: <code><?php echo $xmm_match_query ?></code></p>
                <?php } elseif ('all' == $xmm_select_blogs) { ?>
                    <p><strong><?php _e('Execute in all blogs', 'xmm'); ?></strong></p>
                <?php } elseif ('some' == $xmm_select_blogs) { ?>
                    <div><strong><?php _e('Selected blogs', 'xmm'); ?></strong>:
                        <ul>
                            <?php foreach ($_SESSION['selected_sites'] as $site) { ?>
                                <li><?php echo '(' . $site['blog_id'] . ') ' . $site['path'] ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>
            </div>

            <?php

            echo '<form method="post" action="' . add_query_arg( 'action', 'step4' ) . '">';
            submit_button( __( 'Execute SQL now!', 'xmm' ), 'primary', 'xmm-submit', true );

            ?>
            <span class="button">
                    <a href="javascript:history.go(-1)" onMouseOver="self.status=document.referrer;return true">
                        <?php _e( 'Go Back', 'xmm' ); ?>
                    </a>
                </span>
            <?php

            echo '</form>';

            break;

        case 'step4':
            show_subtitle( __( 'Step 4 - Results of the execution', 'xmm' ) );

            $xmm_sql = $_SESSION['xmm-sql'];
            $xmm_match_query = $_SESSION['xmm-match-query'];
            $xmm_select_blogs = $_SESSION['xmm-select-blogs'];

            if (('query' == $xmm_select_blogs) && !preg_match("/^\\s*(select) /i", $_SESSION['xmm-match-query'])) {
                die(__('The match sentence must be a select', 'xmm'));
            }

            ?>
            <div id="xmm-sql" class="info">
                <p><strong><?php _e('SQL sentence', 'xmm'); ?></strong>: <code><?php echo $xmm_sql ?></code></p>
                <?php if (('query' == $xmm_select_blogs) && !empty($xmm_match_query)) { ?>
                    <p><strong><?php _e('SQL match sentence', 'xmm'); ?></strong>: <code><?php echo $xmm_match_query ?></code></p>
                <?php } elseif ('all' == $xmm_select_blogs) { ?>
                    <p><strong><?php _e('Execute in all blogs', 'xmm'); ?></strong></p>
                <?php } ?>
            </div>

            <?php
            global $switched, $wpdb; // WordPress global variables

            $summarize = false;
            $summary   = array();

            foreach ( $_SESSION['selected_sites'] as $site ) {
                switch_to_blog( $site['blog_id'] );

                // Create the SQL using the prefixes of the blog
                $sql_rendered       = render_sql( $xmm_sql );
                $sql_match_rendered = render_sql( $_SESSION['xmm-match-query'] );

                if ('query' == $xmm_select_blogs) {
                    $results = $wpdb->get_results( $sql_match_rendered, ARRAY_A );
                    if (!$results) {
                        continue;
                    }
                }

                echo '(' . $site['blog_id'] . '): ' . $site['path'] . '&nbsp;';

                if ( preg_match( "/^\\s*(select) /i", $sql_rendered ) ) {
                    $results = $wpdb->get_results( $sql_rendered, ARRAY_A );
                } else {
                    $results = $wpdb->query( $sql_rendered );
                }

                if (is_null($results) || ($results === false)) {
                    print_error();
                } else {
                    // CREATE, ALTER, TRUNCATE, DROP
                    if ($results === true) {
                        echo '<span style="color:green;">';
                        _e('Query executed successfully', 'xmm');
                        echo '</span><br />';
                    }
                    // INSERT, DELETE, UPDATE, etc.
                    elseif (is_numeric($results)) {
                        echo '<span style="color:green;">';
                        _e('Query executed successfully. Number of rows affected:', 'xmm');
                        echo '&nbsp;' . $results . '</span><br />';
                    }
                    // SELECT
                    elseif (empty($results)) {
                        echo '<span style="color:green;">';
                        _e('The query was executed successfully, but returns no results', 'xmm');
                        echo '</span><br />';
                    } else {
                        list($headers, $data, $summary, $summarize) = process_results($results, $summarize, $summary);
                        print_data($headers, $data);
                    }
                }

                restore_current_blog();
            }

            if ( $summarize ) {
                print_summary( $summary );
            }

            echo '</div>';

            break;
    }
}