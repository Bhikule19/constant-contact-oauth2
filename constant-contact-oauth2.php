<?php
/**
 * Plugin Name: Constant Contact OAuth Connect
 * Description: Adds a shortcode to handle OAuth connection with Constant Contact.
 * Version: 1.0
 * Author: Brainstorm Force
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Start the session if not already started
if (!session_id()) {
    session_start();
}

// Register Shortcode
add_shortcode('oauth_connect', 'Constant_Contact_Oauth_Connect');

// Remove header and footer for the OAuth page
add_action('template_redirect', 'Load_Blank_Template_For_Oauth_page');

/**
 * Loads a blank template for the OAuth page.
 *
 * This function checks if the current page is the OAuth page and, if so,
 * applies a filter to load a blank template, removing the header and footer.
 *
 */
function Load_Blank_Template_For_Oauth_page() {
    // Check if the current page is the OAuth page
    if (is_page('oauth-connect2')) { // Replace 'oauth-connect2' with your page slug
        add_filter('template_include', function($template) {
            // Load a blank template
            return plugin_dir_path(__FILE__) . 'blank-template.php';
        });
    }
}

/**
 * Handles the OAuth connection process for Constant Contact.
 *
 * This function renders a form for entering Constant Contact credentials,
 * processes the form submission, and handles the OAuth flow, including
 * generating access tokens and displaying them.
 * 
 */
function Constant_Contact_Oauth_Connect() {
    ob_start();
    
    // Retrieve stored credentials
    $client_id = get_option('cc_client_id');
    $client_secret = get_option('cc_client_secret');
    ?>

    <div class="container">

        <div class="prerequisite-box">
            <h2>🔧 Before You Start</h2>
            <p>Follow these steps before connecting to Constant Contact:</p>
            <ol>
                <li>Go to the <a href="https://developer.constantcontact.com/" target="_blank" rel="noopener noreferrer">Constant Contact App Portal</a>.</li>
                <li>Create a new application with a name of your choice.</li>
                <li>Copy the <strong>API Key (Client ID)</strong> from the app details page.</li>
                <li>Click <strong>Generate Client Secret</strong> and copy the code.</li>
                <li>Set the <strong>Redirect URI</strong> to:<br><code>https://www.convertpro.net/oauth-connect2/</code></li>
            </ol>
            <p>Once you've completed these steps, enter your credentials below.</p>
        </div>


        <h2 class="cc-header" >Connect to Constant Contact</h2>

        <form method="POST">
            <div class="form-group">
                <label for="client_id">Client ID</label>
                <input type="text" name="client_id" id="client_id" required value="">
            </div>
            <div class="form-group">
                <label for="client_secret">Client Secret</label>
                <input type="text" name="client_secret" id="client_secret" required value="">
            </div>

            <button type="submit" name="connect" class="btn">Connect to Constant Contact</button>
        </form>
    </div>

    <?php

    if (isset($_POST['connect'])) {
        $client_id = sanitize_text_field($_POST['client_id']);
        $client_secret = sanitize_text_field($_POST['client_secret']);
        update_option('cc_client_id', $client_id);
        update_option('cc_client_secret', $client_secret);

        //Generate state token and save in session
        $state = bin2hex(random_bytes(16));

        // Redirect to OAuth authorization URL
        $redirect_uri = home_url('/oauth-connect2/');
        $scope = rawurlencode("contact_data campaign_data offline_access");

        $auth_url = "https://authz.constantcontact.com/oauth2/default/v1/authorize?client_id={$client_id}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}";
        
        wp_redirect($auth_url);
        exit;
    }


    if (isset($_GET['code'])) {
        $client_id = get_option('cc_client_id');
        $client_secret = get_option('cc_client_secret');
        $code = sanitize_text_field($_GET['code']);
        $redirect_uri = home_url('/oauth-connect2/');

        $access_token_data = getAccessToken($code, $client_id, $client_secret, $redirect_uri);

        if ($access_token_data && isset($access_token_data['access_token'])) {
            update_option('cc_access_token', $access_token_data['access_token']);
            update_option('cc_refresh_token', $access_token_data['refresh_token']);
            update_option('cc_token_expiry', time() + $access_token_data['expires_in']);
            ?>
                <div class="notice success-msg">
                    Successfully Connected!
                </div>
            <?php
        } else {
            ?>
                <div class="notice error-msg">
                    The authorization code is invalid or has expired. Please enter valid credentials.
                </div>
            <?php
        }
    }

    ?>

    <!-- Revoke Access button -->
    <form method="POST">
        <button class="revoke_acces_btn" type="submit" name="revoke_access">Revoke Access</button>
    </form>
    <?php
    $access_token = get_option('cc_access_token');
    $refresh_token = get_option('cc_refresh_token');
    // $token_expiry = get_option('cc_token_expiry');
    
    ?> 
    
    <!-- Access Token Display -->
    <div class="container">
        <div class="token-output">
            <h3>Access Token</h3>
            <pre class="token-box" id="access_token_box">
                <?php 
                    echo $access_token ?: "Not Found";
                ?>
            </pre>
            <!-- <button id="copy-access" class="copy-btn" onclick="copyToken('access_token_box')">Copy</button> -->

            <h3>Refresh Token</h3>
            <pre class="token-box" id="refresh_token_box">
                <?php 
                    echo $refresh_token ?: "Not Found";
                ?>
            </pre>
            <!-- <button id="copy-refresh" class="copy-btn" onclick="copyToken('refresh_token_box')">Copy</button> -->
        </div>
    </div>

    <?php
    return ob_get_clean();
}

/**
 * Function to Exchange Authorization Code for Access Token.
 *
 * @param string $code The authorization code received from the OAuth server.
 * @param string $client_id The client ID of the Constant Contact application.
 * @param string $client_secret The client secret of the Constant Contact application.
 * @param string $redirect_uri The redirect URI registered with the Constant Contact application.
 * @return array|null The access token data or null if the request fails.
 */
function getAccessToken($code, $client_id, $client_secret, $redirect_uri) {
    $url = "https://authz.constantcontact.com/oauth2/default/v1/token";
    $data = [
        "code" => $code,
        "redirect_uri" => $redirect_uri,
        "grant_type" => "authorization_code"
    ];
    
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

/**
 * Function to Refresh Access Token.
 *
 * @param string $client_id The client ID of the Constant Contact application.
 * @param string $client_secret The client secret of the Constant Contact application.
 * @return string The new access token or an error message if the refresh fails.
 */
function refreshAccessToken($client_id, $client_secret) {
    $refresh_token = get_option('cc_refresh_token');

    if (!$refresh_token) {
        return "Error: No refresh token available.";
    }

    $url = "https://authz.constantcontact.com/oauth2/default/v1/token";
    $data = [
        "refresh_token" => $refresh_token,
        "grant_type" => "refresh_token"
    ];

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
    } else {
        return "Error: Failed to refresh token.";
    }
}

/**
 * Function to Revoke Authorization.
 *
 * This function deletes the stored access token, refresh token, 
 * and token expiry time from the WordPress options, effectively 
 * disconnecting the user from Constant Contact.
 */
function revokeAuthorization() {
    delete_option('cc_access_token');
    delete_option('cc_refresh_token');
    delete_option('cc_token_expiry');
}

// Handle Form Submission for Revoking Access
if (isset($_POST['revoke_access'])) {
    revokeAuthorization();
}

?>
