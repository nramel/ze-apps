<?php
defined('BASEPATH') OR exit('No direct script access allowed');



class ZeCtrl {
  public $load = null ;
  public $input = null ;
  public $session = null ;
  private $_modulePath = null ;
  private $_controllerPath = null ;

  public function __construct()
  {
    $class_info = new ReflectionClass($this);
    $this->_controllerPath = $class_info->getFileName() ;
    $this->_modulePath = dirname(dirname($this->_controllerPath)) ;

    // chargement de l'objet load
    $this->load() ;

    // charge l'objet SessionHandler
    $this->session = new ZeSession();


    // charge l'objet input
    $this->input = new ZeInput();



    // connexion à la base
    ActiveRecord\Config::initialize(function($cfg)
    {
      global $db ;

      $cfg->set_model_directory('.');
      $cfg->set_connections(array(
        'development' => 'mysql://' . $db['default']['username'] . ':' . $db['default']['password'] . '@' . $db['default']['hostname'] . '/' . $db['default']['database']));
    });
  }





  private function load() {
    if ($this->load == null) {
      $this->load = new ZeLoad() ;
      $this->load->setCtrl($this);
    }

    $context = array() ;
    $context['controllerPath'] = $this->_controllerPath ;
    $context['modulePath'] = $this->_modulePath ;
    $this->load->setContext($context) ;
  }
}
