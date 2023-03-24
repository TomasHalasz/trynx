<?php

namespace App\ApplicationModule\Components;
use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;


class PartnerCardControl extends Control
{

    public $cl_partners_book_id;

    /** @var \App\Model\Base*/
    private $PartnersManager;


    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    
    public function __construct( Nette\Localization\Translator $translator, Model\PartnersManager $PartnersManager)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->translator       = $translator;
	    $this->PartnersManager  = $PartnersManager;
    }        
    
    public function render($id = NULL)
    {
        $this->template->render();
    }


    
    

}