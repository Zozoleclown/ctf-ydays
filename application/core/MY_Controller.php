<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Community Auth - MY Controller
 *
 * Community Auth is an open source authentication application for CodeIgniter 3
 *
 * @package     Community Auth
 * @author      Robert B Gottier
 * @copyright   Copyright (c) 2011 - 2017, Robert B Gottier. (http://brianswebdesign.com/)
 * @license     BSD - http://www.opensource.org/licenses/BSD-3-Clause
 * @link        http://community-auth.com
 */

require_once APPPATH . 'third_party/community_auth/core/Auth_Controller.php';

class MY_Controller extends Auth_Controller{

  public function __construct() {
    parent::__construct();

    $this->load->library('twig');
    $this->load->helper("url");
    $this->load->helper("form");

    $this->load->model('categories_model');
    $categories = $this->categories_model->getAll();

    $this->load->vars('categories', $categories);
  }

}
