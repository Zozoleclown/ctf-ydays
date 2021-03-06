<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function login()
    {

        if ($this->uri->uri_string() == 'user/login') {
            show_404();
        }

        $post = (strtolower($_SERVER['REQUEST_METHOD']) == 'post');

		log_message('debug', 'Demande sur la page login reçue:' . print_r($this->input->post(), true));
        if ( $this->input->get(AUTH_LOGOUT_PARAM) ) {
            $this->load->vars("logout", true);
        }

        if ($this->_isRegisterRequest()) {
            log_message("debug", "Requête d'enregistrement reçue: " . print_r($this->input->post(), true));

            $this->_register();
            $this->_force_login($this->input->post('email'));
        } else if ($post) {
            if ( !$this->require_min_level(1) ) {
                $this->add_error("Nom d'utilisateur ou mot-de-passe incorrect");
            }
        }


        $this->setup_login_form();

        $this->twig->display('user/login_form.twig');
    }

    public function logout()
    {
        $this->authentication->logout();

        // Set redirect protocol
        $redirect_protocol = USE_SSL ? 'https' : null;

        redirect(site_url(LOGIN_PAGE . '?' . AUTH_LOGOUT_PARAM . '=1', $redirect_protocol));
    }

    public function profile()
    {
        $this->twig->display('messages/success.twig', [
            "message_intro" => "Profil",
            "message" => "Bienvenue sur votre profil",
            "page" => "profile",
        ]);
    }

    protected function _force_login($email)
    {
        $auth_model = $this->authentication->auth_model;

        if ($auth_data = $this->{$auth_model}->get_auth_data($email)) {
            $this->authentication->redirect_after_login();
            $this->authentication->maintain_state($auth_data);
        }
    }

    protected function _register()
    {
        $mandatory_keys = [
            "username" => "nom d'utilisateur",
            "email" => "adresse mail",
            "password" => "mot de passe",
            "password-confirm" => "confirmation du mot de passe",
        ];

        $missing_params = $this->_get_missing_post_parameters($mandatory_keys);
        if (!empty($missing_params)) {
            $this->load->vars("register_error", "Vous avez oublié de compléter les champs suivants: <ul class='list-group'><li class='list-group-item'>" . implode($missing_params, "</li><li class='list-group-item'>") . "</li>");
            return false;
        }

        if ($this->input->post("password") !== $this->input->post("password-confirm")) {
            $this->load->vars("register_error", "Les mots de passe ne correspondent pas");
            return false;
        }

        $user = [
            "username" => $this->input->post("username"),
            "email" => $this->input->post("email"),
            "password" => $this->input->post("password"),
        ];

        return $this->_create_user($user);
    }

    protected function _isRegisterRequest()
    {

        if ($this->input->post() && !$this->input->post("login_string") && !$this->input->post("login_pass")) {
            return true;
        } else {
            return false;
        }
    }

    private function _create_user($user)
    {
        // Customize this array for your user
        $user_data = [
            'username' => $user["username"],
            'passwd' => $user["password"],
            'email' => $user["email"],
            'auth_level' => '1', // 9 if you want to login @ examples/index.
        ];

        $this->is_logged_in();

        // Load resources
        $this->load->helper('auth');
        $this->load->model('examples/examples_model');
        $this->load->model('examples/validation_callables');
        $this->load->library('form_validation');

        $this->form_validation->set_data($user_data);

        $validation_rules = [
            [
                'field' => 'username',
                'label' => 'username',
                'rules' => 'max_length[12]|is_unique[' . db_table('user_table') . '.username]',
                'errors' => [
                    'is_unique' => 'Ce nom d\'utilisateur est déjà pris',
                ],
            ],
            [
                'field' => 'passwd',
                'label' => 'passwd',
                'rules' => [
                    'trim',
                    'required',
                    [
                        '_check_password_strength',
                        [$this->validation_callables, '_check_password_strength'],
                    ],
                ],
                'errors' => [
                    'required' => 'The password field is required.',
                ],
            ],
            [
                'field' => 'email',
                'label' => 'email',
                'rules' => 'trim|required|valid_email|is_unique[' . db_table('user_table') . '.email]',
                'errors' => [
                    'is_unique' => 'Cette adresse mail est déjà associée à un compte.',
                ],
            ],
            [
                'field' => 'auth_level',
                'label' => 'auth_level',
                'rules' => 'required|integer|in_list[1,6,8,9]',
            ],
        ];

        $this->form_validation->set_rules($validation_rules);

        if ($this->form_validation->run()) {
            $user_data['passwd'] = $this->authentication->hash_passwd($user_data['passwd']);
            $user_data['user_id'] = $this->examples_model->get_unused_id();
            $user_data['created_at'] = date('Y-m-d H:i:s');

            // If username is not used, it must be entered into the record as NULL
            if (empty($user_data['username'])) {
                $user_data['username'] = null;
            }

            $this->db->set($user_data)
                ->insert(db_table('user_table'));

            if ($this->db->affected_rows() == 1) {
                return true;
            } else {
                return false;
            }
        }
    }
}
