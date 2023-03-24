<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;

class StoremoveControl extends Control
{


    private $cl_pricelist_id, $showStorageId, $cl_storage_id;

    /** @var \App\Model\Base*/
    private $StoreManager;    
    
    /** @var \App\Model\Base*/
    private $StoreMoveManager;

    /** @var \App\Model\Base*/
    private $StoreOutManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct(Nette\Localization\Translator $translator, $cl_pricelist_id,$StoreManager,$StoreMoveManager,$StoreOutManager, $cl_storage_id = NULL)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->cl_pricelist_id  = $cl_pricelist_id;
        $this->StoreManager     = $StoreManager;
        $this->StoreMoveManager = $StoreMoveManager;
        $this->StoreOutManager  = $StoreOutManager;
        $this->cl_storage_id	= $cl_storage_id;
        $this->translator       = $translator;
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/Storemove.latte');
    //	$this->template->EnableAddEmptyRow = $this->EnableAddEmptyRow;
        //dump($this->StoreManager);
        //die;
        if (!isset($this->template->data)) {
        	$tmpData = $this->StoreManager->findBy(array('cl_pricelist_id' => $this->cl_pricelist_id));
        	if (!is_null($this->cl_storage_id))
			{
				$tmpData = $tmpData->where('cl_store.cl_storage_id = ?', $this->cl_storage_id);
				$this->showStorageId = $this->cl_storage_id;
			}
            $this->template->data = $tmpData->order('cl_storage.name');
        }
        $this->template->cl_store_out = $this->StoreOutManager->findAll();
        $this->template->cl_store_move = $this->StoreMoveManager->findBy(array('cl_pricelist_id' => $this->cl_pricelist_id));
        $this->template->showStorageId = $this->showStorageId;
        $this->template->render();
    }
    
    public function handleShowMoves($cl_storage_id, $cl_store_id)
    {
        $this->showStorageId = $cl_storage_id;
        $this->template->data = $this->StoreManager->findBy(array('cl_pricelist_id' => $this->cl_pricelist_id, 'cl_store.cl_storage_id' => $cl_storage_id))->order('cl_storage.name');
        $this->redrawControl('allMoves');
        //$this->redrawControl('onetableContainer');
        $this->redrawControl('onetable'.$cl_store_id);
    }

    public function handleRecalcStores($id, $cl_storage_id)
    {
        //bdump($id, $cl_storage_id);
        //die;
        $this->presenter->recalcStores($id, $cl_storage_id);

    }

}

