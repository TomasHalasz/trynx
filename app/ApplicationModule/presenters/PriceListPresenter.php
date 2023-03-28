<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class PriceListPresenter extends \App\Presenters\BaseListPresenter {

    public $id;

    
    /** @persistent */
    public $page_b;
    
    /** @persistent */
    public $filter;        

    /** @persistent */
    public $filterColumn;            

    /** @persistent */
    public $filterValue;       
    
    /** @persistent */
    public $sortKey;        

    /** @persistent */
    public $sortOrder;          
    
    
    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\PriceListCategoriesManager
     */
    public $PriceListCategoriesManager;


    /**
    * @inject
    * @var \App\Model\UsersManager
    */
    public $UsersManager;    	

    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;        

    /**
    * @inject
    * @var \App\Model\StoreManager
    */
    public $StoreManager;            
	
    /**
    * @inject
    * @var \App\Model\StorageManager
    */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\StoragePlacesManager
     */
    public $StoragePlacesManager;

    /**
    * @inject
    * @var \App\Model\StoreMoveManager
    */
    public $StoreMoveManager;         

    /**
    * @inject
    * @var \App\Model\StoreOutManager
    */
    public $StoreOutManager;         
    
    
    /**
    * @inject
    * @var \App\Model\RatesVatManager
    */
    public $RatesVatManager;           

    /**
    * @inject
    * @var \App\Model\WasteCategoryManager
    */
    public $WasteCategoryManager;           



    /**
    * @inject
    * @var \App\Model\PriceListGroupManager
    */
    public $PriceListGroupManager;               

    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;               

    /**
    * @inject
    * @var \App\Model\PricesManager
    */
    public $PricesManager;          
    
    /**
    * @inject
    * @var \App\Model\PricesGroupsManager
    */
    public $PricesGroupsManager;
	
	/**
	 * @inject
	 * @var \App\Model\PriceListLimitsManager
	 */
	public $PriceListLimitsManager;
    
    /**
    * @inject
    * @var \App\Model\PriceListMacroManager
    */
    public $PriceListMacroManager;        

    /**
    * @inject
    * @var \App\Model\PriceListBondsManager
    */
    public $PriceListBondsManager;

    /**
     * @inject
     * @var \App\Model\PriceListTaskManager
     */
    public $PriceListTaskManager;

    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;


    /**
     * @inject
     * @var \App\Model\OfferManager
     */
    public $OfferManager;

    /**
     * @inject
     * @var \App\Model\CommissionManager
     */
    public $CommissionManager;

    /*protected function createComponentOnstore()
     {
		return new OnstoreControl($this->id,$this->StoreManager);
     }*/

    protected function createComponentHelpbox()
    {
        return new Controls\HelpboxControl($this->translator, "");
    }

    protected function createComponentEditTextDescription() {
	    return new Controls\EditTextControl(
            $this->translator,$this->DataManager, $this->id, 'description_txt');
    }    
        
        

    protected function createComponentFiles()
     {
	    if ($this->getUser()->isLoggedIn()){
		$user_id = $this->user->getId();
		$cl_company_id = $this->settings->id;
	    }
	    return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'cl_pricelist_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
     }   	    
    
     
    protected function createComponentImages()
     {
	    if ($this->getUser()->isLoggedIn()){
		$user_id = $this->user->getId();
		$cl_company_id = $this->settings->id;
	    }
	    return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'cl_pricelist_image_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
     }   	
     
     
    protected function createComponentStoremove()
     {
		return new StoremoveControl(
            $this->translator,$this->id,$this->StoreManager,$this->StoreMoveManager,$this->StoreOutManager);
     }

    protected function createComponentPriceListTaskGrid()
    {

        $arrWorkplaces = $this->WorkplacesManager->findAll()->where('disabled = 0')->order('workplace_name')->fetchPairs('id', 'workplace_name');
        $arrUsers = array();
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $arrData = array(
                'name' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 20),
                'work_time' => array($this->translator->translate('Hodin'), 'format' => 'number', 'size' => 8),
                'cl_workplaces.workplace_name' => array($this->translator->translate('Pracoviště'), 'format' => 'chzn-select',
                                                        'values' => $arrWorkplaces,
                                                        'size' => 8),
                'cl_users.name' => array($this->translator->translate('Pracovník'), 'format' => 'chzn-select',
                                            'values' => $arrUsers,
                                            'size' => 8),
                'work_rate' => array($this->translator->translate('Sazba'), 'format' => 'number', 'size' => 6),
                'qty_norm' => array($this->translator->translate('Norma_ks/hod'), 'format' => 'number', 'size' => 6),
                'is_work' => array($this->translator->translate('Práce'), 'format' => 'boolean', 'size' => 7),
                'nmb_repeats' => array($this->translator->translate('Opakování'), 'format' => 'integer', 'size' => 5),
                  'note' => array($this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE),

        );
//              'done' => array($this->translator->translate('Hotovo'), 'format' => 'boolean', 'size' => 7),
        //'work_date_s' => array($this->translator->translate('Začátek'), 'format' => 'datetime2', 'size' => 11),
//                'work_date_e' => array($this->translator->translate('Konec'), 'format' => 'datetime2', 'size' => 11),

        //                'arrTools' => array('tools', array(1 => array('url' => 'report!', 'rightsFor' => 'enable', 'label' => 'PDF', 'class' => 'btn btn-success btn-xs',
        //                    'data' => array('data-ajax="false"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-print'
        //                )
        //                )
        //            ),

        $now = new \Nette\Utils\DateTime;
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PriceListTaskManager,
            $arrData,
            array(),
            $this->id,
            array('work_date_s' => NULL, 'work_date_e' => NULL),
            $this->DataManager,
            FALSE,
            FALSE,
            TRUE,
            array('activeTab' => 3), //custom links
            TRUE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->onChange[] = function () {
            $this->updateSum();

        };
        $control->onPrint[] = function ($itemId) {
            $this->reportTask($itemId);

        };
        return $control;
    }


    protected function createComponentPriceListLimitGrid()
	{
		$arrStorage = $this->StorageManager->getStoreTree();
		bdump($arrStorage);
		$arrData = array('cl_storage.name' => array($this->translator->translate('Sklad'),'format' => "text",'size' => 20, 'required' => 'Sklad musí být vybrán', 'values' => $arrStorage, 'roCondition' => '$defData["changed"] != NULL'),
				'quantity_min' => array($this->translator->translate('Minimální_množství'),'format' => "number",'size' => 20),
				 'quantity_req' => array($this->translator->translate('Požadované_množství'),'format' => 'number','size' => 30),
				 'quantity_function_' => array($this->translator->translate('Skladem'), 'format' => 'number-function', 'size' => 20, 'readonly' => TRUE, 'function' => 'getQuantityStorage', 'function_param' => array('cl_pricelist_id', 'cl_storage_id')),
                'show_warning' => array($this->translator->translate('Upozorňovat'),'format' => 'boolean','size' => 30),
		);
		$control =  new Controls\ListgridControl(
            $this->translator,
							$this->PriceListLimitsManager, //data manager
							$arrData, //data columns
							array(), //row conditions
							$this->id, //parent Id
							array(), //default data
							$this->DataManager, //parent data manager
							NULL, //pricelist manager
							NULL, //pricelist partner manager				
							TRUE, //enable add empty row
							array(), //custom links
							FALSE, //movableRow 
							'cl_storage.name' //orderColumn
							);
		$control->setEnableSearch('cl_storage.name LIKE ? OR cl_storage.name LIKE ? ');
		$control->setPaginatorOff();
		return $control;
     }   
     
    protected function createComponentPriceListMacroGrid()
	{
		$arrPriceList = $this->PriceListManager->findAll()->where('cl_pricelist.id != ? AND cl_pricelist_group.is_component = 1', $this->id)->
				    select('CONCAT(identification," ",item_label) AS label, cl_pricelist.id')->order('label,cl_pricelist.id')->fetchPairs('id', 'label');		
		$arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'chzn-select', 'size' => 20,'values' => $arrPriceList),	
				 'cl_pricelist.item_label' => array($this->translator->translate('Název'),'format' => "text",'size' => 20, 'readonly' => TRUE),	
				 'quantity' => array($this->translator->translate('Množství'),'format' => 'number','size' => 15),
				 'waste' => array($this->translator->translate('Odpad'),'format' => 'number','size' => 15));
		$control = new Controls\ListgridControl(
            $this->translator,
							$this->PriceListMacroManager, //data manager
							$arrData, //data columns
							array(), //row conditions
							$this->id, //parent Id
							array(), //default data
							$this->DataManager, //parent data manager
							NULL, //pricelist manager
							NULL, //pricelist partner manager				
							TRUE, //enable add empty row
							array(), //custom links
							FALSE, //movableRow 
							FALSE,  //orderColumn
							FALSE, //selectmode
							array(), //quicksearch
							"", //fontsize
							'cl_pricelist_macro_id'  //name of parent column 
							);


		return $control;
     }       

    protected function createComponentPriceListBondsGrid()
	{
		$arrPriceList = $this->PriceListManager->findAll()->where('cl_pricelist.id != ? AND cl_pricelist_group.is_component = 1', $this->id)->
				    select('CONCAT(identification," ",item_label) AS label, cl_pricelist.id')->order('label,cl_pricelist.id')->fetchPairs('id', 'label');		
		$arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'chzn-select', 'size' => 20,'values' => $arrPriceList),	
                             'cl_pricelist.item_label' => array($this->translator->translate('Název'),'format' => "text",'size' => 20, 'readonly' => TRUE),
                            'limit_for_bond' => array($this->translator->translate('Limit_pro_vazbu'),'format' => 'number','size' => 15),
                            'quantity' => array($this->translator->translate('Vázané_množství'),'format' => 'number','size' => 15),
                             'multiply' => array($this->translator->translate('Násobit'), 'format' => 'boolean', 'size' => 10),
                             'discount' => array($this->translator->translate('Sleva_%'),'format' => 'number','size' => 15));
		return new Controls\ListgridControl(
            $this->translator,
							$this->PriceListBondsManager, //data manager
							$arrData, //data columns
							array(), //row conditions
							$this->id, //parent Id
							array(), //default data
							$this->DataManager, //parent data manager
							NULL, //pricelist manager
							NULL, //pricelist partner manager				
							TRUE, //enable add empty row
							array(), //custom links
							FALSE, //movableRow 
							FALSE,  //orderColumn
							FALSE, //selectmode
							array(), //quicksearch
							"", //fontsize
							'cl_pricelist_bonds_id'  //name of parent column 
							);
     }        
	 
	 	 
    protected function createComponentPricesGroupsGrid()
	{
		$arrGroups = $this->PricesGroupsManager->findAll()->fetchPairs('id', 'name');		
		$arrCurrencies = $this->CurrenciesManager->findAll()->fetchPairs('id', 'currency_name');
		$arrData = array( 'cl_prices_groups.name' => array($this->translator->translate('Název'),'format' => "text",'size' => 10,'values' => $arrGroups, 'roCondition' => '$defData["changed"] != NULL'));
		if ($this->settings->price_e_type == 0){
		    if ($this->settings->platce_dph == 1){
			    $text = $this->translator->translate('Cena_bez_DPH');
		    }else{
			    $text = $this->translator->translate('Cena');
		    }
		    $arrData['price'] = array($text, 'format' => 'currency','size' => 15, 'decplaces' => $this->settings->des_cena);
		}else{
		    $arrData['price_vat'] = array($this->translator->translate('Cena_s_DPH'),'format' => 'currency','size' => 15, 'decplaces' => $this->settings->des_cena);
		}
		$arrData['cl_currencies.currency_name'] = array($this->translator->translate('Měna'),'format' => "text",'size' => 10, 'values' => $arrCurrencies);
        $arrData['price_multiplier'] = array($this->translator->translate('Cena_za_počet_kusů'),'format' => "number",'size' => 10);
        $arrData['description'] = array($this->translator->translate('Poznámka'),'format' => "text",'size' => 35  );
        $arrOffers = $this->OfferManager->findAll()->select('cl_offer.id AS id, CONCAT(cm_number," ",cl_partners_book.company," ",cl_offer.cm_title) AS cm_number')->
                                                        order('cm_number')->fetchPairs('id', 'cm_number');
        $arrData['cl_offer.cm_number'] = array($this->translator->translate('Nabídka'),'format' => "chzn-select", 'values' => $arrOffers);
        $arrCommissions = $this->CommissionManager->findAll()->select('cl_commission.id AS id, CONCAT(cm_number," ",cl_partners_book.company," ",cl_commission.cm_title) AS cm_number')->
                                                        order('cm_number')->fetchPairs('id', 'cm_number');
        $arrData['cl_commission.cm_number'] = array($this->translator->translate('Zakázka'),'format' => "chzn-select", 'values' => $arrCommissions);

		return new Controls\ListgridControl(
                    $this->translator,
                        $this->PricesManager, //data manager
					    $arrData, //data columns
					    array(), //row conditions
					    $this->id, //parent Id
					    array(), //default data
					    $this->DataManager, //parent data manager
					    NULL, //pricelist manager
					    NULL, //pricelist partner manager				
					    TRUE, //enable add empty row
					    array(), //custom links
					    FALSE, //movableRow 
					    'cl_prices_groups.name' //orderColumn
					);
     }       	 
	 
   
    protected function startup()
    {
	    parent::startup();
        //$this->translator->setPrefix(['applicationModule.PriceList']);
        $this->mainTableName = 'cl_pricelist';
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $arrStoragePlaces = $this->StoragePlacesManager->findAll()->
                            select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');
        $arrStoragePlaces[null] = $this->translator->translate("bez_umístění");
        if ($this->settings->platce_dph == 1){
            $arrData = [
                        'identification' => [$this->translator->translate('Kód'), 'size' => 120],
                        'item_label' => [$this->translator->translate('Název'), 'size' => 250],
                        'cl_pricelist_group.name' => [$this->translator->translate('Skupina'), 'size' => 60],
                        'cl_pricelist_categories.name' => [$this->translator->translate('Kategorie'), 'size' => 60],
                        'cl_storage.name' => [$this->translator->translate('Výchozí_sklad'), 'size' => 20],
                        'cl_storage_places_id' => [$this->translator->translate('Výchozí_umístění'), 'size' => 20, 'arrValues' => $arrStoragePlaces],
                        'quantity' => [$this->translator->translate('Skladem'),'size'  => 30, 'format' => 'number', 'function' => 'calcQuantity', 'function_param' => ['id']],
                        'unit' => [$this->translator->translate('Jednotky'),'size'  => 30],
                        'in_package' => [$this->translator->translate('Počet_v_balení'), 'size' => 10],
                        'price_s' => [$this->translator->translate('Nákupní_cena'), 'format' => 'currency'],
                        'price' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                        'price_vat' =>  [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                        'vat' => [$this->translator->translate('Sazba_DPH'),'size'  => 30],
                        'cl_currencies.currency_name' => [$this->translator->translate('Měna'),'size'  => 10],
                        'cl_prices_groups.name' => [$this->translator->translate('Cenová_skupina'), 'size' => 60],
                        'profit_per' => [$this->translator->translate('Zisk_%'),'size'  => 15, 'format' => 'number'],
                        'profit_abs' => [$this->translator->translate('Zisk_abs'),'size'  => 15, 'format' => 'currency'],
                        'search_tag' => [$this->translator->translate('Značky_pro_hledání'), 'size' => 60],
                        'not_active' => [$this->translator->translate('Neaktivní'),'size'  => 10, 'format' => 'boolean'],
                        'b2b_not_show' => [$this->translator->translate('B2B_vypnuto'),'size'  => 10, 'format' => 'boolean'],
                        'ean_code' => [$this->translator->translate('EAN_kód'), 'size' => 30],
                        'ean_old' => [$this->translator->translate('EAN_kód_starý'), 'size' => 30],
                        'order_code' => [$this->translator->translate('Objednací_kód'), 'size' => 30],
                        'order_label' => [$this->translator->translate('Objednací_název'), 'size' => 250],
                        'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'size' => 20, 'format' => 'text'],
                        'excise_duty' => [$this->translator->translate('Spotřební_daň'),'size'  => 15, 'format' => 'decimal'],
                        'volume' => [$this->translator->translate('Objem'),'size'  => 15, 'format' => 'decimal'],
                        'volume_unit' => [$this->translator->translate('[objem]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getVolumeUnits()],
                        'length' => [$this->translator->translate('Délka'),'size'  => 15, 'format' => 'decimal'],
                        'length_unit' => [$this->translator->translate('[délka]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getDimUnits()],
                        'width' => [$this->translator->translate('Šířka'),'size'  => 15, 'format' => 'decimal'],
                        'width_unit' => [$this->translator->translate('[šířka]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getDimUnits()],
                        'height' => [$this->translator->translate('Výška'),'size'  => 15, 'format' => 'decimal'],
                        'height_unit' => [$this->translator->translate('[výška]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getDimUnits()],
                        'percent' => [$this->translator->translate('%_v_objemu'),'size'  => 15, 'format' => 'decimal'],
                        'cl_waste_category.waste_code' => [$this->translator->translate('Kód_odpadu'), 'size' => 20, 'format' => 'text'],
                        'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],'create_by' => $this->translator->translate('Vytvořil'),'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
        }else{
            $arrData = ['identification' => [$this->translator->translate('Kód'), 'size' => 120],
                        'item_label' => [$this->translator->translate('Název'), 'size' => 250],
                        'cl_pricelist_group.name' => [$this->translator->translate('Skupina'), 'size' => 60],
                        'cl_pricelist_categories.name' => [$this->translator->translate('Kategorie'), 'size' => 60],
                        'cl_storage.name' => [$this->translator->translate('Výchozí_sklad'), 'size' => 20],
                        'cl_storage_places.name' => [$this->translator->translate('Výchozí_umístění'), 'size' => 20, 'arrValues' => $arrStoragePlaces],
                        'quantity' => [$this->translator->translate('Skladem'),'size'  => 30, 'format' => 'number', 'function' => 'calcQuantity', 'function_param' => ['id']],
                        'unit' => [$this->translator->translate('Jednotky'),'size'  => 30],
                        'in_package' => [$this->translator->translate('Počet_v_balení'), 'size' => 10],
                        'price_s' => [$this->translator->translate('Nákupní_cena'), 'format' => 'currency'],
                        'price' => [$this->translator->translate('Prodejní_cena'), 'format' => 'currency'],
                        'cl_currencies.currency_name' => [$this->translator->translate('Měna'),'size'  => 30],
                        'cl_prices_groups.name' => [$this->translator->translate('Cenová_skupina'), 'size' => 60],
                        'profit_per' => [$this->translator->translate('Zisk_%'),'size'  => 15, 'format' => 'number'],
                        'profit_abs' => [$this->translator->translate('Zisk_abs'),'size'  => 15, 'format' => 'currency'],
                        'search_tag' => [$this->translator->translate('Značky_pro_hledání'), 'size' => 60],
                        'not_active' => [$this->translator->translate('Neaktivní'),'size'  => 10, 'format' => 'boolean'],
                        'b2b_not_show' => [$this->translator->translate('B2B_vypnuto'),'size'  => 10, 'format' => 'boolean'],
                        'ean_code' => [$this->translator->translate('EAN_kód'), 'size' => 30],
                        'ean_old' => [$this->translator->translate('EAN_kód_starý'), 'size' => 30],
                        'order_code' => [$this->translator->translate('Objednací_kód'), 'size' => 30],
                        'order_label' => [$this->translator->translate('Objednací_název'), 'size' => 250],
                        'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'size' => 20, 'format' => 'text'],
                        'excise_duty' => [$this->translator->translate('Spotřební_daň'),'size'  => 15, 'format' => 'decimal'],
                        'volume' => [$this->translator->translate('Objem'),'size'  => 15, 'format' => 'decimal'],
                        'volume_unit' => [$this->translator->translate('[objem]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getVolumeUnits()],
                        'length' => [$this->translator->translate('Délka'),'size'  => 15, 'format' => 'decimal'],
                        'length_unit' => [$this->translator->translate('[délka]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getDimUnits()],
                        'width' => [$this->translator->translate('Šířka'),'size'  => 15, 'format' => 'decimal'],
                        'width_unit' => [$this->translator->translate('[šířka]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getDimUnits()],
                        'height' => [$this->translator->translate('Výška'),'size'  => 15, 'format' => 'decimal'],
                        'height_unit' => [$this->translator->translate('[výška]'),'size'  => 5, 'format' => 'text', 'arrValues' => $this->ArraysManager->getDimUnits()],
                        'percent' => [$this->translator->translate('%_v_objemu'),'size'  => 15, 'format' => 'decimal'],
                        'cl_waste_category.waste_code' => [$this->translator->translate('Kód_odpadu'), 'size' => 20, 'format' => 'text'],                        
                        'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],'create_by' => $this->translator->translate('Vytvořil'),'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
        }

        $this->dataColumns = $arrData;
        $this->FilterC = 'UPPER(currency_name) LIKE ?';
        $this->filterColumns = ['identification' => 'autocomplete',
                        'item_label' => 'autocomplete',
                        'quantity' => 'autocomplete',
                        'cl_storage.name' => 'autocomplete',
                        'cl_pricelist_group.name' => 'autocomplete',
                        'cl_pricelist_categories.name' => 'autocomplete',
                        'cl_prices_groups.name' => 'autocomplete',
                        'order_code' => 'autocomplete',
                        'order_label' => 'autocomplete',
                        'price_s' => 'autocomplete',
                        'price' => 'autocomplete',
                        'price_vat' => 'autocomplete',
                        'vat' => 'autocomplete',
                        'unit' => 'autocomplete',
                        'cl_currencies.currency_name' => 'autocomplete',
                        'ean_code' => 'autocomplete',
                        'ean_old' => 'autocomplete',
                        'search_tag' => 'autocomplete',
                        'volume' => 'autocomplete',
                        'length' => 'autocomplete',
                        'width' => 'autocomplete',
                        'height' => 'autocomplete',
                        'percent' => 'autocomplete',
                        'cl_partners_book.company' => 'autocomplete'];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['identification', 'item_label', 'ean_code', 'order_code', 'search_tag', 'cl_partners_book.company'];

        $this->DefSort = 'identification';
        $this->numberSeries = ['use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification'];
        $this->readOnly = ['quantity' => TRUE,'price_s' => TRUE, 'profit_per' => TRUE, 'profit_abs' => TRUE];


        if ($tmpGroup = $this->PriceListGroupManager->findAll()->order('name')->limit(1)->fetchPairs('id', 'name')){
            $tmpGroupId = $tmpGroup['id'];
        }else{
            $newId = $this->PriceListGroupManager->insert(['name' => 'nezařazeno']);
            $tmpGroupId = $newId['id'];
        }


        $this->defValues = ['vat' =>  $this->settings->def_sazba,
                     'unit' =>  $this->settings->def_mj,
                     'cl_pricelist_group_id' => $tmpGroupId,
                     'cl_currencies_id' =>  $this->settings->cl_currencies_id];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'],
                                2 => ['group' =>
                                            [0 => ['url' => $this->link('report!', ['index' => 1]),
                                                    'rightsFor' => 'report',
                                                    'label' => $this->translator->translate('Ceník'),
                                                    'title' => $this->translator->translate('Ceník_pro_zvolenou_skupinu'),
                                                    'class' => 'ajax', 'icon' => 'iconfa-print'],
                                                1 => ['url' => $this->link('report!', ['index' => 2]),
                                                    'rightsFor' => 'report',
                                                    'label' => $this->translator->translate('Duplicity_kódů'),
                                                    'title' => $this->translator->translate('Kontrolní_sestava_duplicit_EAN_a_kódů_zboží'),
                                                    'class' => 'ajax', 'icon' => 'iconfa-print'],
                                                2 => ['url' => $this->link('report!', ['index' => 3]),
                                                    'rightsFor' => 'report',
                                                    'label' => $this->translator->translate('EAN_kódy'),
                                                    'title' => $this->translator->translate('Tisk_EAN_kódů'),
                                                    'class' => 'ajax', 'icon' => 'iconfa-print'],
                                            ],
                                            'group_settings' =>
                                                ['group_label' => $this->translator->translate('Tisk'),
                                                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                                    'group_title' =>  $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
                                ],
            3 => ['url' => $this->link('ImportData:', ['modal'=> $this->modal, 'target' => $this->name]), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary'],
            4 => ['group' => [0 => ['url' => $this->link('priceChange!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Ceny'), 'icon' => ' iconfa-remove-circle', 'data' => ['data-ajax="true"', 'data-history="false"'], 'class' => 'ajax'],
                              1 => ['url' => $this->link('supplierChange!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Dodavatelé'), 'icon' => 'iconfa-remove-sign', 'data' => ['data-ajax="true"', 'data-history="false"'], 'class' => 'ajax'],
                              2 => ['url' => $this->link('exciseDuty!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Spotřební_daň_alkohol'), 'icon' => 'iconfa-remove-sign', 'data' => ['data-ajax="true"', 'data-history="false"'], 'class' => 'ajax'],
                              3 => ['url' => $this->link('notActiveSet!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Neaktivní'), 'icon' => 'iconfa-screenshot', 'data' => ['data-ajax="true"', 'data-history="false"'], 'class' => 'ajax'],
                              4 => ['url' => $this->link('eanGenerator!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('EAN_generátor'), 'icon' => 'iconfa-screenshot', 'data' => ['data-ajax="true"', 'data-history="false"'], 'class' => 'ajax']],
                        'group_settings' => ['group_label' => $this->translator->translate('Nástroje'),
                                                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                                    'group_title' =>  $this->translator->translate('Nástroje'), 'group_icon' => 'iconfa-wrench']]
        ];
	
/*	$this->toolbar = array(	1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'),
							2 => array('group' => array(0 => array('url' => $this->link('report!', array('index' => 1)), 'rightsFor' => 'report', 'label' => 'Podlimitní stavy', 'title' => 'Položky ceníku, které mají podlimitní stav na skladě.',
														'class' => 'ajax', 'icon' => 'iconfa-print')
														),
										'group_settings' => 
													array('group_label' => 'Tisk', 'group_class' => 'btn btn-primary dropdown-toggle btn-sm', 'group_title' =>  'tisk', 'group_icon' => 'iconfa-print')
										)		
							);
	$this->report = array( 1 => array('reportLatte' => __DIR__.'/../templates/PriceList/pricelistReportLimits.latte',
									  'reportName' => 'Podlimitní stavy')
							);	
 */
	
        $this->report = [1 => ['reportLatte' => __DIR__.'/../templates/PriceList/ReportPriceListSettings.latte',
                                                                                        'reportName' => $this->translator->translate('Ceník')],
                                2 => ['reportLatte' => __DIR__.'/../templates/PriceList/ReportDuplicityCheckSettings.latte',
                                                'reportName' => $this->translator->translate('Kontrola_duplicit_kódů_a_EAN')],
                                3 => ['reportLatte' => __DIR__.'/../templates/PriceList/ReportEANSettings.latte',
                                    'reportName' => $this->translator->translate('EAN_kódy')]
        ];

	$arrGroups = $this->PriceListGroupManager->findAll()->fetchPairs('id','name');	    
	$this->quickFilter = ['cl_pricelist_group.name' => ['name' => $this->translator->translate('Zvolte_skupinu'),
								    'values' => $arrGroups]
    ];

    $pdCount = count($this->pdFilter);
    $pdCount2 = $pdCount;
    $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
        'filter' => 'not_active = 1',
        'sum' => [],
        'rightsFor' => 'read',
        'label' => $this->translator->translate('Neaktivní'),
        'title' => $this->translator->translate('Neaktivní položky ceníku'),
        'data' => ['data-ajax="true"', 'data-history="true"'],
        'class' => 'ajax', 'icon' => 'iconfa-filter'];

        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => 'not_active = 0',
            'sum' => [],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Aktivní'),
            'title' => $this->translator->translate('Aktivní položky ceníku'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];

	
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
        $this->PriceListManager->setShowNotActive(TRUE);
	    parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
        $this->PriceListManager->setShowNotActive(TRUE);
		parent::renderEdit($id,$copy,$modal);
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData && !is_null($tmpData->cl_storage_id)) {
            $arrStoragePlaces = $this->StoragePlacesManager->findAll()->
                                            where('cl_storage_id = ?', $tmpData->cl_storage_id)->
                                            select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');
                                            //$arrOne = array($control->value => $control->items[$defData1[$control->name]]);
            $this['edit']['cl_storage_places_id']->items = $arrStoragePlaces;
            $this['edit']['quantity']->setValue($this->StoreManager->calcQuantity(['id' => $this->id]));
        }
    }
    

    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');

	    $form->addHidden('id',NULL);
	    $form->addHidden('cl_number_series_id',NULL);	    
	    $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id','currency_name');
	    $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"),$arrCurrencies)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_měnu'))
			->setHtmlAttribute('class','form-control chzn-select input-sm')
			->setRequired($this->translator->translate('Zřejmě_jste_zapoměli_zvolit_měnu'))
			->setPrompt($this->translator->translate('Zvolte_měnu'));
	    //$arrGroups = $this->PriceListGroupManager->findAll()->fetchPairs('id','name');
	    $arrGroups = $this->PriceListGroupManager->getGroupTree();
        $form->addSelect('cl_pricelist_group_id', $this->translator->translate("Skupina"),$arrGroups)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_skupinu'))
			->setHtmlAttribute('class','form-control chzn-select input-sm')
			->setHtmlAttribute('data-urlajax',$this->link('GetGroupNumberSeries!'))
            ->setRequired($this->translator->translate('Zřejmě_jste_zapoměli_zvolit_skupinu'))
			->setPrompt($this->translator->translate('Zvolte_skupinu'));

        $arrGroups = $this->PriceListCategoriesManager->findAll()->select('id, name')->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_pricelist_categories_id', $this->translator->translate("Kategorie"),$arrGroups)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_kategorii'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_kategorii'));

        //$arrGroups = $this->PriceListGroupManager->findAll()->fetchPairs('id','name');
        $form->addSelect('cl_pricelist_group2_id', $this->translator->translate("Skupina_2"),$arrGroups)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_skupinu'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_skupinu'));

        $arrPricesGroups = $this->PricesGroupsManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_prices_groups_id', $this->translator->translate("Cenová_skupina"),$arrPricesGroups)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_cenovou_skupinu'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_cenovou_skupinu'));


        $arrSupplier = $this->PartnersManager->findAll()->where('supplier = 1')->order('company')->fetchPairs('id','company');
	    $form->addSelect('cl_partners_book_id', $this->translator->translate("Dodavatel"),$arrSupplier)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_dodavatele'))
			->setHtmlAttribute('class','form-control chzn-select input-sm noSelect2')
			->setPrompt($this->translator->translate('Zvolte_dodavatele'));

        $form->addSelect('cl_partners_book_id2', $this->translator->translate("Dodavatel_2"),$arrSupplier)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_dodavatele'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm noSelect2')
            ->setPrompt($this->translator->translate('Zvolte_dodavatele'));
        $form->addSelect('cl_partners_book_id3', $this->translator->translate("Dodavatel_3"),$arrSupplier)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_dodavatele'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm noSelect2')
            ->setPrompt($this->translator->translate('Zvolte_dodavatele'));
        $form->addSelect('cl_partners_book_id4', $this->translator->translate("Dodavatel_4"),$arrSupplier)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_dodavatele'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm noSelect2')
            ->setPrompt($this->translator->translate('Zvolte_dodavatele'));

        $arrProducer = $this->PartnersManager->findAll()->where('producer = 1')->order('company')->fetchPairs('id','company');
        $form->addSelect('cl_producer_id', $this->translator->translate("Výrobce"),$arrProducer)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_výrobce'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm noSelect2')
            ->setPrompt($this->translator->translate('Zvolte_výrobce'));

        $arrWasteCategory = $this->WasteCategoryManager->findAll()->select('id, CONCAT(waste_code, " - ", name) AS waste_code')->order('waste_code')->fetchPairs('id','waste_code');
        $form->addSelect('cl_waste_category_id', $this->translator->translate("Kód_odpadu"),$arrWasteCategory)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_kód_odpadu'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm noSelect2')
            ->setPrompt($this->translator->translate('Zvolte_kód_odpadu'));


	    $form->addText('identification', $this->translator->translate('Kód'), 20, 20)
			->setRequired($this->translator->translate('Kód_musí_být_zadán'))
			->addRule(array($this, 'validateIdentification'), $this->translator->translate('Zadejte_jiný_kód,_tento_je_již_použit.'))
			->setHtmlAttribute('data-urlString', $this->link('checkIdentification!'))
			->setHtmlAttribute('placeholder',$this->translator->translate($this->translator->translate('Kód_položky')));
	    
	    $form->addText('order_code', $this->translator->translate('Objednací_kód'), 60, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Objednací_kód'));
	    $form->addText('ean_code', $this->translator->translate('EAN'), 60, 128)
			->setHtmlAttribute('placeholder',$this->translator->translate('EAN'));
	    $form->addText('order_label', $this->translator->translate('Objednací_název'), 60, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název'));
	    $form->addText('item_label', $this->translator->translate('Název'), 60, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název'));
	    $form->addText('unit', $this->translator->translate('Jednotky'), 5, 5)
			->setHtmlAttribute('placeholder',$this->translator->translate('Jednotky'));
	    $form->addText('quantity', $this->translator->translate('Skladem'), 10, 10)
			->setHtmlAttribute('placeholder',$this->translator->translate('Skladem'));
	    $form->addText('price_s', $this->translator->translate('Nákupní_cena'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Nákupní_cena'));

        $form->addText('search_tag', $this->translator->translate('Značky_pro_hledání'), 20, 20)
            ->setHtmlAttribute('placeholder',$this->translator->translate('hledací_značky'));

	    $form->addText('height', $this->translator->translate('Výška'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Výška'));	    
	    $arrUnits = $this->ArraysManager->getDimUnits();
	    $form->addSelect('height_unit', "",$arrUnits)
			->setHtmlAttribute('class','form-control input-sm myInline');
	    
	    $form->addText('width', $this->translator->translate('Sířka'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Sířka'));
	    $form->addSelect('width_unit', "",$arrUnits)
			->setHtmlAttribute('class','form-control input-sm myInline');
	    
	    $form->addText('length', $this->translator->translate('Délka'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Délka'));
	    $form->addSelect('length_unit', "",$arrUnits)
			->setHtmlAttribute('class','form-control input-sm myInline');	    
	    
	    $form->addText('weight', $this->translator->translate('Váha'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Váha'));	    
	    $arrUnits2 = $this->ArraysManager->getWeightUnits();	    
	    $form->addSelect('weight_unit', "",$arrUnits2)
			->setHtmlAttribute('class','form-control input-sm myInline');

        $form->addText('volume', $this->translator->translate('Obsah'), 20, 20)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Obsah'));
        $arrUnits = $this->ArraysManager->getVolumeUnits();
        $form->addSelect('volume_unit', "",$arrUnits)
            ->setHtmlAttribute('class','form-control input-sm myInline');

        $form->addText('percent', $this->translator->translate('%_v_objemu'), 20, 20)
            ->setHtmlAttribute('placeholder',$this->translator->translate('%_v_objemu'));
	    
	    $form->addText('in_package', $this->translator->translate('Počet_v_balení'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('V_balení'));
	    $form->addText('excise_duty', $this->translator->translate('Spotřební_daň'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Spotřební_daň'));
	    
	    if ($this->settings->platce_dph == 1)
			$tmpText = $this->translator->translate("Cena_bez_DPH");
	    else
			$tmpText = $this->translator->translate("Prodejní_cena");
	    
	    $form->addText('price', $tmpText.":", 0, 20)
			->setHtmlAttribute('placeholder',$tmpText);
	
		$form->addText('profit_per', $this->translator->translate('Zisk_%'), 20, 20)
			->setHtmlAttribute('placeholder','');
		$form->addText('profit_abs', $this->translator->translate('Zisk_abs'), 20, 20)
			->setHtmlAttribute('placeholder','');
	    
	    $form->addText('price_vat', $this->translator->translate('Cena_s_DPH'), 20, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Cena_s_DPH'));
	    $arrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates','rates');
	    $form->addSelect('vat', "Sazba_DPH",$arrVat)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_DPH'))
			->setHtmlAttribute('class','form-control chzn-select input-sm')
			->setPrompt($this->translator->translate('Sazba_DPH'));
	    
	    $arrStorage = $this->StorageManager->getStoreTreeNotNested();
	    $storageSelect = $form->addSelect('cl_storage_id', $this->translator->translate("Výchozí_sklad"),$arrStorage)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_sklad'))
			->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax',$this->link('GetStoragePlaces!'))
			->setPrompt($this->translator->translate('Zvolte_sklad'));

        $arrStoragePlaces = $this->StoragePlacesManager->findAll()->
                select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');

        $form->addSelect('cl_storage_places_id', $this->translator->translate("Výchozí_umístění"), $arrStoragePlaces)
                            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_výchozí_umístění'))
                            ->setHtmlAttribute('class','form-control chzn-select input-sm')
                            ->setPrompt($this->translator->translate('Zvolte_výchozí_umístění'));

	    $form->addCheckbox('not_active', $this->translator->translate('Neaktivní'));
        $form->addCheckbox('b2b_not_show', $this->translator->translate('B2B_vypnuto'));
	    
//		$form->addCheckbox('price_e_type', 'Prodejní cena s DPH');

	    $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = [$this, 'stepBack'];
		$form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
		return $form;
    }

	public function validateIdentification($input) {
        // kontrola jedinecnosti
		$this->redrawControl('content');
		return !$this->CheckIdentification($input->getValue());
	}

	public function CheckIdentification($identification)
	{
		if ($this->DataManager->findAll()->where('identification = ? AND id != ?', $identification, $this->id)->fetch())
			return TRUE;
		else 
			return FALSE;				
	}		
	
	public function handleCheckIdentification($identification)
	{
		$result = ['result' => $this->CheckIdentification($identification)];
		//dump(json_encode($result));
		echo(json_encode($result));
		$this->terminate();
			
	}


    public function stepBack()
    {	    
	$this->redrawControl('content');
	$this->redirect('default');
    }		
	

    public function SubmitEditSubmitted(Form $form)
    {
	    $data=$form->values;
	    if ($form['send']->isSubmittedBy())
	    {
		    //remove format from numbers
		    $data = $this->removeFormat($data);
            $tmpOldData = $this->DataManager->find($this->id);

            $this->PriceListPartnerManager->updatePricelist(['old_data' => ['price' => $tmpOldData['price'], 'vat' => $tmpOldData['vat'], 'price_vat' => $tmpOldData['price_vat']],
                                                             'new_data' => ['price' => $data['price'], 'vat' => $data['vat'], 'price_vat' => $data['price_vat']],
                                                             'id' => $data['id']
                                                            ]);

		    if (!empty($data->id)){
				$this->DataManager->update($data, TRUE);
		    }else{
				$this->DataManager->insert($data);
		    }
	    }
	    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
	    $this->redrawControl('content');
	    $this->redirect('default');
    }	    
    
    public function handleGetGroupNumberSeries($cl_pricelist_group_id)
    {
		//Debugger::fireLog($this->id);	    
		$arrData = new \Nette\Utils\ArrayHash;
		$arrData['id'] = NULL;
		$arrData['number'] = '';	
		if ($data = $this->PriceListGroupManager->find($cl_pricelist_group_id))
		{
			//11.11.2019 - added condition that pricelistgroup has to have set cl_number_series_id otherwise we return empty string.
            //it's because we need to 
			if (!is_null($data->cl_number_series_id) && $data2 = $this->NumberSeriesManager->getNewNumber('pricelist', $data->cl_number_series_id, NULL))
			{
			    $arrData = $data2;
			}
		}

		echo(json_encode($arrData));
		$this->terminate();
    }

    public function handleGetStoragePlaces($cl_storage_id)
    {
        $arrData = new \Nette\Utils\ArrayHash;
        $arrData['id'] = NULL;
        $arrData['number'] = '';
        $arrStoragePlaces = $this->StoragePlacesManager->findAll()->
                        where('cl_storage_id = ?', $cl_storage_id)->
                        select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');

        echo(json_encode($arrStoragePlaces));
        $this->terminate();
    }
	
	public function handleReport($index = 0)
	{
		$this->rptIndex = $index;
		$this->reportModalShow = TRUE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
	}
		
	

    public function DataProcessListGridValidate($data)
    {
		$result = NULL;
		return $result;
    }
    
    public function DataProcessListGrid($data)
    {
		return $data;
    }    
    
    public function UpdateSum()
    {
		return;
    }	

    public function beforeDelete($lineId) {
		$result = TRUE;
		return $result;
    }	   	



    public function calcQuantity($params){
        return $this->StoreManager->calcQuantity($params);
    }



	public function getQuantity($parameters)
	{

		if ($retData = $this->StoreManager->findBy(array('cl_pricelist_id' => $parameters['cl_pricelist_id'], 'cl_storage_id' => $parameters['cl_storage_id']))->fetch())
				$retVal = $retData->quantity;
		else
			$retVal = 0;
		return $retVal;
	}
	
	
	/*check if actual tariff enable new pricelist
	 * 
	 */
	public function beforeNew()
	{
		 
		if ($tmpUser = $this->UsersManager->find($this->getUser()->id))
		{
			$tmpLicense = $this->DataManager->findAll()->count();

			if ($tmpLicense < $this->UserManager->trfRecords($this->getUser()->id))
			{
				$result = TRUE;
			}else{
				$result = FALSE;
				$this->flashMessage( $this->translator->translate("nepovoluje_více_položek_v_ceníku"), 'danger' );
			}
		}
		
		
		return $result;
	}
	
	public function afterCopy($newLine, $oldLine)
    {
     //   return parent::afterCopy($newLine, $oldLine); // TODO: Change the autogenerated stub
        $tmpNewLine = $this->DataManager->find($newLine);
        if ($tmpNewLine){
            $tmpNewLine->update(array('quantity' => 0));
        }
        return TRUE;
    }


    protected function createComponentReportPriceList($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    
            $now = new \Nette\Utils\DateTime;

	    $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
            $form->addMultiSelect('cl_pricelist_group',$this->translator->translate('Skupina'), $tmpArrGroups)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_skupinu'));


	    $tmpArrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates','rates');	    
            $form->addMultiSelect('cl_rates_vat',$this->translator->translate('Sazba_DPH'), $tmpArrVat)
                            ->setHtmlAttribute('multiple','multiple')                      
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_sazbu'));
	
            
	    $form->addCheckbox('on_store', $this->translator->translate('Jen_položky_skladem'));
	    $form->addCheckbox('print_store', $this->translator->translate('Tisknout_množství'));
	
		$tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
		$form->addMultiSelect('cl_partners_book',$this->translator->translate('Dodavatel'), $tmpArrPartners)
							->setHtmlAttribute('multiple','multiple')
							->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_dodavatele'));
	    
		$form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		$form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		$form->addSubmit('save_xml', $this->translator->translate('uložit_do_XML'))->setHtmlAttribute('class','btn btn-sm btn-primary');
     
	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackReportPriceList');	    	    
		$form->onSuccess[] = array($this, 'SubmitReportPriceListSubmitted');

		//$form->getElementPrototype()->target = '_blank';
		return $form;
    }

    public function stepBackReportPriceList()
    {	    
		$this->rptIndex = 0;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
    }		

    public function SubmitReportPriceListSubmitted(Form $form)
    {
		$data=$form->values;	
		//dump(count($data['cl_partners_book']));
		//die;
		if ($form['save']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_xml']->isSubmittedBy())
		{    
			$dataReport = $this->PriceListManager->findAll();
			
			if (count($data['cl_pricelist_group']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_pricelist_group_id' => $data['cl_pricelist_group']));

			}
			
			if (count($data['cl_rates_vat']) > 0)
			{
			    $dataReport = $dataReport->where(array('vat' => $data['cl_rates_vat']));
			}
			
			if (count($data['cl_partners_book']) > 0)
			{
				$dataReport = $dataReport->where(array('cl_partners_book_id' => $data['cl_partners_book']));
			}
			
			if ($data['on_store'] == 1)
			{
			    $dataReport = $dataReport->where('quantity > 0');
			}

			if ($form['save']->isSubmittedBy()) {
				$tmpTitle = $this->translator->translate('Ceník');
				$dataOther = array();
				$dataSettings = $data;
				$dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->where(array('id' => $data['cl_partners_book']))->order('company');
				$dataOther['pricelistCategories'] = $this->PriceListGroupManager->findAll()->where(array('id' => $data['cl_pricelist_group']))->order('name');
				$dataOther['Vat'] = $this->RatesVatManager->findAll()->where(array('id' => $data['cl_rates_vat']))->order('rates,description');
				$dataOther['OnStore'] = $data['on_store'];
				$dataOther['PrintStore'] = $data['print_store'];

				//$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Pricelist/ReportPriceList.latte', $dataOther, $dataSettings, $tmpTitle);
                $reportFile = $this->ReportManager->getReport(__DIR__ . '/../templates/PriceList/ReportPriceList.latte');
                //bdump($reportFile);
                //bdump(is_file($reportFile));
                $template = $this->createMyTemplateWS($dataReport, $reportFile, $dataOther, $dataSettings, $tmpTitle);
				$this->pdfCreate($template, $this->translator->translate('Ceník'));
			} elseif ($form['save_csv']->isSubmittedBy()) {
				bdump($dataReport->count());
				if ( $dataReport->count()>0)
				{
					$filename = $this->translator->translate("Ceník");
					$this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename."-" .date('Ymd-Hi').".csv",true));
				}else{
					$this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
					$this->redirect('default');
				}
			} elseif ($form['save_xml']->isSubmittedBy()) {
				if ( $dataReport->count()>0)
				{
					$arrResult = array();
					foreach($dataReport as $key => $one)
					{
						$tmpInv = $one->toArray();
						$arrLine = array();
						foreach($tmpInv as $key1 => $one1)
						{
							$arrLine[$key1] = $one1;
						}
						$arrResult[$key] = $arrLine;
						$tmpPartnerBook = $one->ref('cl_partners_book');
						$arrResult[$key]['partners_book'] = array('id' => $tmpPartnerBook['id'], 'company' => $tmpPartnerBook['company'],'street' => $tmpPartnerBook['street'],
																	'city' => $tmpPartnerBook['city'],'zip' => $tmpPartnerBook['zip'],'ico' => $tmpPartnerBook['ico'],'dic' => $tmpPartnerBook['dic']);
					}
					$filename = "Ceník";
					$this->sendResponse(new \XMLResponse\XMLResponse($arrResult, $filename."-" .date('Ymd-Hi').".xml", NULL, "pricelist"));
				}else{
					$this->flashMessage($this->translator->translate('Data_nebyly_do_XML_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
					$this->redirect('default');
				}
			}
			
			
			
			
		}
	}


    //aditional control before addline from listgrid
    public function beforeAddLine($data)
    {
        if ($data['control_name'] == "priceListLimitGrid")
        {
            $tmpData = $this->PriceListLimitsManager->findAll()->where('cl_pricelist_id = ?', $data['cl_pricelist_id'])->order('id DESC')->limit(1)->fetch();
            if ($tmpData) {
                $data['quantity_min'] = $tmpData['quantity_min'];
                $data['quantity_req'] = $tmpData['quantity_req'];
            }
        }
        return $data;
    }

    public function afterDataSaveListGrid($dataId,$name = NULL)
	{
	    if ($name == "pricesGroupsGrid")
	    {
			//recalc price with vat our without vat if we are vatpayer
			if ($this->settings->platce_dph == 1 && $tmpData = $this->PricesManager->find($dataId))
			{
				$newData = array();
				$newData['id'] = $dataId;
				if ($this->settings->price_e_type == 0)
				{
					$newData['price_vat'] = $tmpData->price * (1 + ($tmpData->cl_pricelist->vat/100));
				}else{
					$newData['price'] = $tmpData->price_vat / (1 + ($tmpData->cl_pricelist->vat/100));
				}
				$this->PricesManager->update($newData);
			}
	    }elseif ($name == "priceListLimitGrid"){
	    	$tmpData = $this->PriceListLimitsManager->find($dataId);
	    	if ($tmpData) {
				$tmpStore2U = $this->StoreManager->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?', $tmpData['cl_pricelist_id'], $tmpData['cl_storage_id']);
				$tmpStore2U->update(array('cl_pricelist_limits_id' => $tmpData['id']));
			}
		}
	}





    protected function createComponentReportDuplicityCheckSettings($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id',NULL);

        $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group',$this->translator->translate('Skupina'), $tmpArrGroups)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_skupinu'));

        $form->addRadioList('duplicity_type', $this->translator->translate('Kontrolovat'), array('identification' => $this->translator->translate('Kód_zboží'), 'ean_code' => 'EAN', 'order_code' => $this->translator->translate('Objednací_kód')))
                ->setDefaultValue('identification');

        $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_csv', $this->translator->translate('CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackDuplicityCheckSettings');
        $form->onSuccess[] = array($this, 'SubmitDuplicityCheckSettings');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackDuplicityCheckSettings()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitDuplicityCheckSettings(Form $form)
    {
        $data = $form->values;
        if ($form['save_pdf']->isSubmittedBy() || $form['save_csv']->isSubmittedBy()) {
            if ($data['duplicity_type'] == 'identification'){
                $dataReport = $this->PriceListManager->findAll()->
                        select('cl_pricelist.*, (SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE identification = cl_pricelist.identification) AS count')->
                        where('(SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE identification = cl_pricelist.identification) > 1 AND identification != ""')->order('identification');
            }elseif ($data['duplicity_type'] == 'ean_code'){
                $dataReport = $this->PriceListManager->findAll()->
                        select('cl_pricelist.*, (SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE ean_code = cl_pricelist.ean_code) AS count')->
                        where('(SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE ean_code = cl_pricelist.ean_code) > 1 AND ean_code != ""')->order('ean_code');
            }elseif($data['duplicity_type'] == 'order_code'){
                $dataReport = $this->PriceListManager->findAll()->
                        select('cl_pricelist.*, (SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE order_code = cl_pricelist.order_code) AS count')->
                        where('(SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE order_code = cl_pricelist.order_code) > 1 AND order_code != ""')->order('order_code');
            }


            if (count($data['cl_pricelist_group']) > 0) {
                $dataReport = $dataReport->where(array('cl_pricelist_group_id' => $data['cl_pricelist_group']));
            }
            //$dataReport = $dataReport->order('identification');
            //bdump($dataReport);
            if ($form['save_pdf']->isSubmittedBy()) {

                //die;
                $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
                $tmpTitle = $this->translator->translate('Duplicita_kódů_zboží_a_EAN');
                $dataOther = array();
                $dataSettings = $data;
                $dataOther['pricelistGroup'] = $this->PriceListGroupManager->findAll()->where(array('id' => $data['cl_pricelist_group']))->order('name');


                $reportFile = $this->ReportManager->getReport(__DIR__ . '/../templates/PriceList/ReportDuplicityCheck.latte');
                $template = $this->createMyTemplateWS($dataReport, $reportFile, $dataOther, $dataSettings, $tmpTitle);
                $this->pdfCreate($template, $this->translator->translate('Duplicita_kódů_a_EAN'));

            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Kniha_faktur_vydaných");
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
        }

    }

	public function recalcStores($id, $cl_storage_id)
    {

        $result = $this->StoreManager->repairBalance($id, $cl_storage_id);
       //sbdump($result);
        if (empty($result)) {
            $this->flashMessage($this->translator->translate('Bylo_přepočítáno'), 'success');
        }else{
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebylo_přepočítáno').$result->getMessage(), 'danger');
        }

       $this->redrawControl('content');

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



    public function handlePriceChange(){
        //$this->importType = $type;
        $this->showModal('priceChange');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentPriceChangeForm()
    {
        $form = new Form;

        $form->addText('price_change_per', 'Změna ceny %:')
            ->setHtmlAttribute('placeholder','procento změny');
        $form->addText('price_change_abs', 'Změna ceny ABS:')
            ->setHtmlAttribute('placeholder','částka změny');

        $form->addRadioList('base', 'Základ změny je:', array('price_s' => 'Skladová cena', 'price' => 'Prodejní cena bez DPH', 'price_vat' => 'Prodejní cena s DPH'))
            ->setDefaultValue('price');

        $form->addCheckbox('round', 'Zaokrouhlit výsledek na celé číslo')
            ->setDefaultValue(true);

        $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group','Skupina:', $tmpArrGroups)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder','Vyberte skupiny');

        $tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book','Dodavatel:', $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder','Vyberte dodavatele');

        $form->addSubmit('submit', 'Změnit ceny')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-success');
        $form->addSubmit('restore', 'Vrátit předchozí')
            ->setHtmlAttribute('title', 'vrátí ceny, které byly platné před poslední hromadnou změnou')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-warning');

        $form->addSubmit('storno', 'Zpět')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "PriceChangeFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function PriceChangeFormSubmited($form)
    {
        $values = $form->getValues();
        if ($form['submit']->isSubmittedBy() || $form['restore']->isSubmittedBy() ) {
            try {
                $result = 0;
                if ($form['submit']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->priceChange($values);
                    $this->PriceListPartnerManager->updatePricelist();
                }elseif ($form['restore']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->restorePrice();
                    $this->PriceListPartnerManager->updatePricelist();
                }

                $this->flashMessage($this->translator->translate('Aktualizováno_bylo ') . $result . $this->translator->translate(' položek.'), 'success');
                $this->hideModal('priceChange');
                $this->redrawControl('flash');
                $this->redrawControl('content');
            } catch (Exception $e) {
                $form->addError($e->getMessage());
                $this->redrawControl('flash');
                $this->redrawControl('content');
            }
        }
    }



    public function handleSupplierChange(){
        $this->showModal('supplierChange');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentSupplierChangeForm()
    {
        $form = new Form;

        $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group',$this->translator->translate('Skupina'), $tmpArrGroups)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_skupiny'));

        $tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book_old',$this->translator->translate('Původní_dodavatel'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_dodavatele'));

        //$tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
        $form->addSelect('cl_partners_book_new',$this->translator->translate('Nový_dodavatel'), $tmpArrPartners)
            ->setPrompt($this->translator->translate('Vyberte_nového_dodavatele'))
            ->setRequired($this->translator->translate('Nový_dodavatel_musí_být_vybrán'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_dodavatele'));

        $form->addRadioList('partner_order_old', $this->translator->translate('Pořadí_původního_dodavatele'), array( 1 => $this->translator->translate("Dodavatel_1"), 2 => $this->translator->translate("Dodavatel_2"), 3 => $this->translator->translate("Dodavatel_3"), 4 => $this->translator->translate("Dodavatel_4") ))
            ->setDefaultValue('1');

        $form->addRadioList('partner_order_new', $this->translator->translate('Pořadí_nového_dodavatele'), array( 1 => $this->translator->translate("Dodavatel_1"), 2 => $this->translator->translate("Dodavatel_2"), 3 => $this->translator->translate("Dodavatel_3"), 4 => $this->translator->translate("Dodavatel_4") ))
            ->setDefaultValue('1');

        $form->addSubmit('submit', $this->translator->translate('Změnit_dodavatele'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-success');
        $form->addSubmit('restore', $this->translator->translate('Vrátit_předchozí'))
            ->setHtmlAttribute('title', $this->translator->translate('vrátí_položkám_dodavatele_který_byl_platný_před_předchozí_změnou_dodavatele'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-warning');

        $form->addSubmit('storno', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "SupplierChangeFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function SupplierChangeFormSubmited($form)
    {
        $values = $form->getValues();
        if ($form['submit']->isSubmittedBy() || $form['restore']->isSubmittedBy() ) {
            try {
                $result = 0;
                if ($form['submit']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->supplierChange($values);
                }elseif ($form['restore']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->restoreSupplier();
                }

                $this->flashMessage($this->translator->translate('Aktualizováno_bylo') . $result .$this->translator->translate('položek'), 'success');
                $this->hideModal('priceChange');
                $this->redrawControl('flash');
                $this->redrawControl('content');
            } catch (\Exception $e) {
                $form->addError($e->getMessage());
                $this->redrawControl('flash');
                $this->redrawControl('content');
            }
        }
    }

    public function handleNotActiveSet(){
        $this->PriceListManager->setNotActiveReset();

        $this->showModal('notActiveSet');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentNotActiveSetForm()
    {
        $form = new Form;

        $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_pricelist_group',$this->translator->translate('Skupina'), $tmpArrGroups)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_skupinu'))
            ->setPrompt($this->translator->translate('Vyberte_skupinu'));

        $tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
        $form->addSelect('cl_partners_book',$this->translator->translate('Dodavatel'), $tmpArrPartners)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_dodavatele'))
            ->setPrompt($this->translator->translate('Vyberte_dodavatele'));

        $tmpArrPartners2 = $this->PartnersManager->findAll()->order('company')->where('producer = 1')->fetchPairs('id','company');
        $form->addSelect('cl_producer',$this->translator->translate('Výrobce'), $tmpArrPartners2)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_výrobce'))
            ->setPrompt($this->translator->translate('Vyberte_výrobce'));

        $now = new DateTime();
        $form->addText('date_to', $this->translator->translate('Bez_použití_po'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_použití'));


     /*   $form->addSubmit('submit', $this->translator->translate('Nastavit neaktivní'))
            ->setHtmlAttribute('title', $this->translator->translate('položky, které vyhovují zadání nastaví jako neaktivní'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-warning');
        $form->addSubmit('setActive', $this->translator->translate('Nastavit aktivní'))
            ->setHtmlAttribute('title', $this->translator->translate('položky, které vyhovují zadání nastaví jako aktivní'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-success');

        $form->addSubmit('storno', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        */

        $form->addSubmit('submit', $this->translator->translate('Zobrazit_vyhovující_položky'))
            ->setHtmlAttribute('title', $this->translator->translate('zobrazí_položky_které_vyhovují_zadání'))
            ->setHtmlAttribute('class', 'form-control btn-success');
        $form->onSuccess[] = array($this, "NotActiveSetFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function NotActiveSetFormSubmited($form)
    {
        $values = $form->getValues();
        if ($form['submit']->isSubmittedBy() ) {
            if ($values['date_to'] == "") {
                $values['date_to'] = NULL;
            }else{
                $values['date_to'] = date('Y-m-d H:i:s',strtotime($values['date_to']) + 86400 - 10);
            }
            $this->PriceListManager->setNotActivePrepare($values);
           // $this->redrawControl('createDocs');
            $this->redrawControl('itemsprep');
        }
    }

    protected function createComponentListGridItems()
    {
        $this->PriceListManager->setShowNotActive(true);
        $arrData = array(
            'identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10),
            'item_label' => array($this->translator->translate('Název'), 'format' => 'text', 'size' => 20),
            'not_active' => array($this->translator->translate('Neaktivní'), 'format' => 'boolean', 'size' => 7),
            'ean_code' => array($this->translator->translate('EAN'), 'format' => 'text', 'size' => 10),
            'cl_partners_book.company' => array($this->translator->translate('Dodavatel'), 'format' => 'text', 'size' => 15),
            'quantity' => array($this->translator->translate('Skladem'), 'format' => 'number', 'size' => 10),
            'unit' => array($this->translator->translate('Jednotky'), 'format' => 'text', 'size' => 5),
            'price' => array($this->translator->translate('Cena_bez_DPH'), 'format' => 'currency', 'size' => 10),
            'price_vat' => array($this->translator->translate('Cena_s_DPH'), 'format' => 'currency', 'size' => 10)
        );

        $now = new \Nette\Utils\DateTime;
        $tmpParentId = NULL;

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->DataManager,
            $arrData,
            array(),
            $tmpParentId, //parent_id
            array(), //default data
            NULL,
            NULL, //pricelist manager
            FALSE,
            FALSE, //add empty row
            array(), //custom links
            FALSE, //movable row
            'item_label DESC', //ordercolumn
            TRUE, //selectmode
            array(), //quicksearch
            "", //fontsize
            'id', //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setPaginatorOff();
        $control->setFilter(array('filter' => 'not_active_prep = 1'));
        $control->setContainerHeight('auto');
        $control->setEnableSearch('identification LIKE ? OR item_label LIKE ?');
        $control->setSearchPlaceholder('Hledaný text');
        return $control;

    }


    public function handleSetNotActive(){
        try {
            if ($this->isAllowed($this->name, 'edit')) {
                $this->PriceListManager->findAll()->where('not_active_prep = 1')->update(array('not_active' => 1, 'not_active_prep' => 0));
                $this->flashMessage($this->translator->translate('Vybrané_položky_byly_nastavené_jako_neaktivní'), 'success');
                $this->redrawControl('itemsprep');
                $this->redrawControl('flash');
            }
        } catch (\Exception $e) {
            $this->redrawControl('itemsprep');
            $this->redrawControl('flash');
        }
    }

    public function handleSetActive(){
        try {
            if ($this->isAllowed($this->name, 'edit')) {
                $this->PriceListManager->findAll()->where('not_active_prep = 1')->update(array('not_active' => 0, 'not_active_prep' => 0));
                $this->flashMessage($this->translator->translate('Vybrané_položky_byly_nastavené_jako_aktivní'), 'success');
                $this->redrawControl('itemsprep');
                $this->redrawControl('flash');
            }
        } catch (\Exception $e) {
            $this->redrawControl('itemsprep');
            $this->redrawControl('flash');
        }
    }

    public function handleSetNotActivePrep($idPricelist, $value){
        if ($this->isAllowed($this->name, 'edit')) {
            $this->PriceListManager->find($idPricelist)->update(array('not_active_prep' => (($value == "true") ? 1 : 0)));
            $this->redrawControl('preparedCounter');
        }
    }

    public function handleSetNotActivePrepAll($value){
        if ($this->isAllowed($this->name, 'edit')) {
            $this->PriceListManager->findAll()->where('not_active_prep = 1')->update(array('not_active_prep' => 0));
            $this->redrawControl('preparedCounter');
        }
    }

    public function getPrepared(){
        $this->PriceListManager->setShowNotActive(true);
        return   $this->PriceListManager->findAll()->where('not_active_prep = 1')->count();
    }

    public function getNotActive(){
        $this->PriceListManager->setShowNotActive(true);
        return   $this->PriceListManager->findAll()->where('not_active_prep = 1 AND not_active = 1')->count();
    }

    public function handleUpdatePriceS($id){
        if ($this->isAllowed($this->name, 'edit')) {
            $result = $this->StoreManager->UpdateZeroPriceS($id);
            if (!self::hasError($result)) {
                $this->flashMessage($this->translator->translate('Byly_zaktualizovány_nákupní_ceny_v_příjmech_na_sklad._Počet:') . $result['success'], 'success');
                $result = $this->StoreManager->repairOutcomePriceS($id);
                if (empty($result)) {
                    $this->flashMessage($this->translator->translate('Byly_přepočítány_výdejní_ceny'), 'success');
                } else {
                    $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_přepočítány_výdejní_ceny') . $result->getMessage(), 'error');
                }
            } else {
                $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_zaktualizovány_nákupní_ceny_v_příjmech_na_sklad') . $result['error'], 'error');
            }
            $this->redrawControl('content');
        }
    }

    public function handleSetPriceS($id, $value){
        if ($this->isAllowed($this->name, 'edit')) {
            $this->DataManager->find($id)->update(array('price_s' => (float)$value));
            $this->redrawControl('flash');
        }
    }

    protected function createComponentGraphVolume()
    {
        $tmpshowData['price_s'] = $this->DataManager->findAll()->where('cl_pricelist.id = ?', $this->id)->select('DISTINCT DATE(CONCAT(YEAR(:cl_store:cl_store_move.cl_store_docs.doc_date),"/",MONTH(:cl_store:cl_store_move.cl_store_docs.doc_date),"/","01")) AS doc_date2, AVG(:cl_store:cl_store_move.price_s) AS price_s')
            ->where(':cl_store:cl_store_move.cl_store_docs.doc_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->order('doc_date2 DESC')
            ->group('doc_date2')->limit(12)->fetchPairs('doc_date2','price_s');
/*        $tmpshowData['sales'] = $this->CommissionManager->findAll()->select('DATE(CONCAT(YEAR(cm_date),"/",MONTH(cm_date),"/","01")) AS cm_date2,cm_date,SUM(price_e2_vat*currency_rate) AS price_e2_vat')
            ->where('cm_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->order('cm_date2 DESC')
            ->group('cm_date2')->limit(12)->fetchPairs('cm_date2','price_e2_vat');
*/
        $showDataPricelist = array();
        foreach($tmpshowData['price_s'] as $key => $one)
        {
            $showDataPricelist[] = array($key,$one);
        }

        $showData['price_s'] = json_encode($showDataPricelist);
        $name = $this->translator->translate("Historie_nákupních_cen");
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new GraphControl($this->translator, $showData, $name, 'graphVolume', 'pricelist.latte');
    }

    protected function createComponentSalesVolume()
    {
        $tmpshowData['sales'] = $this->DataManager->findAll()->where('cl_pricelist.id = ?', $this->id)->select('DISTINCT DATE(CONCAT(YEAR(:cl_store:cl_store_move.cl_store_docs.doc_date),"/",MONTH(:cl_store:cl_store_move.cl_store_docs.doc_date),"/","01")) AS doc_date2, SUM(:cl_store:cl_store_move.s_out) AS s_out')
            ->where(':cl_store:cl_store_move.cl_store_docs.doc_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->order('doc_date2 DESC')
            ->group('doc_date2')->limit(12)->fetchPairs('doc_date2','s_out');
        /*        $tmpshowData['sales'] = $this->CommissionManager->findAll()->select('DATE(CONCAT(YEAR(cm_date),"/",MONTH(cm_date),"/","01")) AS cm_date2,cm_date,SUM(price_e2_vat*currency_rate) AS price_e2_vat')
                    ->where('cm_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
                    ->order('cm_date2 DESC')
                    ->group('cm_date2')->limit(12)->fetchPairs('cm_date2','price_e2_vat');
        */
        $showDataPricelist = array();
        foreach($tmpshowData['sales'] as $key => $one)
        {
            $showDataPricelist[] = array($key,$one);
        }

        $showData['sales'] = json_encode($showDataPricelist);
        $name = $this->translator->translate("Historie_prodejů");
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new GraphControl($this->translator, $showData, $name, 'graphSales', 'pricelist.latte');
    }

    public function handleEANGenerator(){
        //$this->importType = $type;
        $this->showModal('eanGenerator');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentEanGeneratorForm()
    {
        $form = new Form;

        $form->addCheckbox('all_new', 'Přepsat existující EAN kódy')
            ->setDefaultValue(false);

        $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group','Skupina:', $tmpArrGroups)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder','Vyberte skupiny');

        $tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book',$this->translator->translate('Dodavatel'), $tmpArrPartners)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_dodavatele'));

        $tmpArrPartners2 = $this->PartnersManager->findAll()->order('company')->where('producer = 1')->fetchPairs('id','company');
        $form->addMultiSelect('cl_producer',$this->translator->translate('Výrobce'), $tmpArrPartners2)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_výrobce'));


        $tmpEanSeries = $this->NumberSeriesManager->findAll()->order('form_name')->where('form_use = ?', 'pricelist_ean')->fetchPairs('id','form_name');
        $form->addSelect('cl_number_series_id','Číselná řada EAN:', $tmpEanSeries)
            ->setHtmlAttribute('data', 'select2')
            ->setHtmlAttribute('placeholder','Vyberte číselnou řadu EAN')
            ->setRequired('Musí být vybráno')
            ->setPrompt($this->translator->translate('Vyberte_číselnou_řadu_EAN'));

        $form->addSubmit('submit', 'Generovat EANy')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-success');
        $form->addSubmit('restore', 'Vrátit předchozí')
            ->setHtmlAttribute('title', 'vrátí EANy, které byly platné před poslední hromadnou změnou')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-warning');

        $form->addSubmit('storno', 'Zpět')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "EanGeneratorFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function EanGeneratorFormSubmited($form)
    {
        $values = $form->getValues();
        if ($form['submit']->isSubmittedBy() || $form['restore']->isSubmittedBy() ) {
            try {
                $result = 0;
                if ($form['submit']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->generateEAN($values);
                }elseif ($form['restore']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->restoreEAN($values);
                }

                $this->flashMessage($this->translator->translate('Aktualizováno_bylo ') . $result . $this->translator->translate(' položek.'), 'success');
                $this->hideModal('eanGenerator');
                $this->redrawControl('flash');
                $this->redrawControl('content');
            } catch (Exception $e) {
                $form->addError($e->getMessage());
                $this->redrawControl('flash');
                $this->redrawControl('content');
            }
        }
    }


    protected function createComponentReportEAN($name)
    {
        $form = new Form($this, $name);

        $now = new \Nette\Utils\DateTime;

        $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group',$this->translator->translate('Skupina'), $tmpArrGroups)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_skupinu'));

        $form->addCheckbox('counted', $this->translator->translate('Počet_kopií_podle_množství_skladem'));
        $form->addCheckbox('changed_from_en', $this->translator->translate('Tisknout_jen_novější_než_zadané_datum'));

        $tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book',$this->translator->translate('Dodavatel'), $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_dodavatele'));

        $tmpArrPartners2 = $this->PartnersManager->findAll()->order('company')->where('producer = 1')->fetchPairs('id','company');
        $form->addMultiSelect('cl_producer',$this->translator->translate('Výrobce'), $tmpArrPartners2)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_výrobce'));

        $form->addText('changed_from', 'Změněno od')
            ->setDefaultValue($now->format('d.m.Y H:i'))
                ->setHtmlAttribute('placeholder', 'Změněno od');

        $arrSize = ['0.7' => 8, '1' => 6, '2' => 4, '4' => 2, '5' => 1];
        $form->addSelect('size', $this->translator->translate('Počet_kódů_na_stránku'), $arrSize)
            ->setHtmlAttribute('data', 'select2')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_počet_kódů'))
            ->setRequired($this->translator->translate('Musí_být_vybráno'));


        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportEAN');
        $form->onSuccess[] = array($this, 'SubmitReportEANSubmitted');

        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportEAN()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportEANSubmitted(Form $form)
    {
        $data=$form->values;

        //dump(count($data['cl_partners_book']));
        //die;

        if ($form['save']->isSubmittedBy())
        {
            $data['changed_from'] = date('Y-m-d H:i', strtotime($data['changed_from']));

            $dataReport = $this->PriceListManager->findAll();

            if (count($data['cl_pricelist_group']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_pricelist_group_id' => $data['cl_pricelist_group']));
            }

            if (count($data['cl_partners_book']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_partners_book_id' => $data['cl_partners_book']));
            }

            if (count($data['cl_producer']) > 0)
            {
                $dataReport = $dataReport->where(array('cl_producer_id' => $data['cl_producer']));
            }            

            if ($data['changed_from_en'] == 1)
            {
                $dataReport = $dataReport->where('changed >= ?', $data['changed_from']);
            }

            if ($form['save']->isSubmittedBy()) {
                $tmpTitle = $this->translator->translate('Ceník');
                $dataOther = array();
                $dataSettings = $data;
                //$dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->where(array('id' => $data['cl_partners_book']))->order('company');
                //$dataOther['pricelistCategories'] = $this->PriceListGroupManager->findAll()->where(array('id' => $data['cl_pricelist_group']))->order('name');
                $dataOther['counted'] = $data['counted'];
                $dataOther['size'] = $data['size'];
                //$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Pricelist/ReportPriceList.latte', $dataOther, $dataSettings, $tmpTitle);
                $reportFile = $this->ReportManager->getReport(__DIR__ . '/../templates/PriceList/ReportEAN.latte');
                //bdump($reportFile);
                //bdump(is_file($reportFile));
                $template = $this->createMyTemplateWS($dataReport, $reportFile, $dataOther, $dataSettings, $tmpTitle);
                $this->pdfCreate($template, $this->translator->translate('Ceník_EAN'));
            }




        }
    }



    public function handleExciseDuty(){
        //$this->importType = $type;
        $this->showModal('exciseDuty');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentExciseDutyForm()
    {
        $form = new Form;

        $form->addText('excise_rate', 'Sazba spotřební daně:')
            ->setDefaultValue('322.5')
            ->setHtmlAttribute('placeholder','');

        $tmpArrGroups = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_group','Skupina:', $tmpArrGroups)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder','Vyberte skupiny');

        $tmpArrPartners = $this->PartnersManager->findAll()->order('company')->where('supplier = 1')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book','Dodavatel:', $tmpArrPartners)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder','Vyberte dodavatele');

        $tmpArrCategories = $this->PriceListCategoriesManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_pricelist_categories','Kategorie:', $tmpArrCategories)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder','Vyberte kategorie');


        $form->addSubmit('submit', 'Výpočítat spotřební daň')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-success');
        $form->addSubmit('restore', 'Vrátit předchozí')
            ->setHtmlAttribute('title', 'vrátí spotřební daň, která byly platné před poslední hromadnou změnou')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-warning');

        $form->addSubmit('storno', 'Zpět')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "ExciseDutyFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function ExciseDutyFormSubmited($form)
    {
        $values = $form->getValues();
        if ($form['submit']->isSubmittedBy() || $form['restore']->isSubmittedBy() ) {
            try {
                $result = 0;
                if ($form['submit']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->exciseDutyChange($values);
                }elseif ($form['restore']->isSubmittedBy())
                {
                    $result = $this->PriceListManager->restoreExciseDuty();
                }

                $this->flashMessage($this->translator->translate('Aktualizováno_bylo') . ' ' .  $result . ' ' . $this->translator->translate('položek.'), 'success');
                $this->hideModal('exciseDuty');
                $this->redrawControl('flash');
                $this->redrawControl('content');
            } catch (Exception $e) {
                $form->addError($e->getMessage());
                $this->redrawControl('flash');
                $this->redrawControl('content');
            }
        }
    }







}
