<?php

namespace App\ApplicationModule\Presenters;
use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class InfoBoxControl extends Control
{

    private $showData,$displayName,$templateFile, $settings;
    /** @var \App\Model\Base*/
    private $PartnersEventManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    public function __construct( Nette\Localization\Translator $translator, $showData, $displayName, $templateFile, $settings)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData         = $showData;
        $this->displayName      = $displayName;
        $this->templateFile     = $templateFile;
        $this->settings         = $settings;
        $this->translator       = $translator;
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
        $this->template->data = $this->showData;
        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->settings;
        $this->template->render();
    }



       

}