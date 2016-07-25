<?php

require_once 'requests-lib.php';

// Load javascript
wp_register_script('request-js', plugins_url() . '/xtec-ms-manager/javascript/requests.js', array('jquery'), '1.1', true);
wp_enqueue_script('request-js');

/**
 * Manages all the actions in the network side
 */
function xmm_requests() {

    // Check for view request
    if (isset($_GET['request-id']) && !empty($_GET['request-id']) && isset($_GET['view']) && ($_GET['view'] == 1)) {
        xmm_view_request($_GET['request-id']);
        return ;
    }

    // Check for edit request (show the form)
    if (isset($_GET['request-id']) && !empty($_GET['request-id']) && isset($_GET['edit']) && ($_GET['edit'] == 1)) {
        xmm_edit_request($_GET['request-id']);
        return ;
    }

    // Check for update request
    if (isset($_GET['request-id']) && !empty($_GET['request-id']) && isset($_POST['request-state']) && isset($_POST['update']) && ($_POST['update'] == 1)) {
        $_POST = array_map('stripslashes', $_POST);
        $id = intval( $_GET['request-id'] );
        $request = array (
            'id' => $id,
            'request-state' => $_POST['request-state'],
            'response' => $_POST['response'],
            'priv_notes' => $_POST['priv_notes']
        );
        if ( false !== xmm_update_request($request) ) {
            // Send e-mail to custom addresses
            $args = array(
                'request_id' => $id,
            );
            // TODO: Complete e-mail feature
            // xmm_send_email ( 'new_request', $args );
        }
    }

    // Check for delete request
    if (isset($_GET['request-id']) && !empty($_GET['request-id']) && isset($_GET['delete']) && ($_GET['delete'] == 1)) {
        xmm_delete_request($_GET['request-id']);
    }

    // Show the list of requests
    xmm_list_requests();

    return ;
}

/**
 * Manages all the actions in the blog side
 */
function xmm_blog_requests() {

    // Check for insert request
    if (isset($_POST['select-request']) && !empty($_POST['select-request']) && isset($_POST['insert']) && ($_POST['insert'] == 1)) {
        $_POST = array_map('stripslashes', $_POST);
        xmm_insert_request($_POST['select-request'], $_POST['request-comments']);
    }

    // Check for new request (Show form)
    if (isset($_GET['new']) && ($_GET['new'] == 1)) {
        xmm_new_request();
        return ;
    }

    // Check for view request (Show form)
    if (isset($_GET['request-id']) && !empty($_GET['request-id']) && isset($_GET['view']) && ($_GET['view'] = 1)) {
        xmm_view_request($_GET['request-id']);
        return ;
    }

    // Show the list of requests of the current blog
    xmm_list_blog_requests();

    return ;
}
