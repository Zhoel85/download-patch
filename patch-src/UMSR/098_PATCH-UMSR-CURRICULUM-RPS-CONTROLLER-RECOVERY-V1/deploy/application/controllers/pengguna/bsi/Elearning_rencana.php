<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Elearning_plan_controller.php';

/**
 * PATCH 098 — Recovery controller.
 *
 * Restore the original thin-controller architecture used by Curriculum/RPS:
 * all legacy index/master/RPS routing and execution stays inside
 * Elearning_plan_controller. PATCH 095 auxiliary model/views/tables may remain
 * installed but are intentionally not loaded here until reintegration is
 * proven separately.
 */
class Elearning_rencana extends Elearning_plan_controller
{
}
