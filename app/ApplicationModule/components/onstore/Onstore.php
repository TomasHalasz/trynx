<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;

class OnstoreControl extends Control
{


    private $cl_pricelist_id;

    /** @var \App\Model\Base*/
    private $StoreManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;
    
    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct(Nette\Localization\Translator $translator, $cl_pricelist_id,$StoreManager)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
	    $this->cl_pricelist_id  = $cl_pricelist_id;
	    $this->StoreManager     = $StoreManager;
        $this->translator       = $translator;
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/Onstore.latte');
//	$this->template->EnableAddEmptyRow = $this->EnableAddEmptyRow;
	//dump($this->StoreManager);
	//die;
	$this->template->data = $this->StoreManager->findBy(array('cl_pricelist_id' => $this->cl_pricelist_id))->order('cl_storage.name');
        $this->template->render();
    }
    
    

}

