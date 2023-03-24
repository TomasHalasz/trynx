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

class StoreDetailShowControl extends Control
{

    private $pricelistId = null;
    private $storageId = null;
    private $showExpirationModal = false;

    /** @var \App\Model\StoreManager*/
    private $StoreManager;

    /** @var \App\Model\PricelistManager*/
    private $PricelistManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct( Nette\Localization\Translator $translator, $storeManager, $pricelistManager)
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->translator       = $translator;
        $this->StoreManager     = $storeManager;
        $this->PricelistManager = $pricelistManager;
    }




    public function render()
    {
        //$this->item_id = $itemId;
        $this->template->setFile(__DIR__ . '/StoreDetailShow.latte');
        if (!is_null($this->pricelistId) && !is_null($this->storageId))
        {
            $this->template->storeData = $this->StoreManager->findAll()->
                                            where('cl_store.cl_pricelist_id = ? AND cl_store.cl_storage_id = ?', $this->pricelistId, $this->storageId)->
                                            select('SUM(:cl_store_move.s_in-:cl_store_move.s_out) AS quantity, cl_store.id, cl_store.exp_date, cl_store.batch, cl_pricelist.unit AS unit')->
                                            having('SUM(:cl_store_move.s_in-:cl_store_move.s_out) > 0')->
                                            order('exp_date ASC');
            $tmpPricelist = $this->PricelistManager->find($this->pricelistId);
            $this->template->item_identification = $tmpPricelist->identification;
            $this->template->item_label = $tmpPricelist->item_label;
        }else{
            $this->template->storeData = FALSE;
            $this->template->item_identification = "";
            $this->template->item_label = "";
        }

        $this->template->showExpirationModal = $this->showExpirationModal;

        $this->template->render();
    }

    public function showDetail($pricelistId, $storageId)
    {
        $this->pricelistId = $pricelistId;
        $this->storageId = $storageId;
        $this->showExpirationModal = TRUE;
        //bdump($pricelistId, '$pricelistId');
        $this->redrawControl('expirations');
        //bdump('ted');
        //$this->presenter->showModal('showExpirationModal');
    }



        

}

