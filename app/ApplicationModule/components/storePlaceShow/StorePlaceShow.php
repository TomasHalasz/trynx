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

class StorePlaceShowControl extends Control
{

    private $pricelistId = null;
    private $storageId = null;
    private $showPlaceModal = false;
	public $itemId = NULL;
	
    /** @var \App\Model\StoreManager*/
    private $StoreManager;
	
	/** @var \App\Model\StoragePlacesManager*/
	private $StoragePlacesManager;
	
	/** @var \App\Model\StoreMoveManager*/
	private $StoreMoveManager;
	
    /** @var \App\Model\PricelistManager*/
    private $PricelistManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct( Nette\Localization\Translator $translator, $storeManager, $pricelistManager, $storeMoveManager, $storagePlacesManager)
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->translator           = $translator;
        $this->StoreManager         = $storeManager;
		$this->StoreMoveManager     = $storeMoveManager;
        $this->PricelistManager     = $pricelistManager;
		$this->StoragePlacesManager = $storagePlacesManager;
    }

    



    public function render()
    {
        //$this->item_id = $itemId;
        $this->template->setFile(__DIR__ . '/StorePlaceShow.latte');
        if (!is_null($this->pricelistId) && !is_null($this->storageId))
        {
//            $this->template->storeData = $this->StoreManager->findAll()->select(':cl_store_move.s_end, :cl_store_move.')->where(':cl_store_move.s_end > 0 AND cl_store.cl_pricelist_id = ? AND cl_store.cl_storage_id = ?', $this->pricelistId, $this->storageId)->order(':cl_store_move.created ASC');
			$this->template->storeData = $this->StoreMoveManager->findAll()->
												where('cl_store_move.s_end > 0 AND cl_store_move.cl_pricelist_id = ? AND cl_store_move.cl_storage_id = ?', $this->pricelistId, $this->storageId)->
												order('cl_store_move.created ASC');
            //bdump($this->pricelistId);
            
			$tmpPricelist = $this->PricelistManager->find($this->pricelistId);
            $this->template->item_identification = $tmpPricelist->identification;
            $this->template->item_label = $tmpPricelist->item_label;
        }else{
            $this->template->storeData = FALSE;
            $this->template->item_identification = "";
            $this->template->item_label = "";
        }

        $this->template->showPlaceModal = $this->showPlaceModal;

        $this->template->render();
    }

    public function showDetail($pricelistId, $storageId)
    {
        $this->pricelistId = $pricelistId;
        $this->storageId = $storageId;
        $this->showPlaceModal = TRUE;
        //bdump($pricelistId, '$pricelistId');
        $this->redrawControl('places');
        //bdump('ted');
        //$this->presenter->showModal('showExpirationModal');
    }
	
	public function handleChangePlace($itemId)
	{
	
		$this->presenter->changePlace($itemId);
		
	}
	

	
	
}

