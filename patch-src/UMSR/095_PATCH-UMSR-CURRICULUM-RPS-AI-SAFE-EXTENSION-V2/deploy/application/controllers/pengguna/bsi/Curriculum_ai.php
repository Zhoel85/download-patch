<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PATCH 095 V2 SAFE EXTENSION
 *
 * IMPORTANT:
 * - does NOT replace Elearning_rencana.php;
 * - does NOT extend/override Elearning_plan_controller;
 * - does NOT add _remap();
 * - does NOT modify global routes;
 * - isolated BSI role-17 entry for Foundation gate + AI Curriculum workflow.
 */
class Curriculum_ai extends CI_Controller
{
    private $m095;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Curriculum_ai_095_model', 'curriculum_ai_095');
        $this->m095 = $this->curriculum_ai_095;
        $this->load->helper(array('url', 'form'));
    }

    private function requireBsi()
    {
        if ((string) $this->session->userdata('hak_akses') !== '17') {
            show_error('Akses khusus Administrator BSI.', 403);
            return false;
        }
        return true;
    }

    private function baseUrl()
    {
        return base_url('pengguna/bsi/Curriculum_ai');
    }

    private function csrf()
    {
        return array(
            'name' => isset($this->security) ? $this->security->get_csrf_token_name() : '',
            'hash' => isset($this->security) ? $this->security->get_csrf_hash() : ''
        );
    }

    private function render($view, array $data = array(), $status = 200)
    {
        $data += array(
            'judul' => 'Kurikulum & RPS OBE',
            'base095' => $this->baseUrl(),
            'csrf095' => $this->csrf()
        );

        $this->output->set_status_header((int) $status);
        $this->load->view('templates/header', $data);
        $this->load->view('templates/menu', $data);
        $this->load->view('elearning_plan/curriculum_ai_safe/' . $view, $data);
        $this->load->view('templates/footer', $data);
    }

    private function fail($title, $message, $status = 422, $prodiId = 0)
    {
        log_message('error', 'CURRICULUM_AI_095_SAFE: ' . $title . ' | ' . $message);
        $this->render('error', array(
            'error_title' => $title,
            'error_message' => $message,
            'prodi_id' => (int) $prodiId
        ), $status);
    }

    private function requireGet()
    {
        if ($this->input->method(true) !== 'GET') {
            show_error('Metode permintaan tidak diizinkan.', 405);
            return false;
        }
        return true;
    }

    private function requirePost()
    {
        if ($this->input->method(true) !== 'POST') {
            show_error('Metode permintaan tidak diizinkan.', 405);
            return false;
        }
        return true;
    }

    private function foundationOrFail($prodiId)
    {
        $foundation = $this->m095->foundationStatus((int) $prodiId);
        if (empty($foundation['complete'])) {
            return array(false, $foundation);
        }
        return array(true, $foundation);
    }

    private function curriculumOrFail($prodiId, $curriculumId)
    {
        $curriculum = $this->m095->curriculum((int) $curriculumId);
        if (!$curriculum) {
            return array(false, null, 'Versi Kurikulum tidak ditemukan.');
        }

        $programField = null;
        foreach (array('prodi_id', 'id_prodi', 'program_id') as $candidate) {
            if (array_key_exists($candidate, $curriculum)) {
                $programField = $candidate;
                break;
            }
        }
        if ($programField && (int) $curriculum[$programField] !== (int) $prodiId) {
            return array(false, null, 'Versi Kurikulum bukan milik Program Studi ini.');
        }
        return array(true, $curriculum, '');
    }

    public function health()
    {
        if (!$this->requireBsi() || !$this->requireGet()) {
            return;
        }

        $checks = array(
            'prodi' => $this->db->table_exists('prodi'),
            'foundation' => $this->db->table_exists('prodi_academic_foundation'),
            'profil_lulusan' => $this->db->table_exists('prodi_profil_lulusan'),
            'peo' => $this->db->table_exists('prodi_peo'),
            'curriculum' => $this->db->table_exists('elbsi_curriculum'),
            'curriculum_foundation' => $this->db->table_exists('elbsi_curriculum_foundation'),
            'ai_import_audit' => $this->db->table_exists('elearning_curriculum_ai_import')
        );

        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array(
                'ok' => !in_array(false, $checks, true),
                'patch' => '095_V2_SAFE_EXTENSION',
                'legacy_controller_modified' => false,
                'checks' => $checks
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function index()
    {
        if (!$this->requireBsi() || !$this->requireGet()) {
            return;
        }

        try {
            $programs = array();
            foreach ($this->m095->programList() as $program) {
                $id = isset($program['id']) ? (int) $program['id'] : 0;
                if ($id <= 0) {
                    continue;
                }
                $foundation = $this->m095->foundationStatus($id);
                $programs[] = array(
                    'program' => $program,
                    'foundation' => $foundation,
                    'curriculum_count' => count($this->m095->listCurricula($id))
                );
            }

            $this->render('index', array('programs' => $programs));
        } catch (Throwable $e) {
            $this->fail('Kurikulum & RPS OBE', $e->getMessage(), 500);
        }
    }

    public function prodi($prodiId = 0)
    {
        if (!$this->requireBsi() || !$this->requireGet()) {
            return;
        }

        $prodiId = (int) $prodiId;
        if ($prodiId <= 0) {
            show_404();
            return;
        }

        try {
            $foundation = $this->m095->foundationStatus($prodiId);
            $curricula = array();

            if (!empty($foundation['complete'])) {
                foreach ($this->m095->listCurricula($prodiId) as $row) {
                    $id = isset($row['id']) ? (int) $row['id'] : 0;
                    if ($id <= 0) {
                        continue;
                    }

                    try {
                        $completion = $this->m095->completeness($prodiId, $id);
                    } catch (Throwable $e) {
                        $completion = array(
                            'percent' => 0,
                            'complete' => false,
                            'curriculum_complete' => false,
                            'missing' => array('Pemeriksaan kelengkapan gagal: ' . $e->getMessage()),
                            'rps' => array('ready' => 0, 'total' => 0)
                        );
                    }

                    $curriculum = $this->m095->curriculum($id);
                    $binding = $this->m095->bindingStatus($id, $foundation);

                    $curricula[] = array(
                        'row' => $row,
                        'curriculum' => $curriculum,
                        'completion' => $completion,
                        'binding' => $binding,
                        'immutable' => $curriculum ? $this->m095->isImmutable($curriculum) : false
                    );
                }
            }

            $this->render('prodi', array(
                'prodi_id' => $prodiId,
                'foundation' => $foundation,
                'curricula' => $curricula
            ));
        } catch (Throwable $e) {
            $this->fail('Program Studi tidak dapat diperiksa', $e->getMessage(), 500, $prodiId);
        }
    }

    public function bind($prodiId = 0, $curriculumId = 0)
    {
        if (!$this->requireBsi() || !$this->requirePost()) {
            return;
        }

        try {
            list($ok, $foundation) = $this->foundationOrFail($prodiId);
            if (!$ok) {
                throw new RuntimeException('Fondasi Akademik Prodi belum lengkap: ' . implode(' ', $foundation['missing']));
            }
            list($currOk, $curriculum, $message) = $this->curriculumOrFail($prodiId, $curriculumId);
            if (!$currOk) {
                throw new RuntimeException($message);
            }
            $this->m095->bindFoundation((int) $curriculumId, $foundation);
            $this->session->set_flashdata('berhasil', 'Foundation ACTIVE berhasil dikunci ke Kurikulum DRAFT.');
            redirect($this->baseUrl() . '/prodi/' . (int) $prodiId);
        } catch (Throwable $e) {
            $this->fail('Gagal mengikat Foundation', $e->getMessage(), 422, $prodiId);
        }
    }

    public function revision($prodiId = 0, $curriculumId = 0)
    {
        if (!$this->requireBsi() || !$this->requirePost()) {
            return;
        }

        try {
            list($ok, $foundation) = $this->foundationOrFail($prodiId);
            if (!$ok) {
                throw new RuntimeException('Fondasi Akademik Prodi belum lengkap: ' . implode(' ', $foundation['missing']));
            }
            $newId = $this->m095->cloneAsDraft((int) $prodiId, (int) $curriculumId);
            $this->session->set_flashdata('berhasil', 'Revisi DRAFT dibuat dari versi existing. Versi ACTIVE tidak diubah.');
            redirect($this->baseUrl() . '/prodi/' . (int) $prodiId . '?master_id=' . $newId);
        } catch (Throwable $e) {
            $this->fail('Gagal membuat revisi DRAFT', $e->getMessage(), 422, $prodiId);
        }
    }

    public function command($prodiId = 0, $curriculumId = 0)
    {
        if (!$this->requireBsi() || !$this->requireGet()) {
            return;
        }

        try {
            list($ok, $foundation) = $this->foundationOrFail($prodiId);
            if (!$ok) {
                throw new RuntimeException('Fondasi Akademik Prodi belum lengkap.');
            }
            list($currOk, $curriculum, $message) = $this->curriculumOrFail($prodiId, $curriculumId);
            if (!$currOk) {
                throw new RuntimeException($message);
            }
            if ($this->m095->isImmutable($curriculum)) {
                throw new RuntimeException('Versi ACTIVE/APPROVED bersifat read-only. Buat revisi DRAFT dahulu.');
            }

            $binding = $this->m095->bindingStatus((int) $curriculumId, $foundation);
            if (empty($binding['current'])) {
                throw new RuntimeException('Kurikulum DRAFT belum dikunci ke Foundation ACTIVE saat ini.');
            }

            $text = $this->m095->commandText((int) $prodiId, (int) $curriculumId);
            $name = isset($foundation['program']['nama_prodi']) ? $foundation['program']['nama_prodi'] : ('PRODI_' . (int) $prodiId);
            $filename = 'PERINTAH_CHATGPT_KURIKULUM_'
                . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $name)
                . '_' . (int) $curriculumId . '.txt';

            $this->output
                ->set_content_type('text/plain', 'utf-8')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_header('X-Content-Type-Options: nosniff')
                ->set_output($text);
        } catch (Throwable $e) {
            $this->fail('Perintah AI tidak dapat dibuat', $e->getMessage(), 422, $prodiId);
        }
    }

    public function upload($prodiId = 0, $curriculumId = 0)
    {
        if (!$this->requireBsi() || !$this->requireGet()) {
            return;
        }

        try {
            list($ok, $foundation) = $this->foundationOrFail($prodiId);
            if (!$ok) {
                throw new RuntimeException('Fondasi Akademik Prodi belum lengkap.');
            }
            list($currOk, $curriculum, $message) = $this->curriculumOrFail($prodiId, $curriculumId);
            if (!$currOk) {
                throw new RuntimeException($message);
            }
            if ($this->m095->isImmutable($curriculum)) {
                throw new RuntimeException('Upload JSON hanya untuk Kurikulum DRAFT.');
            }

            $binding = $this->m095->bindingStatus((int) $curriculumId, $foundation);
            if (empty($binding['current'])) {
                throw new RuntimeException('Kurikulum DRAFT belum dikunci ke Foundation ACTIVE.');
            }

            $this->render('upload', array(
                'prodi_id' => (int) $prodiId,
                'curriculum_id' => (int) $curriculumId,
                'foundation' => $foundation,
                'curriculum' => $curriculum,
                'completion' => $this->m095->completeness((int) $prodiId, (int) $curriculumId)
            ));
        } catch (Throwable $e) {
            $this->fail('Upload JSON belum dapat digunakan', $e->getMessage(), 422, $prodiId);
        }
    }

    public function preview($prodiId = 0, $curriculumId = 0)
    {
        if (!$this->requireBsi() || !$this->requirePost()) {
            return;
        }

        try {
            list($ok, $foundation) = $this->foundationOrFail($prodiId);
            if (!$ok) {
                throw new RuntimeException('Fondasi Akademik Prodi belum lengkap.');
            }
            list($currOk, $curriculum, $message) = $this->curriculumOrFail($prodiId, $curriculumId);
            if (!$currOk) {
                throw new RuntimeException($message);
            }
            if ($this->m095->isImmutable($curriculum)) {
                throw new RuntimeException('JSON hanya dapat diterapkan ke Kurikulum DRAFT.');
            }

            if (!isset($_FILES['json_file']) || !is_array($_FILES['json_file'])) {
                throw new RuntimeException('File JSON belum dipilih.');
            }
            $file = $_FILES['json_file'];
            if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload JSON gagal (kode ' . (int) $file['error'] . ').');
            }
            if ((int) $file['size'] <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
                throw new RuntimeException('Ukuran JSON harus lebih dari 0 dan maksimal 5 MB.');
            }
            if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'json') {
                throw new RuntimeException('File wajib berekstensi .json.');
            }

            $raw = file_get_contents($file['tmp_name']);
            $package = json_decode((string) $raw, true);
            if (!is_array($package) || json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('JSON tidak valid: ' . json_last_error_msg());
            }

            $preview = $this->m095->previewPackage((int) $prodiId, (int) $curriculumId, $package);
            $batchId = $this->m095->savePreview((int) $prodiId, (int) $curriculumId, $package, $preview);

            $this->render('preview', array(
                'prodi_id' => (int) $prodiId,
                'curriculum_id' => (int) $curriculumId,
                'foundation' => $foundation,
                'batch_id' => $batchId,
                'preview' => $preview
            ));
        } catch (Throwable $e) {
            $this->fail('Preflight JSON gagal', $e->getMessage(), 422, $prodiId);
        }
    }

    public function apply($prodiId = 0, $batchId = 0)
    {
        if (!$this->requireBsi() || !$this->requirePost()) {
            return;
        }

        try {
            list($ok, $foundation) = $this->foundationOrFail($prodiId);
            if (!$ok) {
                throw new RuntimeException('Fondasi Akademik Prodi belum lengkap.');
            }
            $result = $this->m095->applyPreview((int) $batchId, (int) $prodiId);
            $this->session->set_flashdata('berhasil', 'Paket AI diterapkan ke DRAFT. Existing valid tetap dipertahankan dan conflict tidak ditimpa.');
            redirect($this->baseUrl() . '/prodi/' . (int) $prodiId . '?master_id=' . (int) $result['curriculum_id']);
        } catch (Throwable $e) {
            $this->fail('Apply paket AI gagal', $e->getMessage(), 422, $prodiId);
        }
    }

    public function history($prodiId = 0, $curriculumId = 0)
    {
        if (!$this->requireBsi() || !$this->requireGet()) {
            return;
        }

        try {
            $foundation = $this->m095->foundationStatus((int) $prodiId);
            $this->render('history', array(
                'prodi_id' => (int) $prodiId,
                'curriculum_id' => (int) $curriculumId,
                'foundation' => $foundation,
                'history' => $this->m095->history((int) $prodiId, (int) $curriculumId)
            ));
        } catch (Throwable $e) {
            $this->fail('Riwayat AI tidak dapat dibuka', $e->getMessage(), 500, $prodiId);
        }
    }

    public function book($prodiId = 0, $curriculumId = 0)
    {
        if (!$this->requireBsi() || !$this->requireGet()) {
            return;
        }

        try {
            $html = $this->m095->bookHtml((int) $prodiId, (int) $curriculumId);
            $this->load->library('dompdf_gen');
            $this->dompdf_gen->loadHtml($html);
            $this->dompdf_gen->setPaper('A4', 'portrait');
            $this->dompdf_gen->render();

            $foundation = $this->m095->foundationStatus((int) $prodiId);
            $name = isset($foundation['program']['nama_prodi']) ? $foundation['program']['nama_prodi'] : ('PRODI_' . (int) $prodiId);
            $filename = 'Buku_Kurikulum_OBE_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $name) . '.pdf';

            $this->output
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_output($this->dompdf_gen->output());
        } catch (Throwable $e) {
            $this->fail('Buku Kurikulum belum dapat diunduh', $e->getMessage(), 422, $prodiId);
        }
    }
}
