<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ProductionReadiness extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->session->userdata('id') || (string) $this->session->userdata('hak_akses') !== '1') {
            redirect('Auth');
            return;
        }

        $this->load->model('Production_readiness_model', 'readiness');
    }

    public function index()
    {
        $data = array(
            'judul' => 'Production Readiness',
            'audit' => $this->readiness->audit(),
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('admin/production_readiness/index', $data);
        $this->load->view('templates/footer');
    }
}
