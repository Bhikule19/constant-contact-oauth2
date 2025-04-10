<?php
/**
 * Plugin Name: Constant Contact OAuth Connect
 * Description: Adds a shortcode to handle OAuth connection with Constant Contact.
 * Version: 1.0
 * Author: Brainstorm Force
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!session_id()) {
    session_start();
}

add_shortcode('oauth_connect', 'Constant_Contact_Oauth_Connect');
add_action('template_redirect', 'Load_Blank_Template_For_Oauth_page');

function Load_Blank_Template_For_Oauth_page() {
    if (is_page('oauth-connect2')) {
        add_filter('template_include', function($template) {
            return plugin_dir_path(__FILE__) . 'blank-template.php';
        });
    }
}

function Constant_Contact_Oauth_Connect() {
    ob_start();

    $client_id = get_option('cc_client_id');
    $client_secret = get_option('cc_client_secret');
    $access_token = get_option('cc_access_token');
    $refresh_token = get_option('cc_refresh_token');
    $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : null;

    if (isset($_POST['connect'])) {
        $client_id = sanitize_text_field($_POST['client_id']);
        $client_secret = sanitize_text_field($_POST['client_secret']);
        update_option('cc_client_id', $client_id);
        update_option('cc_client_secret', $client_secret);
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $redirect_uri = home_url('/oauth-connect2/');
        $scope = rawurlencode("contact_data campaign_data offline_access");
        $auth_url = "https://authz.constantcontact.com/oauth2/default/v1/authorize?client_id={$client_id}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}";

        wp_redirect($auth_url);
        exit;
    }

    $show_token_section = false;
    $error_message = '';

    if ($code && empty($_SESSION['token_displayed'])) {
        $access_token_data = getAccessToken($code, $client_id, $client_secret, home_url('/oauth-connect2/'));
        if ($access_token_data && isset($access_token_data['access_token'])) {
            update_option('cc_access_token', $access_token_data['access_token']);
            update_option('cc_refresh_token', $access_token_data['refresh_token']);
            update_option('cc_token_expiry', time() + $access_token_data['expires_in']);
            $_SESSION['token_displayed'] = true;
            $show_token_section = true;
        } else {
            $error_message = 'The authorization code is invalid or has expired. Please enter valid credentials.';
        }
    } elseif ($code && !empty($_SESSION['token_displayed'])) {
        $error_message = 'The authorization code is invalid or has expired. Please enter valid credentials.';
        $_SESSION['token_displayed'] = null;
    }

    if (isset($_POST['revoke_access'])) {
        revokeAuthorization();
        $access_token = $refresh_token = null;
        $_SESSION['token_displayed'] = null;
    }

    if (isset($_POST['go_back'])) {
        $_SESSION['token_displayed'] = null;
        $show_token_section = false;
    }

    echo '<div class="cppro-cc-main-block"><div class="cppro-cc-container">';

    if (!empty($error_message)) {
        echo '<div class="cppro-cc-notice cppro-cc-error-msg">' . esc_html($error_message) . '</div>';
    }

    if ($show_token_section) {
        echo '<form method="POST"><button class="cppro-cc-revoke_acces_btn" type="submit" name="go_back">Enter Client Credentials</button></form>';
        echo '<div class="token-output">';
        echo '<h3>Access Token</h3><pre class="ccpro-cc-token-box">' . esc_html($access_token ?: 'Not Found') . '</pre>';
        echo '<h3>Refresh Token</h3><pre class="ccpro-cc-token-box">' . esc_html($refresh_token ?: 'Not Found') . '</pre>';
        echo '</div>';
        // echo '<form method="POST"><button class="cppro-cc-revoke_acces_btn" type="submit" name="revoke_access">Revoke Access</button></form>';
    } else {
        echo '<div class="cppro-cc-prerequisite-box">';
        echo '<h2 class="cppro-cc-header">🔧 Before You Start</h2>';
        echo '<p>Follow these steps before connecting to Constant Contact:</p>';
        echo '<ol>';
        echo '<li>Go to the <a href="https://developer.constantcontact.com/" target="_blank">Constant Contact App Portal</a>.</li>';
        echo '<li>Create a new application and copy the Client ID and Secret.</li>';
        echo '<li>Set the Redirect URI to: <code>' . home_url('/oauth-connect2/') . '</code></li>';
        echo '</ol>';
        echo '<p>Enter your credentials below.</p>';
        echo '</div>';

        echo '<h2 class="cc-header">Connect to Constant Contact</h2>';
        echo '<form method="POST">';
        echo '<div class="form-group"><label for="client_id">Client ID</label>';
        echo '<input type="text" name="client_id" id="client_id" required></div>';
        echo '<div class="form-group"><label for="client_secret">Client Secret</label>';
        echo '<input type="text" name="client_secret" id="client_secret" required></div>';
        echo '<button type="submit" name="connect" class="cppro-cc-connect-btn">Connect to Constant Contact</button>';
        echo '</form>';
    }
    echo '</div></div>';

    return ob_get_clean();
}

function getAccessToken($code, $client_id, $client_secret, $redirect_uri) {
    $url = "https://authz.constantcontact.com/oauth2/default/v1/token";
    $data = ["code" => $code, "redirect_uri" => $redirect_uri, "grant_type" => "authorization_code"];
    $auth = base64_encode("$client_id:$client_secret");

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic $auth",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function refreshAccessToken($client_id, $client_secret) {
    $refresh_token = get_option('cc_refresh_token');
    if (!$refresh_token) return "Error: No refresh token available.";

    $url = "https://authz.constantcontact.com/oauth2/default/v1/token";
    $data = ["refresh_token" => $refresh_token, "grant_type" => "refresh_token"];
    $auth = base64_encode("$client_id:$client_secret");

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic $auth",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $tokens = json_decode($response, true);
    if (isset($tokens['access_token'])) {
        update_option('cc_access_token', $tokens['access_token']);
        update_option('cc_refresh_token', $tokens['refresh_token']);
        update_option('cc_token_expiry', time() + $tokens['expires_in']);
        return $tokens['access_token'];
    }

    return "Error: Failed to refresh token.";
}

function revokeAuthorization() {
    delete_option('cc_access_token');
    delete_option('cc_refresh_token');
    delete_option('cc_token_expiry');
}
