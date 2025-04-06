<?php
/**
 * Template Name: Blank Template
 */

if (!defined('ABSPATH')) {
    exit;
}

?><!DOCTYPE html>
<html >
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            padding: 50px;
            font-family: 'Catamaran', sans-serif;
            font-size: medium;
            background: #f5f7fa;
            /* height: 100vh; */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            padding: 30px;
            width: 100%;
            max-width: 600px;
        }

        .prerequisite-box {
            background-color: #f9f9ff;
            border-left: 5px solid #7252df;
            padding: 20px;
            margin: 30px auto;
            font-family: Catamaran, sans-serif;
            max-width: 700px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .prerequisite-box h2 {
            color: #333;
            margin-bottom: 12px;
        }

        .prerequisite-box ol {
            padding-left: 20px;
        }

        .prerequisite-box li {
            margin-bottom: 10px;
        }

        .prerequisite-box code {
            background: #eee;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 0.95em;
        }


        h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }

        .btn {
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

        .btn:hover {
            background-color: #5c3ddf;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        pre {
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

        .copy-btn {
        background: #7252df;
        color: white;
        border: none;
        padding: 6px 10px;
        font-size: 12px;
        border-radius: 4px;
        cursor: pointer;
        }

        .revoke_acces_btn{
            background: #ff4d4d;
            color: white;
            border: none;
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 30px;
        }

        .notice {
            padding: 15px 20px;
            margin: 0px 0px 30px 30px;
            /* margin-left: 30px; */
            max-width: 600px;
            font-family: Catamaran, sans-serif;
            font-size: 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .success-msg {
            background-color: #e6f7e9;
            color: #2e7d32;
            border: 5px solid #4caf50;
        }

        .error-msg {
            background-color: #fdecea;
            color: #c62828;
            border: 5px solid #f44336;
        }

    </style>
</head>
<body <?php body_class(); ?>>
    <main class="oauth-page-wrapper">
        <?php
        while (have_posts()) : the_post();
            the_content(); // This will display your HTML form
        endwhile;
        ?>
    </main>
</body>
</html>
