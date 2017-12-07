<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Challenges extends MY_Controller{

  public function __construct() {
    parent::__construct();
  }

  function category($category) {
    $category = $this->categories_model->getBySlug($category);

    $this->twig->display('challenges/category.twig', [
      "page" => $category->slug,
      "category" => $category,
      "title" => "Challenges de " . $category->label
    ]);
  }

  function challenge($category, $challenge_id) {
    $challenge = $this->challenges_model->get($challenge_id);

    $this->twig->display('challenges/challenge.twig', [
      "page" => $category,
      "challenge" => $challenge,
      "title" => $challenge->label
    ]);
  }

  function create() {
    if ( !$this->require_min_level(6) ) return;

    // Si on a des données en POST, l'utilisateur tente donc de créer un challenge
    if( $this->input->post() ) {
      $required_fields = ["label", "description", "password", "category_id"];
      // Si il manque des paramètres obligatoires, on set une erreur
      if ( !$this->_check_mandatory_post_parameters($required_fields) ) {
        $this->add_error("Il semblerait que vous ayez oublié de compléter un champ.");
        foreach ( $this->input->post() as $k => $v ) {
          $this->load->vars($k, $v);
        }
      } else { // Sinon on crée le challenge et on affiche la vue de succès
        $challenge = [
          "label" => $this->input->post("label"),
          "description" => $this->input->post("description"),
          "password_hash" => $this->authentication->hash_passwd($this->input->post("password")),
          "category_id" => $this->input->post('category_id')
        ];

        $this->challenges_model->create($challenge);

        $this->twig->display('messages/success.twig', [
          "message_intro" => "Félicitations !",
          "message" => "Challenge créé avec succès, merci de votre participation !"
        ]);

        return;
      }
    }

    $this->twig->display('challenges/create.twig', [
      "title" => "Création de challenge"
    ]);
  }
}
