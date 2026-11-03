<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PATCH 091
 * Portable ChatGPT workflow for BSI E-Learning.
 *
 * Principles:
 * - additive staging tables;
 * - no mutation during download/upload/preview;
 * - apply only after complete preflight;
 * - existing live data always wins;
 * - no fake deadlines, exam times, attendance, submissions or grades.
 */
class Elearning_bsi_portable_model extends CI_Model
{
    const SCHEMA = 'UMSR_ELB_PACKAGE';
    const SCHEMA_VERSION = '3.0';

    public function tables_ready()
    {
        return $this->db->table_exists('elearning_bsi_portable_batch')
            && $this->db->table_exists('elearning_bsi_generated_content');
    }

    private function first_existing_field($table, array $candidates)
    {
        if (!$this->db->table_exists($table)) {
            return null;
        }
        foreach ($candidates as $field) {
            if ($this->db->field_exists($field, $table)) {
                return $field;
            }
        }
        return null;
    }

    private function canonicalize($value)
    {
        if (is_array($value)) {
            $assoc = array_keys($value) !== range(0, count($value) - 1);
            if ($assoc) {
                ksort($value);
            }
            foreach ($value as $k => $v) {
                $value[$k] = $this->canonicalize($v);
            }
        }
        return $value;
    }

    private function hash_value($value)
    {
        return hash('sha256', json_encode(
            $this->canonicalize($value),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    private function safe_row(array $row)
    {
        $out = array();
        foreach ($row as $key => $value) {
            $lower = strtolower((string)$key);
            if (preg_match('/password|passwd|pwd|secret|api_key|access_token|refresh_token|private_key/', $lower)) {
                continue;
            }
            if (preg_match('/file_path|storage_path|physical_path/', $lower)) {
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    private function row_hash(array $row, array $hashFields)
    {
        foreach ($hashFields as $field) {
            if (isset($row[$field]) && preg_match('/^[a-f0-9]{64}$/i', trim((string)$row[$field]))) {
                return strtolower(trim((string)$row[$field]));
            }
        }
        return $this->hash_value($this->safe_row($row));
    }

    public function get_class_context($idJadwal)
    {
        $idJadwal = (int)$idJadwal;
        if ($idJadwal <= 0 || !$this->db->table_exists('jadwal')) {
            return null;
        }

        $sql = "SELECT
                    j.id AS id_jadwal,
                    j.periode,
                    j.kode_prodi,
                    j.kode_matakuliah,
                    j.nama_kelas,
                    m.id AS id_matakuliah,
                    m.nama_matakuliah,
                    m.sks,
                    m.semester,
                    p.id AS id_prodi,
                    p.nama_prodi,
                    p.id_fakultas,
                    p.visi,
                    p.misi,
                    p.profil_prodi,
                    p.gelar_lulusan,
                    f.nama_fakultas
                FROM jadwal j
                LEFT JOIN matakuliah m
                  ON m.kode_matakuliah = j.kode_matakuliah
                 AND (m.kode_prodi = j.kode_prodi OR m.kode_prodi IS NULL OR m.kode_prodi = '')
                LEFT JOIN prodi p ON p.kode_prodi = j.kode_prodi
                LEFT JOIN fakultas f ON f.id = p.id_fakultas
                WHERE j.id = ?
                ORDER BY CASE WHEN m.kode_prodi = j.kode_prodi THEN 0 ELSE 1 END, m.id ASC
                LIMIT 1";

        $row = $this->db->query($sql, array($idJadwal))->row_array();
        return $row ? $this->safe_row($row) : null;
    }

    private function latest_active_row($table, $codeValue)
    {
        if (!$this->db->table_exists($table)) {
            return null;
        }

        $codeField = $this->first_existing_field(
            $table,
            array('kode_prodi', 'program_code', 'prodi_code')
        );
        if (!$codeField) {
            return null;
        }

        $this->db->where($codeField, (string)$codeValue);

        $statusField = $this->first_existing_field(
            $table,
            array('status', 'state', 'is_active')
        );
        if ($statusField) {
            $this->db->group_start()
                ->where_in($statusField, array('ACTIVE', 'AKTIF', 'Y', '1', 1))
                ->group_end();
        }

        $revisionField = $this->first_existing_field(
            $table,
            array('revision', 'revisi', 'version', 'id')
        );
        if ($revisionField) {
            $this->db->order_by($revisionField, 'DESC');
        }

        $row = $this->db->get($table, 1)->row_array();
        return $row ?: null;
    }

    public function get_foundation_snapshot($kodeProdi)
    {
        $table = 'prodi_academic_foundation';
        $row = $this->latest_active_row($table, $kodeProdi);
        if (!$row) {
            return array('available' => false);
        }

        $idField = $this->first_existing_field(
            $table,
            array('id', 'foundation_id', 'id_foundation')
        );
        $revisionField = $this->first_existing_field(
            $table,
            array('revision', 'revisi', 'version')
        );
        $id = $idField && isset($row[$idField]) ? (int)$row[$idField] : 0;

        $profiles = array();
        if ($id > 0 && $this->db->table_exists('prodi_profil_lulusan')) {
            $link = $this->first_existing_field(
                'prodi_profil_lulusan',
                array('foundation_id', 'id_foundation', 'academic_foundation_id')
            );
            if ($link) {
                $profiles = $this->db->where($link, $id)
                    ->order_by($this->first_existing_field('prodi_profil_lulusan', array('urutan', 'id')) ?: 'id', 'ASC')
                    ->get('prodi_profil_lulusan')->result_array();
            }
        }

        $peo = array();
        if ($id > 0 && $this->db->table_exists('prodi_peo')) {
            $link = $this->first_existing_field(
                'prodi_peo',
                array('foundation_id', 'id_foundation', 'academic_foundation_id')
            );
            if ($link) {
                $peo = $this->db->where($link, $id)
                    ->order_by($this->first_existing_field('prodi_peo', array('urutan', 'id')) ?: 'id', 'ASC')
                    ->get('prodi_peo')->result_array();
            }
        }

        $snapshot = array(
            'row' => $this->safe_row($row),
            'profiles' => array_map(array($this, 'safe_row'), $profiles),
            'peo' => array_map(array($this, 'safe_row'), $peo)
        );

        return array(
            'available' => true,
            'id' => $id,
            'revision' => $revisionField && isset($row[$revisionField]) ? (string)$row[$revisionField] : '1',
            'hash' => $this->row_hash(
                array('foundation' => $row, 'profiles' => $profiles, 'peo' => $peo),
                array('foundation_hash', 'payload_hash', 'context_hash')
            ),
            'data' => $snapshot
        );
    }

    public function get_curriculum_snapshot($kodeProdi)
    {
        $table = 'elbsi_curriculum';
        $row = $this->latest_active_row($table, $kodeProdi);
        if (!$row) {
            return array('available' => false);
        }

        $idField = $this->first_existing_field($table, array('id', 'curriculum_id', 'id_curriculum'));
        $revisionField = $this->first_existing_field($table, array('revision', 'revisi', 'version'));
        $payloadField = $this->first_existing_field(
            $table,
            array('payload_json', 'content_json', 'curriculum_json', 'payload', 'content')
        );

        $payload = array();
        if ($payloadField && isset($row[$payloadField]) && is_string($row[$payloadField])) {
            $decoded = json_decode($row[$payloadField], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return array(
            'available' => true,
            'id' => $idField && isset($row[$idField]) ? (int)$row[$idField] : 0,
            'revision' => $revisionField && isset($row[$revisionField]) ? (string)$row[$revisionField] : '1',
            'hash' => $this->row_hash($row, array('curriculum_hash', 'payload_hash', 'context_hash')),
            'data' => array(
                'row' => $this->safe_row($row),
                'payload' => $payload
            )
        );
    }

    private function extract_weeks($payload)
    {
        if (!is_array($payload)) {
            return array();
        }

        $candidates = array();
        if (isset($payload['weeks']) && is_array($payload['weeks'])) {
            $candidates = $payload['weeks'];
        } elseif (isset($payload['content']['weeks']) && is_array($payload['content']['weeks'])) {
            $candidates = $payload['content']['weeks'];
        } elseif (isset($payload['rps']['weeks']) && is_array($payload['rps']['weeks'])) {
            $candidates = $payload['rps']['weeks'];
        } elseif (isset($payload['rps']['content']['weeks']) && is_array($payload['rps']['content']['weeks'])) {
            $candidates = $payload['rps']['content']['weeks'];
        }

        $weeks = array();
        foreach ($candidates as $index => $week) {
            if (!is_array($week)) {
                continue;
            }
            $slot = isset($week['slot']) ? (int)$week['slot']
                : (isset($week['week']) ? (int)$week['week']
                : (isset($week['pertemuan_ke']) ? (int)$week['pertemuan_ke'] : ($index + 1)));
            if ($slot < 1 || $slot > 16) {
                continue;
            }

            if (empty($week['rps_week_hash'])) {
                $week['rps_week_hash'] = $this->hash_value($week);
            }
            $week['slot'] = $slot;
            $weeks[$slot] = $week;
        }
        ksort($weeks);
        return array_values($weeks);
    }

    private function rps_from_candidate_table($table, $idJadwal, $periode)
    {
        if (!$this->db->table_exists($table)) {
            return null;
        }

        $classField = $this->first_existing_field(
            $table,
            array('id_jadwal', 'class_id', 'jadwal_id')
        );
        if (!$classField) {
            return null;
        }

        $this->db->where($classField, (int)$idJadwal);

        $periodField = $this->first_existing_field(
            $table,
            array('periode', 'period')
        );
        if ($periodField && $periode !== '') {
            $this->db->where($periodField, (string)$periode);
        }

        $statusField = $this->first_existing_field($table, array('status', 'state'));
        if ($statusField) {
            $this->db->where_in(
                $statusField,
                array('PUBLISHED', 'READY', 'DISETUJUI', 'DISAHKAN', 'ACTIVE', 'AKTIF', 'Y')
            );
        }

        $orderField = $this->first_existing_field(
            $table,
            array('revision', 'revisi', 'version', 'id', 'id_rps')
        );
        if ($orderField) {
            $this->db->order_by($orderField, 'DESC');
        }

        $row = $this->db->get($table, 1)->row_array();
        if (!$row) {
            return null;
        }

        $payloadField = $this->first_existing_field(
            $table,
            array('payload_json', 'content_json', 'rps_json', 'payload', 'content')
        );
        $payload = array();
        if ($payloadField && isset($row[$payloadField])) {
            $decoded = json_decode((string)$row[$payloadField], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $weeks = $this->extract_weeks($payload);
        if (count($weeks) !== 16) {
            return null;
        }

        $idField = $this->first_existing_field($table, array('id', 'rps_version_id', 'id_rps'));
        $revisionField = $this->first_existing_field($table, array('revision', 'revisi', 'version'));

        return array(
            'available' => true,
            'source' => $table,
            'id' => $idField && isset($row[$idField]) ? (int)$row[$idField] : 0,
            'revision' => $revisionField && isset($row[$revisionField]) ? (string)$row[$revisionField] : '1',
            'hash' => $this->row_hash($row, array('rps_hash', 'payload_hash', 'context_hash')),
            'weeks' => $weeks,
            'data' => array('row' => $this->safe_row($row), 'payload' => $payload)
        );
    }

    private function rps_from_runtime_meetings($idJadwal)
    {
        if (!$this->db->table_exists('elearning_pertemuan')) {
            return null;
        }

        $rows = $this->db->where('id_jadwal', (int)$idJadwal)
            ->order_by('pertemuan_ke', 'ASC')
            ->get('elearning_pertemuan')->result_array();

        $weeks = array();
        foreach ($rows as $row) {
            $slot = isset($row['pertemuan_ke']) ? (int)$row['pertemuan_ke'] : 0;
            if ($slot < 1 || $slot > 16 || isset($weeks[$slot])) {
                continue;
            }
            $week = array(
                'slot' => $slot,
                'title' => isset($row['judul_pertemuan']) ? (string)$row['judul_pertemuan'] : '',
                'topic' => isset($row['judul_pertemuan']) ? (string)$row['judul_pertemuan'] : ''
            );
            $week['rps_week_hash'] = $this->hash_value($week);
            $weeks[$slot] = $week;
        }

        if (count($weeks) !== 16) {
            return null;
        }
        ksort($weeks);
        $weeks = array_values($weeks);

        return array(
            'available' => true,
            'source' => 'elearning_pertemuan_runtime_fallback',
            'id' => 0,
            'revision' => 'runtime',
            'hash' => $this->hash_value($weeks),
            'weeks' => $weeks,
            'data' => array('warning' => 'RPS lock dibentuk dari 16 pertemuan runtime karena tabel versi RPS terstruktur tidak terdeteksi.')
        );
    }

    public function get_rps_snapshot($idJadwal, $periode)
    {
        $tables = array(
            'elbsi_rps_version',
            'elbsi_rps',
            'elearning_rps_obe_version',
            'elearning_rps_obe',
            'rps_obe_version',
            'rps_obe'
        );

        foreach ($tables as $table) {
            $rps = $this->rps_from_candidate_table($table, $idJadwal, $periode);
            if ($rps) {
                return $rps;
            }
        }

        $fallback = $this->rps_from_runtime_meetings($idJadwal);
        return $fallback ?: array('available' => false);
    }

    public function build_locked_context($idJadwal)
    {
        $class = $this->get_class_context($idJadwal);
        if (!$class) {
            return array('ready' => false, 'errors' => array('Kelas tidak ditemukan.'));
        }

        $errors = array();
        $foundation = $this->get_foundation_snapshot($class['kode_prodi']);
        if (empty($foundation['available'])) {
            $errors[] = 'Fondasi Akademik Prodi ACTIVE belum tersedia.';
        }

        $curriculum = $this->get_curriculum_snapshot($class['kode_prodi']);
        if (empty($curriculum['available'])) {
            $errors[] = 'Kurikulum ACTIVE untuk Prodi ini belum tersedia.';
        }

        $rps = $this->get_rps_snapshot($idJadwal, (string)$class['periode']);
        if (empty($rps['available'])) {
            $errors[] = 'RPS OBE siap dengan tepat 16 pertemuan belum tersedia.';
        }

        $sks = isset($class['sks']) ? (float)$class['sks'] : 0;
        if ($sks <= 0) {
            $errors[] = 'SKS mata kuliah tidak valid.';
        }

        $weekLocks = array();
        if (!empty($rps['weeks'])) {
            foreach ($rps['weeks'] as $week) {
                $weekLocks[] = array(
                    'slot' => (int)$week['slot'],
                    'rps_week_hash' => (string)$week['rps_week_hash']
                );
            }
        }

        $locks = array(
            'foundation_id' => !empty($foundation['id']) ? (int)$foundation['id'] : 0,
            'foundation_revision' => isset($foundation['revision']) ? (string)$foundation['revision'] : '',
            'foundation_hash' => isset($foundation['hash']) ? (string)$foundation['hash'] : '',
            'curriculum_id' => !empty($curriculum['id']) ? (int)$curriculum['id'] : 0,
            'curriculum_revision' => isset($curriculum['revision']) ? (string)$curriculum['revision'] : '',
            'curriculum_hash' => isset($curriculum['hash']) ? (string)$curriculum['hash'] : '',
            'rps_id' => !empty($rps['id']) ? (int)$rps['id'] : 0,
            'rps_revision' => isset($rps['revision']) ? (string)$rps['revision'] : '',
            'rps_hash' => isset($rps['hash']) ? (string)$rps['hash'] : '',
            'weeks' => $weekLocks
        );

        return array(
            'ready' => empty($errors),
            'errors' => $errors,
            'class' => $class,
            'foundation' => $foundation,
            'curriculum' => $curriculum,
            'rps' => $rps,
            'locks' => $locks,
            'workload' => array(
                'credits' => $sks,
                'target_total_hours' => $sks * 45,
                'minimum_active_hours' => $sks * 45 * 0.60
            )
        );
    }

    public function make_output_template(array $context)
    {
        $meetings = array();
        foreach ($context['rps']['weeks'] as $week) {
            $slot = (int)$week['slot'];
            $regular = $slot !== 8 && $slot !== 16;
            $meetings[] = array(
                'slot' => $slot,
                'title' => isset($week['title']) ? (string)$week['title'] : (isset($week['topic']) ? (string)$week['topic'] : ''),
                'topic' => isset($week['topic']) ? (string)$week['topic'] : (isset($week['title']) ? (string)$week['title'] : ''),
                'sub_codes' => isset($week['sub_codes']) && is_array($week['sub_codes']) ? array_values($week['sub_codes']) : array(),
                'rps_week_hash' => (string)$week['rps_week_hash'],
                'subtopic' => '',
                'summary' => '',
                'method' => isset($week['method']) ? (string)$week['method'] : '',
                'lecturer_activity' => '',
                'student_activity' => '',
                'sources' => array(),
                'workload' => array(
                    'total_hours' => 0,
                    'active_hours' => 0
                ),
                'material' => array(
                    'title' => '',
                    'description' => ''
                ),
                'task' => $regular ? array(
                    'title' => '',
                    'description' => '',
                    'rubric' => '',
                    'outcomes' => array(),
                    'classification' => 'FORMATIVE',
                    'assessment_code' => ''
                ) : null
            );
        }

        $exam = array(
            'mode' => 'MCQ',
            'minimum_questions' => 50,
            'questions' => array()
        );

        return array(
            'schema' => self::SCHEMA,
            'schema_version' => self::SCHEMA_VERSION,
            'scope' => array(
                'class_id' => (int)$context['class']['id_jadwal'],
                'period' => (string)$context['class']['periode']
            ),
            'locks' => $context['locks'],
            'content' => array(
                'meetings' => $meetings,
                'exams' => array(
                    'UTS' => array_merge(array('slot' => 8, 'title' => 'UTS'), $exam),
                    'UAS' => array_merge(array('slot' => 16, 'title' => 'UAS'), $exam)
                )
            ),
            'workload' => array(
                'credits' => (float)$context['workload']['credits'],
                'target_total_hours' => (float)$context['workload']['target_total_hours'],
                'minimum_active_hours' => (float)$context['workload']['minimum_active_hours'],
                'total_hours' => 0,
                'active_hours' => 0
            )
        );
    }

    public function build_instruction_text(array $context)
    {
        $template = $this->make_output_template($context);
        $safeContext = array(
            'class' => $context['class'],
            'foundation' => !empty($context['foundation']['data']) ? $context['foundation']['data'] : array(),
            'curriculum' => !empty($context['curriculum']['data']) ? $context['curriculum']['data'] : array(),
            'rps' => array(
                'source' => isset($context['rps']['source']) ? $context['rps']['source'] : '',
                'id' => isset($context['rps']['id']) ? $context['rps']['id'] : 0,
                'revision' => isset($context['rps']['revision']) ? $context['rps']['revision'] : '',
                'weeks' => isset($context['rps']['weeks']) ? $context['rps']['weeks'] : array(),
                'data' => isset($context['rps']['data']) ? $context['rps']['data'] : array()
            )
        );

        $lines = array(
            '============================================================',
            'PERINTAH — LENGKAPI E-LEARNING UMS RAPPANG',
            'JALUR CHATGPT MANUAL / PORTABLE V3',
            '============================================================',
            '',
            'CARA PAKAI:',
            '1. Upload file perintah ini ke ChatGPT.',
            '2. Minta ChatGPT mengerjakan seluruh instruksi dan membuat SATU file JSON.',
            '3. Download JSON hasil ChatGPT.',
            '4. Kembali ke kelas yang sama pada Portal BSI dan pilih Upload JSON.',
            '5. Portal wajib menampilkan PREFLIGHT/PREVIEW sebelum paket dapat diterapkan.',
            '',
            'BATAS KEAMANAN:',
            '- Jangan mengubah class_id, period, seluruh hash lock, slot, atau rps_week_hash.',
            '- Jangan memasukkan username, password, API key, data mahasiswa, nilai, presensi, atau data pribadi.',
            '- AI hanya menyusun konten pembelajaran. Foundation, Kurikulum dan RPS adalah sumber kebenaran Portal.',
            '- Soal BARU hanya PILIHAN GANDA (PG/MCQ). Jangan membuat Essay baru.',
            '- Pertemuan 8 adalah UTS dan 16 adalah UAS; task pada kedua slot harus null.',
            '- Tugas wajib pada slot 1-7 dan 9-15. Rubrik setiap tugas minimal 500 karakter Unicode.',
            '- UTS minimal 50 PG dan UAS minimal 50 PG, masing-masing satu kunci A-E yang sah.',
            '- Materi harus berupa modul belajar substantif, bukan paragraf ringkas, dan memiliki sumber yang dapat ditelusuri.',
            '- Workload total semester HARUS sama dengan SKS x 45 jam.',
            '- Active student learning HARUS minimal 60% dari workload total.',
            '- Jangan menentukan tanggal perkuliahan, deadline real tugas, waktu UTS/UAS, presensi, nilai, atau dokumentasi.',
            '- Jangan menghapus atau mengganti data kelas yang sudah digunakan.',
            '',
            'KONTEKS AKADEMIK TERKUNCI:',
            json_encode($safeContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            '',
            'TEMPLATE OUTPUT WAJIB:',
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            '',
            'SELF-CHECK WAJIB SEBELUM MENYERAHKAN JSON:',
            '1. Tepat 16 meeting unik (slot 1-16).',
            '2. Seluruh hash/scope sama persis dengan template.',
            '3. Slot reguler mempunyai material dan task lengkap; slot 8/16 task=null.',
            '4. Setiap rubrik tugas >=500 karakter dan menjelaskan kriteria, bobot, level 1-4, serta skoring.',
            '5. UTS >=50 PG dan UAS >=50 PG; tidak ada ESSAY.',
            '6. Tidak ada teks soal duplikat.',
            '7. Jumlah workload meeting = target_total_hours dan active_hours >= minimum_active_hours.',
            '8. File dapat diparse sebagai JSON UTF-8 tanpa markdown/komentar.',
            '',
            'HASIL AKHIR: hanya satu file JSON siap Upload JSON ke Portal BSI.'
        );

        return implode("\n", $lines);
    }

    private function unicode_length($value)
    {
        $value = trim((string)$value);
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function normalize_question($text)
    {
        $text = strtolower(trim((string)$text));
        return preg_replace('/\s+/', ' ', $text);
    }

    private function lock_mismatch(array $given, array $expected)
    {
        foreach (array(
            'foundation_id', 'foundation_revision', 'foundation_hash',
            'curriculum_id', 'curriculum_revision', 'curriculum_hash',
            'rps_id', 'rps_revision', 'rps_hash'
        ) as $key) {
            if ((string)(isset($given[$key]) ? $given[$key] : '')
                !== (string)(isset($expected[$key]) ? $expected[$key] : '')) {
                return $key;
            }
        }

        $givenWeeks = isset($given['weeks']) && is_array($given['weeks']) ? $given['weeks'] : array();
        $expectedWeeks = isset($expected['weeks']) && is_array($expected['weeks']) ? $expected['weeks'] : array();
        if ($this->hash_value($givenWeeks) !== $this->hash_value($expectedWeeks)) {
            return 'weeks';
        }
        return null;
    }

    public function validate_package(array $package, array $context)
    {
        $errors = array();

        if (!isset($package['schema']) || $package['schema'] !== self::SCHEMA) {
            $errors[] = 'schema harus ' . self::SCHEMA . '.';
        }
        if (!isset($package['schema_version']) || (string)$package['schema_version'] !== self::SCHEMA_VERSION) {
            $errors[] = 'schema_version harus ' . self::SCHEMA_VERSION . '.';
        }

        $scope = isset($package['scope']) && is_array($package['scope']) ? $package['scope'] : array();
        if ((int)(isset($scope['class_id']) ? $scope['class_id'] : 0) !== (int)$context['class']['id_jadwal']) {
            $errors[] = 'class_id tidak cocok dengan kelas ini.';
        }
        if ((string)(isset($scope['period']) ? $scope['period'] : '') !== (string)$context['class']['periode']) {
            $errors[] = 'period tidak cocok dengan kelas ini.';
        }

        $locks = isset($package['locks']) && is_array($package['locks']) ? $package['locks'] : array();
        $mismatch = $this->lock_mismatch($locks, $context['locks']);
        if ($mismatch !== null) {
            $errors[] = 'Locked context berubah/tidak cocok pada ' . $mismatch . '. Download ulang perintah.';
        }

        $meetings = isset($package['content']['meetings']) && is_array($package['content']['meetings'])
            ? $package['content']['meetings'] : array();
        if (count($meetings) !== 16) {
            $errors[] = 'Meetings harus tepat 16.';
        }

        $bySlot = array();
        $totalHours = 0.0;
        $activeHours = 0.0;

        foreach ($meetings as $meeting) {
            if (!is_array($meeting)) {
                $errors[] = 'Meeting tidak valid.';
                continue;
            }
            $slot = isset($meeting['slot']) ? (int)$meeting['slot'] : 0;
            if ($slot < 1 || $slot > 16 || isset($bySlot[$slot])) {
                $errors[] = 'Slot meeting harus unik 1-16.';
                continue;
            }
            $bySlot[$slot] = $meeting;

            foreach (array('title', 'topic', 'summary', 'method', 'student_activity') as $field) {
                if (trim((string)(isset($meeting[$field]) ? $meeting[$field] : '')) === '') {
                    $errors[] = 'Slot ' . $slot . ': ' . $field . ' wajib diisi.';
                }
            }

            $material = isset($meeting['material']) && is_array($meeting['material']) ? $meeting['material'] : array();
            if ($slot !== 8 && $slot !== 16) {
                if (trim((string)(isset($material['title']) ? $material['title'] : '')) === '') {
                    $errors[] = 'Slot ' . $slot . ': judul materi wajib.';
                }
                if ($this->unicode_length(isset($material['description']) ? $material['description'] : '') < 1000) {
                    $errors[] = 'Slot ' . $slot . ': modul materi terlalu ringkas (minimal 1000 karakter).';
                }
                $sources = isset($meeting['sources']) && is_array($meeting['sources']) ? $meeting['sources'] : array();
                if (count($sources) < 2) {
                    $errors[] = 'Slot ' . $slot . ': minimal 2 sumber.';
                }
            }

            $work = isset($meeting['workload']) && is_array($meeting['workload']) ? $meeting['workload'] : array();
            $th = isset($work['total_hours']) ? (float)$work['total_hours'] : 0;
            $ah = isset($work['active_hours']) ? (float)$work['active_hours'] : 0;
            if ($th < 0 || $ah < 0 || $ah > $th + 0.001) {
                $errors[] = 'Slot ' . $slot . ': workload tidak valid.';
            }
            $totalHours += $th;
            $activeHours += $ah;

            if ($slot === 8 || $slot === 16) {
                if (array_key_exists('task', $meeting) && $meeting['task'] !== null) {
                    $errors[] = 'Slot ' . $slot . ': task harus null.';
                }
            } else {
                $task = isset($meeting['task']) && is_array($meeting['task']) ? $meeting['task'] : array();
                foreach (array('title', 'description', 'rubric') as $field) {
                    if (trim((string)(isset($task[$field]) ? $task[$field] : '')) === '') {
                        $errors[] = 'Slot ' . $slot . ': task.' . $field . ' wajib.';
                    }
                }
                if ($this->unicode_length(isset($task['rubric']) ? $task['rubric'] : '') < 500) {
                    $errors[] = 'Slot ' . $slot . ': rubrik tugas minimal 500 karakter.';
                }
                $classification = strtoupper(trim((string)(isset($task['classification']) ? $task['classification'] : '')));
                if (!in_array($classification, array('FORMATIVE', 'SUMMATIVE'), true)) {
                    $errors[] = 'Slot ' . $slot . ': classification harus FORMATIVE/SUMMATIVE.';
                }
            }
        }

        for ($slot = 1; $slot <= 16; $slot++) {
            if (!isset($bySlot[$slot])) {
                $errors[] = 'Slot ' . $slot . ' tidak ditemukan.';
            }
        }

        $examQuestionSeen = array();
        foreach (array('UTS', 'UAS') as $examName) {
            $exam = isset($package['content']['exams'][$examName]) && is_array($package['content']['exams'][$examName])
                ? $package['content']['exams'][$examName] : array();
            if (strtoupper((string)(isset($exam['mode']) ? $exam['mode'] : '')) !== 'MCQ') {
                $errors[] = $examName . ': mode harus MCQ.';
            }
            $questions = isset($exam['questions']) && is_array($exam['questions']) ? $exam['questions'] : array();
            if (count($questions) < 50) {
                $errors[] = $examName . ': minimal 50 soal PG.';
            }
            foreach ($questions as $i => $question) {
                if (!is_array($question)) {
                    $errors[] = $examName . ' soal #' . ($i + 1) . ' tidak valid.';
                    continue;
                }
                $type = strtoupper((string)(isset($question['type']) ? $question['type'] : 'PG'));
                if (!in_array($type, array('PG', 'MCQ'), true)) {
                    $errors[] = $examName . ' soal #' . ($i + 1) . ': hanya PG diperbolehkan.';
                }
                $text = trim((string)(isset($question['question']) ? $question['question'] : ''));
                if ($text === '') {
                    $errors[] = $examName . ' soal #' . ($i + 1) . ': pertanyaan kosong.';
                }
                $normalized = $this->normalize_question($text);
                if ($normalized !== '' && isset($examQuestionSeen[$normalized])) {
                    $errors[] = $examName . ' soal #' . ($i + 1) . ': duplikat soal terdeteksi.';
                }
                if ($normalized !== '') {
                    $examQuestionSeen[$normalized] = true;
                }

                $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : array();
                foreach (array('A', 'B', 'C', 'D') as $letter) {
                    if (trim((string)(isset($options[$letter]) ? $options[$letter] : '')) === '') {
                        $errors[] = $examName . ' soal #' . ($i + 1) . ': opsi ' . $letter . ' wajib.';
                    }
                }
                $answer = strtoupper(trim((string)(isset($question['answer']) ? $question['answer'] : '')));
                if (!in_array($answer, array('A', 'B', 'C', 'D', 'E'), true) || !isset($options[$answer])) {
                    $errors[] = $examName . ' soal #' . ($i + 1) . ': kunci jawaban tidak sah.';
                }
            }
        }

        $expectedTotal = (float)$context['workload']['target_total_hours'];
        $expectedActive = (float)$context['workload']['minimum_active_hours'];

        $rootWorkload = isset($package['workload']) && is_array($package['workload']) ? $package['workload'] : array();
        $rootTotal = isset($rootWorkload['total_hours']) ? (float)$rootWorkload['total_hours'] : $totalHours;
        $rootActive = isset($rootWorkload['active_hours']) ? (float)$rootWorkload['active_hours'] : $activeHours;

        if (abs($totalHours - $expectedTotal) > 0.10 || abs($rootTotal - $expectedTotal) > 0.10) {
            $errors[] = 'Total workload harus tepat SKS x 45 = ' . $expectedTotal . ' jam.';
        }
        if ($activeHours + 0.10 < $expectedActive || $rootActive + 0.10 < $expectedActive) {
            $errors[] = 'Active student learning minimal 60% = ' . $expectedActive . ' jam.';
        }
        if (abs($rootTotal - $totalHours) > 0.10 || abs($rootActive - $activeHours) > 0.10) {
            $errors[] = 'Ringkasan workload tidak sama dengan jumlah workload 16 meeting.';
        }

        return array(
            'ok' => empty($errors),
            'errors' => array_values(array_unique($errors)),
            'counts' => array(
                'meetings' => count($bySlot),
                'tasks' => count(array_filter(array_keys($bySlot), function ($slot) use ($bySlot) {
                    return $slot !== 8 && $slot !== 16 && isset($bySlot[$slot]['task']) && is_array($bySlot[$slot]['task']);
                })),
                'uts_questions' => isset($package['content']['exams']['UTS']['questions']) && is_array($package['content']['exams']['UTS']['questions'])
                    ? count($package['content']['exams']['UTS']['questions']) : 0,
                'uas_questions' => isset($package['content']['exams']['UAS']['questions']) && is_array($package['content']['exams']['UAS']['questions'])
                    ? count($package['content']['exams']['UAS']['questions']) : 0,
                'total_hours' => $totalHours,
                'active_hours' => $activeHours
            )
        );
    }

    private function get_meeting($idJadwal, $slot)
    {
        if (!$this->db->table_exists('elearning_pertemuan')) {
            return null;
        }
        return $this->db
            ->where('id_jadwal', (int)$idJadwal)
            ->where('pertemuan_ke', (int)$slot)
            ->order_by('id_pertemuan', 'ASC')
            ->get('elearning_pertemuan', 1)->row_array();
    }

    private function meeting_has_material($idPertemuan)
    {
        return $this->db->table_exists('elearning_materi')
            && $this->db->where('id_pertemuan', (int)$idPertemuan)->count_all_results('elearning_materi') > 0;
    }

    private function meeting_has_task($idPertemuan)
    {
        return $this->db->table_exists('elearning_tugas')
            && $this->db->where('id_pertemuan', (int)$idPertemuan)->count_all_results('elearning_tugas') > 0;
    }

    private function exam_state($idJadwal, $jenis)
    {
        $state = array(
            'exists' => false,
            'id_quiz' => 0,
            'questions' => 0,
            'answers' => 0,
            'grades' => 0,
            'safe_attach' => false
        );
        if (!$this->db->table_exists('elearning_quiz')) {
            return $state;
        }

        $quiz = $this->db->where('id_jadwal', (int)$idJadwal)
            ->where('jenis_ujian', (string)$jenis)
            ->order_by('id_quiz', 'DESC')
            ->get('elearning_quiz', 1)->row_array();
        if (!$quiz) {
            return $state;
        }

        $idQuiz = (int)$quiz['id_quiz'];
        $state['exists'] = true;
        $state['id_quiz'] = $idQuiz;
        if ($this->db->table_exists('elearning_quiz_soal')) {
            $state['questions'] = (int)$this->db->where('id_quiz', $idQuiz)->count_all_results('elearning_quiz_soal');
        }
        if ($this->db->table_exists('elearning_quiz_jawaban')) {
            $state['answers'] = (int)$this->db->where('id_quiz', $idQuiz)->count_all_results('elearning_quiz_jawaban');
        }
        if ($this->db->table_exists('elearning_quiz_nilai')) {
            $state['grades'] = (int)$this->db->where('id_quiz', $idQuiz)->count_all_results('elearning_quiz_nilai');
        }
        $state['safe_attach'] = $state['questions'] === 0 && $state['answers'] === 0 && $state['grades'] === 0;
        return $state;
    }

    public function build_preview($idJadwal, array $package)
    {
        $rows = array();
        $summary = array(
            'add_meetings' => 0,
            'attach_materials' => 0,
            'prepared_tasks' => 0,
            'protected_items' => 0,
            'attach_exam_questions' => 0,
            'prepared_exams' => 0
        );

        foreach ($package['content']['meetings'] as $meetingPayload) {
            $slot = (int)$meetingPayload['slot'];
            $existing = $this->get_meeting($idJadwal, $slot);

            $meetingAction = $existing ? 'KEEP_EXISTING' : 'ADD_DRAFT';
            if (!$existing) {
                $summary['add_meetings']++;
            }

            $materialAction = 'ATTACH_GENERATED';
            $taskAction = ($slot === 8 || $slot === 16) ? 'N/A' : 'PREPARED_NEEDS_DEADLINE';

            if ($existing) {
                $idPertemuan = (int)$existing['id_pertemuan'];
                if ($this->meeting_has_material($idPertemuan)) {
                    $materialAction = 'PROTECTED_EXISTING';
                    $summary['protected_items']++;
                } else {
                    $summary['attach_materials']++;
                }

                if ($slot !== 8 && $slot !== 16) {
                    if ($this->meeting_has_task($idPertemuan)) {
                        $taskAction = 'PROTECTED_EXISTING';
                        $summary['protected_items']++;
                    } else {
                        $summary['prepared_tasks']++;
                    }
                }
            } else {
                $summary['attach_materials']++;
                if ($slot !== 8 && $slot !== 16) {
                    $summary['prepared_tasks']++;
                }
            }

            $rows[] = array(
                'slot' => $slot,
                'title' => (string)$meetingPayload['title'],
                'meeting' => $meetingAction,
                'material' => $slot === 8 || $slot === 16 ? 'N/A' : $materialAction,
                'task' => $taskAction
            );
        }

        $exams = array();
        foreach (array('UTS', 'UAS') as $jenis) {
            $state = $this->exam_state($idJadwal, $jenis);
            if (!$state['exists']) {
                $action = 'PREPARED_NEEDS_SCHEDULE';
                $summary['prepared_exams']++;
            } elseif ($state['safe_attach']) {
                $action = 'ATTACH_PG_TO_EXISTING_EXAM';
                $summary['attach_exam_questions'] += count($package['content']['exams'][$jenis]['questions']);
            } else {
                $action = 'PROTECTED_EXISTING';
                $summary['protected_items']++;
            }
            $exams[$jenis] = array(
                'action' => $action,
                'state' => $state,
                'questions_in_json' => count($package['content']['exams'][$jenis]['questions'])
            );
        }

        return array(
            'rows' => $rows,
            'exams' => $exams,
            'summary' => $summary,
            'guard_note' => 'Presensi, submission, nilai, feedback, KRS, jadwal, pengampu, tanggal pelaksanaan dan dokumentasi tidak pernah ditulis oleh import 091.'
        );
    }

    public function create_preview_batch($idJadwal, array $package, array $context, array $preview)
    {
        $payloadJson = json_encode($package, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $payloadJson);
        $actor = (string)$this->session->userdata('id_user');
        if ($actor === '') {
            $actor = (string)$this->session->userdata('username');
        }

        $data = array(
            'id_jadwal' => (int)$idJadwal,
            'periode' => (string)$context['class']['periode'],
            'schema_version' => self::SCHEMA_VERSION,
            'package_hash' => $hash,
            'lock_json' => json_encode($context['locks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'payload_json' => $payloadJson,
            'preview_json' => json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'PREVIEW',
            'created_by' => $actor,
            'created_at' => date('Y-m-d H:i:s')
        );
        $this->db->insert('elearning_bsi_portable_batch', $data);
        return (int)$this->db->insert_id();
    }

    public function get_batch($id, $idJadwal = null)
    {
        if (!$this->tables_ready()) {
            return null;
        }
        $this->db->where('id', (int)$id);
        if ($idJadwal !== null) {
            $this->db->where('id_jadwal', (int)$idJadwal);
        }
        return $this->db->get('elearning_bsi_portable_batch', 1)->row_array();
    }

    public function get_history($idJadwal, $limit = 30)
    {
        if (!$this->tables_ready()) {
            return array();
        }
        return $this->db->where('id_jadwal', (int)$idJadwal)
            ->order_by('id', 'DESC')
            ->limit(max(1, min(100, (int)$limit)))
            ->get('elearning_bsi_portable_batch')->result_array();
    }

    private function new_token()
    {
        try {
            return bin2hex(random_bytes(24));
        } catch (Exception $e) {
            return bin2hex(openssl_random_pseudo_bytes(24));
        }
    }

    private function save_generated($batchId, $idJadwal, $idPertemuan, $slot, $type, $title, array $content, $state)
    {
        $token = $this->new_token();
        $this->db->insert('elearning_bsi_generated_content', array(
            'id_jadwal' => (int)$idJadwal,
            'id_pertemuan' => $idPertemuan ? (int)$idPertemuan : null,
            'slot' => $slot ? (int)$slot : null,
            'content_type' => (string)$type,
            'title' => substr((string)$title, 0, 255),
            'content_json' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'public_token' => $token,
            'state' => (string)$state,
            'source_batch_id' => (int)$batchId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null
        ));
        return $token;
    }

    private function insert_material_link($idPertemuan, $title, $token)
    {
        if (!$this->db->table_exists('elearning_materi') || $this->meeting_has_material($idPertemuan)) {
            return false;
        }

        $this->db->insert('elearning_materi', array(
            'id_pertemuan' => (int)$idPertemuan,
            'nama_materi' => substr((string)$title, 0, 255),
            'jenis' => 'link',
            'file_url' => base_url('pengguna/bsi/Elearning_portable/material/' . rawurlencode($token))
        ));
        return $this->db->affected_rows() > 0;
    }

    private function attach_exam_questions($idQuiz, array $questions)
    {
        if (!$this->db->table_exists('elearning_quiz_soal')) {
            return 0;
        }
        $rows = array();
        foreach ($questions as $question) {
            $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : array();
            $rows[] = array(
                'id_quiz' => (int)$idQuiz,
                'jenis_soal' => 'PG',
                'pertanyaan' => (string)$question['question'],
                'rubrik_penilaian' => isset($question['explanation']) ? (string)$question['explanation'] : null,
                'bobot_soal' => 1,
                'pilihan_a' => isset($options['A']) ? (string)$options['A'] : '',
                'pilihan_b' => isset($options['B']) ? (string)$options['B'] : '',
                'pilihan_c' => isset($options['C']) ? (string)$options['C'] : '',
                'pilihan_d' => isset($options['D']) ? (string)$options['D'] : '',
                'pilihan_e' => isset($options['E']) ? (string)$options['E'] : null,
                'kunci_jawaban' => strtoupper((string)$question['answer']),
                'waktu_dibuat' => date('Y-m-d H:i:s')
            );
        }
        if (!$rows) {
            return 0;
        }
        $this->db->insert_batch('elearning_quiz_soal', $rows);
        return count($rows);
    }

    public function apply_batch($batchId, $idJadwal)
    {
        $batch = $this->get_batch($batchId, $idJadwal);
        if (!$batch || $batch['status'] !== 'PREVIEW') {
            return array('ok' => false, 'message' => 'Batch preview tidak ditemukan atau sudah diproses.');
        }

        $package = json_decode($batch['payload_json'], true);
        if (!is_array($package)) {
            return array('ok' => false, 'message' => 'Payload batch rusak.');
        }

        $context = $this->build_locked_context($idJadwal);
        if (empty($context['ready'])) {
            return array('ok' => false, 'message' => implode(' ', $context['errors']));
        }

        $validation = $this->validate_package($package, $context);
        if (empty($validation['ok'])) {
            return array('ok' => false, 'message' => 'Context/paket berubah. ' . implode(' ', array_slice($validation['errors'], 0, 5)));
        }

        $preview = $this->build_preview($idJadwal, $package);
        $stats = array(
            'meetings_created' => 0,
            'materials_attached' => 0,
            'materials_protected' => 0,
            'tasks_prepared' => 0,
            'tasks_protected' => 0,
            'exam_questions_attached' => 0,
            'exams_prepared' => 0,
            'exams_protected' => 0
        );

        $this->db->trans_begin();

        foreach ($package['content']['meetings'] as $meetingPayload) {
            $slot = (int)$meetingPayload['slot'];
            $meeting = $this->get_meeting($idJadwal, $slot);

            if (!$meeting) {
                $this->db->insert('elearning_pertemuan', array(
                    'id_jadwal' => (int)$idJadwal,
                    'nip' => null,
                    'pertemuan_ke' => $slot,
                    'judul_pertemuan' => substr((string)$meetingPayload['title'], 0, 255),
                    'tanggal_pelaksanaan' => null,
                    'status_pertemuan' => 'draft'
                ));
                $meeting = $this->get_meeting($idJadwal, $slot);
                $stats['meetings_created']++;
            }

            if (!$meeting) {
                $this->db->trans_rollback();
                return array('ok' => false, 'message' => 'Gagal membuat meeting slot ' . $slot . '.');
            }

            $idPertemuan = (int)$meeting['id_pertemuan'];

            if ($slot !== 8 && $slot !== 16) {
                $material = $meetingPayload['material'];
                $materialState = $this->meeting_has_material($idPertemuan) ? 'PROTECTED' : 'PREPARED';
                $materialToken = $this->save_generated(
                    $batchId,
                    $idJadwal,
                    $idPertemuan,
                    $slot,
                    'MATERIAL',
                    $material['title'],
                    array(
                        'material' => $material,
                        'summary' => $meetingPayload['summary'],
                        'method' => $meetingPayload['method'],
                        'lecturer_activity' => isset($meetingPayload['lecturer_activity']) ? $meetingPayload['lecturer_activity'] : '',
                        'student_activity' => $meetingPayload['student_activity'],
                        'sources' => $meetingPayload['sources'],
                        'workload' => $meetingPayload['workload'],
                        'sub_codes' => isset($meetingPayload['sub_codes']) ? $meetingPayload['sub_codes'] : array()
                    ),
                    $materialState
                );

                if ($materialState === 'PROTECTED') {
                    $stats['materials_protected']++;
                } elseif ($this->insert_material_link($idPertemuan, $material['title'], $materialToken)) {
                    $this->db->where('public_token', $materialToken)
                        ->update('elearning_bsi_generated_content', array('state' => 'ATTACHED', 'updated_at' => date('Y-m-d H:i:s')));
                    $stats['materials_attached']++;
                }

                $task = $meetingPayload['task'];
                if ($this->meeting_has_task($idPertemuan)) {
                    $this->save_generated(
                        $batchId, $idJadwal, $idPertemuan, $slot, 'TASK',
                        $task['title'], $task, 'PROTECTED'
                    );
                    $stats['tasks_protected']++;
                } else {
                    $this->save_generated(
                        $batchId, $idJadwal, $idPertemuan, $slot, 'TASK',
                        $task['title'], $task, 'PREPARED'
                    );
                    $stats['tasks_prepared']++;
                }
            }
        }

        foreach (array('UTS', 'UAS') as $jenis) {
            $examPayload = $package['content']['exams'][$jenis];
            $state = $this->exam_state($idJadwal, $jenis);
            if ($state['exists'] && $state['safe_attach']) {
                $token = $this->save_generated(
                    $batchId, $idJadwal, null, (int)$examPayload['slot'], $jenis,
                    $examPayload['title'], $examPayload, 'PREPARED'
                );
                $attached = $this->attach_exam_questions($state['id_quiz'], $examPayload['questions']);
                if ($attached > 0) {
                    $this->db->where('public_token', $token)
                        ->update('elearning_bsi_generated_content', array('state' => 'ATTACHED', 'updated_at' => date('Y-m-d H:i:s')));
                    $stats['exam_questions_attached'] += $attached;
                }
            } elseif (!$state['exists']) {
                $this->save_generated(
                    $batchId, $idJadwal, null, (int)$examPayload['slot'], $jenis,
                    $examPayload['title'], $examPayload, 'PREPARED'
                );
                $stats['exams_prepared']++;
            } else {
                $this->save_generated(
                    $batchId, $idJadwal, null, (int)$examPayload['slot'], $jenis,
                    $examPayload['title'], $examPayload, 'PROTECTED'
                );
                $stats['exams_protected']++;
            }
        }

        $this->db->where('id', (int)$batchId)->update('elearning_bsi_portable_batch', array(
            'preview_json' => json_encode(array('preview' => $preview, 'apply_stats' => $stats), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'APPLIED',
            'applied_at' => date('Y-m-d H:i:s')
        ));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array('ok' => false, 'message' => 'Transaksi gagal. Tidak ada perubahan parsial yang dipertahankan.');
        }

        $this->db->trans_commit();
        return array('ok' => true, 'stats' => $stats);
    }

    public function get_generated_by_token($token)
    {
        if (!$this->db->table_exists('elearning_bsi_generated_content')) {
            return null;
        }
        return $this->db->where('public_token', (string)$token)
            ->where('content_type', 'MATERIAL')
            ->get('elearning_bsi_generated_content', 1)->row_array();
    }

    public function can_read_generated(array $content)
    {
        $role = (string)$this->session->userdata('hak_akses');
        if ($role === '17') {
            return true;
        }

        $idJadwal = (int)$content['id_jadwal'];
        $nim = trim((string)$this->session->userdata('nim'));
        if ($nim !== '' && $this->db->table_exists('krsnilaikhs')) {
            $q = $this->db->where('nim', $nim)->where('id_jadwal', $idJadwal)->count_all_results('krsnilaikhs');
            if ($q > 0) {
                return true;
            }
        }

        $nip = trim((string)$this->session->userdata('nip'));
        if ($nip !== '' && $this->db->table_exists('jadwal')) {
            $jadwal = $this->db->where('id', $idJadwal)->get('jadwal', 1)->row_array();
            if ($jadwal) {
                $pattern = '/(^|[^0-9A-Za-z])' . preg_quote($nip, '/') . '($|[^0-9A-Za-z])/';
                foreach ($jadwal as $key => $value) {
                    if ((stripos((string)$key, 'dosen') !== false || stripos((string)$key, 'nip') !== false)
                        && preg_match($pattern, (string)$value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
