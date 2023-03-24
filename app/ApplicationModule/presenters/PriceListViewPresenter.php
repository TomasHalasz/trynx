<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class PriceListViewPresenter extends \App\Presenters\BaseListPresenter {

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
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchUsersManager
     */
    public $CompanyBranchUsersManager;


    /*protected function createComponentOnstore()
     {
		return new OnstoreControl($this->id,$this->StoreManager);
     }*/       
    
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
		 $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
		 $tmpStorageId = NULL;
		 if (!is_null($tmpBranchId)) {
			 $tmpStorageId = $this->CompanyBranchManager->find($tmpBranchId)->cl_storage_id;
		 }
			
		return new StoremoveControl(
            $this->translator,$this->id,$this->StoreManager,$this->StoreMoveManager,$this->StoreOutManager, $tmpStorageId);
     }      
    
	 
    protected function createComponentPriceListLimitGrid()
	{
		$arrStorage = $this->StorageManager->getStoreTree();
		bdump($arrStorage);
		$arrData = array('cl_storage.name' => array($this->translator->translate('Sklad'),'format' => "text",'size' => 20,'values' => $arrStorage, 'roCondition' => '$defData["changed"] != NULL'),
							'quantity_min' => array($this->translator->translate('Minimální množství'),'format' => "number",'size' => 20),
							'quantity_req' => array($this->translator->translate('Požadované množství'),'format' => 'number','size' => 30),
							'quantity_function_' => array($this->translator->translate('Skladem'), 'format' => 'number-function', 'size' => 20, 'readonly' => TRUE, 'function' => 'getQuantityStorage', 'function_param' => array('cl_pricelist_id', 'cl_storage_id'))
		);
		return new Controls\ListgridControl(
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
     }   
     
    protected function createComponentPriceListMacroGrid()
	{
		$arrPriceList = $this->PriceListManager->findAll()->where('cl_pricelist.id != ? AND cl_pricelist_group.is_component = 1', $this->id)->
				    select('CONCAT(identification," ",item_label) AS label, cl_pricelist.id')->order('label,cl_pricelist.id')->fetchPairs('id', 'label');		
		$arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'chzn-select', 'size' => 20,'values' => $arrPriceList),
				 'cl_pricelist.item_label' => array($this->translator->translate('Název'),'format' => "text",'size' => 20, 'readonly' => TRUE),
				 'quantity' => array($this->translator->translate('Množství'),'format' => 'number','size' => 15),
				 'waste' => array($this->translator->translate('Odpad'),'format' => 'number','size' => 15));
		return new Controls\ListgridControl(
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
     }       

    protected function createComponentPriceListBondsGrid()
	{
		$arrPriceList = $this->PriceListManager->findAll()->where('cl_pricelist.id != ? AND cl_pricelist_group.is_component = 1', $this->id)->
				    select('CONCAT(identification," ",item_label) AS label, cl_pricelist.id')->order('label,cl_pricelist.id')->fetchPairs('id', 'label');		
		$arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'chzn-select', 'size' => 20,'values' => $arrPriceList),
				 'cl_pricelist.item_label' => array($this->translator->translate('Název'),'format' => "text",'size' => 20, 'readonly' => TRUE),
				 'quantity' => array($this->translator->translate('Množství'),'format' => 'number','size' => 15),
				 'discount' => array($this->translator->translate('Sleva %'),'format' => 'number','size' => 15));
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
		$arrData = array( 'cl_prices_groups.name' => array($this->translator->translate('Název'),'format' => "text",'size' => 40,'values' => $arrGroups, 'roCondition' => '$defData["changed"] != NULL'));
		if ($this->settings->price_e_type == 0){
		    if ($this->settings->platce_dph == 1){
			$text = $this->translator->translate('Cena bez DPH');
		    }else{
			$text = $this->translator->translate('Cena');
		    }
		    $arrData['price'] = array($text, 'format' => 'currency','size' => 30, 'decplaces' => $this->settings->des_cena);
		}else{
		    $arrData['price_vat'] = array($this->translator->translate('Cena s DPH'),'format' => 'currency','size' => 30, 'decplaces' => $this->settings->des_cena);
		}
		$arrData['cl_currencies.currency_name'] = array($this->translator->translate('Měna'),'format' => "text",'size' => 40, 'values' => $arrCurrencies);
		return new Controls\ListgridControl(
            $this->translator,$this->PricesManager, //data manager
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
        $this->mainTableName = 'cl_pricelist';
	//$settings = $this->CompaniesManager->getTable()->fetch();
        //				    'quantity' => array('Skladem','size'  => 30, 'format' => 'number'),
        //'quantity' => array('Skladem','size'  => 30, 'format' => 'number'),
	if ($this->settings->platce_dph == 1){
	    $arrData = ['identification' => [$this->translator->translate('Kód'), 'size' => 120],
				    'item_label' => [$this->translator->translate('Název'), 'size' => 250],
				    'cl_pricelist_group.name' => [$this->translator->translate('Skupina'), 'size' => 60],
				    'quantity' => [$this->translator->translate('Skladem'), 'format' => 'number', 'function' => 'getStoreQuant', 'function_param' => ['id']],
				    'unit' => [$this->translator->translate('Jednotky'),'size'  => 30],
				    'price' => [$this->translator->translate('Cena bez DPH'), 'format' => 'currency'],
				    'price_vat' =>  [$this->translator->translate('Cena s DPH'), 'format' => 'currency'],
				    'vat' => [$this->translator->translate('Sazba DPH'),'size'  => 30],
				    'cl_currencies.currency_name' => [$this->translator->translate('Měna'),'size'  => 10],
				    'not_active' => [$this->translator->translate('Neaktivní'), 'size' => 10, 'format' => 'boolean'],
				    'ean_code' => [$this->translator->translate('EAN kód'), 'size' => 30],
				    'order_code' => [$this->translator->translate('Objednací kód'), 'size' => 30],
				    'order_label' => [$this->translator->translate('Objednací název'), 'size' => 250],
				    'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'size' => 20, 'format' => 'text'],
                    'search_tag' => [$this->translator->translate('Značky pro hledání'), 'size' => 60],
				    'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],'create_by' => $this->translator->translate('Vytvořil'),'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
	}else{
	    $arrData = ['identification' => [$this->translator->translate('Kód'), 'size' => 120],
				    'item_label' => [$this->translator->translate('Název'), 'size' => 250],
				    'cl_pricelist_group.name' => [$this->translator->translate('Skupina'), 'size' => 60],
                    'quantity' => [$this->translator->translate('Skladem'), 'format' => 'number', 'function' => 'getStoreQuant', 'function_param' => ['id']],
				    'unit' => [$this->translator->translate('Jednotky'),'size'  => 30],
				    'price' => [$this->translator->translate('Prodejní cena'), 'format' => 'currency'],
				    'cl_currencies.currency_name' => [$this->translator->translate('Měna'),'size'  => 30],
                    'not_active' => [$this->translator->translate('Neaktivní'), 'size' => 10, 'format' => 'boolean'],
				    'ean_code' => [$this->translator->translate('EAN kód'), 'size' => 30],
				    'order_code' => [$this->translator->translate('Objednací kód'), 'size' => 30],
				    'order_label' => [$this->translator->translate('Objednací název'), 'size' => 250],
				    'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'size' => 20, 'format' => 'text'],
                    'search_tag' => [$this->translator->translate('Značky pro hledání'), 'size' => 60],
				    'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],'create_by' => $this->translator->translate('Vytvořil'),'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
	}
		
	$this->dataColumns = $arrData;
	$this->FilterC = 'UPPER(currency_name) LIKE ?';
	$this->filterColumns = ['identification' => 'autocomplete',
					'item_label' => 'autocomplete', 
					'quantity' => 'autocomplete', 
					'cl_pricelist_group.name' => 'autocomplete',
					'order_code' => 'autocomplete', 
					'order_label' => 'autocomplete', 
					'price' => 'autocomplete',
					'price_vat' => 'autocomplete', 
					'vat' => 'autocomplete', 
					'unit' => 'autocomplete', 
					'cl_currencies.currency_name' => 'autocomplete', 
					'ean_code' => 'autocomplete'];

    $this->userFilterEnabled = TRUE;
    $this->userFilter = ['identification', 'item_label', 'ean_code', 'order_code', 'search_tag', 'cl_partners_book.company'];

	$this->DefSort = 'identification';
	$this->numberSeries = ['use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification'];
	$this->readOnly = ['quantity' => TRUE,'price_s' => TRUE];

	$this->defValues = ['vat' =>  $this->settings->def_sazba,
				 'unit' =>  $this->settings->def_mj,
				 'cl_currencies_id' =>  $this->settings->cl_currencies_id];
    $this->toolbar = [];
	
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
	


	$arrGroups = $this->PriceListGroupManager->findAll()->fetchPairs('id','name');	    
	$this->quickFilter = ['cl_pricelist_group.name' => ['name' => 'Zvolte skupinu',
								    'values' => $arrGroups]
    ];
	
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
		parent::renderEdit($id,$copy,$modal);
		
		
    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    $form->addHidden('cl_number_series_id',NULL);	    
	    $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id','currency_name');
	    $form->addSelect('cl_currencies_id', "Měna:",$arrCurrencies)
			->setHtmlAttribute('data-placeholder','Zvolte měnu')
			->setHtmlAttribute('class','form-control chzn-select input-sm')
			->setRequired($this->translator->translate('Zřejmě jste zapoměli zvolit měnu'))
			->setPrompt($this->translator->translate('Zvolte měnu'));
	    $arrGroups = $this->PriceListGroupManager->findAll()->fetchPairs('id','name');
	    $form->addSelect('cl_pricelist_group_id', $this->translator->translate("Skupina"),$arrGroups)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte skupinu'))
			->setHtmlAttribute('class','form-control chzn-select input-sm')
			->setHtmlAttribute('data-urlajax',$this->link('GetGroupNumberSeries!'))
			->setPrompt($this->translator->translate('Zvolte skupinu'));
	    

	    $arrSupplier = $this->PartnersManager->findAll()->where('supplier = 1')->fetchPairs('id','company');
	    $form->addSelect('cl_partners_book_id', $this->translator->translate("Dodavatel"),$arrSupplier)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte dodavatele'))
			->setHtmlAttribute('class','form-control chzn-select input-sm noSelect2')
			->setPrompt($this->translator->translate('Zvolte dodavatele'));
	    
	    $form->addText('identification', $this->translator->translate('Kód'), 0, 20)
			->setRequired($this->translator->translate('Kód musí být zadán'))
			->addRule(array($this, 'validateIdentification'), $this->translator->translate('Zadejte jiný kód tento je již použit'))
			->setHtmlAttribute('data-urlString', $this->link('checkIdentification!'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Kód položky'));
	    
	    $form->addText('order_code', $this->translator->translate('Objednací kód'), 60, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Objednací kód'));
	    $form->addText('ean_code', $this->translator->translate('EAN'), 60, 128)
			->setHtmlAttribute('placeholder',$this->translator->translate('EAN'));
	    $form->addText('order_label', $this->translator->translate('Objednací název'), 60, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název'));
	    $form->addText('item_label', $this->translator->translate('Název'), 60, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název'));
	    $form->addText('unit', $this->translator->translate('Jednotky'), 0, 5)
			->setHtmlAttribute('placeholder',$this->translator->translate('Jednotky'));
	    $form->addText('quantity', $this->translator->translate('Skladem'), 0, 10)
			->setHtmlAttribute('placeholder',$this->translator->translate('Skladem'));
	    $form->addText('price_s', $this->translator->translate('Nákupní cena'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Nákupní cena'));

        $form->addText('search_tag', $this->translator->translate('Značky pro hledání'), 0, 20)
            ->setHtmlAttribute('placeholder',$this->translator->translate('hledací značky'));

	    $form->addText('height', $this->translator->translate('Výška'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Výška'));
	    $arrUnits = $this->ArraysManager->getDimUnits();
	    $form->addSelect('height_unit', "",$arrUnits)
			->setHtmlAttribute('class','form-control input-sm myInline');
	    
	    $form->addText('width', $this->translator->translate('Sířka'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Sířka'));
	    $form->addSelect('width_unit', "",$arrUnits)
			->setHtmlAttribute('class','form-control input-sm myInline');
	    
	    $form->addText('length', $this->translator->translate('Délka'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Délka'));
	    $form->addSelect('length_unit', "",$arrUnits)
			->setHtmlAttribute('class','form-control input-sm myInline');	    
	    
	    $form->addText('weight', $this->translator->translate('Váha'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Váha'));
	    $arrUnits2 = $this->ArraysManager->getWeightUnits();	    
	    $form->addSelect('weight_unit', "",$arrUnits2)
			->setHtmlAttribute('class','form-control input-sm myInline');	    	    
	    
	    $form->addText('in_package', $this->translator->translate('Počet v balení'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('V balení'));
	    $form->addText('excise_duty', $this->translator->translate('Spotřební daň'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Spotřební daň'));
	    
	    if ($this->settings->platce_dph == 1)
			$tmpText = $this->translator->translate("Cena bez DPH");
	    else
			$tmpText = $this->translator->translate("Prodejní cena");
	    
	    $form->addText('price', $tmpText.":", 0, 20)
			->setHtmlAttribute('placeholder',$tmpText);	    	    
	    $form->addText('price_vat', $this->translator->translate('Cena s DPH'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Cena s DPH'));
	    $arrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates','rates');
	    $form->addSelect('vat', $this->translator->translate("Sazba DPH:"),$arrVat)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte DPH'))
			->setHtmlAttribute('class','form-control chzn-select input-sm')
			->setPrompt($this->translator->translate('Sazba DPH'));
	    
	    $arrStorage = $this->StorageManager->getStoreTreeNotNested();
	    $form->addSelect('cl_storage_id', $this->translator->translate("Výchozí sklad"),$arrStorage)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte sklad'))
			->setHtmlAttribute('class','form-control chzn-select input-sm')
			->setPrompt($this->translator->translate('Zvolte sklad'));
	    
//		$form->addCheckbox('price_e_type', 'Prodejní cena s DPH');

        $form->addCheckbox('not_active', $this->translator->translate('Neaktivní'));

	    $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
		
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
		    /*foreach($data as $key => $one)
		    {
			    if (isset($this->dataColumns[$key]['format']) && ($this->dataColumns[$key]['format']=='number' || $this->dataColumns[$key]['format']=='currency'))
			    {
			    $data[$key] = str_replace(' ', '', $one);
			    $data[$key] = str_replace(',', '.', $data[$key]);
			    }
		    }*/	    

		    if (!empty($data->id)){
			$this->DataManager->update($data, TRUE);
		    }else{
			$this->DataManager->insert($data);
		    }
	    }
	    $this->flashMessage($this->translator->translate('Změny byly uloženy'), 'success');
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
			//dump($data->cl_number_series_id);
			if ($data2 = $this->NumberSeriesManager->getNewNumber('pricelist', $data->cl_number_series_id, NULL))
			{
			$arrData = $data2;
			}
		}

		echo(json_encode($arrData));
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
			//dump($tmpLicense);
			//die;
			if ($tmpLicense < $this->UserManager->trfPricelist($this->getUser()->id))
			{
				$result = TRUE;
			}else{
				$result = FALSE;
				$this->flashMessage( $this->translator->translate('nepovoluje_více_položek_v_ceníku'), 'danger' );
			}
		}
		
		
		return $result;
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
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte skupinu'));


	    $tmpArrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates','rates');	    
            $form->addMultiSelect('cl_rates_vat','Sazba DPH:', $tmpArrVat)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte sazbu'));
	    
	    $form->addCheckbox('on_store', $this->translator->translate('Jen položky skladem'));
	    $form->addCheckbox('print_store', $this->translator->translate('Tisknout množství'));
	
            $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		
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
		if ($form['save']->isSubmittedBy())
		{    
			$dataReport = $this->PriceListManager->findAll();
			
			if (count($data['cl_pricelist_group']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_pricelist_group_id' => $data['cl_pricelist_group']));

			}
			
			if (count($data['cl_rates_vat']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_rates_vat_id' => $data['cl_rates_vat']));
			}
			
			if ($data['on_store'] == 1)
			{
			    $dataReport = $dataReport->where('quantity > 0');
			}
//			dump($dataReport->fetchAll());	
//			die;
			
			$tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;	
			$tmpTitle = $this->translator->translate('Ceník');
			$template = $this->createTemplate()->setFile( __DIR__.'/../templates/PriceList/ReportPriceList.latte');
			$template->data = $dataReport;  
			$template->dataSettings = $data;
			$template->dataSettingsCategories = $this->PriceListGroupManager->findAll()->where(array('id' =>$data['cl_pricelist_group']))->order('name');
			$template->dataSettingsVat = $this->RatesVatManager->findAll()->where(array('id' =>$data['cl_rates_vat']))->order('rates,description');			
			$template->dataSettingsOnStore = $data['on_store'];
			$template->dataSettingsPrintStore = $data['print_store'];
			
			$template->settings = $this->settings;			
			$template->title = $tmpTitle;
			$template->author = $tmpAuthor;
			$template->today = new \Nette\Utils\DateTime;
			$this->tmpLogo();
		    $pdf = new \PdfResponse\PdfResponse($template, $this->context);

		    // Všechny tyto konfigurace jsou volitelné:
		    // Orientace stránky
		    $pdf->pageOrientation = \PdfResponse\PdfResponse::ORIENTATION_PORTRAIT;
		    // Formát stránky
		    //$pdf->pageFormat = "A4-L";
		    $pdf->pageFormat = "A4";
		    // Okraje stránky
		    //$pdf->pageMargins = "100,100,100,100,20,60";
		    $pdf->pageMargins = "5,5,5,5,20,60";
		    // Způsob zobrazení PDF
		    //$pdf->displayLayout = "continuous";
		    // Velikost zobrazení
		    $pdf->displayZoom = "fullwidth";
		    // Název dokumentu
		    $pdf->documentTitle = $tmpTitle;
		    // Dokument vytvořil:
		    $pdf->documentAuthor = $tmpAuthor;
			$pdf->outputDestination = \PdfResponse\PdfResponse::OUTPUT_DOWNLOAD;			

		    // Ignorovat styly v html (v tagu <style>?)
		    //$pdf->ignoreStylesInHTMLDocument = true;

		    // Další styly mimo HTML dokument
		    //$pdf->styles .= "p {font-size: 80%;}";

		    // Callback - těsně před odesláním výstupu do prohlížeče
		    $pdf->onBeforeComplete[] = array($this, 'pdfBeforeComplete');	

		    //$pdf->mPDF->IncludeJS("app.alert('This is alert box created by JavaScript in this PDF file!',3);");
		    //$pdf->mPDF->IncludeJS("app.alert('Now opening print dialog',1);");
		    //$pdf->mPDF->OpenPrintDialog();

		    // Ukončíme presenter -> předáme řízení PDFresponse
		    //$this->terminate($pdf);		
		    //$pdf->OpenPrintDialog();
    
		    $this->sendResponse($pdf);         	
		    //erase tmp files			
			
			
		}
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
	    }
	}
	
	
	public function getStoreQuant($arrData)
    {
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpStorageId = NULL;
        //bdump($tmpBranchId,'branchId');
        if (!is_null($tmpBranchId)) {
            $tmpStorageId = $this->CompanyBranchManager->find($tmpBranchId)->cl_storage_id;

            $arrQuant = $this->StoreManager->findAll()->select('cl_pricelist_id AS id, SUM(:cl_store_move.s_end) AS quantity2')
                ->where('cl_pricelist_id = ? AND cl_storage_id = ?', $arrData['id'], $tmpStorageId)
                ->group('cl_pricelist_id')
                ->fetch();
            $retVal = $arrQuant['quantity2'];
        }else{
            $arrPriceList = $this->StoreMoveManager->findAll()
                ->select('cl_pricelist.*,  SUM(s_end) AS quantity2,  cl_pricelist.cl_currencies.currency_name')
                ->where('cl_pricelist_id = ?', $arrData['id'])
                ->group('cl_pricelist_id')
                ->fetch();
            //                ->where('item_label LIKE ? OR ean_code LIKE ? OR identification LIKE ?', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%')
            $retVal = $arrPriceList['quantity2'];
        }

        return($retVal);
    }

	

}
