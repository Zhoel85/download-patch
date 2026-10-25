<?php
defined('BASEPATH') or exit('No direct script access allowed');

$hook['post_controller_constructor'][] = array(
    'class' => 'SecurityAccessHook',
    'function' => 'handle',
    'filename' => 'SecurityAccessHook.php',
    'filepath' => 'hooks',
);

$hook['post_controller_constructor'][] = array(
    'class' => 'LegacyDependencyHook',
    'function' => 'handle',
    'filename' => 'LegacyDependencyHook.php',
    'filepath' => 'hooks',
);

$hook['display_override'][] = array(
    'class' => 'FinalNavigationHook',
    'function' => 'render',
    'filename' => 'FinalNavigationHook.php',
    'filepath' => 'hooks',
);
