<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class LimitsControl extends Control
{

    private $showData,$displayName,$templateFile;
    private $mode = 'min';  //min - for quantity under minimum, req - for quantity under required

    /** @var \App\Model\PriceListLimitsManager*/
    private $PriceListLimitsManager;
	
	/** @var \App\Model\Base*/
	private $ArraysManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;



    public function __construct($showData, $displayName, $templateFile, \App\Model\PriceListLimitsManager $priceListLimitsManager, Nette\Localization\Translator $translator)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData = $showData;
        $this->displayName = $displayName;
        $this->templateFile = $templateFile;
        $this->PriceListLimitsManager = $priceListLimitsManager;
        $this->translator = $translator;
	
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
		//$this->MessagesManager->findAll()->where('closed = ?',0)->count();	
        // required to enable form access in snippets
        //$this->template->_form = $this['eventForm'];	
        $this->template->data = $this->showData;
        if ($this->showData['limits']){
            $tmpData = $this->PriceListLimitsManager->findAll()->
                                            select('cl_pricelist.identification, cl_pricelist.item_label, cl_pricelist_limits.quantity_min, cl_pricelist_limits.quantity_req, cl_storage.name AS storage_name, SUM(cl_pricelist:cl_store_move.s_end) AS quantity')->
                                            where('show_warning = 1  AND cl_pricelist:cl_store_move.cl_storage_id = cl_pricelist_limits.cl_storage_id')->
                                            group('cl_pricelist_limits.id, cl_pricelist_limits.cl_storage_id');
            //:cl_store.cl_pricelist_id = cl_pricelist_limits.cl_pricelist_id

            if ($this->mode == "min"){
                $tmpData = $tmpData->having('SUM(cl_pricelist:cl_store_move.s_end) < cl_pricelist_limits.quantity_min');
            }elseif($this->mode == "req"){
                $tmpData = $tmpData->having('SUM(cl_pricelist:cl_store_move.s_end) <= cl_pricelist_limits.quantity_req');
            }
            $tmpData = $tmpData->order('cl_pricelist.item_label, cl_pricelist.identification');
            $this->template->dataLimits = $tmpData;
        }
        $this->template->mode = $this->mode;
        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->presenter->settings;
        $this->template->render();
    }

    /** return quantity on store
     * @param $arr
     * @return float
     */
    public function GetQuantityStorage($arr)
    {
        if (isset($arr['cl_pricelist_id']) && isset($arr['cl_storage_id'])){
            $quantity = $this->StoreManager->getQuantityStorage($arr['cl_pricelist_id'], $arr['cl_storage_id']);
        }else{
            $quantity = 0;
        }
        return $quantity;;
    }

    public function handleChangeMode($mode = 'min')
    {
        $this->mode = $mode;
        $this->redrawControl('orderscontent');
    }
       

}