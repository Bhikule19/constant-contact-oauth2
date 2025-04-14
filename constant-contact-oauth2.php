<?php
/**
 * Plugin Name: Constant Contact OAuth Connect
 * Description: Adds a shortcode to handle OAuth connection with Constant Contact.
 * Version: 1.0
 * Author: Brainstorm Force
 * 
 * This plugin facilitates OAuth 2.0 authentication with Constant Contact API.
 * It provides a shortcode [oauth_connect] to render a form for connecting to 
 * Constant Contact, authorizing the application, and retrieving access tokens.
 */

 // Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Start PHP session if not already started.
if (!session_id()) {
    session_start();
}

// Register Shortcode for OAuth connection form.
add_shortcode('oauth_connect', 'Constant_Contact_Oauth_Connect');

// Remove header and footer for the OAuth page.
add_action('template_redirect', 'Load_Blank_Template_For_Oauth_page');

/**
 * Loads a blank template for the OAuth page.
 *
 * This function checks if the current page is the OAuth page and, if so,
 * applies a filter to load a blank template, removing the header and footer.
 *
 */
function Load_Blank_Template_For_Oauth_page() {
    if (is_page('oauth-connect2')) {
        add_filter('template_include', function($template) {
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

    // Handle the initial form submission to start the OAuth flow.
    if (isset($_POST['connect'])) {
        $_SESSION['cc_client_id'] = sanitize_text_field($_POST['client_id']);
        $_SESSION['cc_client_secret'] = base64_encode(sanitize_text_field($_POST['client_secret']));
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

        $redirect_uri = home_url('/oauth-connect2/');
        $scope = rawurlencode("contact_data campaign_data offline_access");

        // Authorization URL.
        $auth_url = "https://authz.constantcontact.com/oauth2/default/v1/authorize?client_id={$_SESSION['cc_client_id']}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$_SESSION['oauth_state']}";

        if (ob_get_length()) ob_end_clean();
        wp_redirect($auth_url);
        exit;
    }

    ob_start();

    $client_id     = $_SESSION['cc_client_id'] ?? null;
    $client_secret = isset($_SESSION['cc_client_secret']) ? base64_decode($_SESSION['cc_client_secret']) : null;
    $access_token  = $_SESSION['cc_access_token'] ?? null;
    $refresh_token = $_SESSION['cc_refresh_token'] ?? null;
    $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : null;

    $error_message = '';

    // Check if the authorization code is present in the URL.
    if ($code && empty($_SESSION['token_displayed'])) {
        // Exchange authorization code for access token.
        $access_token_data = getAccessToken($code, $client_id, $client_secret, home_url('/oauth-connect2/'));
        if ($access_token_data && isset($access_token_data['access_token'])) {
            $_SESSION['cc_access_token'] = $access_token_data['access_token'];
            $_SESSION['cc_refresh_token'] = $access_token_data['refresh_token'];
            $_SESSION['token_displayed'] = true;
        } else {
            // Handle error if token exchange fails.
            $error_message = 'The authorization code is invalid or has expired. Please enter valid credentials.';
        }
    } elseif ($code && !empty($_SESSION['token_displayed'])) {
        // If the token has already been displayed, do not process it again.
        $error_message = 'The authorization code is invalid or has expired. Please enter valid credentials.';
        $_SESSION['token_displayed'] = null;
    }

    // Handle return to credentials form.
    if (isset($_POST['go_back'])) {
        $_SESSION['token_displayed'] = null;
    }

    // Output the HTML for the OAuth connection form.
    echo '<div class="cppro-cc-main-block"><div class="cppro-cc-container">';

    if (!empty($error_message)) {
        echo '<div class="cppro-cc-notice cppro-cc-error-msg">' . esc_html($error_message) . '</div>';
    }

    // Display token information if available.
    if (!empty($_SESSION['token_displayed'])) {
        // Display the access and refresh tokens.
        $access_token = $_SESSION['cc_access_token'] ?? null;
        $refresh_token = $_SESSION['cc_refresh_token'] ?? null;
        $output = '<form method="POST"><button class="cppro-cc-revoke_acces_btn" type="submit" name="go_back">Enter Client Credentials</button></form>';
        $output .= '<div class="token-output">';
        $output .= '<h3>Access Token</h3><pre class="ccpro-cc-token-box">' . esc_html($access_token ?: 'Not Found') . '</pre>';
        $output .= '<h3>Refresh Token</h3><pre class="ccpro-cc-token-box">' . esc_html($refresh_token ?: 'Not Found') . '</pre>';
        $output .= '</div>';
        
        echo $output;
    } else {
        // Display the credentials form and instructions.
        $output = '<div class="cppro-cc-prerequisite-box">';
        $output .= '<h2 class="cppro-cc-header">🔧 Before You Start</h2>';
        $output .= '<p>Follow these steps before connecting to Constant Contact:</p>';
        $output .= '<ol>';
        $output .= '<li>Go to the <a href="https://developer.constantcontact.com/" target="_blank">Constant Contact App Portal</a>.</li>';
        $output .= '<li>Create a new application and copy the Client ID and Secret.</li>';
        $output .= '<li>Set the Redirect URI to: <code>' . esc_url(home_url('/oauth-connect2/')) . '</code></li>';
        $output .= '</ol>';
        $output .= '<p>Enter your credentials below.</p>';
        $output .= '</div>';
        
        $output .= '<h2 class="cc-header">Connect to Constant Contact</h2>';
        $output .= '<form method="POST">';
        $output .= '<div class="form-group"><label for="client_id">Client ID</label>';
        $output .= '<input type="text" name="client_id" id="client_id" required></div>';
        $output .= '<div class="form-group"><label for="client_secret">Client Secret</label>';
        $output .= '<input type="text" name="client_secret" id="client_secret" required></div>';
        $output .= '<button type="submit" name="connect" class="cppro-cc-connect-btn">Connect to Constant Contact</button>';
        $output .= '</form>';
        
        echo $output;
    }

    echo '</div></div>';

    $content = ob_get_clean(); // store output.
    if (!empty($_SESSION['token_displayed'])) {
        // Clear session variables after displaying the tokens.
        unset($_SESSION['cc_access_token']);
        unset($_SESSION['cc_refresh_token']);
        unset($_SESSION['cc_client_id']);
        unset($_SESSION['cc_client_secret']);
        unset($_SESSION['cc_token_expiry']);
        unset($_SESSION['token_displayed']);
    }
    
    return $content; // finally return cleaned output.
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
    // Prepare the token endpoint URL and request data.
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