<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SneatMigrationCenter extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (
            !$this->session->userdata('id') ||
            (string) $this->session->userdata('hak_akses') !== '1'
        ) {
            redirect('Auth');
            return;
        }

        $this->load->model('Sneat_migration_model');
    }

    public function index()
    {
        $data = array(
            'status' => $this->Sneat_migration_model->status(),
            'legacy' => $this->Sneat_migration_model->legacyCandidates(),
        );

        $this->load->view('admin/sneat_migration/index', $data);
    }
}
