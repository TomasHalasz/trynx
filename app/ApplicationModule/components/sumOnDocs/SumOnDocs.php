<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class SumOnDocsControl extends Control
{

    public $parent_id;

    /** @var \App\Model\Base*/
    private $dataManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    private $dataArr; 
    
    private $settings;
    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct(Nette\Localization\Translator $translator, $dataManager, $parent_id, $settings, $dataArr)
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->dataManager      = $dataManager;
        $this->settings         = $settings;
        $this->parent_id        = $parent_id;
        $this->dataArr          = $dataArr;
        $this->translator       = $translator;
    }        

    public function action()
    {
	
    }
    public function render()
    {
       // //profiler::start();
        //$id = NULL
        //10.07.2018 - this is have to do solution for situation when we need redraw control without redrawing whole content snippet.
        //in this case we don't get property from paren template {control pairedDocs $data->id}
        //so we define default value NULL and use $id only if it is not NULL
        //if (!is_null($id))
        //{
          //  $this->id = $id;
        //}
        $this->template->setFile(__DIR__ . '/SumOnDocs.latte');
        $this->template->data = $this->dataManager->findBy([$this->dataManager->tableName.'.id' => $this->parent_id])->fetch();
        $this->template->dataArr = $this->dataArr;
        $this->template->settings = $this->settings;
        $this->template->render();
     //   //profiler::finish('SumOnDocs '. $this->getName());
    }
    
     

        

}

