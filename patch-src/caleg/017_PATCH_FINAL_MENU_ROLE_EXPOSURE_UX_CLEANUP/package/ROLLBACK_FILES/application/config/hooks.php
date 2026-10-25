<?php
defined('BASEPATH') or exit('No direct script access allowed');

$hook['post_controller_constructor'][] = array(
    'class' => 'SecurityAccessHook',
    'function' => 'handle',
    'filename' => 'SecurityAccessHook.php',
    'filepath' => 'hooks',
);
