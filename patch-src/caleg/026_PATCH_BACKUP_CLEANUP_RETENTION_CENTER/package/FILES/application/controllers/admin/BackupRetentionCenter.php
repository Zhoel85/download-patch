<?php
defined('BASEPATH') or exit('No direct script access allowed');

class BackupRetentionCenter extends CI_Controller
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

        $this->load->model('Backup_retention_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
    }

    public function index()
    {
        $policy = $this->Backup_retention_model->defaultPolicy();
        $this->render($policy, false);
    }

    public function preview()
    {
        $policy = $this->policyFromPost();
        $this->render($policy, true);
    }

    public function cleanup()
    {
        $policy = $this->policyFromPost();
        $phrase = trim((string) $this->input->post('confirm_phrase', true));

        if ($phrase !== 'HAPUS BACKUP LAMA') {
            $this->session->set_flashdata(
                'backup_retention_error',
                'Konfirmasi tidak cocok. Ketik tepat: HAPUS BACKUP LAMA'
            );
            redirect('admin/BackupRetentionCenter');
            return;
        }

        $report = $this->Backup_retention_model->inventory($policy);
        $result = $this->Backup_retention_model->cleanup($report['items'], $policy);

        if ($result['failed'] > 0) {
            $this->session->set_flashdata(
                'backup_retention_error',
                'Cleanup selesai sebagian. Berhasil: ' .
                    $result['deleted'] .
                    ', gagal: ' .
                    $result['failed'] .
                    '. Periksa log.'
            );
        } else {
            $this->session->set_flashdata(
                'backup_retention_success',
                'Cleanup berhasil. ' .
                    $result['deleted'] .
                    ' item dihapus, ruang dibebaskan sekitar ' .
                    $this->Backup_retention_model->formatBytes($result['freed_bytes']) .
                    '.'
            );
        }

        redirect('admin/BackupRetentionCenter');
    }

    private function render($policy, $preview)
    {
        $report = $this->Backup_retention_model->inventory($policy);

        $data = array(
            'policy' => $policy,
            'report' => $report,
            'preview' => (bool) $preview,
            'history' => $this->Backup_retention_model->recentLogs(20),
        );

        $this->load->view('admin/backup_retention/index', $data);
    }

    private function policyFromPost()
    {
        $defaults = $this->Backup_retention_model->defaultPolicy();

        return array(
            'keep_days' => $this->clampInt(
                $this->input->post('keep_days', true),
                1,
                3650,
                $defaults['keep_days']
            ),
            'keep_source' => $this->clampInt(
                $this->input->post('keep_source', true),
                1,
                50,
                $defaults['keep_source']
            ),
            'keep_database' => $this->clampInt(
                $this->input->post('keep_database', true),
                1,
                50,
                $defaults['keep_database']
            ),
            'keep_deployment' => $this->clampInt(
                $this->input->post('keep_deployment', true),
                1,
                100,
                $defaults['keep_deployment']
            ),
            'keep_baseline' => $this->clampInt(
                $this->input->post('keep_baseline', true),
                1,
                20,
                $defaults['keep_baseline']
            ),
            'keep_sneat' => $this->clampInt(
                $this->input->post('keep_sneat', true),
                1,
                10,
                $defaults['keep_sneat']
            ),
        );
    }

    private function clampInt($value, $min, $max, $fallback)
    {
        if (!is_numeric($value)) {
            return (int) $fallback;
        }

        $value = (int) $value;

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }
}
