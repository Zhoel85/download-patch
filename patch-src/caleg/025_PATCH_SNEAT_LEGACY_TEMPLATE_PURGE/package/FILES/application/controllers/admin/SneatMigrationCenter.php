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
            'manifest' => $this->Sneat_migration_model->cleanupManifest(),
        );

        $this->load->view('admin/sneat_migration/index', $data);
    }

    public function finalize_cleanup()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') {
            show_404();
            return;
        }

        $result = $this->Sneat_migration_model->cleanup();

        $this->session->set_flashdata(
            'success',
            'Cleanup template lama selesai. Dipindahkan: ' .
                count($result['moved']) .
                ', dipertahankan: ' .
                count($result['kept']) .
                '.'
        );

        redirect('admin/SneatMigrationCenter');
    }

    public function restore_legacy()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') {
            show_404();
            return;
        }

        $result = $this->Sneat_migration_model->restore();

        if (!empty($result['failed'])) {
            $this->session->set_flashdata(
                'error',
                'Restore selesai dengan kegagalan pada ' .
                    count($result['failed']) .
                    ' file.'
            );
        } else {
            $this->session->set_flashdata(
                'success',
                'Backup template legacy berhasil dipulihkan: ' .
                    count($result['restored']) .
                    ' file.'
            );
        }

        redirect('admin/SneatMigrationCenter');
    }
}
