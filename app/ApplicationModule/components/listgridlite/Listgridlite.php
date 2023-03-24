<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;
use Exception;

class ListgridliteControl extends Control
{


    private $dataColumns,$dataSource,$conditionRows,$parentId,$defaultData,$orderColumn;

    /** @var \App\Model\Base*/
    private $DataManager;

    /** @var \App\Model\Base*/
    private $ParentManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;
    
    /**
     * 
     * @param type $dataSource - DB result with data
     * @param type $DataManager - model manager for showed data
     * @param type $dataColumns - array(columnName,array(Name,Format))
     * @param type $conditionRows
     * @param type $parentId - parent ID for constraints
     * @param type $parentManager - parent data model manager* 
     */
    public function __construct( Nette\Localization\Translator $translator, $DataManager,$dataColumns,$conditionRows,$parentId, $parentManager,$orderColumn)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->dataColumns      = $dataColumns;
        $this->conditionRows    = $conditionRows;
        $this->orderColumn      = $orderColumn;
        $this->dataSource       = $DataManager->findAll()->where(array($parentManager->tableName.'_id' => $parentId))->order($this->orderColumn);
        $this->DataManager      = $DataManager;
        $this->parentId         = $parentId;
        $this->translator       = $translator;
	
    }        
    
    public function render()
    {
        $this->template->cmpName = $this->name;
        $this->template->setFile(__DIR__ . '/Listgridlite.latte');
	$this->template->dataSource = $this->dataSource;	
	$this->template->dataColumns = $this->dataColumns;	
	$this->template->conditionRows = $this->conditionRows;	
	$this->template->render();
    }
    


   
	    
}

