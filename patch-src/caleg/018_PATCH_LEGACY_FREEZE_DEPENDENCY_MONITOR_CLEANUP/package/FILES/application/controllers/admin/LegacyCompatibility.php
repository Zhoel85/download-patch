<?php
defined('BASEPATH') or exit('No direct script access allowed');

class LegacyCompatibility extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('id') || (string) $this->session->userdata('hak_akses') !== '1') {
            redirect('Auth');
            return;
        }
        $this->load->helper(array('url','form'));
        $this->load->model('Legacy_dependency_model', 'legacyDependency');
    }

    public function index()
    {
        $data = array(
            'judul' => 'Legacy / Compatibility',
            'stats7' => $this->legacyDependency->stats(7),
            'recent' => $this->legacyDependency->recent(100),
            'legacy_links' => array(
                array('label'=>'Pengguna / Role Legacy','route'=>'admin/DataUser','replacement'=>'User, Role & Permission'),
                array('label'=>'Kord. Kabupaten Legacy','route'=>'admin/AdminKabupaten','replacement'=>'Organisasi'),
                array('label'=>'Kord. Kecamatan Legacy','route'=>'admin/AdminKecamatan','replacement'=>'Organisasi'),
                array('label'=>'Kord. Desa Legacy','route'=>'admin/AdminDesa','replacement'=>'Organisasi'),
                array('label'=>'Admin Real Count Legacy','route'=>'admin/Adminrealcount','replacement'=>'Real Count Engine'),
                array('label'=>'Suara Masuk Legacy','route'=>'admin/saksi/SuaraMasuk','replacement'=>'Real Count Engine'),
                array('label'=>'Display Real Count Legacy','route'=>'admin/saksi/RealCount','replacement'=>'Big Screen Real Count'),
            ),
        );
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('admin/legacy_compatibility/index', $data);
        $this->load->view('templates/footer');
    }

    public function set_mode()
    {
        if (strtoupper((string) $this->input->method(TRUE)) !== 'POST') {
            show_404();
            return;
        }

        $resource = strtoupper(trim((string) $this->input->post('resource_code', TRUE)));
        $mode = strtoupper(trim((string) $this->input->post('mode', TRUE)));

        if (
            !in_array($resource, array('LEGACY_REAL_COUNT','LEGACY_SAKSI'), TRUE) ||
            !in_array($mode, array('MONITOR','READ_ONLY'), TRUE)
        ) {
            $this->session->set_flashdata('error', 'Perubahan mode ditolak oleh safety policy.');
            redirect('admin/LegacyCompatibility');
            return;
        }

        $ok = $this->legacyDependency->setMode($resource, $mode, $this->session->userdata('id'));
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Mode legacy berhasil diperbarui.' : 'Mode legacy gagal diperbarui.');
        redirect('admin/LegacyCompatibility');
    }
}
