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
        $this->load->helper(array('url', 'form'));
    }

    public function index()
    {
        $data = array(
            'judul' => 'Legacy / Compatibility',
            'legacy_links' => array(
                array('label' => 'Pengguna / Role Legacy', 'route' => 'admin/DataUser', 'replacement' => 'User, Role & Permission Center'),
                array('label' => 'Kord. Kabupaten Legacy', 'route' => 'admin/AdminKabupaten', 'replacement' => 'Organisasi'),
                array('label' => 'Kord. Kecamatan Legacy', 'route' => 'admin/AdminKecamatan', 'replacement' => 'Organisasi'),
                array('label' => 'Kord. Desa Legacy', 'route' => 'admin/AdminDesa', 'replacement' => 'Organisasi'),
                array('label' => 'Admin Real Count Legacy', 'route' => 'admin/Adminrealcount', 'replacement' => 'Real Count Engine'),
                array('label' => 'Suara Masuk Legacy', 'route' => 'admin/saksi/SuaraMasuk', 'replacement' => 'Real Count Engine'),
                array('label' => 'Display Real Count Legacy', 'route' => 'admin/saksi/RealCount', 'replacement' => 'Big Screen Real Count'),
            ),
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('admin/legacy_compatibility/index', $data);
        $this->load->view('templates/footer');
    }
}
