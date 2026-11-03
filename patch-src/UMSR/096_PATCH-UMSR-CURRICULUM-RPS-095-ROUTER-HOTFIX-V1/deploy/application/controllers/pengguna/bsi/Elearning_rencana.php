<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Elearning_plan_controller.php';

/**
 * PATCH 095 compatibility layer.
 *
 * The existing Curriculum/RPS core remains the execution engine. This wrapper
 * adds a server-side Foundation gate, AI Curriculum completion workflow, and
 * final Book Curriculum download without replacing the core controller.
 */
class Elearning_rencana extends Elearning_plan_controller
{
    private $c095;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Elearning_curriculum_095_model', 'curriculum095');
        $this->c095 = $this->curriculum095;
        $this->load->helper(array('url', 'form'));
    }

    private function base095()
    {
        return 'pengguna/bsi/Elearning_rencana';
    }

    private function requireBsi()
    {
        if (!$this->c095->roleAllowed()) {
            show_error('Akses khusus Administrator BSI.', 403);
            return false;
        }
        return true;
    }

    private function csrfInput()
    {
        if (!isset($this->security)) {
            return '';
        }
        return '<input type="hidden" name="' . html_escape($this->security->get_csrf_token_name())
            . '" value="' . html_escape($this->security->get_csrf_hash()) . '">';
    }

    private function jsonResponse(array $payload, $status = 200)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function render095($view, array $data)
    {
        $data += array(
            'judul' => 'Kurikulum & RPS OBE',
            'base' => $this->base095(),
            'mode' => 'bsi'
        );
        $html = $this->load->view('templates/header', $data, true)
            . $this->load->view('templates/menu', $data, true);

        $swal = $this->load->view('elearning_plan/_swal', $data, true);
        if ($swal !== '') {
            $html .= $swal;
        }

        $html .= $this->load->view('elearning_plan/' . $view, $data, true)
            . $this->load->view('templates/footer', $data, true);
        $this->output->set_content_type('text/html', 'utf-8')->set_output($html);
    }

    private function flashWarning($title, array $messages)
    {
        $this->session->set_flashdata('elbsi_swal', json_encode(array(
            'icon' => 'warning',
            'title' => $title,
            'summary' => 'Periksa kelengkapan berikut sebelum melanjutkan.',
            'messages' => array_values(array_unique($messages)),
            'status' => 422
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function programFromLegacyMethod($method, array $args)
    {
        if (strpos((string) $method, 'master') === 0) {
            return isset($args[0]) ? (int) $args[0] : 0;
        }

        $classMethods = array(
            'alokasi', 'rps', 'rps_template', 'rps_manual_command',
            'rps_start_export', 'rps_start_preview', 'rps_start_commit',
            'rps_preview', 'rps_commit', 'rps_action', 'pdf', 'rps_json', 'legacy_json'
        );
        if (in_array($method, $classMethods, true)) {
            return $this->c095->programIdByClass(isset($args[0]) ? (int) $args[0] : 0);
        }
        return 0;
    }

    private function selectedCurriculumId($classId = 0)
    {
        $raw = $this->input->post('master_id');
        if ($raw === null || $raw === '') {
            $raw = $this->input->get('master_id');
        }
        if ($raw !== null && $raw !== '' && (int) $raw > 0) {
            return (int) $raw;
        }

        if ($classId > 0 && $this->db->table_exists('elbsi_rps_state')) {
            $row = $this->db->select('curriculum_id')
                ->where('id_jadwal', (int) $classId)
                ->get('elbsi_rps_state', 1)->row_array();
            if ($row && (int) ($row['curriculum_id'] ?? 0) > 0) {
                return (int) $row['curriculum_id'];
            }
        }
        return 0;
    }

    private function firstReadyCurriculum($prodiId)
    {
        foreach ($this->c095->listCurricula($prodiId) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) continue;
            $status = $this->c095->completeness($prodiId, $id);
            if (!empty($status['curriculum_complete'])) {
                return $id;
            }
        }
        return 0;
    }

    private function gateLegacy($method, array $args)
    {
        $prodiId = $this->programFromLegacyMethod($method, $args);
        if ($prodiId <= 0) {
            return true;
        }

        $foundation = $this->c095->foundationStatus($prodiId);
        if (!$foundation['complete']) {
            if ($this->input->method(true) === 'GET') {
                $this->render095('foundation_gate_095', array(
                    'program' => $foundation['program'],
                    'foundation_status' => $foundation,
                    'prodi_id' => $prodiId
                ));
            } else {
                $this->flashWarning('Fondasi Akademik Prodi belum lengkap', $foundation['missing']);
                redirect($this->base095());
            }
            return false;
        }

        // Prevent validation/activation of an incomplete Curriculum version.
        if ($method === 'master_action' && $this->input->method(true) === 'POST') {
            $action = strtolower(trim((string) $this->input->post('action')));
            if (in_array($action, array('validate_bsi', 'activate', 'approve', 'publish', 'aktifkan'), true)) {
                $masterId = (int) $this->input->post('master_id');
                if ($masterId > 0) {
                    $completion = $this->c095->completeness($prodiId, $masterId);
                    if (empty($completion['curriculum_complete'])) {
                        $this->flashWarning('Kurikulum belum siap diaktifkan', $completion['missing']);
                        redirect($this->base095() . '/master/' . $prodiId . '?master_id=' . $masterId);
                        return false;
                    }
                }
            }
        }

        // RPS creation may start only after one Curriculum version has complete Context RPS.
        if (strpos((string) $method, 'rps') === 0 && !in_array($method, array('rps_json'), true)) {
            $classId = isset($args[0]) ? (int) $args[0] : 0;
            $masterId = $this->selectedCurriculumId($classId);
            if ($masterId <= 0) {
                $masterId = $this->firstReadyCurriculum($prodiId);
            }
            if ($masterId <= 0) {
                $message = array('Belum ada versi Kurikulum dengan CPL, BOK, Mata Kuliah, I/R/M/A, CPMK, Sub-CPMK, Assessment, Signature Output, workload, dan referensi resmi yang lengkap.');
                if ($this->input->method(true) === 'GET') {
                    $this->render095('foundation_gate_095', array(
                        'program' => $foundation['program'],
                        'foundation_status' => $foundation,
                        'prodi_id' => $prodiId,
                        'curriculum_gate' => true,
                        'curriculum_missing' => $message
                    ));
                } else {
                    $this->flashWarning('Context Kurikulum belum siap untuk RPS', $message);
                    redirect($this->base095() . '/master/' . $prodiId);
                }
                return false;
            }
            $completion = $this->c095->completeness($prodiId, $masterId);
            if (empty($completion['curriculum_complete'])) {
                if ($this->input->method(true) === 'GET') {
                    $this->render095('foundation_gate_095', array(
                        'program' => $foundation['program'],
                        'foundation_status' => $foundation,
                        'prodi_id' => $prodiId,
                        'curriculum_gate' => true,
                        'curriculum_missing' => $completion['missing']
                    ));
                } else {
                    $this->flashWarning('Context Kurikulum belum siap untuk RPS', $completion['missing']);
                    redirect($this->base095() . '/master/' . $prodiId . '?master_id=' . $masterId);
                }
                return false;
            }
        }

        return true;
    }

    private function masterToolsScript($prodiId)
    {
        $foundation = $this->c095->foundationStatus($prodiId);
        $masterId = (int) $this->input->get('master_id');
        $curriculum = $masterId > 0 ? $this->c095->curriculum($masterId) : null;
        $completion = $curriculum ? $this->c095->completeness($prodiId, $masterId) : null;
        $binding = ($curriculum && $foundation['complete'])
            ? $this->c095->bindingStatus($masterId, $foundation)
            : array('bound' => false, 'current' => false);

        $data = array(
            'prodi_id' => (int) $prodiId,
            'master_id' => $masterId,
            'foundation_complete' => (bool) $foundation['complete'],
            'foundation_revision' => (string) ($foundation['revision'] ?? ''),
            'foundation_hash_short' => substr((string) ($foundation['hash'] ?? ''), 0, 12),
            'curriculum' => $curriculum ? array(
                'state' => $this->c095->curriculumState($curriculum),
                'immutable' => $this->c095->isImmutable($curriculum),
                'code' => (string) ($curriculum['code'] ?? ''),
                'title' => (string) ($curriculum['title'] ?? '')
            ) : null,
            'binding_current' => !empty($binding['current']),
            'completion' => $completion ? array(
                'percent' => (int) $completion['percent'],
                'complete' => (bool) $completion['complete'],
                'curriculum_complete' => (bool) $completion['curriculum_complete'],
                'rps_ready' => (int) $completion['rps']['ready'],
                'rps_total' => (int) $completion['rps']['total'],
                'missing' => array_slice($completion['missing'], 0, 12)
            ) : null,
            'base_url' => base_url($this->base095()),
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash()
        );

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return <<<'JS'
<script>
(function(){
  var d = __DATA__;
  function esc(s){var x=document.createElement('div');x.textContent=s==null?'':String(s);return x.innerHTML;}
  function form(action,label,klass,icon){
    return '<form method="post" action="'+esc(action)+'" class="d-inline">'+
      '<input type="hidden" name="'+esc(d.csrf_name)+'" value="'+esc(d.csrf_hash)+'">'+
      '<input type="hidden" name="master_id" value="'+d.master_id+'">'+
      '<button type="submit" class="btn btn-sm '+klass+'"><i class="bx '+icon+' me-1"></i>'+esc(label)+'</button></form>';
  }
  if(!d.foundation_complete){return;}
  var host=null, nodes=document.querySelectorAll('h1,h2,h3,h4,h5,h6,div');
  for(var i=0;i<nodes.length;i++){
    var t=(nodes[i].textContent||'').trim();
    if(t.indexOf('1. Buku Kurikulum Resmi')===0){host=nodes[i].closest('.card')||nodes[i];break;}
  }
  if(!host){
    var c=document.querySelector('.container-xxl,.container-fluid,.content-wrapper');
    host=c?c.firstElementChild:null;
  }
  if(!host || document.getElementById('curriculumAi095')) return;

  var pct=d.completion?d.completion.percent:0;
  var rps=d.completion?(d.completion.rps_ready+'/'+d.completion.rps_total):'-';
  var body='<div class="card border-0 shadow-sm mb-3" id="curriculumAi095" style="border-radius:14px;border-left:4px solid #696cff!important">'+
    '<div class="card-body p-3 p-md-4"><div class="d-flex flex-column flex-xl-row justify-content-between gap-3">'+
    '<div><div class="d-flex flex-wrap align-items-center gap-2"><h6 class="mb-0 fw-semibold">AI Curriculum Assistant</h6>'+
    '<span class="badge bg-label-success">Foundation Rev. '+esc(d.foundation_revision)+'</span>'+
    '<span class="badge bg-label-primary">'+pct+'% lengkap</span>'+
    '<span class="badge bg-label-info">RPS '+esc(rps)+'</span></div>'+
    '<div class="text-muted small mt-2">Foundation terkunci. AI hanya melengkapi DRAFT: KEEP data valid, ADD bagian kosong, COMPLETE bagian belum lengkap, dan CONFLICT tidak menimpa existing.</div>';

  if(d.completion && d.completion.missing && d.completion.missing.length){
    body+='<div class="small mt-2 text-warning"><i class="bx bx-info-circle me-1"></i>'+esc(d.completion.missing.slice(0,3).join(' · '))+'</div>';
  }
  body+='</div><div class="d-flex flex-wrap gap-2 align-items-start justify-content-xl-end">';

  if(!d.master_id){
    body+='<span class="btn btn-sm btn-outline-secondary disabled">Pilih / buat versi Kurikulum</span>';
  } else if(d.curriculum && d.curriculum.immutable){
    body+=form(d.base_url+'/ai_curriculum_revision/'+d.prodi_id,'Buat Revisi & Lengkapi AI','btn-primary','bx-git-branch');
  } else if(!d.binding_current){
    body+=form(d.base_url+'/foundation_bind/'+d.prodi_id,'Ikat Foundation ACTIVE','btn-outline-primary','bx-link');
  } else {
    body+='<a class="btn btn-sm btn-outline-primary" href="'+esc(d.base_url+'/ai_curriculum_command/'+d.prodi_id+'?master_id='+d.master_id)+'"><i class="bx bx-download me-1"></i>Unduh Perintah AI</a>';
    body+='<a class="btn btn-sm btn-primary" href="'+esc(d.base_url+'/ai_curriculum_upload/'+d.prodi_id+'?master_id='+d.master_id)+'"><i class="bx bx-upload me-1"></i>Upload JSON AI</a>';
    body+='<a class="btn btn-sm btn-outline-secondary" href="'+esc(d.base_url+'/ai_curriculum_history/'+d.prodi_id+'?master_id='+d.master_id)+'"><i class="bx bx-history me-1"></i>Riwayat</a>';
  }
  if(d.master_id && d.completion && d.completion.complete){
    body+='<a class="btn btn-sm btn-success" href="'+esc(d.base_url+'/curriculum_book/'+d.prodi_id+'?master_id='+d.master_id)+'"><i class="bx bxs-file-pdf me-1"></i>Buku Kurikulum OBE</a>';
  } else if(d.master_id){
    body+='<span class="btn btn-sm btn-outline-success disabled" title="Aktif setelah Kurikulum dan semua RPS lengkap 100%"><i class="bx bxs-file-pdf me-1"></i>Buku Kurikulum OBE</span>';
  }
  body+='</div></div></div></div>';
  host.insertAdjacentHTML('beforebegin',body);
})();
</script>
JS;
        return str_replace('__DATA__', $json, $script);
    }

    private function indexGateScript()
    {
        $base = json_encode(base_url($this->base095()), JSON_UNESCAPED_SLASHES);
        return <<<JS
<script>
(function(){
  var base={$base};
  var links=document.querySelectorAll('a[href*="/master/"]');
  links.forEach(function(a){
    var m=(a.getAttribute('href')||'').match(/\/master\/(\d+)/);
    if(!m)return;
    fetch(base+'/foundation_status/'+m[1],{credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(x){
        if(x.complete)return;
        a.classList.add('opacity-50');
        a.setAttribute('title','Fondasi Akademik Prodi belum lengkap');
        a.addEventListener('click',function(e){
          e.preventDefault();
          if(window.Swal){Swal.fire({icon:'warning',title:'Fondasi belum lengkap',html:(x.missing||[]).map(function(v){return '<div class="text-start small mb-1">• '+v+'</div>';}).join('')});}
          else{alert('Fondasi Akademik Prodi belum lengkap. Lengkapi Foundation terlebih dahulu.');}
        });
        var badge=document.createElement('span');badge.className='badge bg-label-warning ms-2';badge.textContent='FOUNDATION BELUM LENGKAP';
        a.appendChild(badge);
      }).catch(function(){});
  });
})();
</script>
JS;
    }

    private function appendScript($script)
    {
        $html = $this->output->get_output();
        if ($html === '') return;
        if (stripos($html, '</body>') !== false) {
            $html = preg_replace('/<\/body>/i', $script . '</body>', $html, 1);
        } else {
            $html .= $script;
        }
        $this->output->set_output($html);
    }

    /**
     * PATCH 096
     * Dispatch legacy Curriculum/RPS methods without assuming that the parent
     * controller implements _remap(). 095 called parent::_remap() unconditionally,
     * which can cause HTTP 500 on every Elearning_rencana route when the baseline
     * core exposes normal public methods instead of its own _remap().
     */
    private function dispatchLegacy096($method, array $args)
    {
        $method = trim((string) $method);
        if ($method === '' || $method === '_remap' || strpos($method, '_') === 0) {
            show_404();
            return null;
        }

        $parentClass = get_parent_class($this);

        // Preserve a parent-provided _remap when the baseline actually has one.
        if ($parentClass && method_exists($parentClass, '_remap')) {
            $remap = new ReflectionMethod($parentClass, '_remap');
            if (!$remap->isPublic()) {
                $remap->setAccessible(true);
            }
            return $remap->invoke($this, $method, $args);
        }

        // Normal CI3 controller baseline: invoke the inherited public method.
        if (!method_exists($this, $method)) {
            show_404();
            return null;
        }

        $target = new ReflectionMethod($this, $method);
        if (!$target->isPublic() || $target->isConstructor() || $target->isDestructor()) {
            show_404();
            return null;
        }

        return $target->invokeArgs($this, $args);
    }

    public function _remap($method, $args = array())
    {
        $ours = array(
            'foundation_status', 'foundation_bind',
            'ai_curriculum_command', 'ai_curriculum_upload', 'ai_curriculum_preview',
            'ai_curriculum_apply', 'ai_curriculum_history', 'ai_curriculum_revision',
            'curriculum_book'
        );

        if (in_array($method, $ours, true)) {
            if (!$this->requireBsi()) return;
            try {
                $this->handle095($method, $args);
            } catch (Throwable $e) {
                log_message('error', 'CURRICULUM_095 ' . get_class($e) . ': ' . $e->getMessage()
                    . ' @ ' . $e->getFile() . ':' . $e->getLine());
                $this->flashWarning('Kurikulum & RPS OBE', array($e->getMessage()));
                $prodiId = isset($args[0]) ? (int) $args[0] : 0;
                redirect($prodiId > 0 ? $this->base095() . '/master/' . $prodiId : $this->base095());
            }
            return;
        }

        if (!$this->gateLegacy($method, $args)) {
            return;
        }

        $this->dispatchLegacy096($method, $args);

        if ($method === 'master' && isset($args[0])) {
            $this->appendScript($this->masterToolsScript((int) $args[0]));
        } elseif ($method === 'index') {
            $this->appendScript($this->indexGateScript());
        }
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

    private function masterId()
    {
        $id = $this->input->post('master_id');
        if ($id === null || $id === '') $id = $this->input->get('master_id');
        return (int) $id;
    }

    private function handle095($method, array $args)
    {
        $prodiId = isset($args[0]) ? (int) $args[0] : 0;
        if ($prodiId <= 0) {
            show_404();
            return;
        }

        if ($method === 'foundation_status') {
            if (!$this->requireGet()) return;
            $s = $this->c095->foundationStatus($prodiId);
            $this->jsonResponse(array(
                'complete' => (bool) $s['complete'],
                'active' => (bool) $s['active'],
                'revision' => (string) ($s['revision'] ?? ''),
                'missing' => $s['missing']
            ));
            return;
        }

        $foundation = $this->c095->foundationStatus($prodiId);
        if (!$foundation['complete']) {
            throw new RuntimeException('Fondasi Akademik Prodi belum lengkap: ' . implode(' ', $foundation['missing']));
        }

        if ($method === 'foundation_bind') {
            if (!$this->requirePost()) return;
            $masterId = $this->masterId();
            $this->c095->bindFoundation($masterId, $foundation);
            $this->session->set_flashdata('berhasil', 'Kurikulum DRAFT berhasil dikunci ke Foundation ACTIVE.');
            redirect($this->base095() . '/master/' . $prodiId . '?master_id=' . $masterId);
            return;
        }

        if ($method === 'ai_curriculum_revision') {
            if (!$this->requirePost()) return;
            $sourceId = $this->masterId();
            $newId = $this->c095->cloneAsDraft($prodiId, $sourceId);
            $this->session->set_flashdata('berhasil', 'Revisi DRAFT berhasil dibuat dari versi aktif. Data existing dipertahankan dan Foundation ACTIVE sudah dikunci.');
            redirect($this->base095() . '/master/' . $prodiId . '?master_id=' . $newId);
            return;
        }

        $masterId = $this->masterId();
        if ($masterId <= 0) {
            throw new RuntimeException('Pilih versi Kurikulum terlebih dahulu.');
        }

        if ($method === 'ai_curriculum_command') {
            if (!$this->requireGet()) return;
            $text = $this->c095->commandText($prodiId, $masterId);
            $program = $foundation['program'];
            $filename = 'PERINTAH_CHATGPT_KURIKULUM_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($program['nama_prodi'] ?? $prodiId)) . '_' . $masterId . '.txt';
            $this->output
                ->set_content_type('text/plain', 'utf-8')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_header('X-Content-Type-Options: nosniff')
                ->set_output($text);
            return;
        }

        if ($method === 'ai_curriculum_upload') {
            if (!$this->requireGet()) return;
            $pack = $this->c095->commandPackage($prodiId, $masterId);
            $this->render095('curriculum_ai_upload_095', array(
                'prodi_id' => $prodiId,
                'master_id' => $masterId,
                'program' => $foundation['program'],
                'foundation_status' => $foundation,
                'curriculum' => $pack['curriculum'],
                'completion' => $pack['completion']
            ));
            return;
        }

        if ($method === 'ai_curriculum_preview') {
            if (!$this->requirePost()) return;
            if (!isset($_FILES['json_file']) || !is_array($_FILES['json_file'])) {
                throw new RuntimeException('File JSON belum dipilih.');
            }
            $file = $_FILES['json_file'];
            if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload JSON gagal.');
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
            $preview = $this->c095->previewPackage($prodiId, $masterId, $package);
            $batchId = $this->c095->savePreview($prodiId, $masterId, $package, $preview);
            $this->render095('curriculum_ai_preview_095', array(
                'prodi_id' => $prodiId,
                'master_id' => $masterId,
                'program' => $foundation['program'],
                'batch_id' => $batchId,
                'preview' => $preview
            ));
            return;
        }

        if ($method === 'ai_curriculum_apply') {
            if (!$this->requirePost()) return;
            $batchId = (int) $this->input->post('batch_id');
            $saved = $this->c095->applyPreview($batchId, $prodiId);
            $this->session->set_flashdata('berhasil', 'Paket AI berhasil diterapkan ke DRAFT secara aman. Existing valid tetap dipertahankan; conflict tidak ditimpa.');
            redirect($this->base095() . '/master/' . $prodiId . '?master_id=' . (int) $saved['curriculum_id']);
            return;
        }

        if ($method === 'ai_curriculum_history') {
            if (!$this->requireGet()) return;
            $this->render095('curriculum_ai_history_095', array(
                'prodi_id' => $prodiId,
                'master_id' => $masterId,
                'program' => $foundation['program'],
                'history' => $this->c095->history($prodiId, $masterId)
            ));
            return;
        }

        if ($method === 'curriculum_book') {
            if (!$this->requireGet()) return;
            $html = $this->c095->bookHtml($prodiId, $masterId);
            $this->load->library('dompdf_gen');
            $this->dompdf_gen->loadHtml($html);
            $this->dompdf_gen->setPaper('A4', 'portrait');
            $this->dompdf_gen->render();
            $program = $foundation['program'];
            $filename = 'Buku_Kurikulum_OBE_' . date('Y') . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($program['nama_prodi'] ?? $prodiId)) . '.pdf';
            $this->output
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_output($this->dompdf_gen->output());
            return;
        }

        show_404();
    }
}
