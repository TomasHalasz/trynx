<?php
namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Pohoda;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Tracy\Debugger;
use App\APIModule\Presenters;

class InvoiceAdvancePresenter extends \App\Presenters\BaseListPresenter {


    const
	    DEFAULT_STATE = 'Czech Republic';
	    
    
    public $newId = NULL;
    public $paymentModalShow = FALSE, $headerModalShow = FALSE, $footerModalShow = FALSE, $pairedDocsShow = FALSE, $createDocShow = FALSE;

    
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
    * @var \App\Model\InvoiceAdvanceManager
    */
    public $DataManager;    
    
    /**
    * @inject
    * @var \App\Model\InvoiceAdvanceManager
    */
    public $InvoiceAdvanceManager;


    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;


    /**
    * @inject
    * @var \App\Model\DocumentsManager
    */
    public $DocumentsManager;        
    
   
    /**
    * @inject
    * @var \App\Model\InvoiceAdvanceItemsManager
    */
    public $InvoiceAdvanceItemsManager;


    /**
    * @inject
    * @var \App\Model\InvoiceAdvancePaymentsManager
    */
    public $InvoiceAdvancePaymentsManager;
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;            
    
    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;        

    
    /**
    * @inject
    * @var \App\Model\PartnersBranchManager
    */
    public $PartnersBranchManager;   
    
    /**
    * @inject
    * @var \App\Model\RatesVatManager
    */
    public $RatesVatManager;

    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $PriceListManager;    

    /**
    * @inject
    * @var \App\Model\BankAccountsManager
    */
    public $BankAccountsManager;    
    
    
    /**
    * @inject
    * @var \App\Model\PriceListPartnerManager
    */
    public $PriceListPartnerManager;        

    /**
    * @inject
    * @var \App\Model\InvoiceTypesManager
    */
    public $InvoiceTypesManager;            

    /**
    * @inject
    * @var \App\Model\PaymentTypesManager
    */
    public $PaymentTypesManager;                
    


    /**
    * @inject
    * @var \App\Model\StoreDocsManager
    */
    public $StoreDocsManager;    

    
    /**
    * @inject
    * @var \App\Model\StoreMoveManager
    */
    public $StoreMoveManager;        

    /**
    * @inject
    * @var \App\Model\StoreManager
    */
    public $StoreManager;            
    
    /**
    * @inject
    * @var \App\Model\StoreOutManager
    */
    public $StoreOutManager;                
    
    /**
    * @inject
    * @var \App\Model\StorageManager
    */
    public $StorageManager;                
    
    /**
    * @inject
    * @var \App\Model\EmailingManager
    */
    public $EmailingManager;     
    
    /**
    * @inject
    * @var \App\Model\PricesManager
    */
    public $PricesManager;     

    /**
    * @inject
    * @var \App\Model\CenterManager
    */
    public $CenterManager;     
    
    /**
    * @inject
    * @var \App\Model\PairedDocsManager
    */
    public $PairedDocsManager;     
    
    /**
    * @inject
    * @var \App\Model\HeadersFootersManager
    */
    public $HeadersFootersManager;        
    
    /**
    * @inject
    * @var \App\Model\TextsManager
    */
    public $TextsManager;         
                    
    /**
    * @inject
    * @var \App\Model\PriceListBondsManager
    */
    public $PriceListBondsManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchManager
     */
    public $CompanyBranchManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchUsersManager
     */
    public $CompanyBranchUsersManager;


    /**
     * @inject
     * @var \MainServices\EETService
     */
    public $EETService;

    /**
     * @inject
     * @var \App\Model\EETManager
     */
    public $EETManager;
   
    
    protected function createComponentEditTextFooter() {
	return new Controls\EditTextControl(
        $this->translator,$this->DataManager, $this->id, 'footer_txt');
    }
    
    protected function createComponentEditTextHeader() {
	return new Controls\EditTextControl(
        $this->translator,$this->DataManager, $this->id, 'header_txt');
    }    
    protected function createComponentEditTextDescription() {
		return new Controls\EditTextControl(
            $this->translator,$this->DataManager, $this->id, 'description_txt');
    }    
    
    protected function createComponentPairedDocs() {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
	    return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }
    
    protected function createComponentTextsUse() {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
	    return new TextsUseControl($this->DataManager, $this->id, 'invoice_advance', $this->TextsManager, $this->translator);
    }    
        

    protected function createComponentSumOnDocs() {
        //$this->translator->setPrefix(['applicationModule.InvoiceAdvance']);
        if ($data = $this->DataManager->findBy(array('id' => $this->id))->fetch())
        {
            if ($data->cl_currencies)
            {
                $tmpCurrencies = $data->cl_currencies->currency_name;
            }

            if ($this->settings->platce_dph)
            {
                $tmpPriceNameBase	= $this->translator->translate("Celkem_bez_DPH");
                $tmpPriceNameVat	= $this->translator->translate("Celkem_s_DPH");
            }else{
                $tmpPriceNameBase	= $this->translator->translate("Prodej");
                $tmpPriceNameVat	= "";
            }

            $taxAdvance_base = $data->base_payed1 + $data->base_payed2 + $data->base_payed3;
            $taxAdvance_vat = $data->vat_payed1 + $data->vat_payed2 + $data->vat_payed3;
            if ($this->settings->platce_dph == 1)
            {
                $dataArr = [
                        ['name' => $this->translator->translate('Výdejní_cena'), 'value' => $data->price_s, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate("Zisk").round($data->profit,1)."%", 'value' => $data->profit_abs, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Záloha'), 'value' => $data->advance_payed, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Daňová_záloha_základ'), 'value' => $taxAdvance_base, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Daňová_záloha_daň'), 'value' => $taxAdvance_vat, 'currency' => $tmpCurrencies],
                        ['name' => 'separator'],
                        ['name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('DPH'), 'value' => $data->price_vat1 + $data->price_vat2 + $data->price_vat3, 'currency' => $this->settings->cl_currencies->currency_name],
                        ['name' => $this->translator->translate('Zaokrouhlení'), 'value' => $data->price_correction, 'currency' => $tmpCurrencies],
                        ['name' => $tmpPriceNameVat, 'value' => $data->price_e2_vat, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Zaplaceno'), 'value' => $data->price_payed, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Zbývá_k_úhradě'), 'value' => $data->price_e2_vat - $data->price_payed, 'currency' => $tmpCurrencies],
                ];
            }else{
                $dataArr = [
                        ['name' => $this->translator->translate('Výdejní_cena'), 'value' => $data->price_s, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate("Zisk").round($data->profit,1)."%", 'value' => $data->profit_abs, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Výdejní_cena'), 'value' => $data->price_s, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Záloha'), 'value' => $data->advance_payed, 'currency' => $tmpCurrencies],
                        ['name' => 'separator'],
                        ['name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Zaokrouhlení'), 'value' => $data->price_correction, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Zaplaceno'), 'value' => $data->price_payed, 'currency' => $tmpCurrencies],
                        ['name' => $this->translator->translate('Zbývá_k_úhradě'), 'value' => $data->price_e2 - $data->price_payed, 'currency' => $tmpCurrencies],
                ];
            }

            if ($this->settings->invoice_to_store == 0){

            }

        }else{
            $dataArr = [];
        }

