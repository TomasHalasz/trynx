<?php

namespace App\ApplicationModule\Presenters;

use App\APIModule\Presenters\Array2XML;
use App\Controls;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use App\EDI;
use Netpromotion\Profiler\Profiler;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class StorePresenter extends \App\Presenters\BaseListPresenter
{


    const
        DEFAULT_STATE = 'Czech Republic';


    public $newId = NULL, $pairedDocsShow = FALSE, $createDocShow = FALSE;
    public $filterStoreUsed = array();


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
     * @var \App\Model\StoreDocsManager
     */
    public $DataManager;


    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;


    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

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
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;


    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

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
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;


    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $InvoiceArrivedManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;


    /**
     * @inject
     * @var \App\Model\InvoiceItemsManager
     */
    public $InvoiceItemsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceTypesManager
     */
    public $InvoiceTypesManager;

    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;

    /**
     * @inject
     * @var \App\Model\PricesManager
     */
    public $PricesManager;


    /**
     * @inject
     * @var \App\Model\PriceListGroupManager
     */
    public $PriceListGroupManager;


    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $PairedDocsManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteManager
     */
    public $DeliveryNoteManager;


    /**
     * @inject
     * @var \App\Model\DeliveryNoteItemsManager
     */
    public $DeliveryNoteItemsManager;

    /**
     * @inject
     * @var \App\Model\StoragePlacesManager
     */
    public $StoragePlacesManager;

    /**
     * @inject
     * @var \App\Model\OrderItemsManager
     */
    public $OrderItemsManager;

    /**
     * @inject
     * @var \App\Model\OrderManager
     */
    public $OrderManager;

    /**
     * @inject
     * @var \App\Model\PriceListBondsManager
     */
    public $PriceListBondsManager;

    /**
     * @inject
     * @var \App\Model\TransportTypesManager
     */
    public $TransportTypesManager;

    protected function createComponentChangeStoragePlace()
    {

        //$translator = clone $this->translator;
        //$translator->setPrefix([]);

        $control = new ChangeStoragePlaceControl($this->StoreMoveManager, $this->StoragePlacesManager, $this->itemId,
            $this->translator);

        $control->onChange[] = function ($item_id) {
            $this->afterChangeStorage($item_id);
        };

        return $control;
    }


    protected function createComponentPairedDocs()
    {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }

    protected function createComponentEditTextDescription()
    {
        return new Controls\EditTextControl(
            $this->translator, $this->DataManager, $this->id, 'description_txt');
    }

    protected function createComponentBulkInsert()
    {
        if ($data = $this->DataManager->findBy(array('id' => $this->id))->fetch()) {
            if ($data->doc_type == 0) {
                return new Controls\BulkInsertControl(
                    $this->translator,
                    $this->session,
                    $this->DataManager,
                    $this->id,
                    $this->PriceListManager,
                    TRUE,
                    'Nákupní cena',
                    FALSE);
            } elseif ($data->doc_type == 1) {
                return new Controls\BulkInsertControl(
                    $this->translator,
                    $this->session,
                    $this->DataManager,
                    $this->id,
                    $this->PriceListManager);
            }
        } else {
            return new Controls\BulkInsertControl(
                $this->translator, $this->session,
                $this->DataManager,
                $this->id,
                $this->PriceListManager);
        }

    }

    protected function createComponentSumOnDocs()
    {
        ////$this->translator->setPrefix(['applicationModule.Store']);
        if ($data = $this->DataManager->findBy(['id' => $this->id])->fetch()) {
            if ($data->cl_currencies) {
                $tmpCurrencies = $data->cl_currencies->currency_name;
            }

            if ($this->settings->platce_dph) {
                if ($data->doc_type == 0) {
                    $tmpPriceNameBase = $this->translator->translate("Nákup_bez_DPH");
                    $tmpPriceNameVat = $this->translator->translate("Nákup_s_DPH");
                } else {
                    $tmpPriceNameS = $this->translator->translate("Skladová_cena");
                    $tmpPriceNameBase = $this->translator->translate("Prodej_bez_DPH");
                    $tmpPriceNameVat = $this->translator->translate("Prodej_s_DPH");
                }
            } else {
                if ($data->doc_type == 0) {
                    $tmpPriceNameBase = $this->translator->translate("Celkem");
                    $tmpPriceNameVat = "";
                } else {
                    $tmpPriceNameS = $this->translator->translate("Skladová_cena");
                    $tmpPriceNameBase = $this->translator->translate("Celkem");
                    $tmpPriceNameVat = "";
                }
            }
            $tmpPriceE2Base = 0;
            $tmpPriceE2Vat = 0;

            if ($data->doc_type == 0) {
                $dataArr = [
                    ['name' => $tmpPriceNameBase, 'value' => $data->price_in, 'currency' => $tmpCurrencies],
                    ['name' => $tmpPriceNameVat, 'value' => $data->price_in_vat, 'currency' => $tmpCurrencies],
                ];
            } else {
                //výdejka
                $dataArr = [
                    ['name' => $tmpPriceNameS, 'value' => $data->price_s, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate("Zisk") . " " . round($data->profit, 1) . "%", 'value' => $data->profit_abs, 'currency' => $tmpCurrencies],
                    ['name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies],
                    ['name' => $tmpPriceNameVat, 'value' => $data->price_e2_vat, 'currency' => $tmpCurrencies],
                ];
            }

            if (!is_null($data->cl_transport_types_id)) {
                $dataArr = [
                    ['name' => $this->translator->translate('Doprava'), 'value' => $data->transport_km * $data->cl_transport_types->price_km * $data->currency_rate , 'currency' => $tmpCurrencies]];
            }

        } else {
            $dataArr = [];
        }

        return new SumOnDocsControl(
            $this->translator, $this->DataManager, $this->id, $this->settings, $dataArr);
    }

    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        return new Controls\FilesControl(
            $this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_store_docs_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    protected function createComponentStoreListgrid()
    {
        $tmpDatap = $this->DataManager->find($this->id);

        $arrStore = $this->StorageManager->getStoreTreeNotNested();

        //dump($arrStore);
        //$tmpStorage = $this->StorageManager->findAll()->fetchPairs('id','name');
        //$tmpStorage = $this->findAll()->where('cl_storage_id IS NULL')->order('name')->fetchPairs('id','name');
        //$arrStore = $tmpStorage;
        $arrPackages = $this->PriceListManager->findAll()->where('cl_pricelist_group.is_return_package=?',1)
                                                        ->select('item_label, CONCAT(item_label," - ", identification) AS item_label2')
                                                        ->order('item_label')
                                                        ->fetchPairs('item_label','item_label2');
        //	die;
        if ($tmpDatap->doc_type == 0) {
            $arrData = ['cl_pricelist.identification' => ['Kód', 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'cl_pricelist.item_label' => ['Název', 'format' => 'text', 'size' => 15, 'readonly' => TRUE],
                'cl_storage.name' => ['Sklad', 'format' => 'chzn-select-req',
                    'values' => $arrStore, 'required' => 'Vyberte sklad',
                    'size' => 7, 'roCondition' => '$defData["changed"] != NULL'],
                'cl_store.quantitys' => ['Skladem', 'format' => "number", 'size' => 7, 'readonly' => TRUE, 'decplaces' => $this->settings->des_mj,  'function' => 'calcQuantity', 'function_param' => ['cl_pricelist_id', 'cl_storage_id']],
                's_in' => ['Příjem', 'format' => "number",
                    'size' => 8,
                    'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$this->presenter->StoreOutManager->getOutsForIn($defData["id"]) != FALSE'],
                'cl_pricelist.unit' => ['', 'format' => 'text', 'size' => 5, 'readonly' => TRUE, 'e100p' => "false"],
                'weight_brutto' => ['Celkem[kg]', 'format' => 'number', 'size' => 15,  'e100p' => "true"],                                                
                'package_name' => ['Obal', 'format' => 'chzn-select-req',
                    'values' => $arrPackages, 'required' => 'Vyberte obal',
                    'size' => 12],
                'weight_pack' => ['Obal[kg]', 'format' => 'number', 'size' => 6, 'readonly' => TRUE, 'e100p' => "true"],                                                
                'price_in' => ['Nákup bez DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_in_vat' => ['Nákup s DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'price_s' => ['Skladová cena', 'format' => "number", 'size' => 8, 'readonly' => TRUE, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'price_e2' => ['Celkem bez DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'vat' => ['DPH %', 'format' => 'number', 'size' => 8, 'readonly' => TRUE, 'e100p' => "false"],
                'price_e2_vat' => ['Celkem s DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'cl_pricelist.price' => ['Prodej bez DPH', 'format' => 'number', 'size' => 8, 'readonly' => TRUE, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'profit' => ['Zisk %', 'format' => 'number', 'size' => 8, 'readonly' => TRUE, 'e100p' => "false", 'function' => 'getProfitStoreIn', 'function_param' => ['cl_pricelist.price', 'price_s']],
                'exp_date' => ['Expirace', 'format' => 'date', 'size' => 15, 'required' => 'Expirace musí být zadána', 'newline' => TRUE, 'e100p' => "false"],              
                'import' => ['Import', 'format' => 'boolean', 'size' => 5, 'readonly' => TRUE],
                'import_fin' => ['Nasklad', 'format' => 'boolean', 'size' => 5, 'readonly' => TRUE],
                'waste_code' => ['Kód odpadu', 'format' => 'number', 'size' => 30, 'newline' => TRUE, 'e100p' => "true"],                                                
                'batch' => ['Šarže', 'format' => 'text', 'size' => 20, 'newline' => TRUE, 'e100p' => "false"],
                'description' => ['Poznámka', 'format' => "text", 'size' => 70, 'newline' => TRUE, 'e100p' => "false"]];

//                                            'rules' => array( 'rule' => array('condition' => array(),
//                                                                            'rule' => array(Form::FILLED, 'Expirace musí být zadána', ''))),
//                'weight_netto' => ['Netto[kg]', 'format' => 'number', 'size' => 6,  'e100p' => "true"],                

            if ($this->StoragePlacesManager->findAll()->count() > 0) {
                $arrData['cl_storage_places_id__'] = ['Umístění', 'format' => 'text', 'size' => 20, 'readonly' => TRUE, 'newline' => TRUE, 'function' => 'getStoragePlaceName', 'function_param' => ['cl_storage_places'],
                                                'hidCondition' => ['data' => 'id', 'condition' => '>', 'value' => '0']];
                $arrData['arrTools'] = ['Nástroje', [1 => ['url' => 'customFunction!', 'type' => 'changePlace', 'rightsFor' => 'enable', 'label' => '', 'class' => 'btn btn-success btn-xs', 'title' => 'Změnit umístění ve skladu',
                    'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-screenshot']]];
            }

            if ($this->settings->use_package == 0){
                unset($arrData['package_name']);
                //unset($arrData['weight_netto']);
                unset($arrData['weight_pack']);
                unset($arrData['weight_brutto']);
            }




        } else {
            $arrData = ['cl_pricelist.identification' => ['Kód', 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'cl_pricelist.item_label' => ['Název', 'format' => 'text', 'size' => 15, 'readonly' => TRUE],
                'cl_storage.name' => ['Sklad', 'format' => 'chzn-select-req',
                    'values' => $arrStore, 'required' => 'Vyberte sklad',
                    'size' => 7, 'roCondition' => '$defData["changed"] != NULL'],
                'cl_store.quantitys' => ['Skladem', 'format' => "number", 'size' => 7, 'readonly' => TRUE, 'decplaces' => $this->settings->des_mj,  'function' => 'calcQuantity', 'function_param' => ['cl_pricelist_id', 'cl_storage_id']],
                's_out' => ['Výdej', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'cl_pricelist.unit' => ['', 'format' => 'text', 'size' => 5, 'readonly' => TRUE, 'e100p' => "false"],
                'package_name' => ['Obal', 'format' => 'chzn-select-req',
                    'values' => $arrPackages, 'required' => 'Vyberte obal',
                    'size' => 12],
                'weight_pack' => ['Obal[kg]', 'format' => 'number', 'size' => 6,  'readonly' => TRUE,  'e100p' => "true"],                                        
                'weight_brutto' => ['Celkem[kg]', 'format' => 'number', 'size' => 15, 'readonly' => TRUE, 'e100p' => "true"],                                                                                
                'price_s' => ['Skladová cena', 'format' => "number", 'size' => 8, 'readonly' => TRUE, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'profit' => ['Zisk %', 'format' => "number", 'size' => 7, 'e100p' => "false",
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'price_e' => ['Prodej', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'discount' => ['Sleva %', 'format' => "number", 'size' => 7, 'e100p' => "false",
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'price_e2' => ['Celkem bez DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => "false",
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'vat' => ['DPH %', 'format' => 'number', 'size' => 8, 'e100p' => "false",
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'price_e2_vat' => ['Celkem s DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => "false",
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'order_number' => ['Objednávka', 'format' => "text", 'size' => 12],
                'quantity_prices__' => ['množstevní ceny', 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_store_docs.cl_currencies_id', 'cl_pricelist.price', 'cl_store_docs.cl_partners_book_id']],
                'waste_code' => ['Kód odpadu', 'format' => 'number', 'size' => 30, 'newline' => TRUE, 'e100p' => "true"],                                                
                'description' => ['Poznámka', 'format' => "text", 'size' => 100, 'newline' => TRUE, 'e100p' => "false"]];
            if ($this->StoragePlacesManager->findAll()->count() > 0) {
                $arrData['cl_storage_places_id__'] = ['Umístění', 'format' => 'text', 'size' => 20, 'readonly' => TRUE, 'newline' => TRUE, 'function' => 'getStoragePlaceNameOut', 'function_param' => ['id']];
            }
            //'weight_netto' => ['Netto[kg]', 'format' => 'number', 'size' => 6,  'e100p' => "true"],                

            if ($this->settings->use_package == 0){
                unset($arrData['package_name']);
              //  unset($arrData['weight_netto']);
                unset($arrData['weight_pack']);
                unset($arrData['weight_brutto']);
            }

            if ($this->user->getIdentity()->store_manager == 0){
                unset($arrData['price_s']);
                unset($arrData['profit']);                
                unset($arrData['price_e']);
                unset($arrData['discount']);                
                unset($arrData['price_e2']);
                unset($arrData['price_e2_vat']);
            }


        }

        if (!$this->settings->exp_on) {
            unset($arrData['exp_date']);
        }
        if (!$this->settings->batch_on) {
            unset($arrData['batch']);
        }


        ////$this->translator->setPrefix(['components.listgrid']);
        $tlbr = [
            1 => ['group' =>
                [0 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.identification', 'cmpName' => 'storeListgrid']),
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('Kód_zboží'),
                    'title' => $this->translator->translate('Seřadí_podle_kódu_zboží'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => ''],
                    1 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.item_label', 'cmpName' => 'storeListgrid']),
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Název'),
                        'title' => $this->translator->translate('Seřadí_podle_názvu_položky'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                    2 => ['url' => $this->link('SortItems!', ['sortBy' => 'id', 'cmpName' => 'storeListgrid']),
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Výchozí_pořadí'),
                        'title' => $this->translator->translate('Seřadí_tak_jak_položky_vznikly'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Seřadit'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('seřadit'), 'group_icon' => 'iconfa-sort']
            ],
            2 => ['url' => $this->link('DeleteAll!'), 'rightsFor' => 'erase', 'data' => ['data-history=false'],
                'label' => 'Vymazat', 'title' => 'Vymaže importované položky (pokud ještě nebyly naskladněny)', 'class' => 'btn btn-warning', 'icon' => 'iconfa-eraser'],
            3 => ['url' => $this->link('ImportToStore!'), 'rightsFor' => 'erase', 'data' => ['data-history=false'],
                'label' => 'Naskladnit', 'title' => 'Naskladní importované položky', 'class' => 'btn btn-primary', 'icon' => 'iconfa-eraser'],
        ];

        if ($tmpDatap->doc_type == 1) {
            unset($tlbr[2]);
            unset($tlbr[3]);
        }


        $control = new Controls\ListgridControl(
            $this->translator,
            $this->StoreMoveManager,
            $arrData,
            [],
            $this->id,
            ['units' => $this->settings->def_mj], //default data
            $this->DataManager,
            $this->PriceListManager,
            $this->PriceListPartnerManager,
            FALSE,
            ['getQuantity' => $this->link('getQuant!'),
                'pricelist2' => $this->link('RedrawPriceList2!')
            ], //custom links
            TRUE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE, //pricelistbottom
            FALSE, //readonly
            FALSE,  //nodelete
            TRUE, //enableSearch
            'cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ? OR cl_store_move.description LIKE ?',
            NULL, //toolbar
            FALSE, //forceEnable
            FALSE, //paginatorOff
            [1 => ['conditions' => [1 => ['left' => 'minus', 'condition' => '==', 'right' => 1]
            ],
                'colour' => $this->RGBtoHex(255, 151, 151)], //yellow  -

            ], //colours conditions
            20, //pagelength
            'auto' //$containerHeight
        );

        $control->setToolbar($tlbr);

        $control->onChange[] = function () {
            $this->updateSum();
        };

        $control->onCustomFunction[] = function ($itemId, $type) {
            $this->customFunction($itemId, $type);
        };

        return $control;

    }


    public function calcQuantity($params){
        return $this->StoreManager->calcQuantity($params);
    }

    protected function createComponentStoreListgridSelect()
    {
        $tmpDatap = $this->DataManager->find($this->bscId);

        $arrStore = $this->StorageManager->getStoreTreeNotNested();

        if ($tmpDatap->doc_type == 0) {
            $arrData = ['cl_pricelist.identification' => ['Kód', 'format' => 'text', 'size' => 15, 'readonly' => TRUE],
                'cl_pricelist.item_label' => ['Název', 'format' => 'text', 'size' => 40, 'readonly' => TRUE],
                'vat' => ['DPH %', 'format' => 'number', 'size' => 8, 'readonly' => TRUE],
                'cl_storage.name' => ['Sklad', 'format' => 'chzn-select-req',
                    'values' => $arrStore, 'required' => 'Vyberte sklad',
                    'size' => 10, 'roCondition' => '$defData["changed"] != NULL'],
                'cl_store.quantitys' => ['Skladem', 'format' => "number", 'size' => 7, 'readonly' => TRUE, 'decplaces' => $this->settings->des_mj,  'function' => 'calcQuantity', 'function_param' => ['cl_pricelist_id', 'cl_storage_id']],
                's_in' => ['Příjem', 'format' => "number",
                    'size' => 8,
                    'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$this->presenter->StoreOutManager->getOutsForIn($defData["id"]) != FALSE'],
                'cl_pricelist.unit' => ['', 'format' => 'text', 'size' => 5, 'readonly' => TRUE],
                'price_in' => ['Nákup bez DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_in_vat' => ['Nákup s DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_s' => ['Skladová cena', 'format' => "number", 'size' => 8, 'readonly' => TRUE, 'decplaces' => $this->settings->des_cena],
                'description' => ['Poznámka', 'format' => "text", 'size' => 100, 'newline' => TRUE]];
        } else {
            $arrData = ['cl_pricelist.identification' => ['Kód', 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'cl_pricelist.item_label' => ['Název', 'format' => 'text', 'size' => 15, 'readonly' => TRUE],
                'cl_storage.name' => ['Sklad', 'format' => 'chzn-select-req',
                    'values' => $arrStore, 'required' => 'Vyberte sklad',
                    'size' => 8, 'roCondition' => '$defData["changed"] != NULL'],
                'cl_store.quantitys' => ['Skladem', 'format' => "number", 'size' => 7, 'readonly' => TRUE, 'decplaces' => $this->settings->des_mj,  'function' => 'calcQuantity', 'function_param' => ['cl_pricelist_id', 'cl_storage_id']],
                's_out' => ['Výdej', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'cl_pricelist.unit' => ['', 'format' => 'text', 'size' => 5, 'readonly' => TRUE],
                'price_s' => ['Skladová cena', 'format' => "number", 'size' => 8, 'readonly' => TRUE, 'decplaces' => $this->settings->des_cena],
                'profit' => ['Zisk %', 'format' => "number", 'size' => 7, 'e100p' => "false",
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'price_e' => ['Prodej', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'discount' => ['Sleva %', 'format' => "number", 'size' => 7,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'price_e2' => ['Celkem bez DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'vat' => ['DPH %', 'format' => 'number', 'size' => 8,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'price_e2_vat' => ['Celkem s DPH', 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena,
                    'roCondition' => '$this->presenter->madeFromInvoice($defData["id"]) == TRUE'],
                'description' => ['Poznámka', 'format' => "text", 'size' => 100, 'newline' => TRUE]];
        }


        $control = new Controls\ListgridControl(
            $this->translator,
            $this->StoreMoveManager,
            $arrData,
            [],
            $this->id,
            ['units' => $this->settings->def_mj], //default data
            $this->DataManager,
            NULL,
            $this->PriceListPartnerManager,
            FALSE,
            ['getQuantity' => $this->link('getQuant!'),
                'pricelist2' => $this->link('RedrawPriceList2!')
            ], //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            TRUE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            TRUE, //readonly
            TRUE, //readonly
            FALSE, //enablesearch
            [], //txtsearch condition
            [], //toolbar
            FALSE, //forceenable
            TRUE //paginatoroff
        );

        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Store']);
        $this->formName = $this->translator->translate("Příjem_a_výdej");
        $this->mainTableName = 'cl_store_docs';

        //$settings = $this->CompaniesManager->getTable()->fetch();
        $arrData = ['doc_number' => $this->translator->translate('Doklad'),
            'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
            'doc_type' => [$this->translator->translate('Typ'), 'arrValues' => [0 => 'příjem', 1 => 'výdej']],
            'doc_date' => [$this->translator->translate('Datum_pohybu'), 'format' => 'date'],
            'cl_partners_book.company' => [$this->translator->translate('Dodavatel_/_Odběratel'), 'format' => 'text', 'show_clink' => true],
            'doc_title' => $this->translator->translate('Popis'),
            'cl_storage.name' => $this->translator->translate('Sklad'),
            'cl_company_branch.name' => $this->translator->translate('Pobočka'),
            'cl_center.name' => $this->translator->translate('Středisko'),
            'price_in' => [$this->translator->translate('Nákup_celkem'), 'format' => 'currency'],
            'price_s' => [$this->translator->translate('Výdej_celkem'), 'format' => 'currency'],
            'price_e2' => [$this->translator->translate('Prodej_celkem'), 'format' => 'currency'],
            'profit' => [$this->translator->translate('Zisk_%'), 'format' => 'currency'],
            'profit_abs' => [$this->translator->translate('Zisk_abs'), 'format' => 'currency'],
            'minus' => [$this->translator->translate('Výdej_do_mínusu'), 'format' => 'boolean'],
            'currency_rate' => $this->translator->translate('Kurz'),
            'cl_currencies.currency_name' => $this->translator->translate('Měna'),
            'cl_invoice.inv_number' => [$this->translator->translate('Faktura_vydaná'), 'format' => 'text'],
            'cl_invoice_arrived.inv_number' => [$this->translator->translate('Faktura_přijatá'), 'format' => 'text'],
            'cl_sale.sale_number' => [$this->translator->translate('Prodejka'), 'format' => 'text'],
            'cl_delivery_note.dn_number' => [$this->translator->translate('Dodací_list_v'), 'format' => 'text'],
            'cl_commission.cm_number' => [$this->translator->translate('Zakázka'), 'format' => 'text'],
            'invoice_number' => $this->translator->translate('Faktura'),
            'delivery_number' => $this->translator->translate('Dodací_list_p.'),
            'weight_brutto' => [$this->translator->translate('Váha_brutto'), 'format' => 'number'],
            'weight_netto' => [$this->translator->translate('Váha_netto'), 'format' => 'number'],
            'cl_transport_types.name' => [$this->translator->translate('Doprava'), 'format' => 'text'],
            'transport_km' => [$this->translator->translate('Km'), 'format' => 'number'],
            'transport_price' => [$this->translator->translate('Cena_dopravy_vypočtená'), 'format' => 'currency', 'function' => 'getTransportPrice', 'function_param' => ['transport_km', 'cl_transport_types.price_km']],
            'delivery_price' => [$this->translator->translate('Cena_dopravy_zadaná'), 'format' => 'currency'],
            'manipulation' => [$this->translator->translate('Čas_manipulace'), 'format' => 'number'],
            'vehicle_plate' => ['RZ vozidla', 'format' => "text", 'size' => 8],              
            'trip_number' => ['RZ vozidla', 'format' => "text", 'size' => 8],                          
            'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];

        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = ['doc_number' => 'autocomplete', 'cl_partners_book.company' => 'autocomplete', 'cl_storage.name' => 'autocomplete',
            'doc_title' => 'autocomplete', 'invoice_number' => 'autocomplete', 'cl_sale.sale_number' => 'autocomplete', 'cl_delivery_note.dn_number' => 'autocomplete', 'delivery_number' => 'autocomplete',
            'cl_commission.cm_number' => 'autocomplete', 'minus' => 'none', 'weight_brutto' => 'none', 'weight_netto' => 'none', 'vehicle_plate' => 'autocomplete',
            'cl_status.status_name' => 'autocomplete', 'doc_type' => 'autocomplete', 'doc_date' => 'none', 'price_in' => 'none', 'price_e2' => 'none', 'cl_invoice.inv_number' => 'autocomplete'];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['doc_number', 'doc_title', 'cl_partners_book.company', 'cl_invoice.inv_number', 'invoice_number'];

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_store_move.description', ':cl_store_move.batch', ':cl_store_move.cl_pricelist.identification', ':cl_store_move.cl_pricelist.item_label'];

        $this->DefSort = 'doc_date DESC';


        $this->conditionRows = [['minus', '==', 1, 'color:red', 'lastcond']];

        /*array('pay_date', '==', NULL, 'color:red', 'lastcond'),
            array('due_date', '>=', $testDate, 'color:green', 'notlastcond'),
            array('pay_date', '==', NULL, 'color:green', 'lastcond')
        */


        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;

        //30.09.2019 - default storage for company branch
        $cl_storage_id = NULL;
        $tmpCompanyBranchId = $this->user->getIdentity()->cl_company_branch_id;
        if (!is_null($tmpCompanyBranchId)) {
            if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('id = ?', $tmpCompanyBranchId)->limit(1)->fetch())
                $cl_storage_id = $tmpBranch->cl_storage_id;
        } else {
            $cl_storage_id = $this->settings->cl_storage_id;
        }
        $cl_center_id = $this->CenterManager->findAll()->where('default_center = 1')->fetch();

        $this->defValues = ['doc_date' => new \Nette\Utils\DateTime,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'currency_rate' => $this->settings->cl_currencies->fix_rate,
            'cl_storage_id' => $cl_storage_id,
            'cl_center_id' => $cl_center_id,
            'cl_company_branch_id' => $this->user->getIdentity()->cl_company_branch_id];

        //$this->numberSeries = 'commission';
        $this->numberSeries = ['use' => ['store_in', 'store_out'], 'table_key' => 'cl_number_series_id', 'table_number' => 'doc_number'];
        $this->readOnly = ['doc_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE];

        //$this->showChildLink = 'PartnersEvent:default';
        //Condition for color highlit rows
        //$testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-30 day');
        //$this->conditionRows = array( 'cdate','<=',$testDate);

        /*$this->toolbar = array(	0 => array('group_start' => ''),
                                    1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový dodací list', 'class' => 'btn btn-primary'),
                                    2 => $this->getNumberSeriesArray('delivery_note'),
                                    3 => array('group_end' => ''));
        */
        if ($tmpNS = $this->NumberSeriesManager->findAll()->where(['form_use' => 'store_in'])->order('form_default')->fetch())
            $tmpNSStore_in = $tmpNS->id;
        else
            $tmpNSStore_in = NULL;

        if ($tmpNS = $this->NumberSeriesManager->findAll()->where(['form_use' => 'store_out'])->order('form_default')->fetch())
            $tmpNSStore_out = $tmpNS->id;
        else
            $tmpNSStore_out = NULL;

        $this->toolbar = [0 => ['group_start' => ''],
            1 => ['url' => $this->link('new!', ['data' => 'store_in', 'defData' => json_encode(['cl_number_series_id' => $tmpNSStore_in])]), 'rightsFor' => '', 'label' => $this->translator->translate('Nový_příjem'), 'class' => 'btn btn-primary', 'data' => ['data-scroll-to=".btn-success"']],
            2 => $this->getNumberSeriesArray('store_in'),
            3 => ['group_end' => ''],
            4 => ['group_start' => ''],
            5 => ['url' => $this->link('new!', ['data' => 'store_out', 'defData' => json_encode(['cl_number_series_id' => $tmpNSStore_out])]), 'rightsFor' => '', 'label' => $this->translator->translate('Nový_výdej'), 'class' => 'btn btn-primary', 'data' => ['data-scroll-to=".btn-success"']],
            6 => $this->getNumberSeriesArray('store_out'),
            7 => ['group_end' => ''],
            8 => ['group' =>
                [0 => ['url' => $this->link('report!', ['index' => 1]),
                    'rightsFor' => 'report',
                    'label' => $this->translator->translate('Příjemky_v_období'),
                    'title' => $this->translator->translate('Přehled_příjemek_ve_zvoleném_období'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => 'iconfa-print'],
                    1 => ['url' => $this->link('report!', ['index' => 2]),
                        'rightsFor' => 'report',
                        'label' => $this->translator->translate('Výdejky_v_období'),
                        'title' => $this->translator->translate('Přehled_výdejek_ve_zvoleném_období'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-print'],
                    2 => ['url' => $this->link('report!', ['index' => 3]),
                        'rightsFor' => 'report',
                        'label' => $this->translator->translate('Odpady_v_období'),
                        'title' => $this->translator->translate('Přehled_výdejů_a_příjmů_odpadů_ve_zvoleném_období'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-print'],                        
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Tisk'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
            ]
        ];

        $this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/Store/ReportIncomeSettings.latte',
                                'reportName' => 'Příjemky '],
                        2 => ['reportLatte' => __DIR__ . '/../templates/Store/ReportOutcomeSettings.latte',
                                'reportName' => 'Výdejky '],
                        3 => ['reportLatte' => __DIR__ . '/../templates/Store/ReportWasteSettings.latte',
                                'reportName' => 'Odpady ']                                
                        ];

        $this->rowFunctions = ['copy' => 'disabled'];

        /*baselist child section
         * all bellow is for master->child show
         */
        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Store\card.latte'],
            'items' => ['active' => true, 'name' => $this->translator->translate('položky'), 'lattefile' => $this->getLattePath() . 'Store\items.latte'],
            'memos' => ['active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'Store\description.latte'],
            'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'Store\files.latte']
        ];
        $this->bscSums = ['lattefile' => $this->getLattePath() . 'Store\sums.latte'];

        $this->bscToolbar = [
            13 => ['group' =>
                    [1 => ['url' => 'savePDFpurchase!',
                        'rightsFor' => 'report',
                        'label' => $this->translator->translate('Potvrzení_výkupu'),
                        'title' => '',
                        'data' => ['data-ajax="false"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-print'],
                    ],
                    'group_settings' =>
                    ['group_label' => $this->translator->translate('Tisk'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('Tiskové_sestavy'), 'group_icon' => 'iconfa-print']
            ],
            1 => ['url' => 'bulkInsert!', 'urlparams' => ['keyname' => 'bscId', 'key' => 'id'], 'rightsFor' => 'write', 'label' => $this->translator->translate('H_položky'), 'title' => $this->translator->translate('Hromadné_vložení_položek_do_dokladu'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-barcode'],
            2 => ['url' => 'importEDI!', 'urlparams' => ['keyname' => 'bscId', 'key' => 'id'], 'rightsFor' => 'write', 'label' => 'EDI', 'title' => $this->translator->translate('Import_z_formátu_EDI'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-scroll-to="false"'], 'icon' => 'iconfa-plus'],
            3 => ['url' => 'ImportData:', 'urlparams' => ['keyname' => 'id', 'key' => 'id'],
                'urlparams2' => ['modal' => true, 'target' => $this->name],
                'rightsFor' => 'write', 'label' => 'CSV', 'title' => $this->translator->translate('Import_CSV'),
                'class' => 'btn btn-success modalClick',
                'data' => ['data-href', 'data-history="false"',
                    'data-title = "Import CSV"']],
            4 => ['url' => 'createDelivery!', 'urlparams' => ['keyname' => 'bscId', 'key' => 'id'], 'rightsFor' => 'write', 'label' => $this->translator->translate('dodací_list'), 'title' => $this->translator->translate('Vytvoří_z_výdejky_dodací_list'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'doc_type', 'condition' => '==', 'value' => '1']],
                'data' => ['data-ajax="true"', 'data-history="true"',
                    'data-confirm="Ano"', 'data-cancel="Ne"', 'data-prompt="' . $this->translator->translate("Opravdu_si_přejete_vytvořit_dodací_list") . '"'], 'icon' => 'glyphicon glyphicon-edit'],
            5 => ['url' => 'createInvoice!', 'urlparams' => ['keyname' => 'bscId', 'key' => 'id'], 'rightsFor' => 'write', 'label' => $this->translator->translate('fakturovat'), 'title' => $this->translator->translate('Vytvoří_z_výdejky_fakturu'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'doc_type', 'condition' => '==', 'value' => '1']],
                'data' => ['data-ajax="true"', 'data-history="false"',
                    'data-confirm="Ano"', 'data-cancel="Ne"', 'data-prompt="' . $this->translator->translate("Opravdu_si_přejete_vytvořit_fakturu") . '"'], 'icon' => 'glyphicon glyphicon-edit'],
            6 => ['group' =>
                [1 => ['url' => 'exportXML!',
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('export_XML'),
                    'title' => $this->translator->translate('exportuje_obsah_dokladu_do_XML'),
                    'class' => 'ajax', 'icon' => 'glyphicon iconfa-save',
                    'data' => ['data-ajax="false"', 'data-history="false"'],
                ],
                    2 => ['url' => 'importXML!',
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('import_XML'),
                        'title' => $this->translator->translate('importuje_obsah_dokladu_z_XML'),
                        'class' => 'ajax', 'icon' => 'glyphicon iconfa-save',
                        'data' => ['data-ajax="true"', 'data-history="false"']],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Export/Import'),
                        'group_class' => 'btn btn-success dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-edit']
            ],


            7 => ['url' => 'createIncomeModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('naskladnit'), 'title' => $this->translator->translate('Naskladní_zvolené_položky_výdejky_na_vybraný_sklad'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'doc_type', 'condition' => '==', 'value' => '1']],
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-log-in'],
            8 => ['url' => 'createOutgoingModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('vydat'), 'title' => $this->translator->translate('Vydá_zvolené_položky_příjemky'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'doc_type', 'condition' => '==', 'value' => '0']],
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-log-out'],
            9 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
            10 => ['url' => 'saveStorePlacement!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('umístěnka'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            11 => ['url' => 'savePDF!', 'urlparams' => ['keyname' => 'latteIndex', 'key' => 'doc_type'], 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            12 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save']
        ];

        $tmpData = $this->DataManager->find($this->id);
        //bdump($tmpData);
        if ($tmpData && $tmpData->doc_type == 1) {  /*0 - income , 1 - outcome */
            // unset($this->bscToolbar[1]);
            unset($this->bscToolbar[2]);
            unset($this->bscToolbar[3]);
            unset($this->bscToolbar[13]);
        }elseif ($tmpData && $tmpData->doc_type == 0) {

        }

        $this->bscTitle = ['doc_number' => $this->translator->translate('Číslo_dokladu'), 'cl_partners_book.company' => 'Partner', 'doc_title' => $this->translator->translate('Popis')];
        /*end of bsc section
         *
         */

        //04.07.2018 - settings for documents saving and emailing
        $this->docTemplate = [1 => $this->ReportManager->getReport(__DIR__ . '/../templates/Store/pdfStoreIn.latte'),
            2 => $this->ReportManager->getReport(__DIR__ . '/../templates/Store/pdfStoreOut.latte'),
            3 => $this->ReportManager->getReport(__DIR__ . '/../templates/Store/pdfPlacement.latte')
        ];
        $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $this->docTitle = [1 => [$this->translator->translate("Doklad_"), "doc_number"],
            2 => [$this->translator->translate("Doklad_"), "doc_number"],
            3 => [$this->translator->translate("Umístěnka"), "doc_number"],
        ];

        //14.02.2019 - filter for show only not used items to store income
        $this->filterStoreUsed = ['filter' => 'cl_store_docs_in_id IS NULL'];

        $arrValues_in = $this->StatusManager->findAll()->where('status_use = ?', 'store_in')->fetchPairs('id', 'status_name');
        $arrValues_out = $this->StatusManager->findAll()->where('status_use = ?', 'store_out')->fetchPairs('id', 'status_name');
        $arrValues = array_merge($arrValues_in, $arrValues_out);
        $this->quickFilter = ['cl_status.status_name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
            'values' => $arrValues]
        ];

        if ( $this->isAllowed($this->presenter->name,'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
        }

    }


    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {

        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);


        //dump($this->conditionRows);
        //die;
        //bdump($this->bscId,'bscid');
        //bdump($this->id,'id');
    }

    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);
        //$this->template->_form = $this['edit'];
        $this->template->getLatte()->addProvider('formsStack', [$this['edit']]);
    }


    protected function createComponentEdit($name)
    {

        if ($this->bscId == NULL) {
            $tmpDatap = $this->DataManager->find($this->id);
        } else {
            $tmpDatap = $this->DataManager->find($this->bscId);
        }


        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        //$form->addHidden('doc_type',$tmpDatap->doc_type);
        $form->addText('doc_number', $this->translator->translate('Doklad'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_zakázky'));
        $form->addText('doc_date', $this->translator->translate('Datum_pohybu'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_přijetí'))
            ->setHtmlAttribute('data-url-change_doc_date', $this->link('changeDocDate!'));
        $form->addText('doc_title', $this->translator->translate('Popis'), 150, 150)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis'));
        $form->addText('invoice_number', $this->translator->translate('Faktura_č'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_faktury'));
        $form->addText('delivery_number', $this->translator->translate('Dodací_list_č'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_dodacího_listu'));
        $form->addText('vehicle_plate', $this->translator->translate('RZ_vozidla'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('RZ_vozidla'));            
        $form->addText('delivery_price', $this->translator->translate('Cena_dopravy'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Cena_dopravy'));            
        $form->addText('trip_number', $this->translator->translate('Číslo_jízdy'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_jízdy'));            
            

        if ($tmpDatap && $tmpDatap->doc_type == 0) {
            $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'store_in')->fetchPairs('id', 'status_name');
        } elseif ($tmpDatap && $tmpDatap->doc_type == 1) {
            $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'store_out')->fetchPairs('id', 'status_name');
        } else {
            $arrStatus = $this->StatusManager->findAll()->where('status_use = ? OR status_use = ?', 'store_out', 'store_in')->fetchPairs('id', 'status_name');
        }

        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_dokladu'))
            ->setPrompt($this->translator->translate('Zvolte_stav_dokladu'));

        $arrTransportTypes = $this->TransportTypesManager->findAll()->where('deactive = 0')->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_transport_types_id', $this->translator->translate("Doprava"), $arrTransportTypes)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_typ_dopravy'))
            ->setPrompt($this->translator->translate('Zvolte_dopravu'));

        $form->addText('transport_km', $this->translator->translate('Vzdálenost'), 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vzdálenost_km'));

        $form->addText('manipulation', $this->translator->translate('Čas_manipulace_hod'), 15, 15)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Čas_manipulace'));

        $form->addText('weight_netto', $this->translator->translate('Váha_netto'), 15, 15)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Váha_netto'));

        $form->addText('weight_brutto', $this->translator->translate('Váha_brutto'), 15, 15)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Váha_brutto'));


        if ($tmpDatap && $tmpDatap->doc_type == 0) {
            $tmpLabel = $this->translator->translate("Dodavatel");
            $tmpPlaceHolder = $this->translator->translate("Zvolte_dodavatele");
            $tmpRequired = $this->translator->translate("Dodavatel_musí_být_vybrán");
        } elseif ($tmpDatap && $tmpDatap->doc_type == 1) {
            $tmpLabel = $this->translator->translate("Odběratel");
            $tmpPlaceHolder = $this->translator->translate("Zvolte_odběratele");
            $tmpRequired = $this->translator->translate("Odběratel_musí_být_vybrán");
        } else {
            $tmpLabel = $this->translator->translate("Odb/dod");
            $tmpPlaceHolder = $this->translator->translate("Zvolte_");
            $tmpRequired = $this->translator->translate("musí_být_vybrán");
        }


        if ($tmpInvoice = $this->DataManager->find($this->id)) {
            //if (isset($tmpInvoice['cl_partners_book_id']))
            if (array_key_exists('cl_partners_book_id', $tmpInvoice->toArray())) {
                $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
                if ($tmpPartnersBookId == NULL) {
                    $tmpPartnersBookId = 0;
                }
            } else {
                $tmpPartnersBookId = 0;
            }

        } else {
            $tmpPartnersBookId = 0;
        }
        //bdump($tmpPartnersBookId);
        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id', 'company');
        //$arrPartners = $this->PartnersManager->findAll()->fetchPairs('id','company');

        $form->addSelect('cl_partners_book_id', $tmpLabel, $arrPartners)
            ->setHtmlAttribute('data-placeholder', $tmpPlaceHolder)
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($tmpPlaceHolder);

        $arrUsers = [];
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $form->addSelect('cl_users_id', $this->translator->translate('Obchodník'), $arrUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_obchodníka'))
            ->setPrompt($this->translator->translate('Zvolte_obchodníka'));

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate('Středisko'), $arrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
            ->setPrompt($this->translator->translate('Zvolte středisko'));

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_měnu'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setPrompt($this->translator->translate('Zvolte_měnu'));
        $form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kurz'));

        //$arrStorage = $this->StorageManager->findAll()->select('CONCAT(name," - ",description) AS name,id')->order('name')->fetchPairs('id','name');
        $arrStorage = $this->StorageManager->getStoreTreeNotNested();
        $form->addSelect('cl_storage_id', $this->translator->translate("Sklad"), $arrStorage)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-url-change_storage', $this->link('changeStorage!'))
            ->setPrompt($this->translator->translate('Zvolte_sklad'));

        //order
        $arrOrders = $this->OrderManager->findAll()->select('cl_order.id, CONCAT(od_number," ",cl_partners_book.company) AS od_number')->
                            where('cl_status.s_fin = 0 AND cl_status.s_storno = 0')->
                            order('od_date,cl_partners_book.company')->
                            fetchPairs('id', 'od_number');
        $form->addSelect('cl_order_id', $this->translator->translate("Objednávka"), $arrOrders)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_objednávku'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_objednávku'));
        $form->addCheckbox('no_order', $this->translator->translate('Nevykrývat_objednávku'));

        //$arrUsers = $this->UserManager->getAll()->fetchPairs('id','name');
        //$arrUsers = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->fetchPairs('id','name');
        //$form->addSelect('cl_users_id', "Obchodník:",$arrUsers)
        //	    ->setHtmlAttribute('data-placeholder','Zvolte obchodníka')
        //	    ->setPrompt('Zvolte obchodníka');

        //$form->addText('created', 'Datum vytvoření:', 10, 10)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Datum vytvoření');
        //$form->addText('create_by', 'Vytvořil:', 20, 20)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Vytvořil');
        //$form->addText('changed', 'Datum změny:', 10, 10)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Datum změny');
        //$form->addText('change_by', 'Změnil:', 20, 20)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Změnil');

        $form->onValidate[] = [$this, 'FormValidate'];
        $form->addSubmit('send_fin', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-success');
        $form->addSubmit('create_income', $this->translator->translate('Vytvořit_příjemku'))->setHtmlAttribute('class', 'btn btn-success');
        $form->addSubmit('create_invoice', $this->translator->translate('Vytvořit_fakturu'))->setHtmlAttribute('class', 'btn btn-success');
        $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class', 'btn btn-success');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBack'];
        //	    ->onClick[] = callback($this, 'stepSubmit');
        $form->onSuccess[] = [$this, 'SubmitEditSubmitted'];


        return $form;

    }

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        /*02.12.2020 - cl_partners_book_id required and prepare data for just created partner
        */
        $data = $this->updatePartnerId($data);
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0) {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }

        if ($data['cl_storage_id'] == NULL) {
            $form->addError($this->translator->translate('Sklad_musí_být_vybrán'));
        }
        if (!$this->bscOff) {
            $this->redrawControl('bscArea');
            $this->redrawControl('formedit');
            $this->redrawControl('baselistScripts');
        } else {

        }
        $this->redrawControl('content');
    }

    public function stepBack()
    {
        // bdump('stepback');
        $this->redirect('default');

    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        $data = $this->removeFormat($data);
        if ($form['send_fin']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy() || $form['create_invoice']->isSubmittedBy() || $form['create_income']->isSubmittedBy()) {
            $data['doc_date'] = date('Y-m-d H:i:s', strtotime($data['doc_date']));

            if (!empty($data->id)) {
                $this->DataManager->update($data, TRUE);
                $this->CreateInvoiceArrived($this->id);
                //$this->StoreManager->createInvoiceArrived($data['id']);

                $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
            } else {
                //$row=$this->DataManager->insert($data);
                //$this->newId = $row->id;
                //$this->flashMessage('Nový záznam byl uložen.', 'success');
            }

            if (!is_null($data['cl_order_id'])) {
                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(['cl_store_docs_id' => $this->id, 'cl_order_id' => $data['cl_order_id']]);
            }

            //24.04.2022 - send notification email for cl_center_id
            $this->centerNotify($data, $this->numberSeries['use']);

            //$this->redirect('default');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            //$this->redirect('default');
            $this->redrawControl('content');
        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');

            //$this->redirect('default');

            //$this->redirect('default');
        }

    }

    public function handleGetQuant($cl_storage_id, $cl_store_move_id)
    {
        $tmpStoreMove = $this->StoreMoveManager->find($cl_store_move_id);
        if ($found = $this->StoreManager->findOneBy(['cl_storage_id' => $cl_storage_id, 'cl_pricelist_id' => $tmpStoreMove->cl_pricelist_id])) {
            //echo($found->quantity);
            if ($found->cl_storage->price_method == 0) {
                //FiFo
                $arrResponse = ['quantity' => $found->quantity, 'price_s' => $found->cl_pricelist->price_s];
            } else {
                //VAP
                $arrResponse = ['quantity' => $found->quantity, 'price_s' => $found->price_s];
            }

        } else {
            $arrResponse = ['quantity' => 0, 'price_s' => 0];
            //echo(0);
        }
        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($arrResponse));
        //$this->terminate();
    }

    /*    public function handleGetCurrencyRate($idCurrency)
        {
            if ($rate = $this->CurrenciesManager->findOneBy(array('id' => $idCurrency))->fix_rate)
                echo($rate);
            else {
                echo(0);
            }
            //in future there can be another work with rates

        $this->terminate();
        }*/


    public function handleGetStores()
    {
        $arrStore = $this->StorageManager->getStoreTree2();
        $this->sendJson($arrStore);
    }

    public function handleMakeIncome($cl_storage_id)
    {
        if ($tmpOutgoing = $this->DataManager->find($this->id)) {
            $storage = $this->StorageManager->find($cl_storage_id);
            //at first, create cl_store_docs
            $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(['cl_company_id' => $this->settings->id,
                'doc_date' => $tmpOutgoing->doc_date,
                'cl_partners_name' => $tmpOutgoing->cl_partners_book->company,
                'currency_code' => $tmpOutgoing->cl_currencies->currency_code,
                'storage_name' => $storage->name,
                'doc_title' => $this->translator->translate('příjem_z_výdejky') . $tmpOutgoing->doc_number,
                'doc_type' => 'store_in',
                'create_by' => $tmpOutgoing->create_by]);

            foreach ($tmpOutgoing->related('cl_store_move') as $one) {

                $data = $one->toArray();
                $s_in = $data['s_out'];
                $data['s_in'] = 0;
                $data['s_end'] = 0;
                $data['s_out'] = 0;
                $data['s_total'] = 0;
                $data['price_in'] = $data['price_s'];
                $data['price_in_vat'] = $data['price_s'] * (1 + ($data['vat'] / 100));
                $data['price_e2'] = 0;
                $data['price_e2_vat'] = 0;
                $data['cl_storage_id'] = $tmpDocs['cl_storage_id'];
                $data['cl_store_id'] = NULL;
                $data['cl_store_docs_id'] = $tmpDocs->id;
                unset($data['id']);

                $row = $this->StoreMoveManager->insert($data);
                $data['id'] = $row->id;
                $data['s_in'] = $s_in;
                $data['s_end'] = $s_in;
                $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocs);
                $this->StoreMoveManager->update($data2);

                $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
            }
            $this->StoreManager->UpdateSum($tmpDocs->id);
            $this->sendJson(['url' => $this->link('Store:edit', $tmpDocs->id)]);
        }
    }

    public function handleMakeRecalc($idCurrency, $rate, $oldrate, $recalc)
    {
        //in future there can be another work with rates
        //dump($this->editId);
        if ($rate > 0) {
            if ($recalc == 1) {
                //Debugger::fireLog('MakeRecalc doc : '.$this->id);
                $recalcItems = $this->StoreMoveManager->findBy(['cl_store_docs_id' => $this->id]);

                foreach ($recalcItems as $one) {
                    $data = new \Nette\Utils\ArrayHash;
                    if ($one->cl_store_doc->doc_type == 0) {
                        //recalc of income
                        if ($this->settings->platce_dph == 1) {
                            $data['price_s'] = $one['price_in'] * $rate;
                        } else {
                            $data['price_s'] = $one['price_in_vat'] * $rate;
                        }
                        //Debugger::firelog($data['price_s']);

                    } elseif ($one->cl_store_doc->doc_type == 1) {
                        //recalc of outgoing
                        $data['price_e'] = $one['price_e'] * $oldrate / $rate;
                        $data['price_e2'] = $one['price_e2'] * $oldrate / $rate;
                        $data['price_e2_vat'] = $one['price_e2_vat'] * $oldrate / $rate;
                        if ($data['price_e'] > 0) {
                            $data['profit'] = 100 - (($one['price_s'] / ($data['price_e'] * $rate)) * 100);
                        } else {
                            $data['profit'] = 0;
                        }
                    }

                    $one->update($data);
                    //  Debugger::fireLog('MakeRecalc item: '.$one->id);
                    if ($one->cl_store_doc->doc_type == 0) {
                        //income  = update of VAP prices
                        $this->StoreManager->updateVAP($one->cl_store_id, $one->cl_store_doc->doc_date);
                    }

                }
            }

            //we must save parent data
            $parentData = new \Nette\Utils\ArrayHash;
            $parentData['id'] = $this->id;
            if ($rate <> $oldrate)
                $parentData['currency_rate'] = $rate;

            $parentData['cl_currencies_id'] = $idCurrency;
            $this->DataManager->update($parentData);

            $this->StoreManager->UpdateSum($this->id);

        }
        $this->redrawControl('items');

    }


    public function DataProcessMain($defValues, $data)
    {

        if ($data['use'] == 'store_in')
            $defValues['doc_type'] = 0;
        elseif ($data['use'] == 'store_out')
            $defValues['doc_type'] = 1;

        return $defValues;
    }


    public function DataProcessListGridValidate($data)
    {
        $result = NULL;
        $tmpData = $this->StoreMoveManager->find($data['id']);
        if ($tmpData->cl_store_doc->doc_type == 0) {
            //give in to store
            //test if we can save less quantity than was saved
            $testOut = $this->StoreOutManager->findBy(['cl_store_move_in_id' => $tmpData->id])->
            sum('s_out');
            /*01.03.2020 - turnoff because we need to update price_s at income and s_in is readonly if there was something giveout from this
             * if ($data->s_in < $testOut)
            {
                //stop
                $result = 'Chybný příjem';
                $this->flashMessage('Z příjemky již bylo vydáváno, není možné o tolik snížit příjem.', 'danger');
                $this->redrawControl('flash');
            }*/

            //check if is work with expiration or batch enabled nad if is exp_date or batch filed
            if (!is_null($tmpData->cl_pricelist_id) && !is_null($tmpData->cl_pricelist->cl_pricelist_group_id)) {
                if ($this->settings->exp_on && $tmpData->cl_pricelist->cl_pricelist_group->request_exp_date == 1 && $data['exp_date'] == '') {
                    $result = $this->translator->translate('Chybí_datum_expirace');
                    $this->flashMessage($this->translator->translate('Není_zadán_datum_expirace'), 'danger');
                    $this->redrawControl('flash');

                } elseif ($this->settings->batch_on && $tmpData->cl_pricelist->cl_pricelist_group->request_batch == 1 && $data['batch'] == '') {
                    $result = $this->translator->translate('Chybí_šarže');
                    $this->flashMessage($this->translator->translate('Není_zadána_šarže'), 'danger');
                    $this->redrawControl('flash');

                }
            }
            if ($data['s_in'] < 0) {
                $result = $this->translator->translate('Není_možné_přijmout_záporné_množství');
                $this->flashMessage($this->translator->translate('Není_možné_přijmout_záporné_množství'), 'danger');
                $this->redrawControl('flash');
            }

        } elseif ($tmpData->cl_store_doc->doc_type == 1) {
            //give out from store
            bdump($tmpData->s_out, 'tmpData->s_out');
            if ($data['s_out'] < 0) {
                $result = $this->translator->translate('Není_možné_vydat_záporné_množství');
                $this->flashMessage($this->translator->translate('Není_možné_vydat_záporné_množství'), 'danger');
                $this->redrawControl('flash');
            }

        }
        return $result;
    }

    public function DataProcessListGrid($data)
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //now cl_store, find record of store according to storage or batch, if doesn't exist, create it and add to return $data
        $tmpData = $this->StoreMoveManager->find($data['id']);
        if ($tmpData->cl_store_doc->doc_type == 0) {
            if (isset($data['batch']) && $data['batch'] == '') {
                $data['batch'] = NULL;
            }
            $data = $this->StoreManager->GiveInStore($data, $tmpData, $tmpParentData);
            //Debugger::firelog($data);

        } elseif ($tmpData->cl_store_doc->doc_type == 1) {
            //give out from store
            $data = $this->StoreManager->GiveOutStore($data, $tmpData, $tmpParentData);
        }

        //now update or create data in cl_store_out
        //only for give out
        //if (!$tmpOut = $this->StoreOutManager->findBy(array('cl_store_move_id' => $data['id'])))
        //{
        //record doesn't exist,
        //}


        return $data;
    }

    public function afterDataSaveListGrid($dataId, $name = NULL)
    {


        //parent bond

        //now cl_store, find record of store according to storage or batch, if doesn't exist, create it and add to return $data
        $tmpItem = $this->StoreMoveManager->find($dataId);
        if ($tmpItem->cl_store_doc->doc_type == 0) {
            //23.07.2021 - moved from above
            //23.07.2021 - removed because VAP should be changed only on income
            $cl_store = $this->StoreMoveManager->find($dataId);
            $this->StoreManager->updateVAP($cl_store->cl_store_id, $cl_store->cl_store_docs->doc_date);
                        
            //23.03.2023 - weight calculation
            $tmpPackage = $this->PriceListManager->findAll()->where('item_label = ?', $tmpItem['package_name'])->fetch();
            if ($tmpPackage){
                $tmpPackageWeight = ($tmpPackage['weight'] / $this->ArraysManager->getWeightToBase($tmpPackage['weight_unit']));
                $tmpWeight_netto = $cl_store['weight_brutto'] - $tmpPackageWeight;
                $cl_store->update(['s_in' => $tmpWeight_netto, 'weight_pack' => $tmpPackageWeight]);
                //if ($cl_store['weight_pack'] == 0)
                //    $tmpWeight_brutto = $cl_store['weight_netto'] + $tmpPackageWeight;
                //else
                //    $tmpWeight_brutto = $cl_store['weight_netto'] + $cl_store['weight_pack'];

            }else{
                //$tmpWeight_brutto = $cl_store['weight_netto'] + $cl_store['weight_pack'];
                $tmpWeight_netto = $cl_store['weight_brutto'] - $cl_store['weight_pack'];
                $cl_store->update(['s_in' => $tmpWeight_netto, 'weight_netto' => $tmpWeight_netto]);
            }
            //$cl_store->update(['weight_brutto' => $tmpWeight_brutto]);

            if (!is_null($tmpItem->cl_pricelist_id)) {
                //find if there are bonds in cl_pricelist_bonds
                $tmpBonds = $this->PriceListBondsManager->findAll()->
                                where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpItem->cl_pricelist_id, $tmpItem->s_in);
                foreach ($tmpBonds as $key => $oneBond) {
                    $tmpItemBond = $this->StoreMoveManager->findBy(['cl_parent_bond_id' => $tmpItem->id,
                        'cl_pricelist_id' => $oneBond->cl_pricelist_id])->fetch();
                    $newItem = $this->PriceListBondsManager->getBondData($oneBond, $tmpItem);
                    $newItem['cl_store_docs_id'] = $tmpItem->cl_store_docs_id;
                    if (!$tmpItemBond) {
                        $tmpNew = $this->StoreMoveManager->insert($newItem);
                        $newItem['id'] = $tmpNew->id;
                    } else {
                        $newItem['id'] = $tmpItemBond->id;
                        $tmpNew = $this->StoreMoveManager->update($newItem);
                    }
                    $tmpNew = $this->StoreMoveManager->find($newItem['id']);
                    $tmpParentData = $tmpItem->cl_store_doc;
                    $newData = $this->StoreManager->GiveInStore($newItem, $tmpNew, $tmpParentData);
                    //dump($newData);
                    $this->StoreMoveManager->update($newData);
                    //$dataIdtmp = $this->StoreManager->giveInItem($tmpItem->cl_store_doc_id, $tmpNew['id'], $this->DeliveryNoteItemsBackManager);
                }
            }
        }elseif ($tmpItem->cl_store_doc->doc_type == 1) {

            //23.03.2023 - weight calculation
            $cl_store = $this->StoreMoveManager->find($dataId);
            $tmpPackage = $this->PriceListManager->findAll()->where('item_label = ?', $tmpItem['package_name'])->fetch();
            if ($tmpPackage){
                $tmpPackageWeight = ($tmpPackage['weight'] / $this->ArraysManager->getWeightToBase($tmpPackage['weight_unit']));
                $cl_store->update(['weight_pack' => $tmpPackageWeight]);
                if ($cl_store['weight_pack'] == 0)
                    $tmpWeight_brutto = $cl_store['weight_netto'] + $tmpPackageWeight;
                else
                    $tmpWeight_brutto = $cl_store['weight_netto'] + $cl_store['weight_pack'];

            }else{
                $tmpWeight_brutto = $cl_store['weight_netto'] + $cl_store['weight_pack'];
            }
            $cl_store->update(['weight_brutto' => $tmpWeight_brutto]);


            if (!is_null($tmpItem->cl_pricelist_id)) {
                //find if there are bonds in cl_pricelist_bonds
                $tmpBonds = $this->PriceListBondsManager->findAll()->
                                where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpItem->cl_pricelist_id, $tmpItem->s_out);
                foreach ($tmpBonds as $key => $oneBond) {
                    $tmpItemBond = $this->StoreMoveManager->findBy(['cl_parent_bond_id' => $tmpItem->id,
                        'cl_pricelist_id' => $oneBond->cl_pricelist_id])->fetch();
                    $newItem = $this->PriceListBondsManager->getBondData($oneBond, $tmpItem);
                    $newItem['cl_store_docs_id'] = $tmpItem->cl_store_docs_id;
                    if (!$tmpItemBond) {
                        $tmpNew = $this->StoreMoveManager->insert($newItem);
                        $newItem['id'] = $tmpNew->id;
                    } else {
                        $newItem['id'] = $tmpItemBond->id;
                        $tmpNew = $this->StoreMoveManager->update($newItem);
                    }
                    $tmpNew = $this->StoreMoveManager->find($newItem['id']);
                    $tmpParentData = $tmpItem->cl_store_doc;
                    $newData = $this->StoreManager->GiveOutStore($newItem, $tmpNew, $tmpParentData);
                    //dump($newData);
                    $this->StoreMoveManager->update($newData);
                    //$dataIdtmp = $this->StoreManager->giveInItem($tmpItem->cl_store_doc_id, $tmpNew['id'], $this->DeliveryNoteItemsBackManager);
                }
            }

        }


    }


    public function UpdateSum()
    {
        $this->StoreManager->UpdateSum($this->id);


        parent::UpdateSum();
        //$this->redrawControl('baselistArea');
        //$this->redrawControl('bscArea');
        //$this->redrawControl('bsc-child');

        $this->CreateInvoiceArrived($this->id);
        $this['storeListgrid']->redrawControl('editLines');
        //$this['sumOnDocs']->redrawControl();

    }

    public function ListGridInsert($sourceData)
    {
        $arrPrice = new \Nette\Utils\ArrayHash;
        //if (isset($sourceData['cl_pricelist_id']))
        if (array_key_exists('cl_pricelist_id', $sourceData->toArray())) {
            $arrPrice['id'] = $sourceData['cl_pricelist_id'];
            $sourcePriceData = $this->PriceListManager->find($sourceData->cl_pricelist_id);
        } else {
            $arrPrice['id'] = $sourceData['id'];
            $sourcePriceData = $this->PriceListManager->find($sourceData->id);
        }

        $arrPrice['cl_currencies_id'] = $sourcePriceData['cl_currencies_id'];
        ///04.09.2017 - find price if there are defince prices_groups
        $tmpData = $this->DataManager->find($this->id);
        //if ( isset($tmpData['cl_partners_book_id'])
        //if ( array_key_exists('cl_partners_book_id',$tmpData->toArray())
        if (!is_null($tmpData['cl_partners_book_id'])
            && $tmpPrice = $this->PricesManager->getPrice($tmpData->cl_partners_book,
                $arrPrice['id'],
                $tmpData->cl_currencies_id,
                $tmpData->cl_storage_id)) {
            $arrPrice['price'] = $tmpPrice['price'];
            $arrPrice['price_vat'] = $tmpPrice['price_vat'];
            $arrPrice['discount'] = $tmpPrice['discount'];
            $arrPrice['price_e2'] = $tmpPrice['price_e2'];
            $arrPrice['price_e2_vat'] = $tmpPrice['price_e2_vat'];
            $arrPrice['cl_currencies_id'] = $tmpPrice['cl_currencies_id'];
        } else {
            $arrPrice['price'] = $sourceData->price;
            $arrPrice['price_vat'] = $sourceData->price_vat;
            $arrPrice['discount'] = 0;
            $arrPrice['price_e2'] = $sourceData->price;
            $arrPrice['price_e2_vat'] = $sourceData->price_vat;
         //   $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
        }

        $arrPrice['vat'] = $sourceData->vat;


        //new record into cl_store_move
        $arrData = new \Nette\Utils\ArrayHash;

        //27.03.2023 - work with waste code
        $arrData['waste_code'] = (!is_null($sourcePriceData['cl_waste_category_id']) ? $sourcePriceData->cl_waste_category['waste_code'] : '');

        $arrData[$this->DataManager->tableName . '_id'] = $this->id;
        //$arrData['cl_pricelist_id'] = $sourceData->id;
        $arrData['cl_pricelist_id'] = $arrPrice['id'];
        $arrData['item_order'] = $this->StoreMoveManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;

        $tmpParentData = $this->DataManager->find($this->id);

        //13.06.2017 - přidáme zásobu, aby se zobrazilo množství skladem
        //if ($tmpStore = $this->StoreManager->findOneBy(array('cl_pricelist_id' => $arrPrice['id'],
        //					     'cl_storage_id' => $tmpParentData['cl_storage_id'])))
        //08.11.2020 - we need to work with store which has some balance
        if (is_null($tmpParentData['cl_storage_id'])) {
            $tmpStorageId = $this->settings['cl_storage_id'];
        } else {
            $tmpStorageId = $tmpParentData['cl_storage_id'];
        }
        //05.12.2020 - new cl_store_move have to be without cl_store_move, because we don't know yet correct cl_Store_id
        /*if ($tmpStore = $this->StoreManager->findAll()->where('(cl_pricelist_id = ? AND cl_storage_id = ? AND quantity != 0) ',
                                            $arrPrice['id'], $tmpStorageId)->order('exp_date ASC')->fetch())
	    {
		    $arrData['cl_store_id'] = $tmpStore->id;
	    }*/

        $arrData['cl_storage_id'] = $tmpStorageId;
        //Debugger::firelog($tmpParentData['cl_storage_id']);

        //dump($this->id);
        //die;
        if ($tmpParentData->doc_type == 0) {
            //income
            $arrData['s_in'] = 1;
            if ($this->settings->platce_dph == 1) {
                //if (isset($sourceData['price_s']))
                if (array_key_exists('price_s', $sourceData->toArray())) {
                    $tmpPrices = $sourceData->price_s;
                } else {
                    $tmpPrices = $sourceData->cl_pricelist->price_s;
                }

                $arrData['price_in'] = $tmpPrices * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                $arrData['price_in_vat'] = ($tmpPrices * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate) * (1 + ($sourceData->vat / 100));
            } elseif ($this->settings->platce_dph == 0) {
                $arrData['price_in_vat'] = $sourceData->price_s * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                $arrData['price_in'] = ($sourceData->price_s * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate) * (1 + ($sourceData->vat / 100));
            }
            $arrData['vat'] = $arrPrice['vat'];
            //$arrData['cl_storage_id'] = $tmpParentData->cl_storage_id;

        } elseif ($tmpParentData->doc_type == 1) {
            //outgoing
            $arrData['s_out'] = 0;
            //$arrData['price_e'] = $sourceData->price * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
            //$arrData['price_e2'] = $sourceData->price * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
            //$arrData['price_e2_vat'] = ($sourceData->price * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate)* (1 + ($sourceData->vat/100));
            //$arrData['price_s'] = $sourceData->price_s * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
            $arrData['price_e'] = $arrPrice['price'] * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
            $arrData['price_e2'] = $arrPrice['price'] * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
            $arrData['price_e2_vat'] = ($arrPrice['price'] * $sourceData->cl_currencies->fix_rate / $tmpParentData->currency_rate) * (1 + ($arrPrice['vat'] / 100));
            $arrData['vat'] = $arrPrice['vat'];
            //31.05.2017 - FiFo or VAT store
            //if (!isset($tmpParentData['cl_storage_id']) || $tmpParentData->cl_storage->price_method == 0)
            //if (!array_key_exists('cl_storage_id',$tmpParentData->toArray()) || $tmpParentData->cl_storage->price_method == 0)
            if (is_null($tmpParentData['cl_storage_id']) || $tmpParentData->cl_storage->price_method == 0) {
                $arrData['price_s'] = $sourcePriceData->price_s * $sourcePriceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
            } else {
                //find store for use
                if ($tmpStore = $this->StoreManager->findAll()->where(['cl_pricelist_id' => $arrData['cl_pricelist_id'], 'cl_storage_id' => $tmpParentData->cl_storage_id])->fetch()) {
                    $arrData['price_s'] = $tmpStore->price_s * $sourcePriceData->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                }
            }

            //$arrData['cl_storage_id'] = $tmpParentData->cl_storage_id;
        }

        $row = $this->StoreMoveManager->insert($arrData);
        //$this->updateSum();
        $this->StoreManager->UpdateSum($this->id);
        return ($row);
    }

    //control method to determinate if we can delete
    public function beforeDelete($lineId)
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //	Debugger::fireLog($tmpParentData);
        if ($tmpParentData->doc_type == 0) {
            //incoming
            //test if there outgoing moves from this income
            if ($result = $this->StoreOutManager->findBy(['cl_store_move_in_id' => $lineId])->sum('s_out')) {
                $result = FALSE;
                $this->flashMessage($this->translator->translate('Z_příjemky_již_bylo_vydáváno_záznam_není_možné_vymazat'), 'danger');
                $this->redrawControl('flash');
            } else {
                $result = TRUE;
            }
            $tmpData = $this->StoreMoveManager->find($lineId);
            //update cl_order_items.cl_store_docs_id
            $orderItem = $this->OrderItemsManager->findAll()->where('cl_store_docs_id = ? AND cl_store_move_id = ?', $tmpParentData->id, $tmpData->id)->fetch();
            if ($orderItem) {
                $orderItem->update(array('cl_store_docs_id' => NULL, 'cl_store_move_id' => NULL));
            }
        } elseif ($tmpParentData->doc_type == 1) {
            //outgoing
            //check if there is another document created from this outgoing eg. invoice
            $result = TRUE;
        }
        //Debugger::fireLog($result);
        return $result;
    }

    //new sums on parent records cl_store, cl_pricelist after delete
    public function afterDelete($line)
    {

        //bdump($line, 'afterDelete line');
        $this->StoreManager->updateStore($line);
        $this->StoreManager->UpdateSum($this->id);
    }

    public function handleSavePDFpurchase($id){
        $data = $this->DataManager->find($id);
        $template = $this->createMyTemplateWS($data, $this->ReportManager->getReport(__DIR__ . '/../templates/Store/pdfPurchase.latte'));
        $this->pdfCreate($template, $data['doc_number'] . ' ' . $this->translator->translate('Potvrzení_výkupu'));
    }

    public function handleSnippetsUpdate()
    {
        //update called after closing modal windows
        $this->redrawControl('cl_currencies');
        $this->redrawControl('cl_storage');
        $this->redrawControl('cl_status');
        $this->redrawControl('cl_partners_book');
        $this['storeListgrid']->redrawControl('pricelist2');

    }

    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
        //dump($cl_partners_book_id);
        $arrUpdate = new \Nette\Utils\ArrayHash;
        $arrUpdate['id'] = $this->id;
        $arrUpdate['cl_partners_book_id'] = $cl_partners_book_id;

        //dump($arrUpdate);
        //die;
        $this->DataManager->update($arrUpdate);

        $this['storeListgrid']->redrawControl('pricelist2');
    }

    /*
     * separately delete child records 
     */
    public function beforeDeleteBaseList($id)
    {
        $tmpData = $this->StoreMoveManager->findBy(array('cl_store_docs_id' => $id));
        $result = TRUE;
        foreach ($tmpData as $one) {
            //$this->DataManager->delete($lineId);
            if (!$this->beforeDelete($one->id) && $result) {
                $one2 = $one->toArray(); //self::toArray(
                $one->delete();
                $this->afterDelete($one2);
            } else {
                $result = FALSE;
            }
        }
        return $result;
    }


    private function createInvoice()
    {
        if ($tmpData = $this->DataManager->find($this->id)) {
            if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
                $tmpInvoiceType = $tmpInvoiceType->id;
            } else {
                $tmpInvoiceType = NULL;
            }
            //default values for invoice
            $defDueDate = new \Nette\Utils\DateTime;
            $arrInvoice = new \Nette\Utils\ArrayHash;
            $arrInvoice['cl_partners_book_id'] = $tmpData->cl_partners_book_id;
            $arrInvoice['cl_currencies_id'] = $this->settings->cl_currencies_id;
            $arrInvoice['currency_rate'] = $this->settings->cl_currencies->fix_rate;
            $arrInvoice['vat_active'] = $this->settings->platce_dph;
            $arrInvoice['cl_store_docs_id'] = $tmpData->id;

            $arrInvoice['cl_currencies_id'] = $tmpData->cl_currencies_id;
            $arrInvoice['currency_rate'] = $tmpData->currency_rate;
            $arrInvoice['inv_date'] = new \Nette\Utils\DateTime;
            $arrInvoice['vat_date'] = new \Nette\Utils\DateTime;

            $arrInvoice['konst_symb'] = $this->settings->konst_symb;
            $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
            //$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;

            $arrInvoice['header_show'] = $this->settings->header_show;
            $arrInvoice['footer_show'] = $this->settings->footer_show;
            $arrInvoice['header_txt'] = $this->settings->header_txt;
            $arrInvoice['footer_txt'] = $this->settings->footer_txt;

            //settings for concrete partner
            if ($tmpData->cl_partners_book->due_date > 0)
                $strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
            else
                $strModify = '+' . $this->settings->due_date . ' day';

            $arrInvoice['due_date'] = $defDueDate->modify($strModify);

            if (isset($tmpData->cl_partners_book->cl_payment_types_id)) {
                $clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
                $spec_symb = $tmpData->cl_partners_book->spec_symb;
            } else {
                $clPayment = $this->settings->cl_payment_types_id;
                $spec_symb = "";
            }
            $arrInvoice['cl_payment_types_id'] = $clPayment;
            $arrInvoice['spec_symb'] = $spec_symb;

            debugger::log('ArrInvoice' . dump($arrInvoice));
            //create or update invoice
            if ($tmpData->cl_invoice_id == NULL) {
                //new number
                $nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
                $arrInvoice['inv_number'] = $nSeries['number'];
                $arrInvoice['cl_number_series_id'] = $nSeries['id'];
                $tmpStatus = 'invoice';
                if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
                    $arrInvoice['cl_status_id'] = $nStatus->id;

                //bdump($arrInvoice);
                $row = $this->InvoiceManager->insert($arrInvoice);
                $this->DataManager->update(array('id' => $this->id, 'cl_invoice_id' => $row->id));
                $invoiceId = $row->id;
            } else {
                $arrInvoice['id'] = $tmpData->cl_invoice_id;
                $row = $this->InvoiceManager->update($arrInvoice);
                $invoiceId = $tmpData->cl_invoice_id;
            }
            //now content of invoice
            //at first, delete old content
            //next insert new content
            $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $invoiceId))->delete();
            $tmpItems = $tmpData->related('cl_store_move');
            $lastOrder = 0;
            foreach ($tmpItems as $one) {
                $newItem = new \Nette\Utils\ArrayHash;
                $newItem['cl_invoice_id'] = $invoiceId;
                $newItem['item_order'] = $one->item_order;
                $newItem['cl_pricelist_id'] = $one->cl_pricelist_id;
                $newItem['item_label'] = $one->pricelist->item_label;
                $newItem['quantity'] = $one->s_out;
                $newItem['units'] = $one->pricelist->unit;
                $newItem['price_s'] = $one->price_s;
                $newItem['price_e'] = $one->price_e;
                $newItem['discount'] = $one->discount;
                $newItem['price_e2'] = $one->price_e2;
                $newItem['vat'] = $one->vat;
                $newItem['price_e2_vat'] = $one->price_e2_vat;
                $this->InvoiceItemsManager->insert($newItem);
                $lastOrder = $one->item_order;
            }

            //InvoicePresenter::updateSum($invoiceId,$this);
            $this->InvoiceManager->updateInvoiceSum($invoiceId);

            $this->flashMessage($this->translator->translate('Změny_byly_uloženy,_faktura_byla_vytvořena.'), 'success');
        }

    }


    public function handleChangeDocDate($doc_date)
    {
        //Debugger::fireLog('handleChangeDocDate');
        if ($storedData = $this->DataManager->find($this->id)) {
            $docDate = date('Y-m-d H:i:s', strtotime($doc_date));
            if ($storedData->doc_date != $docDate) {
                $storedData->update(array('doc_date' => $docDate));
                foreach ($storedData->related('cl_store_move') as $one) {
                    if (!is_null($one->cl_store_id)) {
                        //Debugger::fireLog('call updateVAP on: '.$one->cl_store_id);
                        //23.07.2021 - only on income
                        if ($storedData->doc_type == 0) {
                            $this->StoreManager->updateVAP($one->cl_store_id);
                        }
                    }
                }
                $this->redrawControl('items');
            }
        }
    }

    public function handleChangeStorage($cl_storage_id)
    {
        if (!empty($cl_storage_id)) {
            if ($storedData = $this->DataManager->find($this->id)) {
                if ($storedData->cl_storage_id != $cl_storage_id) {
                    $storedData->update(array('cl_storage_id' => $cl_storage_id));
                }
            }
            $this->payload->result = 'TRUE';

        } else {
            //$this->redrawControl('flash');
            $this->payload->result = 'FALSE';
        }
        $this->redrawControl('flash');
    }

    public function madeFromInvoice($id)
    {
        $retVal = FALSE;
        if ($tmpStoreMove = $this->StoreMoveManager->find($id)) {
            if ($tmpStoreMove->cl_invoice_items_id != NULL || $tmpStoreMove->cl_invoice_items_back_id != NULL) {
                $retVal = TRUE;
            } else {
                $retVal = FALSE;
            }
        }
        return $retVal;

    }


    public function handleShowPairedDocs()
    {
        //bdump('ted');
        $this->pairedDocsShow = TRUE;
       /* $this->showModal('pairedDocsModal');
        $this->redrawControl('pairedDocs');
        $this->redrawControl('contents');*/
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('pairedDocs2');
        $this->showModal('pairedDocsModal');
    }

    public function actionFromInvoiceArrived($cl_invoice_arrived_id)
    {
        //look if store_in for given invoice_arrived exists
        $tmpStoreDocsInvoice = $this->StoreDocsManager->findAll()->where('cl_invoice_arrived_id = ?', $cl_invoice_arrived_id)->fetch();
        $tmpInvoice = $this->InvoiceArrivedManager->find($cl_invoice_arrived_id);
        if ($tmpInvoice) {
            if (!$tmpStoreDocsInvoice) {
                $this->flashMessage($this->translator->translate('Příjemka_byla_vytvořena_doplňte_položky'), 'success');

                $this->handleNew("store_in", json_encode(array('cl_invoice_arrived_id' => $cl_invoice_arrived_id,
                    'cl_partners_book_id' => $tmpInvoice->cl_partners_book_id,
                    'invoice_number' => $tmpInvoice->rinv_number,
                    'delivery_number' => $tmpInvoice->delivery_number,
                    'cl_currencies_id' => $tmpInvoice->cl_currencies_id,
                    'currency_rate' => $tmpInvoice->currency_rate,
                    'doc_title' => $tmpInvoice->inv_title,
                    'doc_date' => $tmpInvoice->arv_date)));
                //create pairedocs record
                //there we don't know cl_store_docs_id, so we have to do it in method $this->afterNew
            } else {
                $this->redirect(':Application:Store:edit', array('id' => $tmpStoreDocsInvoice->id));
            }
        } else {
            $this->flashMessage($this->translator->translate('Příjemka_nebyla_vytvořena!'), 'error');
        }
    }

    public function afterNew($row = FALSE)
    {
        $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_arrived_id' => $row->cl_invoice_arrived_id, 'cl_store_docs_id' => $row->id));
    }


    public function handleCreateIncomeModalWindow()
    {
        //bdump('ted');
        $this->createDocShow = TRUE;
        $this->showModal('createIncomeModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    protected function createComponentStorages($name)
    {
        $form = new Form($this, $name);

        $arrStorage = $this->StorageManager->getStoreTreeNotNested();
        //$contries = array(
//			        102 => \Nette\Utils\Html::el()->setText('Czech republic')->data('lon', ...)->data('lat', ...)
        //);
        $form->addSelect('cl_storage_id', $this->translator->translate("Sklad"), $arrStorage)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
            ->setHtmlAttribute('class', 'form-control chzn-selectModal input-sm')
            ->setPrompt($this->translator->translate('Zvolte_sklad'));

        /*$form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
    */
        //$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        return $form;

    }

    public function handleShowStoreUsed()
    {
        $this->filterStoreUsed = array();
        $this->redrawControl('itemsForStore');
        $this->redrawControl('itemsForStore2');
    }

    public function handleShowStoreNotUsed()
    {
        $this->filterStoreUsed = array('filter' => 'cl_store_docs_in_id IS NULL');
        $this->redrawControl('itemsForStore');
        $this->redrawControl('itemsForStore2');
    }

    public function handleCreateIncome($dataItems, $cl_storage_id, $bscId)
    {
        if ($tmpOutgoing = $this->DataManager->find($bscId)) {
            //$storage = $this->StorageManager->find($cl_storage_id);
            //at first, create cl_store_docs
            $companyId = $tmpOutgoing->cl_partners_book_id;
            if ($tmpOutgoing->cl_partners_book_id != NULL) {
                $companyName = $tmpOutgoing->cl_partners_book->company;
            } else {
                $companyName = "";
            }
            //30.09.2019 -  cl_store_docs.cl_company_branch_id according to destination cl_company_branch_id of selected cl_storage_id
            if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('cl_storage_id = ?', $cl_storage_id)->limit(1)->fetch())
                $cl_company_branch_id = $tmpBranch->id;
            else
                $cl_company_branch_id = NULL;

            $tmpNow = new DateTime();
            $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(array('cl_company_id' => $this->settings->id,
                'doc_date' => $tmpNow,  //$tmpOutgoing->doc_date
                'cl_partners_book_id' => $companyId,
                'currency_code' => $tmpOutgoing->cl_currencies->currency_code,
                'cl_storage_id' => $cl_storage_id,
                'cl_company_branch_id' => $cl_company_branch_id,
                'doc_title' => $this->translator->translate('příjem_z_výdejky') . $tmpOutgoing->doc_number,
                'doc_type' => 'store_in',
                'create_by' => $tmpOutgoing->create_by));

            //foreach($tmpOutgoing->related('cl_store_move') as $one)
            //{
            $arrDataItems = json_decode($dataItems, true);
            foreach ($arrDataItems as $key => $one) {
                $storeItem = $this->StoreMoveManager->find($one);
                if ($storeItem) {
                    $data = $storeItem->toArray();
                    $s_in = $data['s_out'];
                    $data['s_in'] = 0;
                    $data['s_end'] = 0;
                    $data['s_out'] = 0;
                    $data['s_out_fin'] = 0;
                    $data['s_total'] = 0;
                    $data['price_in'] = $data['price_s'];
                    $data['price_in_vat'] = $data['price_s'] * (1 + ($data['vat'] / 100));
                    $data['price_e2'] = 0;
                    $data['price_e2_vat'] = 0;
                    $data['cl_storage_id'] = $tmpDocs['cl_storage_id'];
                    $data['cl_store_id'] = NULL;
                    $data['cl_invoice_items_id'] = NULL;
                    $data['cl_invoice_items_back_id'] = NULL;
                    $data['cl_store_docs_id'] = $tmpDocs->id;
                    unset($data['id']);
                    unset($data['cl_store_docs_in_id']);

                    //Debugger::fireLog($data);
                    $row = $this->StoreMoveManager->insert($data);
                    $data['id'] = $row->id;
                    $data['s_in'] = $s_in;
                    $data['s_end'] = $s_in;
                    $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocs);
                    $this->StoreMoveManager->update($data2);
                    //Debugger::fireLog($data2['cl_store_id']);
                    $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
                    $storeItem->update(array('cl_store_docs_in_id' => $tmpOutgoing->id));
                }

            }
            $this->StoreManager->UpdateSum($tmpDocs->id);


            $this->createDocShow = FALSE;
            $this->payload->id = $tmpDocs->id;
            $this->redrawControl('content');
        } else {
            $this->createDocShow = FALSE;
            $this->redrawControl('content');
        }


    }

    public function handleCreateOutgoingModalWindow()
    {
        //bdump('ted');
        $this->createDocShow = TRUE;
        $this->showModal('createOutgoingModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    protected function createComponentPartners($name)
    {
        $form = new Form($this, $name);

        $arrPartners = $this->PartnersManager->findAll()->order('company')->fetchPairs('id', 'company');
        //$contries = array(
//			        102 => \Nette\Utils\Html::el()->setText('Czech republic')->data('lon', ...)->data('lat', ...)
        //);
        $form->addSelect('cl_partners_book_id', $this->translator->translate("Odběratel"), $arrPartners)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_odběratele'))
            ->setHtmlAttribute('class', 'form-control chzn-selectModal input-sm')
            ->setPrompt($this->translator->translate('Zvolte_odběratele'));

        /*$form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
    */
        //$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        return $form;

    }


    /**
     * Create outgoing move from income
     * @param type $dataItems
     * @param type $cl_partners_book_id
     */
    public function handleCreateOutgoing($dataItems, $cl_partners_book_id = NULL, $bscId)
    {
        if ($tmpIncome = $this->DataManager->find($bscId)) {
            //$storage = $this->StorageManager->find($tmpIncome->cl_storage_id);
            //at first, create cl_store_docs
            if ($cl_partners_book_id != NULL) {
                $tmpPartnersBook = $this->PartnersManager->find($cl_partners_book_id);
                if ($tmpPartnersBook) {
                    $companyName = $tmpPartnersBook->company;
                } else {
                    $companyName = "";
                }
            } else {
                if ($tmpIncome->cl_partners_book_id != NULL) {
                    $companyName = $tmpIncome->cl_partners_book->company;
                } else {
                    $companyName = "";
                }
            }
            $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(array('cl_company_id' => $this->settings->id,
                'cl_company_branch_id' => $tmpIncome->cl_company_branch_id,
                'doc_date' => $tmpIncome->doc_date,
                'cl_partners_book_id' => $cl_partners_book_id,
                'currency_code' => $tmpIncome->cl_currencies->currency_code,
                'cl_storage_id' => $tmpIncome->cl_storage_id,
                'doc_title' => $this->translator->translate('výdej_z_příjemky') . $tmpIncome->doc_number,
                'doc_type' => 'store_out',
                'create_by' => $tmpIncome->create_by));

            //foreach($tmpOutgoing->related('cl_store_move') as $one)
            //{
            $arrDataItems = json_decode($dataItems, true);
            foreach ($arrDataItems as $key => $one) {
                $storeItem = $this->StoreMoveManager->find($one);
                if ($storeItem) {
                    $data = $storeItem->toArray();
                    $s_out = $data['s_in'];
                    $data['s_in'] = 0;
                    $data['s_end'] = 0;
                    $data['s_out'] = 0;
                    $data['s_out_fin'] = 0;
                    $data['s_total'] = 0;
                    //$data['price_in']	                = $data['price_s'];
                    //$data['price_in_vat']             = $data['price_s']*(1+($data['vat']/100));
                    $data['price_e2'] = 0;
                    $data['price_e2_vat'] = 0;
                    $data['price_s'] = 0;
                    $data['cl_storage_id'] = $storeItem['cl_storage_id'];
                    $data['cl_store_id'] = NULL;
                    $data['cl_invoice_items_id'] = NULL;
                    $data['cl_invoice_items_back_id'] = NULL;
                    $data['cl_store_docs_id'] = $tmpDocs->id;
                    unset($data['id']);

                    $tmpPrice = $this->PricesManager->getPrice($tmpDocs->cl_partners_book,
                        $storeItem->cl_pricelist_id,
                        $tmpDocs->cl_currencies_id,
                        $tmpIncome->cl_storage_id);
                    if ($this->settings->platce_dph == 1) {
                        $data['price_e'] = $tmpPrice['price'];
                        $data['vat'] = $storeItem->cl_pricelist->vat;
                    } else {
                        $data['price_e'] = $tmpPrice['price_vat'];
                        $data['vat'] = 0;
                    }
                    $data['price_e2'] = $tmpPrice['price'];
                    $data['price_e2_vat'] = $tmpPrice['price_vat'];


                    //Debugger::fireLog($data);
                    $row = $this->StoreMoveManager->insert($data);
                    $data['id'] = $row->id;
                    $data['s_in'] = 0;
                    $data['s_end'] = 0;
                    $data['s_out'] = $s_out;
                    $data['price_e2'] = $tmpPrice['price'] * $s_out;
                    $data['price_e2_vat'] = $tmpPrice['price_vat'] * $s_out;

                    //$data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocs);
                    $data2 = $this->StoreManager->GiveOutStore($data, $row, $tmpDocs);
                    $this->StoreMoveManager->update($data2);
                    //Debugger::fireLog($data2['cl_store_id']);
                    //23.07.2021 - removed because VAP should be changed only on income
                    // $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
                }
            }
            $this->StoreManager->UpdateSum($tmpDocs->id);

            $this->createDocShow = FALSE;
            $this->payload->id = $tmpDocs->id;
            $this->redrawControl();
        } else {
            $this->createDocShow = FALSE;
            $this->redrawControl();
        }

    }

    public function handleCreateInvoice($bscId)
    {

        //find cl_store_docs record
        $tmpStoreDoc = $this->StoreDocsManager->find($bscId);
        if ($tmpStoreDoc) {
            //20.02.2019 - at first test if there is not allready created invoice
            if (!is_null($tmpStoreDoc->cl_invoice_id)) {
                $this->flashmessage($this->translator->translate('K_této_výdejce_již_faktura_existuje'), 'danger');
            } else {
                //create invoice
                //at first main record cl_invoice
                $tmpNow = new \Nette\Utils\DateTime;
                $arrInvoice = array('cl_partners_book_id' => $tmpStoreDoc->cl_partners_book_id,
                    'vat_active' => $this->settings->platce_dph,
                    'inv_date' => $tmpNow,
                    'vat_date' => $tmpNow,
                    'cl_currencies_id' => $tmpStoreDoc->cl_currencies_id,
                    'currency_rate' => $tmpStoreDoc->currency_rate);

                $numberSeries = array('use' => 'invoice', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number');
                $nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
                $arrInvoice[$numberSeries['table_key']] = $nSeries['id'];
                $arrInvoice[$numberSeries['table_number']] = $nSeries['number'];

                $tmpStatus = $numberSeries['use'];
                $nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch();
                if ($nStatus) {
                    $arrInvoice['cl_status_id'] = $nStatus->id;
                }
                $tmpInvoiceTypes = $this->InvoiceTypesManager->findAll()->where('inv_type = ?', '1')->order('default_type DESC')->fetch();
                if ($tmpInvoiceTypes) {
                    $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceTypes->id;
                } else {
                    $arrInvoice['cl_invoice_types_id'] = NULL;
                }

                $arrInvoice['cl_payment_types_id'] = $this->PartnersManager->getPaymentType($tmpStoreDoc->cl_partners_book_id);
                $arrInvoice['due_date'] = $this->PartnersManager->getDueDate($tmpStoreDoc->cl_partners_book_id, new \Nette\Utils\DateTime);
                $arrInvoice['cl_store_docs_id'] = $tmpStoreDoc->id;
                $arrInvoice['cl_company_branch_id'] = $this->user->getIdentity()->cl_company_branch_id;

                //if is reference to cl_commission_id then use cl_partners_branch_id,cl_payment_types_id, cl_users_id, cl_center_id
                if (!is_null($tmpStoreDoc->cl_commission_id)) {
                    $arrInvoice['cl_partners_branch_id'] = $tmpStoreDoc->cl_commission['cl_partners_branch_id'];
                    $arrInvoice['cl_payment_types_id'] = $tmpStoreDoc->cl_commission['cl_payment_types_id'];
                    $arrInvoice['cl_users_id'] = $tmpStoreDoc->cl_commission['cl_users_id'];
                    $arrInvoice['cl_center_id'] = $tmpStoreDoc->cl_commission['cl_center_id'];
                }


                $invoice = $this->InvoiceManager->insert($arrInvoice);
                //20.02.2019 - update cl_store_docs with cl_invoice_id
                $tmpStoreDoc->update(array('cl_invoice_id' => $invoice->id));

                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_id' => $invoice->id, 'cl_store_docs_id' => $tmpStoreDoc->id));


                //second cl_invoice_items
                $order = 1;
                foreach ($tmpStoreDoc->related('cl_store_move') as $key => $one) {

                    //20.02.2019 - update price_s at invoice item
                    $tmpItem = array();
                    $tmpItem['item_order'] = $order;
                    $tmpItem['cl_invoice_id'] = $invoice->id;
                    $tmpItem['cl_pricelist_id'] = $one->cl_pricelist_id;
                    $tmpItem['item_label'] = $one->cl_pricelist->item_label;
                    $tmpItem['units'] = $one->cl_pricelist->unit;
                    $tmpItem['vat'] = $one->cl_pricelist->vat;
                    $tmpItem['cl_store_move_id'] = $key;
                    $tmpItem['cl_storage_id'] = $one->cl_storage_id;
                    $tmpItem['description1'] = $one->description;
                    $tmpItem['quantity'] = $one->s_out;
                    if ($one->cl_storage->price_method == 0) {
                        $tmpItem['price_s'] = $one->price_s;
                    } else {
                        $tmpItem['price_s'] = $one->price_vap;
                    }
                    //$tmpItem['profit']		= $one->profit;
                    $tmpItem['discount'] = $one->discount;
                    $tmpItem['price_e'] = $one->price_e;
                    $tmpItem['price_e2'] = $one->price_e2;
                    $tmpItem['price_e2_vat'] = $one->price_e2_vat;
                    $invoiceItem = $this->InvoiceItemsManager->insert($tmpItem);
                    $one->update(array('cl_invoice_items_id' => $invoiceItem->id));
                    $order++;
                }
                //total sums of invoice
                $this->InvoiceManager->updateInvoiceSum($invoice->id);
                //redirect to invoice
                $this->redirect(':Application:Invoice:edit', array('id' => $invoice->id));
            }
        } else {
            $this->flashmessage($this->translator->translate('Faktura_nebyla_vytvořena'), 'warning');
        }
    }

    public function handleSaveStorePlacement()
    {
        if ($tmpData = $this->DataManager->find($this->id)) {
            $dataOther = array();
            $tmpTemplate = $this->createDocument($tmpData, 3, $dataOther);
            $this->pdfCreate($tmpTemplate, '', FALSE);
            //bdump('ted');
        }
    }

    public function handleBulkInsert()
    {
        $tmpData = $this->DataManager->find($this->id);
        if (is_null($tmpData->cl_storage_id)) {
            $this->flashMessage($this->translator->translate("Na_dokladu_není_nastaven_výchozí_sklad._Vložení_položek_není_možné"), "danger");
            $this->redrawControl('bscAreaEdit');
            $this->redrawControl('createDocs');
            $this->redrawControl('contents');
        } elseif (is_null($tmpData->cl_partners_book_id)) {
            $this->flashMessage($this->translator->translate("Na_dokladu_není_nastaven_odběratel_/_dodavatel._Vložení_položek_není_možné."), "danger");
            $this->redrawControl('bscAreaEdit');
            $this->redrawControl('createDocs');
            $this->redrawControl('contents');
        } else {
            $this->createDocShow = TRUE;
            $this->showModal('bulkInsert');
            $this->redrawControl('bscAreaEdit');
            $this->redrawControl('createDocs');
            $this->redrawControl('contents');
        }
    }

    public function insertBulkData($data)
    {
        $tmpData = $this->DataManager->find($this->id);

        $this->createDocShow = FALSE;
        $this->hideModal('bulkInsert');

        if ($tmpData->doc_type == 0) {
            //income
            $this->bulkIncome($data);

        } elseif ($tmpData->doc_type == 1) {
            //outcome
            $this->bulkOutcome($data);
        }
        $mySection = $this->getSession('bulkInsert-' . $this->DataManager->getTableName());
        $mySection['data'] = array();
        $mySection['lastId'] = 0;

        $this->flashMessage($this->translator->translate('Položky_byly_vloženy_do_dokladu') . $tmpData->doc_number, 'success');

        $this->redrawControl('contents');

    }

    private function bulkIncome($data)
    {

        $tmpDocs = $this->StoreDocsManager->find($this->id);
        foreach ($data as $key => $one) {
            $tmpPricelist = $this->PriceListManager->find($one['id']);
            $arrData = array();
            $s_in = $one['quantity'];
            $arrData['item_order'] = $one['item_order'];
            $arrData['cl_pricelist_id'] = $one['id'];
            $arrData['s_in'] = 0;
            $arrData['s_end'] = 0;
            $arrData['s_out'] = 0;
            $arrData['s_out_fin'] = 0;
            $arrData['s_total'] = 0;
            //$data['price_in']	                = $data['price_s'];
            //$data['price_in_vat']             = $data['price_s']*(1+($data['vat']/100));
            $arrData['price_e2'] = 0;
            $arrData['price_e2_vat'] = 0;
            $arrData['price_s'] = 0;
            if ($one['input_value'] > 0) {
                $arrData['price_in'] = $one['input_value'];
                $arrData['price_in_vat'] = $one['input_value'] * (1 + ($tmpPricelist['vat'] / 100));
            } else {
                $arrData['price_in'] = $tmpPricelist['price_s'];
                $arrData['price_in_vat'] = $tmpPricelist['price_s'] * (1 + ($tmpPricelist['vat'] / 100));
            }

            $arrData['cl_storage_id'] = $tmpDocs->cl_storage_id;
            $arrData['cl_store_id'] = NULL;
            $arrData['cl_invoice_items_id'] = NULL;
            $arrData['cl_invoice_items_back_id'] = NULL;
            $arrData['cl_store_docs_id'] = $tmpDocs->id;
            $arrData['vat'] = $tmpPricelist->vat;
            unset($arrData['id']);

            $row = $this->StoreMoveManager->insert($arrData);
            $arrData['id'] = $row->id;
            $arrData['s_in'] = $s_in;
            $arrData['s_end'] = $s_in;
            $data2 = $this->StoreManager->GiveInStore($arrData, $row, $tmpDocs);
            $this->StoreMoveManager->update($data2);
            $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
        }
        $this->UpdateSum();

    }


    private function bulkOutcome($data)
    {
        $tmpDocs = $this->StoreDocsManager->find($this->id);
        foreach ($data as $key => $one) {
            $tmpPricelist = $this->PriceListManager->find($one['id']);
            $arrData = array();
            $s_out = $one['quantity'];
            $arrData['item_order'] = $one['item_order'];
            $arrData['cl_pricelist_id'] = $one['id'];
            $arrData['s_in'] = 0;
            $arrData['s_end'] = 0;
            $arrData['s_out'] = 0;
            $arrData['s_total'] = 0;
            //$data['price_in']	                = $data['price_s'];
            //$data['price_in_vat']             = $data['price_s']*(1+($data['vat']/100));
            $arrData['price_e2'] = 0;
            $arrData['price_e2_vat'] = 0;
            $arrData['price_s'] = 0;
            $arrData['cl_storage_id'] = $tmpDocs->cl_storage_id;
            $arrData['cl_store_id'] = NULL;
            $arrData['cl_invoice_items_id'] = NULL;
            $arrData['cl_invoice_items_back_id'] = NULL;
            $arrData['cl_store_docs_id'] = $tmpDocs->id;
            unset($arrData['id']);

            $tmpPrice = $this->PricesManager->getPrice($tmpDocs->cl_partners_book,
                $one['id'], //cl_pricelist_id
                $tmpDocs->cl_currencies_id,
                $tmpDocs->cl_storage_id);

            if ($this->settings->platce_dph == 1) {
                $arrData['price_e'] = $tmpPrice['price'];
                $arrData['vat'] = $tmpPricelist->vat;
            } else {
                $arrData['price_e'] = $tmpPrice['price_vat'];
                $arrData['vat'] = 0;
            }
            $arrData['price_e2'] = $tmpPrice['price'];
            $arrData['price_e2_vat'] = $tmpPrice['price_vat'];

            $row = $this->StoreMoveManager->insert($arrData);
            $arrData['id'] = $row->id;
            $arrData['s_in'] = 0;
            $arrData['s_end'] = 0;
            $arrData['s_out'] = $s_out;

            $data2 = $this->StoreManager->GiveOutStore($arrData, $row, $tmpDocs);
            $this->StoreMoveManager->update($data2);
            //23.07.2021 - removed because VAP should be changed only on income
            //$this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);

        }
        //$this->StoreManager->UpdateSum($tmpDocs->id);
        $this->UpdateSum();

    }


    public function handleCreateDelivery($bscId)
    {
        $retArr = $this->DeliveryNoteManager->createDelivery($bscId);
        if (self::hasError($retArr)) {
            $this->flashmessage($this->translator->translate($retArr['error']), 'warning');
        } else {
            $this->flashmessage($this->translator->translate($retArr['success']), 'success');
            //redirect to deliverynote
            $this->redirect(':Application:DeliveryNote:edit', array('id' => $retArr['deliveryN_id']));
        }
        $this->redrawControl('content');
        //find cl_store_docs record

    }

    public function CreateInvoiceArrived($bscId)
    {
        try {
            $tmpData = $this->DataManager->find($bscId);
            if ($tmpData->doc_type == 0) {
                $result = $this->StoreManager->createInvoiceArrived($bscId);
                if (!$result) {
                    //$this->translator->setPrefix(['applicationModule.Store']);
                    $this->flashmessage($this->translator->translate('Faktura_přijatá_nebyla_vytvořena_Chybí_číslo_faktury'), 'danger');
                }
            }


        } catch (\Exception $e) {
            $this->flashmessage($this->translator->translate('Chyba_při_vytváření_faktury_přijaté') . $e->getMessage(), 'warning');
        }
    }

    /**CallBack from listgrid
     * @param $itemId
     * @param $type
     */
    public function customFunction($itemId, $type)
    {
        $this->itemId = $itemId;
        if ($type == 'changePlace') {
            $this['changeStoragePlace']->setItemId($itemId);
            $this->redrawControl('bscAreaEdit');
            $this->redrawControl('createDocs');
        }
        $this->showModal($type);

        //$this['storeListgrid']->redrawControl('editLines');
        //$this['sumOnDocs']->redrawControl();

    }

    public function getStoragePlaceName($arrData)
    {
        $strPlace = $this->StoragePlacesManager->getStoragePlaceName($arrData);
        return ($strPlace);
    }


    /**CallBack from component ChangeStoragePlace, used for redraw editline
     * @param $item_id
     */
    public function afterChangeStorage($item_id)
    {
        //$this->redrawControl();
        $this['storeListgrid']->redrawControl('editLines');
    }

    public function getStoragePlaceNameOut($arrData)
    {
        $strPlace = '';
        if (!is_null($arrData['id'])) {
            $tmpStoreMoveSource = $this->StoreOutManager->findAll()->where('cl_store_move_id = ?', $arrData['id']);
            $arrPlace = array();
            foreach ($tmpStoreMoveSource as $key => $one) {
                $tmpMoveIn = $this->StoreMoveManager->find($one->cl_store_move_in_id);
                if ($tmpMoveIn && !empty($tmpMoveIn['cl_storage_places'])) {
                    $tmpArr2 = array();
                    $tmpArr = json_decode($tmpMoveIn['cl_storage_places'], TRUE);
                    foreach ($tmpArr as $key => $one) {
                        $tmpArr2[] = $key;
                    }
                    //if ($tmpMoveIn && !is_null($tmpMoveIn->cl_storage_places_id)) {
                    $tmpPlaces = $this->StoragePlacesManager->findAll()->
                    where('id IN ?', $tmpArr2)->
                    select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');

                } else {
                    $tmpPlaces = array();
                }
                if (count($tmpPlaces) > 0) {
                    $arrPlace[] = implode(', ', $tmpPlaces);
                }

            }
            $strPlace = implode(', ', $arrPlace);
        }

        return ($strPlace);

    }


    public function handleSavePDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);

        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, $noPreview);
    }

    public function handleDownloadPDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);

        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, TRUE);
    }

    public function preparePDFData($id)
    {
        $data = $this->DataManager->find($id);
        if ($data->doc_type == 0) //income
        {
            $latteIndex = 1;
        } elseif ($data->doc_type == 1) //outgoing
        {
            $latteIndex = 2;
        } else {
            $latteIndex = 0;
        }

        $arrData = array('settings' => $this->settings,
            'stamp' => $this->getStamp(),
            'logo' => $this->getLogo(),
            'latteIndex' => $latteIndex);
        return $arrData;
    }

    public function getProfitStoreIn($arr)
    {
        bdump($arr);
        $newArr = array();
        if ($arr['cl_pricelist.price'] > 0) {
            $profit = (1 - ($arr['price_s'] / $arr['cl_pricelist.price'])) * 100;
        } else {
            $profit = 0;
        }

        return $profit;
    }

    public function handleNew($data = '', $defData)
    {
        if (intval($data) > 0) {
            $this->numberSeries['cl_number_series_id'] = $data;
            $data = '';
        } else {
            $this->numberSeries['use'] = $data;
        }
        parent::handleNew($data, $defData);
    }

    public function handleExportXML()
    {
        $tmpData = $this->StoreMoveManager->findAll()->where('cl_store_docs_id = ?', $this->id)->order('item_order');
        $tmpParent = $this->DataManager->find($this->id);
        $arrResult = array();
        foreach ($tmpData as $key => $one) {
            $tmpInv = $one->toArray();
            $arrLine = array();
            foreach ($tmpInv as $key1 => $one1) {
                $arrLine[$key1] = $one1;
            }
            $arrResult[$key] = $arrLine;
            $tmpPricelist = $one->ref('cl_pricelist');
            $arrResult[$key]['cl_pricelist'] = array('id' => $tmpPricelist['id'], 'identification' => $tmpPricelist['identification'], 'item_label' => $tmpPricelist['item_label'],
                'unit' => $tmpPricelist['unit']);
        }
        //date('Ymd-Hi')
        $this->sendResponse(new \XMLResponse\XMLResponse($arrResult, $tmpParent->doc_number . ".xml", NULL, "cl_store_doc"));

    }

    public function handleImportXML()
    {
        $this->createDocShow = TRUE;
        $this->showModal('uploadXML');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }


    protected function createComponentUploadXMLForm()
    {
        $form = new Form;
        $form->addHidden('id')
            ->setDefaultValue($this->bscId);
        $form->addUpload('upload_xml', $this->translator->translate('XML_soubor'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('Maximální_velikost_souboru_je_1024_kB'), 1024 * 1024 /* v bytech */);
        $form->addSubmit('submit', $this->translator->translate('Importovat'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "XMLFormSubmited");
        return $form;
    }

    /**
     * UploadXML form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function xmlFormSubmited($form)
    {
        $values = $form->getValues();
        try {
            $file = $form->getHttpData($form::DATA_FILE, 'upload_xml');
            if ($file && $file->isOk()) {
                $xml = $file->getContents();
                $this->importXML($xml, $values->id);
            }
            $this->createDocShow = FALSE;
            $this->hideModal('uploadXML');
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }


    public function importXML($xml, $id)
    {
        $ob = simplexml_load_string($xml);
        $json = json_encode($ob);
        $dataIn = json_decode($json, true);
//		bdump($dataIn);
        $tmpParent = $this->DataManager->find($id);
        if ($tmpParent) {
            if ($tmpParent->doc_type == 0) {
                $this->xmlToIncome($dataIn, $tmpParent);
            } elseif ($tmpParent->doc_type == 1) {
                $this->xmlToOutcome($dataIn, $tmpParent);
            }
        } else {
            $this->flashMessage($this->translator->translate('Chyba_při_importu._Nenalezen_hlavní_doklad.'), 'danger');
        }
    }

    private function xmlToOutcome($dataIn, $tmpParent)
    {
        $i = $tmpParent->related('cl_store_move')->max('item_order') + 1;
        try {
            if (!isset($tmpParent['cl_storage_id']) || is_null($tmpParent['cl_storage_id'])) {
                throw new \Exception($this->translator->translate('Chybí_sklad_na_kartě_dokladu'));
            }
            foreach ($dataIn as $key => $one) {
                $data = $one;
                foreach ($one as $keyo => $oneo) {
                    if (is_array($oneo))
                        unset($data[$keyo]);
                }
                if (!isset($data['cl_pricelist_id']) || !isset($data['s_out'])) {
                    throw new \Exception($this->translator->translate('Chybný_formát_souboru'));
                }
                $s_out = $data['s_out'];
                $data['s_in'] = 0;
                $data['s_end'] = 0;
                $data['s_out'] = 0;
                $data['s_out_fin'] = 0;
                $data['s_total'] = 0;
                $data['cl_storage_id'] = $tmpParent['cl_storage_id'];
                $data['cl_store_id'] = NULL;
                $data['cl_invoice_items_id'] = NULL;
                $data['cl_invoice_items_back_id'] = NULL;
                $data['cl_store_docs_id'] = $tmpParent->id;
                $data['item_order'] = $i;
                unset($data['id']);
                $row = $this->StoreMoveManager->insert($data);
                $data['id'] = $row->id;
                $data['s_in'] = 0;
                $data['s_end'] = 0;
                $data['s_out'] = $s_out;

                $data2 = $this->StoreManager->GiveOutStore($data, $row, $tmpParent);
                $this->StoreMoveManager->update($data2);

                //23.07.2021 - removed because VAP should be changed only on income
                //$this->StoreManager->updateVAP($data2['cl_store_id'], $tmpParent['doc_date']);
                $i++;
            }
            $this->UpdateSum();
            $this->flashMessage($this->translator->translate('Data_z_XML_soubory_byly_naimportovány_do_výdejky.'), 'success');
        } catch (\Exception $e) {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_při_importu') . $e->getMessage(), 'danger');
        }

    }


    private function xmlToIncome($dataIn, $tmpParent)
    {
        $i = $tmpParent->related('cl_store_move')->max('item_order') + 1;
        try {
            if (!isset($tmpParent['cl_storage_id']) || is_null($tmpParent['cl_storage_id'])) {
                throw new \Exception($this->translator->translate('Chybí_sklad_na_kartě_dokladu.'));
            }
            foreach ($dataIn as $key => $one) {
                $data = $one;
                foreach ($one as $keyo => $oneo) {
                    if (is_array($oneo))
                        unset($data[$keyo]);
                }
                if (!isset($data['cl_pricelist_id']) || !isset($data['s_in'])) {
                    throw new \Exception($this->translator->translate('Chybný_formát_souboru'));
                }
                $s_in = $data['s_in'];
                $data['s_in'] = 0;
                $data['s_end'] = 0;
                $data['s_out'] = 0;
                $data['s_out_fin'] = 0;
                unset($data['s_total']); // Very important !!
                $data['cl_storage_id'] = $tmpParent['cl_storage_id'];
                $data['cl_store_id'] = NULL;
                $data['cl_invoice_items_id'] = NULL;
                $data['cl_invoice_items_back_id'] = NULL;
                $data['cl_store_docs_id'] = $tmpParent['id'];
                $data['item_order'] = $i;
                unset($data['created']);
                unset($data['create_by']);
                unset($data['changed']);
                unset($data['change_by']);
                unset($data['id']);
                unset($data['cl_company_id']);
                unset($data['cl_users_id']);
                unset($data['cl_storage_places_id']);
                unset($data['cl_invoice_items_id']);
                unset($data['cl_invoice_items_back_id']);
                unset($data['cl_delivery_note_items_id']);
                unset($data['cl_store_docs_macro_id']);
                unset($data['cl_store_docs_macro_in_id']);
                unset($data['cl_pricelist_macro_id']);
                unset($data['cl_countries_id']);
                unset($data['cl_store_docs_in_id']);
                $row = $this->StoreMoveManager->insert($data);
                $data['id'] = $row->id;
                $data['s_in'] = $s_in;
                $data['s_end'] = $s_in;
                $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpParent);
                $this->StoreMoveManager->update($data2);
                $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpParent['doc_date']);
                $i++;
            }
            $this->UpdateSum();
            $this->flashMessage($this->translator->translate('Data_z_XML_soubory_byly_naimportovány_do_příjemky'), 'success');
        } catch (\Exception $e) {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_při_importu_') . $e->getMessage(), 'danger');
        }

    }


    /**
     * delete imported items which are not on store
     */
    public function handleDeleteAll()
    {
        $itemsToDelete = $this->StoreMoveManager->findAll()->where('cl_store_docs_id = ? AND import = 1 AND import_fin = 0', $this->id);
        $count = count($itemsToDelete);
        try {
            $itemsToDelete->delete();
            $this->StoreManager->UpdateSum($this->id);
            $this->flashMessage($this->translator->translate('Bylo_vymazáno_') . $count . $this->translator->translate('_položek'), 'success');
        } catch (\Exception $e) {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_při_mazání_položek.'), 'error');
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this['storeListgrid']->redrawControl('paginator');
        $this['storeListgrid']->redrawControl('editLines');
        $this->redrawControl('flashMessage');
    }

    /**
     * put imported items on store
     */
    public function handleImportToStore()
    {
        try {
            $tmpParentData = $this->DataManager->find($this->id);
            $tmpItems = $this->StoreMoveManager->findAll()->where('cl_store_docs_id = ? AND import = 1 AND import_fin = 0', $this->id);
            if (is_null($tmpParentData['cl_order_id']) && $tmpParentData['no_order'] == 0) {
                $this->flashMessage($this->translator->translate('V_příjemce_není_číslo_objednávky_Naskladnění_neproběhlo'), 'warning');
            } else {
                session_write_close();
                $maxCount = count($tmpItems);
                $i = 1;
                if ($maxCount > 0) {
                    foreach ($tmpItems as $key => $one) {
                        $this->UserManager->setProgressBar($i++, $maxCount, $this->user->getId(), $this->translator->translate('Naskladnění_importu'));
                        $data = $one->toArray();
                        $tmpData = $this->StoreMoveManager->find($data['id']);
                        if (isset($data['batch']) && $data['batch'] == '') {
                            $data['batch'] = NULL;
                        }
                        $data['s_end'] = $data['s_in'];
                        $data['s_out'] = 0;
                        $data['s_total'] = $data['s_in'];
                        //$data['price_in']	    = $data['price_s'];

                        $data['vat'] = $tmpData->cl_pricelist['vat'];
                        $data['price_in_vat'] = $data['price_in'] * (1 + ($tmpData->cl_pricelist['vat'] / 100));
                        $data['price_e2'] = $data['price_in'] * $data['s_in'];
                        $data['price_e2_vat'] = $data['price_in_vat'] * $data['s_in'];
                        $data['cl_store_id'] = NULL;
                        $data['import_fin'] = 1;
                        //bdump($data, 'ImportToStore');
                        $dataNew = $this->StoreManager->GiveInStore($data, $tmpData, $tmpParentData);
                        //bdump($data, 'ImportToStore - dataNew');
                        if ($tmpParentData['no_order'] == 0) {
                            //pair imported record with cl_order_items if exits
                            //cl_order.cl_partners_book_id = ? AND
                            //cl_order.cl_status.s_fin = 0 AND cl_order.cl_status.s_storno = 0 AND
                            //cl_order_items.cl_store_docs_id IS NULL AND
                            $orderItems = $this->OrderItemsManager->findAll()->where('cl_order_id =? AND
                                                                                            cl_order_items.cl_storage_id = ? AND
                                                                                            cl_order_items.cl_pricelist_id = ?',
                                $tmpParentData['cl_order_id'],
                                $one['cl_storage_id'], $one['cl_pricelist_id'])->fetch();
                            if ($orderItems) {
                                $orderItems->update(array('rea_date' => $tmpParentData['doc_date'],
                                    'quantity_rcv' => $data['s_in'],
                                    'price_e_rcv' => $data['price_in'],
                                    'cl_store_docs_id' => $tmpParentData['id'],
                                    'cl_store_move_id' => $one['id']));
                            }
                        }

                        $this->StoreMoveManager->update($dataNew);
                    }
                    $this->UserManager->resetProgressBar($this->user->getId());
                    $this->StoreManager->UpdateSum($this->id);
                    $this->flashMessage($this->translator->translate('Naskladněno_bylo_') . $maxCount . $this->translator->translate('__položek'), 'success');
                } else {
                    $this->flashMessage($this->translator->translate('Žádná_položka_není_importována_a_nenaskladněna'), 'danger');
                }

            }
        } catch (\Exception $e) {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_při_importu_položek'), 'error');
            $this->flashMessage($e->getMessage(), 'error');
        }
        $this->redrawControl('content');
        //$this['storeListgrid']->redrawControl('paginator');
        //$this['storeListgrid']->redrawControl('editLines');
    }


    public function handleImportEDI($bscId)
    {
        //$this->importType = $type;
        $this->showModal('importEDI');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentImportEDIForm()
    {
        $form = new Form;
        $form->addUpload('upload_file', $this->translator->translate('Importní_soubor'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('Maximální_velikost_souboru_je_2_MB'), 2000 * 1024 /* v bytech */);
        $form->addSubmit('submit', $this->translator->translate('Importovat'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "ImportEDIFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function importEDIFormSubmited($form)
    {
        $values = $form->getValues();
        try {
            $file = $form->getHttpData($form::DATA_FILE, 'upload_file');
            $result = 0;
            if ($file && $file->isOk()) {
                $result = $this->importEDI($file);
            }
            $this->flashMessage($this->translator->translate('Importováno_bylo_') . $result . $this->translator->translate('__položek.'), 'success');
            $this->hideModal('importEDI');
            $this->redrawControl('flash');
            $this->redrawControl('content');
        } catch (\Exception $e) {
            $form->addError($e->getMessage());
            $this->redrawControl('flash');
            $this->redrawControl('content');
        }
    }


    private function importEDI($file)
    {
        //return;
        try {
            $json_result = EDI\INH::import($file);

            $arrResult = json_decode($json_result, TRUE);
            //bdump($arrResult);
            //die;
            if (array_key_exists('error', $arrResult)) {
                throw new \Exception($arrResult['error']);
            }

            $tmpParentData = $this->DataManager->find($this->id);
            if (!$tmpParentData) {
                return;
            }

            $counter = 1;
            foreach ($arrResult['success'] as $key => $one) {
                if ($one[1] == 'HDR') {
                    //header
                } elseif ($one[1] == 'HDD') {
                    //header2
                } elseif ($one[1] == 'LIN') {
                    //record
                    //find in cl_pricelist
                    $tmpPricelist = $this->PriceListManager->findAll()->where('ean_code = ?', $one['ean_code'])->fetch();
                    if (!$tmpPricelist) {
                        $arrPricelist = array();
                        $itemIdentification = substr($one['item_label'], 0, strpos($one['item_label'], ' '));
                        $itemLabel = substr($one['item_label'], strpos($one['item_label'], ' ') + 1);
                        $arrPricelist['item_label'] = $itemLabel;
                        $arrPricelist['identification'] = $itemIdentification;
                        $arrPricelist['ean_code'] = $one['ean_code'];
                        $arrPricelist['price_s'] = $one['price_in'];
                        $arrPricelist['vat'] = $one['vat'];
                        $arrPricelist['unit'] = $one['units'];
                        $arrPricelist['cl_currencies_id'] = $tmpParentData['cl_currencies_id'];
                        $arrPricelist['cl_partners_book_id'] = $tmpParentData['cl_partners_book_id'];
                        $newId = $this->PriceListManager->insert($arrPricelist);
                        $tmpPricelist = $this->PriceListManager->find($newId);
                    }

                    $arrData = array();
                    $arrData['cl_store_docs_id'] = $this->id;
                    $arrData['cl_pricelist_id'] = $tmpPricelist['id'];
                    $arrData['item_order'] = $counter++;
                    $arrData['cl_storage_id'] = $tmpParentData['cl_storage_id'];
                    $arrData['price_in'] = $one['price_in'];
                    $arrData['vat'] = $one['vat'];
                    $arrData['s_in'] = $one['quantity'];
                    $arrData['import'] = 1;
                    $this->StoreMoveManager->insert($arrData);
                }
            }
            $this->StoreManager->updateSum($this->id);
            return ($counter);
        } catch (\Exception $ex) {
            $this->flashMessage($this->translator->translate('Import_skončil_s_chybou_') . $ex->getMessage(), 'danger');
            return 0;
        }
    }

    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }


    protected function createComponentReportIncome($name)
    {
        $form = new \Nette\Application\UI\Form($this, $name);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Příjem_od'), 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Příjem_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));


        $tmpArrPartners = $this->PartnersManager->findAll()->where('supplier = 1')->order('company')->fetchPairs('id', 'company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Dodavatel'), $tmpArrPartners)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele_pro_tisk'))
            ->setHtmlAttribute('multiple', 'multiple');

        /*
        $tmpArrPartners2 = $this->PartnersManager->findAll()->where('customer = 1')->order('company')->fetchPairs('id','company');
        $form->addMultiSelect('cl_partners_book2', $this->translator->translate('Odběratel'), $tmpArrPartners2)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele_pro_tisk'))
            ->setHtmlAttribute('multiple','multiple');*/

        $form->addTextarea('identification', $this->translator->translate('Kód_zboží'), 30, 3)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kód_zboží_nebo_jeho_část_nebo_více_kódů_oddělených_středníkem_případně_čárkou'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklad'), $tmpArrStorage)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'))
            ->setHtmlAttribute('multiple', 'multiple');

        $tmpArrGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_pricelist_group_id', $this->translator->translate('Skupina'), $tmpArrGroup)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'))
            ->setHtmlAttribute('multiple', 'multiple');

        $form->addMultiSelect('cl_pricelist_group_id2', $this->translator->translate('Bez_skupiny'), $tmpArrGroup)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'))
            ->setHtmlAttribute('multiple', 'multiple');

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('save_pdf', $this->translator->translate('uložit_do_PDF'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('save', $this->translator->translate('Tisk'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportIncome');
        $form->onSuccess[] = array($this, 'SubmitReportIncome');
        $form->onValidate[] = array($this, 'FormValidate2');
        return $form;
    }

    public function formValidate2(Form $form)
    {
        $data = $form->values;
    }

    public function stepBackReportIncome()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function submitReportIncome(Form $form)
    {
        $data = $form->values;
        if ($form['save']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy()) {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));

            //dump($data);
            //die;
            $dataReport = $this->DataManager->findAll()->select('cl_store_docs.doc_date, cl_store_docs.doc_number, cl_partners_book.company, cl_store_docs.invoice_number,cl_store_docs.delivery_number,
                                            SUM(:cl_store_move.price_e2) AS price_e2,
                                            SUM(:cl_store_move.price_in * :cl_store_move.s_in) AS price_in, SUM(:cl_store_move.price_s * :cl_store_move.s_in) AS price_s, cl_store_docs.cl_partners_book_id, cl_currencies.currency_code, cl_store_docs.currency_rate')->
            where('cl_store_docs.doc_type = 0 AND (cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?)', $data['date_from'], $data['date_to'])
                ->group('cl_store_docs.id')
                ->order('cl_store_docs.doc_date');


            if ($data['identification'] != '') {
                $tmpIdent = str_ireplace(';', ',', $data['identification']);
                $tmpIdent = str_ireplace(' ', '', $tmpIdent);
                $arrIdentification = explode(',', $tmpIdent);
                if (count($arrIdentification) == 1) {
                    $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.identification LIKE ?', '%' . $data['identification'] . '%');
                } else {
                    $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.identification IN ?', $arrIdentification);
                }
            }

            $data['cl_partners_book'] = $data['cl_partners_book'] == NULL ? array() : $data['cl_partners_book'];
            //$data['cl_partners_book2']  = $data['cl_partners_book2'] == NULL ? array() : $data['cl_partners_book2'];
            //$data['cl_producer']        = $data['cl_producer'] == NULL ? array() : $data['cl_producer'];

            if (count($data['cl_partners_book']) >= 1) {
                //$dataReport = $dataReport->where(array('cl_pricelist.cl_partners_book_id' =>  $data['cl_partners_book']));
                //$dataReport = $dataReport->where('cl_pricelist.cl_partners_book_id IN (?) OR cl_store_docs_in.cl_partners_book_id IN (?)', $data['cl_partners_book'], $data['cl_partners_book']);
                //$dataReport->alias(':cl_store_out.cl_store_move', 'cl_store_move_in');
                //$dataReport->alias('cl_store_move_in.cl_store_docs', 'cl_store_docs_in');
                $dataReport = $dataReport->where('cl_store_docs.cl_partners_book_id IN (?)', $data['cl_partners_book']);

            }
            /*if(count($data['cl_partners_book2']) >= 1){
                $dataReport = $dataReport->where('cl_store_docs.cl_partners_book_id IN ?', $data['cl_partners_book2']);
            }

            if(count($data['cl_producer']) >= 1){
                $dataReport = $dataReport->where('cl_pricelist.cl_producer_id IN ?', $data['cl_producer']);
            }*/

            if (count($data['cl_storage_id']) > 0) {
                $dataReport = $dataReport->where(array(':cl_store_move.cl_storage_id' => $data['cl_storage_id']));
            }

            if (count($data['cl_pricelist_group_id']) > 0) {
                $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.cl_pricelist_group_id IN (?)', $data['cl_pricelist_group_id']);
            }

            if (count($data['cl_pricelist_group_id2']) > 0) {
                $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.cl_pricelist_group_id NOT IN (?)', $data['cl_pricelist_group_id2']);
            }


            //dump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Příjmy_v_období');

            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
            where(array('cl_partners_book.id' => $data['cl_partners_book']))->
            order('company');
            $dataOther['dataSettingsIdentification'] = $data['identification'];
            $dataOther['dataSettingsStorage'] = $this->StorageManager->findAll()->where(array('id' => $data['cl_storage_id']))->order('name');
            $dataOther['dataSettingsPricelistGroup'] = $this->PriceListGroupManager->findAll()->where(array('id' => $data['cl_pricelist_group_id']))->order('name');
            $dataOther['dataSettingsPricelistGroup2'] = $this->PriceListGroupManager->findAll()->where(array('id' => $data['cl_pricelist_group_id2']))->order('name');
            /*            $dataOther['customers']	= $this->PartnersManager->findAll()->
                                                    where(array('cl_partners_book.id' => $data['cl_partners_book2']))->
                                                    order('company');
                        $dataOther['customersIds'] = $this->PartnersManager->findAll()->
                                                    where(array('cl_partners_book.id' => $data['cl_partners_book2']))->
                                                    order('company')->fetchPairs('id');
                        $dataOther['producers'] = $this->PartnersManager->findAll()->
                                                    where(array('cl_partners_book.id' => $data['cl_producer']))->
                                                    order('company');*/
            //$dataOther['cl_store_move_in'] = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 0');

            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Store/ReportIncome.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);

            if ($form['save']->isSubmittedBy()) {
                //$this->pdfCreate($template, $tmpTitle);
                $this->pdfCreate($template, 'Příjmy v období' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Příjmy_v_období");
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            } elseif ($form['save_pdf']->isSubmittedBy()) {
                $this->pdfCreate($template, $tmpTitle, FALSE, TRUE);
            }

        }
    }

    protected function createComponentReportOutcome($name)
    {
        $form = new \Nette\Application\UI\Form($this, $name);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Výdej_od'), 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Výdej_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $tmpArrPartners2 = $this->PartnersManager->findAll()->where('customer = 1')->order('company')->fetchPairs('id', 'company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Odběratel'), $tmpArrPartners2)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele_pro_tisk'))
            ->setHtmlAttribute('multiple', 'multiple');

        $form->addTextarea('identification', $this->translator->translate('Kód_zboží'), 30, 3)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kód_zboží_nebo_jeho_část_nebo_více_kódů_oddělených_středníkem_případně_čárkou'));

        $tmpArrStorage = $this->StorageManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_storage_id', $this->translator->translate('Sklad'), $tmpArrStorage)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_sklady_pro_tisk'))
            ->setHtmlAttribute('multiple', 'multiple');

        $tmpArrGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_pricelist_group_id', $this->translator->translate('Skupina'), $tmpArrGroup)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'))
            ->setHtmlAttribute('multiple', 'multiple');

        $form->addMultiSelect('cl_pricelist_group_id2', $this->translator->translate('Bez_skupiny'), $tmpArrGroup)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_skupinu'))
            ->setHtmlAttribute('multiple', 'multiple');

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('save_pdf', $this->translator->translate('uložit_do_PDF'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('save', $this->translator->translate('Tisk'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportOutcome');
        $form->onSuccess[] = array($this, 'SubmitReportOutcome');
        $form->onValidate[] = array($this, 'FormValidate3');
        return $form;
    }

    public function formValidate3(Form $form)
    {
        $data = $form->values;
    }

    public function stepBackReportOutcome()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function submitReportOutcome(Form $form)
    {
        $data = $form->values;
        if ($form['save']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy()) {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));


            $dataReport = $this->DataManager->findAll()->select('cl_store_docs.doc_date, cl_store_docs.doc_number, cl_partners_book.company, cl_store_docs.invoice_number,cl_store_docs.delivery_number, 
                                cl_invoice.inv_number AS cl_invoice_number, cl_sale.sale_number AS cl_sale_number,
                                                                        SUM(:cl_store_move.price_e2) AS price_e2, SUM(:cl_store_move.price_s * :cl_store_move.s_out) AS price_s, cl_store_docs.cl_partners_book_id, cl_currencies.currency_code, cl_store_docs.currency_rate')->
            where('cl_store_docs.doc_type = 1 AND (cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?)', $data['date_from'], $data['date_to'])
                ->group('cl_store_docs.id')
                ->order('cl_store_docs.doc_date');


            if ($data['identification'] != '') {
                $tmpIdent = str_ireplace(';', ',', $data['identification']);
                $tmpIdent = str_ireplace(' ', '', $tmpIdent);
                $arrIdentification = explode(',', $tmpIdent);
                if (count($arrIdentification) == 1) {
                    $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.identification LIKE ?', '%' . $data['identification'] . '%');
                } else {
                    $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.identification IN ?', $arrIdentification);
                }
            }

            $data['cl_partners_book'] = $data['cl_partners_book'] == NULL ? array() : $data['cl_partners_book'];

            if (count($data['cl_partners_book']) >= 1) {
                $dataReport = $dataReport->where('cl_store_docs.cl_partners_book_id IN (?)', $data['cl_partners_book']);

            }

            if (count($data['cl_storage_id']) > 0) {
                $dataReport = $dataReport->where(array(':cl_store_move.cl_storage_id' => $data['cl_storage_id']));
            }

            if (count($data['cl_pricelist_group_id']) > 0) {
                $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.cl_pricelist_group_id IN (?)', $data['cl_pricelist_group_id']);
            }

            if (count($data['cl_pricelist_group_id2']) > 0) {
                $dataReport = $dataReport->where(':cl_store_move.cl_pricelist.cl_pricelist_group_id NOT IN (?)', $data['cl_pricelist_group_id2']);
            }


            //dump($dataReport->fetchAll());
            //die;
            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Výdeje_v_období');

            $dataOther = [];
            $dataSettings = $data;
            $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
            where(['cl_partners_book.id' => $data['cl_partners_book']])->
            order('company');
            $dataOther['dataSettingsIdentification'] = $data['identification'];
            $dataOther['dataSettingsStorage'] = $this->StorageManager->findAll()->where(['id' => $data['cl_storage_id']])->order('name');
            $dataOther['dataSettingsPricelistGroup'] = $this->PriceListGroupManager->findAll()->where(['id' => $data['cl_pricelist_group_id']])->order('name');
            $dataOther['dataSettingsPricelistGroup2'] = $this->PriceListGroupManager->findAll()->where(['id' => $data['cl_pricelist_group_id2']])->order('name');
            /*            $dataOther['customers']	= $this->PartnersManager->findAll()->
                                                    where(array('cl_partners_book.id' => $data['cl_partners_book2']))->
                                                    order('company');
                        $dataOther['customersIds'] = $this->PartnersManager->findAll()->
                                                    where(array('cl_partners_book.id' => $data['cl_partners_book2']))->
                                                    order('company')->fetchPairs('id');
                        $dataOther['producers'] = $this->PartnersManager->findAll()->
                                                    where(array('cl_partners_book.id' => $data['cl_producer']))->
                                                    order('company');*/
            //$dataOther['cl_store_move_in'] = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 0');

            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Store/ReportOutcome.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);

            if ($form['save']->isSubmittedBy()) {
                //$this->pdfCreate($template, $tmpTitle);
                $this->pdfCreate($template, 'Výdeje v období' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Výdeje_v_období");
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            } elseif ($form['save_pdf']->isSubmittedBy()) {
                $this->pdfCreate($template, $tmpTitle, FALSE, TRUE);
            }

        }
    }

    public function getTransportPrice($arr)
    {
        return ($arr['transport_km'] * (is_numeric($arr['cl_transport_types.price_km']) ? $arr['cl_transport_types.price_km'] : 0));
    }


    protected function createComponentReportWaste($name)
    {
        $form = new \Nette\Application\UI\Form($this, $name);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', $this->translator->translate('Období_od'), 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $this->translator->translate('Období_do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $tmpArrPartners2 = $this->PartnersManager->findAll()->order('company')->fetchPairs('id', 'company');
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Firma'), $tmpArrPartners2)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_firmu_pro_tisk'))
            ->setHtmlAttribute('multiple', 'multiple');

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('save_xls', $this->translator->translate('uložit_do_XLS'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');            

        $form->addSubmit('save_pdf', $this->translator->translate('uložit_do_PDF'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('save', $this->translator->translate('Tisk'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportWaste');
        $form->onSuccess[] = array($this, 'SubmitReportWaste');
        $form->onValidate[] = array($this, 'FormValidate4');
        return $form;
    }

    public function formValidate4(Form $form)
    {
        $data = $form->values;
    }

    public function stepBackReportWaste()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function submitReportWaste(Form $form)
    {
        $data = $form->values;
        if ($form['save']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_xls']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy()) {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));


            $dataReport = $this->DataManager->findAll()->select('cl_partners_book.company,:cl_store_move.cl_pricelist.cl_waste_category.waste_code,:cl_store_move.cl_pricelist.cl_waste_category.name,
                                                                 SUM(:cl_store_move.s_out) AS s_out, SUM(:cl_store_move.s_in) AS s_in, :cl_store_move.cl_pricelist.unit')->
                                            where('cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?', $data['date_from'], $data['date_to'])
                                            ->group('cl_partners_book.company,:cl_store_move.cl_pricelist.cl_waste_category.waste_code,:cl_store_move.cl_pricelist.cl_waste_category.name,:cl_store_move.cl_pricelist.unit')
                                            ->order('cl_partners_book.company');


            $data['cl_partners_book'] = $data['cl_partners_book'] == NULL ? array() : $data['cl_partners_book'];

            if (count($data['cl_partners_book']) >= 1) {
                $dataReport = $dataReport->where('cl_store_docs.cl_partners_book_id IN (?)', $data['cl_partners_book']);
            }

            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('Odpady_v_období');

            $dataOther = [];
            $dataSettings = $data;
            $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
                                                            where(['cl_partners_book.id' => $data['cl_partners_book']])->
                                                            order('company');
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Store/ReportWaste.latte', $dataOther, $dataSettings, $tmpTitle);
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);

            if ($form['save']->isSubmittedBy()) {
                //$this->pdfCreate($template, $tmpTitle);
                $this->pdfCreate($template, 'Odpady v období' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Odpady_v_období");
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }elseif($form['save_xls']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Odpady_v_období");
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".xls", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_XLS_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }                
            } elseif ($form['save_pdf']->isSubmittedBy()) {
                $this->pdfCreate($template, $tmpTitle, FALSE, TRUE);
            }

        }
    }    


}
