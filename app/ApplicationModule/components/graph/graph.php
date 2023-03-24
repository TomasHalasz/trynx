<?php

namespace App\ApplicationModule\Presenters;
use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class GraphControl extends Control
{

    private $showData,$displayName,$templateFile, $graphName;
    /** @var \App\Model\Base*/
    private $PartnersEventManager;

    /** @var Nette\Localization\Translator @inject */
    public $translator;


    public function __construct(Nette\Localization\ITranslator $translator, $showData, $displayName, $graphName, $templateFile)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData         = $showData;
		$this->displayName      = $displayName;
		$this->graphName        = $graphName;
		$this->templateFile     = $templateFile;
        $this->translator       = $translator;
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
		//dump($this->showData);
		//die;
		
		
		//dump($showData);
		//die;
		//$this->template->dataInvoice = json_encode($showDataInvoice);
		//$this->template->dataCommision = json_encode($showDataCommission);
		//$this->template->dataOrder = json_encode($showDataOrder);
		$this->template->showData = $this->showData;
		$this->template->displayName = $this->displayName;
		$this->template->graphName = $this->graphName;
        $this->template->render();
    }



       

}