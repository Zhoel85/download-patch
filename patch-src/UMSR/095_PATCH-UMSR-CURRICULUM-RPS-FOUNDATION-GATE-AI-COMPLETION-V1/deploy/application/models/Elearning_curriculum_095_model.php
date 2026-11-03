<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PATCH 095
 * Foundation gate + AI Curriculum completion support.
 *
 * This model is intentionally schema-tolerant because the Curriculum Engine
 * has gone through several additive revisions. It never treats prodi.profil_prodi
 * as canonical Graduate Profile; PL/PEO come only from the Academic Foundation.
 */
class Elearning_curriculum_095_model extends CI_Model
{
    const SCHEMA = 'UMSR_CURRICULUM_PACKAGE';
    const SCHEMA_VERSION = '1.0';

    private function text($value)
    {
        return trim((string) $value);
    }

    private function fields($table)
    {
        return $this->db->table_exists($table) ? $this->db->list_fields($table) : array();
    }

    private function field($table, array $candidates)
    {
        $fields = array_flip($this->fields($table));
        foreach ($candidates as $candidate) {
            if (isset($fields[$candidate])) {
                return $candidate;
            }
        }
        return null;
    }

    private function rowValue(array $row, array $keys, $default = '')
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }
        return $default;
    }

    private function canonical($value)
    {
        if (is_array($value)) {
            $keys = array_keys($value);
            $assoc = $keys !== range(0, count($value) - 1);
            if ($assoc) {
                ksort($value);
            }
            foreach ($value as $key => $item) {
                $value[$key] = $this->canonical($item);
            }
        }
        return $value;
    }

    private function hashValue($value)
    {
        return hash('sha256', json_encode(
            $this->canonical($value),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    public function roleAllowed()
    {
        return (string) $this->session->userdata('hak_akses') === '17';
    }

    public function program($prodiId)
    {
        $prodiId = (int) $prodiId;
        if ($prodiId <= 0 || !$this->db->table_exists('prodi')) {
            return null;
        }
        return $this->db->where('id', $prodiId)->get('prodi', 1)->row_array();
    }

    public function programIdByClass($classId)
    {
        $classId = (int) $classId;
        if ($classId <= 0 || !$this->db->table_exists('jadwal') || !$this->db->table_exists('prodi')) {
            return 0;
        }
        $jadwal = $this->db->where('id', $classId)->get('jadwal', 1)->row_array();
        if (!$jadwal) {
            return 0;
        }
        $prodi = $this->db->where('kode_prodi', $this->text($jadwal['kode_prodi'] ?? ''))
            ->get('prodi', 1)->row_array();
        return $prodi ? (int) $prodi['id'] : 0;
    }

    private function foundationRow($prodiId)
    {
        $table = 'prodi_academic_foundation';
        $program = $this->program($prodiId);
        if (!$program || !$this->db->table_exists($table)) {
            return null;
        }

        $idField = $this->field($table, array('id_prodi', 'prodi_id', 'program_id'));
        $codeField = $this->field($table, array('kode_prodi', 'prodi_code', 'program_code'));
        if (!$idField && !$codeField) {
            return null;
        }

        $this->db->from($table);
        $this->db->group_start();
        $added = false;
        if ($idField) {
            $this->db->where($idField, (int) $prodiId);
            $added = true;
        }
        if ($codeField) {
            if ($added) {
                $this->db->or_where($codeField, $this->text($program['kode_prodi'] ?? ''));
            } else {
                $this->db->where($codeField, $this->text($program['kode_prodi'] ?? ''));
            }
        }
        $this->db->group_end();

        $statusField = $this->field($table, array('status', 'state', 'foundation_status', 'is_active'));
        if ($statusField) {
            $this->db->where_in($statusField, array('ACTIVE', 'AKTIF', '1', 1, 'Y'));
        }

        $revisionField = $this->field($table, array('revision', 'revisi', 'version', 'id'));
        if ($revisionField) {
            $this->db->order_by($revisionField, 'DESC');
        }
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    private function childRows($table, array $foundation)
    {
        if (!$this->db->table_exists($table)) {
            return array();
        }
        $foundationId = (int) $this->rowValue($foundation, array('id', 'foundation_id', 'id_foundation'), 0);
        if ($foundationId <= 0) {
            return array();
        }
        $fk = $this->field($table, array('foundation_id', 'id_foundation', 'academic_foundation_id'));
        if (!$fk) {
            return array();
        }
        $this->db->where($fk, $foundationId);
        $order = $this->field($table, array('urutan', 'sort_order', 'kode', 'code', 'id'));
        if ($order) {
            $this->db->order_by($order, 'ASC');
        }
        return $this->db->get($table)->result_array();
    }

    private function foundationMappings(array $foundation)
    {
        $candidates = array(
            'prodi_peo_profil_lulusan',
            'prodi_peo_pl_map',
            'prodi_peo_mapping',
            'prodi_peo_profil_map'
        );
        $foundationId = (int) $this->rowValue($foundation, array('id', 'foundation_id', 'id_foundation'), 0);
        foreach ($candidates as $table) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            $fk = $this->field($table, array('foundation_id', 'id_foundation', 'academic_foundation_id'));
            $this->db->from($table);
            if ($fk && $foundationId > 0) {
                $this->db->where($fk, $foundationId);
            }
            return array('table' => $table, 'rows' => $this->db->get()->result_array());
        }
        return array('table' => null, 'rows' => array());
    }

    public function foundationStatus($prodiId)
    {
        $program = $this->program($prodiId);
        $foundation = $this->foundationRow($prodiId);
        $missing = array();

        if (!$program) {
            return array(
                'complete' => false,
                'active' => false,
                'missing' => array('Master Program Studi tidak ditemukan.'),
                'program' => null,
                'foundation' => null,
                'profiles' => array(),
                'peo' => array(),
                'mapping' => array(),
                'hash' => ''
            );
        }

        if (!$foundation) {
            $missing[] = 'Fondasi Akademik Prodi ACTIVE belum tersedia.';
            return array(
                'complete' => false,
                'active' => false,
                'missing' => $missing,
                'program' => $program,
                'foundation' => null,
                'profiles' => array(),
                'peo' => array(),
                'mapping' => array(),
                'hash' => ''
            );
        }

        $table = 'prodi_academic_foundation';
        $required = array(
            array('visi_keilmuan', 'visi_ilmiah', 'scientific_vision'),
            array('penciri', 'penciri_prodi', 'keunikan', 'distinctiveness'),
            array('tujuan_prodi', 'tujuan', 'program_objectives'),
            array('strategi_prodi', 'strategi', 'strategy'),
            array('nilai_transversal', 'transversal_values', 'nilai_penciri')
        );
        $labels = array('Visi Keilmuan', 'Penciri/Keunikan Prodi', 'Tujuan Prodi', 'Strategi Prodi', 'Nilai Transversal');
        foreach ($required as $index => $candidates) {
            $f = $this->field($table, $candidates);
            if ($f && $this->text($foundation[$f] ?? '') === '') {
                $missing[] = $labels[$index] . ' belum diisi.';
            }
        }

        $level = strtoupper($this->text($program['status'] ?? ''));
        if ($this->text($program['nama_prodi'] ?? '') === '') {
            $missing[] = 'Nama Program Studi belum tersedia.';
        }
        if ($this->text($program['kode_prodi'] ?? '') === '') {
            $missing[] = 'Kode Program Studi belum tersedia.';
        }
        if ($level === '') {
            $missing[] = 'Jenjang Program Studi belum tersedia.';
        }

        $profiles = $this->childRows('prodi_profil_lulusan', $foundation);
        $peo = $this->childRows('prodi_peo', $foundation);
        if (!$profiles) {
            $missing[] = 'Profil Lulusan pada Fondasi belum tersedia.';
        }
        if (!$peo) {
            $missing[] = 'PEO pada Fondasi belum tersedia.';
        }

        $mapping = $this->foundationMappings($foundation);
        if ($mapping['table'] && $peo && count($mapping['rows']) < count($peo)) {
            $missing[] = 'Mapping PEO ke Profil Lulusan belum lengkap.';
        }

        $snapshot = array(
            'program' => array(
                'id' => (int) $program['id'],
                'code' => $this->text($program['kode_prodi'] ?? ''),
                'name' => $this->text($program['nama_prodi'] ?? ''),
                'level' => $level,
                'degree' => $this->text($program['gelar_lulusan'] ?? '')
            ),
            'foundation' => $foundation,
            'profiles' => $profiles,
            'peo' => $peo,
            'mapping' => $mapping['rows']
        );

        return array(
            'complete' => empty($missing),
            'active' => true,
            'missing' => array_values(array_unique($missing)),
            'program' => $program,
            'foundation' => $foundation,
            'profiles' => $profiles,
            'peo' => $peo,
            'mapping' => $mapping['rows'],
            'hash' => $this->hashValue($snapshot),
            'snapshot' => $snapshot,
            'revision' => (string) $this->rowValue($foundation, array('revision', 'revisi', 'version'), '1'),
            'id' => (int) $this->rowValue($foundation, array('id', 'foundation_id', 'id_foundation'), 0)
        );
    }

    public function curriculum($id)
    {
        $id = (int) $id;
        if ($id <= 0 || !$this->db->table_exists('elbsi_curriculum')) {
            return null;
        }
        $row = $this->db->where('id', $id)->get('elbsi_curriculum', 1)->row_array();
        if (!$row) {
            return null;
        }
        $payloadField = $this->field('elbsi_curriculum', array('payload_json', 'payload', 'content_json', 'curriculum_json'));
        $payload = array();
        if ($payloadField && isset($row[$payloadField])) {
            $decoded = json_decode((string) $row[$payloadField], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        $row['_payload_field'] = $payloadField;
        $row['payload'] = $payload;
        return $row;
    }

    public function curriculumState(array $row)
    {
        return strtoupper($this->text($this->rowValue($row, array('state', 'status'), 'DRAFT')));
    }

    public function isImmutable(array $row)
    {
        return in_array($this->curriculumState($row), array('ACTIVE', 'APPROVED', 'PUBLISHED', 'DISAHKAN'), true);
    }

    public function listCurricula($prodiId)
    {
        if (!$this->db->table_exists('elbsi_curriculum')) {
            return array();
        }
        $fk = $this->field('elbsi_curriculum', array('prodi_id', 'id_prodi', 'program_id'));
        if (!$fk) {
            return array();
        }
        return $this->db->where($fk, (int) $prodiId)->order_by('id', 'DESC')->get('elbsi_curriculum')->result_array();
    }

    private function binding($curriculumId)
    {
        $table = 'elbsi_curriculum_foundation';
        if (!$this->db->table_exists($table)) {
            return null;
        }
        $cf = $this->field($table, array('curriculum_id', 'id_curriculum', 'elbsi_curriculum_id'));
        if (!$cf) {
            return null;
        }
        $this->db->where($cf, (int) $curriculumId);
        $order = $this->field($table, array('id', 'updated_at', 'created_at'));
        if ($order) {
            $this->db->order_by($order, 'DESC');
        }
        return $this->db->get($table, 1)->row_array();
    }

    public function bindFoundation($curriculumId, array $foundationStatus)
    {
        if (!$foundationStatus['complete']) {
            throw new RuntimeException('Fondasi Akademik Prodi belum lengkap.');
        }
        if (!$this->db->table_exists('elbsi_curriculum_foundation')) {
            throw new RuntimeException('Tabel pengikat Kurikulum-Fondasi belum tersedia.');
        }
        $curriculum = $this->curriculum($curriculumId);
        if (!$curriculum) {
            throw new RuntimeException('Versi kurikulum tidak ditemukan.');
        }
        if ($this->isImmutable($curriculum)) {
            throw new RuntimeException('Versi ACTIVE/APPROVED bersifat read-only. Buat revisi DRAFT dahulu.');
        }

        $table = 'elbsi_curriculum_foundation';
        $cf = $this->field($table, array('curriculum_id', 'id_curriculum', 'elbsi_curriculum_id'));
        $ff = $this->field($table, array('foundation_id', 'id_foundation', 'academic_foundation_id'));
        if (!$cf || !$ff) {
            throw new RuntimeException('Schema pengikat Kurikulum-Fondasi tidak dikenali.');
        }

        $existing = $this->binding($curriculumId);
        $data = array();
        $data[$cf] = (int) $curriculumId;
        $data[$ff] = (int) $foundationStatus['id'];

        $rf = $this->field($table, array('foundation_revision', 'revision', 'locked_revision'));
        if ($rf) {
            $data[$rf] = (string) $foundationStatus['revision'];
        }
        $hf = $this->field($table, array('foundation_hash', 'locked_foundation_hash', 'context_hash'));
        if ($hf) {
            $data[$hf] = (string) $foundationStatus['hash'];
        }
        $updatedAt = $this->field($table, array('updated_at', 'locked_at'));
        if ($updatedAt) {
            $data[$updatedAt] = date('Y-m-d H:i:s');
        }
        $createdAt = $this->field($table, array('created_at'));
        if ($createdAt && !$existing) {
            $data[$createdAt] = date('Y-m-d H:i:s');
        }
        $actor = $this->field($table, array('created_by', 'updated_by', 'locked_by'));
        if ($actor) {
            $data[$actor] = $this->actor();
        }

        if ($existing) {
            $pk = $this->field($table, array('id'));
            if ($pk) {
                $this->db->where($pk, $existing[$pk])->update($table, $data);
            } else {
                $this->db->where($cf, (int) $curriculumId)->update($table, $data);
            }
        } else {
            $this->db->insert($table, $data);
        }
        return true;
    }

    public function bindingStatus($curriculumId, array $foundationStatus)
    {
        $row = $this->binding($curriculumId);
        if (!$row) {
            return array('bound' => false, 'current' => false, 'row' => null);
        }
        $table = 'elbsi_curriculum_foundation';
        $ff = $this->field($table, array('foundation_id', 'id_foundation', 'academic_foundation_id'));
        $hf = $this->field($table, array('foundation_hash', 'locked_foundation_hash', 'context_hash'));
        $current = $ff && (int) ($row[$ff] ?? 0) === (int) $foundationStatus['id'];
        if ($current && $hf && $this->text($row[$hf] ?? '') !== '') {
            $current = hash_equals(strtolower($this->text($row[$hf])), strtolower((string) $foundationStatus['hash']));
        }
        return array('bound' => true, 'current' => $current, 'row' => $row);
    }

    private function listValue(array $payload, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }
        return array();
    }

    private function nonEmpty($value)
    {
        if (is_array($value)) {
            return !empty($value);
        }
        return $this->text($value) !== '';
    }

    private function courseCode(array $course)
    {
        return $this->text($this->rowValue($course, array('code', 'kode', 'course_code', 'kode_matakuliah'), ''));
    }

    private function courseCredits(array $course)
    {
        return (float) $this->rowValue($course, array('sks', 'credits', 'credit'), 0);
    }

    private function courseSemester(array $course)
    {
        return (int) $this->rowValue($course, array('semester', 'semester_no'), 0);
    }

    private function courseCpmk(array $course)
    {
        return $this->listValue($course, array('cpmk', 'outcomes', 'course_outcomes'));
    }

    private function courseSub(array $course)
    {
        return $this->listValue($course, array('sub', 'sub_cpmk', 'subcpmk'));
    }

    private function courseCpl(array $course)
    {
        return $this->listValue($course, array('cpl', 'cpl_codes', 'assigned_cpl'));
    }

    private function courseIrma(array $course)
    {
        return $this->listValue($course, array('irma', 'i_r_m_a', 'curriculum_map'));
    }

    private function workloadStatus(array $course)
    {
        $sks = $this->courseCredits($course);
        if ($sks <= 0) {
            return array('ok' => false, 'message' => 'SKS belum valid.');
        }
        $work = isset($course['workload']) && is_array($course['workload']) ? $course['workload'] : array();
        $total = (float) $this->rowValue($work, array('total_hours', 'total', 'semester_hours'), 0);
        $active = (float) $this->rowValue($work, array('active_hours', 'active', 'student_active_hours'), 0);
        $target = $sks * 45;
        $minimum = $target * 0.60;
        if ($total <= 0) {
            return array('ok' => false, 'message' => 'Workload belum diisi.', 'target' => $target, 'minimum_active' => $minimum);
        }
        if (abs($total - $target) > 0.10) {
            return array('ok' => false, 'message' => 'Total workload harus SKS x 45 jam.', 'target' => $target, 'minimum_active' => $minimum);
        }
        if ($active + 0.10 < $minimum) {
            return array('ok' => false, 'message' => 'Active learning harus minimal 60%.', 'target' => $target, 'minimum_active' => $minimum);
        }
        return array('ok' => true, 'target' => $target, 'minimum_active' => $minimum, 'total' => $total, 'active' => $active);
    }

    private function publishedRpsCount($prodiId, array $courses)
    {
        $result = array('ready' => 0, 'total' => count($courses), 'codes' => array());
        if (!$courses || !$this->db->table_exists('elbsi_rps_state') || !$this->db->table_exists('jadwal')) {
            return $result;
        }
        $program = $this->program($prodiId);
        if (!$program) {
            return $result;
        }
        $rows = $this->db->select('DISTINCT j.kode_matakuliah AS code', false)
            ->from('elbsi_rps_state s')
            ->join('jadwal j', 'j.id=s.id_jadwal')
            ->where('j.kode_prodi', $program['kode_prodi'])
            ->where_in('s.phase', array('PUBLISHED', 'ACTIVE', 'DISAHKAN'))
            ->get()->result_array();
        $ready = array();
        foreach ($rows as $row) {
            $ready[$this->text($row['code'] ?? '')] = true;
        }
        foreach ($courses as $course) {
            $code = $this->courseCode($course);
            if ($code !== '' && isset($ready[$code])) {
                $result['ready']++;
                $result['codes'][] = $code;
            }
        }
        return $result;
    }

    public function completeness($prodiId, $curriculumId)
    {
        $foundation = $this->foundationStatus($prodiId);
        $curriculum = $this->curriculum($curriculumId);
        if (!$curriculum) {
            return array('percent' => 0, 'complete' => false, 'missing' => array('Versi kurikulum tidak ditemukan.'));
        }
        $payload = $curriculum['payload'];
        $missing = array();
        $checks = array();

        $checks['foundation'] = (bool) $foundation['complete'];
        if (!$checks['foundation']) {
            foreach ($foundation['missing'] as $gap) {
                $missing[] = $gap;
            }
        }

        $binding = $foundation['complete'] ? $this->bindingStatus($curriculumId, $foundation) : array('bound' => false, 'current' => false);
        $checks['foundation_binding'] = !empty($binding['current']);
        if (!$checks['foundation_binding']) {
            $missing[] = 'Kurikulum belum terkunci ke revision Fondasi ACTIVE saat ini.';
        }

        $cpl = $this->listValue($payload, array('cpl', 'graduate_outcomes'));
        $bok = $this->listValue($payload, array('bok', 'boks', 'body_of_knowledge', 'bahan_kajian'));
        $courses = $this->listValue($payload, array('courses', 'mata_kuliah'));
        $checks['cpl'] = !empty($cpl);
        $checks['bok'] = !empty($bok);
        $checks['courses'] = !empty($courses);
        if (!$checks['cpl']) $missing[] = 'CPL Kurikulum belum tersedia.';
        if (!$checks['bok']) $missing[] = 'BOK/Bahan Kajian belum tersedia.';
        if (!$checks['courses']) $missing[] = 'Struktur Mata Kuliah belum tersedia.';

        $courseCoreOk = true;
        $cpmkOk = true;
        $subOk = true;
        $irmaOk = true;
        $workloadOk = true;
        $assessmentOk = true;
        $signatureOk = true;

        foreach ($courses as $course) {
            if (!is_array($course)) {
                $courseCoreOk = false;
                continue;
            }
            $code = $this->courseCode($course);
            $label = $code !== '' ? $code : 'Mata Kuliah';
            if ($code === '' || $this->courseCredits($course) <= 0 || $this->courseSemester($course) <= 0 || !$this->courseCpl($course)) {
                $courseCoreOk = false;
                $missing[] = $label . ': kode/SKS/semester/pembebanan CPL belum lengkap.';
            }
            if (!$this->courseCpmk($course)) {
                $cpmkOk = false;
                $missing[] = $label . ': CPMK belum lengkap.';
            }
            if (!$this->courseSub($course)) {
                $subOk = false;
                $missing[] = $label . ': Sub-CPMK belum lengkap.';
            }
            if (!$this->courseIrma($course) && empty($payload['curriculum_map']) && empty($payload['irma'])) {
                $irmaOk = false;
            }
            $w = $this->workloadStatus($course);
            if (!$w['ok']) {
                $workloadOk = false;
                $missing[] = $label . ': ' . $w['message'];
            }
            $assessment = $this->listValue($course, array('assessments', 'assessment', 'assessment_blueprint'));
            if (!$assessment && empty($payload['assessments']) && empty($payload['assessment_plan'])) {
                $assessmentOk = false;
            }
            $signature = $this->rowValue($course, array('signature_output', 'signature_outputs'), null);
            if (!$this->nonEmpty($signature) && empty($payload['signature_outputs'])) {
                $signatureOk = false;
            }
        }

        $checks['course_context'] = $courseCoreOk && !empty($courses);
        $checks['cpmk'] = $cpmkOk && !empty($courses);
        $checks['sub_cpmk'] = $subOk && !empty($courses);
        $checks['irma'] = $irmaOk && !empty($courses);
        $checks['assessment'] = $assessmentOk && !empty($courses);
        $checks['signature_output'] = $signatureOk && !empty($courses);
        $checks['workload'] = $workloadOk && !empty($courses);

        if (!$checks['irma']) $missing[] = 'Curriculum Map I/R/M/A belum lengkap.';
        if (!$checks['assessment']) $missing[] = 'Assessment Blueprint/CPL Assessment Plan belum lengkap.';
        if (!$checks['signature_output']) $missing[] = 'Signature Output belum lengkap.';
        if (!$checks['workload']) $missing[] = 'Workload seluruh Mata Kuliah belum memenuhi SKS x 45 jam dan active learning minimal 60%.';

        $officialReference = $this->text($this->rowValue($curriculum, array('official_reference'), ''));
        $checks['official_reference'] = $officialReference !== '';
        if (!$checks['official_reference']) $missing[] = 'Referensi/dokumen Kurikulum resmi belum tersedia.';

        $rps = $this->publishedRpsCount($prodiId, $courses);
        $checks['rps_ready'] = $rps['total'] > 0 && $rps['ready'] >= $rps['total'];
        if (!$checks['rps_ready']) {
            $missing[] = 'RPS OBE PUBLISHED belum lengkap (' . $rps['ready'] . '/' . $rps['total'] . ').';
        }

        $passed = count(array_filter($checks));
        $total = count($checks);
        $percent = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        return array(
            'percent' => $percent,
            'complete' => $total > 0 && $passed === $total,
            'curriculum_complete' => !empty($checks['foundation'])
                && !empty($checks['foundation_binding'])
                && !empty($checks['cpl'])
                && !empty($checks['bok'])
                && !empty($checks['courses'])
                && !empty($checks['course_context'])
                && !empty($checks['cpmk'])
                && !empty($checks['sub_cpmk'])
                && !empty($checks['irma'])
                && !empty($checks['assessment'])
                && !empty($checks['signature_output'])
                && !empty($checks['workload'])
                && !empty($checks['official_reference']),
            'checks' => $checks,
            'missing' => array_values(array_unique($missing)),
            'rps' => $rps,
            'foundation' => $foundation,
            'binding' => $binding
        );
    }

    public function curriculumHash(array $curriculum)
    {
        return $this->hashValue(array(
            'id' => (int) ($curriculum['id'] ?? 0),
            'code' => $this->text($curriculum['code'] ?? ''),
            'title' => $this->text($curriculum['title'] ?? ''),
            'official_reference' => $this->text($curriculum['official_reference'] ?? ''),
            'state' => $this->curriculumState($curriculum),
            'revision' => (string) $this->rowValue($curriculum, array('revision', 'revisi', 'version'), ''),
            'payload' => $curriculum['payload']
        ));
    }

    public function commandPackage($prodiId, $curriculumId)
    {
        $foundation = $this->foundationStatus($prodiId);
        if (!$foundation['complete']) {
            throw new RuntimeException('Fondasi Akademik Prodi belum lengkap: ' . implode(' ', $foundation['missing']));
        }
        $curriculum = $this->curriculum($curriculumId);
        if (!$curriculum || (int) $this->rowValue($curriculum, array('prodi_id', 'id_prodi', 'program_id'), 0) !== (int) $prodiId) {
            throw new RuntimeException('Versi kurikulum tidak ditemukan pada Prodi ini.');
        }
        if ($this->isImmutable($curriculum)) {
            throw new RuntimeException('Versi ACTIVE/APPROVED bersifat read-only. Gunakan Buat Revisi & Lengkapi dengan AI.');
        }

        $binding = $this->bindingStatus($curriculumId, $foundation);
        if (!$binding['current']) {
            throw new RuntimeException('Kurikulum DRAFT belum dikunci ke Fondasi ACTIVE. Ikat Fondasi terlebih dahulu.');
        }

        $completion = $this->completeness($prodiId, $curriculumId);
        $template = array(
            'schema' => self::SCHEMA,
            'schema_version' => self::SCHEMA_VERSION,
            'scope' => array(
                'prodi_id' => (int) $prodiId,
                'curriculum_id' => (int) $curriculumId,
                'curriculum_revision' => (string) $this->rowValue($curriculum, array('revision', 'revisi', 'version'), ''),
                'curriculum_hash' => $this->curriculumHash($curriculum)
            ),
            'foundation_lock' => array(
                'foundation_id' => (int) $foundation['id'],
                'foundation_revision' => (string) $foundation['revision'],
                'foundation_hash' => (string) $foundation['hash']
            ),
            'proposed_payload' => $curriculum['payload'],
            'notes' => array()
        );

        return array(
            'foundation' => $foundation,
            'curriculum' => $curriculum,
            'completion' => $completion,
            'template' => $template
        );
    }

    public function commandText($prodiId, $curriculumId)
    {
        $pack = $this->commandPackage($prodiId, $curriculumId);
        $program = $pack['foundation']['program'];

        $lines = array(
            '============================================================',
            'PERINTAH CHATGPT — LENGKAPI KURIKULUM & RPS OBE UMS RAPPANG',
            'CURRICULUM ENGINE PORTABLE AI — PATCH 095',
            '============================================================',
            '',
            'PROGRAM STUDI: ' . $this->text($program['nama_prodi'] ?? ''),
            'KODE PRODI: ' . $this->text($program['kode_prodi'] ?? ''),
            '',
            'PRINSIP WAJIB:',
            '1. Fondasi Akademik Prodi adalah LOCKED SOURCE OF TRUTH dan TIDAK BOLEH diubah.',
            '2. Profil Lulusan dan PEO berasal dari Foundation. Jangan membuat PL/PEO baru di proposed_payload.',
            '3. Pertahankan setiap data existing yang sudah valid. Isi hanya bagian kosong/tidak lengkap.',
            '4. Bila menemukan data existing yang berbeda secara substantif, jangan menimpa; tulis pada notes sebagai CONFLICT untuk review manusia.',
            '5. Lengkapi CPL, BOK/Bahan Kajian, Mata Kuliah, CPL-MK, I/R/M/A, CPMK, Sub-CPMK, Assessment Blueprint, rubrik, Signature Output, workload, evidence, referensi, dan QA.',
            '6. Setiap mata kuliah wajib mempunyai code, SKS, semester, CPL, CPMK, Sub-CPMK, I/R/M/A, assessment/signature output, serta workload.',
            '7. Workload UMS Rappang: total semester = SKS x 45 jam; active student learning minimal 60%.',
            '8. RPS Context harus siap untuk 16 pertemuan. UTS slot 8 dan UAS slot 16 pada tahap RPS.',
            '9. Jangan memasukkan data mahasiswa, nilai, credential, API key, password, atau data pribadi.',
            '10. Kembalikan SATU file JSON UTF-8 murni tanpa markdown.',
            '',
            'FONDASI AKADEMIK TERKUNCI:',
            json_encode($pack['foundation']['snapshot'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            '',
            'KONDISI KURIKULUM EXISTING:',
            json_encode(array(
                'id' => (int) $pack['curriculum']['id'],
                'code' => $this->text($pack['curriculum']['code'] ?? ''),
                'title' => $this->text($pack['curriculum']['title'] ?? ''),
                'state' => $this->curriculumState($pack['curriculum']),
                'official_reference' => $this->text($pack['curriculum']['official_reference'] ?? ''),
                'payload' => $pack['curriculum']['payload']
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            '',
            'GAP YANG HARUS DIPRIORITASKAN:',
            json_encode($pack['completion']['missing'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            '',
            'OUTPUT TEMPLATE WAJIB:',
            json_encode($pack['template'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            '',
            'SELF-CHECK:',
            '- Jangan ubah scope dan foundation_lock.',
            '- Jangan tambahkan profiles/peo sebagai sumber baru di proposed_payload.',
            '- Kode existing tidak boleh diganti diam-diam.',
            '- Relasi traceability harus konsisten: Foundation PL/PEO -> CPL -> BOK -> MK -> CPMK -> Sub-CPMK -> Assessment/Rubric/Evidence.',
            '- Curriculum Map I/R/M/A harus tersedia.',
            '- Workload tiap MK harus lolos SKS x 45 dan active >=60%.',
            '- proposed_payload harus merupakan versi lengkap setelah penambahan yang diperlukan, bukan hanya potongan perubahan.'
        );
        return implode("\n", $lines);
    }

    private function sameScalar($a, $b)
    {
        return $this->text($a) === $this->text($b);
    }

    private function listIdentity(array $item)
    {
        foreach (array('code', 'kode', 'id', 'course_code', 'kode_matakuliah', 'assessment_code', 'rubric_code') as $key) {
            if (array_key_exists($key, $item) && $this->text($item[$key]) !== '') {
                return $key . ':' . $this->text($item[$key]);
            }
        }
        return '';
    }

    private function mergeSafe($existing, $proposed, $path, array &$stats, array &$conflicts)
    {
        if (is_array($existing) && is_array($proposed)) {
            $existingAssoc = array_keys($existing) !== range(0, count($existing) - 1);
            $proposedAssoc = array_keys($proposed) !== range(0, count($proposed) - 1);
            if ($existingAssoc || $proposedAssoc) {
                $out = $existing;
                foreach ($proposed as $key => $value) {
                    $childPath = $path === '' ? (string) $key : $path . '.' . $key;
                    if (!array_key_exists($key, $out)) {
                        $out[$key] = $value;
                        $stats['add']++;
                    } else {
                        $out[$key] = $this->mergeSafe($out[$key], $value, $childPath, $stats, $conflicts);
                    }
                }
                return $out;
            }

            $out = $existing;
            $index = array();
            foreach ($out as $i => $item) {
                if (is_array($item)) {
                    $identity = $this->listIdentity($item);
                    if ($identity !== '') {
                        $index[$identity] = $i;
                    }
                }
            }
            foreach ($proposed as $item) {
                if (!is_array($item)) {
                    if (!in_array($item, $out, true)) {
                        $out[] = $item;
                        $stats['add']++;
                    } else {
                        $stats['keep']++;
                    }
                    continue;
                }
                $identity = $this->listIdentity($item);
                if ($identity !== '' && isset($index[$identity])) {
                    $i = $index[$identity];
                    $out[$i] = $this->mergeSafe($out[$i], $item, $path . '[' . $identity . ']', $stats, $conflicts);
                } else {
                    $out[] = $item;
                    $stats['add']++;
                    if ($identity !== '') {
                        $index[$identity] = count($out) - 1;
                    }
                }
            }
            return $out;
        }

        $existingEmpty = !$this->nonEmpty($existing);
        $proposedEmpty = !$this->nonEmpty($proposed);
        if ($existingEmpty && !$proposedEmpty) {
            $stats['complete']++;
            return $proposed;
        }
        if ($proposedEmpty || $this->sameScalar($existing, $proposed)) {
            $stats['keep']++;
            return $existing;
        }

        $stats['conflict']++;
        $conflicts[] = array(
            'path' => $path,
            'existing' => is_scalar($existing) ? (string) $existing : $existing,
            'proposed' => is_scalar($proposed) ? (string) $proposed : $proposed,
            'action' => 'KEEP_EXISTING'
        );
        return $existing;
    }

    public function validatePackage($prodiId, $curriculumId, array $package)
    {
        $pack = $this->commandPackage($prodiId, $curriculumId);
        $errors = array();

        if (($package['schema'] ?? '') !== self::SCHEMA) {
            $errors[] = 'schema harus ' . self::SCHEMA . '.';
        }
        if ((string) ($package['schema_version'] ?? '') !== self::SCHEMA_VERSION) {
            $errors[] = 'schema_version harus ' . self::SCHEMA_VERSION . '.';
        }

        $scope = isset($package['scope']) && is_array($package['scope']) ? $package['scope'] : array();
        if ((int) ($scope['prodi_id'] ?? 0) !== (int) $prodiId) $errors[] = 'prodi_id tidak cocok.';
        if ((int) ($scope['curriculum_id'] ?? 0) !== (int) $curriculumId) $errors[] = 'curriculum_id tidak cocok.';
        if ((string) ($scope['curriculum_hash'] ?? '') !== (string) $this->curriculumHash($pack['curriculum'])) {
            $errors[] = 'curriculum_hash berubah. Download ulang perintah.';
        }

        $lock = isset($package['foundation_lock']) && is_array($package['foundation_lock']) ? $package['foundation_lock'] : array();
        if ((int) ($lock['foundation_id'] ?? 0) !== (int) $pack['foundation']['id']
            || (string) ($lock['foundation_revision'] ?? '') !== (string) $pack['foundation']['revision']
            || !hash_equals((string) $pack['foundation']['hash'], (string) ($lock['foundation_hash'] ?? ''))) {
            $errors[] = 'Foundation lock berubah. Download ulang perintah.';
        }

        $proposed = isset($package['proposed_payload']) && is_array($package['proposed_payload'])
            ? $package['proposed_payload'] : null;
        if ($proposed === null) {
            $errors[] = 'proposed_payload wajib berupa object JSON.';
        }
        foreach (array('profiles', 'profil_lulusan', 'peo') as $blocked) {
            if (is_array($proposed) && array_key_exists($blocked, $proposed)) {
                $errors[] = $blocked . ' tidak boleh dikelola oleh AI Curriculum; gunakan Foundation Prodi.';
            }
        }

        return array('ok' => empty($errors), 'errors' => $errors, 'context' => $pack);
    }

    public function previewPackage($prodiId, $curriculumId, array $package)
    {
        $valid = $this->validatePackage($prodiId, $curriculumId, $package);
        if (!$valid['ok']) {
            throw new RuntimeException(implode(' ', $valid['errors']));
        }
        $stats = array('keep' => 0, 'add' => 0, 'complete' => 0, 'conflict' => 0);
        $conflicts = array();
        $merged = $this->mergeSafe(
            $valid['context']['curriculum']['payload'],
            $package['proposed_payload'],
            '',
            $stats,
            $conflicts
        );

        $before = $this->completeness($prodiId, $curriculumId);
        return array(
            'stats' => $stats,
            'conflicts' => $conflicts,
            'merged_payload' => $merged,
            'before' => $before,
            'foundation_hash' => $valid['context']['foundation']['hash'],
            'curriculum_hash' => $this->curriculumHash($valid['context']['curriculum'])
        );
    }

    public function actor()
    {
        $id = $this->session->userdata('id_user');
        if ($id !== null && $id !== '') {
            return 'user:' . (string) $id;
        }
        $username = $this->session->userdata('username');
        return $username ? 'username:' . (string) $username : 'unknown';
    }

    public function tablesReady()
    {
        return $this->db->table_exists('elearning_curriculum_ai_import');
    }

    public function savePreview($prodiId, $curriculumId, array $package, array $preview)
    {
        if (!$this->tablesReady()) {
            throw new RuntimeException('Tabel audit AI Curriculum 095 belum tersedia.');
        }
        $json = json_encode($package, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->insert('elearning_curriculum_ai_import', array(
            'prodi_id' => (int) $prodiId,
            'curriculum_id' => (int) $curriculumId,
            'schema_version' => self::SCHEMA_VERSION,
            'package_hash' => hash('sha256', $json),
            'foundation_hash' => (string) $preview['foundation_hash'],
            'curriculum_hash' => (string) $preview['curriculum_hash'],
            'package_json' => $json,
            'preview_json' => json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'PREVIEW',
            'created_by' => $this->actor(),
            'created_at' => date('Y-m-d H:i:s'),
            'applied_at' => null
        ));
        return (int) $this->db->insert_id();
    }

    public function history($prodiId, $curriculumId = 0)
    {
        if (!$this->tablesReady()) {
            return array();
        }
        $this->db->where('prodi_id', (int) $prodiId);
        if ((int) $curriculumId > 0) {
            $this->db->where('curriculum_id', (int) $curriculumId);
        }
        return $this->db->order_by('id', 'DESC')->limit(50)->get('elearning_curriculum_ai_import')->result_array();
    }

    public function applyPreview($batchId, $prodiId)
    {
        if (!$this->tablesReady()) {
            throw new RuntimeException('Tabel audit AI Curriculum 095 belum tersedia.');
        }
        $batch = $this->db->where('id', (int) $batchId)
            ->where('prodi_id', (int) $prodiId)
            ->where('status', 'PREVIEW')
            ->get('elearning_curriculum_ai_import', 1)->row_array();
        if (!$batch) {
            throw new RuntimeException('Batch preview tidak ditemukan atau sudah diterapkan.');
        }

        $package = json_decode($batch['package_json'], true);
        if (!is_array($package)) {
            throw new RuntimeException('Payload batch tidak valid.');
        }
        $curriculumId = (int) $batch['curriculum_id'];
        $fresh = $this->previewPackage($prodiId, $curriculumId, $package);
        if (!hash_equals((string) $batch['curriculum_hash'], (string) $fresh['curriculum_hash'])
            || !hash_equals((string) $batch['foundation_hash'], (string) $fresh['foundation_hash'])) {
            throw new RuntimeException('Foundation/Kurikulum berubah setelah preview. Upload ulang JSON.');
        }

        $curriculum = $this->curriculum($curriculumId);
        if (!$curriculum || $this->isImmutable($curriculum)) {
            throw new RuntimeException('Hanya kurikulum DRAFT yang dapat dilengkapi AI.');
        }
        $payloadField = $curriculum['_payload_field'];
        if (!$payloadField) {
            throw new RuntimeException('Kolom payload kurikulum tidak dikenali.');
        }

        $this->db->trans_begin();
        $update = array(
            $payloadField => json_encode($fresh['merged_payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $revisionField = $this->field('elbsi_curriculum', array('revision', 'revisi'));
        if ($revisionField) {
            $update[$revisionField] = (int) ($curriculum[$revisionField] ?? 0) + 1;
        }
        $updatedAt = $this->field('elbsi_curriculum', array('updated_at'));
        if ($updatedAt) {
            $update[$updatedAt] = date('Y-m-d H:i:s');
        }
        $updatedBy = $this->field('elbsi_curriculum', array('updated_by'));
        if ($updatedBy) {
            $update[$updatedBy] = $this->actor();
        }
        $this->db->where('id', $curriculumId)->update('elbsi_curriculum', $update);
        $this->db->where('id', (int) $batchId)->update('elearning_curriculum_ai_import', array(
            'status' => 'APPLIED',
            'preview_json' => json_encode($fresh, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'applied_at' => date('Y-m-d H:i:s')
        ));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            throw new RuntimeException('Transaksi gagal. Tidak ada perubahan parsial yang dipertahankan.');
        }
        $this->db->trans_commit();
        return array('curriculum_id' => $curriculumId, 'preview' => $fresh);
    }

    public function cloneAsDraft($prodiId, $curriculumId)
    {
        $foundation = $this->foundationStatus($prodiId);
        if (!$foundation['complete']) {
            throw new RuntimeException('Fondasi Akademik Prodi belum lengkap.');
        }
        $source = $this->curriculum($curriculumId);
        if (!$source) {
            throw new RuntimeException('Versi kurikulum sumber tidak ditemukan.');
        }
        $fk = $this->field('elbsi_curriculum', array('prodi_id', 'id_prodi', 'program_id'));
        if (!$fk || (int) $source[$fk] !== (int) $prodiId) {
            throw new RuntimeException('Versi kurikulum berbeda Prodi.');
        }

        $fields = array_flip($this->fields('elbsi_curriculum'));
        $copy = array();
        foreach ($source as $key => $value) {
            if ($key === 'id' || $key === 'payload' || $key === '_payload_field' || !isset($fields[$key])) {
                continue;
            }
            $copy[$key] = $value;
        }

        $stateField = $this->field('elbsi_curriculum', array('state', 'status'));
        if ($stateField) $copy[$stateField] = 'DRAFT';
        $codeField = $this->field('elbsi_curriculum', array('code', 'kode'));
        if ($codeField) $copy[$codeField] = 'AI-' . (int) $prodiId . '-' . date('YmdHis');
        $revisionField = $this->field('elbsi_curriculum', array('revision', 'revisi'));
        if ($revisionField) $copy[$revisionField] = (int) ($source[$revisionField] ?? 0) + 1;
        foreach (array('approved_by', 'approved_at', 'published_by', 'published_at', 'activated_by', 'activated_at') as $field) {
            if (isset($fields[$field])) $copy[$field] = null;
        }
        if (isset($fields['created_at'])) $copy['created_at'] = date('Y-m-d H:i:s');
        if (isset($fields['updated_at'])) $copy['updated_at'] = date('Y-m-d H:i:s');
        if (isset($fields['created_by'])) $copy['created_by'] = $this->actor();
        if (isset($fields['updated_by'])) $copy['updated_by'] = $this->actor();

        $this->db->trans_begin();
        $this->db->insert('elbsi_curriculum', $copy);
        $newId = (int) $this->db->insert_id();
        if ($newId <= 0) {
            $this->db->trans_rollback();
            throw new RuntimeException('Gagal membuat revisi DRAFT.');
        }
        $this->bindFoundation($newId, $foundation);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            throw new RuntimeException('Gagal membuat revisi DRAFT secara atomik.');
        }
        $this->db->trans_commit();
        return $newId;
    }

    public function bookHtml($prodiId, $curriculumId)
    {
        $curriculum = $this->curriculum($curriculumId);
        $foundation = $this->foundationStatus($prodiId);
        $completion = $this->completeness($prodiId, $curriculumId);
        if (!$foundation['complete'] || !$curriculum) {
            throw new RuntimeException('Fondasi/Kurikulum belum siap.');
        }
        if (!$completion['complete']) {
            throw new RuntimeException('Buku Kurikulum final hanya dapat diunduh setelah Kurikulum dan seluruh RPS OBE lengkap 100%.');
        }

        $program = $foundation['program'];
        $payload = $curriculum['payload'];
        $cpl = $this->listValue($payload, array('cpl', 'graduate_outcomes'));
        $bok = $this->listValue($payload, array('bok', 'boks', 'body_of_knowledge', 'bahan_kajian'));
        $courses = $this->listValue($payload, array('courses', 'mata_kuliah'));
        $esc = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
        $arrText = function ($value) use ($esc) {
            if (is_array($value)) {
                $parts = array();
                foreach ($value as $v) {
                    if (is_array($v)) {
                        $parts[] = isset($v['code']) ? $v['code'] : (isset($v['text']) ? $v['text'] : json_encode($v, JSON_UNESCAPED_UNICODE));
                    } else {
                        $parts[] = $v;
                    }
                }
                return $esc(implode(', ', $parts));
            }
            return $esc($value);
        };

        ob_start();
        ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page{margin:18mm 14mm 18mm 14mm}
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#1f2d3d;line-height:1.45}
.cover{height:245mm;border:2px solid #183a6b;padding:22mm 15mm;text-align:center;box-sizing:border-box}
.cover h1{font-size:25px;color:#183a6b;margin-top:35mm}.cover h2{font-size:18px}.cover .meta{margin-top:25mm;font-size:12px}
h1{font-size:20px;color:#183a6b;border-bottom:2px solid #183a6b;padding-bottom:5px;page-break-before:always}
h2{font-size:14px;color:#244d7e}.small{font-size:8px;color:#6b778c}
table{width:100%;border-collapse:collapse;margin:7px 0 14px}th,td{border:1px solid #aeb8c5;padding:5px;vertical-align:top}th{background:#183a6b;color:white}
.badge{display:inline-block;padding:3px 7px;border-radius:10px;background:#eaf2ff;color:#183a6b}
.page-break{page-break-before:always}
</style>
</head>
<body>
<div class="cover">
    <div class="small">UNIVERSITAS MUHAMMADIYAH SIDENRENG RAPPANG</div>
    <h1>BUKU KURIKULUM OBE</h1>
    <h2><?= $esc($program['nama_prodi'] ?? '') ?></h2>
    <div class="meta">
        <?= $esc($curriculum['title'] ?? '') ?><br>
        <?= $esc($curriculum['official_reference'] ?? '') ?><br><br>
        <span class="badge">Foundation Rev. <?= $esc($foundation['revision']) ?></span>
        <span class="badge">Kelengkapan 100%</span>
    </div>
</div>

<h1>DAFTAR ISI</h1>
<ol>
<li>Identitas Program Studi dan Fondasi Akademik</li>
<li>Evaluasi, Landasan, Penciri, dan Arah Pengembangan</li>
<li>Profil Lulusan dan Program Educational Objectives</li>
<li>Capaian Pembelajaran Lulusan</li>
<li>Body of Knowledge / Bahan Kajian</li>
<li>Struktur Mata Kuliah dan Beban Studi</li>
<li>Curriculum Map I/R/M/A dan Traceability</li>
<li>CPMK, Sub-CPMK, Assessment, Signature Output, dan Workload</li>
<li>RPS OBE, Evidence, PPEPP/CQI, dan Implementasi</li>
</ol>

<h1>BAB I · IDENTITAS PROGRAM STUDI</h1>
<table>
<tr><th style="width:28%">Komponen</th><th>Keterangan</th></tr>
<tr><td>Program Studi</td><td><?= $esc($program['nama_prodi'] ?? '') ?></td></tr>
<tr><td>Kode Program Studi</td><td><?= $esc($program['kode_prodi'] ?? '') ?></td></tr>
<tr><td>Jenjang</td><td><?= $esc($program['status'] ?? '') ?></td></tr>
<tr><td>Gelar Lulusan</td><td><?= $esc($program['gelar_lulusan'] ?? '') ?></td></tr>
</table>
<h2>Fondasi Akademik</h2>
<table>
<?php foreach ((array) $foundation['foundation'] as $key => $value): if (is_scalar($value) && !preg_match('/^(id|created|updated|hash)/i',$key)): ?>
<tr><td style="width:28%"><?= $esc(ucwords(str_replace('_',' ',$key))) ?></td><td><?= $esc($value) ?></td></tr>
<?php endif; endforeach; ?>
</table>

<h1>BAB II–IV · EVALUASI, LANDASAN, VISI, TUJUAN, STRATEGI & PENCIRI</h1>
<?php
$chapters = array('evaluation','tracer','swot','gap_analysis','benchmark','landasan','vision','mission','goals','strategy','distinctiveness','university_values');
foreach ($chapters as $key):
    if (!isset($payload[$key])) continue;
?>
<h2><?= $esc(ucwords(str_replace('_',' ',$key))) ?></h2>
<div><?= nl2br($arrText($payload[$key])) ?></div>
<?php endforeach; ?>

<h1>BAB V · PROFIL LULUSAN, PEO & CPL</h1>
<h2>Profil Lulusan</h2>
<table><tr><th>Kode</th><th>Rumusan</th></tr>
<?php foreach ($foundation['profiles'] as $row): ?>
<tr><td><?= $esc($this->rowValue($row,array('code','kode','kode_pl'),'') ) ?></td><td><?= $esc($this->rowValue($row,array('text','rumusan','nama','deskripsi'),'') ) ?></td></tr>
<?php endforeach; ?></table>
<h2>Program Educational Objectives (PEO)</h2>
<table><tr><th>Kode</th><th>Rumusan</th></tr>
<?php foreach ($foundation['peo'] as $row): ?>
<tr><td><?= $esc($this->rowValue($row,array('code','kode','kode_peo'),'') ) ?></td><td><?= $esc($this->rowValue($row,array('text','rumusan','nama','deskripsi'),'') ) ?></td></tr>
<?php endforeach; ?></table>
<h2>Capaian Pembelajaran Lulusan</h2>
<table><tr><th>Kode</th><th>Rumusan</th></tr>
<?php foreach ($cpl as $row): ?>
<tr><td><?= $esc(is_array($row)?$this->rowValue($row,array('code','kode'),''):'') ?></td><td><?= $esc(is_array($row)?$this->rowValue($row,array('text','rumusan','description'),''):$row) ?></td></tr>
<?php endforeach; ?></table>

<h1>BAB VI · BODY OF KNOWLEDGE / BAHAN KAJIAN</h1>
<table><tr><th>Kode</th><th>Body of Knowledge / Bahan Kajian</th></tr>
<?php foreach ($bok as $row): ?>
<tr><td><?= $esc(is_array($row)?$this->rowValue($row,array('code','kode'),''):'') ?></td><td><?= $esc(is_array($row)?$this->rowValue($row,array('text','name','nama','description'),''):$row) ?></td></tr>
<?php endforeach; ?></table>

<h1>BAB VII · STRUKTUR MATA KULIAH & SKS</h1>
<table><tr><th>Kode</th><th>Mata Kuliah</th><th>Semester</th><th>SKS</th><th>CPL</th></tr>
<?php foreach ($courses as $course): ?>
<tr>
<td><?= $esc($this->courseCode($course)) ?></td>
<td><?= $esc($this->rowValue($course,array('name','nama','title'),'') ) ?></td>
<td><?= $esc($this->courseSemester($course)) ?></td>
<td><?= $esc($this->courseCredits($course)) ?></td>
<td><?= $arrText($this->courseCpl($course)) ?></td>
</tr>
<?php endforeach; ?></table>

<h1>BAB VIII · CURRICULUM MAP I/R/M/A & TRACEABILITY</h1>
<?php if (!empty($payload['curriculum_map']) || !empty($payload['irma'])): ?>
<pre><?= $esc(json_encode($payload['curriculum_map'] ?? $payload['irma'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
<?php endif; ?>
<?php foreach ($courses as $course): ?>
<h2><?= $esc($this->courseCode($course) . ' · ' . $this->rowValue($course,array('name','nama','title'),'') ) ?></h2>
<div><b>CPL:</b> <?= $arrText($this->courseCpl($course)) ?></div>
<div><b>I/R/M/A:</b> <?= $arrText($this->courseIrma($course)) ?></div>
<div><b>CPMK:</b> <?= $arrText($this->courseCpmk($course)) ?></div>
<div><b>Sub-CPMK:</b> <?= $arrText($this->courseSub($course)) ?></div>
<div><b>Signature Output:</b> <?= $arrText($this->rowValue($course,array('signature_output','signature_outputs'),'') ) ?></div>
<div><b>Assessment:</b> <?= $arrText($this->listValue($course,array('assessments','assessment','assessment_blueprint'))) ?></div>
<div><b>Workload:</b> <?= $arrText($course['workload'] ?? array()) ?></div>
<?php endforeach; ?>

<h1>BAB IX · RPS OBE, EVIDENCE & PPEPP/CQI</h1>
<p>Seluruh mata kuliah pada versi ini telah memiliki RPS OBE PUBLISHED sesuai gate Kurikulum 095.</p>
<?php foreach (array('assessment_plan','evidence','evidence_index','ppepp','cqi','capa','references','qa') as $key): if (!isset($payload[$key])) continue; ?>
<h2><?= $esc(ucwords(str_replace('_',' ',$key))) ?></h2>
<pre><?= $esc(json_encode($payload[$key], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?></pre>
<?php endforeach; ?>

</body></html>
        <?php
        return ob_get_clean();
    }
}
