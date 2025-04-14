<?php
/**
 * Template Name: Blank Template
 */

if (!defined('ABSPATH')) {
    exit;
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .cppro-cc-main-block {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
        }

        .cppro-cc-container {
            margin: 0;
            padding: 30px;
            width: 100%;
            max-width: 600px;
        }

        .cppro-cc-main-block .cppro-cc-container .cppro-cc-prerequisite-box {
            background-color: #f9f9ff;
            border-left: 5px solid #7252df;
            padding: 20px;
            margin: 30px auto;
            font-family: Catamaran, sans-serif;
            max-width: 700px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .cppro-cc-main-block .cppro-cc-container .cppro-cc-prerequisite-box h2 {
            color: #333;
            margin-bottom: 12px;
        }

        .cppro-cc-main-block .cppro-cc-container .cppro-cc-prerequisite-box ol {
            padding-left: 20px;
        }

        .cppro-cc-main-block .cppro-cc-container .cppro-cc-prerequisite-box li {
            margin-bottom: 10px;
        }

        .cppro-cc-main-block .cppro-cc-container .cppro-cc-prerequisite-box code {
            background: #eee;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 0.95em;
        }


        .cppro-cc-main-block .cppro-cc-container .cppro-cc-header {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }

        .cppro-cc-main-block .cppro-cc-container .cppro-cc-connect-btn {
            background-color: #7252df;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .cppro-cc-main-block .cppro-cc-container .cppro-cc-connect-btn:hover {
            background-color: #5c3ddf;
        }
        .cppro-cc-main-block .cppro-cc-container .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .cppro-cc-container .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .cppro-cc-main-block .cppro-cc-container input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .ccpro-cc-token-box {
            display: block;
            padding: 9.5px;
            margin: 0 0 10px;
            font-size: 13px;
            line-height: 1.42857143;
            color: #333;
            word-break: break-all;
            word-wrap: break-word;
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 4px;
            white-space: break-spaces;
            max-width: 100%;
            overflow: auto;
        }

        .cppro-cc-revoke_acces_btn{
            background-color: #7252df;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .cppro-cc-notice {
            padding: 15px 20px;
            max-width: 600px;
            font-family: Catamaran, sans-serif;
            font-size: 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .cppro-cc-error-msg {
            background-color: #fdecea;
            color: #c62828;
            border: 5px solid #f44336;
        }

    </style>
</head>
<body>
    <main class="oauth-page-wrapper">
        <?php
        while (have_posts()) : the_post();
            the_content(); // This will display your HTML form.
        endwhile;
        ?>
    </main>
</body>
</html>
