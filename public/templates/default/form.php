<?php
/**
 * Default assessment router (Step 1-5)
 */

if (!defined('ABSPATH')) {
    exit;
}

$iso42k_step = isset($_GET['iso42k_step']) ? sanitize_text_field($_GET['iso42k_step']) : 'step-1';

switch ($iso42k_step) {
    case 'step-2':
        include ISO42K_PLUGIN_DIR . 'public/templates/step-2.php';
        break;
    case 'step-3':
        include ISO42K_PLUGIN_DIR . 'public/templates/step-3.php';
        break;
    case 'results':
        include ISO42K_PLUGIN_DIR . 'public/templates/results.php';
        break;
    case 'step-1':
    default:
        include ISO42K_PLUGIN_DIR . 'public/templates/step-1.php';
        break;
}
