<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Caching\Cache;
use Nette\Utils\DateTime;
use Pohoda;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Tracy\Debugger;
use App\APIModule\Presenters;
use Nette\Application\Responses\FileResponse;
use Netpromotion\Profiler\Profiler;

class InvoicePresenter extends \App\Presenters\BaseListPresenter
{


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
     * @var \App\Model\InvoiceManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;


    /**
     * @inject
     * @var \App\Model\InvoiceAdvanceManager
     */
    public $InvoiceAdvanceManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteManager
     */
    public $DeliveryNoteManager;


    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\CashDefManager
     */
    public $CashDefManager;


    /**
     * @inject
     * @var \App\Model\CashManager
     */
    public $CashManager;


    /**
     * @inject
     * @var \App\Model\InvoiceItemsManager
     */
    public $InvoiceItemsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceItemsBackManager
     */
    public $InvoiceItemsBackManager;

    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;

    /**
     * @inject
     * @var \App\Model\CurrenciesManager
     */
    public $CurrenciesManager;


    /**
     * @inject
     * @var \App\Model\DeliveryNotePaymentsManager
     */
    public $DeliveryNotePaymentsManager;


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

    public function createComponentInvoiceBacklistgrid()
    {
        //$this->translator->setPrefix(['applicationModule.invoice']);
        $tmpParentData = $this->DataManager->find($this->id);
        if ($tmpParentData->price_e_type == 1) {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        } else {
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }

        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_invoice_items__description1'])) {
            $userTmpAdapt['cl_invoice_items__description1'] = $this->translator->translate("Poznámka_1");
        }
        if (!isset($userTmpAdapt['cl_invoice_items__description2'])) {
            $userTmpAdapt['cl_invoice_items__description2'] = $this->translator->translate("Poznámka_2");
        }
        $arrStore = $this->StorageManager->getStoreTreeNotNested();
        if ($this->settings->platce_dph == 1) {
            $arrData = [
                'cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE, 'e100p' => "false"],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20,
                    'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                    'values' => $arrStore, 'e100p' => "false",
                    'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($defData1["cl_pricelist_id"]);',
                    'size' => 8, 'roCondition' => '$defData["changed"] != NULL || ($this["editLine"]["cl_pricelist_id"]->value == 0 && $this["editLine"]["quantity"]->value != 0)'],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 7, 'e100p' => "false"],
                'price_s' => [$this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE, 'e100p' => "false"],
                'price_e' => [$tmpProdej, 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
                'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 8, 'readonly' => TRUE, 'e100p' => "false"],
                'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 8, 'e100p' => "false"],
                'vat' => [$this->translator->translate('DPH_%'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 5, 'e100p' => "false"],
                'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 8, 'e100p' => "false"],
                'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_invoice.cl_currencies_id', 'cl_pricelist.price', 'cl_invoice.cl_partners_book_id']],
                'description1' => [$userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"],
                'description2' => [$userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"]];
        } else {
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE, 'e100p' => "false"],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20,
                    'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                    'values' => $arrStore, 'e100p' => "false",
                    'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($defData1["cl_pricelist_id"]);',
                    'size' => 8, 'roCondition' => '$defData["changed"] != NULL || ($this["editLine"]["cl_pricelist_id"]->value == 0 && $this["editLine"]["quantity"]->value != 0)'],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 7, 'e100p' => "false"],
                'price_s' => [$this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE, 'e100p' => "false"],
                'price_e' => [$this->translator->translate('Prodej'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_e_type' => [$this->translator->translate('Typ prodejni ceny'), 'format' => "hidden"],
                'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 8, 'readonly' => TRUE, 'e100p' => "false"],
                'price_e2' => [$this->translator->translate('Celkem'), 'format' => "number", 'size' => 8, 'e100p' => "false"],
                'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_invoice.cl_currencies_id', 'cl_pricelist.price', 'cl_invoice.cl_partners_book_id']],
                'description1' => [$userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"],
                'description2' => [$userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"]];
        }

        if ($this->settings->invoice_to_store == 0) {
            unset($arrData['cl_storage.name']);
            unset($arrData['price_s']);
            unset($arrData['profit']);
        }

        //$translator = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->InvoiceItemsBackManager,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba, 'cl_storage_id' => $this->settings->cl_storage_id_back),
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
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');
        //$this->translator->setPrefix(['applicationModule.invoice']);
        $tlbr = [
            1 => ['group' =>
                [0 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.identification', 'cmpName' => 'invoiceBacklistgrid']),
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('Kód_zboží'),
                    'title' => $this->translator->translate('Seřadí_podle_kódu_zboží'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => ''],
                    1 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.item_label', 'cmpName' => 'invoiceBacklistgrid']),
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Název'),
                        'title' => $this->translator->translate('Seřadí_podle_názvu_položky'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                    2 => ['url' => $this->link('SortItems!', ['sortBy' => 'id', 'cmpName' => 'invoiceBacklistgrid']),
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
            ]
        ];
        $control->setToolbar($tlbr);
        if ($tmpParentData['dn_is_origin'] == 1) {
            $control->setReadOnly();
            $control->setEnableAddEmptyRow(FALSE);
        }

        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }

    public function UpdateSum()
    {
        $this->InvoiceManager->updateInvoiceSum($this->id);
        parent::UpdateSum();
        $this['invoicelistgrid']->redrawControl('editLines');
        if ($this->settings->invoice_to_store == 1) {
            $this['invoiceBacklistgrid']->redrawControl('editLines');
        }
        //$this['sumOnDocs']->redrawControl();
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
        if ($tmpParentData->price_e_type == 1) {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        } else {
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }

        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_invoice_items__description1'])) {
            $userTmpAdapt['cl_invoice_items__description1'] = $this->translator->translate("Poznámka_1");

        }
        if (!isset($userTmpAdapt['cl_invoice_items__description2'])) {
            $userTmpAdapt['cl_invoice_items__description2'] = $this->translator->translate("Poznámka_2");
        }

        $arrStore = $this->StorageManager->getStoreTreeNotNested();
        //bdump($this['invoicelistgrid'],"test");
        //$arrStore = $this->StorageManager->getStoreTreeNotNestedAmount($this['invoicelistgrid']["editLine"]["cl_pricelist_id"]->value);
        if ($this->settings->platce_dph == 1) {
            //'values' => $arrStore,
            //'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($this["editLine"]["cl_pricelist_id"]->value);',
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE, 'e100p' => "false"],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'textarea', 'size' => 20, 'rows' => 1, 'colspan' => 8,
                    'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req', 'show_type' => 1,
                    'values' => $arrStore, 'e100p' => "false", 'newTrColSpan' => 3,
                    'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($defData1["cl_pricelist_id"]);',
                    'size' => 8, 'roCondition' => '$defData["changed"] != NULL || ($this["editLine"]["cl_pricelist_id"]->value == 0)'],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_mj],
                'units' => [$this->translator->translate('Jednotky'), 'format' => 'text', 'show_type' => 1, 'size' => 7, 'e100p' => "false"],
                'price_s' => [$this->translator->translate('Skladová_cena'), 'format' => "number", 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE, 'e100p' => "false"],
                'price_e' => [$tmpProdej, 'format' => "number", 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
                'discount' => [$this->translator->translate('Sleva_%'), 'show_type' => 1, 'format' => "number", 'size' => 8],
                'profit' => [$this->translator->translate('Zisk_%'), 'show_type' => 1, 'format' => "number", 'size' => 8, 'readonly' => TRUE, 'e100p' => "false"],
                'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 8, 'e100p' => "false"],
                'vat' => [$this->translator->translate('DPH_%'), 'format' => "chzn-select", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 6, 'e100p' => "false"],
                'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 8, 'e100p' => "false"],
                'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_invoice.cl_currencies_id', 'cl_pricelist.price', 'cl_invoice.cl_partners_book_id']],
                'description1' => [$userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"],
                'description2' => [$userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"]];
        } else {
            $arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE, 'e100p' => "false"),
                'item_label' => array($this->translator->translate('Popis'), 'format' => 'textarea', 'size' => 20, 'rows' => 1, 'colspan' => 6),
                'cl_storage.name' => array($this->translator->translate('Sklad'), 'format' => 'chzn-select-req', 'show_type' => 0,
                    'values' => $arrStore, 'newTrColSpan' => 3,
                    'size' => 8, 'roCondition' => '$defData["changed"] != NULL || ($this["editLine"]["cl_pricelist_id"]->value == 0)'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj),
                'units' => array($this->translator->translate('Jednotky'), 'format' => 'text', 'show_type' => 1, 'size' => 7, 'e100p' => "false"),
                'price_s' => array($this->translator->translate('Skladová_cena'), 'format' => "number", 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE, 'e100p' => "false"),
                'price_e' => array($this->translator->translate('Prodej'), 'format' => "number", 'show_type' => 1, 'size' => 8, 'decplaces' => $this->settings->des_cena),
                'price_e_type' => array($this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"),
                'discount' => array($this->translator->translate('Sleva_%'), 'format' => "number", 'show_type' => 1, 'size' => 8),
                'profit' => array($this->translator->translate('Zisk_%'), 'format' => "number", 'show_type' => 1, 'size' => 8, 'readonly' => TRUE, 'e100p' => "false"),
                'price_e2' => array($this->translator->translate('Celkem'), 'format' => "number", 'size' => 8, 'e100p' => "false"),
                'cl_pricelist_id' => array($this->translator->translate('Položka_ceníku'), 'format' => 'hidden'),
                'quantity_prices__' => array($this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => array('cl_pricelist_id', 'cl_invoice.cl_currencies_id', 'cl_pricelist.price', 'cl_invoice.cl_partners_book_id')),
                'description1' => array($userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"),
                'description2' => array($userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"));

        }
        if ($this->settings->invoice_to_store == 0) {
            unset($arrData['cl_storage.name']);
            unset($arrData['price_s']);
            unset($arrData['profit']);
            $arrData['item_label']['colspan'] = 3;
            $arrData['quantity']['newTrColSpan'] = 3;
        }

        //$translator = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->InvoiceItemsManager,
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
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');
        //$this->translator->setPrefix(['applicationModule.invoice']);
        $tlbr = [
            1 => ['group' =>
                [0 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.identification', 'cmpName' => 'invoicelistgrid']),
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('Kód_zboží'),
                    'title' => $this->translator->translate('Seřadí_podle_kódu_zboží'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => ''],
                    1 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.item_label', 'cmpName' => 'invoicelistgrid']),
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Název'),
                        'title' => $this->translator->translate('Seřadí_podle_názvu_položky'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                    2 => ['url' => $this->link('SortItems!', ['sortBy' => 'id', 'cmpName' => 'invoicelistgrid']),
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
            2 => ['url' => $this->link('InsertDiscount!'), 'rightsFor' => 'edit', 'data' => ['data-history=false'],
                'label' => 'Sleva', 'title' => 'Vloží slevu z celkové částky faktury', 'class' => 'btn btn-warning', 'icon' => 'iconfa-eraser'],
        ];
        $control->setToolbar($tlbr);

        if ($tmpParentData['dn_is_origin'] == 1) {
            $control->setReadOnly();
            $control->setEnableAddEmptyRow(FALSE);
        }

        $control->setShowType([1 => ['label' => 'text a cena', 'title' => 'vloží nový řádek bez kusů, jen s polem pro text a cenu']]);

        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }

    public function handleMakeDeliveryNote()
    {
        $retArr = $this->DeliveryNoteManager->createDeliveryFromInvoice($this->id);
        if (self::hasError($retArr)) {
            $this->flashmessage($this->translator->translate($retArr['error']), 'warning');
        } else {
            $this->flashmessage($this->translator->translate($retArr['success']), 'success');
            //redirect to deliverynote
            $this->redirect(':Application:DeliveryNote:edit', ['id' => $retArr['deliveryN_id']]);
        }
        $this->redrawControl('content');

    }

    public function forceRO($data)
    {
        $ret = parent::forceRO($data);
        if (!is_null($data) && !is_null($data['cl_eet_id']) && $data->cl_eet->eet_status == 3) {
            $ret = $ret || TRUE;
        } else {
            $ret = $ret || FALSE;
        }

        return $ret;
    }

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);
        //$this->translator->setPrefix(array('applicationModule.invoice' => 'applicationModule.invoice'));
        //profiler::start();
        //$this->translator->setPrefix(['applicationModule.invoice']);
        //$this->translator->domain('applicationModule.invoice');
        if ($this->user->getIdentity()->quick_sums) {
            //    $fromCache = $this->cache->load('headerInvoice');
            //    if ($fromCache === null)
            //        $fromCache = $this->cache->save('headerInvoice', $this->refreshHeader(),[
            //            \Nette\Caching\Cache::EXPIRE => '20 minutes', // akceptuje i sekundy nebo timestamp
            //                                    ]);

            //    $this->template->headerText = $fromCache;
            $this->template->headerText = $this->refreshHeader();
        }
        //profiler::finish('quick sums presenter');

        // $this->cache->save('headerInvoice', $this->template->headerText, [
        //     Cache::EXPIRE => '20 minutes', // akceptuje i sekundy nebo timestamp
        // ]);


    }

    public function refreshHeader()
    {

        $tmpInvoice = $this->dataForSums;
        if ($this->settings->platce_dph == 1) {
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount = $tmpInvoiceC->where('cl_invoice.storno = 0 AND cl_invoice.due_date <= NOW() AND ABS(price_payed)<ABS(cl_invoice.price_e2_vat-cl_invoice.advance_payed)')->sum('(cl_invoice.price_e2_vat-cl_invoice.price_payed-cl_invoice.advance_payed)*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount2 = $tmpInvoiceC->where('cl_invoice.storno = 0 AND ABS(cl_invoice.price_payed) < ABS(cl_invoice.price_e2_vat-cl_invoice.advance_payed)')->sum('(cl_invoice.price_e2_vat-cl_invoice.price_payed-cl_invoice.advance_payed)*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount3 = $tmpInvoiceC->
                            where('cl_invoice.storno = 0 AND MONTH(cl_invoice.vat_date) = MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(cl_invoice.vat_date) = YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))')->
                            sum('cl_invoice.price_e2_vat*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount4 = $tmpInvoiceC->
                            where('cl_invoice.storno = 0 AND MONTH(cl_invoice.vat_date) = MONTH(NOW()) AND YEAR(cl_invoice.vat_date) = YEAR(NOW())')->
                            sum('cl_invoice.price_e2_vat*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount5 = $tmpInvoiceC->
                            where('cl_invoice.storno = 0 AND cl_invoice.due_date <= DATE_SUB(NOW(),INTERVAL 7 DAY) AND ABS(cl_invoice.price_payed) < ABS(cl_invoice.price_e2_vat-cl_invoice.advance_payed)')->
                            sum('(cl_invoice.price_e2_vat-cl_invoice.price_payed-cl_invoice.advance_payed)*cl_invoice.currency_rate');
        } else {
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount = $tmpInvoiceC->where('cl_invoice.storno = 0 AND cl_invoice.due_date <= NOW() AND ABS(cl_invoice.price_payed) < ABS(cl_invoice.price_e2-cl_invoice.advance_payed)')->sum('(cl_invoice.price_e2-cl_invoice.price_payed-cl_invoice.advance_payed)*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount2 = $tmpInvoiceC->where('cl_invoice.storno = 0 AND ABS(cl_invoice.price_payed) < ABS(cl_invoice.price_e2-cl_invoice.advance_payed)')->sum('(cl_invoice.price_e2-cl_invoice.price_payed-cl_invoice.advance_payed)*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount3 = $tmpInvoiceC->
                            where('cl_invoice.storno = 0 AND MONTH(cl_invoice.inv_date) = MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(cl_invoice.inv_date) = YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))')->
                            sum('cl_invoice.price_e2*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount4 = $tmpInvoiceC->
                            where('cl_invoice.storno = 0 AND MONTH(cl_invoice.inv_date) = MONTH(NOW()) AND YEAR(cl_invoice.inv_date) = YEAR(NOW())')->
                            sum('cl_invoice.price_e2*cl_invoice.currency_rate');
            $tmpInvoiceC = clone $tmpInvoice;
            $tmpAmount5 = $tmpInvoiceC->
                            where('cl_invoice.storno = 0 AND cl_invoice.due_date <= DATE_SUB(NOW(),INTERVAL 7 DAY) AND ABS(cl_invoice.price_payed) < ABS(cl_invoice.price_e2-cl_invoice.advance_payed)')->
                            sum('(cl_invoice.price_e2-cl_invoice.price_payed-cl_invoice.advance_payed)*cl_invoice.currency_rate');
        }

        $headerText = [0 => [$this->translator->translate('Dnes_po_splatnosti'), $tmpAmount, $this->settings->cl_currencies->currency_name, 0, 'label-warning', 'rightsFor' => 'report'],
            1 => [$this->translator->translate('Týden_a_více_nezaplaceno'), $tmpAmount5, $this->settings->cl_currencies->currency_name, 0, 'label-danger', 'rightsFor' => 'report'],
            2 => [$this->translator->translate('Celkem_nezaplaceno'), $tmpAmount2, $this->settings->cl_currencies->currency_name, 0, 'label-warning', 'rightsFor' => 'report'],
            3 => [$this->translator->translate('Obrat_v_tomto_měsíci'), $tmpAmount4, $this->settings->cl_currencies->currency_name, 0, 'label-success', 'rightsFor' => 'report'],
            4 => [$this->translator->translate('Obrat_v_minulém_měsíci'), $tmpAmount3, $this->settings->cl_currencies->currency_name, 0, 'label-success', 'rightsFor' => 'report']];
        return ($headerText);

    }

    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);
    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        $data = $this->updatePartnerId($data);
        //$this->redrawControl('content');
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0) {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }
        //13.06.2021 - invoice number uniqueness
        if (is_null($this->id))
            $this->id = $this->bscId;

        $tmpData = $this->DataManager->findAll()->where('inv_number = ? AND id != ? AND YEAR(vat_date) = ?', $data['inv_number'], $this->id, date('Y', strtotime($data['vat_date'])))->fetch();
        if ($tmpData) {
            $form->addError($this->translator->translate('Zadané_číslo_faktury_je_již_použité'));
        }

        $this->redrawControl('content');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        //bdump($data,'data after update');
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {
            $data = $this->RemoveFormat($data);//
            $result = $this->checkNumberSeries($data, 'inv_number');
            //25.02.2023 - TH cl_number_series_id should stay the same because number changes could be in the same number_serie
            //if ($result)
            //    $data['cl_number_series_id'] = NULL;

            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {
                    //bdump($data,'invoiceForm');
                    $tmpData = $this->DataManager->find($data['id']);
                    if ($tmpData['vat_active'] == 0) //TH07.03.2023 - because there is send empty value for vat_date and this has affect on updateInvoiceSum method
                        unset($data['vat_date']);
                    $this->DataManager->update($data, TRUE);
                    $this->InvoiceManager->updateInvoiceSum($this->id);
                    if ($tmpData) {
                        //unvalidate document for downloading
                        $tmpDocuments = new \Nette\Utils\ArrayHash();
                        $tmpDocuments['id'] = $tmpData->cl_documents_id;
                        $tmpDocuments['valid'] = 0;
                        $newDocuments = $this->DocumentsManager->update($tmpDocuments);
                    }
                    $this->UpdatePairedDocs($data);
                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                } else {
                    //$row=$this->DataManager->insert($data);
                    //$this->newId = $row->id;
                    //$this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            } else {
                //$this->flashMessage('Změny nebyly uloženy.', 'success');
            }
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redrawControl('content');
        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy.'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redirect('default');
        }
    }

    public function stepHeaderBack()
    {
        $this->headerModalShow = FALSE;
        $this->activeTab = 2;
        $this->redrawControl('headerModalControl');
    }

    public function SubmitEditHeaderSubmitted(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {
            $this->DataManager->update($data);
        }
        $this->headerModalShow = FALSE;
        $this->activeTab = 2;
        $this->redrawControl('items');
        $this->redrawControl('headerModalControl');
    }

    public function stepFooterBack()
    {
        $this->headerModalShow = FALSE;
        $this->activeTab = 3;
        $this->redrawControl('footerModalControl');
    }

    public function SubmitEditFooterSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy()) {
            $this->DataManager->update($data);
        }
        $this->footerModalShow = FALSE;
        $this->activeTab = 3;
        $this->redrawControl('items');
        $this->redrawControl('footerModalControl');
    }

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

    public function handleMakeRecalc($idCurrency, $rate, $oldrate, $recalc)
    {
        //in future there can be another work with rates
        //dump($this->editId);
        if ($rate > 0) {
            if ($recalc == 1) {
                $recalcItems = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $this->id));
                //$recalcItems = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => array($this->id,827,829,799,769,770,757,740,741,742,721,718)));
                foreach ($recalcItems as $one) {
                    //$data = new \Nette\Utils\ArrayHash;
                    //$data['price_s'] = $one['price_s'] * $oldrate / $rate;

                    //if ($this->settings->platce_dph == 1)
                    $data['price_e'] = $one['price_e'] * $oldrate / $rate;
                    $data['price_e2'] = $one['price_e2'] * $oldrate / $rate;
                    $data['price_e2_vat'] = $one['price_e2_vat'] * $oldrate / $rate;

                    $one->update($data);
                }
            }

            //we must save parent data
            $parentData = new \Nette\Utils\ArrayHash;
            $parentData['id'] = $this->id;
            if ($rate <> $oldrate)
                $parentData['currency_rate'] = $rate;

            $parentData['cl_currencies_id'] = $idCurrency;
            $this->DataManager->update($parentData);


            $this->UpdateSum($this->id, $this);

        }
        $this->redrawControl('items');

    }

    public function beforeAddLine($data)
    {
        //parent::beforeAddLine($data);
        //dump($data['control_name']);
        if ($data['control_name'] == "invoicelistgrid") {
            $data['price_e_type'] = $this->settings->price_e_type;
        }

        return $data;
    }

    public function ListGridInsert($sourceData, $dataManager)
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
        //12.06.2020 - correction for cases when we are giving sourceData from cl_pricelist_partner
        //$arrPrice['price_s'] = $sourceData['price_s'];
        $arrPrice['price_s'] = $sourcePriceData['price_s'];
        $arrPrice['cl_currencies_id'] = $sourcePriceData['cl_currencies_id'];
        //bdump($arrPrice);
        //die;
        ///04.09.2017 - find price if there are defince prices_groups
        $tmpData = $this->DataManager->find($this->id);
        if (isset($tmpData['cl_partners_book_id'])
            && $tmpPrice = $this->PricesManager->getPrice($tmpData->cl_partners_book,
                $arrPrice['id'],
                $tmpData->cl_currencies_id,
                $this->settings['cl_storage_id'])) {
            $arrPrice['price'] = $tmpPrice['price'];
            $arrPrice['price_vat'] = $tmpPrice['price_vat'];
            $arrPrice['discount'] = $tmpPrice['discount'];
            $arrPrice['price_e2'] = $tmpPrice['price_e2'];
            $arrPrice['price_e2_vat'] = $tmpPrice['price_e2_vat'];
            $arrPrice['cl_currencies_id'] = $tmpPrice['cl_currencies_id'];
            //bdump($arrPrice);
        } else {
            $arrPrice['price'] = $sourceData->price;
            $arrPrice['price_vat'] = $sourceData->price_vat;
            $arrPrice['discount'] = 0;
            $arrPrice['price_e2'] = $sourceData->price;
            $arrPrice['price_e2_vat'] = $sourceData->price_vat;
            //$arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
        }

        //26.01.2022
        //$arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
        $arrPrice['vat'] = $sourceData->vat;

        $arrData = new \Nette\Utils\ArrayHash;
        $arrData[$this->DataManager->tableName . '_id'] = $this->id;

        $arrData['cl_pricelist_id'] = $sourcePriceData->id;
        $arrData['item_order'] = $dataManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;

        $arrData['item_label'] = $sourcePriceData->item_label;
        $arrData['quantity'] = 1;

        $arrData['units'] = $sourcePriceData->unit;

        $arrData['vat'] = $arrPrice['vat'];

        $arrData['price_s'] = $arrPrice['price_s'];

        $arrData['price_e_type'] = $this->settings->price_e_type;
        if ($arrData['price_e_type'] == 1) {
            $arrData['price_e'] = $arrPrice['price_vat'];
        } else {
            $arrData['price_e'] = $arrPrice['price'];
        }
        $arrData['discount'] = $arrPrice['discount'];
        $arrData['price_e2'] = $arrPrice['price_e2'];
        $arrData['price_e2_vat'] = $arrPrice['price_e2_vat'];

        //default storage from pricelist
        $arrData['cl_storage_id'] = $sourcePriceData['cl_storage_id'];

        //20.10.2019 - default storage
        if ($dataManager->tableName == 'cl_invoice_items_back') {
            $tmpDefData = $this['invoiceBacklistgrid']->getDefaultData();
        } elseif ($dataManager->tableName == 'cl_invoice_items') {
            $tmpDefData = $this['invoicelistgrid']->getDefaultData();
        }
        if (isset($tmpDefData['cl_storage_id']) && !is_null($tmpDefData['cl_storage_id']) && is_null($arrData['cl_storage_id'])) {
            $arrData['cl_storage_id'] = $tmpDefData['cl_storage_id'];
        }


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
        //bdump($arrPrice['cl_currencies_id']);
        //bdump($tmpOrder['cl_currencies_id']);
        if ( $arrPrice['cl_currencies_id'] != $tmpOrder['cl_currencies_id'] ) {
            $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOrder;
            $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOrder;
            $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOrder;
        }


        $row = $dataManager->insert($arrData);
        $this->updateSum($this->id, $this);
        return ($row);

    }

    public function handleRedrawPriceList2($cl_partners_book_id)
    {
        //dump($cl_partners_book_id);
        $arrUpdate = new \Nette\Utils\ArrayHash;
        $arrUpdate['id'] = $this->id;
        $arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL : $cl_partners_book_id);

        //dump($arrUpdate);
        //die;
        $this->DataManager->update($arrUpdate);

        $this['invoicelistgrid']->redrawControl('pricelist2');
    }

    public function handleRedrawDueDate2($invdate)
    {
        $invdate = date_create($invdate);
        $tmpData = $this->DataManager->find($this->id);
        $arrUpdate = new \Nette\Utils\ArrayHash;
        $arrUpdate['id'] = $this->id;
        if (isset($tmpData['cl_partners_book_id']) && $tmpData->cl_partners_book->due_date > 0) {
            $strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
        } else {
            $strModify = '+' . $this->settings->due_date . ' day';
        }
        if (isset($tmpData['cl_partners_book_id']) && isset($tmpData->cl_partners_book->cl_payment_types_id)) {
            $clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
            $spec_symb = $tmpData->cl_partners_book->spec_symb;
            if ($tmpData->cl_partners_book->cl_payment_types->payment_type == 0) {
                $arrUpdate['due_date'] = $invdate->modify($strModify);
            } else {
                $arrUpdate['due_date'] = $invdate;
            }
        } else {
            $clPayment = $this->settings->cl_payment_types_id;
            $spec_symb = "";
            if ($this->settings->cl_payment_types->payment_type == 0) {
                $arrUpdate['due_date'] = $invdate->modify($strModify);
            } else {
                $arrUpdate['due_date'] = $invdate;
            }
        }
        $this->DataManager->update($arrUpdate);
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
        $arrUpdate = new \Nette\Utils\ArrayHash;
        $arrUpdate['id'] = $this->id;
        if ($value == 'true') {
            $arrUpdate['pdp'] = 1;
            $arrUpdate['footer_txt'] = $tmpData->footer_txt;
            $pdpText = nl2br($this->settings->pdp_text);
            $arrUpdate['footer_txt'] = ($arrUpdate['footer_txt'] != '') ? $arrUpdate['footer_txt'] .= '<br><br>' . $pdpText : $pdpText;
        } else {
            $arrUpdate['pdp'] = 0;
            $arrUpdate['footer_txt'] = "";
        }

        //Debugger::fireLog($arrUpdate);
        $this->DataManager->update($arrUpdate);
        $this->updateSum($this->id, $this);
        $this['invoicelistgrid']->redrawControl('editLines');
        //$this->redrawControl('formedit');
        $this->redrawControl('items');
    }

    public function handleExport($value)
    {
        //Debugger::fireLog($value);
        $tmpData = $this->DataManager->find($this->id);
        $arrUpdate = new \Nette\Utils\ArrayHash;
        $arrUpdate['id'] = $this->id;
        if ($value == 'true') {
            $arrUpdate['export'] = 1;
        } else {
            $arrUpdate['export'] = 0;
        }

        //Debugger::fireLog($arrUpdate);
        $this->DataManager->update($arrUpdate);
        $this->updateSum($this->id, $this);
        $this['invoicelistgrid']->redrawControl('editLines');
        //$this->redrawControl('formedit');
        $this->redrawControl('items');

    }

    public function afterDelete($line)
    {
        /*08.01.2022 - update sum of used amount for selected tax cl_invoice_advance*/
        //bdump('TEEED');
        $this->InvoiceManager->updateAdvancePriceE2Used($line['used_cl_invoice_id']);
    }

    public function beforeDelete($lineId, $name = NULL)
    {
        $result = FALSE;
        if ($name == "paymentListGrid") {
            //delete connected cl_delivery_note_payments
            //and update payment on cl_delivery_note
            $tmpDNPayment = $this->DeliveryNotePaymentsManager->findAll()->where('cl_invoice_payments_id = ?', $lineId)->fetch();
            if ($tmpDNPayment) {
                $deliveryNoteId = $tmpDNPayment['cl_delivery_note_id'];
                $tmpDNPayment->delete();
                $this->DeliveryNoteManager->paymentUpdate($deliveryNoteId);
            }

            $tmpPayment = $this->InvoicePaymentsManager->find($lineId);
            if ($tmpPayment && !is_null($tmpPayment['cl_cash_id'])){
                $tmpPayment->cl_cash->delete();
            }
            $result = TRUE;
        }


        if ($name == "invoicelistgrid") {
            if ($tmpLine = $this->InvoiceItemsManager->find($lineId)) {

                //07.05.2017 - if line is from helpdesk, we must delete connection
                if (!is_null($tmpLine->cl_partners_event_id)) {
                    $this->PartnersEventManager->find($tmpLine->cl_partners_event_id)->update(array('cl_invoice_id' => NULL));
                    $tmpLine->update(array('cl_partners_event_id' => NULL));
                    $result = TRUE;
                }
                if ($this->settings->invoice_to_store == 1) {
                    $this->StoreManager->deleteItemStoreMove($tmpLine);
                    $result = TRUE;
                } else {
                    $result = TRUE;
                }
            } else {
                $result = TRUE;
            }
        } elseif ($name == "invoiceBacklistgrid" && $this->settings->invoice_to_store == 1) {
            if ($tmpLine = $this->InvoiceItemsBackManager->find($lineId)) {
                if ($result = $this->StoreOutManager->findBy(array('cl_store_move_in_id' => $tmpLine->cl_store_move_id))->sum('s_out')) {
                    $this->flashMessage($this->translator->translate('Z_příjemky_již_bylo_vydáváno_záznam_není_možné_vymazat'), 'danger');
                    $this->redrawControl('flash');
                    $result = FALSE;
                } else {
                    $this->StoreManager->deleteItemStoreMove($tmpLine);
                    $result = TRUE;
                }
            } else {
                $result = TRUE;
            }
        } else {
            $result = TRUE;
        }

        return $result;
    }

    public function beforeDeleteBaseList($id)
    {
        foreach ($this->DataManager->find($id)->related('cl_invoice_items') as $one) {
            //07.05.2017 - if line is from helpdesk, we must delete connection
            if (!is_null($one->cl_partners_event_id)) {
                $this->PartnersEventManager->find($one->cl_partners_event_id)->update(array('cl_invoice_id' => NULL));
                $one->update(array('cl_partners_event_id' => NULL));
            }

        }

        $data = $this->DataManager->find($id);
        if (!is_null($data['cl_eet_id']) && $data->cl_eet->eet_status == 3) {
            $ret = FALSE;
        } else {
            $ret = TRUE;
        }

        return $ret;
    }

    public function emailSetStatus()
    {
        $this->setStatus($this->id, ['status_use' => 'invoice',
            's_new' => 0,
            's_eml' => 1]);
    }

    public function handleGetGroupNumberSeries($cl_invoice_types_id)
    {
        //19.10.2019 - not used anymore
        return;

        //Debugger::fireLog($this->id);
        $arrData = new \Nette\Utils\ArrayHash;
        $arrData['id'] = NULL;
        $arrData['number'] = '';
        if ($data = $this->InvoiceTypesManager->find($cl_invoice_types_id)) {
            //dump($data->cl_number_series_id);
            if ($tmpData = $this->DataManager->find($this->id)) {
                if ($data->cl_number_series_id == $tmpData->cl_number_series_id) {
                    $tmpId = $this->id;
                } else {
                    $tmpId = NULL;
                    //ponizit o 1 minule pouzitou ciselnou radu.
                    $this->NumberSeriesManager->update(array('id' => $tmpData->cl_number_series_id, 'last_number' => $tmpData->cl_invoice_types->cl_number_series->last_number - 1));
                }

                if ($data2 = $this->NumberSeriesManager->getNewNumber('invoice', $data->cl_number_series_id, $tmpId)) {
                    $arrData = $data2;
                }
                $arrUpdate = array();
                $arrUpdate['id'] = $this->id;
                $arrUpdate['inv_number'] = $arrData['number'];
                $arrUpdate['cl_invoice_types_id'] = $cl_invoice_types_id;
                $arrUpdate['cl_number_series_id'] = $data->cl_number_series_id;


                //20.12.2018 - headers and footers
                if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $data->cl_number_series_id))->fetch()) {
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
        //    $this->redrawControl('contents');
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

    public function DataProcessMain($defValues, $data)
    {
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
        $arrData = new \Nette\Utils\ArrayHash;
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
        $arrData = new \Nette\Utils\ArrayHash;
        $arrData['id'] = $this->id;
        //Debugger::fireLog($value);
        if ($value == 'true')
            $arrData['footer_show'] = 1;
        else
            $arrData['footer_show'] = 0;

        $this->DataManager->update($arrData);

        $this->terminate();
    }

    //javascript call when changing cl_partners_book_id

    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    //javascript call when changing cl_partners_book_id or change inv_date

    public function DataProcessListGrid($data)
    {
        parent::DataProcessListGrid($data);
        return $data;
    }

    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId, $name);
        if ($name == "paymentListGrid") {
            //24.05.2021 - pay deliverynote if is only one deliverynote and not payed yet
            $tmpData = $this->InvoicePaymentsManager->find($dataId);
            $tmpParent = $this->DataManager->findAll()->where('id = ?', $this->id)->fetch();
            $tmpDn = $this->DeliveryNoteManager->findAll()->where('cl_invoice_id = ?', $this->id)->fetchAll();
            if (count($tmpDn) == 1) {
                foreach ($tmpDn as $key => $tmpDnotes) {
                    $arrPayment['cl_delivery_note_id'] = $tmpDnotes['id'];
                    break;
                }
                $arrPayment['cl_currencies_id'] = $tmpData['cl_currencies_id'];
                $arrPayment['cl_payment_types_id'] = $tmpData['cl_payment_types_id'];
                $arrPayment['pay_date'] = $tmpData['pay_date'];
                $arrPayment['pay_price'] = $tmpData['pay_price'];
                $arrPayment['cl_cash_id'] = $tmpData['cl_cash_id'];
                $arrPayment['pay_type'] = $tmpData['pay_type'];
                $arrPayment['pay_vat'] = $tmpData['pay_vat'];
                $arrPayment['cl_invoice_payments_id'] = $dataId;

                // bdump($arrPayment);
                $tmpDNPayment = $this->DeliveryNotePaymentsManager->findAll()->where('cl_invoice_payments_id = ?', $dataId)->fetch();
                if (!$tmpDNPayment) {
                    //insert new
                    $this->DeliveryNotePaymentsManager->insert($arrPayment);
                } else {
                    //update
                    $arrPayment['id'] = $tmpDNPayment['id'];
                    $this->DeliveryNotePaymentsManager->update($arrPayment);
                }
                // bdump($tmpDnotes['id']);
                $this->DeliveryNoteManager->paymentUpdate($tmpDnotes['id']);
            }

            /*08.01.2022 - set default values for tax advance*/
            if (!is_null($tmpData['used_cl_invoice_id'])){
                $arrPaymentUpdate = [];
                $arrPaymentUpdate['pay_type'] = 1;
                $arrPaymentUpdate['pay_vat'] = 1;
                $tmpData->update($arrPaymentUpdate);
            }
            /*08.01.2022 - update sum of used amount for selected tax cl_invoice_advance*/
            $this->InvoiceManager->updateAdvancePriceE2Used($tmpData['used_cl_invoice_id']);
        }
        if ($name == "invoicelistgrid" && $this->settings->invoice_to_store == 1) {
            //saled items - give out
            //1. check if cl_store_docs exists if not, create new one
            $tmpData = $this->InvoiceItemsManager->find($dataId);
            bdump($tmpData['cl_store_move_id']);
            if (is_null($tmpData['cl_store_move_id'])) {
                $docId = $this->StoreDocsManager->createStoreDoc(1, $this->id, $this->DataManager);
            } else {
                $docId = $tmpData->cl_store_move['cl_store_docs_id'];
            }

            //2. giveout current item
            $dataIdtmp = $this->StoreManager->giveOutItem($docId, $dataId, $this->InvoiceItemsManager);

            //if ($dataId)
            //{
            //		$this->InvoiceItemsManager->find($dataId)->update(array('cl_store_move_id'=> $dataId));
            //}


        } elseif ($name == "invoiceBacklistgrid" && $this->settings->invoice_to_store == 1) {
            //back items - store in
            $tmpData = $this->InvoiceItemsBackManager->find($dataId);


            if (is_null($tmpData['cl_store_move_id'])) {
                $docId = $this->StoreDocsManager->createStoreDoc(0, $this->id, $this->DataManager);
            } else {
                $docId = $tmpData->cl_store_move['cl_store_docs_id'];
            }
            //2. store in current item
            $dataIdtmp = $this->StoreManager->giveInItem($docId, $dataId, $this->InvoiceItemsBackManager);

            $this->StoreManager->UpdateSum($docId);
        }

        if ($name == "invoicelistgrid" || ($name == "invoiceBacklistgrid" && $this->settings->invoice_to_store == 1)) {
            //14.03.2019 - insert cl_pricelist_bond into cl_invoice_items
            $tmpInvoiceItem = $this->InvoiceItemsManager->find($dataId);
            if ($name == "invoicelistgrid") {
                $tmpInvoiceItem = $this->InvoiceItemsManager->find($dataId);
            } elseif ($name == "invoiceBacklistgrid" && $this->settings->invoice_to_store == 1) {
                $tmpInvoiceItem = $this->InvoiceItemsBackManager->find($dataId);
            }

            if (!is_null($tmpInvoiceItem->cl_pricelist_id)) {
                //update and profit
                $priceS = $tmpInvoiceItem->price_s * $tmpInvoiceItem->quantity;
                if ($tmpInvoiceItem->price_e2 != 0) {
                    $profit = 100 - (($priceS / $tmpInvoiceItem->price_e2) * 100);
                } else {
                    $profit = 0;
                }
                $tmpInvoiceItem->update(array('profit' => $profit));

                //find if there are bonds in cl_pricelist_bonds
                $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpInvoiceItem->cl_pricelist_id, $tmpInvoiceItem->quantity);

                foreach ($tmpBonds as $key => $oneBond) {
                    //found in cl_invoice_items if there already is bonded item

                    if ($name == "invoicelistgrid") {
                        $tmpInvoiceItemBond = $this->InvoiceItemsManager->findBy(array('cl_parent_bond_id' => $tmpInvoiceItem->id, 'cl_pricelist_id' => $oneBond->cl_pricelist_id))->fetch();
                    } elseif ($name == "invoiceBacklistgrid" && $this->settings->invoice_to_store == 1) {
                        $tmpInvoiceItemBond = $this->InvoiceItemsBackManager->findBy(array('cl_parent_bond_id' => $tmpInvoiceItem->id,
                            'cl_pricelist_id' => $oneBond->cl_pricelist_id))->fetch();
                    }

                    $newItem = $this->PriceListBondsManager->getBondData($oneBond, $tmpInvoiceItem);
                    $newItem['cl_invoice_id'] = $this->id;

                    if (!$tmpInvoiceItemBond) {
                        if ($name == "invoicelistgrid") {
                            $tmpNew = $this->InvoiceItemsManager->insert($newItem);
                            $tmpId = $tmpNew->id;
                            if ($this->settings->invoice_to_store == 1) {
                                //give out from store
                                $dataId = $this->StoreManager->giveOutItem($docId, $tmpId, $this->InvoiceItemsManager);
                            }
                        } elseif ($name == "invoiceBacklistgrid" && $this->settings->invoice_to_store == 1) {
                            $tmpNew = $this->InvoiceItemsBackManager->insert($newItem);
                            $tmpId = $tmpNew->id;
                            //give in to store
                            $dataIdtmp = $this->StoreManager->giveInItem($docId, $tmpNew['id'], $this->InvoiceItemsBackManager);
                        }
                        $tmpId = $tmpNew->id;
                    } else {
                        $newItem['id'] = $tmpInvoiceItemBond->id;
                        if ($name == "invoicelistgrid") {
                            $tmpNew = $this->InvoiceItemsManager->update($newItem);
                            $dataId = $this->StoreManager->giveOutItem($docId, $newItem['id'], $this->InvoiceItemsManager);
                        } elseif ($name == "invoiceBacklistgrid" && $this->settings->invoice_to_store == 1) {
                            $tmpNew = $this->InvoiceItemsBackManager->update($newItem);
                            //give in to store
                            $dataIdtmp = $this->StoreManager->giveInItem($docId, $newItem['id'], $this->InvoiceItemsBackManager);
                        }
                        $tmpId = $tmpInvoiceItemBond->id;
                    }

                    //update and profit
                    $tmpBondItem = $this->InvoiceItemsManager->find($tmpId);
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


    //control method to determinate if we can delete

    public function stepBackReportInvoiceBook()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }


    //aditional control before delete from baseList

    public function SubmitReportInvoiceBookSubmitted(Form $form)
    {
        $data = $form->values;
        //dump(count($data['cl_partners_book']));
        //die;
        // $data = $this->removeFormat($data);
        if ($form['save_pdf']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_xml']->isSubmittedBy() || $form['save_xls']->isSubmittedBy()) {

            $data['cl_partners_branch'] = array();
            $tmpPodm = array();
            if ($data['after_due_date'] == 1) {
                if ($data['min_difference'] == 1) {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_invoice.due_date < NOW() AND FLOOR(cl_invoice.price_payed) < FLOOR(cl_invoice.price_e2_vat)';
                    else
                        $tmpPodm = 'cl_invoice.due_date < NOW() AND FLOOR(cl_invoice.price_payed) < FLOOR(cl_invoice.price_e2)';
                } else {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_invoice.due_date < NOW() AND cl_invoice.price_payed < cl_invoice.price_e2_vat';
                    else
                        $tmpPodm = 'cl_invoice.due_date < NOW() AND cl_invoice.price_payed < cl_invoice.price_e2';
                }
            }

            if ($data['not_payed'] == 1) {
                if ($data['min_difference'] == 1) {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'FLOOR(cl_invoice.price_payed) < FLOOR(cl_invoice.price_e2_vat)';
                    else
                        $tmpPodm = 'FLOOR(cl_invoice.price_payed) < FLOOR(cl_invoice.price_e2)';
                } else {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_invoice.price_payed < cl_invoice.price_e2_vat';
                    else
                        $tmpPodm = 'cl_invoice.price_payed < cl_invoice.price_e2';
                }
            }

            if ($data['payed'] == 1) {
                if ($data['min_difference'] == 1) {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'FLOOR(cl_invoice.price_payed) >= FLOOR(cl_invoice.price_e2_vat)';
                    else
                        $tmpPodm = 'FLOOR(cl_invoice.price_payed) >= FLOOR(cl_invoice.price_e2)';
                } else {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_invoice.price_payed >= cl_invoice.price_e2_vat';
                    else
                        $tmpPodm = 'cl_invoice.price_payed >= cl_invoice.price_e2';
                }
            }


            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                //$tmpDate = new \Nette\Utils\DateTime;
                //$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
                //date('Y-m-d H:i:s',strtotime($data['date_to']));
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));


            if ($data['type'] == 0){
                $data['type'] == 1;
            }

            if ($data['type'] == 1){
                $dateType = 'cl_invoice.inv_date';
            }elseif ($data['type'] == 2){
                $dateType = 'cl_invoice.vat_date';
            }

            if (count($data['cl_partners_book']) == 0) {
                if ($this->settings->platce_dph) {
                    $dataReport = $this->InvoiceManager->findAll()->
                                        where($tmpPodm)->
                                        where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to'])->
                                        order('cl_invoice.vat_date ASC, cl_invoice.inv_number ASC');
                } else {
                    $dataReport = $this->InvoiceManager->findAll()->
                                        where($tmpPodm)->
                                        where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to'])->
                                        order('cl_invoice.vat_date ASC, cl_invoice.inv_number ASC');
                }
            } else {
                $tmpPartners = array();
                $tmpBranches = array();
                foreach ($data['cl_partners_book'] as $one) {
                    $arrOne = str_getcsv($one, "-");
                    $tmpPartners[] = $arrOne[0];
                    if ($arrOne[1] != '') {
                        $tmpBranches[] = $arrOne[1];
                    }
                }

                $data['cl_partners_book'] = $tmpPartners;

                $data['cl_partners_branch'] = $tmpBranches;


                if ($this->settings->platce_dph) {
                    $dataReport = $this->InvoiceManager->findAll()->
                                            where($tmpPodm)->
                                            where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to']);
                } else {
                    $dataReport = $this->InvoiceManager->findAll()->
                                            where($tmpPodm)->
                                            where($dateType . ' >= ? AND ' . $dateType . ' <= ?', $data['date_from'], $data['date_to']);
                }

                if (count($tmpBranches) > 0) {
                    $dataReport = $dataReport->where('cl_partners_book_id IN (?) OR cl_partners_branch_id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch']);
                } else {
                    $dataReport = $dataReport->where('cl_partners_book_id IN (?)', $data['cl_partners_book']);
                }
                $dataReport = $dataReport->order('cl_invoice.vat_date ASC, cl_invoice.inv_number ASC');
                //bdump($tmpBranches);
            }

            $data['amount_from'] = (int)str_replace(' ', '', $data['amount_from']);
            //bdump($amountFrom);
            //die;
            if ($data['amount_from'] != 0) {
                $dataReport = $dataReport->where('price_e2 >= ?', $data['amount_from']);
            }

            if (count($data['cl_status_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_status_id' => $data['cl_status_id']));
            }

            if (count($data['cl_currencies_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_invoice.cl_currencies_id' => $data['cl_currencies_id']));
            }

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_invoice.cl_center_id' => $data['cl_center_id']));
            }

            if (count($data['cl_users_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_invoice.cl_users_id' => $data['cl_users_id']));
            }

            if (count($data['cl_payment_types_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_invoice.cl_payment_types_id' => $data['cl_payment_types_id']));
            }

            if ($data['minus'] == 1) {
                if ($this->settings->platce_dph)
                    $dataReport = $dataReport->where('cl_invoice.price_e2_vat < 0');
                else
                    $dataReport = $dataReport->where('cl_invoice.price_e2 < 0');
            }

            if ($data['overpayed'] == 1) {
                if ($data['min_difference'] == 1) {
                    if ($this->settings->platce_dph)
                        $dataReport = $dataReport->where('(FLOOR(cl_invoice.price_e2_vat) - FLOOR(cl_invoice.price_payed)) < 0 AND cl_invoice.price_e2_vat > 0');
                    else
                        $dataReport = $dataReport->where('(FLOOR(cl_invoice.price_e2) - FLOOR(cl_invoice.price_payed)) < 0 AND cl_invoice.price_e2 > 0');

                } else {
                    if ($this->settings->platce_dph)
                        $dataReport = $dataReport->where('(cl_invoice.price_e2_vat - cl_invoice.price_payed) < 0');
                    else
                        $dataReport = $dataReport->where('(cl_invoice.price_e2 - cl_invoice.price_payed) < 0');
                }
            }

            $dataReport = $dataReport->select('cl_partners_book.company,cl_status.status_name,cl_currencies.currency_code,cl_invoice_types.name AS "druh faktury",cl_payment_types.name AS "druh platby",cl_invoice.*');

            if ($form['save_pdf']->isSubmittedBy()) {
                $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
                $tmpTitle = $this->translator->translate('Kniha_faktur_vydaných_za_období');

                $dataOther = [];
                $dataSettings = $data;
                $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
                                                where(['cl_partners_book.id' => $data['cl_partners_book']])->
                                                select('cl_partners_book.company AS company')->
                                                order('company');
                $dataOther['dataSettingsStatus'] = $this->StatusManager->findAll()->where(['id' => $data['cl_status_id']])->order('status_name');
                $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(['id' => $data['cl_center_id']])->order('name');
                $dataOther['dataSettingsUsers'] = $this->UserManager->getAll()->where(['id' => $data['cl_users_id']])->order('name');
                $dataOther['dataSettingsCurrencies'] = $this->CurrenciesManager->findAll()->where(['id' => $data['cl_currencies_id']])->order('currency_code');
                $dataOther['dataSettingsPayments'] = $this->PaymentTypesManager->findAll()->where(['id' => $data['cl_payment_types_id']])->order('name');

                $tmpArrVat = $this->getVats($dataReport);

                $dataOther['arrVat'] = $tmpArrVat;
                //bdump($tmpArrVat);
                $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Invoice/ReportInvoiceBook.latte', $dataOther, $dataSettings, 'Kniha faktur vydaných');
                $tmpDate1 = new \DateTime($data['date_from']);
                $tmpDate2 = new \DateTime($data['date_to']);
                $this->pdfCreate($template, $this->translator->translate('Kniha_faktur_vydaných_za_období') . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));

                //$template->settings = $this->settings;
                //$template->title = $tmpTitle;
                //$template->author = $tmpAuthor;
                //$template->today = new \Nette\Utils\DateTime;
                //$this->tmpLogo();


            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Kniha_faktur_vydaných");
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }elseif($form['save_xls']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Kniha_faktur_vydaných");
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".xls", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_XLS_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            } elseif ($form['save_xml']->isSubmittedBy()) {

                if ($dataReport->count() > 0) {
                    $arrResult = array();
                    foreach ($dataReport as $key => $one) {
                        $tmpInv = $one->toArray();
                        $arrResult[$key] = array('id' => $tmpInv['id'], 'inv_number' => $tmpInv['inv_number'], 'inv_date' => $tmpInv['inv_date'],
                            'vat_date' => $tmpInv['vat_date'], 'due_date' => $tmpInv['due_date'], 'pay_date' => $tmpInv['pay_date'],
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
                        $arrResult[$key]['partners_book'] = array('id' => $tmpPartnerBook['id'], 'company' => $tmpPartnerBook['company'], 'street' => $tmpPartnerBook['street'],
                            'city' => $tmpPartnerBook['city'], 'zip' => $tmpPartnerBook['zip'], 'ico' => $tmpPartnerBook['ico'], 'dic' => $tmpPartnerBook['dic']);
                        $arrResult[$key]['invoice_items'] = $one->related('cl_invoice_items')->
                        select('cl_pricelist_id,cl_pricelist.identification,cl_invoice_items.item_label, cl_invoice_items.quantity, cl_invoice_items.units, cl_invoice_items.price_s, cl_invoice_items.price_e,  cl_invoice_items.price_e_type,discount,price_e2, price_e2_vat,cl_invoice_items.cl_storage_id')->
                        fetchAll();
                        $arrResult[$key]['invoice_items_back'] = $one->related('cl_invoice_items_back')->
                        select('cl_pricelist_id,cl_pricelist.identification,cl_invoice_items_back.item_label, cl_invoice_items_back.quantity, cl_invoice_items_back.units, cl_invoice_items_back.price_s, cl_invoice_items_back.price_e,  cl_invoice_items_back.price_e_type,discount,price_e2, price_e2_vat,cl_invoice_items_back.cl_storage_id')->
                        fetchAll();
                    }
                    $filename = $this->translator->translate("Kniha_faktur_vydaných");
                    $this->sendResponse(new \XMLResponse\XMLResponse($arrResult, $filename . "-" . date('Ymd-Hi') . ".xml"));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_XML_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
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
        foreach ($tmpArr as $one) {
            if ($one->price_base1 != 0)
                $tmpArr2[$one->vat1] = $one->vat1;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one) {
            if ($one->price_base2 != 0)
                $tmpArr2[$one->vat2] = $one->vat2;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one) {
            if ($one->price_base3 != 0)
                $tmpArr2[$one->vat3] = $one->vat3;
        }

        $tmpArr = $dataReport;
        foreach ($tmpArr as $one) {
            if ($one->price_base0 != 0)
                $tmpArr2[0] = 0;
        }
        foreach ($tmpArr2 as $one) {
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

    public function handleDeletePaired($id, $type)
    {
        if ($type == 'cl_store_docs') {
            if ($data = $this->DataManager->find($id)) {
                $data->update(array('id' => $id, 'cl_store_docs_id' => NULL));
                $this->flashMessage($this->translator->translate('Vazba_na_výdejku_byla_zrušena._Výdejka_však_stále_existuje.'), 'success');
            }
        } elseif ($type == 'cl_commission') {
            if ($data = $this->DataManager->find($id)) {
                $data->update(array('id' => $id, 'cl_commission_id' => NULL));
                $this->flashMessage($this->translator->translate('Vazba_na_zakázku_byla_zrušena._Zakázka_však_stále_existuje.'), 'success');
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

    public function preparePDFData($id)
    {
        return $this->InvoiceManager->preparePDFData($id);
    }

    public function handleDownloadPDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);
        //bdump($noDownload, 'savePDF from invoicepresenter');

        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, TRUE);
    }

    public function handleSendDoc($id, $latteIndex = NULL, $arrData = [], $recepients = [], $emailingTextIndex = null)
    {
        $tmpData = $this->preparePDFData($id);
        parent::handleSendDoc($id, $tmpData['latteIndex'], $tmpData, [], $emailingTextIndex);
    }

    public function DataProcessListGridValidate($data)
    {
        $retVal = NULL;
        if (isset($data['cl_pricelist_id']) && isset($data['quantity'])) {
            if ($data['cl_pricelist_id'] > 0 && $data['quantity'] < 0 && $this->settings->invoice_to_store == 1) {
                $retVal = $this->translator->translate('Množství_pro_výdej_nesmí_být_záporné_pokud_jde_o_položku_ceníku.');
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

    //aditional processing data after save in listgrid
    //23.11.2018 - there must be giveout from store and receiving backitems

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
        if ($tmpData['price_e2'] == 0 && $tmpData['price_e2_vat'] == 0) {
            $this->flashMessage($this->translator->translate('Doklad_má_nulovou_částku._Není_možné_jej_odeslat_do_EET.'), 'danger');
        } elseif (!is_null($tmpData->cl_payment_types_id) && ($tmpData->cl_payment_types->payment_type == 1 || $tmpData->cl_payment_types->eet_send == 1)) {
            //send to EET
            $dataForSign = $this->CompaniesManager->getDataForSignEET($tmpData['cl_company_branch_id']);
            try {
                //bdump($dataForSign);
                $arrRet = $this->EETService->sendEET($tmpData, $dataForSign);
                //bdump($arrRet);
                $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                $tmpData->update(array('cl_eet_id' => $tmpId));
            } catch (\Exception $e) {
                $this->flashMessage($this->translator->translate('Chyba_certifikátu._Zkontrolujte_nahraný_certifikát_a_heslo'), 'danger');
                $this->flashMessage($e->getMessage(), 'danger');
            }
        } else {
            $this->flashMessage($this->translator->translate('Doklad_není_hrazen_v_hotovosti._Není_možné_jej_odeslat_do_EET.'), 'danger');
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
        if (intval($data) > 0) {
            $this->numberSeries['cl_number_series_id'] = $data;
            $data = '';
        } else {
            $this->numberSeries['use'] = $data;
        }
        parent::handleNew($data, $defData);
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
        $data = $form->values;
        //dump(count($data['cl_partners_book']));
        //die;
        if ($form['save_xml']->isSubmittedBy() || $form['save_cash_xml']->isSubmittedBy()) {

            $data['cl_partners_branch'] = array();
            $tmpPodm = array();
            if ($data['after_due_date'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice.due_date < NOW() AND cl_invoice.price_payed < cl_invoice.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice.due_date < NOW() AND cl_invoice.price_payed < cl_invoice.price_e2';
            }

            if ($data['not_payed'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice.price_payed < cl_invoice.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice.price_payed < cl_invoice.price_e2';
            }

            if ($data['payed'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice.price_payed >= cl_invoice.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice.price_payed >= cl_invoice.price_e2';
            }


            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                //$tmpDate = new \Nette\Utils\DateTime;
                //$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
                //date('Y-m-d H:i:s',strtotime($data['date_to']));
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));

            if ($this->settings->platce_dph) {
                $dataReport = $this->InvoiceManager->findAll()->
                where($tmpPodm)->
                where('cl_invoice.vat_date >= ? AND cl_invoice.vat_date <= ?', $data['date_from'], $data['date_to'])->
                order('cl_invoice.inv_number ASC, cl_invoice.vat_date ASC');
            } else {
                $dataReport = $this->InvoiceManager->findAll()->
                where($tmpPodm)->
                where('cl_invoice.inv_date >= ? AND cl_invoice.inv_date <= ?', $data['date_from'], $data['date_to'])->
                order('cl_invoice.inv_number ASC, cl_invoice.vat_date ASC');
            }

            if (count($data['cl_status_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_status_id' => $data['cl_status_id']));
            }


            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_invoice.cl_center_id' => $data['cl_center_id']));
            }

            if (count($data['cl_users_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_invoice.cl_users_id' => $data['cl_users_id']));
            }

            if (count($data['cl_payment_types_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_invoice.cl_payment_types_id' => $data['cl_payment_types_id']));
            }
            $dataReport = $dataReport->select('cl_partners_book.company,cl_status.status_name,cl_currencies.currency_code,cl_invoice_types.name AS "druh faktury",cl_payment_types.name AS "druh platby",cl_invoice.*');


            if ($form['save_xml']->isSubmittedBy()) {
                $this->exportPohoda($dataReport);
            } elseif ($form['save_cash_xml']->isSubmittedBy()) {
                $this->exportCashPohoda($dataReport);
            }
        }
    }

    private function exportPohoda($dataReport)
    {
        $tmpArrVat = $this->getVats($dataReport);
        $customer = array();
        // zadejte ICO
        $pohoda = new Pohoda\Export($this->settings->ico);
        try {

            foreach ($dataReport as $key => $one) {
                if ($this->settings->platce_dph == 1) {
                    $validRates = $this->RatesVatManager->findAllValid($one->vat_date);
                } else {
                    $validRates = array();
                }
                // cislo faktury
                $invoice = new Pohoda\Invoice($one->inv_number);
                $invoice->setType(Pohoda\Invoice::INVOICE_TYPE);
                // cena faktury s DPH (po staru) - volitelně
                $invoice->setText($one->inv_title);
                //$price = 1000;
                $arrPrice = array();
                $arrPrice['zakl_dan0'] = 0;
                $arrPrice['zakl_dan1'] = 0;
                $arrPrice['zakl_dan2'] = 0;
                $arrPrice['zakl_dan3'] = 0;
                $arrPrice['dan1'] = 0;
                $arrPrice['dan2'] = 0;
                $arrPrice['dan3'] = 0;
                $arrPrice['sazba1'] = 0;
                $arrPrice['sazba2'] = 0;
                $arrPrice['sazba3'] = 0;

                for ($x = 0; $x <= 3; $x++) {
                    foreach ($validRates as $keyR => $oneR) {
                        //dump($oneR);
                        if (isset($one['vat' . $x]) && $oneR['rates'] == $one['vat' . $x]) {
                            if ($one['vat' . $x] == 0 && $one['price_base' . $x] != 0) {
                                $arrPrice['zakl_dan0'] = $one['price_base' . $x];
                                $arrPrice['sazba' . $x] = $one['vat' . $x];
                            } elseif ($one['price_base' . $x] != 0) {
                                $arrPrice['zakl_dan' . $x] = $one['price_base' . $x];
                                $arrPrice['dan' . $x] = $one['price_vat' . $x];
                                $arrPrice['sazba' . $x] = $one['vat' . $x];
                            }
                        } elseif (!isset($one['vat' . $x]) && $one['price_base' . $x] != 0 && $arrPrice['zakl_dan0'] == 0) {
                            $arrPrice['zakl_dan0'] = $one['price_base' . $x];
                        }

                    }
                    //dump($keyR);
                    //dump($oneR);
                }
                //dump($arrPrice);
                $invoice->setPaymentType($this->ArraysManager->getPaymentTypePohodaName($one->cl_payment_types['payment_type']));
                //dump($arrPrice);

                //nulova sazba dph
                $invoice->setPriceNone($arrPrice['zakl_dan0']);

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
                $invoice->setPriceRound($one->price_correction);
                $invoice->setWithVat($one->export == 0); //viz inv:classificationVAT - true nastavi cleneni dph na inland - tuzemske plneni, jinak da nonSubsume - nezahrnovat do DPH

                if ($this->settings->cl_currencies_id != $one->cl_currencies_id) {
                    $invoice->setForeignCurrency($one->cl_currencies->currency_code, (float)$one->currency_rate);
                }
                //$invoice->setActivity('eshop'); //cinnost v pohode [volitelne, typ:ids]
                //$invoice->setCentre('stredisko'); //stredisko v pohode [volitelne, typ:ids]
                if (!is_null($one->cl_commission_id)) {
                    $invoice->setContract($one->cl_commission->cm_number); //zakazka v pohode [volitelne, typ:ids]
                }

                //nebo pridanim polozek do faktury (nove)
                //$invoice->setText('Faktura za zboží');
                //polozky na fakture
                foreach ($one->related('cl_invoice_items')->order('item_order') as $keyItem => $oneItem) {
                    $item = new Pohoda\InvoiceItem();
                    $item->setText($oneItem->item_label);
                    $item->setQuantity($oneItem->quantity); //pocet
                    if (!is_null($oneItem->cl_pricelist_id)) {
                        $item->setCode($oneItem->cl_pricelist->identification); //katalogove cislo
                    }
                    $item->setUnit($oneItem->units); //jednotka
                    $item->setNote($oneItem->description1 . " " . $oneItem->description2); //poznamka
                    //$item->setStockItem(230); //ID produktu v Pohode
                    //nastaveni ceny je volitelne, Pohoda si umi vytahnout cenu ze sve databaze pokud je nastaven stockItem
                    $item->setUnitPrice($oneItem->price_e); //cena

                    if ($oneItem->vat == 21)
                        $item->setRateVAT($item::VAT_HIGH); //21%
                    elseif ($oneItem->vat == 15)
                        $item->setRateVAT($item::VAT_LOW); //15%
                    elseif ($oneItem->vat == 10)
                        $item->setRateVAT($item::VAT_THIRD); //10%
                    elseif ($oneItem->vat == 0)
                        $item->setRateVAT($item::VAT_NONE); //21%

                    $item->setPayVAT($oneItem->price_e_type == 1); //cena bez dph
                    $item->setDiscountPercentage((float)$oneItem->discount);
                    $invoice->addItem($item);
                }

                //polozky zpět na fakture
                foreach ($one->related('cl_invoice_items_back')->order('item_order') as $keyItem => $oneItem) {
                    $item = new Pohoda\InvoiceItem();
                    $item->setText($oneItem->item_label);
                    $item->setQuantity(0 - $oneItem->quantity); //pocet
                    if (!is_null($oneItem->cl_pricelist_id)) {
                        $item->setCode($oneItem->cl_pricelist->identification); //katalogove cislo
                    }
                    $item->setUnit($oneItem->units); //jednotka
                    $item->setNote($oneItem->description1 . " " . $oneItem->description2); //poznamka
                    //$item->setStockItem(230); //ID produktu v Pohode
                    //nastaveni ceny je volitelne, Pohoda si umi vytahnout cenu ze sve databaze pokud je nastaven stockItem
                    $item->setUnitPrice($oneItem->price_e); //cena

                    if ($oneItem->vat == 21)
                        $item->setRateVAT($item::VAT_HIGH); //21%
                    elseif ($oneItem->vat == 15)
                        $item->setRateVAT($item::VAT_LOW); //15%
                    elseif ($oneItem->vat == 10)
                        $item->setRateVAT($item::VAT_THIRD); //10%
                    elseif ($oneItem->vat == 0)
                        $item->setRateVAT($item::VAT_NONE); //21%

                    $item->setPayVAT($oneItem->price_e_type == 1); //cena bez dph
                    $item->setDiscountPercentage((float)$oneItem->discount);
                    $invoice->addItem($item);
                }

                // variabilni cislo
                $invoice->setVariableNumber((int)$one->var_symb);
                // datum vytvoreni faktury
                $invoice->setDateCreated($one->inv_date);
                // datum zdanitelneho plneni
                $invoice->setDateTax($one->vat_date);
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
                    "zip" => (int)str_replace(" ", "", $one->cl_company->zip),
                    "ico" => $one->cl_company->ico,
                    "dic" => $one->cl_company->dic
                ]);

                // nastaveni identity prijemce
                if (!is_null($one->cl_partners_branch_id) && $one->cl_partners_branch['use_as_main'] == 1) {
                    //if there is branch set, let's use it
                    $tmpCustomer = [
                        "company" => $one->cl_partners_branch['b_name'],
                        "city" => $one->cl_partners_branch['b_city'],
                        "street" => $one->cl_partners_branch['b_street'],
                        "number" => "",
                        "zip" => (int)str_replace(" ", "", $one->cl_partners_branch['b_zip']),
                        "ico" => (int)str_replace(" ", "", $one->cl_partners_branch['b_ico']),
                        "dic" => $one->cl_partners_branch['b_dic']
                    ];
                } else {
                    //common partner
                    if (!is_null($one->cl_partners_book_id)) {
                        $tmpCustomer = [
                            "company" => $one->cl_partners_book->company,
                            "city" => $one->cl_partners_book->city,
                            "street" => $one->cl_partners_book->street,
                            "number" => "",
                            "zip" => (int)str_replace(" ", "", $one->cl_partners_book->zip),
                            "ico" => (int)str_replace(" ", "", $one->cl_partners_book->ico),
                            "dic" => $one->cl_partners_book->dic
                        ];
                    } else {
                        $tmpCustomer = array();
                    }
                }


                // nebo jednoduseji identitu nechat vytvorit
                //$customerAddress = $invoice->createCustomerAddress($customer, "z125", ["street" => "Pod Mostem"]);

                if (!is_null($one->cl_partners_book_id)) {
                    $customer[$tmpCustomer['company']] = $invoice->createCustomerAddress($tmpCustomer);
                } else {
                    $invoice->createCustomerAddress($tmpCustomer);
                }

                if ($invoice->isValid()) {
                    // pokud je faktura validni, pridame ji do exportu
                    $pohoda->addInvoice($invoice);
                    //pokud se ma importovat do adresare
                    //$pohoda->addAddress($customer);
                } else {
                    var_dump($invoice->getErrors());
                }
            }
            //příprava adres pro import, řešíme až zde aby nebyly duplicity v adresáři
            foreach ($customer as $key => $oneCustomer) {
                $pohoda->addAddress($oneCustomer);
            }


            // ulozeni do souboru
            $errorsNo = 0; // pokud si pocitate chyby, projevi se to v nazvu souboru

            $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
            $subFolder = $this->ArraysManager->getSubFolder(array(), 'cl_invoice_id');
            $destFile = $dataFolder . '/' . $subFolder;  // . '/' . 'invoice_export.xml';
            $pohoda->setExportFolder($destFile); //mozno nastavit slozku, do ktere bude proveden export

            $file = $pohoda->exportToFile(time(), 'Trynx', 'invoice_export', $errorsNo);
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

    private function exportCashPohoda($dataReport)
    {
        $dataReport = $dataReport->where('cl_payment_types.payment_type = 1');
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<dat:dataPack version=\"2.0\" id=\"po001\" ico=\"" . $this->settings->ico . "\" application=\"Trynx\" note=\"Import Pokladního dokladu\" 
xmlns:dat=\"http://www.stormware.cz/schema/version_2/data.xsd\" 
xmlns:vch=\"http://www.stormware.cz/schema/version_2/voucher.xsd\" 
xmlns:typ=\"http://www.stormware.cz/schema/version_2/type.xsd\" >";
        $tmpBranch = $this->CompanyBranchManager->find($this->user->getIdentity()->cl_company_branch_id);

        if ($tmpBranch) {
            $tmpCash = $tmpBranch->cl_cash_def;
        } else {
            $tmpCash = $this->CashDefManager->findAll()->where('def_cash = 1')->fetch();
        }
        if (!$tmpCash) {
            $tmpCash = $this->CashDefManager->findAll()->limit(1)->fetch();
            $cashShortName = $tmpCash->short_name;
        } else {
            $cashShortName = "HP";
        }


        foreach ($dataReport as $key => $one) {
            if ($this->settings->platce_dph == 1) {
                $validRates = $this->RatesVatManager->findAllValid($one->vat_date);
            } else {
                $validRates = array();
            }

            //income příjem, expense výdej   HPR a HPU  - doplnit do definice pobočky
            $xml .= "<!-- Pokladní doklad bez položek -->
	<dat:dataPackItem version=\"2.0\" id=\"$one->inv_number\">
		<vch:voucher version=\"2.0\">
			<vch:voucherHeader>
				<vch:voucherType>" . (($one->price_e2_vat < 0) ? "expanse" : "receipt") . "</vch:voucherType>
				<vch:cashAccount>
					<typ:ids>" . $tmpCash->short_name . "</typ:ids>
				</vch:cashAccount>
				<vch:date>" . $one->inv_date->format('Y-m-d') . "</vch:date>
				<vch:datePayment>" . $one->inv_date->format('Y-m-d') . "</vch:datePayment>
				<vch:dateTax>" . $one->vat_date->format('Y-m-d') . "</vch:dateTax>
				<vch:classificationVAT>
					<typ:classificationVATType>nonSubsume</typ:classificationVATType>
				</vch:classificationVAT>
				<vch:text>úhrada faktury $one->inv_number</vch:text>
				<!--adresa bez vazby na program POHODA-->
				<vch:partnerIdentity>
					<typ:address>
						<typ:name>" . $one->cl_partners_book->company . "</typ:name>
						<typ:city>" . $one->cl_partners_book->city . "</typ:city>
						<typ:street>" . $one->cl_partners_book->street . "</typ:street>
						<typ:zip>" . $one->cl_partners_book->zip . "</typ:zip>
					</typ:address>
				</vch:partnerIdentity>
				<vch:centre>
					<typ:ids></typ:ids>
				</vch:centre>
				<vch:note>načteno z XML.</vch:note>
				<vch:intNote>Import Pokladního dokladu bez položek.</vch:intNote>
			</vch:voucherHeader>
			<vch:voucherSummary>
				<vch:homeCurrency>
					<typ:priceNone>$one->price_e2_vat</typ:priceNone>
				</vch:homeCurrency>
			</vch:voucherSummary>
		</vch:voucher>
	</dat:dataPackItem>";


        }
        $xml .= "</dat:dataPack>";

        $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
        $subFolder = $this->ArraysManager->getSubFolder(array(), 'cl_invoice_id');
        $file = $dataFolder . '/' . $subFolder . '/' . 'invoice_cash_export.xml';
        file_put_contents($file, $xml);

        $httpResponse = $this->getHttpResponse();
        $httpResponse->setHeader('Pragma', "public");
        $httpResponse->setHeader('Expires', 0);
        $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
        $httpResponse->setHeader('Content-Transfer-Encoding', "binary");
        $httpResponse->setHeader('Content-Description', "File Transfer");
        $httpResponse->setHeader('Content-Length', filesize($file));
        //$this->sendResponse(new DownloadResponse($file, basename($file) , array('application/octet-stream', 'application/force-download', 'application/download')));
        $this->sendResponse(new \Nette\Application\Responses\FileResponse($file, basename($file), 'application/download'));

    }

    public function handleChangePartner()
    {
        $this->createDocShow = TRUE;
        if ($tmpInvoice = $this->DataManager->find($this->id)) {
            $this['edit2']->setValues($tmpInvoice);
        }
        $this->showModal('changePartner');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleUpdatePartner($cl_partners_book_id)
    {
        if ($tmpInvoice = $this->DataManager->find($this->id)) {
            $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($cl_partners_book_id);
            $arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?', $cl_partners_book_id)->fetchPairs('id', 'b_name');
            $tmpInvoice->update(array('cl_partners_book_id' => $cl_partners_book_id, 'cl_partners_book_workers_id' => NULL, 'cl_partners_branch_id' => NULL));
            $this['edit2']->setValues($tmpInvoice);
        }
        $this->redrawControl('partnerData');
    }

    public function stepBack2()
    {
        $this->redirect('default');
    }

    public function FormValidate2(Form $form)
    {
        $data = $form->values;
        /*02.12.2020 - cl_partners_book_id required and prepare data for just created partner
        */
        $data = $this->updatePartnerId($data);
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0) {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }

        $this->redrawControl('content');

    }

    public function SubmitEditSubmitted2(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() || $form['send_fin']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy() || $form['store_out']->isSubmittedBy()) {

            //$data['inv_date'] = date('Y-m-d H:i:s',strtotime($data['inv_date']));
            //$data['vat_date'] = date('Y-m-d H:i:s',strtotime($data['vat_date']));
            //$data['due_date'] = date('Y-m-d H:i:s',strtotime($data['due_date']));
            if ($this->id == NULL) {
                $tmpId = $this->bscId;
            } else {
                $tmpId = $this->id;
            }
            $data['id'] = $tmpId;
            $data = $this->RemoveFormat($data);//
            $this->DataManager->update($data);
            $this->UpdatePairedDocs($data);
        }
    }

    public function UpdatePairedDocs($data){
        $tmpPaired = $this->PairedDocsManager->findAll()->where('cl_invoice_id = ? AND cl_delivery_note_id IS NOT NULL', $data['id'])->fetch();
        if ($tmpPaired && !is_null($tmpPaired['cl_delivery_note_id'])){
            $this->DeliveryNoteManager->update(['id' => $tmpPaired['cl_delivery_note_id'],
                                                'cl_partners_book_id' => $data['cl_partners_book_id'],
                                                'cl_center_id' => $data['cl_center_id'],
                                                'cl_partners_branch_id' => $data['cl_partners_branch_id'],
                                                'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                                'cl_users_id' => $data['cl_users_id']
                ]);
            $tmpPaired2 = $this->PairedDocsManager->findAll()->where('cl_delivery_note_id = ? AND cl_store_docs_id IS NOT NULL', $tmpPaired['cl_delivery_note_id']);
            //if ($tmpPaired2 && !is_null($tmpPaired2['cl_store_docs_id'])) {
            foreach($tmpPaired2 as $key => $one){
                $this->StoreDocsManager->update(['id' => $one['cl_store_docs_id'],
                                                'cl_partners_book_id' => $data['cl_partners_book_id'],
                                                'cl_center_id' => $data['cl_center_id'],
                                                'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                                'cl_users_id' => $data['cl_users_id']
                ]);
                //                                                'cl_partners_branch_id' => $data['cl_partners_branch_id'],
            }
            $tmpPaired3 = $this->PairedDocsManager->findAll()->where('cl_commission_id IS NOT NULL AND cl_delivery_note_id = ?', $tmpPaired['cl_delivery_note_id'])->fetch();
            if ($tmpPaired3 && !is_null($tmpPaired3['cl_commission_id'])){
                $this->CommissionManager->update(['id' => $tmpPaired3['cl_commission_id'],
                    'cl_partners_book_id' => $data['cl_partners_book_id'],
                    'cl_center_id' => $data['cl_center_id'],
                    'cl_partners_branch_id' => $data['cl_partners_branch_id'],
                    'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                    'cl_users_id' => $data['cl_users_id']
                ]);
            }
        }else {
            $tmpPaired = $this->PairedDocsManager->findAll()->where('cl_invoice_id = ? AND cl_store_docs_id IS NOT NULL', $data['id']);
            //if ($tmpPaired && !is_null($tmpPaired['cl_store_docs_id'])) {
            foreach($tmpPaired as $key => $one){
                $this->StoreDocsManager->update(['id' => $one['cl_store_docs_id'],
                                                'cl_partners_book_id' => $data['cl_partners_book_id'],
                                                'cl_center_id' => $data['cl_center_id'],
                                                'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                                'cl_users_id' => $data['cl_users_id']
                ]);
            }
            $tmpPaired = $this->PairedDocsManager->findAll()->where('cl_commission_id IS NOT NULL AND cl_invoice_id = ?', $data['id'])->fetch();
            if ($tmpPaired && !is_null($tmpPaired['cl_commission_id'])){
                $this->CommissionManager->update(['id' => $tmpPaired['cl_commission_id'],
                    'cl_partners_book_id' => $data['cl_partners_book_id'],
                    'cl_center_id' => $data['cl_center_id'],
                    'cl_partners_branch_id' => $data['cl_partners_branch_id'],
                    'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                    'cl_users_id' => $data['cl_users_id']
                ]);
            }


        }

    }

    public function handleInsertDiscount()
    {
        $this->showModal('insertDiscount');
        $this->redrawControl('insertDiscount');
        $this->redrawControl('contents');
    }

    //validating of data from listgrid

    public function stepBack3()
    {
        $this->redirect('default');
    }

    public function FormValidate3(Form $form)
    {

        $this->redrawControl('content');

    }

    public function SubmitEditSubmitted3(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {
            /*if ($this->id == NULL) {
                $tmpId = $this->bscId;
            } else {
                $tmpId = $this->id;
            }*/
            $tmpId = $data['id'];
            //$data['id'] = $tmpId;
            bdump($tmpId, 'tmpId');
            $data = $this->RemoveFormat($data);//
            $oldText = $data['text'];
            if ($data['discount_per'] != 0) {
                if (strpos($data['text'], '[%]')) {
                    $text = str_ireplace('[%]', $data['discount_per'], $data['text']);
                } else {
                    if (strlen($data['text']) > 0) {
                        $text = $data['text'] . $data['discount_per'] . '%';
                    } else {
                        $text = 'Poskytnuta sleva ' . $data['discount_per'] . '%';
                    }

                }
            } elseif ($data['discount_abs'] != 0) {
                if (strlen($data['text']) > 0) {
                    $text = $data['text'];
                } else {
                    $text = 'Poskytnuta sleva ';
                }
            }

            $usedVat = $this->InvoiceItemsManager->findAll()->where($this->DataManager->tableName . '_id = ?', $tmpId)->select('vat, SUM(price_e2) AS price_e2')->group('vat')->order('price_e2 DESC, vat DESC')->fetchPairs('vat', 'price_e2');
            foreach ($usedVat as $keyVat => $oneVat) {
                if ($data['discount_abs'] != 0) {
                    $tmpPrice = -$data['discount_abs'];
                } elseif ($data['discount_per'] != 0) {
                    $tmpPrice = -($oneVat * $data['discount_per'] / 100);
                }

                $arrData = new \Nette\Utils\ArrayHash;
                $arrData[$this->DataManager->tableName . '_id'] = $tmpId;
                $arrData['item_order'] = $this->InvoiceItemsManager->findAll()->where($this->DataManager->tableName . '_id = ?', $tmpId)->max('item_order') + 1;
                $arrData['item_label'] = $text;
                $arrData['quantity'] = 1;
                $arrData['vat'] = $keyVat;

                $arrData['price_s'] = 0;
                $arrData['price_e_type'] = $this->settings->price_e_type;
                if ($arrData['price_e_type'] == 1) {
                    $arrData['price_e'] = $tmpPrice * (1 + ($keyVat / 100));
                } else {
                    $arrData['price_e'] = $tmpPrice;
                }
                $arrData['discount'] = 0;
                $arrData['price_e2'] = $tmpPrice;
                $arrData['price_e2_vat'] = $tmpPrice * (1 + ($keyVat / 100));

                $row = $this->InvoiceItemsManager->insert($arrData);
                if ($data['discount_abs'] != 0) {
                    break;
                }
            }
            $this->settings->update(array('invoice_discount_txt' => $oldText));

            $this->updateSum($this->id, $this);

            //$this->DataManager->update($data);
        }

    }

    protected function createComponentHelpbox()
    {
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\HelpboxControl($this->translator, "");
    }

    protected function createComponentEditTextFooter()
    {
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\EditTextControl($this->translator,
            $this->DataManager, $this->id, 'footer_txt');
    }

    protected function createComponentEditTextHeader()
    {
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\EditTextControl($this->translator,
            $this->DataManager, $this->id, 'header_txt');
    }

    protected function createComponentEditTextDescription()
    {
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\EditTextControl($this->translator,
            $this->DataManager, $this->id, 'description_txt');
    }

    protected function createComponentPairedDocs()
    {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        //$translator = clone $this->translator->setPrefix([]);
        return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }

    protected function createComponentTextsUse()
    {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        //$translator = clone $this->translator->setPrefix([]);
        return new TextsUseControl($this->DataManager, $this->id, 'invoice', $this->TextsManager, $this->translator);
    }

    protected function createComponentSumOnDocs()
    {
        //$this->translator->setPrefix(['applicationModule.invoice']);
        if ($data = $this->DataManager->findBy(['id' => $this->id])->fetch()) {
            if ($data->cl_currencies) {
                $tmpCurrencies = $data->cl_currencies->currency_name;
            } else {
                $tmpCurrencies = '';
            }

            if ($this->settings->platce_dph) {
                $tmpPriceNameBase = $this->translator->translate("SumWithoutVAT");
                $tmpPriceNameVat = $this->translator->translate("SumWithVAT");
            } else {
                $tmpPriceNameBase = $this->translator->translate("Prodej");
                $tmpPriceNameVat = "";
            }

            $taxAdvance_base = $data->base_payed1 + $data->base_payed2 + $data->base_payed3;
            $taxAdvance_vat = $data->vat_payed1 + $data->vat_payed2 + $data->vat_payed3;
            $taxAdvance_used = $this->InvoicePaymentsManager->findAll()->where('used_cl_invoice_id = ?', $this->id)->sum('pay_price');
            if ($this->settings->platce_dph == 1) {
                $dataArr = [
                    ['name' => $this->translator->translate('Výdejní_cena'), 'value' => $data->price_s, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate("Zisk") . " " . round($data->profit, 1) . "%", 'value' => $data->profit_abs, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Daňová_záloha_základ'), 'value' => $taxAdvance_base, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Daňová_záloha_daň'), 'value' => $taxAdvance_vat, 'currency' => $tmpCurrencies],
                    ['name' => 'separator'],
                    ['name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('DPH'), 'value' => $data->price_vat1 + $data->price_vat2 + $data->price_vat3, 'currency' => $this->settings->cl_currencies->currency_name],
                    ['name' => $this->translator->translate('Zaokrouhlení'), 'value' => $data->price_correction, 'currency' => $tmpCurrencies],
                    ['name' => $tmpPriceNameVat, 'value' => $data->price_e2_vat, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Záloha'), 'value' => $data->advance_payed, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Zaplaceno'), 'value' => $data->price_payed - $data->advance_payed, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Zbývá_k_úhradě'), 'value' => $data->price_e2_vat - $data->price_payed, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Čerpáno_ze_zálohy'), 'value' => $taxAdvance_used, 'currency' => $tmpCurrencies]
                ];
            } else {
                $dataArr = [
                    ['name' => $this->translator->translate('Výdejní_cena'), 'value' => $data->price_s, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate("Zisk") . round($data->profit, 1) . "%", 'value' => $data->profit_abs, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Výdejní_cena'), 'value' => $data->price_s, 'currency' => $tmpCurrencies],

                    ['name' => 'separator'],
                    ['name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Zaokrouhlení'), 'value' => $data->price_correction, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Záloha'), 'value' => $data->advance_payed, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Zaplaceno'), 'value' => $data->price_payed - $data->advance_payed, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Zbývá_k_úhradě'), 'value' => $data->price_e2 - $data->price_payed, 'currency' => $tmpCurrencies],
                    ['name' => $this->translator->translate('Čerpáno_ze_zálohy'), 'value' => $taxAdvance_used, 'currency' => $tmpCurrencies]
                ];
            }

            if ($this->settings->invoice_to_store == 0) {

            }

        } else {
            $dataArr = array();
        }
        //$translator = clone $this->translator->setPrefix([]);
        return new SumOnDocsControl($this->translator,
            $this->DataManager, $this->id, $this->settings, $dataArr);
    }

    protected function createComponentEmail()
    {
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\EmailControl($this->translator,
            $this->EmailingManager, $this->mainTableName, $this->id);
    }

    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl($this->translator,
            $this->FilesManager, $this->UserManager, $this->id, 'cl_invoice_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    protected function createComponentPaymentListGrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        $tmpPrice = 0;
        if ($tmpParentData) {
            $tmpCurrenciesId = $tmpParentData->cl_currencies_id;
        } else {
            $tmpCurrenciesId = NULL;
        }

        $arrUsedInvoices = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $this->id)->select('used_cl_invoice_id AS id')->fetchPairs('id','id');
        if (is_null($tmpParentData['cl_partners_book_id'])){
            $arrTaxAdvances = [];
        }else{
            $arrTaxAdvances = $this->DataManager->findAll()
                ->where('cl_number_series.form_use = ? AND cl_partners_book_id = ? AND ((cl_invoice.price_e2_vat - cl_invoice.price_e2_used) > 0 OR cl_invoice.id IN (?))',
                    'invoice_tax', $tmpParentData['cl_partners_book_id'], $arrUsedInvoices)
                ->select('CONCAT(inv_number, " - ", ?, price_e2_vat - price_e2_used, " ", cl_currencies.currency_code) AS inv_number, cl_invoice.id AS id', 'k čerpání: ')
                ->order('cl_invoice.inv_date ASC')
                ->fetchPairs('id', 'inv_number');
        }

        if ($this->settings->platce_dph == 1) {
            $arrData = [
                'used_cl_invoice_id' =>  [$this->translator->translate('Daňová_záloha_k_čerpání'),'format' => 'chzn-selectModal', 'values' => $arrTaxAdvances],
                'pay_price' => [$this->translator->translate('Částka'), 'format' => 'currency', 'size' => 10],
                'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text', 'size' => 10, 'values' => $this->CurrenciesManager->findAllTotal()->fetchPairs('id', 'currency_name')],
                'pay_doc' => [$this->translator->translate('Doklad'), 'format' => 'text', 'size' => 20],
                'cl_cash.cash_number' => [$this->translator->translate('Pokladna'), 'format' => 'url',  'size' => 12, 'url' => 'cash', 'value_url' => 'cl_cash_id'],
                'cl_payment_types.name' => [$this->translator->translate('Typ_platby'), 'format' => 'text', 'size' => 10, 'values' => $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name')],
                'pay_type' => [$this->translator->translate('Druh_úhrady'), 'format' => 'text', 'size' => 10, 'values' => ['0' => 'běžná úhrada', '1' => 'záloha']],
                'pay_vat' => [$this->translator->translate('Daňová_záloha'), 'format' => 'text', 'size' => 10, 'values' => ['0' => 'ne', '1' => 'ano']],
                'vat' => [$this->translator->translate('DPH_%'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 7],
                'pay_date' => [$this->translator->translate('Datum_platby'), 'format' => 'date', 'size' => 10]
            ];
            if ($tmpParentData) {
                $tmpPrice = $tmpParentData->price_e2_vat;
            }
        } else {
            $arrData = [
                'used_cl_invoice_id' =>  [$this->translator->translate('Daňová_záloha_k_čerpání'),'format' => 'chzn-selectModal', 'values' => $arrTaxAdvances],
                'pay_price' => [$this->translator->translate('Částka'), 'format' => 'currency', 'size' => 10],
                'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text', 'size' => 10, 'values' => $this->CurrenciesManager->findAllTotal()->fetchPairs('id', 'currency_name')],
                'pay_doc' => [$this->translator->translate('Doklad'), 'format' => 'text', 'size' => 20],
                'cl_cash.cash_number' => [$this->translator->translate('Pokladna'), 'format' => 'url',  'size' => 12, 'url' => 'cash', 'value_url' => 'cl_cash_id'],
                'cl_payment_types.name' => [$this->translator->translate('Typ_platby'), 'format' => 'text', 'size' => 10, 'values' => $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name')],
                'pay_type' => [$this->translator->translate('Druh_úhrady'), 'format' => 'text', 'size' => 10, 'values' => ['0' => 'běžná úhrada', '1' => 'záloha']],
                'pay_vat' => [$this->translator->translate('Daňová_záloha'), 'format' => 'text', 'size' => 15, 'values' => ['0' => 'ne', '1' => 'ano']],
                'pay_date' => [$this->translator->translate('Datum_platby'), 'format' => 'date', 'size' => 15]
            ];
            if ($tmpParentData) {
                $tmpPrice = $tmpParentData->price_e2;
            }
        }

        //$translator = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->InvoicePaymentsManager,
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

        $control->onChange[] = function ($lineId) {
            $this->updatePaymentSum($lineId);
        };

        return $control;

    }

    public function updatePaymentSum($lineId){
        $this->DataManager->updateInvoiceSum($this->id);
        //$this->DataManager->paymentUpdate($this->id);
        //$this->DataManager->cashUpdate($this->id, $lineId);

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
        $this->formName = $this->translator->translate("Faktury_vydané");
        $this->mainTableName = 'cl_invoice';
        //$settings = $this->CompaniesManager->getTable()->fetch();
        if ($this->settings->platce_dph == 1) {
            $arrData = ['inv_number' => $this->translator->translate('Číslo_dokladu'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean', 'style' => 'glyphicon glyphicon-lock'],
                'storno' => [$this->translator->translate('Storno'), 'format' => 'boolean'],
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_invoice_types.name' => [$this->translator->translate('Druh_dokladu'), 'format' => 'text'],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'show_clink' => true],
                'cl_partners_branch.b_name' => $this->translator->translate('Pobočka'),
                'cl_payment_types.name' => $this->translator->translate('Forma_úhrady'),
                'content__' => [$this->translator->translate('Obsah_faktury'), 'size' => 20, 'format' => 'text',  'function' => 'getContent',  'function_param' => ['id']],
                'cl_eet.eet_status' => [$this->translator->translate('Stav_EET'), 'size' => 20, 'arrValues' => $this->ArraysManager->getEETStatusTypes(),
                    'format' => 'colorpoint', 'colours' => $this->ArraysManager->getEETColours(),
                    'hideOnCond' => 'cl_payment_types.eet_send', 'hideOnVal' => 0],
                'due_date' => [$this->translator->translate('Splatnost'), 'format' => 'date'],
                'vat_date' => [$this->translator->translate('DUZP'), 'format' => 'date'],
                'pay_date' => [$this->translator->translate('Uhrazeno'), 'format' => 'date'],
                'inv_title' => $this->translator->translate('Popis'),
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                'price_e2_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                'advance_payed' => [$this->translator->translate('Záloha'), 'format' => 'currency'],
                'price_e2_used' => [$this->translator->translate('Čerpáno'), 'format' => 'currency'],
                'price_s' => [$this->translator->translate('Výdejní_cena'), 'format' => 'currency'],
                'profit' => [$this->translator->translate('Zisk_%'), 'format' => 'currency'],
                'profit_abs' => [$this->translator->translate('Zisk_abs.'), 'format' => 'currency'],
                'cl_currencies.currency_code' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'export' => [$this->translator->translate('Export'), 'format' => 'boolean'],
                'pdp' => [$this->translator->translate('PDP'), 'format' => 'boolean'],
                'var_symb' => $this->translator->translate('Var_symbol'),
                'spec_symb' => $this->translator->translate('Spec_symbol'),
                'konst_symb' => $this->translator->translate('Konst_symbol'),
                'correction_inv_number' => $this->translator->translate('Číslo_opravované_faktury'),
                'cm_number' => $this->translator->translate('Zakázka'),
                'cl_commission.cm_number' => $this->translator->translate('Spárovaná_zakázka'),
                'cl_store_docs.doc_number' => $this->translator->translate('Číslo_výdejky'),
                'delivery_number' => $this->translator->translate('Dodací_listy'),
                'cl_payment_order.po_number' => [$this->translator->translate('Platební_příkaz'), 'format' => 'url', 'size' => 10, 'url' => 'paymentorder', 'value_url' => 'cl_payment_order_id'],
                'cl_users.name' => $this->translator->translate('Obchodník'),
                'transport__' => [$this->translator->translate('Doprava'), 'format' => 'text', 'function' => 'getTransportType',  'function_param' => ['id']],
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        } else {
            $arrData = ['inv_number' => $this->translator->translate('Číslo_dokladu'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean', 'style' => 'glyphicon glyphicon-lock'],
                'storno' => [$this->translator->translate('Storno'), 'format' => 'boolean'],
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_invoice_types.name' => [$this->translator->translate('Druh_dokladu'), 'format' => 'text'],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'show_clink' => true],
                'cl_partners_branch.b_name' => $this->translator->translate('Pobočka'),
                'cl_payment_types.name' => $this->translator->translate('Forma_úhrady'),
                'content__' => [$this->translator->translate('Obsah_faktury'), 'size' => 20, 'format' => 'text',  'function' => 'getContent',  'function_param' => ['id']],
                'cl_eet.eet_status' => [$this->translator->translate('Stav_EET'), 'size' => 20, 'arrValues' => $this->ArraysManager->getEETStatusTypes(),
                    'format' => 'colorpoint', 'colours' => $this->ArraysManager->getEETColours()],
                'due_date' => [$this->translator->translate('Splatnost'), 'format' => 'date'],
                'pay_date' => [$this->translator->translate('Uhrazeno'), 'format' => 'date'],
                'inv_title' => $this->translator->translate('Popis'),
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_celkem'), 'format' => 'currency'],
                'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                'advance_payed' => [$this->translator->translate('Záloha'), 'format' => 'currency'],
                'price_e2_used' => [$this->translator->translate('Čerpáno'), 'format' => 'currency'],
                'price_s' => [$this->translator->translate('Výdejní_cena'), 'format' => 'currency'],
                'profit' => [$this->translator->translate('Zisk_%'), 'format' => 'currency'],
                'profit_abs' => [$this->translator->translate('Zisk_abs.'), 'format' => 'currency'],
                'cl_currencies.currency_code' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'export' => [$this->translator->translate('Export'), 'format' => 'boolean'],
                'pdp' => [$this->translator->translate('PDP'), 'format' => 'boolean'],
                'var_symb' => $this->translator->translate('Var._symbol'),
                'spec_symb' => $this->translator->translate('Spec._symbol'),
                'konst_symb' => $this->translator->translate('Konst._symbol'),
                'correction_inv_number' => $this->translator->translate('Číslo_opravované_faktury'),
                'cm_number' => $this->translator->translate('Zakázka'),
                'cl_commission.cm_number' => $this->translator->translate('Spárovaná_zakázka'),
                'cl_store_docs.doc_number' => $this->translator->translate('Číslo_výdejky'),
                'delivery_number' => $this->translator->translate('Dodací_listy'),
                'cl_payment_order.po_number' => [$this->translator->translate('Platební_příkaz'), 'format' => 'url', 'size' => 10, 'url' => 'paymentorder', 'value_url' => 'cl_payment_order_id'],
                'cl_users.name' => $this->translator->translate('Obchodník'),
                'transport__' => [$this->translator->translate('Doprava'), 'format' => 'text', 'function' => 'getTransportType',  'function_param' => ['id']],
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        }
        if ($this->settings->invoice_to_store == 0) {
            unset($arrData['price_s']);
            unset($arrData['profit_abs']);
            unset($arrData['profit']);
        }
        if (!$this->settings->eet_active && !$this->settings->eet_test) {
            unset($arrData['cl_eet.eet_status']);
        }

        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = array('inv_number' => '', 'cl_partners_book.company' => 'autocomplete', 'cl_invoice_types.name' => 'autocomplete',
            'cl_invoice.var_symb' => 'autocomplete', 'cl_status.status_name' => 'autocomplete',
            'inv_title' => '', 'cl_users.name' => 'autocomplete', 'inv_date' => 'none', 'cl_payment_types.name' => 'autocomplete',
            'due_date' => 'none', 'pay_date' => '', 'vat_date' => '', 'cl_partners_branch.b_name' => 'autocomplete',
            'correction_inv_number' => 'autocomplete', 'cl_currencies.currency_code' => 'autocomplete',
            'price_e2' => '', 'price_e2_vat' => '', 'price_payed' => '', 'advance_payed' => '', 'cl_commission.cm_number' => 'autocomplete', 'cl_store_docs.doc_number' => 'autocomplete');
        $this->DefSort = 'inv_date DESC';

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['cl_invoice.inv_number', 'cl_invoice.var_symb', 'cl_partners_book.company', 'cl_invoice.inv_title', 'cl_invoice.price_e2', 'cl_invoice.price_e2_vat'];
        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_invoice_items.item_label', ':cl_invoice_items.cl_pricelist.identification', ':cl_invoice_items.cl_pricelist.item_label', ':cl_invoice_items.description1', ':cl_invoice_items.description2',
            ':cl_invoice_items_back.item_label', ':cl_invoice_items_back.description1', ':cl_invoice_items_back.description2'];

        /*$testDate = new \Nette\Utils\DateTime;
        $testDate = $testDate->modify('-1 day');
        $this->conditionRows = array( array('due_date','<=',$testDate, 'color:red', 'lastcond'), array('price_payed','<=','price_e2_vat', 'color:green'));
         *
         */
        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);

        //                                ['storno', '==', 1, 'color:gray', 'notlastcond'],
        $this->conditionRows = [['storno', '==', 1, 'color:gray', 'lastcond'],
                                ['due_date', '<', $testDate, 'color:red', 'notlastcond'],
                                ['pay_date', '==', NULL, 'color:red', 'lastcond'],
                                ['due_date', '>=', $testDate, 'color:green', 'notlastcond'],
                                ['pay_date', '==', NULL, 'color:green', 'lastcond']];


        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;
        if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ? AND inv_type != ?', 1, 4)->fetch()) {
            $tmpInvoiceType = $tmpInvoiceType->id;
        } else {
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
        $tmpNSInvoice = 'invoice';
        $tmpNSCorrection = 'invoice_correction';
        $tmpNSAdvance = 'invoice_advance';
        $tmpNSInternal = 'invoice_internal';
        if ($tmpBranch) {
            $tmpNSInvoiceId = $tmpBranch->cl_number_series_id_invoice;
            $tmpNSCorrectionId = $tmpBranch->cl_number_series_id_correction;
            $tmpNSAdvanceId = $tmpBranch->cl_number_series_id_advance;
            $tmpCenterId = $tmpBranch->cl_center_id;
        } else {
            //20.12.2018 - headers and footers
            //if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $data->cl_number_series_id))->fetch()){
            //    $arrUpdate['header_txt'] = $hfData['header_txt'];
            //    $arrUpdate['footer_txt'] = $hfData['footer_txt'];
            //}

            $tmpNSInvoiceId = NULL;
            $tmpNSCorrectionId = NULL;
            $tmpNSAdvanceId = NULL;
            $tmpCenterId = NULL;
        }
        $tmpNSInternalId = NULL;
        if ($defBankAccount = $this->BankAccountsManager->findAll()->where('cl_currencies_id = ? AND default_account = 1', $this->settings->cl_currencies_id)->fetch()) {
            $defBankAccountId = $defBankAccount['id'];
        } else {
            $defBankAccountId = NULL;
        }


        $this->defValues = ['inv_date' => new \Nette\Utils\DateTime,
            'vat_date' => new \Nette\Utils\DateTime,
            'due_date' => $defDueDate,
            'cl_company_branch_id' => $tmpBranchId,
            'cl_center_id' => $tmpCenterId,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'currency_rate' => $this->settings->cl_currencies->fix_rate,
            'konst_symb' => $this->settings->konst_symb,
            'cl_invoice_types_id' => $tmpInvoiceType,
            'cl_payment_types_id' => $this->settings->cl_payment_types_id,
            'header_show' => $this->settings->header_show,
            'footer_show' => $this->settings->footer_show,
            'header_txt' => $this->settings->header_txt,
            'footer_txt' => $this->settings->footer_txt,
            'vat_active' => $this->settings->platce_dph,
            'price_e_type' => $this->settings->price_e_type,
            'cl_bank_accounts_id' => $defBankAccountId,
            'cl_users_id' => $this->user->getId()];
        //$this->numberSeries = 'commission';
        $this->numberSeries = ['use' => 'invoice', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number'];
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

        /*8 => array('group_start' => ''),
                                9 => array('url' => $this->link('new!', array('data'=> $tmpNSAdvance, 'defData' => array('cl_number_series_id' => $tmpNSAdvanceId))), 'rightsFor' => 'write', 'label' => 'Záloha', 'title' => 'nová zálohová faktura', 'class' => 'btn btn-primary'),
								10 => $this->getNumberSeriesArray('invoice_advance'),
								11 => array('group_end' => ''),
        */
        if (!is_null($tmpNSInvoiceId))
            $invoiceLink = $this->link('new!', ['data' => $tmpNSInvoice, 'defData' => json_encode(['cl_number_series_id' => $tmpNSInvoiceId])]);
        else
            $invoiceLink = $this->link('new!', ['data' => $tmpNSInvoice]);

        if (!is_null($tmpNSCorrectionId))
            $correctionLink = $this->link('new!', ['data' => $tmpNSCorrection, 'defData' => json_encode(['cl_number_series_id' => $tmpNSCorrectionId])]);
        else
            $correctionLink = $this->link('new!', ['data' => $tmpNSCorrection]);

        if (!is_null($tmpNSInternalId))
            $internalLink = $this->link('new!', ['data' => $tmpNSInternal, 'defData' => json_encode(['cl_number_series_id' => $tmpNSInternalId])]);
        else
            $internalLink = $this->link('new!', ['data' => $tmpNSInternal]);

        $this->toolbar = [0 => ['group_start' => ''],
            1 => ['url' => $invoiceLink, 'rightsFor' => 'write', 'label' => $this->translator->translate('Faktura'), 'title' => $this->translator->translate('nová_faktura'), 'class' => 'btn btn-primary'],
            2 => $this->getNumberSeriesArray('invoice'),
            3 => ['group_end' => ''],
            4 => ['group_start' => ''],
            5 => ['url' => $correctionLink, 'rightsFor' => 'write', 'label' => $this->translator->translate('Opravný_d.'), 'title' => $this->translator->translate('nový_opravný_daňový_doklad'), 'class' => 'btn btn-primary'],
            6 => $this->getNumberSeriesArray('invoice_correction'),
            7 => ['group_end' => ''],
            'export' => ['group' =>
                [
                    0 => ['url' => $this->link('report!', ['index' => 2]),
                        'rightsFor' => $this->translator->translate('report'),
                        'label' => $this->translator->translate('Pohoda_XML'),
                        'title' => $this->translator->translate('Faktury_vystavené_ve_zvoleném_období'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-file'],
                    1 => ['url' => $this->link('report!', ['index' => 3]),
                        'rightsFor' => $this->translator->translate('report'),
                        'label' => $this->translator->translate('Stereo_2022'),
                        'title' => $this->translator->translate('Faktury_vystavené_ve_zvoleném_období'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-file'],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Export'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('Export_viditelných_dat'), 'group_icon' => 'iconfa-file-export']
            ],
            8 => ['url' => $this->link('ImportData:', ['modal' => $this->modal, 'target' => $this->name]), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary'],
            9 => ['group' =>
                [0 => ['url' => $this->link('report!', ['index' => 1]),
                    'rightsFor' => $this->translator->translate('report'),
                    'label' => $this->translator->translate('Kniha_faktur_vydaných'),
                    'title' => $this->translator->translate('Faktury_vystavené_ve_zvoleném_období'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => 'iconfa-print'],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Tisk'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
            ]
        ];

        $this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/Invoice/ReportInvoiceBookSettings.latte',
                        'reportName' => 'Kniha faktur vydaných'],
                        2 => ['reportLatte' => __DIR__ . '/../templates/Invoice/ExportInvoiceBookSettings.latte',
                        'reportName' => 'Pohoda XML export'],
                        3 => ['reportLatte' => __DIR__ . '/../templates/Invoice/ExportInvoiceBookStereoSettings.latte',
                            'reportName' => 'Stereo 2022 export']
                    ];

        $this->rowFunctions = ['copy' => 'enabled'];

        //settings for CSV attachments
        $this->csv_h = ['columns' => 'inv_number,inv_date,vat_date,cl_invoice.due_date,var_symb,konst_symb,cl_invoice.spec_symb,inv_title,cl_partners_book.company,cl_partners_book_workers.worker_name,cl_currencies.currency_code,price_e2,price_e2_vat,price_correction,price_base0,price_base1,price_base2,price_base3,
                                            correction_base1,correction_base2,correction_base3,price_vat0,price_vat1,price_vat2,price_vat3,vat1,vat2,vat3,price_payed,base_payed0,base_payed1,base_payed2,base_payed3,vat_payed1,vat_payed2,vat_payed3,advance_payed,cl_invoice.header_txt,cl_invoice.footer_txt,pdp,export,storno'];
        $this->csv_i = ['columns' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_invoice_items.item_label,cl_pricelist.order_label,cl_invoice_items.quantity,cl_invoice_items.units,cl_storage.name AS storage_name,cl_invoice_items.price_e,cl_invoice_items.discount,cl_invoice_items.price_e2,cl_invoice_items.price_e2_vat',
            'datasource' => 'cl_invoice_items',
            'columns2' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_invoice_items_back.item_label,cl_pricelist.order_label,cl_invoice_items_back.quantity,cl_invoice_items_back.units,cl_storage.name AS storage_name,cl_invoice_items_back.price_e,cl_invoice_items_back.discount,cl_invoice_items_back.price_e2,cl_invoice_items_back.price_e2_vat',
            'datasource2' => 'cl_invoice_items_back'];

        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Invoice\card.latte'],
            'items' => ['active' => true, 'name' => $this->translator->translate('položky_prodej'), 'lattefile' => $this->getLattePath() . 'Invoice\items.latte'],
            'itemsback' => ['active' => false, 'name' => $this->translator->translate('položky_zpět'), 'lattefile' => $this->getLattePath() . 'Invoice\itemsback.latte'],
            //'disabledCondition' => array("cl_invoice_types.inv_type", '==', 2)),
            'header' => ['active' => false, 'name' => $this->translator->translate('záhlaví'), 'lattefile' => $this->getLattePath() . 'Invoice\header.latte'],
            'assignment' => ['active' => false, 'name' => $this->translator->translate('zápatí'), 'lattefile' => $this->getLattePath() . 'Invoice\footer.latte'],
            'memos' => ['active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'Invoice\description.latte'],
            'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'Invoice\files.latte']
        ];
        $this->previewLatteFile = '../../../' . $this->getLattePath() . 'Invoice\previewContent.latte';
        $this->enabledPreviewDoc = TRUE;
        /*$this->bscPages = array(
                    'header' => array('active' => false, 'name' => 'záhlaví', 'lattefile' => $this->getLattePath(). 'Invoice\header.latte')
                    );	*/

        if (!is_null($this->id)) {
            $tmpData = $this->DataManager->find($this->id);
            if ($tmpData->cl_invoice_types->inv_type == 2) { //correction document
                unset($this->bscPages['items']);
                $this->bscPages['itemsback']['active'] = true;
            }
            if ($this->settings->invoice_to_store == 0 && $tmpData->cl_invoice_types->inv_type == 1) {
                unset($this->bscPages['itemsback']);
            }
        }

        //17.08.2018 - settings for sending doc by email
        $this->docEmail = ['template' => __DIR__ . '/../templates/Invoice/emailInvoice.latte',
            'emailing_text' => 'invoice'];
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

        $this->bscSums = ['lattefile' => $this->getLattePath() . 'Invoice\sums.latte'];
        $this->bscToolbar = [
            11 => ['url' => 'makePayment!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Uhradit'), 'class' => 'btn btn-success', 'title' =>  $this->translator->translate('Provede_jednorázovou_úhradu'),
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            10 => ['url' => 'makeCorrection!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Opravný_doklad'),
                'title' => $this->translator->translate('Vytvořit_opravný_doklad_dobropis'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'],
                'showCondition' => [['column' => 'cl_invoice_types.inv_type', 'condition' => '==', 'value' => '1']],
                'icon' => 'glyphicon glyphicon-edit'],
            0 => ['url' => 'makeDeliveryNote!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Dodací_list'),
                'title' => $this->translator->translate('Vytvořit_dodací_list'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            1 => ['url' => 'changePartner!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Změna_odběratele'),
                'title' => $this->translator->translate('Umožní změnu odběratele u dokladu odeslaného do EET'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'cl_eet.eet_status', 'condition' => '==', 'value' => '3', 'next' => 'AND'],
                    ['column' => 'cl_payment_types.eet_send', 'condition' => '==', 'value' => '1']],
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            2 => ['url' => 'showEETModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('EET_zpráva'),
                'title' => $this->translator->translate('zobrazí vrácené varování nebo chyby z EET'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'cl_eet.eet_status', 'condition' => '<=', 'value' => '2', 'next' => 'AND'],
                    ['column' => 'cl_payment_types.eet_send', 'condition' => '==', 'value' => '1']],
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            3 => ['url' => 'sendEET!', 'rightsFor' => 'read', 'label' => $this->translator->translate('Odeslat_do_EET'),
                'title' => $this->translator->translate('odešle doklad do EET'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'cl_eet.eet_status', 'condition' => '<=', 'value' => '1', 'next' => 'AND'],
                    ['column' => 'cl_payment_types.eet_send', 'condition' => '==', 'value' => '1']],
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            4 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('časté_texty'), 'class' => 'btn btn-success showTextsUse',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'], 'icon' => 'glyphicon glyphicon-list'],
            5 => ['url' => 'paymentShow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('úhrady_a_zálohy'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            6 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
            7 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            8 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
            9 => $arrEmlTxt,
            'langDoc' => ['group' =>
                [
                    'CZ' => ['url' => 'setDocLang!', 'urlparams' => ['keyname' => 'lang', 'value' => 'CZ', 'key' => null],
                        'rightsFor' => 'print',
                        'label' => $this->translator->translate('česky'),
                        'title' => $this->translator->translate('Nastaví_jazyk_tisk_dokladu'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                    'EN' => ['url' => 'setDocLang!', 'urlparams' => ['keyname' => 'lang', 'value' => 'EN', 'key' => null],
                        'rightsFor' => 'print',
                        'label' => $this->translator->translate('anglicky'),
                        'title' => $this->translator->translate('Nastaví_jazyk_tisk_dokladu'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                    'DE' => ['url' => 'setDocLang!', 'urlparams' => ['keyname' => 'lang', 'value' => 'DE', 'key' => null],
                        'rightsFor' => 'print',
                        'label' => $this->translator->translate('německy'),
                        'title' => $this->translator->translate('Nastaví_jazyk_tisk_dokladu'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => '']
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Jazyk'),
                        'show_selected' => $this->docLang,
                        'group_class' => 'btn btn-success dropdown-toggle btn-sm pull-right',
                        'group_title' => $this->translator->translate('Jazyk'), 'group_icon' => '']
            ]
        ];
        $this->bscTitle = ['inv_number' => $this->translator->translate('Číslo_faktury'), 'cl_partners_book.company' => $this->translator->translate('Odběratel')];
        if (!$this->settings->eet_active && !$this->settings->eet_test) {
            unset($this->bscToolbar[1]);
            unset($this->bscToolbar[2]);
        }


        //17.08.2018 - settings for documents saving and emailing
        $this->docTemplate[1] = $this->ReportManager->getReport(__DIR__ . '/../templates/Invoice/invoicev2.latte');
        $this->docTemplate[2] = $this->ReportManager->getReport(__DIR__ . '/../templates/Invoice/correctionv1.latte');
        $this->docTemplate[3] = $this->ReportManager->getReport(__DIR__ . '/../templates/Invoice/advancev1.latte');

        $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        //$this->docTitle[1]	    = array("Faktura ", "inv_number");
        if ($this->settings['pdf_name_type'] == 0) {
            $this->docTitle[1] = ["", "cl_partners_book.company", "var_symb"];
            $this->docTitle[2] = ["", "cl_partners_book.company", "var_symb"];
            $this->docTitle[3] = ["", "cl_partners_book.company", "var_symb"];
        } elseif ($this->settings['pdf_name_type'] == 1) {
            $this->docTitle[1] = ["", "var_symb", "cl_partners_book.company"];
            $this->docTitle[2] = ["", "var_symb", "cl_partners_book.company"];
            $this->docTitle[3] = ["", "var_symb", "cl_partners_book.company"];
        }
        //$this->docTitle[2]	    = array("Opravný doklad ", "inv_number");
        //$this->docTitle[3]	    = array("Záloha ", "inv_number");


        $this->quickFilter = ['cl_invoice_types.name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
            'values' => $this->InvoiceTypesManager->findAll()->where('inv_type IN ?', [1, 2, 3])->fetchPairs('id', 'name')]
        ];

        /*predefined filters*/
        $pdCount = count($this->pdFilter);
        $pdCount2 = $pdCount;
        if ($this->settings->platce_dph == 1) {
            $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
                'filter' => 'storno = 0 AND price_payed < price_e2_vat AND cl_invoice.due_date <= NOW()',
                'sum' => ['(price_e2_vat -price_payed-advance_payed) * currency_rate' => 's DPH'],
                'rightsFor' => 'read',
                'label' => $this->translator->translate('po_splatnosti'),
                'title' => $this->translator->translate('Nezaplacené_faktury_po_splatnosti'),
                'data' => ['data-ajax="true"', 'data-history="true"'],
                'class' => 'ajax', 'icon' => 'iconfa-filter'];
            $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
                'filter' => 'storno = 0 AND price_payed < price_e2_vat',
                'sum' => ['(price_e2_vat -price_payed-advance_payed) * currency_rate' => 's DPH'],
                'rightsFor' => 'read',
                'label' => $this->translator->translate('nezaplacené'),
                'title' => $this->translator->translate('Všechny_doposud_nezaplacené_faktury'),
                'data' => ['data-ajax="true"', 'data-history="true"'],
                'class' => 'ajax', 'icon' => 'iconfa-filter'];
        }else{
            $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
                'filter' => 'storno = 0 AND price_payed < price_e2 AND cl_invoice.due_date <= NOW()',
                'sum' => ['(price_e2_vat -price_payed-advance_payed) * currency_rate' => 's DPH'],
                'rightsFor' => 'read',
                'label' => $this->translator->translate('po_splatnosti'),
                'title' => $this->translator->translate('Nezaplacené_faktury_po_splatnosti'),
                'data' => ['data-ajax="true"', 'data-history="true"'],
                'class' => 'ajax', 'icon' => 'iconfa-filter'];
            $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
                'filter' => 'storno = 0 AND price_payed < price_e2',
                'sum' => ['(price_e2_vat -price_payed-advance_payed) * currency_rate' => 's DPH'],
                'rightsFor' => 'read',
                'label' => $this->translator->translate('nezaplacené'),
                'title' => $this->translator->translate('Všechny_doposud_nezaplacené_faktury'),
                'data' => ['data-ajax="true"', 'data-history="true"'],
                'class' => 'ajax', 'icon' => 'iconfa-filter'];

        }

        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => 'storno = 1',
            'sum' => ['(price_e2_vat) * currency_rate' => 's DPH'],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Stornované'),
            'title' => $this->translator->translate('Všechny_stornované_faktury'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];

        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => '(price_e2_vat < 0 OR price_e2 < 0)',
            'sum' => ['(price_e2_vat) * currency_rate' => 's DPH'],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('záporné'),
            'title' => $this->translator->translate('Všechny_záporné_faktury'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];
        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => '(cl_delivery_note_id IS NOT NULL AND dn_is_origin = 1 AND (cl_invoice.price_e2_vat != cl_delivery_note.price_e2_vat OR cl_invoice.price_e2 != cl_delivery_note.price_e2))',
            'sum' => ['(cl_invoice.price_e2_vat) * cl_invoice.currency_rate' => 's DPH'],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('rozdíl_proti_dodacímu_listu'),
            'title' => $this->translator->translate('Všechny_faktury_s_rozdílem_proti_dodacímu_listu'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];

        $pdCount2 = $pdCount;
        if ($this->settings->platce_dph == 0) {
            $this->pdFilter[++$pdCount2]['sum'] = ['(price_e2 - price_payed - advance_payed) * currency_rate' => 'celkem'];
            $this->pdFilter[++$pdCount2]['sum'] = ['(price_e2 - price_payed - advance_payed) * currency_rate' => 'celkem'];
            $this->pdFilter[++$pdCount2]['sum'] = ['(price_e2) * currency_rate' => 'celkem'];
        }

        if ($this->isAllowed($this->presenter->name, 'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
        }
        $this->groupActions['payment'] = 'uhradit';
        $this->groupActions['reminder'] = 'odeslat upomínky';

    }


    public function groupActionsMethod($data, $checked, $totalR)
    {
        parent::groupActionsMethod($data, $checked, $totalR);
        if ($data['action'] == 'reminder') {
            $this->groupActionReminder($data, $checked, $totalR, 'ireminder');
        }
    }






    protected function createComponentPreviewContent()
    {
        return new \Controls\PreviewContent($this->previewLatteFile, $this->DataManager, $this->InvoiceItemsManager, $this->InvoiceItemsBackManager);
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$translatorStatus  = clone $this->translator;
        //$translatorPaymentTypes  = clone $this->translator;
        //
        //
        //$translatorForm  = clone $this->translator;
        //$translatorForm->setPrefix(array());
        //$translatorForm->setPrefix(['applicationModule.invoice']);
        //$form->setTranslator($translatorForm);
        $form->addHidden('id', NULL);
        $form->addText('inv_number', $this->translator->translate('Číslo_faktury'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_faktury'));
        $form->addText('od_number', $this->translator->translate('Číslo_objednávky'), 30, 30)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_objednávky'));
        $form->addText('delivery_number', $this->translator->translate('Dodací_list'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Dodací_list'));
        $form->addText('inv_date', $this->translator->translate('Vystavení'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vystavení'));
        $form->addText('vat_date', $this->translator->translate('DUZP'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_uskutečnění_zdanitelného_plnění'));
        $form->addText('due_date', $this->translator->translate('Splatnost'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Splatnost'));
        $form->addText('inv_title', $this->translator->translate('Popis'), 150, 150)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis'));
        $form->addText('var_symb', $this->translator->translate('Var_symbol'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Var._symbol'));
        $form->addText('konst_symb', $this->translator->translate('Konst_symbol'), 5, 5)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Konstatní_symbol'));
        $form->addText('spec_symb', $this->translator->translate('Spec_symbol'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Spec._symbol'));
        $form->addText('correction_inv_number', $this->translator->translate('Opravovaná_faktura'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('title', $this->translator->translate('Číslo_opravovaného_nebo_opravného_dokladu'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_opravovaného_nebo_opravného_dokladu'));

        $form->addText('cm_number', $this->translator->translate('Zakázka'), 20, 40)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_zakázky'));

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate('Středisko'), $arrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
            ->setPrompt($this->translator->translate('Zvolte středisko'));

        $form->addCheckbox('pdp', $this->translator->translate('Přenesená_daňová_povinnost'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('data-urlajax', $this->link('PDP!'))
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('export', $this->translator->translate('Exportní_fa'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('data-urlajax', $this->link('export!'))
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('storno', $this->translator->translate('Stornováno'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');


        //$translatorStatus->setPrefix(['customModule.status']);
        $arrStatus = $this->StatusManager->findAll()->where('status_use = ? OR status_use = ?', 'invoice', 'invoice_correction')->fetchPairs('id', 'status_name');
        //$arrStatus = $this->ArraysManager->arrSpaceToUnderscore($arrStatus);
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_faktury'))
            ->setRequired($this->translator->translate('Vyberte_prosím_stav_faktury'))
            ->setPrompt($this->translator->translate('Zvolte_stav_faktury'));

        //$translatorPaymentTypes->setPrefix(['customModule.payment_types']);
        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        //$arrPay = $this->ArraysManager->arrSpaceToUnderscore($arrPay);
        $form->addSelect('cl_payment_types_id', $this->translator->translate('Forma_úhrady'), $arrPay)
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm');

        //$test = $this->translator->translate('Forma úhrady');
        //$form->addSelect('cl_payment_types_id','Forma úhrady',$arrPay)
        //    ->setTranslator(NULL)
        //    ->setHtmlAttribute('class','form-control chzn-select input-sm');


        //28.12.2018 - have to set $tmpId for found right record it could be bscId or id
        if ($this->id == NULL) {
            $tmpId = $this->bscId;
        } else {
            $tmpId = $this->id;
        }
        if ($tmpInvoice = $this->DataManager->find($tmpId)) {
            if (isset($tmpInvoice['cl_partners_book_id'])) {
                $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
            } else {
                $tmpPartnersBookId = 0;
            }

        } else {
            $tmpPartnersBookId = 0;
        }
        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id', 'company');
        $form->addSelect('cl_partners_book_id', $this->translator->translate('Odběratel'), $arrPartners)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_odběratele'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($this->translator->translate('Zvolte_odběratele'));


        $arrBank = $this->BankAccountsManager->findAll()->
        select('cl_bank_accounts.id, CONCAT(cl_currencies.currency_code, " ", account_number) AS account_number')->
        order('cl_currencies.currency_code')->fetchPairs('id', 'account_number');

        $form->addSelect('cl_bank_accounts_id', $this->translator->translate('Účet'), $arrBank)
            ->setPrompt($this->translator->translate('Zvolte_účet'))
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-urlajaxaccount', $this->link('changeAccount!', ['type' => 'account']))
            ->setHtmlAttribute('data-type', 'account')
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_účet'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm');


        $arrBank2 = $this->PartnersAccountManager->findAll()->
                        where('cl_partners_book_id = ?', $tmpPartnersBookId)->
                        select('cl_partners_account.id, CONCAT(cl_currencies.currency_code, " ", account_code, "/", bank_code) AS account_number')->
                        order('cl_currencies.currency_code')->fetchPairs('id', 'account_number');

        $form->addSelect('cl_partners_account_id', $this->translator->translate('Účet_odběratele'), $arrBank2)
                    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_účet'))
                    ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
                    ->setPrompt($this->translator->translate('Zvolte_účet'));


        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id', 'currency_code');
        $form->addSelect('cl_currencies_id', $this->translator->translate('Měna'), $arrCurrencies)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', 'Zvolte')
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('data-urlajaxaccount', $this->link('changeAccount!', ['type' => 'currency']))
            ->setHtmlAttribute('data-type', 'currency')
            ->setPrompt($this->translator->translate('Zvolte_měnu'));

        $form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kurz'));
        //$arrUsers = $this->UserManager->getAll()->fetchPairs('id','name');
        //$arrUsers = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->fetchPairs('id','name');
        $arrUsers = [];
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        //dump($arrUsers);
        //die;

        $form->addSelect('cl_users_id', $this->translator->translate('Obchodník'), $arrUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_obchodníka'))
            ->setPrompt($this->translator->translate('Zvolte_obchodníka'));


        $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
        $form->addSelect('cl_partners_book_workers_id', $this->translator->translate('Kontakt'), $arrWorkers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kontaktní_osobu'))
            ->setPrompt($this->translator->translate('Zvolte kontaktní osobu'));


        $arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?', $tmpPartnersBookId)->fetchPairs('id', 'b_name');
        $form->addSelect('cl_partners_branch_id', $this->translator->translate('Pobočka'), $arrBranch)
            ->setTranslator(NULL)
            ->setPrompt($this->translator->translate('Zvolte_pobočku'))
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Pobočka'));

        //$form->addTextArea('footer_txt', 'Zápatí:', 100,3 )
        //	->setHtmlAttribute('placeholder','Text v zápatí faktury');
        $form->onValidate[] = [$this, 'FormValidate'];
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('store_out', $this->translator->translate('Vydat_ze_skladu'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBack'];
        //	    ->onClick[] = callback($this, 'stepSubmit');

        $form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
        return $form;
    }

    protected function createComponentHeaderEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        //$form->addCheckbox('header_show', 'Tiskount záhlaví');
        $form->addTextArea('header_txt', $this->translator->translate('Záhlaví:'), 100, 10)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Text_v_záhlaví_faktury'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepHeaderBack');
        $form->onSuccess[] = array($this, 'SubmitEditHeaderSubmitted');
        return $form;
    }

    protected function createComponentFooterEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        //$form->addCheckbox('footer_show', 'Tiskount zápatí');
        $form->addTextArea('footer_txt', $this->translator->translate('Zápatí:'), 100, 3)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Text_v_zápatí_faktury'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepFooterBack');

        $form->onSuccess[] = array($this, 'SubmitEditFooterSubmitted');
        return $form;
    }

    protected function createComponentReportInvoiceBook($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        // $form->setTranslator($this->translator);
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        if ($this->settings->platce_dph) {
            $lcText1 = $this->translator->translate('DUZP_od:');
            $lcText2 = $this->translator->translate('DUZP_do:');
        } else {
            $lcText1 = $this->translator->translate('Vystaveno_od:');
            $lcText2 = $this->translator->translate('Vystaveno_do:');
        }
        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $form->addRadioList('type', 'Dle datumu', [1 => 'vystavení', 2 => 'zdanitelného plnění'])
            ->setDefaultValue(1);

        $tmpArrPartners = $this->PartnersManager->findAll()->
        select('CONCAT(cl_partners_book.id,"-",IFNULL(:cl_partners_branch.id,"")) AS id, CONCAT(cl_partners_book.company," ",IFNULL(:cl_partners_branch.b_name,"")) AS company')->
        order('company')->fetchPairs('id', 'company');

        //bdump($tmpArrPartners);
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Odběratel:'), $tmpArrPartners)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_odběratele'));

        $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->order('status_name')->fetchPairs('id', 'status_name');
        $form->addMultiSelect('cl_status_id', $this->translator->translate('Stav_dokladu:'), $tmpArrStatus)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', 'Vyberte stav');

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Středisko:'), $tmpArrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_středisko'));

        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci:'), $tmpUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_payment_types_id', $this->translator->translate('Forma_úhrady:'), $arrPay)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_formu_úhrady_pro_tisk'));

        $tmpArrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id', 'currency_code');
        $form->addMultiSelect('cl_currencies_id', $this->translator->translate('Měna:'), $tmpArrCurrencies)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_měnu'));


        $form->addCheckbox('after_due_date', $this->translator->translate('Po_splatnosti'));
        $form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
        $form->addCheckbox('payed', $this->translator->translate('Zaplacené'));
        $form->addCheckbox('minus', $this->translator->translate('Mínusová_částka'));
        $form->addCheckbox('overpayed', $this->translator->translate('Přeplacené_faktury'));

        $form->addCheckbox('min_difference', $this->translator->translate('Rozdíl_větší_než_1'))
            ->setDefaultValue(TRUE);

        $form->addText('amount_from', $this->translator->translate('Částka_od'), 0, 8)
            ->setDefaultValue(0)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Částka_od'));

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xml', $this->translator->translate('uložit_do_XML'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xls', $this->translator->translate('XLS'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportInvoiceBook');
        $form->onSuccess[] = array($this, 'SubmitReportInvoiceBookSubmitted');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    protected function createComponentExportInvoiceBook($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        //$form->setTranslator($this->translator);
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        if ($this->settings->platce_dph) {
            $lcText1 = $this->translator->translate('DUZP_od');
            $lcText2 = $this->translator->translate('DUZP_do');
        } else {
            $lcText1 = $this->translator->translate('Vystaveno_od');
            $lcText2 = $this->translator->translate('Vystaveno_do');
        }
        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', 'Datum_začátek');

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->order('status_name')->fetchPairs('id', 'status_name');
        $form->addMultiSelect('cl_status_id', $this->translator->translate('Stav_dokladu'), $tmpArrStatus)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_stav'));

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Středisko'), $tmpArrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_středisko'));

        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_payment_types_id', 'Forma_úhrady', $arrPay)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_formu_úhrady_pro:tisk'));


        $form->addCheckbox('after_due_date', $this->translator->translate('Po_splatnosti'));
        $form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
        $form->addCheckbox('payed', $this->translator->translate('Zaplacené'));

        $form->addSubmit('save_xml', $this->translator->translate('Exportovat_faktury'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_cash_xml', $this->translator->translate('Exportovat_úhrady_faktur'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackExportBook');
        $form->onSuccess[] = array($this, 'SubmitExportBookSubmitted');

        return $form;
    }

    protected function createComponentEdit2($name)
    {
        $form = new Form($this, $name);
        //$translatorStatus  = clone $this->translator;
        $form->setTranslator($this->translator);
        //28.12.2018 - have to set $tmpId for found right record it could be bscId or id
        if ($this->id == NULL) {
            $tmpId = $this->bscId;
        } else {
            $tmpId = $this->id;
        }
        if ($tmpInvoice = $this->DataManager->find($tmpId)) {
            if (isset($tmpInvoice['cl_partners_book_id'])) {
                $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
            } else {
                $tmpPartnersBookId = 0;
            }

        } else {
            $tmpPartnersBookId = 0;
        }

        $arrPartners = $this->PartnersManager->findAll()->where('customer = 1')->fetchPairs('id', 'company');

        $form->addSelect('cl_partners_book_id', $this->translator->translate("Odběratel"), $arrPartners)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_odběratele'))
            ->setHtmlAttribute('data-urlajax', $this->link('updatePartner!'))
            ->setPrompt($this->translator->translate('Zvolte_odběratele'));

        //
        //->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate("Středisko"), $arrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
            ->setPrompt($this->translator->translate('Zvolte_středisko'));

        //$translatorStatus->setPrefix(['customModule.status']);
        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_faktury'))
            ->setRequired($this->translator->translate('Vyberte_prosím_stav_faktury'))
            ->setPrompt($this->translator->translate('Zvolte_stav_faktury'));


        $arrUsers = array();
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        //dump($arrUsers);
        //die;
        $form->addSelect('cl_users_id', $this->translator->translate("Obchodník"), $arrUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_obchodníka'))
            ->setPrompt($this->translator->translate('Zvolte obchodníka'));


        $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
        $form->addSelect('cl_partners_book_workers_id', $this->translator->translate("Kontakt"), $arrWorkers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kontaktní_osobu'))
            ->setPrompt($this->translator->translate('Zvolte_kontaktní_osobu'));


        $arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?', $tmpPartnersBookId)->fetchPairs('id', 'b_name');
        $form->addSelect('cl_partners_branch_id', $this->translator->translate("Pobočka"), $arrBranch)
            ->setTranslator(NULL)
            ->setPrompt($this->translator->translate('Zvolte pobočku'))
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Pobočka'));

        $form->onValidate[] = array($this, 'FormValidate2');
        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack2');
        $form->onSuccess[] = array($this, 'SubmitEditSubmitted2');
        return $form;
    }

    protected function createComponentInsertDiscount($name)
    {
        $form = new Form($this, $name);
        if ($this->id == NULL) {
            $tmpId = $this->bscId;
        } else {
            $tmpId = $this->id;
        }

        if ($tmpInvoice = $this->DataManager->find($tmpId)) {

        }
        $form->addHidden('id', $tmpId);
        $form->addText('discount_per', 'Sleva %')
            ->setHtmlAttribute('placeholder', 'Sleva v %');
        $form->addText('discount_abs', 'Sleva v ' . $tmpInvoice->cl_currencies['currency_code'])
            ->setHtmlAttribute('placeholder', 'Sleva absolutní');
        $form->addText('text', 'Text')
            ->setDefaultValue($this->settings['invoice_discount_txt'])
            ->setHtmlAttribute('Vkládaný text do faktury');

        $form->onValidate[] = [$this, 'FormValidate3'];
        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBack3'];
        $form->onSuccess[] = [$this, 'SubmitEditSubmitted3'];
        return $form;
    }

    public function handleMakeCorrection($id)
    {
        $this->numberSeries = ['use' => 'invoice_correction', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number'];
        $dataNS = $this->NumberSeriesManager->getNewNumberSeries($this->numberSeries);
        $retArr = $this->DataManager->createCorrection($id, $dataNS);
        if (self::hasError($retArr)) {
            $this->flashmessage($this->translator->translate($retArr['error']), 'error');
        } else {
            $this->flashmessage($this->translator->translate($retArr['success']), 'success');
            $this->redirect(':Application:Invoice:edit', ['id' => $retArr['id']]);
        }
        $this->redrawControl('content');
    }


    public function handleMakePayment($id)
    {
        $retArr = $this->DataManager->makePayment($id);
        $this->DataManager->cashUpdate($id);

        if (self::hasError($retArr)) {
            $this->flashmessage($this->translator->translate($retArr['error']), 'error');
        } else {
            $this->flashmessage($this->translator->translate($retArr['success']), 'success');
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
        $data['cl_payment_order_id'] = NULL;
        $data['cl_documents_id'] = NULL;
        $data['cl_store_docs_id'] = NULL;
        $data['cl_store_docs_id_in'] = NULL;
        $data['cl_commission_id'] = NULL;
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


    public function afterCopy($newLine, $oldLine)
    {
        //parent::afterCopy($newLine, $oldLine);
        $tmpItems = $this->InvoiceItemsManager->findAll()->where('cl_invoice_id = ?', $oldLine);
        $tmpNewPrev = FALSE;
        foreach ($tmpItems as $one) {
            $tmpOne = $one->toArray();
            $tmpOne['cl_invoice_id'] = $newLine;
            $tmpOne['cl_store_move_id'] = NULL;
            $tmpOne['cl_delivery_note_id'] = NULL;
            $tmpOne['cl_delivery_note_items_id'] = NULL;
            $tmpOne['cl_commission_id'] = NULL;
            if (!is_null($tmpOne['cl_parent_bond_id']) && $tmpNewPrev) {
                $tmpOne['cl_parent_bond_id'] = $tmpNewPrev['id'];
            }
            unset($tmpOne['id']);
            $tmpNewPrev = $this->InvoiceItemsManager->insert($tmpOne);

        }

        $tmpItems = $this->InvoiceItemsBackManager->findAll()->where('cl_invoice_id = ?', $oldLine);
        $tmpNewPrev = FALSE;
        foreach ($tmpItems as $one) {
            $tmpOne = $one->toArray();
            $tmpOne['cl_invoice_id'] = $newLine;
            $tmpOne['cl_store_move_id'] = NULL;
            $tmpOne['cl_delivery_note_id'] = NULL;
            $tmpOne['cl_delivery_note_items_back_id'] = NULL;
            if (!is_null($tmpOne['cl_parent_bond_id']) && $tmpNewPrev) {
                $tmpOne['cl_parent_bond_id'] = $tmpNewPrev['id'];
            }
            unset($tmpOne['id']);
            $tmpNewPrev = $this->InvoiceItemsBackManager->insert($tmpOne);
        }

        bdump($newLine);
        bdump($oldLine);
        $tmpItems = $this->InvoiceItemsManager->findAll()->where('cl_invoice_id = ?', $newLine);
        foreach ($tmpItems as $one) {
            bdump($one);
            $this->afterDataSaveListGrid($one['id'], 'invoicelistgrid');
        }

        $tmpItems = $this->InvoiceItemsBackManager->findAll()->where('cl_invoice_id = ?', $newLine);
        foreach ($tmpItems as $one) {
            $this->afterDataSaveListGrid($one['id'], 'invoiceBacklistgrid');
        }

        $this->DataManager->updateInvoiceSum($newLine);


        //$this->afterDataSaveListGrid($dataId, 'invoicelistgrid');
        //$this->afterDataSaveListGrid($dataId, 'invoiceBacklistgrid');

        /*  $tmpPayments = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $oldLine);
          foreach ($tmpPayments as $one) {
              $tmpOne = $one->toArray();
              $tmpOne['cl_invoice_id'] = $newLine;
              unset($tmpOne['id']);
              $this->InvoicePaymentsManager->insert($tmpOne);
          }*/


    }


    protected function createComponentExportInvoiceBookStereo($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        $now = new \Nette\Utils\DateTime;
        if ($this->settings->platce_dph) {
            $lcText1 = $this->translator->translate('DUZP_od');
            $lcText2 = $this->translator->translate('DUZP_do');
        } else {
            $lcText1 = $this->translator->translate('Vystaveno_od');
            $lcText2 = $this->translator->translate('Vystaveno_do');
        }
        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', 'Datum_začátek');

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->order('status_name')->fetchPairs('id', 'status_name');
        $form->addMultiSelect('cl_status_id', $this->translator->translate('Stav_dokladu'), $tmpArrStatus)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_stav'));

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Středisko'), $tmpArrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_středisko'));

        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_payment_types_id', 'Forma_úhrady', $arrPay)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_formu_úhrady'));


        $form->addCheckbox('after_due_date', $this->translator->translate('Po_splatnosti'));
        $form->addCheckbox('not_payed', $this->translator->translate('Nezaplacené'));
        $form->addCheckbox('payed', $this->translator->translate('Zaplacené'));

        $form->addText('ma_dati', $this->translator->translate('Účet_MD'), 15, 15)
            ->setDefaultValue('311001')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Účet_MD'));
        $form->addText('dal', $this->translator->translate('Účet_Dal'), 15, 15)
            ->setDefaultValue('600')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Účet_Dal'));
        $form->addText('item_label', $this->translator->translate('Popis'), 30, 30)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis'));
        $form->addText('short_desc', $this->translator->translate('Druh'), 30, 30)
            ->setDefaultValue('PDPH')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Druh'));
        $form->addText('txt_rada', $this->translator->translate('Číselná_řada_pro_Stereo'), 10, 10)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číselná_řada'));


        $form->addSubmit('save_xml', $this->translator->translate('Exportovat_faktury'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackExportBookStereo');
        $form->onSuccess[] = array($this, 'SubmitExportBookStereoSubmitted');

        return $form;
    }

    public function stepBackExportBookStereo()
    {
        $this->rptIndex = 2;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitExportBookStereoSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['save_xml']->isSubmittedBy() || $form['save_cash_xml']->isSubmittedBy()) {

            $data['cl_partners_branch'] = array();
            $tmpPodm = array();
            if ($data['after_due_date'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice.due_date < NOW() AND cl_invoice.price_payed < cl_invoice.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice.due_date < NOW() AND cl_invoice.price_payed < cl_invoice.price_e2';
            }

            if ($data['not_payed'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice.price_payed < cl_invoice.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice.price_payed < cl_invoice.price_e2';
            }

            if ($data['payed'] == 1) {
                if ($this->settings->platce_dph)
                    $tmpPodm = 'cl_invoice.price_payed >= cl_invoice.price_e2_vat';
                else
                    $tmpPodm = 'cl_invoice.price_payed >= cl_invoice.price_e2';
            }


            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                //$tmpDate = new \Nette\Utils\DateTime;
                //$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
                //date('Y-m-d H:i:s',strtotime($data['date_to']));
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));

            if ($this->settings->platce_dph) {
                $dataReport = $this->InvoiceManager->findAll()->
                where($tmpPodm)->
                where('cl_invoice.vat_date >= ? AND cl_invoice.vat_date <= ?', $data['date_from'], $data['date_to'])->
                order('cl_invoice.inv_number ASC, cl_invoice.vat_date ASC');
            } else {
                $dataReport = $this->InvoiceManager->findAll()->
                where($tmpPodm)->
                where('cl_invoice.inv_date >= ? AND cl_invoice.inv_date <= ?', $data['date_from'], $data['date_to'])->
                order('cl_invoice.inv_number ASC, cl_invoice.vat_date ASC');
            }

            if (count($data['cl_status_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_status_id' => $data['cl_status_id']));
            }


            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_invoice.cl_center_id' => $data['cl_center_id']));
            }

            if (count($data['cl_users_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_invoice.cl_users_id' => $data['cl_users_id']));
            }

            if (count($data['cl_payment_types_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_invoice.cl_payment_types_id' => $data['cl_payment_types_id']));
            }
            //$dataReport = $dataReport->select('cl_partners_book.company,cl_status.status_name,cl_currencies.currency_code,cl_invoice_types.name AS "druh faktury",cl_payment_types.name AS "druh platby",cl_invoice.*');


            if ($form['save_xml']->isSubmittedBy()) {
                $dateFrom = date('d-m-Y', strtotime($data['date_from']));
                $dateTo = date('d-m-Y', strtotime($data['date_to']));
                $filename = $this->settings['name'] . ' - faktury vydané ' . $dateFrom . '-' . $dateTo;
                $arrData = $this->DataManager->exportStereo($dataReport, $data);
                $this->sendResponse(new \CsvResponse\NCsvResponse($arrData, $filename . ".csv", true, ';', 'CP1250', NULL, [], FALSE,''));
            }


        }
    }



    public function getContent($arrData){
        if (!is_null($arrData['id'])){
            $result = $this->InvoiceItemsManager->findAll()->where('cl_invoice_id = ?', ($arrData['id']))->order('price_e2 DESC')->limit(1)->fetch();
            if ($result)
                $result = $result['item_label'];
            else
                $result = '';

        }else{
            $result = '';
        }
        return $result;
    }

    public function getTransportType($arrData){
        if (!is_null($arrData['id'])){
            $tmpResult = $this->DeliveryNoteManager->findAll()->where('cl_invoice_id = ?', $arrData['id'])->limit(1)->fetch();
            bdump($tmpResult);
            if ($tmpResult && !is_null($tmpResult['cl_transport_id'])  && !is_null($tmpResult->cl_transport['cl_transport_types_id'])){
                $result = $tmpResult->cl_transport->cl_transport_types['name'];
            } else {
                $result = '';
            }

        }else{
            $result = '';
        }
        return $result;
    }


}
