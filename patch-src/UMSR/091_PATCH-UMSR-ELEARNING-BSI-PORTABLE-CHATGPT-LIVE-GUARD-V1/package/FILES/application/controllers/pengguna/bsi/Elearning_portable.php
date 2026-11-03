<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PATCH 091 - BSI Portable ChatGPT workflow.
 *
 * This controller is deliberately separate from pengguna/bsi/Elearning so the
 * current class controller and the Dosen controller stay untouched.
 */
class Elearning_portable extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Elearning_bsi_portable_model', 'portable');

        $method = (string)$this->router->fetch_method();
        if ($method !== 'material' && (string)$this->session->userdata('hak_akses') !== '17') {
            $this->session->set_flashdata('gagal', 'Akses khusus BSI.');
            redirect(base_url('Auth'));
        }
    }

    private function class_url($idJadwal)
    {
        return base_url('pengguna/bsi/Elearning/kelas/' . (int)$idJadwal);
    }

    private function require_tables($idJadwal)
    {
        if (!$this->portable->tables_ready()) {
            $this->session->set_flashdata(
                'gagal',
                'Tabel PATCH 091 belum tersedia. Jalankan SQL 091_ELEARNING_BSI_PORTABLE_V3_INSTALL.sql.'
            );
            redirect($this->class_url($idJadwal));
        }
    }

    private function render_bsi($view, array $data)
    {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/menu', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    public function download_instruction($idJadwal = 0)
    {
        $idJadwal = (int)$idJadwal;
        if ($idJadwal <= 0) {
            show_404();
        }

        $context = $this->portable->build_locked_context($idJadwal);
        if (empty($context['ready'])) {
            $this->session->set_flashdata('gagal', implode(' ', $context['errors']));
            redirect($this->class_url($idJadwal));
        }

        $text = $this->portable->build_instruction_text($context);
        $period = preg_replace('/[^0-9A-Za-z_-]/', '', (string)$context['class']['periode']);
        $filename = 'PERINTAH_CHATGPT_ELEARNING_' . $idJadwal . '_' . $period . '.txt';

        $this->output
            ->set_content_type('text/plain', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_output($text);
    }

    public function upload($idJadwal = 0)
    {
        $idJadwal = (int)$idJadwal;
        if ($idJadwal <= 0) {
            show_404();
        }
        $this->require_tables($idJadwal);

        $context = $this->portable->build_locked_context($idJadwal);
        $data = array(
            'judul' => 'Upload JSON E-Learning BSI',
            'id_jadwal' => $idJadwal,
            'context' => $context,
            'errors' => array()
        );
        $this->render_bsi('pengguna/bsi/elearning/portable_upload', $data);
    }

    public function preview($idJadwal = 0)
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
        }

        $idJadwal = (int)$idJadwal;
        if ($idJadwal <= 0) {
            show_404();
        }
        $this->require_tables($idJadwal);

        $context = $this->portable->build_locked_context($idJadwal);
        $errors = array();

        if (empty($context['ready'])) {
            $errors = $context['errors'];
        }

        if (!isset($_FILES['json_file']) || !is_array($_FILES['json_file'])) {
            $errors[] = 'File JSON belum dipilih.';
        } else {
            $file = $_FILES['json_file'];
            if ((int)$file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload file gagal (kode ' . (int)$file['error'] . ').';
            }
            if ((int)$file['size'] <= 0 || (int)$file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran JSON harus lebih dari 0 dan maksimal 5 MB.';
            }
            $name = isset($file['name']) ? (string)$file['name'] : '';
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
                $errors[] = 'File wajib berekstensi .json.';
            }
        }

        $package = null;
        if (!$errors) {
            $raw = file_get_contents($_FILES['json_file']['tmp_name']);
            if ($raw === false || trim($raw) === '') {
                $errors[] = 'File JSON kosong/tidak dapat dibaca.';
            } else {
                $package = json_decode($raw, true);
                if (!is_array($package) || json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'JSON tidak valid: ' . json_last_error_msg();
                }
            }
        }

        $validation = null;
        if (!$errors && is_array($package)) {
            $validation = $this->portable->validate_package($package, $context);
            if (empty($validation['ok'])) {
                $errors = $validation['errors'];
            }
        }

        if ($errors) {
            $data = array(
                'judul' => 'Upload JSON E-Learning BSI',
                'id_jadwal' => $idJadwal,
                'context' => $context,
                'errors' => $errors
            );
            $this->render_bsi('pengguna/bsi/elearning/portable_upload', $data);
            return;
        }

        $preview = $this->portable->build_preview($idJadwal, $package);
        $batchId = $this->portable->create_preview_batch($idJadwal, $package, $context, $preview);

        $data = array(
            'judul' => 'Preflight JSON E-Learning BSI',
            'id_jadwal' => $idJadwal,
            'context' => $context,
            'validation' => $validation,
            'preview' => $preview,
            'batch_id' => $batchId
        );
        $this->render_bsi('pengguna/bsi/elearning/portable_preview', $data);
    }

    public function apply($idJadwal = 0, $batchId = 0)
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
        }

        $idJadwal = (int)$idJadwal;
        $batchId = (int)$batchId;
        if ($idJadwal <= 0 || $batchId <= 0) {
            show_404();
        }
        $this->require_tables($idJadwal);

        $result = $this->portable->apply_batch($batchId, $idJadwal);
        if (empty($result['ok'])) {
            $this->session->set_flashdata('gagal', isset($result['message']) ? $result['message'] : 'Paket gagal diterapkan.');
            redirect(base_url('pengguna/bsi/Elearning_portable/history/' . $idJadwal));
        }

        $stats = isset($result['stats']) ? $result['stats'] : array();
        $message = 'Paket berhasil diterapkan secara aman.';
        if ($stats) {
            $message .= ' Pertemuan baru: ' . (int)$stats['meetings_created']
                . ', materi ditempel: ' . (int)$stats['materials_attached']
                . ', tugas disiapkan: ' . (int)$stats['tasks_prepared']
                . ', soal PG ditempel: ' . (int)$stats['exam_questions_attached'] . '.';
        }

        $this->session->set_flashdata('sukses', $message);
        redirect($this->class_url($idJadwal));
    }

    public function history($idJadwal = 0)
    {
        $idJadwal = (int)$idJadwal;
        if ($idJadwal <= 0) {
            show_404();
        }
        $this->require_tables($idJadwal);

        $context = $this->portable->build_locked_context($idJadwal);
        $data = array(
            'judul' => 'Riwayat Import E-Learning BSI',
            'id_jadwal' => $idJadwal,
            'context' => $context,
            'history' => $this->portable->get_history($idJadwal, 50)
        );
        $this->render_bsi('pengguna/bsi/elearning/portable_history', $data);
    }

    public function material($token = '')
    {
        $token = trim((string)$token);
        if ($token === '' || !preg_match('/^[a-f0-9]{48}$/i', $token)) {
            show_404();
        }

        $content = $this->portable->get_generated_by_token($token);
        if (!$content) {
            show_404();
        }
        if (!$this->portable->can_read_generated($content)) {
            show_error('Anda tidak berhak membaca materi ini.', 403);
        }

        $payload = json_decode($content['content_json'], true);
        if (!is_array($payload)) {
            show_error('Konten materi tidak valid.', 500);
        }

        $data = array(
            'title' => $content['title'],
            'payload' => $payload,
            'content' => $content
        );
        $this->load->view('pengguna/bsi/elearning/portable_material', $data);
    }
}