        return new SumOnDocsControl(
            $this->translator,$this->DataManager, $this->id, $this->settings, $dataArr);
    }
    
    protected function createComponentEmail()
    {
        //$translator = clone $this->translator->setPrefix([]);
	    return new Controls\EmailControl(
            $this->translator,$this->EmailingManager, $this->mainTableName, $this->id);
    }

    protected function createComponentFiles()
     {
	    if ($this->getUser()->isLoggedIn()){
		$user_id = $this->user->getId();
		$cl_company_id = $this->settings->id;
	    }
        // $translator = clone $this->translator->setPrefix([]);
	    return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'cl_invoice_advance_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
     }   	    
    


    public function createComponentInvoicelistgrid()
     {
        //if ($this->settings->platce_dph == 1)
            //$arrData = array('item_label' => array('Popis','size' => 30),
                          //'quantity' => array('Množství','format' => 'number','size' => 10),
                          //'units' => array('','text','size' => 5),
                          //'price_e' => array('Cena bez DPH','format' => "number",'size' => 10),
                          //'price_e2' => array('Celkem bez DPH','format' => "number",'size' => 12),
                          //'vat' => array('DPH %','format' => "number",'values' => array($this->RatesVatManager->findAllValid()->fetchPairs('rates','rates')),'size' => 7),
                          //'price_e2_vat' => array('Celkem s DPH','format' => "number",'size' => 10));
        $tmpParentData = $this->DataManager->find($this->id);
            //dump($this->settings->platce_dph);
            //die;
        if ( $tmpParentData->price_e_type == 1)
        {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        }else{
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }

        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id',$userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_invoice_items__description1']))
        {
            $userTmpAdapt['cl_invoice_items__description1'] = $this->translator->translate("Poznámka_1");

        }
        if (!isset($userTmpAdapt['cl_invoice_items__description2']))
        {
            $userTmpAdapt['cl_invoice_items__description2'] = $this->translator->translate("Poznámka_2");
        }

        $arrStore = $this->StorageManager->getStoreTreeNotNested();
        //bdump($this['invoicelistgrid'],"test");
        //$arrStore = $this->StorageManager->getStoreTreeNotNestedAmount($this['invoicelistgrid']["editLine"]["cl_pricelist_id"]->value);
        if ($this->settings->platce_dph == 1)
        {
            //'values' => $arrStore,
            //'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($this["editLine"]["cl_pricelist_id"]->value);',
                            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'),'format' => 'text','size' => 10,'readonly' => TRUE,'e100p' => "false"],
                                'item_label' => [$this->translator->translate('Popis'),'format' => 'textarea','size' => 20, 'colspan' => 8, 'rows' => 1,
                         'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                         'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'),'format' => 'hidden'],
                         'cl_storage.name' => [$this->translator->translate('Sklad'),'format' => 'chzn-select-req', 'show_type' => 1,
                                         'values' => $arrStore,'e100p' => "false",'newTrColSpan' => 3,
                                         'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($defData1["cl_pricelist_id"]);',
                                         'size' => 8, 'roCondition' => '$defData["changed"] != NULL || ($this["editLine"]["cl_pricelist_id"]->value == 0)'],
                          'quantity' => [$this->translator->translate('Množství'),'format' => 'number', 'show_type' => 1, 'size' => 8,'decplaces' => $this->settings->des_mj],
                          'units' => ['','format' => 'text', 'show_type' => 1, 'size' => 7,'e100p' => "false"],
                          'price_s' => [$this->translator->translate('Skladová_cena'),'format' => "number", 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE,'e100p' => "false"],
                          'price_e' => [$tmpProdej,'format' => "number", 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_cena],
                          'price_e_type' => [$this->translator->translate('Typ prodejni ceny'),'format' => "hidden"],
                          'discount' => [$this->translator->translate('Sleva_%'),'format' => "number", 'show_type' => 1, 'size' => 8],
                          'profit' => [$this->translator->translate('Zisk_%'),'format' => "number", 'show_type' => 1, 'size' => 8, 'readonly' => TRUE,'e100p' => "false"],
                          'price_e2' => [$this->translator->translate('Celkem_bez_DPH'),'format' => "number",'size' => 8,'e100p' => "false"],
                          'vat' => [$this->translator->translate('DPH_%'),'format' => "chzn-select",'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates','rates'),'size' => 6,'e100p' => "false"],
                          'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'),'format' => "number",'size' => 8,'e100p' => "false"],
						  'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_invoice_advance.cl_currencies_id','cl_pricelist.price', 'cl_invoice_advance.cl_partners_book_id']],
                          'description1' => [$userTmpAdapt['cl_invoice_items__description1'],'format' => "text",'size' => 50,'newline' => TRUE,'e100p' => "false"],
                          'description2' => [$userTmpAdapt['cl_invoice_items__description2'],'format' => "text",'size' => 50,'newline' => TRUE,'e100p' => "false"]];
        }else{
                            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'),'format' => 'text','size' => 10,'readonly' => TRUE,'e100p' => "false"],
                                            'item_label' => [$this->translator->translate('Popis'),'format' => 'textarea', 'size' => 20, 'colspan' => 6, 'rows' => 1],
                                            'cl_storage.name' => [$this->translator->translate('Sklad'),'format' => 'chzn-select-req', 'show_type' => 1,
                                                'values' => $arrStore,'e100p' => "false",'newTrColSpan' => 3,
                                         'size' => 8, 'roCondition' => '$defData["changed"] != NULL || ($this["editLine"]["cl_pricelist_id"]->value == 0)'],
                          'quantity' => [$this->translator->translate('Množství'),'format' => 'number', 'show_type' => 1, 'size' => 8,'decplaces' => $this->settings->des_mj],
                          'units' => ['','format' => 'text', 'show_type' => 1, 'size' => 7,'e100p' => "false"],
                          'price_s' => [$this->translator->translate('Skladová_cena'),'format' => "number", 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE,'e100p' => "false"],
                          'price_e' => [$this->translator->translate('Prodej'),'format' => "number", 'show_type' => 1, 'size' => 8,'decplaces' => $this->settings->des_cena],
                          'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'),'format' => "hidden"],
                          'discount' => [$this->translator->translate('Sleva_%'),'format' => "number", 'show_type' => 1, 'size' => 8],
                          'profit' => [$this->translator->translate('Zisk_%'),'format' => "number", 'show_type' => 1, 'size' => 8, 'readonly' => TRUE,'e100p' => "false"],
                          'price_e2' => [$this->translator->translate('Celkem'),'format' => "number",'size' => 8,'e100p' => "false"],
                          'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
						   'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_invoice_advance.cl_currencies_id','cl_pricelist.price', 'cl_invoice_advance.cl_partners_book_id']],
                          'description1' => [$userTmpAdapt['cl_invoice_items__description1'],'format' => "text",'size' => 50,'newline' => TRUE,'e100p' => "false"],
                          'description2' => [$userTmpAdapt['cl_invoice_items__description2'],'format' => "text",'size' => 50,'newline' => TRUE,'e100p' => "false"]];

        }
        if ($this->settings->invoice_to_store == 0){
            unset($arrData['cl_storage.name']);
            unset($arrData['price_s']);
            unset($arrData['profit']);
        }


        $control =  new Controls\ListgridControl(
                        $this->translator,
                        $this->InvoiceAdvanceItemsManager,
                        $arrData,
                        [],
                        $this->id,
                        ['units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba],
                        $this->DataManager,
                        $this->PriceListManager,
                        $this->PriceListPartnerManager,
                        TRUE,
                        ['pricelist2' => $this->link('RedrawPriceList2!'),
                          'duedate' => $this->link('RedrawDueDate2!')
                        ], //custom links
                        TRUE, //movable row
                        NULL, //ordercolumn
                        FALSE, //selectmode
                        [], //quicksearch
                        "", //fontsize
                        FALSE, //parentcolumnname
                        TRUE //pricelistbottom
                        );
        $control->setContainerHeight("auto");

         $control->setShowType([1 => ['label' => 'text a cena', 'title' => 'vloží nový řádek bez kusů, jen s polem pro text a cenu']]);

        $control->onChange[] = function ()
            {
            $this->updateSum();

            };
        return $control;

     }       
     
  protected function createComponentPaymentListGrid()
     {
        $tmpParentData = $this->DataManager->find($this->id);
            //	dump($this->id);
            //die;
        $tmpPrice = 0;
        if ($tmpParentData){
            $tmpCurrenciesId = $tmpParentData->cl_currencies_id;
        }else{
            $tmpCurrenciesId = NULL;
        }
        //$this->translator->setPrefix(['applicationModule.InvoiceAdvance']);
        if ($this->settings->platce_dph == 1)
        {
            $arrData = ['pay_price' => [$this->translator->translate('Částka'),'format' => 'currency','size' => 10],
                     'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text', 'size' => 10, 'values' => $this->CurrenciesManager->findAllTotal()->fetchPairs('id','currency_name')],
                     'pay_doc' => [$this->translator->translate('Doklad'),'format' => 'text','size' => 20],
                     'cl_cash.cash_number' => [$this->translator->translate('Pokladna'), 'format' => 'url',  'size' => 12, 'url' => 'cash', 'value_url' => 'cl_cash_id'],
                     'cl_payment_types.name' => [$this->translator->translate('Typ_platby'), 'format' => 'text', 'size' => 10, 'values' => $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name')],
                     'pay_type' => [$this->translator->translate('Druh_úhrady'),'format' => 'text','size' => 10, 'values' => ['0' => $this->translator->translate('běžná_úhrada'), '1' => $this->translator->translate('záloha')]],
                     'pay_vat' => [$this->translator->translate('Daňová_záloha'),'format' => 'text','size' => 10, 'values' => ['0' => 'ne', '1' => 'ano']],
                     'vat' => [$this->translator->translate('DPH_%'),'format' => "number",'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates','rates'),'size' => 7],
                     'pay_date' => [$this->translator->translate('Datum_platby'), 'format' => 'date', 'size' => 10]
            ];
            if ($tmpParentData){
                $tmpPrice = $tmpParentData->price_e2_vat;
            }
        }
        else
        {
            $arrData = ['pay_price' => [$this->translator->translate('Částka'),'format' => 'currency','size' => 10],
                     'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text', 'size' => 10, 'values' => $this->CurrenciesManager->findAllTotal()->fetchPairs('id','currency_name')],
                     'pay_doc' => [$this->translator->translate('Doklad'),'format' => 'text','size' => 20],
                     'cl_cash.cash_number' => [$this->translator->translate('Pokladna'), 'format' => 'url',  'size' => 12, 'url' => 'cash', 'value_url' => 'cl_cash_id'],
                     'cl_payment_types.name' => [$this->translator->translate('Typ platby'), 'format' => 'text', 'size' => 10, 'values' => $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name')],
                     'pay_type' => [$this->translator->translate('Druh_úhrady'),'format' => 'text','size' => 10, 'values' => ['0' => $this->translator->translate('běžná_úhrada'), '1' => $this->translator->translate('záloha')]],
                     'pay_vat' => [$this->translator->translate('Daňová_záloha'),'format' => 'text','size' => 15, 'values' => ['0' => 'ne', '1' => 'ano']],
                     'pay_date' => [$this->translator->translate('Datum_platby'), 'format' => 'date', 'size' => 15]
            ];
            if ($tmpParentData){
                $tmpPrice = $tmpParentData->price_e2;
            }
        }

        $control = new Controls\ListgridControl(
                         $this->translator,
                        $this->InvoiceAdvancePaymentsManager,
                        $arrData,
                        [],
                        $this->id,
                        ['pay_date' => new \Nette\Utils\DateTime, 'cl_currencies_id' => $tmpCurrenciesId,
                            'vat' => $this->settings->def_sazba, 'pay_price' => $tmpPrice],
                        $this->DataManager,
                        NULL,
                        NULL,
                        TRUE,
                        [], //custom links
                        TRUE, //movable row
                        FALSE, //ordercolumn
                        FALSE, //selectmode
                        [], //quicksearch
                        "", //fontsize
                        FALSE, //parentcolumnname
                        FALSE, //pricelistbottom
                        FALSE, //readonly
                        FALSE, //nodelete
                        FALSE, //enablesearch
                        '', //txtsearch
                        NULL, //toolbar
                        TRUE, //forceEnable
                        TRUE, //paginatorOff
                         [], //colours
                        20 //pagelength
                        );

        $control->onChange[] = function ()
            {
            $this->updatePaymentSum();
            };

        return $control;
	
     }

    public function updatePaymentSum(){
        $this->DataManager->paymentUpdate($this->id);
        if (isset($this['sumOnDocs'])) {
            $this['sumOnDocs']->redrawControl('sumOnDocsSnp');
        }
        if (isset($this['pairedDocs'])) {
            $this['pairedDocs']->redrawControl('docs');
        }
    }

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.InvoiceAdvance']);
        $this->formName = $this->translator->translate("Zálohové_faktury");
        $this->mainTableName = 'cl_invoice_advance';
        //$settings = $this->CompaniesManager->getTable()->fetch();
        if ($this->settings->platce_dph == 1)
        {
            $arrData = ['inv_number' => [$this->translator->translate('Císlo_dokladu'), 'format' => 'text'],
                        'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean', 'style' => 'glyphicon glyphicon-lock'],
                        'storno' => [$this->translator->translate('Storno'), 'format' => 'boolean'],
                        'cl_status.status_name' => [$this->translator->translate('Stav'),'format' => 'colortag'],
                        'cl_invoice_types.name' => [$this->translator->translate('Druh_dokladu'), 'format' => 'text'],
                        'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                        'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                        'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'show_clink' => true],
                        'cl_partners_branch.b_name' => [$this->translator->translate('Odběratel'), 'format' => 'text'],
                        'cl_payment_types.name' => [$this->translator->translate('Forma_úhrady'), 'format' => 'text'],
                        'due_date' => [$this->translator->translate('Splatnost'), 'format' => 'date'],
                        'pay_date' => [$this->translator->translate('Dat_úhrady'), 'format' => 'date'],
                        'inv_title' => $this->translator->translate('Popis'), 's_eml' => ['E-mail', 'format' => 'boolean'],
                        'price_e2' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                        'price_e2_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                        'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                        'price_s' => [$this->translator->translate('Výdejní_cena'), 'format' => 'currency'],
                        'profit' => [$this->translator->translate('Zisk_%'), 'format' => 'currency'],
                        'profit_abs' => [$this->translator->translate('Zisk_abs.'), 'format' => 'currency'],
                        'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text'],
                        'currency_rate' => [$this->translator->translate('Kurz'), 'format' => 'text'],
                        'export' => [$this->translator->translate('Export'), 'format' => 'boolean'],
                        'pdp' => [$this->translator->translate('PDP'), 'format' => 'boolean'],
                        'var_symb' => [$this->translator->translate('Var._symbol'), 'format' => 'text'],
                        'spec_symb' => [$this->translator->translate('Spec._symbol'), 'format' => 'text'],
                        'konst_symb' => [$this->translator->translate('Konst._symbol'), 'format' => 'text'],
                        'cm_number' => [$this->translator->translate('Zakázka'), 'format' => 'text'],
                        'cl_commission.cm_number' => [$this->translator->translate('Spárovaná_zakázka'), 'format' => 'text'],
                        'cl_store_docs.doc_number' => [$this->translator->translate('Císlo_výdejky'), 'format' => 'text'],
                        'delivery_number' => [$this->translator->translate('Dodací_listy'), 'format' => 'text'],
                        'cl_users.name' => [$this->translator->translate('Obchodník'), 'format' => 'text'],
                        'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'],
                        'create_by' => [$this->translator->translate('Vytvořil'), 'format' => 'text'],
                        'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'],
                        'change_by' => [$this->translator->translate('Změnil'), 'format' => 'text']];
        }
        else
        {
            $arrData = ['inv_number' => [$this->translator->translate('Císlo_dokladu'), 'format' => 'text'],
                        'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean', 'style' => 'glyphicon glyphicon-lock'],
                        'storno' => [$this->translator->translate('Storno'), 'format' => 'boolean'],
                        'cl_status.status_name' => [$this->translator->translate('Stav'),'format' => 'colortag'],
                        'cl_invoice_types.name' => [$this->translator->translate('Druh_dokladu'), 'format' => 'text'],
                        'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                        'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                        'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'show_clink' => true],
                        'cl_partners_branch.b_name' => $this->translator->translate('Pobočka'),
                        'cl_payment_types.name' => [$this->translator->translate('Forma úhrady'), 'format' => 'text'],
                        'due_date' => [$this->translator->translate('Splatnost'), 'format' => 'date'],
                        'pay_date' => [$this->translator->translate('Dat_úhrady'), 'format' => 'date'],
                        'inv_title' => [$this->translator->translate('Popis'), 'format' => 'text'],
                        's_eml' => ['E-mail', 'format' => 'boolean'],
                        'price_e2' => [$this->translator->translate('Cena_celkem'), 'format' => 'currency'],
                        'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                        'price_s' => [$this->translator->translate('Výdejní_cena'), 'format' => 'currency'],
                        'profit' => [$this->translator->translate('Zisk_%'), 'format' => 'currency'],
                        'profit_abs' => [$this->translator->translate('Zisk_abs.'), 'format' => 'currency'],
                        'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text'],
                        'currency_rate' => [$this->translator->translate('Kurz'), 'format' => 'text'],
                        'export' => [$this->translator->translate('Export'), 'format' => 'boolean'],
                        'pdp' => [$this->translator->translate('PDP'), 'format' => 'boolean'],
                        'var_symb' => [$this->translator->translate('Var._symbol'), 'format' => 'text'],
                        'spec_symb' => [$this->translator->translate('Spec._symbol'), 'format' => 'text'],
                        'konst_symb' => [$this->translator->translate('Konst._symbol'), 'format' => 'text'],
                        'cm_number' => [$this->translator->translate('Zakázka'), 'format' => 'text'],
                        'cl_commission.cm_number' => [$this->translator->translate('Spárovaná_zakázka'), 'format' => 'text'],
                        'cl_store_docs.doc_number' => [$this->translator->translate('Císlo_výdejky'), 'format' => 'text'],
                        'delivery_number' => [$this->translator->translate('Dodací_listy'), 'format' => 'text'],
                        'cl_users.name' => [$this->translator->translate('Obchodník'), 'format' => 'text'],
                        'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'],
                        'create_by' => [$this->translator->translate('Vytvořil'), 'format' => 'text'],
                        'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'],
                        'change_by' => [$this->translator->translate('Změnil'), 'format' => 'text']];
        }
        if ($this->settings->invoice_to_store == 0){
            unset($arrData['price_s']);
            unset($arrData['profit_abs']);
            unset($arrData['profit']);
        }
        if (!$this->settings->eet_active && !$this->settings->eet_test)
        {
            unset($arrData['cl_eet.eet_status']);
        }

        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = ['inv_number' => '' , 'cl_partners_book.company' => 'autocomplete', 'cl_invoice_types.name' => 'autocomplete',
                        'cl_invoice_advance.var_symb' => 'autocomplete', 'cl_status.status_name' => 'autocomplete',
                        'inv_title' => '' , 'cl_users.name' => 'autocomplete', 'inv_date' => 'none', 'cl_payment_types.name' => 'autocomplete',
                        'pay_date' =>'', 'due_date' => '', 'cl_partners_branch.b_name' => 'autocomplete',
                        'cl_currencies.currency_name' => 'autocomplete',
                        'price_e2' => '', 'price_e2_vat' => '', 'price_payed' => '', 'cl_commission.cm_number' => 'autocomplete', 'cl_store_docs.doc_number' => 'autocomplete'];
        $this->DefSort = 'inv_date DESC';

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['inv_number', 'var_symb', 'cl_partners_book.company', 'inv_title', 'price_e2', 'price_e2_vat'];

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_invoice_advance_items.item_label', ':cl_invoice_advance_items.cl_pricelist.identification', ':cl_invoice_advance_items.cl_pricelist.item_label'];

        /*$testDate = new \Nette\Utils\DateTime;
        $testDate = $testDate->modify('-1 day');
        $this->conditionRows = array( array('due_date','<=',$testDate, 'color:red', 'lastcond'), array('price_payed','<=','price_e2_vat', 'color:green'));
         *
         */
        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);

        $this->conditionRows = [['due_date','<',$testDate, 'color:red', 'notlastcond'],
                          ['pay_date','==',NULL, 'color:red', 'lastcond'],
                          ['due_date','>=',$testDate, 'color:green', 'notlastcond'],
                          ['pay_date','==',NULL, 'color:green', 'lastcond']];


        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
    //		$currencyRate = 1;
        if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ? AND inv_type != ?',1,4)->fetch())
        {
            $tmpInvoiceType = $tmpInvoiceType->id;
        }
        else {
            $tmpInvoiceType = NULL;
        }
        $defDueDate = new \Nette\Utils\DateTime;
        if (!is_null($this->settings->cl_payment_types_id) && $this->settings->cl_payment_types->payment_type == 0) {
            $defDueDate->modify('+' . $this->settings->due_date . ' day');
        }

        //07.07.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;
        //$tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        $tmpNSAdvance = 'invoice_advance';
        if ($tmpBranch) {
                $tmpNSAdvanceId = $tmpBranch->cl_number_series_id_advance;
                $tmpCenterId = $tmpBranch->cl_center_id;
            }else{
                //20.12.2018 - headers and footers
                //if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $data->cl_number_series_id))->fetch()){
                //    $arrUpdate['header_txt'] = $hfData['header_txt'];
                //    $arrUpdate['footer_txt'] = $hfData['footer_txt'];
                //}

                $tmpNSAdvanceId = NULL;
                $tmpCenterId = NULL;
            }

        $this->defValues = ['inv_date' => new \Nette\Utils\DateTime,
                     'due_date' => $defDueDate,
                     'cl_company_branch_id'  => $tmpBranchId,
                     'cl_center_id' => $tmpCenterId,
                     'cl_currencies_id' =>  $this->settings->cl_currencies_id ,
                     'currency_rate' => $this->settings->cl_currencies->fix_rate,
                     'konst_symb' => $this->settings->konst_symb,
                     'cl_invoice_types_id' => $tmpInvoiceType,
                     'cl_payment_types_id' => $this->settings->cl_payment_types_id,
                     'header_show'  => $this->settings->header_show,
                     'footer_show'  => $this->settings->footer_show,
                     'header_txt'   => $this->settings->header_txt,
                     'footer_txt'   => $this->settings->footer_txt,
                     'vat_active'   => $this->settings->platce_dph,
                     'price_e_type' => $this->settings->price_e_type,
                     'cl_users_id' => $this->user->getId()];
        //$this->numberSeries = 'commission';
        $this->numberSeries = ['use' => 'invoice_advance', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number'];
        $this->readOnly = ['inv_number' => TRUE,
                    'created' => TRUE,
                    'create_by' => TRUE,
                    'changed' => TRUE,
                    'change_by' => TRUE];
    //	$this->toolbar = array(	1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));


            /*$this->toolbar = array(	0 => array('group_start' => ''),
                1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový dodací list', 'class' => 'btn btn-primary'),
                2 => $this->getNumberSeriesArray('delivery_note'),
                3 => array('group_end' => ''));*/
            //array('data' => $form_use, 'defData' => array('cl_number_series_id' => $nsOne->id))

            $this->toolbar = [
                                    0 => ['group_start' => ''],
                                    1 => ['url' => $this->link('new!', ['data'=> $tmpNSAdvance, 'defData' => json_encode(['cl_number_series_id' => $tmpNSAdvanceId])]), 'rightsFor' => 'write', 'label' => $this->translator->translate('Záloha'), 'title' => $this->translator->translate('nová_zálohová_faktura'), 'class' => 'btn btn-primary'],
                                    2 => $this->getNumberSeriesArray('invoice_advance'),
                                    3 => ['group_end' => ''],
                                    'export' => ['group' =>
                                                            [0 => ['url' => $this->link('report!', ['index' => 2]),
                                                                'rightsFor' => 'report',
                                                                'label' => $this->translator->translate('Pohoda_XML'),
                                                                'title' => $this->translator->translate('Faktury_vystavené_ve_zvoleném_období'),
                                                                'data' => ['data-ajax="true"', 'data-history="false"'],
                                                                'class' => 'ajax', 'icon' => 'iconfa-file'],
                                                            ],
                                                            'group_settings' =>
                                                                ['group_label' => $this->translator->translate('Export'),
                                                                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                                                    'group_title' =>  $this->translator->translate('tisk'), 'group_icon' => 'iconfa-file-export']
                                    ],
                                    4 => ['url' => $this->link('ImportData:', ['modal'=> $this->modal, 'target' => $this->name]), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary'],
                                    5 => ['group' =>
                                                    [0 => ['url' => $this->link('report!', ['index' => 1]),
                                                            'rightsFor' => 'report',
                                                            'label' => $this->translator->translate('Kniha_faktur_vydaných'),
                                                            'title' => $this->translator->translate('Faktury_vystavené_ve_zvoleném_období'),
                                                            'data' => ['data-ajax="true"', 'data-history="false"'],
                                                            'class' => 'ajax', 'icon' => 'iconfa-print'],
                                                    ],
                                                    'group_settings' =>
                                                        ['group_label' => $this->translator->translate('Tisk'),
                                                            'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                                            'group_title' =>  $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
                                    ]
            ];

            $this->report = [1 => ['reportLatte' => __DIR__.'/../templates/InvoiceAdvance/ReportInvoiceBookSettings.latte',
                                                                                            'reportName' => $this->translator->translate('Kniha_faktur_vydaných')],
                                    2 => ['reportLatte' => __DIR__.'/../templates/InvoiceAdvance/ExportInvoiceBookSettings.latte',
                                        'reportName' => 'Pohoda XML export']];

        $this->rowFunctions = ['copy' => 'enabled'];

        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath().'InvoiceAdvance\card.latte'],
                    'items' => ['active' => true, 'name' => $this->translator->translate('položky_prodej'), 'lattefile' => $this->getLattePath().'InvoiceAdvance\items.latte'],
                    'header' => ['active' => false, 'name' => $this->translator->translate('záhlaví'), 'lattefile' => $this->getLattePath().'InvoiceAdvance\header.latte'],
                    'assignment' => ['active' => false, 'name' => $this->translator->translate('zápatí'), 'lattefile' =>  $this->getLattePath().'InvoiceAdvance\footer.latte'],
                    'memos' => ['active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath().'InvoiceAdvance\description.latte'],
                    'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath().'InvoiceAdvance\files.latte']
        ];



        //17.08.2018 - settings for sending doc by email
        $this->docEmail	    = ['template' => __DIR__ .'/../templates/InvoiceAdvance/emailInvoice.latte',
            'emailing_text' => 'invoice_advance'];
        /*19.02.2023 - more templates to select by user*/
        $tmpEmailTexts = $this->getEmailingTexts($this->docEmail['emailing_text']);
        if (count($tmpEmailTexts) > 1) {
            $arrEmlTxtParts = [];
            foreach($tmpEmailTexts as $key => $one){
                $arrEmlTxtParts[$key] = ['url' => 'sendDoc!',
                    'urlparams' => ['keyname' => 'emailingTextIndex', 'value' => $key],  'rightsFor' => 'read',
                    'label' => $one,
                    'class' => 'ajax',
                    'icon' => ''];
            }
            $arrEmlTxt =
                ['group' => $arrEmlTxtParts,
                    'group_settings' =>
                        ['group_label' => $this->translator->translate('e-mail'),
                            'group_title' => $this->translator->translate('Odešle_dokument_emailem'),
                            'group_class' => 'btn btn-success dropdown-toggle btn-sm pull-right',
                            'group_icon' => 'glyphicon glyphicon-send']
                ];
        }else{
            $arrEmlTxt = ['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => 'e-mail', 'title' => $this->translator->translate('odešle_doklad_emailem'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'cl_partners_book_id', 'condition' => '!=', 'value' => NULL]],
                'icon' => 'glyphicon glyphicon-send'];
        }


        $this->bscSums = ['lattefile' => $this->getLattePath().'Invoice\sums.latte'];
        $this->bscToolbar = [

                      1 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('casté_texty'), 'class' => 'btn btn-success showTextsUse',
                                'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'],'icon' => 'glyphicon glyphicon-list'],
                      2 => ['url' => 'makeInvoice!',  'urlparams' => ['keyname' => 'type', 'value' => 1, 'key' => null], 'rightsFor' => 'write', 'label' => $this->translator->translate('daňová_faktura'), 'title' => $this->translator->translate('Vytvoří_ze_zálohové_faktury_daňový_doklad'), 'class' => 'btn btn-success',
                                'data' => ['data-ajax="true"', 'data-history="true"'],'icon' => 'glyphicon glyphicon-edit'],
                      3 => ['url' => 'makeInvoice!',  'urlparams' => ['keyname' => 'type', 'value' => 2, 'key' => null], 'rightsFor' => 'write', 'label' => $this->translator->translate('konečná_faktura'), 'title' => $this->translator->translate('Vytvoří_ze_zálohové_faktury_konečnou_fakturu'), 'class' => 'btn btn-success',
                                'data' => ['data-ajax="true"', 'data-history="true"'],'icon' => 'glyphicon glyphicon-edit'],
                      4 => ['url' => 'paymentShow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('úhrady_a_zálohy'), 'class' => 'btn btn-success',
                                'data' => ['data-ajax="true"', 'data-history="false"'],'icon' => 'glyphicon glyphicon-edit'],
                      5 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                                'data' => ['data-ajax="true"', 'data-history="false"'],'icon' => 'glyphicon glyphicon-list-alt'],
                      6 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                                  'data' => ['data-ajax="true"', 'data-history="false"'],'icon' => 'glyphicon glyphicon-print'],
                      7 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                            'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
                      8 => $arrEmlTxt
        ];
        //19.02.2023
        //['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => $this->translator->translate('E-mail'), 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send']

        $this->bscTitle = ['inv_number' => $this->translator->translate('Císlo zálohy'), 'cl_partners_book.company' => $this->translator->translate('Odběratel')];

        if (!$this->settings->eet_active && !$this->settings->eet_test)
        {
            //unset($this->bscToolbar[1]);
            //unset($this->bscToolbar[2]);
        }


        //17.08.2018 - settings for documents saving and emailing
        $this->docTemplate[1]  =  $this->ReportManager->getReport(__DIR__ . '/../templates/InvoiceAdvance/advancev1.latte');

        $this->docAuthor    = $this->user->getIdentity()->name . " z " . $this->settings->name;
        //$this->docTitle[1]	    = array("Faktura ", "inv_number");
        /*$this->docTitle[1]	    = array("", "cl_partners_book.company", "inv_number");
        $this->docTitle[2]	    = array("", "cl_partners_book.company", "inv_number");
        $this->docTitle[3]	    = array("", "cl_partners_book.company", "inv_number");*/

        if ($this->settings['pdf_name_type'] == 0 ) {
            $this->docTitle[1] = ["", "cl_partners_book.company", "var_symb"];
            $this->docTitle[2] = ["", "cl_partners_book.company", "var_symb"];
            $this->docTitle[3] = ["", "cl_partners_book.company", "var_symb"];
        }elseif ($this->settings['pdf_name_type'] == 1 ){
            $this->docTitle[1] = ["", "var_symb", "cl_partners_book.company"];
            $this->docTitle[2] = ["", "var_symb", "cl_partners_book.company"];
            $this->docTitle[3] = ["", "var_symb", "cl_partners_book.company"];
        }


        //$this->docTitle[2]	    = array("Opravný doklad ", "inv_number");
        //$this->docTitle[3]	    = array("Záloha ", "inv_number");


        $this->quickFilter = ['cl_invoice_types.name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
                                        'values' =>  $this->InvoiceTypesManager->findAll()->where('inv_type IN ?', [1,2,3])->fetchPairs('id','name')]
        ];
        /*predefined filters*/
        $this->pdFilter = [0 => ['url' => $this->link('pdFilter!', ['index' => 0, 'pdFilterIndex' => 0]),
                                            'filter' => '(price_payed < price_e2_vat OR price_payed < price_e2) AND due_date <= NOW()',
                                            'rightsFor' => 'read',
                                            'label' => $this->translator->translate('po_splatnosti'),
                                            'title' => $this->translator->translate('Nezaplacené_faktury_po_splatnosti'),
                                            'data' => ['data-ajax="true"', 'data-history="true"'],
                                            'class' => 'ajax', 'icon' => 'iconfa-filter'],
                                1 => ['url' => $this->link('pdFilter!', ['index' => 1, 'pdFilterIndex' => 1]),
                                            'filter' => '(price_payed < price_e2_vat OR price_payed < price_e2)',
                                            'rightsFor' => 'read',
                                            'label' => $this->translator->translate('nezaplacené'),
                                            'title' => $this->translator->translate('Všechny_doposud_nezaplacené_faktury'),
                                            'data' => ['data-ajax="true"', 'data-history="true"'],
                                            'class' => 'ajax', 'icon' => 'iconfa-filter']
        ];

        if ( $this->isAllowed($this->presenter->name,'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
        }
        $this->groupActions['reminder'] = 'odeslat upomínky';
    }

    public function forceRO($data)
    {
        $ret = parent::forceRO($data);
            if (!is_null($data['cl_eet_id']) && $data->cl_eet->eet_status == 3) {
                $ret = $ret || TRUE;
            } else {
                $ret = $ret || FALSE;
            }

        return $ret;
    }



    public function groupActionsMethod($data, $checked, $totalR)
    {
        parent::groupActionsMethod($data, $checked, $totalR);
        if ($data['action'] == 'reminder') {
            $this->groupActionReminder($data, $checked, $totalR, 'areminder');
        }
    }






    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
            parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

            $tmpInvoice = $this->dataForSums;
            if ($this->settings->platce_dph == 1)
            {
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount = $tmpInvoiceC->where('storno = 0 AND cl_invoice_advance.due_date <= NOW() AND ABS(price_payed) < ABS(price_e2_vat-advance_payed)')->sum('(price_e2_vat-price_payed-advance_payed)*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount2 = $tmpInvoiceC->where('storno = 0 AND ABS(price_payed) < ABS(price_e2_vat-advance_payed)')->sum('(price_e2_vat-price_payed-advance_payed)*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount3 = $tmpInvoiceC->
                                    where('storno = 0 AND MONTH(inv_date) = MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(inv_date) = YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))')->
                                    sum('price_e2_vat*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount4 = $tmpInvoiceC->
                                    where('storno = 0 AND MONTH(inv_date) = MONTH(NOW()) AND YEAR(inv_date) = YEAR(NOW())')->
                                    sum('price_e2_vat*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount5 = $tmpInvoiceC->
                                    where('storno = 0 AND cl_invoice_advance.due_date <= DATE_SUB(NOW(),INTERVAL 7 DAY) AND ABS(price_payed) < ABS(price_e2_vat-advance_payed)')->
                                    sum('(price_e2_vat-price_payed-advance_payed)*currency_rate');                
            }else{
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount = $tmpInvoiceC->where('storno = 0 AND  cl_invoice_advance.due_date <= NOW() AND ABS(price_payed) < ABS(price_e2-advance_payed)')->sum('(price_e2-price_payed-advance_payed)*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount2 = $tmpInvoiceC->where('storno = 0 AND ABS(price_payed) < ABS(price_e2-advance_payed)')->sum('(price_e2-price_payed-advance_payed)*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount3 = $tmpInvoiceC->
                                    where('storno = 0 AND MONTH(inv_date) = MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(inv_date) = YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))')->
                                    sum('price_e2*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount4 = $tmpInvoiceC->
                                    where('storno = 0 AND MONTH(inv_date) = MONTH(NOW()) AND YEAR(inv_date) = YEAR(NOW())')->
                                    sum('price_e2*currency_rate');
                $tmpInvoiceC = clone $tmpInvoice;
                $tmpAmount5 = $tmpInvoiceC->
                                    where('storno = 0 AND  cl_invoice_advance.due_date <= DATE_SUB(NOW(),INTERVAL 7 DAY) AND ABS(price_payed) < ABS(price_e2-advance_payed)')->
                                    sum('(price_e2-price_payed-advance_payed)*currency_rate');                                
            }
	    if ($this->user->getIdentity()->quick_sums){
		$this->template->headerText = [0 => [$this->translator->translate('Dnes_po_splatnosti'), $tmpAmount, $this->settings->cl_currencies->currency_name, 0, 'label-warning', 'rightsFor' => 'report'],
						    1 => [$this->translator->translate('Týden_a_více_nezaplaceno'), $tmpAmount5, $this->settings->cl_currencies->currency_name, 0, 'label-danger', 'rightsFor' => 'report'],
						    2 => [$this->translator->translate('Celkem_nezaplaceno'), $tmpAmount2, $this->settings->cl_currencies->currency_name, 0, 'label-warning', 'rightsFor' => 'report'],
						    3 => [$this->translator->translate('Obrat_v_tomto_měsíci'), $tmpAmount4, $this->settings->cl_currencies->currency_name, 0, 'label-success', 'rightsFor' => 'report'],
						    4 => [$this->translator->translate('Obrat_v_minulém_měsíci'), $tmpAmount3, $this->settings->cl_currencies->currency_name, 0, 'label-success', 'rightsFor' => 'report']];
	    }


	
    }

    public function renderEdit($id, $copy, $modal){
		parent::renderEdit($id, $copy, $modal);
    }

    protected function getArrInvoiceVat(){
		$tmpData = $this->DataManager->find($this->id);
		$arrRatesVatValid = $this->RatesVatManager->findAllValid($tmpData->inv_date);
		$arrInvoiceVat = [];
		foreach($arrRatesVatValid as $key => $one)
		{
			if ($tmpData->vat1 == $one['rates'])
			{
				$baseValue = $tmpData->price_base1;
				$vatValue =  $tmpData->price_vat1;
				$basePayedValue =  $tmpData->base_payed1;
				$vatPayedValue =  $tmpData->vat_payed1;
                $correctionBase = $tmpData->correction_base1;
			}elseif ($tmpData->vat2 == $one['rates']){
				$baseValue = $tmpData->price_base2;
				$vatValue =  $tmpData->price_vat2;		
				$basePayedValue =  $tmpData->base_payed2;		
				$vatPayedValue =  $tmpData->vat_payed2;
                $correctionBase = $tmpData->correction_base2;
			}elseif ($tmpData->vat3 == $one['rates']){
				$baseValue = $tmpData->price_base3;
				$vatValue =  $tmpData->price_vat3;		
				$basePayedValue =  $tmpData->base_payed3;		
				$vatPayedValue =  $tmpData->vat_payed3;
                $correctionBase = $tmpData->correction_base3;
            }elseif ($one['rates'] == 0){
                $baseValue = $tmpData->price_base0;
                $vatValue =  0;
                $basePayedValue =  $tmpData->base_payed0;
                $vatPayedValue =  0;
                $correctionBase = $tmpData->correction_base0;
			}  else {

				$baseValue = 0;
				$vatValue =  0;			
				$basePayedValue =  0;		
				$vatPayedValue = 0;
                $correctionBase = 0;
			}

			$arrInvoiceVat[$one['rates']] = ['base' => $baseValue,
							  'vat' => $vatValue,
							  'correction' => $correctionBase,
							  'payed' => $basePayedValue,
							  'vatpayed' => $vatPayedValue];
		}
	return ($arrInvoiceVat);
	
    }
    
    protected function getArrInvoicePay(){
		$tmpData = $this->DataManager->find($this->id);
		$arrInvoicePay = [];
		foreach($tmpData->related('cl_invoice_advance_payments')->where('pay_type = 0') as $key => $one)
		{
                    $arrInvoicePay[$key] = ['pay_date' => $one->pay_date,
                                                 'pay_price' => $one->pay_price, 
                                                 'pay_doc' => $one->pay_doc,
                                                 'currency_name' => $one->cl_currencies->currency_name];
		}
	return ($arrInvoicePay);
	
    }    
    
    
    protected function createComponentEdit($name)
    {
        //$this->translator->setPrefix(['applicationModule.InvoiceAdvance']);
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
        $form->addText('inv_number', $this->translator->translate('Císlo_faktury'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Císlo_faktury'));
        $form->addText('od_number', $this->translator->translate('Císlo_objednávky'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Císlo_objednávky'));
        $form->addText('delivery_number', $this->translator->translate('Dodací_list'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Dodací_list'));
        $form->addText('inv_date', $this->translator->translate('Vystavení'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Vystavení'));
        /*$form->addText('vat_date', 'DUZP:', 20, 20)
            ->setHtmlAttribute('class','form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder','Datum uskutečnění zdanitelného plnění');*/
        $form->addText('due_date', $this->translator->translate('Splatnost'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Splatnost'));
        $form->addText('inv_title', $this->translator->translate('Popis'), 150, 150)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Popis'));
        $form->addText('var_symb', $this->translator->translate('Var._symbol'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Var._symbol'));
        $form->addText('konst_symb', $this->translator->translate('K._symbol'), 5, 5)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Konstatní_symbol'));
        $form->addText('spec_symb', $this->translator->translate('Spec._symbol'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Spec._symbol'));
        $form->addText('correction_inv_number', $this->translator->translate('Opravovaná_faktura'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Císlo_faktury'));
	
		$form->addText('cm_number', $this->translator->translate('Zakázka'), 20, 40)
			->setHtmlAttribute('class','form-control input-sm')
			->setHtmlAttribute('placeholder',$this->translator->translate('Císlo_zakázky'));

	    /*$arrTypes = $this->InvoiceTypesManager->findAll()->where('inv_type IN (1,2,3)')->fetchPairs('id','name');
	    $form->addSelect('cl_invoice_types_id', "Druh dokladu:",$arrTypes)
		    ->setHtmlAttribute('data-placeholder','Zvolte druh dokladu')
		    ->setHtmlAttribute('class','form-control chzn-select input-sm')
		    ->setHtmlAttribute('data-urlajax',$this->link('GetGroupNumberSeries!'))
		    ->setRequired('Druh dokladu musí být vybrán')
		    ->setPrompt('Zvolte druh dokladu');*/

	    
	    $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
	    $form->addSelect('cl_center_id', $this->translator->translate("Středisko"),$arrCenter)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_středisko'))
		    ->setPrompt($this->translator->translate('Zvolte_středisko'));
	    
	    $form->addCheckbox('pdp', $this->translator->translate('Přenesená_daňová_povinnost'))
			->setDefaultValue(FALSE)
			->setHtmlAttribute('data-urlajax',$this->link('PDP!'))
			->setHtmlAttribute('class', 'items-show');
	
		$form->addCheckbox('export', $this->translator->translate('Exportní_fa'))
			->setDefaultValue(FALSE)
			->setHtmlAttribute('data-urlajax',$this->link('export!'))
			->setHtmlAttribute('class', 'items-show');
	
		$form->addCheckbox('storno', $this->translator->translate('Stornováno'))
			->setDefaultValue(FALSE)
			->setHtmlAttribute('class', 'items-show');
	    
	    $arrStatus= $this->StatusManager->findAll()->where('status_use = ?','invoice_advance')->fetchPairs('id','status_name');
	    $form->addSelect('cl_status_id', $this->translator->translate("Stav"),$arrStatus)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_stav_faktury'))
		    ->setRequired($this->translator->translate('Vyberte_prosím_stav_faktury'))
		    ->setPrompt($this->translator->translate('Zvolte_stav_faktury'));

	    //28.12.2018 - have to set $tmpId for found right record it could be bscId or id
	    if ($this->id == NULL){
		    $tmpId = $this->bscId;
	    }else{
		    $tmpId = $this->id;
	    }
            if ($tmpInvoice = $this->DataManager->find($tmpId)) 
            {
                if (isset($tmpInvoice['cl_partners_book_id']))
                {
                    $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
                }else{
                    $tmpPartnersBookId = 0;
                }
               
            }else{
                $tmpPartnersBookId = 0;
            }
        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
	    /*
            $mySection = $this->getSession('selectbox'); // returns SessionSection with given name
	    //06.07.2018 - session selectbox is filled via baselist->handleUpdatePartnerInForm which is called by ajax from onchange event of selectbox
	    //this is necessary because Nette is controlling values of selectbox send in form with values which were in selectbox accesible when it was created.
	    if (isset($mySection->cl_partners_book_id_values ))
	    {
		    $arrPartners = 	$mySection->cl_partners_book_id_values;
	    }else{
		    $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
	    }*/

            //$arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
	    
	    //$mySection = $this->getSession('selectbox'); // returns SessionSection with given name	
	    //06.07.2018 - session selectbox is filled via baselist->handleUpdatePartnerInForm which is called by ajax from onchange event of selectbox
	    //this is necessary because Nette is controlling values of selectbox send in form with values which were in selectbox accesible when it was created.
	    //if (isset($mySection->cl_partners_book_id_values ))
	    //{
		//$arrPartners = 	$mySection->cl_partners_book_id_values;
	    //}else{
		//$arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');            
	    //}
	    
	    $form->addSelect('cl_partners_book_id', $this->translator->translate("Odběratel"),$arrPartners)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_odběratele'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
		    ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
		    ->setPrompt($this->translator->translate('Zvolte_odběratele'));
            
	    $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id','name');
	    $form->addSelect('cl_payment_types_id',$this->translator->translate('Forma_úhrady'),$arrPay)
		    ->setHtmlAttribute('class','form-control chzn-select input-sm');
	
		$arrBank = $this->BankAccountsManager->findAll()->
								select('cl_bank_accounts.id, CONCAT(cl_currencies.currency_code, " ", account_number) AS account_number')->
								order('cl_currencies.currency_code')->fetchPairs('id','account_number');

		$form->addSelect('cl_bank_accounts_id',$this->translator->translate('Účet'),$arrBank)
			->setPrompt($this->translator->translate('Zvolte_účet'))
            ->setHtmlAttribute('data-urlajaxaccount', $this->link('changeAccount!', ['type' => 'account']))
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_účet'))
			->setHtmlAttribute('class','form-control chzn-select input-sm');
	    
	    $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id','currency_code');
	    $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"),$arrCurrencies)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
		    ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajaxaccount', $this->link('changeAccount!', ['type' => 'currency']))
		    ->setHtmlAttribute('data-urlajax',$this->link('GetCurrencyRate!'))
		    ->setHtmlAttribute('data-urlrecalc',$this->link('makeRecalc!')) 
		    ->setPrompt($this->translator->translate('Zvolte_měnu'));
	    
        $form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
		    	->setHtmlAttribute('class','form-control input-sm')
			->setHtmlAttribute('data-urlrecalc',$this->link('makeRecalc!')) 		    
			->setHtmlAttribute('placeholder',$this->translator->translate('Kurz'));
	    //$arrUsers = $this->UserManager->getAll()->fetchPairs('id','name');
	    //$arrUsers = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->fetchPairs('id','name');
	    $arrUsers = array();
	    $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id','name');
	    $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id','name');
	    //dump($arrUsers);
	    //die;
	    $form->addSelect('cl_users_id', $this->translator->translate("Obchodník"),$arrUsers)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_obchodníka'))
		    ->setPrompt($this->translator->translate('Zvolte_obchodníka'));
	    
	    
	    $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
	    $form->addSelect('cl_partners_book_workers_id', $this->translator->translate("Kontakt"),$arrWorkers)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_kontaktní_osobu'))
		    ->setPrompt($this->translator->translate('Zvolte_kontaktní_osobu'));
	    

	    $arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?',$tmpPartnersBookId)->fetchPairs('id','b_name');
	    $form->addSelect('cl_partners_branch_id', $this->translator->translate("Pobočka"),$arrBranch)
		    ->setPrompt($this->translator->translate('Zvolte pobočku'))
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Pobočka'));
	    
            //$form->addTextArea('footer_txt', 'Zápatí:', 100,3 )
		//	->setHtmlAttribute('placeholder','Text v zápatí faktury');	    
	    $form->onValidate[] = array($this, 'FormValidate');	    
            $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-sm btn-primary');
            $form->addSubmit('store_out', $this->translator->translate('Vydat za skladu'))->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
				->setHtmlAttribute('class','btn btn-sm btn-primary')
				->setValidationScope([])
				->onClick[] = array($this, 'stepBack');	    	    
	//	    ->onClick[] = callback($this, 'stepSubmit');

	    $form->onSuccess[] = array($this,'SubmitEditSubmitted');
	    return $form;
    }

    public function stepBack()
    {	    
		$this->redirect('default');
    }		
    
     public function FormValidate(Form $form)
    {
        $data=$form->values;
        /*02.12.2020 - cl_partners_book_id required and prepare data for just created partner
        */
        $data = $this->updatePartnerId($data);
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
        {
            $form->addError($this->translator->translate('Partner musí být vybrán'));
        }
        //13.06.2021 - invoice number uniqueness
        $tmpData = $this->DataManager->findAll()->where('inv_number = ? AND id != ?', $data['inv_number'], $this->id)->fetch();
        if ($tmpData){
            $form->addError($this->translator->translate('Zadané_číslo_faktury_je_již_použité'));
        }

        $this->redrawControl('content');

		
    }

    public function SubmitEditSubmitted(Form $form)
    {

        $data=$form->values;

        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() )
        {
            $data = $this->RemoveFormat($data);//

            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly))
            {//if record is not marked as finished, we can save edited data
                if (!empty($data->id))
                {

                    $this->DataManager->update($data, TRUE);
                    $this->InvoiceAdvanceManager->updateInvoiceSum($this->id);

                    if ($tmpData = $this->DataManager->find($data['id']))
                    {
                        //unvalidate document for downloading
                        $tmpDocuments = array();
                        $tmpDocuments['id'] = $tmpData->cl_documents_id;
                        $tmpDocuments['valid'] = 0;
                        $newDocuments = $this->DocumentsManager->update($tmpDocuments);
                    }

                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                }
                else
                {
                    //$row=$this->DataManager->insert($data);
                    //$this->newId = $row->id;
                    //$this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            }else{
                //$this->flashMessage('Změny nebyly uloženy.', 'success');
            }
            $this->redrawControl('content');


        }else{
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redirect('default');

            //$this->redirect('default');
        }



    }

    /**return correct account or currency for given account or currency
     * 07.11.2021 TH
     * @param $type
     * @param $idData
     * @return void
     */
  /*  public function handleChangeAccount($type, $idData) : void{
        $arrRet = [];
        if ($type == 'account'){
            if ($account = $this->BankAccountsManager->findAll()->where('id = ?', $idData)->limit(1)->fetch())
                $arrRet = ['type' => 'currency', 'id' => $account->cl_currencies_id];
            else
                $arrRet = ['error' => 'nocurrency'];
        }elseif ($type == 'currency'){
            if ($account = $this->BankAccountsManager->findAll()->where('cl_currencies_id = ?', $idData)->order('default_account')->limit(1)->fetch())
                $arrRet = ['type' => 'account', 'id' => $account['id']];
            else
                $arrRet = ['error' => 'noaccount'];
        }else{
            $arrRet = ['error' => 'notype'];
        }
        $this->sendJson($arrRet, \Nette\Utils\Json::PRETTY);
    }*/

    /**return correct account or currency for given account or currency
     * 07.11.2021 TH
     * @param $type
     * @param $idData
     * @return void
     */
    public function handleChangeAccount($type, $idData): void
    {
        $arrRet = [];
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData) {
            if ($type == 'account') {
                if ($account = $this->BankAccountsManager->findAll()->where('id = ?', $idData)->limit(1)->fetch())
                    $arrRet = ['type' => 'currency', 'id' => $account->cl_currencies_id];
                else
                    $arrRet = ['error' => 'nocurrency'];
            } elseif ($type == 'currency') {
                if ($account = $this->BankAccountsManager->findAll()->where('cl_currencies_id = ?', $idData)->order('default_account')->limit(1)->fetch()) {
                    if (!is_null($tmpData['cl_bank_accounts_id']) && $tmpData->cl_bank_accounts['cl_currencies_id'] != $idData)
                        $arrRet = ['type' => 'account', 'id' => $account['id']];
                    else
                        $arrRet = ['error' => 'noCurrencyChange'];
                }else
                    $arrRet = ['error' => 'noaccount'];
            } else {
                $arrRet = ['error' => 'notype'];
            }
        }else{
            $arrRet = ['error' => 'nodata'];
        }
        $this->sendJson($arrRet, \Nette\Utils\Json::PRETTY);
    }



    protected function createComponentHeaderEdit($name)
    {	
		$form = new Form($this, $name);
	    $form->addHidden('id',NULL);
	    //$form->addCheckbox('header_show', 'Tiskount záhlaví');
		$form->addTextArea('header_txt', $this->translator->translate('Záhlaví'), 100,10 )
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Text v záhlaví faktury'));
		$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepHeaderBack');	    	    
		$form->onSuccess[] = array($this,'SubmitEditHeaderSubmitted');
		return $form;
    }

    public function stepHeaderBack()
    {	    
		$this->headerModalShow = FALSE;
		$this->activeTab = 2;	
		$this->redrawControl('headerModalControl');
    }		

    public function SubmitEditHeaderSubmitted(Form $form)
    {
		$data=$form->values;	
		//later there must be another condition for user rights, admin can edit everytime
		if ($form['send']->isSubmittedBy())
		{
			$this->DataManager->update($data);
		}
		$this->headerModalShow = FALSE;
		$this->activeTab = 2;		
		$this->redrawControl('items');	    	
		$this->redrawControl('headerModalControl');	    
    }
    
    protected function createComponentFooterEdit($name)
    {	
		$form = new Form($this, $name);
	    $form->addHidden('id',NULL);
	    //$form->addCheckbox('footer_show', 'Tiskount zápatí');
            $form->addTextArea('footer_txt', $this->translator->translate('Zápatí'), 100,3 )
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Text v zápatí faktury'));
            $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepFooterBack');	    	    

		$form->onSuccess[] = array($this,'SubmitEditFooterSubmitted');
            return $form;
    }

    public function stepFooterBack()
    {	    
		$this->headerModalShow = FALSE;
		$this->activeTab = 3;	
		$this->redrawControl('footerModalControl');
    }		

    public function SubmitEditFooterSubmitted(Form $form)
    {
		$data=$form->values;	
		if ($form['send']->isSubmittedBy())
		{
			$this->DataManager->update($data);
		}
		$this->footerModalShow = FALSE;
		$this->activeTab = 3;		
		$this->redrawControl('items');	    	
		$this->redrawControl('footerModalControl');	    
    }    
		
		
		
		
		
 
    

    public function handleMakeRecalc($idCurrency,$rate,$oldrate,$recalc)
    {
		//in future there can be another work with rates
		//dump($this->editId);
		if ($rate>0)
		{
			if ($recalc == 1)
			{
			$recalcItems = $this->InvoiceAdvanceItemsManager->findBy(array('cl_invoice_advance_id' => $this->id));
			//$recalcItems = $this->InvoiceAdvanceItemsManager->findBy(array('cl_invoice_advance_id' => array($this->id,827,829,799,769,770,757,740,741,742,721,718)));
			foreach($recalcItems as $one)
			{
				//$data = array();
				//$data['price_s'] = $one['price_s'] * $oldrate / $rate;

				//if ($this->settings->platce_dph == 1)
				$data['price_e'] = $one['price_e'] * $oldrate / $rate;
				$data['price_e2'] = $one['price_e2'] * $oldrate  / $rate;	    
				$data['price_e2_vat'] = $one['price_e2_vat'] * $oldrate / $rate;	    	    

				$one->update($data);
			}
			}

			//we must save parent data 
			$parentData = array();
			$parentData['id'] = $this->id;
			if ($rate<>$oldrate)
			$parentData['currency_rate'] = $rate;

			$parentData['cl_currencies_id'] = $idCurrency;
			$this->DataManager->update($parentData);	


			$this->UpdateSum($this->id,$this);

		}
		$this->redrawControl('items');	

    }
    
   
    public function UpdateSum()
    {
        $this->InvoiceAdvanceManager->updateInvoiceSum($this->id);
        parent::UpdateSum();
        $this['invoicelistgrid']->redrawControl('editLines');
        /*if ($this->settings->invoice_to_store == 1){
            $this['invoiceBacklistgrid']->redrawControl('editLines');
        }*/
        //$this['sumOnDocs']->redrawControl();
    }    
    
    public function beforeAddLine($data) {
	//parent::beforeAddLine($data);
	//dump($data['control_name']);
	if ($data['control_name'] == "invoicelistgrid")
	{
	    $data['price_e_type'] = $this->settings->price_e_type;	
	}

	return $data;
    }
    
    public function ListGridInsert($sourceData,$dataManager)
    {
	    $arrPrice = array();
	    //if (isset($sourceData['cl_pricelist_id']))
        if (array_key_exists('cl_pricelist_id',$sourceData->toArray()))
	    {
			$arrPrice['id'] = $sourceData['cl_pricelist_id'];
			$sourcePriceData = $this->PriceListManager->find($sourceData->cl_pricelist_id);		
	    }  else {
			$arrPrice['id'] = $sourceData['id'];
			$sourcePriceData = $this->PriceListManager->find($sourceData->id);		
	    }
	    $arrPrice['price_s'] = $sourceData['price_s'];
        $arrPrice['cl_currencies_id'] = $sourcePriceData['cl_currencies_id'];
	    ///04.09.2017 - find price if there are defince prices_groups
	    $tmpData = $this->DataManager->find($this->id);
	    if ( isset($tmpData['cl_partners_book_id']) 
		    && $tmpPrice = $this->PricesManager->getPrice($tmpData->cl_partners_book,
								$arrPrice['id'],
								$tmpData->cl_currencies_id,
								$this->settings['cl_storage_id']))
        {
            $arrPrice['price']          = $tmpPrice['price'];
            $arrPrice['price_vat']      = $tmpPrice['price_vat'];
            $arrPrice['discount']       = $tmpPrice['discount'];
            $arrPrice['price_e2']       = $tmpPrice['price_e2'];
            $arrPrice['price_e2_vat']   = $tmpPrice['price_e2_vat'];
            $arrPrice['cl_currencies_id'] = $tmpPrice['cl_currencies_id'];
        }else{
            $arrPrice['price']          = $sourceData->price;
            $arrPrice['price_vat']      = $sourceData->price_vat;
            $arrPrice['discount']       = 0;
            $arrPrice['price_e2']       = $sourceData->price;
            $arrPrice['price_e2_vat']   = $sourceData->price_vat;
           // $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
        }



	    $arrPrice['vat'] = $sourceData->vat;	    
	
	    $arrData = array();
	    $arrData[$this->DataManager->tableName.'_id'] = $this->id;

	    $arrData['cl_pricelist_id'] = $sourcePriceData->id;
	    $arrData['item_order'] = $this->InvoiceAdvanceItemsManager->findAll()->where($this->DataManager->tableName.'_id = ?', $arrData[$this->DataManager->tableName.'_id'])->max('item_order') + 1;

	    $arrData['item_label'] = $sourcePriceData->item_label;
	    $arrData['quantity'] = 1;

	    $arrData['units'] = $sourcePriceData->unit;

	    $arrData['vat'] = $arrPrice['vat'];
		
	    $arrData['price_s'] = $arrPrice['price_s'];
	    
	    $arrData['price_e_type'] = $this->settings->price_e_type;		
		if ($arrData['price_e_type'] == 1)
		{
			$arrData['price_e'] = $arrPrice['price_vat'];
		}else{
			$arrData['price_e'] = $arrPrice['price'];
		}
        $arrData['discount'] = $arrPrice['discount'];
        $arrData['price_e2'] = $arrPrice['price_e2'];
        $arrData['price_e2_vat'] = $arrPrice['price_e2_vat'];
        
        //default storage from pricelist
        $arrData['cl_storage_id'] = $sourcePriceData['cl_storage_id'];
        //20.10.2019 - only if there is not default storage on presenter
        /*if ($dataManager->tableName == 'cl_invoice_items_back'){
            $tmpDefData = $this['invoiceBacklistgrid']->getDefaultData();
            if (isset($tmpDefData['cl_storage_id']) && !is_null($tmpDefData['cl_storage_id']))
            {
                $arrData['cl_storage_id'] = $tmpDefData['cl_storage_id'];
            }
        }*/

	    
	    //prepocet kurzem
	    //potrebujeme kurz ceníkove polozky a kurz zakazky
	    if ($sourceData->cl_currencies_id != NULL)
			$ratePriceList = $sourceData->cl_currencies->fix_rate;
	    else
			$ratePriceList = 1;

	    if ($tmpOrder = $this->DataManager->find($this->id))
			$rateOrder = $tmpOrder->currency_rate;
	    else
			$rateOrder = 1;


		
	    //$arrData['price_s'] = $arrData['price_s'] * $ratePriceList / $rateOrder;
        if ( $arrPrice['cl_currencies_id'] != $tmpOrder['cl_currencies_id'] ) {
            $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOrder;
            $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOrder;
            $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOrder;
        }


		
	    $row = $dataManager->insert($arrData);
	    $this->updateSum($this->id,$this);		    
	    return($row);
	    
    }
    
    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
		//dump($cl_partners_book_id);
		$arrUpdate = array();
		$arrUpdate['id'] = $this->id;
		$arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL:$cl_partners_book_id ) ;

		//dump($arrUpdate);
		//die;
		$this->DataManager->update($arrUpdate);

		$this['invoicelistgrid']->redrawControl('pricelist2');
    }    
    
    //javascript call when changing cl_partners_book_id or change inv_date
    public function handleRedrawDueDate2($invdate)
    {
		$invdate = date_create($invdate);
		//Debugger::fireLog($invdate);	

		$tmpData = $this->DataManager->find($this->id);
                //dump(isset($tmpData->cl_partners_book_id);
		$arrUpdate = array();
		$arrUpdate['id'] = $this->id;
		if (isset($tmpData['cl_partners_book_id']) && $tmpData->cl_partners_book->due_date > 0)
        {
			$strModify = '+'.$tmpData->cl_partners_book->due_date.' day';
        }else{
			$strModify = '+'.$this->settings->due_date.' day';
        }



		if (isset($tmpData['cl_partners_book_id']) && isset($tmpData->cl_partners_book->cl_payment_types_id))
		{
			$clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
			$spec_symb = $tmpData->cl_partners_book->spec_symb;
			if ($tmpData->cl_partners_book->cl_payment_types->payment_type == 0){
                $arrUpdate['due_date'] = $invdate->modify($strModify);
            }else{
                $arrUpdate['due_date'] = $invdate;
            }
		}
		else 
		{
			$clPayment = $this->settings->cl_payment_types_id;
			$spec_symb = "";
			if ($this->settings->cl_payment_types->payment_type == 0){
                $arrUpdate['due_date'] = $invdate->modify($strModify);
            }else{
                $arrUpdate['due_date'] = $invdate;
            }
		}


		//Debugger::fireLog($arrUpdate);

		$this->DataManager->update($arrUpdate);

		//$this->redrawControl('duedate2');
		//echo($arrUpdate['due_date']->format('d.m.Y'));
		$return = array('due_date' => $arrUpdate['due_date']->format('d.m.Y'),
				'cl_payment_types_id' => $clPayment,
				'spec_symb' => $spec_symb);
		echo(json_encode($return));
		$this->terminate();
    }    

    public function handlePDP($value)
    {
		//Debugger::fireLog($value);
		$tmpData = $this->DataManager->find($this->id);
		$arrUpdate = array();
		$arrUpdate['id'] = $this->id;
		if ($value == 'true') {
			$arrUpdate['pdp'] = 1;
			$arrUpdate['footer_txt'] = $tmpData->footer_txt;
			$pdpText = nl2br($this->settings->pdp_text);
			$arrUpdate['footer_txt'] = ($arrUpdate['footer_txt'] != '') ? $arrUpdate['footer_txt'] .= '<br><br>'.$pdpText : $pdpText;
		}else {
			$arrUpdate['pdp'] = 0;
			$arrUpdate['footer_txt'] = "";
		}

		//Debugger::fireLog($arrUpdate);
		$this->DataManager->update($arrUpdate);
		$this->updateSum($this->id,$this);		    	
		$this['invoicelistgrid']->redrawControl('editLines');	
		//$this->redrawControl('formedit');
		$this->redrawControl('items');
    }
	
	public function handleExport($value)
	{
		//Debugger::fireLog($value);
		$tmpData = $this->DataManager->find($this->id);
		$arrUpdate = array();
		$arrUpdate['id'] = $this->id;
		if ($value == 'true') {
			$arrUpdate['export'] = 1;
		}else {
			$arrUpdate['export'] = 0;
		}
		
		//Debugger::fireLog($arrUpdate);
		$this->DataManager->update($arrUpdate);
		$this->updateSum($this->id,$this);
		$this['invoicelistgrid']->redrawControl('editLines');
		//$this->redrawControl('formedit');
		$this->redrawControl('items');
		
	}
	
	
	
	
	//control method to determinate if we can delete
    public function beforeDelete($lineId, $name = NULL) {

	    $result = FALSE;

        if ($name == "paymentListGrid") {
            $tmpPayment = $this->InvoiceAdvancePaymentsManager->find($lineId);
            if ($tmpPayment && !is_null($tmpPayment['cl_cash_id'])){
                $tmpPayment->cl_cash->delete();
            }
            $result = TRUE;
        }


	    if ($name == "invoicelistgrid"){
			if ($tmpLine = $this->InvoiceAdvanceItemsManager->find($lineId))
			{
				//07.05.2017 - if line is from helpdesk, we must delete connection
				if (!is_null($tmpLine->cl_partners_event_id))
				{
					$this->PartnersEventManager->find($tmpLine->cl_partners_event_id)->update(array('cl_invoice_advance_id' => NULL));
					$tmpLine->update(array('cl_partners_event_id' => NULL));
					$result = TRUE;
				}
				if ($this->settings->invoice_to_store == 1) {
					$this->StoreManager->deleteItemStoreMove($tmpLine);
					$result = TRUE;
				}

			}else{
				$result = TRUE;
			}
	
        }else{
	    	$result = TRUE;
		}
		
		return $result;
    }	    
    

    
    //aditional control before delete from baseList
    public function beforeDeleteBaseList($id)
    {
        foreach($this->DataManager->find($id)->related('cl_invoice_advance_items') as $one)
        {
            //07.05.2017 - if line is from helpdesk, we must delete connection
            /*if (!is_null($one->cl_partners_event_id))
            {
            $this->PartnersEventManager->find($one->cl_partners_event_id)->update(array('cl_invoice_advance_id' => NULL));
            $one->update(array('cl_partners_event_id' => NULL));
            }*/

        }

        $data = $this->DataManager->find($id);
        /*if (!is_null($data['cl_eet_id']) && $data->cl_eet->eet_status == 3) {
            $ret = FALSE;
        } else {
            $ret = TRUE;
        }*/

        return TRUE;
    }



    public function emailSetStatus() {
		$this->setStatus($this->id, array('status_use' => 'invoice',
						's_new'  => 0,
						's_eml' => 1));
    }    


    
    public function handleGetGroupNumberSeries($cl_invoice_types_id)
    {
        //19.10.2019 - not used anymore
        return;

		//Debugger::fireLog($this->id);	    
		$arrData = array();
		$arrData['id'] = NULL;
		$arrData['number'] = '';	
		if ($data = $this->InvoiceTypesManager->find($cl_invoice_types_id)){
		    //dump($data->cl_number_series_id);
		    if ($tmpData = $this->DataManager->find($this->id)){
			if ($data->cl_number_series_id == $tmpData->cl_number_series_id){
				$tmpId = $this->id;
			}else{
				$tmpId = NULL;
				//ponizit o 1 minule pouzitou ciselnou radu.
				$this->NumberSeriesManager->update(array('id' => $tmpData->cl_number_series_id, 'last_number' => $tmpData->cl_invoice_types->cl_number_series->last_number - 1));
			}

			if ($data2 = $this->NumberSeriesManager->getNewNumber('invoice', $data->cl_number_series_id, $tmpId)){
				$arrData = $data2;
			}
			$arrUpdate = array();
			$arrUpdate['id']		    = $this->id;
			$arrUpdate['inv_number']	    = $arrData['number'];
			$arrUpdate['cl_invoice_types_id']   = $cl_invoice_types_id;
			$arrUpdate['cl_number_series_id']   = $data->cl_number_series_id;
			
			
			//20.12.2018 - headers and footers
			if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $data->cl_number_series_id))->fetch()){
			    $arrUpdate['header_txt'] = $hfData['header_txt'];
			    $arrUpdate['footer_txt'] = $hfData['footer_txt'];
			}

			//update main data
			$this->DataManager->update($arrUpdate);
		    }
		}
		
		echo(json_encode($arrData));
		$this->terminate();
    }
    
    public function handlePaymentShow()
    {
        $this->paymentModalShow = TRUE;
        //$this->redrawControl('paymentControl');
        //$this->pairedDocsShow = TRUE;
        $this->showModal('paymentModal');
        $this->redrawControl('paymentControl');
        $this->redrawControl('contents');
    }

    public function handleHeaderShow()
    {
		$this->headerModalShow = TRUE;
		$this->redrawControl('headerModalControl');
    }    
    
    public function handleFooterShow()
    {
		$this->footerModalShow = TRUE;
		$this->redrawControl('footerModalControl');
    }    
        
    
    public function DataProcessMain($defValues, $data) {
		$defValues['var_symb'] = preg_replace('/\D/', '', $defValues['inv_number']);
		
		//20.12.2018 - headers and footers
        //19.10.2019 - solved in BaseListPresenter->getNumberSeries
		//if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $defValues['cl_number_series_id']))->fetch()){
		//    $defValues['header_txt'] = $hfData['header_txt'];
		//    $defValues['footer_txt'] = $hfData['footer_txt'];
		//}
		
		return $defValues;
    }
    
    public function handleInvHeaderShow($value)
    {
		$arrData = array();
		$arrData['id'] = $this->id;
		//Debugger::fireLog($value);	
		if ($value == 'true')
			$arrData['header_show'] = 1;
		else
			$arrData['header_show'] = 0;

		$this->DataManager->update($arrData);

		$this->terminate();
    }
    
    public function handleInvFooterShow($value)
    {
		$arrData = array();
		$arrData['id'] = $this->id;
		//Debugger::fireLog($value);	
		if ($value == 'true')
			$arrData['footer_show'] = 1;
		else
			$arrData['footer_show'] = 0;

		$this->DataManager->update($arrData);

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

    public function DataProcessListGrid($data)
    {
        parent::DataProcessListGrid($data);
        return $data;
    }
    
    //aditional processing data after save in listgrid
    //23.11.2018 - there must be giveout from store and receiving backitems
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
		parent::afterDataSaveListGrid($dataId,$name);
	
		if ($name == "invoicelistgrid" && $this->settings->invoice_to_store == 1){
		    //24.02.2020 - there is no connection to store from advance invoice
			//saled items - give out
			//1. check if cl_store_docs exists if not, create new one
			//$docId = $this->StoreDocsManager->createStoreDoc(1, $this->id, $this->DataManager);
			
			//2. giveout current item
			//$dataIdtmp = $this->StoreManager->giveOutItem($docId, $dataId, $this->InvoiceAdvanceItemsManager);

		}
		
		if ($name == "invoicelistgrid"){
			//14.03.2019 - insert cl_pricelist_bond into cl_invoice_advance_items
			$tmpInvoiceItem = $this->InvoiceAdvanceItemsManager->find($dataId);
			//bdump($tmpInvoiceItem->cl_pricelist_id, 'cl_pricelist_id');
			if (!is_null($tmpInvoiceItem->cl_pricelist_id)){
				//update and profit
				$priceS = $tmpInvoiceItem->price_s * $tmpInvoiceItem->quantity;
	            if ($tmpInvoiceItem->price_e2 != 0) {
					//$profit = (($tmpInvoiceItem->price_e2  / $priceS) - 1) * 100;
					$profit = 100 - (($priceS / $tmpInvoiceItem->price_e2) * 100);
	            } else{
	                $profit = 0;
	            }
				$tmpInvoiceItem->update(array('profit' => $profit));
	
				//find if there are bonds in cl_pricelist_bonds
				//$tmpBonds = $this->PriceListBondsManager->findBy(array('cl_pricelist_bonds_id' => $tmpInvoiceItem->cl_pricelist_id));
                $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpInvoiceItem->cl_pricelist_id, $tmpInvoiceItem->quantity);
				foreach ($tmpBonds as $key => $oneBond){
					//found in cl_invoice_advance_items if there already is bonded item
					$tmpInvoiceItemBond = $this->InvoiceAdvanceItemsManager->findBy(array('cl_parent_bond_id' => $tmpInvoiceItem->id,
												   'cl_pricelist_id' => $oneBond->cl_pricelist_id))->fetch();
					$newItem = array();
					$newItem['cl_invoice_advance_id']		 = $this->id;
					$newItem['item_order']		     = $tmpInvoiceItem->item_order + 1;
					$newItem['cl_pricelist_id']		 = $oneBond->cl_pricelist_id;
					$newItem['cl_storage_id']        = $tmpInvoiceItem->cl_storage_id;
					$newItem['item_label']		     = $oneBond->cl_pricelist->item_label;
					$newItem['quantity']		     = $oneBond->quantity * ($oneBond->multiply == 1) ? $tmpInvoiceItem->quantity : 1 ;// $tmpInvoiceItem->quantity;
					$newItem['units']			     = $oneBond->cl_pricelist->unit;
					$newItem['price_s']			     = $oneBond->cl_pricelist->price_s;
					$newItem['price_e']			     = $oneBond->cl_pricelist->price;
					$newItem['discount']		     = $oneBond->discount;
					$newItem['price_e2']	         = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount/100))) * ($oneBond->quantity * $tmpInvoiceItem->quantity);
					$newItem['vat']			         = $oneBond->cl_pricelist->vat;
					$newItem['price_e2_vat']		 = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount/100)) * ($oneBond->quantity * $tmpInvoiceItem->quantity);
					$newItem['price_e_type']		 = $tmpInvoiceItem->price_e_type;
					$newItem['cl_parent_bond_id']	 = $tmpInvoiceItem->id;
					//bdump($newItem);
					if (!$tmpInvoiceItemBond){
						$tmpNew = $this->InvoiceAdvanceItemsManager->insert($newItem);
						$tmpId = $tmpNew->id;
					}else{
						$newItem['id'] = $tmpInvoiceItemBond->id;
						$tmpNew = $this->InvoiceAdvanceItemsManager->update($newItem);
						$tmpId = $tmpInvoiceItemBond->id;
					}
					if ($this->settings->invoice_to_store == 1){
						//give out from store
                        //16,03,2021 - suppose there is no reason for store giveout in advance invoice
						//$dataId = $this->StoreManager->giveOutItem($docId, $tmpId, $this->InvoiceAdvanceItemsManager);
					}
					//update and profit
					$tmpBondItem = $this->InvoiceAdvanceItemsManager->find($tmpId);
					if ($tmpBondItem) {
						$priceS = $tmpBondItem->price_s * $tmpBondItem->quantity;
						//if ($priceS != 0) {
						//    //$profit = (($tmpBondItem->price_e2 / $priceS) - 1) * 100;
							$profit = 100 - (($priceS / $tmpBondItem->price_e2) * 100);
						//} else {
						  //  $profit = 0;
						//}
						$tmpBondItem->update(array('profit' => $profit));
					}
	
				}
			}
			
		}
		
	
    }    
 

	public function afterCopy($newLine, $oldLine) {
		//parent::afterCopy($newLine, $oldLine);
		$tmpItems = $this->InvoiceAdvanceItemsManager->findAll()->where('cl_invoice_advance_id = ?',$oldLine);
		foreach ($tmpItems as $one)
		{
			$tmpOne = $one->toArray();
			$tmpOne['cl_invoice_advance_id'] = $newLine;
			unset($tmpOne['id']);
			$this->InvoiceAdvanceItemsManager->insert($tmpOne);
		}
                
                
		/*$tmpPayments = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_invoice_advance_id = ?',$oldLine);
		foreach ($tmpPayments as $one)
		{
                    $tmpOne = $one->toArray();
                    $tmpOne['cl_invoice_advance_id'] = $newLine;
                    unset($tmpOne['id']);
                    $this->InvoiceAdvancePaymentsManager->insert($tmpOne);
		} */
                
                
		
	}
        
        

    protected function createComponentReportInvoiceBook($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    
            $now = new \Nette\Utils\DateTime;
            if ($this->settings->platce_dph)
            {
                $lcText1 = $this->translator->translate('DUZP_od');
                $lcText2 = $this->translator->translate('DUZP_do');
            }else{
                $lcText1 = $this->translator->translate('Vystaveno_od');
                $lcText2 = $this->translator->translate('Vystaveno_do');
            }
            $form->addText('date_from', $lcText1, 0, 16)
                    ->setDefaultValue('01.'.$now->format('m.Y'))
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

            $form->addText('date_to', $lcText2, 0, 16)
                    ->setDefaultValue($now->format('d.m.Y'))
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

            $tmpArrPartners = $this->PartnersManager->findAll()->
				    select('CONCAT(cl_partners_book.id,"-",IFNULL(:cl_partners_branch.id,"")) AS id, CONCAT(cl_partners_book.company," ",IFNULL(:cl_partners_branch.b_name,"")) AS company')->
				    order('company')->fetchPairs('id','company');
	    
	    //bdump($tmpArrPartners);
            $form->addMultiSelect('cl_partners_book',$this->translator->translate('Odběratel'), $tmpArrPartners)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_odběratele'));
	    
            $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->order('status_name')->fetchPairs('id','status_name');
            $form->addMultiSelect('cl_status_id',$this->translator->translate('Stav_dokladu'), $tmpArrStatus)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_stav'));
	    
	    $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
            $form->addMultiSelect('cl_center_id',$this->translator->translate('Středisko'), $tmpArrCenter)
                            ->setHtmlAttribute('multiple','multiple')
                            ->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_středisko'));

	    $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);		
	    $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
			    ->setHtmlAttribute('multiple','multiple')
			    ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addMultiSelect('cl_payment_types_id',$this->translator->translate('Forma_úhrady'),$arrPay)
            ->setHtmlAttribute('multiple','multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_formu_úhrady_pro_tisk'));
	
		$tmpArrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id','currency_code');
		$form->addMultiSelect('cl_currencies_id',$this->translator->translate('Měna'), $tmpArrCurrencies)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_měnu'));
	
	
		$form->addCheckbox('after_due_date', 'Po splatnosti');
	    $form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
	    $form->addCheckbox('payed', $this->translator->translate('Zaplacené'));
	    

            $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class','btn btn-sm btn-primary');
            $form->addSubmit('save_xml', $this->translator->translate('uložit_do_XML'))->setHtmlAttribute('class','btn btn-sm btn-primary');
            $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		
	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackReportInvoiceBook');	    	    
		$form->onSuccess[] = array($this, 'SubmitReportInvoiceBookSubmitted');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
    }

    public function stepBackReportInvoiceBook()
    {	    
		$this->rptIndex = 0;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
    }		

    public function SubmitReportInvoiceBookSubmitted(Form $form)
    {
		$data=$form->values;	
		//dump(count($data['cl_partners_book']));
		//die;
		if ($form['save_pdf']->isSubmittedBy() || $form['save_csv']->isSubmittedBy()  || $form['save_xml']->isSubmittedBy())
		{    
		    
			$data['cl_partners_branch'] = array();
			$tmpPodm = array();
			if ($data['after_due_date'] == 1)
			{		    
			    if ($this->settings->platce_dph)
				$tmpPodm = 'cl_invoice_advance.due_date < NOW() AND cl_invoice_advance.price_payed < cl_invoice_advance.price_e2_vat';
			    else
				$tmpPodm = 'cl_invoice_advance.due_date < NOW() AND cl_invoice_advance.price_payed < cl_invoice_advance.price_e2';
			}

			if ($data['not_payed'] == 1)
			{		    
			    if ($this->settings->platce_dph)
				$tmpPodm = 'cl_invoice_advance.price_payed < cl_invoice_advance.price_e2_vat';
			    else
				$tmpPodm = 'cl_invoice_advance.price_payed < cl_invoice_advance.price_e2';
			}
			
			if ($data['payed'] == 1)
			{		    
			    if ($this->settings->platce_dph)
				$tmpPodm = 'cl_invoice_advance.price_payed >= cl_invoice_advance.price_e2_vat';
			    else
				$tmpPodm = 'cl_invoice_advance.price_payed >= cl_invoice_advance.price_e2';
			}			
					    
		    
			if ($data['date_to'] == "")
				$data['date_to'] = NULL; 			
			else
			{
				//$tmpDate = new \Nette\Utils\DateTime;
				//$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
				//date('Y-m-d H:i:s',strtotime($data['date_to']));
				$data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
			}

			if ($data['date_from'] == "")
				$data['date_from'] = NULL; 			
			else
				$data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from'])); 						

			if (count($data['cl_partners_book']) == 0)
			{
                if ($this->settings->platce_dph)
                {
				    $dataReport = $this->InvoiceAdvanceManager->findAll()->
                        							where($tmpPodm)->
                                                        where('cl_invoice_advance.inv_date >= ? AND cl_invoice_advance.inv_date <= ?', $data['date_from'], $data['date_to'])->
                                                        order('cl_invoice_advance.inv_number ASC, cl_invoice_advance.inv_date ASC');
                }else{
					$dataReport = $this->InvoiceAdvanceManager->findAll()->
							                        where($tmpPodm)->
                                                        where('cl_invoice_advance.inv_date >= ? AND cl_invoice_advance.inv_date <= ?', $data['date_from'], $data['date_to'])->
                                                        order('cl_invoice_advance.inv_number ASC, cl_invoice_advance.inv_date ASC');
                            }
			}else{
			    $tmpPartners = array();
			    $tmpBranches = array();
			    foreach($data['cl_partners_book'] as $one)
			    {
                    $arrOne = str_getcsv($one, "-");
                    $tmpPartners[] = $arrOne[0];
                    if ($arrOne[1] != '') {
						$tmpBranches[] = $arrOne[1];
					}
			    }

			    $data['cl_partners_book'] = $tmpPartners;

			    $data['cl_partners_branch'] = $tmpBranches;

			    
                            if ($this->settings->platce_dph)
                            {
                                $dataReport = $this->InvoiceAdvanceManager->findAll()->
                                            where($tmpPodm)->
                                            where('cl_invoice_advance.inv_date >= ? AND cl_invoice_advance.inv_date <= ?', $data['date_from'], $data['date_to']);
                            }else{
                                $dataReport = $this->InvoiceAdvanceManager->findAll()->
                                            where($tmpPodm)->
                                            where('cl_invoice_advance.inv_date >= ? AND cl_invoice_advance.inv_date <= ?', $data['date_from'], $data['date_to']);
                            }
                            if (count($tmpBranches) > 0)
							{
								$dataReport = $dataReport->where('cl_partners_book_id IN (?) OR cl_partners_branch_id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch']);
							}else{
								$dataReport = $dataReport->where('cl_partners_book_id IN (?)', $data['cl_partners_book']);
							}
                            $dataReport = $dataReport->order('cl_invoice_advance.inv_number ASC, cl_invoice_advance.inv_date ASC');
                            //bdump($tmpBranches);
			}
			if (count($data['cl_status_id']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_status_id' =>  $data['cl_status_id']));
			}
			
			if (count($data['cl_currencies_id']) > 0)
			{
				$dataReport = $dataReport->where(array('cl_currencies_id' =>  $data['cl_currencies_id']));
			}

			if (count($data['cl_center_id']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_invoice_advance.cl_center_id' =>  $data['cl_center_id']));
			}
			
			if (count($data['cl_users_id']) > 0)
			{
			    $dataReport = $dataReport->
								where(array('cl_invoice_advance.cl_users_id' =>  $data['cl_users_id']));
			}

            if (count($data['cl_payment_types_id']) > 0)
            {
                $dataReport = $dataReport->
                                where(array('cl_invoice_advance.cl_payment_types_id' =>  $data['cl_payment_types_id']));
            }


            $dataReport = $dataReport->select('cl_partners_book.company,cl_status.status_name,cl_currencies.currency_code,cl_invoice_types.name AS "druh faktury",cl_payment_types.name AS "druh platby",cl_invoice_advance.*');
		    
		    if ($form['save_pdf']->isSubmittedBy())
		    {			
			$tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;	
			$tmpTitle = $this->translator->translate('Kniha faktur vydaných za období');
			//$template = $this->createTemplate()->setFile( __DIR__.'/../templates/Invoice/ReportInvoiceBook.latte');
			//$template->data = $dataReport;  
			//$template->dataSettings = $data;
			//$template->dataSettingsPartners = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
			//$template->dataSettingsStatus = $this->StatusManager->findAll()->where(array('id' =>$data['cl_status_id']))->order('status_name');
			
			$dataOther = array();
			$dataSettings = $data;
			$dataOther['dataSettingsPartners']	= $this->PartnersManager->findAll()->
								    where(array('cl_partners_book.id' => $data['cl_partners_book']))->
								    select('cl_partners_book.company AS company')->
								    order('company');
			$dataOther['dataSettingsStatus']		= $this->StatusManager->findAll()->where(array('id' =>$data['cl_status_id']))->order('status_name');
			$dataOther['dataSettingsCenter']		= $this->CenterManager->findAll()->where(array('id' =>$data['cl_center_id']))->order('name');
			$dataOther['dataSettingsUsers']			= $this->UserManager->getAll()->where(array('id' =>$data['cl_users_id']))->order('name');
			$dataOther['dataSettingsCurrencies']	= $this->CurrenciesManager->findAll()->where(array('id' =>$data['cl_currencies_id']))->order('currency_code');
            $dataOther['dataSettingsPayments']		= $this->PaymentTypesManager->findAll()->where(array('id' =>$data['cl_payment_types_id']))->order('name');
		    
            $tmpArrVat = $this->getVats($dataReport);

            $dataOther['arrVat'] = $tmpArrVat;
			//bdump($tmpArrVat);
			$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Invoice/ReportInvoiceBook.latte', $dataOther, $dataSettings, 'Kniha faktur vydaných');
			$tmpDate1 = new \DateTime($data['date_from']);
			$tmpDate2 = new \DateTime($data['date_to']);
			$this->pdfCreate($template, $this->translator->translate('Kniha faktur vydaných za období').date_format($tmpDate1,'d.m.Y').' - '.date_format($tmpDate2,'d.m.Y'));
			
			//$template->settings = $this->settings;			
			//$template->title = $tmpTitle;
			//$template->author = $tmpAuthor;
			//$template->today = new \Nette\Utils\DateTime;
			//$this->tmpLogo();

			
			
		}
		elseif ($form['save_csv']->isSubmittedBy())
		{
		    if ( $dataReport->count()>0)
		    {
                $filename = $this->translator->translate("Kniha faktur vydaných");
                $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename."-" .date('Ymd-Hi').".csv",true));
		    }else{
                $this->flashMessage($this->translator->translate('Data nebyly do CSV uloženy Zadaným podmínkám nevyhověl žádný záznam'), 'danger');
                $this->redirect('default');
		    }		    
		}
        elseif ($form['save_xml']->isSubmittedBy())
        {

            if ( $dataReport->count()>0)
            {
                $arrResult = array();
                foreach($dataReport as $key => $one)
                {
                    $tmpInv = $one->toArray();
                    $arrResult[$key] = array('id' => $tmpInv['id'], 'inv_number' => $tmpInv['inv_number'], 'inv_date' => $tmpInv['inv_date'],
                                            'inv_date' => $tmpInv['inv_date'], 'due_date' => $tmpInv['due_date'], 'pay_date' => $tmpInv['pay_date'],
                                            'var_symb' => $tmpInv['var_symb'], 'konst_symb' => $tmpInv['konst_symb'], 'inv_title' => $tmpInv['inv_title'],
                                            'price_base0' => $tmpInv['price_base0'], 'price_base1' => $tmpInv['price_base1'], 'price_base2' => $tmpInv['price_base2'], 'price_base3' => $tmpInv['price_base3'],
                                            'correction_base0' => $tmpInv['correction_base0'], 'correction_base1' => $tmpInv['correction_base1'], 'correction_base2' => $tmpInv['correction_base2'], 'correction_base3' => $tmpInv['correction_base3'],
                                            'price_vat1' => $tmpInv['price_vat1'], 'price_vat2' => $tmpInv['price_vat2'], 'price_vat3' => $tmpInv['price_vat3'],
                                            'vat1' => $tmpInv['vat1'], 'vat2' => $tmpInv['vat2'], 'vat3' => $tmpInv['vat3'],
                                            'price_e2' => $tmpInv['price_e2'], 'price_e2_vat' => $tmpInv['price_e2_vat'], 'price_correction' => $tmpInv['price_correction'], 'price_payed' => $tmpInv['price_payed'],
                                            'base_payed0' => $tmpInv['base_payed0'], 'base_payed1' => $tmpInv['base_payed1'], 'base_payed2' => $tmpInv['base_payed2'], 'base_payed3' => $tmpInv['base_payed3'],
                                            'vat_payed1' => $tmpInv['vat_payed1'], 'vat_payed2' => $tmpInv['vat_payed2'], 'vat_payed3' => $tmpInv['vat_payed3'], 'advance_payed' => $tmpInv['advance_payed'],
                                            'pdp' => $tmpInv['pdp'], 'price_e_type' => $tmpInv['price_e_type'], 'storno' => $tmpInv['storno'], 'correction_inv_number' => $tmpInv['correction_inv_number'],
                                            'delivery_number' => $tmpInv['delivery_number'], 'od_number' => $tmpInv['od_number'], 'druh faktury' => $tmpInv['druh faktury'],
                                            'druh platby' => $tmpInv['druh platby'], 'status_name' => $tmpInv['status_name'], 'currency_code' => $tmpInv['currency_code'], 'currency_rate' => $tmpInv['currency_rate']);
                    $tmpPartnerBook = $one->ref('cl_partners_book');
                    $arrResult[$key]['partners_book'] = array('id' => $tmpPartnerBook['id'], 'company' => $tmpPartnerBook['company'],'street' => $tmpPartnerBook['street'],
                                                              'city' => $tmpPartnerBook['city'],'zip' => $tmpPartnerBook['zip'],'ico' => $tmpPartnerBook['ico'],'dic' => $tmpPartnerBook['dic']);
                    $arrResult[$key]['invoice_items'] = $one->related('cl_invoice_advance_items')->
                                                            select('cl_pricelist_id,cl_pricelist.identification,cl_invoice_advance_items.item_label, cl_invoice_advance_items.quantity, cl_invoice_advance_items.units, cl_invoice_advance_items.price_s, cl_invoice_advance_items.price_e,  cl_invoice_advance_items.price_e_type,discount,price_e2, price_e2_vat,cl_invoice_advance_items.cl_storage_id')->
                                                            fetchAll();
                    $arrResult[$key]['invoice_items_back'] = $one->related('cl_invoice_advance_items_back')->
                                    select('cl_pricelist_id,cl_pricelist.identification,cl_invoice_advance_items_back.item_label, cl_invoice_advance_items_back.quantity, cl_invoice_advance_items_back.units, cl_invoice_advance_items_back.price_s, cl_invoice_advance_items_back.price_e,  cl_invoice_advance_items_back.price_e_type,discount,price_e2, price_e2_vat,cl_invoice_advance_items_back.cl_storage_id')->
                                                                fetchAll();
                }
                $filename = $this->translator->translate("Kniha faktur vydaných");
                $this->sendResponse(new \XMLResponse\XMLResponse($arrResult, $filename."-" .date('Ymd-Hi').".xml"));
            }else{
                $this->flashMessage($this->translator->translate('Data nebyly do XML uloženy. Zadaným podmínkám nevyhověl žádný záznam.'), 'danger');
                $this->redirect('default');
            }
        }
        }
    }

    /*** returns array of VATs used in given dataReport
     * @param $dataReport
     * @return array
     */
    public function getVats($dataReport)
    {
        $tmpArrVat = array();
        $tmpArr2 = array();
        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base1 != 0)
                $tmpArr2[$one->vat1] = $one->vat1;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base2 != 0)
                $tmpArr2[$one->vat2] = $one->vat2;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base3 != 0)
                $tmpArr2[$one->vat3] = $one->vat3;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one)
        {
            if ($one->price_base0 != 0)
                $tmpArr2[0] = 0;
        }
        foreach ($tmpArr2 as $one)
        {
            $tmpArrVat[] = $one;
        }
        rsort($tmpArrVat);

        return ($tmpArrVat);
    }

    public function handlePairedDocs()
    {
        $this->pairedDocsShow = TRUE;
        $this->redrawControl('pairedDocs');
    }
    
    public function handleDeletePaired($id,$type)
    {
	if ($type=='cl_store_docs')
	{
	    if ($data = $this->DataManager->find($id))
	    {	
		$data->update(array('id' => $id, 'cl_store_docs_id' => NULL));
		$this->flashMessage($this->translator->translate('Vazba na výdejku byla zrušena. Výdejka však stále existuje.'),'success');
	    }
	}elseif ($type=='cl_commission')
	{
	    if ($data = $this->DataManager->find($id))
	    {	
		$data->update(array('id' => $id, 'cl_commission_id' => NULL));
		$this->flashMessage($this->translator->translate('Vazba na zakázku byla zrušena. Zakázka však stále existuje.'),'success');
	    }
	}	
	$this->pairedDocsShow = TRUE;	
	//$this->redrawControl('pairedDocs');
	$this->redirect(':edit');
    }

    
   public function handleShowPairedDocs()
    {
	//bdump('ted');
	$this->pairedDocsShow = TRUE;
	/*$this->showModal('pairedDocsModal');
	$this->redrawControl('pairedDocs');
	$this->redrawControl('contents');*/
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('pairedDocs2');
        $this->showModal('pairedDocsModal');
    }
        
    
    public function handleSavePDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);
        //bdump($noDownload, 'savePDF from invoicepresenter');

        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, $noPreview);
    }

    public function handleDownloadPDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);
        //bdump($noDownload, 'savePDF from invoicepresenter');

        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, TRUE);
    }
    
    public function handleSendDoc($id, $latteIndex = NULL, $arrData = [], $recepients = [], $emailingTextIndex = NULL)
    {
        $tmpData = $this->preparePDFData($id);
        parent::handleSendDoc($id, $tmpData['latteIndex'], $tmpData, $recepients, $emailingTextIndex);
    }
    
    
    public function preparePDFData($id)
    {
        $data = $this->DataManager->find($id);

        if ($data->cl_invoice_types->inv_type == 3) //zálohová faktura
            {
                //$tmpTemplateFile =  __DIR__ . '/../templates/Invoice/advancev1.latte';
                $latteIndex = 1;
            }
                //$tmpTitle = "Faktura ".$data->inv_number;
                //$tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            if ($this->settings->platce_dph)
            {
                $tmpPrice = $data->price_e2_vat;
            }else{
                $tmpPrice = $data->price_e2;
            }
            if (!empty($data->inv_title))
            {
                $tmpMsg = $data->inv_title;
            }else{
                if ($tmpContent = $this->InvoiceAdvanceItemsManager->findBy(array('cl_invoice_advance_id' => $data->id))->order('price_e2_vat DESC')->limit(1)->fetch())
                {
                    $tmpMsg = $tmpContent->item_label;
                }else{
                    $tmpMsg = '';
                }
            }
            if ($data->pay_date >= $data->inv_date)
            {
                $tmpDppd = $data->inv_date;
            }else{
                $tmpDppd = $data->pay_date;
            }
            if ($data->advance_payed > 0)
            {
                $tmpSA = 1;
            }else{
                $tmpSA = 0;
            }
            if ($data->cl_invoice_types->inv_type == 2)
            {
                $tmpTd = 1;
            }elseif ($data->cl_invoice_types->inv_type == 3)
            {
                $tmpTd = 0;
            }else{
                $tmpTd = 9;
            }

            if (!is_null($data['cl_bank_accounts_id']))
			{
				
				$tmpBankAcc = $data->cl_bank_accounts->iban_code;
				$tmpBank[] = $data->cl_bank_accounts;
			}else {
				if ($tmpBank = $this->BankAccountsManager->findBy(array('default_account' => 1, 'cl_currencies_id' => $data->cl_currencies_id))->limit(1)->fetch()) {
					$tmpBankAcc = $tmpBank->iban_code;
					$tmpBank = $this->BankAccountsManager->findBy(array('show_invoice' => 1, 'cl_currencies_id' => $data->cl_currencies_id))->order('default_account DESC');
				} else {
					if ($tmpBank = $this->BankAccountsManager->findAll()->order('default_account DESC')->limit(1)->fetch()) {
						$tmpBankAcc = $tmpBank->iban_code;
					} else {
						$tmpBankAcc = NULL;
					}
					$tmpBank = $this->BankAccountsManager->findBy(array('show_invoice' => 1))->order('default_account DESC');
				}
			}
            
            
            if (!is_null($tmpBankAcc) && $this->settings->print_qr)
            {
                //$this->qrService->setSize(15);
                $qrCode = $this->qrService->getQrInvoice(array(
                                                'am'		=> (int)$tmpPrice,
                                                'vs'		=> $data->var_symb,
                                                'dt'		=> $data->due_date,
                                                'cc'		=> $data->cl_currencies->currency_code,
                                                'acc'		=> $tmpBankAcc,
                                                'id'		=> $data->inv_number,
                                                'msg'		=> $tmpMsg,
                                                'dd'		=> $data->inv_date,
                                                'tp'		=> 0,
                                                'vii'		=> $this->settings->dic,
                                                'ini'		=> $this->settings->ico,
                                                'vir'		=> $data->cl_partners_book->dic,
                                                'inr'		=> $data->cl_partners_book->ico,
                                                'dppd'		=> $tmpDppd,
                                                'on'		=> $data->od_number,
                                                'sa'		=> $tmpSA,
                                                'td'		=> $tmpTd,
                                                'tb0'		=> $data->price_base1,
                                                't0'		=> $data->price_vat1,
                                                'tb1'		=> $data->price_base2,
                                                't1'		=> $data->price_vat2,
                                                'tb2'		=> $data->price_base3,
                                                't2'		=> $data->price_vat3,
                                                'ntb'		=> $data->price_base0,
                                                'fx'		=> $data->currency_rate,
                                                'fxa'		=> $data->cl_currencies->amount
                    ));
            }else{
                $qrCode = NULL;
            }

            $arrData = array('settings' => $this->settings,
                     'stamp' => $this->getStamp(),
                     'logo' => $this->getLogo(),
                     'RatesVatValid' => $this->RatesVatManager->findAllValid($this->DataManager->find($data['id'])->inv_date),
                     'arrInvoiceVat' => $this->getArrInvoiceVat(),
                     'arrInvoicePay' => $this->getArrInvoicePay(),
                     'bankAccounts'  => $tmpBank,
                     'taxAdvancePayments' => $this->InvoiceAdvancePaymentsManager->findAll()->where(array('cl_invoice_advance_id' => $this->id,
                                           'pay_type' => 1, 'pay_vat' => 1)),
                    'qrCode' => $qrCode,
                    'latteIndex' => $latteIndex);

                //$invoiceTemplate = $this->createMyTemplate($data,$tmpTemplateFile,$tmpTitle,$tmpAuthor,$arrData);
                //$data = $this->DataManager->find($data['id']);
        return $arrData;
    }
    
   //validating of data from listgrid
    public function DataProcessListGridValidate($data)
    {
        $retVal = NULL;
        if (isset($data['cl_pricelist_id']) && isset($data['quantity']))
        {
            if ($data['cl_pricelist_id'] > 0 && $data['quantity'] < 0 && $this->settings->invoice_to_store == 1){
                $retVal = $this->translator->translate('Množství pro výdej nesmí být záporné, pokud jde o položku ceníku.');
            }
        }

        return $retVal;
    }    
    
    public function handleShowTextsUse()
    {
        //bdump('ted');
        $this->redrawControl('textUseControl');
        $this->redrawControl('textsUse');
        $this->pairedDocsShow = TRUE;
        $this->showModal('textsUseModal');

        //$this->redrawControl('contents');
    }

    public function handleShowEETModalWindow()
    {
        $this->createDocShow = TRUE;
        $this->showModal('showEETModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleSendEET()
    {
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData['price_e2'] == 0 && $tmpData['price_e2_vat'] == 0){
            $this->flashMessage($this->translator->translate('Doklad má nulovou částku. Není možné jej odeslat do EET.'), 'danger');
        }elseif (!is_null($tmpData->cl_payment_types_id) && ($tmpData->cl_payment_types->payment_type == 1  || $tmpData->cl_payment_types->eet_send == 1)) {
            //send to EET
            $dataForSign = $this->CompaniesManager->getDataForSignEET($tmpData['cl_company_branch_id']);
            try {
            	//bdump($dataForSign);
                $arrRet = $this->EETService->sendEET($tmpData, $dataForSign);
                //bdump($arrRet);
                $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                $tmpData->update(array('cl_eet_id' => $tmpId));
            } catch (\Exception $e) {
                $this->flashMessage($this->translator->translate('Chyba certifikátu. Zkontrolujte nahraný certifikát a heslo'), 'danger');
                $this->flashMessage($e->getMessage(), 'danger');
            }
        }else{
            $this->flashMessage($this->translator->translate('Doklad není hrazen v hotovosti. Není možné jej odeslat do EET.'), 'danger');
        }
        //$this->redrawControl('baselistArea');
        $this->redrawControl('content');

    }


    public function handleNew($data = '', $defData)
    {

        //07.07.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;

/*        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        if ($tmpBranch) {
            $this->numberSeries['cl_number_series_id'] = $tmpBranch->cl_number_series_id_invoice;
        }
*/
        // bdump($this->numberSeries);
        if (intval($data) > 0){
            $this->numberSeries['cl_number_series_id'] = $data;
            $data = '';
        }else{
            $this->numberSeries['use'] = $data;
        }
        parent::handleNew($data,$defData);
    }
	
	
	
	protected function createComponentExportInvoiceBook($name)
	{
		$form = new Form($this, $name);
		//$form->setMethod('POST');
		$form->addHidden('id',NULL);
		
		$now = new \Nette\Utils\DateTime;
		if ($this->settings->platce_dph)
		{
			$lcText1 = $this->translator->translate('DUZP od');
			$lcText2 = $this->translator->translate('DUZP do');
		}else{
			$lcText1 = $this->translator->translate('Vystaveno od');
			$lcText2 = $this->translator->translate('Vystaveno do');
		}
		$form->addText('date_from', $lcText1, 0, 16)
			->setDefaultValue('01.'.$now->format('m.Y'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Datum začátek'));
		
		$form->addText('date_to', $lcText2, 0, 16)
			->setDefaultValue($now->format('d.m.Y'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Datum konec'));
	
		$tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->order('status_name')->fetchPairs('id','status_name');
		$form->addMultiSelect('cl_status_id',$this->translator->translate('Stav dokladu'), $tmpArrStatus)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte stav'));
		
		$tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_center_id',$this->translator->translate('Středisko'), $tmpArrCenter)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte středisko'));
		
		$tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
		$form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte obchodníka pro tisk'));
		
		$arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_payment_types_id',$this->translator->translate('Forma úhrady'),$arrPay)
			->setHtmlAttribute('multiple','multiple')
			->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte formu úhrady pro tisk'));
		

		
		$form->addCheckbox('after_due_date', $this->translator->translate('Po splatnosti'));
		$form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
		$form->addCheckbox('payed', $this->translator->translate('Zaplacené'));
		
		$form->addSubmit('save_xml', $this->translator->translate('Exportovat'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		
		$form->addSubmit('back', $this->translator->translate('Návrat'))
			->setHtmlAttribute('class','btn btn-sm btn-primary')
			->setValidationScope([])
			->onClick[] = array($this, 'stepBackExportBook');
		$form->onSuccess[] = array($this, 'SubmitExportBookSubmitted');

		return $form;
	}
	
	public function stepBackExportBook()
	{
		$this->rptIndex = 1;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
	}
	
	public function SubmitExportBookSubmitted(Form $form)
	{
		$data=$form->values;
		//dump(count($data['cl_partners_book']));
		//die;
		if ($form['save_xml']->isSubmittedBy() )
		{
			
			$data['cl_partners_branch'] = array();
			$tmpPodm = array();
			if ($data['after_due_date'] == 1)
			{
				if ($this->settings->platce_dph)
					$tmpPodm = 'cl_invoice_advance.due_date < NOW() AND cl_invoice_advance.price_payed < cl_invoice_advance.price_e2_vat';
				else
					$tmpPodm = 'cl_invoice_advance.due_date < NOW() AND cl_invoice_advance.price_payed < cl_invoice_advance.price_e2';
			}
			
			if ($data['not_payed'] == 1)
			{
				if ($this->settings->platce_dph)
					$tmpPodm = 'cl_invoice_advance.price_payed < cl_invoice_advance.price_e2_vat';
				else
					$tmpPodm = 'cl_invoice_advance.price_payed < cl_invoice_advance.price_e2';
			}
			
			if ($data['payed'] == 1)
			{
				if ($this->settings->platce_dph)
					$tmpPodm = 'cl_invoice_advance.price_payed >= cl_invoice_advance.price_e2_vat';
				else
					$tmpPodm = 'cl_invoice_advance.price_payed >= cl_invoice_advance.price_e2';
			}
			
			
			if ($data['date_to'] == "")
				$data['date_to'] = NULL;
			else
			{
				//$tmpDate = new \Nette\Utils\DateTime;
				//$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
				//date('Y-m-d H:i:s',strtotime($data['date_to']));
				$data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
			}
			
			if ($data['date_from'] == "")
				$data['date_from'] = NULL;
			else
				$data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from']));
			
			if ($this->settings->platce_dph)
			{
				$dataReport = $this->InvoiceAdvanceManager->findAll()->
											where($tmpPodm)->
											where('cl_invoice_advance.inv_date >= ? AND cl_invoice_advance.inv_date <= ?', $data['date_from'], $data['date_to'])->
											order('cl_invoice_advance.inv_number ASC, cl_invoice_advance.inv_date ASC');
			}else{
				$dataReport = $this->InvoiceAdvanceManager->findAll()->
											where($tmpPodm)->
											where('cl_invoice_advance.inv_date >= ? AND cl_invoice_advance.inv_date <= ?', $data['date_from'], $data['date_to'])->
											order('cl_invoice_advance.inv_number ASC, cl_invoice_advance.inv_date ASC');
			}

			if (count($data['cl_status_id']) > 0)
			{
				$dataReport = $dataReport->where(array('cl_status_id' =>  $data['cl_status_id']));
			}
			

			
			if (count($data['cl_center_id']) > 0)
			{
				$dataReport = $dataReport->where(array('cl_invoice_advance.cl_center_id' =>  $data['cl_center_id']));
			}
			
			if (count($data['cl_users_id']) > 0)
			{
				$dataReport = $dataReport->
				where(array('cl_invoice_advance.cl_users_id' =>  $data['cl_users_id']));
			}
			
			if (count($data['cl_payment_types_id']) > 0)
			{
				$dataReport = $dataReport->
				where(array('cl_invoice_advance.cl_payment_types_id' =>  $data['cl_payment_types_id']));
			}
			$dataReport = $dataReport->select('cl_partners_book.company,cl_status.status_name,cl_currencies.currency_code,cl_invoice_types.name AS "druh faktury",cl_payment_types.name AS "druh platby",cl_invoice_advance.*');
            $tmpArrVat = $this->getVats($dataReport);

            // zadejte ICO
            $pohoda = new Pohoda\Export($this->settings->ico);
            try {
                foreach($dataReport as $key => $one) {
                    if ($this->settings->platce_dph == 1) {
                        $validRates = $this->RatesVatManager->findAllValid($one->inv_date);
                    }else{
                        $validRates = array();
                    }

                    // cislo faktury
                    $invoice = new Pohoda\Invoice($one->inv_number);
                    $invoice->setType(Pohoda\Invoice::ADVANCE_TYPE);
                    // cena faktury s DPH (po staru) - volitelně
                    $invoice->setText($one->inv_title);
                    //$invoice->setText('zalo ...');
                    //$price = 1000;
                    $arrPrice = array();
                    foreach ($validRates as $keyR => $oneR){
                        for ($x = 0; $x <= 3; $x++){
                            if ($x == 0 && $one['price_base0'] != 0){
                                $arrPrice['zakl_dan0']	= $one['price_base0'];
                            }elseif (isset($one['vat'.$x]) && $one['vat'.$x] != $oneR['rates']) {
                                $arrPrice['zakl_dan'.$x]	= $one['price_base'.$x];
                                $arrPrice['dan'.$x]		    = $one['price_vat'.$x];
                            }
                        }
                    }

                    if ($one['price_e2'] == 0) {
                        $invoice->setPriceNone($one['price_e2_vat']);
                    }else{
                        //nizsi sazba dph
                        $invoice->setPriceLow($arrPrice['zakl_dan2']); //cena bez dph ve snizene sazbe
                        $invoice->setPriceLowVAT($arrPrice['dan2']); //samotna dan
                        $invoice->setPriceLowSum($arrPrice['zakl_dan2'] + $arrPrice['dan2']); // cena s dph ve snizene sazba

                        //druha nizsi sazba dph
                        $invoice->setPriceLow3($arrPrice['zakl_dan3']); //cena bez dph ve snizene sazbe
                        $invoice->setPriceLow3VAT($arrPrice['dan3']); //samotna dan
                        $invoice->setPriceLow3Sum($arrPrice['zakl_dan3'] + $arrPrice['dan3']); //cena s dph v druhe snizene sazbe

                        //nebo vyssi sazba dph
                        $invoice->setPriceHigh($arrPrice['zakl_dan1']); //cena bez dph ve zvysene sazbe
                        $invoice->setPriceHightVAT($arrPrice['dan1']); //samotna dan
                        $invoice->setPriceHighSum($arrPrice['zakl_dan1'] + $arrPrice['dan1']); //cena s dph ve zvysene sazbe

                    }
                    $invoice->setPriceRound(0);
                    $invoice->setWithVat($one->export == 0); //viz inv:classificationVAT - true nastavi cleneni dph na inland - tuzemske plneni, jinak da nonSubsume - nezahrnovat do DPH

                    //$invoice->setActivity('eshop'); //cinnost v pohode [volitelne, typ:ids]
                    //$invoice->setCentre('stredisko'); //stredisko v pohode [volitelne, typ:ids]
                    if (!is_null($one->cl_commission_id)) {
                        $invoice->setContract($one->cl_commission->cm_number); //zakazka v pohode [volitelne, typ:ids]
                    }

                    //nebo pridanim polozek do faktury (nove)
                    //$invoice->setText('Faktura za zboží');
                    //polozky na fakture
                    $tmpCount = count($one->related('cl_invoice_advance_items'));
                    if ($tmpCount == 0){
                        $item = new Pohoda\InvoiceItem();
                        $item->setText($one['inv_title']);
                        $item->setQuantity(1); //pocet
                        //$item->setUnit(''); //jednotka
                        //$item->setNote($oneItem->description1. " " . $oneItem->description2); //poznamka
                        //$item->setStockItem(230); //ID produktu v Pohode
                        //nastaveni ceny je volitelne, Pohoda si umi vytahnout cenu ze sve databaze pokud je nastaven stockItem

                       if ($one['price_e2'] == 0)
                       {
                         $item->setUnitPrice($one['price_e2_vat']); //cena
                         $item->setRateVAT($item::VAT_NONE); //0%
                       }else{
                           $item->setRateVAT($item::VAT_HIGH); //21%
                           $item->setRateVAT($item::VAT_LOW); //15%
                           $item->setRateVAT($item::VAT_THIRD); //10%
                       }



                        $item->setPayVAT(TRUE); //cena bez dph

                        $invoice->addItem($item);

                    }else{
                        foreach ($one->related('cl_invoice_advance_items')->order('item_order') as $keyItem => $oneItem){
                            $item = new Pohoda\InvoiceItem();
                            $item->setText($oneItem->item_label);
                            $item->setQuantity($oneItem->quantity); //pocet
                            if (!is_null($oneItem->cl_pricelist_id)) {
                                $item->setCode($oneItem->cl_pricelist->identification); //katalogove cislo
                            }
                            $item->setUnit($oneItem->units); //jednotka
                            $item->setNote($oneItem->description1. " " . $oneItem->description2); //poznamka
                            //$item->setStockItem(230); //ID produktu v Pohode
                            //nastaveni ceny je volitelne, Pohoda si umi vytahnout cenu ze sve databaze pokud je nastaven stockItem
                            $item->setUnitPrice($oneItem->price_e); //cena

                            if ($oneItem->vat == 21 )
                                $item->setRateVAT($item::VAT_HIGH); //21%
                            elseif ($oneItem->vat == 15 )
                                $item->setRateVAT($item::VAT_LOW); //15%
                            elseif ($oneItem->vat == 10 )
                                $item->setRateVAT($item::VAT_THIRD); //10%
                            elseif ($oneItem->vat == 0 )
                                $item->setRateVAT($item::VAT_NONE); //21%

                            $item->setPayVAT($oneItem->price_e_type == 1); //cena bez dph

                            $invoice->addItem($item);
                        }
                    }


                    // variabilni cislo
                    $invoice->setVariableNumber($one->var_symb);
                    // datum vytvoreni faktury
                    $invoice->setDateCreated($one->inv_date);
                    // datum zdanitelneho plneni
                    //$invoice->setDateTax($one->vat_date);
                    // datum splatnosti
                    $invoice->setDateDue($one->due_date);
                    //datum vytvoreni objednavky
                    //$invoice->setDateOrder('2014-01-24');

                    //středisko
                    if (!is_null($one->cl_center_id)) {
                        $invoice->setCentre($one->cl_center->name);
                    }

                    //cislo objednavky v eshopu
                    $invoice->setNumberOrder($one->od_number);

                    // nastaveni identity dodavatele
                    $invoice->setProviderIdentity([
                        "company" => $one->cl_company->name,
                        "city" => $one->cl_company->city,
                        "street" => $one->cl_company->street,
                        "number" => "",
                        "zip" => (int)str_replace(" ","", $one->cl_company->zip),
                        "ico" => $one->cl_company->ico,
                        "dic" => $one->cl_company->dic
                    ]);

                    // nastaveni identity prijemce
                    if (!is_null($one->cl_partners_book_id)){
                        $customer = [
                            "company" => $one->cl_partners_book->company,
                            "city" => $one->cl_partners_book->city,
                            "street" => $one->cl_partners_book->street,
                            "number" => "",
                            "zip" => (int)str_replace(" ","", $one->cl_partners_book->zip),
                            "ico" => $one->cl_partners_book->ico,
                            "dic" => $one->cl_partners_book->dic
                        ];
                    }else{
                        $customer = array();
                    }

                    //"icDph" => "SK....", //volitelne, v pripade slovenskeho platce dph

                    /*$customerAddress =
                        new Pohoda\Export\Address(
                            new Pohoda\Object\Identity(
                                "z125", //identifikator zakaznika [pokud neni zadan, nebude propojen s adresarem]
                                new Pohoda\Object\Address($customer), //adresa zakaznika
                                new Pohoda\Object\Address(["street" => "Pod Mostem"]) //pripadne dodaci adresa
                            )
                        );
                    $invoice->setCustomerAddress($customerAddress);
                    */

                    // nebo jednoduseji identitu nechat vytvorit
                    //$customerAddress = $invoice->createCustomerAddress($customer, "z125", ["street" => "Pod Mostem"]);
                    $customerAddress = $invoice->createCustomerAddress($customer);

                    if ($invoice->isValid()) {
                        // pokud je faktura validni, pridame ji do exportu
                        $pohoda->addInvoice($invoice);
                        //pokud se ma importovat do adresare
                        $pohoda->addAddress($customerAddress);
                    } else {
                        var_dump($invoice->getErrors());
                    }
                }

                // ulozeni do souboru
                $errorsNo = 0; // pokud si pocitate chyby, projevi se to v nazvu souboru
                //$pohoda->setExportFolder(__DIR__ ); //mozno nastavit slozku, do ktere bude proveden export
                //$pohoda->exportToFile(time(), 'popis', date("Y-m-d_H-i-s"), $errorsNo);
                // vypsani na obrazovku jako XML s hlavickou

                //Debugger::$showBar = false;
                //$pohoda->exportAsXml(time(), 'popis', date("Y-m-d_H-i-s"));
                //die;


                $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
                $subFolder  = $this->ArraysManager->getSubFolder(array(), 'cl_invoice_advance_id');
                $destFile   =  $dataFolder . '/' . $subFolder;  // . '/' . 'invoice_export.xml';
                $pohoda->setExportFolder($destFile); //mozno nastavit slozku, do ktere bude proveden export

                $file = $pohoda->exportToFile(time(), 'Trynx', 'invoice_advance_export', $errorsNo);
                // vypsani na obrazovku jako XML s hlavickou
                //Debugger::$showBar = false;s
                //$pohoda->exportAsXml(time(), 'popis', date("Y-m-d_H-i-s"));

                $httpResponse = $this->getHttpResponse();
                $httpResponse->setHeader('Pragma', "public");
                $httpResponse->setHeader('Expires', 0);
                $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
                $httpResponse->setHeader('Content-Transfer-Encoding', "binary");
                $httpResponse->setHeader('Content-Description', "File Transfer");
                $httpResponse->setHeader('Content-Length', filesize($file));
                //$this->sendResponse(new DownloadResponse($file, basename($file) , array('application/octet-stream', 'application/force-download', 'application/download')));
                $this->sendResponse(new \Nette\Application\Responses\FileResponse($file, basename($file), 'application/download'));


            } catch (Pohoda\InvoiceException $e) {
                $this->flashMessage($e->getMessage(), 'error');
            } catch (\InvalidArgumentException $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }




		}
	}

	public function handleMakeInvoice($type = 1)
    {
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData && round($tmpData['price_payed'],0) == round($tmpData['price_e2_vat'],0)){
            $newInvoice = $this->InvoiceManager->createInvoiceFromAdvance($tmpData, $type);
            $tmpData->update(['cl_invoice_id' => $newInvoice]);
            $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $newInvoice, 'cl_invoice_advance_id' => $this->id]);
            $this->flashMessage($this->translator->translate('Faktura_byla_vytvořena_nebo_zaktualizována'), 'success');
            $this->redirect(':Application:Invoice:edit', array('id' => $newInvoice));

        }else{
            $this->flashMessage($this->translator->translate('Zálohová_faktura_není_celá_uhrazena_Konečnou_fakturu_není_možné_vytvořit'), 'danger');
        }
        $this->redrawControl('content');

    }


    public function beforeCopy($data)
    {
        parent::beforeCopy($data);
        $tmpNow = new \Nette\Utils\DateTime;
        $data['inv_date'] = $tmpNow;
        $data['vat_date'] = $tmpNow;
        $dateDiff = $data['due_date']->diff($data['inv_date']);
        $days = $dateDiff->format('%d');
        $data['due_date'] = $tmpNow->modifyClone('+ ' . $days . 'day');
        $data['pay_date'] = NULL;
        $data['locked'] = 0;
        $data['price_payed'] = 0;
        $data['base_payed0'] = 0;
        $data['base_payed1'] = 0;
        $data['base_payed2'] = 0;
        $data['base_payed3'] = 0;
        $data['vat_payed1'] = 0;
        $data['vat_payed2'] = 0;
        $data['vat_payed3'] = 0;
        $data['advance_payed'] = 0;
        $data['cl_documents_id'] = NULL;
        $data['cl_store_docs_id'] = NULL;
        $data['cl_store_docs_id_in'] = NULL;
        $data['cl_commission_id'] = NULL;
        $data['cl_invoice_id'] = NULL;  //10.02.2023 - oprava Juggova
        $data['cl_offer_id'] = NULL;
        $data['cl_delivery_note_id'] = NULL;
        $data['storno'] = 0;
        $data['correction_inv_number'] = '';
        $data['delivery_number'] = '';
        $data['od_number'] = '';

        if ($data['cl_currencies_id'] != $this->settings['cl_currencies_id']) {
            $date = new \Nette\Utils\DateTime;
            $tmpCurrency = $this->CurrenciesManager->findAll()->where('id = ?', $data['cl_currencies_id'])->fetch();
            if ($tmpCurrency) {
                $cnb = new \CnbApi\CnbApi(__DIR__ . '/../../../temp');
                $tmpCurr = $cnb->findRateByCode($tmpCurrency['currency_code'], $date);
                $numRate = $tmpCurr->getRate();
                $data['currency_rate'] = $numRate;
            }
        }

        return $data;
    }



        
}



