<?php

namespace App\ApplicationModule\Presenters;
use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class EventsListControl extends Control
{

    private $showData,$displayName,$templateFile;
    /** @var \App\Model\Base*/
    private $PartnersEventManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    public function __construct( Nette\Localization\Translator $translator, $showData, $displayName, $templateFile)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData             = $showData;
	    $this->displayName          = $displayName;
	    $this->templateFile         = $templateFile;
        $this->translator           = $translator;
	
    }        
    
    public function render()
    {
        $this->template->setTranslator($this->translator);
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
		//$this->MessagesManager->findAll()->where('closed = ?',0)->count();	
        // required to enable form access in snippets
        //$this->template->_form = $this['eventForm'];	
        $this->template->data = $this->showData;
        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->presenter->settings;
        $this->template->render();
    }



       

}