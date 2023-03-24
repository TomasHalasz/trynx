<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Application\Responses\FileResponse;

use Tracy\Debugger;

class SaleReviewPresenter extends \App\Presenters\BaseListPresenter
{


    const
        DEFAULT_STATE = 'Czech Republic';

    public $createDocShow = FALSE, $branchNumberSeriesCorrectionId, $pairedDocsShow = FALSE;

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
     * @var \App\Model\SaleManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    /**
     * @inject
     * @var \App\Model\CurrenciesManager
     */
    public $CurrenciesManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\SaleItemsManager
     */
    public $SaleItemsManager;

    /**
     * @inject
     * @var \App\Model\RatesVatManager
     */
    public $RatesVatManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;

    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;

    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;


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

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $PairedDocsManager;

    protected function createComponentPairedDocs()
    {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }


    protected function createComponentEmail()
    {
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\EmailControl(
            $this->translator, $this->EmailingManager, $this->mainTableName, $this->id);
    }


    protected function createComponentSumOnDocs()
    {
        //$this->translator->setPrefix(['applicationModule.SaleReview']);
        if ($data = $this->DataManager->findBy(array('id' => $this->id))->fetch()) {
            if ($data->cl_currencies) {
                $tmpCurrencies = $data->cl_currencies->currency_name;
            }

            if ($this->settings->platce_dph) {
                $tmpPriceNameBase = $this->translator->translate("Celkem_bez_DPH");
                $tmpPriceNameVat = $this->translator->translate("Celkem_s_DPH");
            } else {
                $tmpPriceNameBase = $this->translator->translate("Prodej");
                $tmpPriceNameVat = "";
            }


            if ($this->settings->platce_dph == 1) {
                $dataArr = array(
                    array('name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies),
                    array('name' => $this->translator->translate('DPH'), 'value' => $data->price_vat1 + $data->price_vat2 + $data->price_vat3, 'currency' => $tmpCurrencies),
                    array('name' => $this->translator->translate('Zaokrouhlení'), 'value' => $data->price_correction, 'currency' => $tmpCurrencies),
                    array('name' => $tmpPriceNameVat, 'value' => $data->price_e2_vat, 'currency' => $tmpCurrencies),
                );
            } else {
                $dataArr = array(
                    array('name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies),
                    array('name' => $this->translator->translate('Zaokrouhlení'), 'value' => $data->price_correction, 'currency' => $tmpCurrencies),
                );
            }
        } else {
            $dataArr = array();
        }

        return new SumOnDocsControl(
            $this->translator, $this->DataManager, $this->id, $this->settings, $dataArr);
    }

    protected function createComponentListgridItemsSelect()
    {

        /*if ($tmpParentData = $this->DataManager->find($this->id)) {
            if ($tmpParentData->price_e_type == 1) {
                $tmpProdej = "Prodej s DPH";
            } else {
                $tmpProdej = "Prodej bez DPH";
            }
        }*/
        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_commission_items__description1'])) {
            $userTmpAdapt['cl_commission_items__description1'] = $this->translator->translate("Poznámka_1");

        }
        if (!isset($userTmpAdapt['cl_commission_items__description2'])) {
            $userTmpAdapt['cl_commission_items__description2'] = $this->translator->translate("Poznámka_2");
        }
        if ($this->settings->platce_dph == 1) {
            $arrData = array('item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'roCondition' => 'TRUE'),
                'price_e' => array($this->translator->translate('Prodejní_cena'), 'format' => "number", 'size' => 12, 'roCondition' => 'TRUE'),
                'vat' => array($this->translator->translate('DPH_%'), 'format' => "number", 'size' => 10, 'roCondition' => 'TRUE'),
                'quantity' => array($this->translator->translate('Prodáno'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => 'TRUE'),
                'units' => array('', 'format' => 'text', 'size' => 7, 'roCondition' => 'TRUE'),
                'quantity_back' => array($this->translator->translate('Vrátit'), 'format' => 'number', 'size' => 10, 'data' => 'toBack', 'decplaces' => $this->settings->des_mj),
                'quantity_in' => array($this->translator->translate('Celkem_vráceno'), 'format' => 'number', 'size' => 10, 'roCondition' => 'TRUE', 'decplaces' => $this->settings->des_mj),
                'price_e2_vat_back' => array($this->translator->translate('Cena_s_DPH'), 'format' => "number", 'size' => 12),
                'note' => array($this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE));
        } else {
            $arrData = array('item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'roCondition' => 'TRUE'),
                'price_e' => array($this->translator->translate('Prodejní_cena'), 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena, 'roCondition' => 'TRUE'),
                'quantity' => array($this->translator->translate('Prodáno'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => 'TRUE'),
                'units' => array('', 'format' => 'text', 'size' => 7, 'roCondition' => 'TRUE'),
                'quantity_back' => array($this->translator->translate('Vrátit'), 'format' => 'number', 'size' => 10, 'class' => 'toBack', 'decplaces' => $this->settings->des_mj),
                'quantity_in' => array($this->translator->translate('Celkem_vráceno'), 'format' => 'number', 'size' => 10, 'roCondition' => 'TRUE', 'decplaces' => $this->settings->des_mj),
                'price_e2_back' => array($this->translator->translate('Cena'), 'format' => "number", 'size' => 12),
                'note' => array($this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE));
        }
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->SaleItemsManager,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
            $this->DataManager,
            NULL, //pricelist manager
            $this->PriceListPartnerManager,
            FALSE, //add emtpy row
            array(
                'activeTab' => 2
            ), //custom links,
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            TRUE, //nodelete
            FALSE, //enablesearch
            '', //txtSearchCondition
            array(), //toolbar
            TRUE, //forceEnable
            TRUE //paginatorOff
        );
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;
    }


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.SaleReview']);
        $this->mainTableName = 'cl_sale';
        if ($this->user->isLoggedIn() && $this->presenter->getAction() != "downloadLastPdf" && $this->presenter->getAction() != "setDownloaded") {


            $this->formName = $this->translator->translate("Prodejky");
            //$settings = $this->CompaniesManager->getTable()->fetch();
            if ($this->settings->platce_dph == 1) {
                $arrData = ['sale_number' => [$this->translator->translate('Číslo_dokladu'), 'format' => 'text'],
                    'sale_type' => [$this->translator->translate('Druh_dokladu'), 'size' => 20, 'arrValues' => $this->ArraysManager->getSaleTypes()],
                    'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                    'cl_company_branch.name' => [$this->translator->translate('Pobočka'), 'format' => 'text'],
                    'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                    'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'show_clink' => true],
                    'cl_payment_types.name' => $this->translator->translate('Forma_úhrady'),
                    'cl_eet.eet_status' => [$this->translator->translate('Stav_EET'), 'size' => 20, 'arrValues' => $this->ArraysManager->getEETStatusTypes(),
                        'format' => 'colorpoint', 'colours' => $this->ArraysManager->getEETColours()],
                    'vat_date' => [$this->translator->translate('DUZP'), 'format' => 'date'],
                    'inv_title' => $this->translator->translate('Poznámka'),
                    'discount' => [$this->translator->translate('Sleva_%'), 'format' => 'currency'],
                    'discount_abs' => [$this->translator->translate('Sleva_abs'), 'format' => 'currency'],
                    'price_e2' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                    'price_e2_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                    'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                    'currency_rate' => $this->translator->translate('Kurz'),
                    'cl_users.name' => $this->translator->translate('Obchodník'),
                    'downloaded' => ['PrintAuto', 'format' => 'boolean'],
                    'pay_date' => [$this->translator->translate('Uhrazeno'), 'format' => 'date'],
                    'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
            } else {
                $arrData = ['sale_number' => $this->translator->translate('Číslo_dokladu'),
                    'sale_type' => [$this->translator->translate('Druh_dokladu'), 'size' => 20, 'arrValues' => $this->ArraysManager->getSaleTypes()],
                    'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                    'cl_company_branch.name' => [$this->translator->translate('Pobočka'), 'format' => 'text'],
                    'inv_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                    'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'show_clink' => true],
                    'cl_payment_types.name' => $this->translator->translate('Forma_úhrady'),
                    'cl_eet.eet_status' => [$this->translator->translate('Stav_EET'), 'size' => 20, 'arrValues' => $this->ArraysManager->getEETStatusTypes(),
                        'format' => 'colorpoint', 'colours' => $this->ArraysManager->getEETColours()],
                    'pay_date' => [$this->translator->translate('Uhrazeno'), 'format' => 'date'],
                    'inv_title' => $this->translator->translate('Poznámka'),
                    'discount' => [$this->translator->translate('Sleva_%'), 'format' => 'currency'],
                    'discount_abs' => [$this->translator->translate('Sleva_abs'), 'format' => 'currency'],
                    'price_e2' => [$this->translator->translate('Cena_celkem'), 'format' => 'currency'],
                    'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                    'advance_payed' => [$this->translator->translate('Záloha'), 'format' => 'currency'],
                    'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                    'currency_rate' => $this->translator->translate('Kurz'),
                    'downloaded' => ['PrintAuto', 'format' => 'boolean'],
                    'cl_users.name' => $this->translator->translate('Obchodník'),
                    'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
            }

            if (!$this->settings->eet_active && !$this->settings->eet_test) {
                unset($arrData['cl_eet.eet_status']);
            }

            $this->dataColumns = $arrData;
            $this->filterColumns = ['sale_number' => '', 'cl_partners_book.company' => 'autocomplete', 'sale_type' => 'autocomplete',
                'cl_status.status_name' => 'autocomplete', 'cl_eet.eet_status' => 'autocomplete', 'cl_payment_types.name' => 'autocomplete',
                'inv_title' => '', 'cl_users.name' => 'autocomplete'];
            $this->DefSort = 'inv_date DESC';

            $defDueDate = new \Nette\Utils\DateTime;

            $this->defValues = ['inv_date' => new \Nette\Utils\DateTime,
                'vat_date' => new \Nette\Utils\DateTime,
                'cl_currencies_id' => $this->settings->cl_currencies_id,
                'currency_rate' => $this->settings->cl_currencies->fix_rate,
                'price_e_type' => $this->settings->price_e_type];
            //$this->numberSeries = 'commission';
            $this->numberSeries = ['use' => 'sale', 'table_key' => 'cl_number_series_id', 'table_number' => 'sale_number'];
            $this->readOnly = ['sale_number' => TRUE,
                'created' => TRUE,
                'create_by' => TRUE,
                'changed' => TRUE,
                'change_by' => TRUE];
            //	$this->toolbar = array(	1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));

            $this->toolbar = [1 => ['group' =>
                [0 => ['url' => $this->link('report!', ['index' => 1]),
                    'rightsFor' => 'report',
                    'label' => $this->translator->translate('Kniha_prodejek'),
                    'title' => $this->translator->translate('Prodejky_ve_zvoleném_období'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => 'iconfa-print'],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Tisk'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
            ]
            ];

            $this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/SaleReview/ReportSaleSettings.latte',
                'reportName' => 'Kniha prodejek']];

            //$this->showChildLink = 'PartnersEvent:default';
            //Condition for color highlit rows
            //$testDate = new \Nette\Utils\DateTime;
            //$testDate = $testDate->modify('-30 day');
            //$this->conditionRows = array( 'cdate','<=',$testDate);

            $this->rowFunctions = ['copy' => 'disabled'];


            $this->bscOff = FALSE;
            $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
            $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'SaleReview\card.latte'],
                'items' => ['active' => true, 'name' => $this->translator->translate('položky_prodeje'), 'lattefile' => $this->getLattePath() . 'SaleReview\items.latte']

            ];
            /*$this->bscPages = array(
                        'header' => array('active' => false, 'name' => 'záhlaví', 'lattefile' => $this->getLattePath(). 'Invoice\header.latte')
                        );	*/
            /*if ($this->settings->invoice_to_store == 0){
                unset($this->bscPages['itemsback']);
            }*/

//                        array('column' => 'cl_payment_types.payment_type', 'condition' => '==', 'value' => '1', 'next' => 'OR'),

            $this->bscSums = ['lattefile' => $this->getLattePath() . 'Sale\sums.latte'];
            $this->bscToolbar = [
                1 => ['url' => 'showEETModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('EET_zpráva'), 'title' => $this->translator->translate('zobrazí_vrácené_varování_nebo_chyby_z_EET'), 'class' => 'btn btn-success',
                    'showCondition' => [['column' => 'cl_eet.eet_status', 'condition' => '<=', 'value' => '2', 'next' => 'AND'],
                        ['column' => 'cl_payment_types.eet_send', 'condition' => '==', 'value' => '1']],
                    'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
                2 => ['url' => 'sendEET!', 'rightsFor' => 'read', 'label' => $this->translator->translate('Odeslat_do_EET'), 'title' => $this->translator->translate('odešle_doklad_do_EET'), 'class' => 'btn btn-success',
                    'showCondition' => [['column' => 'cl_eet.eet_status', 'condition' => '<=', 'value' => '1', 'next' => 'AND'],
                        ['column' => 'cl_payment_types.eet_send', 'condition' => '==', 'value' => '1']],
                    'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
                3 => ['url' => 'createCorrectionModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Opravný_doklad'), 'title' => $this->translator->translate('vytvoří_opravný_doklad_z_vybraných_položek_prodejky'), 'class' => 'btn btn-success',
                    'showCondition' => [['column' => 'sale_type', 'condition' => '==', 'value' => '0']],
                    'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
                4 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                    'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
                5 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                    'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
                6 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => 'PDF', 'class' => 'btn btn-success',
                    'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
                7 => ['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => $this->translator->translate('E-mail'), 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send'],

            ];

            if (!$this->settings->eet_active && !$this->settings->eet_test) {
                unset($this->bscToolbar[1]);
                unset($this->bscToolbar[2]);
            }

            //public function handleSavePDF($id, $latteIndex = NULL, $dataOther = array(), $noDownload = FALSE)

            $this->bscTitle = ['sale_number' => $this->translator->translate('Číslo_prodejky'), 'cl_partners_book.company' => $this->translator->translate('Odběratel')];
            $this->userFilterEnabled = TRUE;
            $this->userFilter = ['sale_number', 'price_e2_vat', 'cl_partners_book.company', 'cl_eet.eet_status'];

            $this->cxsEnabled = TRUE;
            $this->userCxsFilter = [':cl_sale_items.item_label', ':cl_sale_items.note', ':cl_sale_items.cl_pricelist.identification', ':cl_sale_items.cl_pricelist.item_label'];

            //17.08.2018 - settings for documents saving and emailing
            $this->docTemplate[1] = $this->ReportManager->getReport(__DIR__ . '/../templates/Sale/saledoc65.latte');
            $this->docTemplate[2] = $this->ReportManager->getReport(__DIR__ . '/../templates/SaleReview/correction.latte');

            $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $this->docTitle[1] = [$this->translator->translate("Prodejka"), "sale_number"];
            $this->docTitle[2] = [$this->translator->translate("Opravný_doklad_"), "sale_number"];
            //17.08.2018 - settings for sending doc by email
            $this->docEmail = ['template' => __DIR__ . '/../templates/SaleReview/emailSale.latte',
                'emailing_text' => 'sale'];

            /*$this->quickFilter = array('cl_invoice_types.name' => array('name' => 'Zvolte filtr zobrazení',
                'values' =>  $this->InvoiceTypesManager->findAll()->where('inv_type != ?',4)->fetchPairs('id','name'))
            );*/

            //13.05.2019 - select branch if there are defined
            //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
            //$tmpBranchId = $this->getUser()->cl_company_branch_id;
            $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
            $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
            if ($tmpBranch) {
                $tmpBranchDiscount = $tmpBranch->discount;
                $tmpBranchNumberSeries = $tmpBranch->cl_number_series_id_correction;
                $tmpBranchPartnersBook = $tmpBranch->cl_partners_book_id;
                $tmpBranchPartnersStorage = $tmpBranch->cl_storage_id;
            } else {
                $tmpBranchDiscount = 0;
                $tmpBranchNumberSeries = NULL;
                $tmpBranchPartnersBook = $this->settings->cl_partners_book_id_sale;
                $tmpBranchPartnersStorage = $this->settings->cl_storage_id_sale;
            }
            $this->branchNumberSeriesCorrectionId = $tmpBranchNumberSeries;

        }
        if ($this->isAllowed($this->presenter->name, 'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
        }

    }


    /*public function createComponentSalelistgrid()
     {
        $tmpParentData = $this->DataManager->find($this->id);
        if ( $tmpParentData->price_e_type == 1)
        {
            $tmpProdej = "Prodej s DPH";
        }else{
            $tmpProdej = "Prodej bez DPH";
        }
        if ($this->settings->platce_dph == 1)
                            $arrData = array('item_label' => array('Popis','format' => 'text','size' => 30),
                          'quantity' => array('Množství','format' => 'number','size' => 10,'decplaces' => $this->settings->des_mj),
                          'units' => array('','format' => 'text','size' => 5),
                          'price_e' => array($tmpProdej,'format' => "number",'size' => 10, 'decplaces' => $this->settings->des_cena),
                          'price_e_type' => array('Typ prodejni ceny','format' => "hidden"),
                          'discount' => array('Sleva %','format' => "number",'size' => 10),
                          'price_e2' => array('Celkem bez DPH','format' => "number",'size' => 12),
                          'vat' => array('DPH %','format' => "number",'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates','rates'),'size' => 7),
                          'price_e2_vat' => array('Celkem s DPH','format' => "number",'size' => 12));
        else
                            $arrData = array('item_label' => array('Popis','format' => 'text','size' => 30),
                          'quantity' => array('Množství','format' => 'number','size' => 10,'decplaces' => $this->settings->des_mj),
                          'units' => array('','format' => 'text','size' => 5),
                          'price_e' => array('Prodej','format' => "number",'size' => 10,'decplaces' => $this->settings->des_cena),
                          'price_e_type' => array('Typ prodejni ceny','format' => "hidden"),
                          'discount' => array('Sleva %','format' => "number",'size' => 10),
                          'price_e2' => array('Celkem','format' => "number",'size' => 12));




        return new ListgridControl(
                        $this->SaleItemsManager,
                        $arrData,
                        array(),
                        $this->id,
                        array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
                        $this->DataManager,
                        $this->PriceListManager,
                        $this->PriceListPartnerManager,
                        TRUE,
                        array(
                         ) //custom links
                        );

     }*/


    public function forceRO($data)
    {

        $ret = parent::forceRO($data);
        if (!is_null($data['cl_eet_id']) && $data->cl_eet->eet_status == 3) {
            $ret = $ret || TRUE;
        } else {
            $ret = $ret || FALSE;
        }
        //bdump($ret, 'forceRO');
        return $ret;
    }

    public function beforeDeleteBaseList($id)
    {
        $data = $this->DataManager->find($id);
        if (!is_null($data['cl_eet_id']) && $data->cl_eet->eet_status == 3) {
            $ret = FALSE;
        } else {
            $ret = TRUE;
        }

        return $ret;
    }

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);
        //dump($this->conditionRows);
        //die;

    }


    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        return new Controls\FilesControl(
            $this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_partners_book_main_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }


    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);

    }

    public function createComponentSalelistgrid()
    {
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
            $arrData = array('item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30,
                'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'),
                'cl_pricelist_id' => array($this->translator->translate('Položka_ceníku'), 'format' => 'hidden'),
                'cl_storage.name' => array($this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                    'values' => $arrStore,
                    'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($defData1["cl_pricelist_id"]);',
                    'size' => 10, 'roCondition' => 'TRUE'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE),
                'price_e' => array($tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena),
                'price_e_type' => array($this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"),
                'discount' => array($this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 10),
                'price_e2' => array($this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 12),
                'vat' => array('DPH %', 'format' => "chzn-select", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 7),
                'price_e2_vat' => array($this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 12),
                'note' => array($this->translator->translate('poznámka'), 'format' => "text", 'size' => 50, 'newline' => TRUE));
        } else {
            $arrData = array('item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30),
                'cl_storage.name' => array($this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                    'values' => $arrStore,
                    'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($defData1["cl_pricelist_id"]);',
                    'size' => 10, 'roCondition' => '$defData["changed"] != NULL || ($this["editLine"]["cl_pricelist_id"]->value == 0)'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE),
                'price_e' => array($this->translator->translate('Prodej'), 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena),
                'price_e_type' => array($this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"),
                'discount' => array($this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 10),
                'price_e2' => array($this->translator->translate('Celkem'), 'format' => "number", 'size' => 12),
                'cl_pricelist_id' => array($this->translator->translate('Položka_ceníku'), 'format' => 'hidden'),
                'note' => array($this->translator->translate('poznámka'), 'format' => "text", 'size' => 50, 'newline' => TRUE));

        }
        if ($this->settings->invoice_to_store == 0) {
            unset($arrData['cl_storage.name']);
            unset($arrData['price_s']);
        }


        $control = new Controls\ListgridControl(
            $this->translator,
            $this->SaleItemsManager,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
            $this->DataManager,
            FALSE,
            FALSE,
            FALSE,
            array(), //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE, //pricelistbottom
            FALSE //readonlymode
        );
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }

    public function UpdateSum()
    {
        $this->DataManager->updateSaleSum($this->id);

        $this->redrawControl('baselistArea');
        $this->redrawControl('bscArea');
        $this->redrawControl('bsc-child');

        $this['salelistgrid']->redrawControl('editLines');
        //$this['sumOnDocs']->redrawControl();

        $this->redrawControl('recapitulation');
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addText('sale_number', $this->translator->translate('Číslo_prodejky'), 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_prodejky'));
        $form->addText('inv_title', $this->translator->translate('Poznámka'), 20, 50)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Poznámka'));
        $form->addText('inv_date', $this->translator->translate('Vystavení'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vystavení'));
        $form->addText('vat_date', $this->translator->translate('DUZP'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_uskutečnění_zdanitelného_plnění'));

        $form->addText('discount', $this->translator->translate('Sleva_%'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm ')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Sleva_%'));

        $form->addText('discount_abs', $this->translator->translate('Sleva_abs'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm ')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Sleva_abs'));

        $form->addSelect('sale_type', $this->translator->translate("Druh_dokladu"), $this->ArraysManager->getSaleTypes())
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_druh_dokladu'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('readonly', true)
            ->setRequired($this->translator->translate('Druh_dokladu_musí_být_vybrán'))
            ->setPrompt($this->translator->translate('Zvolte_druh_dokladu'));


        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'sale')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', "Stav:", $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_prodejky'))
            ->setRequired($this->translator->translate('Vyberte_prosím_stav_prodejky'))
            ->setPrompt($this->translator->translate('Zvolte_stav_faktury'));

        if ($tmpInvoice = $this->DataManager->find($this->id)) {
            if (isset($tmpInvoice['cl_partners_book_id'])) {
                $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
            } else {
                $tmpPartnersBookId = 0;
            }

        } else {
            $tmpPartnersBookId = 0;
        }

        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id', 'company');

        $form->addSelect('cl_partners_book_id', $this->translator->translate("Odběratel"), $arrPartners)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_odběratele'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setPrompt($this->translator->translate('Zvolte_odběratele'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_payment_types_id', $this->translator->translate('Forma_úhrady'), $arrPay)
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm');

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setPrompt($this->translator->translate('Zvolte_měnu'));

        $form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kurz'));
        //$arrUsers = $this->UserManager->getAll()->fetchPairs('id','name');

        //$arrUsers = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->fetchPairs('id','name');
        $arrUsers = array();
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        $form->addSelect('cl_users_id', $this->translator->translate("Obchodník"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_obchodníka'))
            ->setPrompt($this->translator->translate('Zvolte_obchodníka'));

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
        //	    ->onClick[] = callback($this, 'stepSubmit');

        $form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        return $form;
    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() || $form['send_fin']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy() || $form['store_out']->isSubmittedBy()) {

            $data['inv_date'] = date('Y-m-d H:i:s', strtotime($data['inv_date']));
            $data['vat_date'] = date('Y-m-d H:i:s', strtotime($data['vat_date']));

            //$myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {
                    $this->DataManager->update($data, TRUE);
                    $this->UpdateSum();

                    if ($tmpData = $this->DataManager->find($data['id'])) {
                        //unvalidate document for downloading
                        $tmpDocuments = new \Nette\Utils\ArrayHash();
                        $tmpDocuments['id'] = $tmpData->cl_documents_id;
                        $tmpDocuments['valid'] = 0;
                        $newDocuments = $this->DocumentsManager->update($tmpDocuments);
                    }

                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                } else {
                    //$row=$this->DataManager->insert($data);
                    //$this->newId = $row->id;
                    //$this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            } else {
                //$this->flashMessage('Změny nebyly uloženy.', 'success');
            }

            if ($form['send_fin']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy()) {
                $data = $this->DataManager->find($data['id']);
                $tmpTemplateFile = __DIR__ . '/../templates/Sale/saledoc.latte';
                $tmpTitle = $this->translator->translate("Prodejka_") . $data->sale_number;
                $tmpAuthor = $this->user->getIdentity()->name . $this->translator->translate("_z_") . $this->settings->name;
                if ($this->settings->platce_dph) {
                    $tmpPrice = $data->price_e2_vat;
                } else {
                    $tmpPrice = $data->price_e2;
                }
                if (!empty($data->inv_title)) {
                    $tmpMsg = $data->inv_title;
                } else {
                    if ($tmpContent = $this->SaleItemsManager->findBy(array('cl_sale_id' => $data->id))->order('price_e2_vat DESC')->limit(1)->fetch()) {
                        $tmpMsg = $tmpContent->item_label;
                    } else {
                        $tmpMsg = '';
                    }
                }
                $tmpDppd = $data->vat_date;

                $arrData = array('settings' => $this->settings,
                    'RatesVatValid' => $this->RatesVatManager->findAllValid($this->DataManager->find($data['id'])->vat_date),
                    'arrInvoiceVat' => $this->getArrInvoiceVat(),
                    'arrInvoicePay' => $this->getArrInvoicePay()
                );

                $saleTemplate = $this->createMyTemplate($data, $tmpTemplateFile, $tmpTitle, $tmpAuthor, $arrData);
                $data = $this->DataManager->find($data['id']);
            }

            if ($form['send_fin']->isSubmittedBy()) {


            } elseif ($form['save_pdf']->isSubmittedBy()) {


            } else {
                //$this->redirect('default');
                $this->redrawControl('flash');
                $this->redrawControl('formedit');
                $this->redrawControl('timestamp');
                $this->redrawControl('items');
                $this->redrawControl('content');
                //$this->redirect('default');
            }

        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redirect('default');

            //$this->redirect('default');
        }


    }


    protected function getArrInvoiceVat()
    {
        $tmpData = $this->DataManager->findAll()->where('id = ?', $this->id)->fetch();
        $arrRatesVatValid = $this->RatesVatManager->findAllValid($tmpData->vat_date);
        $arrInvoiceVat = new \Nette\Utils\ArrayHash;
        foreach ($arrRatesVatValid as $key => $one) {
            if ($tmpData->vat1 == $one['rates']) {
                $baseValue = $tmpData->price_base1;
                $vatValue = $tmpData->price_vat1;
                $basePayedValue = $tmpData->base_payed1;
                $vatPayedValue = $tmpData->vat_payed1;
            } elseif ($tmpData->vat2 == $one['rates']) {
                $baseValue = $tmpData->price_base2;
                $vatValue = $tmpData->price_vat2;
                $basePayedValue = $tmpData->base_payed2;
                $vatPayedValue = $tmpData->vat_payed2;
            } elseif ($tmpData->vat3 == $one['rates']) {
                $baseValue = $tmpData->price_base3;
                $vatValue = $tmpData->price_vat3;
                $basePayedValue = $tmpData->base_payed3;
                $vatPayedValue = $tmpData->vat_payed3;
            } else {
                $baseValue = 0;
                $vatValue = 0;
                $basePayedValue = 0;
                $vatPayedValue = 0;
            }

            $arrInvoiceVat[$one['rates']] = array('base' => $baseValue,
                'vat' => $vatValue,
                'payed' => $basePayedValue,
                'vatpayed' => $vatPayedValue);
        }
        return ($arrInvoiceVat);

    }


    protected function getArrInvoicePay()
    {
        /*$tmpData = $this->DataManager->find($this->id);
        $arrInvoicePay = new \Nette\Utils\ArrayHash;
        foreach($tmpData->related('cl_invoice_payments')->where('pay_type = 0') as $key => $one)
        {
                    $arrInvoicePay[$key] = array('pay_date' => $one->pay_date,
                                                 'pay_price' => $one->pay_price,
                                                 'pay_doc' => $one->pay_doc,
                                                 'currency_name' => $one->cl_currencies->currency_name);
        }
    return ($arrInvoicePay);
         *
         */
        return array();
    }


    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }


    protected function createComponentReportSaleBook($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
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
            ->setAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportSaleBook');
        $form->onSuccess[] = array($this, 'SubmitReportSaleBookSubmitted');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportSaleBook()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportSaleBookSubmitted(Form $form)
    {
        $data = $form->values;
        //dump(count($data['cl_partners_book']));
        //die;
        if ($form['save_pdf']->isSubmittedBy() || $form['save_csv']->isSubmittedBy()) {
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
                $dataReport = $this->DataManager->findAll()->
                where('vat_date >= ? AND vat_date <= ?', $data['date_from'], $data['date_to'])->
                order('sale_number ASC, vat_date ASC');
            } else {
                $dataReport = $this->DataManager->findAll()->
                where('inv_date >= ? AND inv_date <= ?', $data['date_from'], $data['date_to'])->
                order('sale_number ASC, vat_date ASC');
            }

            if ($form['save_pdf']->isSubmittedBy()) {

                $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
                $tmpTitle = $this->translator->translate('Kniha_prodejek_za_období');
                $template = $this->createTemplate()->setFile(__DIR__ . '/../templates/SaleReview/ReportSaleBook.latte');
                $template->data = $dataReport;
                $template->dataSettings = $data;

                //dump(implode(',',$data['cl_partners_book']));
                //$tmpArr = $this->RatesVatManager->findAllValid($data['date_from'])->fetchPairs('rates', 'rates');
                //$template->arrVat = array();
                //foreach ($tmpArr as $one)
                //{
                //$template->arrVat[] = $one;
                //}

                $template->arrVat = array();
                $tmpArr2 = array();
                $tmpArr = $dataReport;
                foreach ($tmpArr as $one) {
                    if ($one->price_vat1 != 0)
                        $tmpArr2[$one->vat1] = $one->vat1;
                }

                $tmpArr = $dataReport;
                foreach ($tmpArr as $one) {
                    if ($one->price_vat2 != 0)
                        $tmpArr2[$one->vat2] = $one->vat2;
                }

                $tmpArr = $dataReport;
                foreach ($tmpArr as $one) {
                    if ($one->price_vat3 != 0)
                        $tmpArr2[$one->vat3] = $one->vat3;
                }

                foreach ($tmpArr2 as $one) {
                    $template->arrVat[] = $one;
                }
                rsort($template->arrVat);

                $tmpPayments = $this->PaymentTypesManager->findAll()->fetchPairs('id', 'name');
                $arrPayments = array();
                foreach ($tmpPayments as $key => $one) {
                    $arrPayments[$key] = array('name' => $one, 'sum' => 0);
                }
                $template->arrPayments = $arrPayments;

                //dump($template->arrVat);
                //die;
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
            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = "Kniha prodejek";
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy_Zadaným_podmínkám_nevyhověl_žádný_záznam'), 'danger');
                    $this->redirect('default');
                }
            }

        }
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


    public function handleMakeRecalc()
    {

    }

    public function handleSavePDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);
        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, $noPreview);
    }

    public function handleDownloadPDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);
        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, FALSE, TRUE);
    }


    /**Send to output pdf with last notprinted sale document
     * @param null $companyBranchKey
     * @return mixed
     */
    public function actionDownloadLastPDF($companyBranchKey = NULL)
    {
        $this->template->setFile('');
        //dump($companyBranchKey);
        if (!is_null($companyBranchKey) && $companyBranchKey != "") {
            $tmpBranch = $this->CompanyBranchManager->findAllTotal()->where('branch_key = ?', $companyBranchKey)->fetch();
            if ($tmpBranch) {
                $companyBranchId = $tmpBranch->id;
            } else {
                $companyBranchId = NULL;
            }
            if (!is_null($companyBranchId)) {
                $tmpSale = $this->DataManager->findAllTotal()->where('cl_company_branch_id = ? AND downloaded = 0 AND sale_number IS NOT NULL', $companyBranchId)->
                order('id DESC')->limit(1)->fetch();
            } else {
                $tmpSale = FALSE;
            }
            if ($tmpSale) {
                $this->id = $tmpSale->id;
                $colName = $this->mainTableName . '_id';
                $type = 1; //1 for PDF
                if ($tmpFile = $this->FilesManager->findAllTotal()->where(array($colName => $this->id, 'document_file' => $type))->fetch()) {
                    $dataFolder = $this->CompaniesManager->getDataFolder($tmpSale->cl_company_id);
                    $subFolder = $this->ArraysManager->getSubFolder($tmpFile);
                    $fileName = $dataFolder . '/' . $subFolder . '/' . $tmpFile->file_name;
                    //$destFile = $dataFolder . '/' . $subFolder . '/' . $fileName;
                    // bdump($fileName, 'filename');
                    if (file($fileName)) {
                        //$pdfData = file_get_contents($fileName);

                        $httpResponse = $this->context->getByType('Nette\Http\Response');
                        $httpResponse->setHeader('Pragma', "public");
                        $httpResponse->setHeader('Expires', 0);
                        $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
                        $httpResponse->setHeader('Content-Transfer-Encoding', "binary");
                        $httpResponse->setHeader('Content-Description', "File Transfer");
                        $httpResponse->setHeader('Content-Length', filesize($fileName));
                        $this->sendResponse(new FileResponse($fileName, $tmpFile->file_name, 'application/octet-stream'));
                        //echo($qrCode);
                        //$this->terminate();

                    } else {
                    }
                }

            } else {

            }
        } else {

        }

        $this->terminate();
    }

    /**Set last notprinted sale document as downloaded
     * @param null $companyBranchKey
     * @return mixed
     */
    public function actionSetDownloaded($companyBranchKey = NULL)
    {
        if (!is_null($companyBranchKey) && $companyBranchKey != "") {
            $tmpBranch = $this->CompanyBranchManager->findAllTotal()->where('branch_key = ?', $companyBranchKey)->fetch();
            if ($tmpBranch) {
                $companyBranchId = $tmpBranch->id;
            } else {
                $companyBranchId = NULL;
            }
            if (!is_null($companyBranchId)) {
                $tmpSale = $this->DataManager->findAllTotal()->where('cl_company_branch_id = ? AND downloaded = 0 AND sale_number IS NOT NULL', $companyBranchId)->
                order('id DESC')->limit(1)->fetch();
            } else {
                $tmpSale = FALSE;
            }
            if ($tmpSale) {
                $tmpSale->update(array('downloaded' => 1));
            } else {

            }
        } else {

        }

        $this->terminate();
    }


    public function handleSendDoc($id, $latteIndex = NULL, $arrData = [], $recepients = [], $emailingTextIndex = NULL)
    {
        $tmpData = $this->preparePDFData($id);
        parent::handleSendDoc($id, $tmpData['latteIndex'], $tmpData);
    }


    public function preparePDFData($id)
    {
        $data = $this->DataManager->findAllTotal()->where('id = ?', $id)->fetch();
        if ($data->sale_type == 0) //prodejka
        {
            //$tmpTemplateFile =  __DIR__ . '/../templates/Sale/saledoc.latte';
            $latteIndex = 1;
        } elseif ($data->sale_type == 1) //opravny danovy doklad
        {
            //$tmpTemplateFile =  __DIR__ . '/../templates/Sale/correction.latte';
            $latteIndex = 2;
        }

        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        $dataBranch = FALSE;
        if (!is_null($data->cl_company_branch_id)) {
            $tmpBranch = $this->CompanyBranchManager->findallTotal()->where('id = ?', $data->cl_company_branch_id)->fetch();
            if ($tmpBranch) {
                $dataBranch = $tmpBranch;
            }
        }


        $arrData = array('settings' => $this->settings,
            'branch' => $dataBranch,
            'RatesVatValid' => $this->RatesVatManager->findAllValid($this->DataManager->find($id)->vat_date),
            'arrInvoiceVat' => $this->getArrInvoiceVat(),
            'latteIndex' => $latteIndex);

        return $arrData;
    }

    public function handleCreateCorrectionModalWindow()
    {
        //bdump('ted');
        //18.05.2019 - prepare price_e2_back and price_e2_vat_back
        //also set quantity_back to maximum
        $tmpItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $this->id);
        foreach ($tmpItems as $key => $one) {
            if ($this->settings->platce_dph == 1) {
                $price_e2 = $one->price_e2_vat / (1 + ($one->vat / 100));
                if ($one->quantity > 0)
                    $one->update(array('price_e2_back' => $price_e2 / $one->quantity, 'price_e2_vat_back' => $one->price_e2_vat / $one->quantity));
                else
                    $one->update(array('price_e2_back' => $price_e2, 'price_e2_vat_back' => $one->price_e2_vat));
            } else {
                if ($one->quantity > 0)
                    $one->update(array('price_e2_back' => $one->price_e2 / $one->quantity));
                else
                    $one->update(array('price_e2_back' => $one->price_e2));
            }
            $tmpSum = $this->SaleItemsManager->findAll()->where('cl_sale_items_id = ?', $key)->sum('-quantity');
            //$one->update(array('quantity_back' => $one->quantity - $tmpSum));
            //02.08.2019 - default is nothing to back
            $one->update(array('quantity_back' => 0));
            $one->update(array('quantity_in' => $tmpSum));

        }
        $this->createDocShow = TRUE;
        $this->showModal('createCorrectionModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleSelectAll()
    {
        $tmpItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $this->id);
        foreach ($tmpItems as $key => $one) {
            $tmpSum = $this->SaleItemsManager->findAll()->where('cl_sale_items_id = ?', $key)->sum('-quantity');
            $one->update(array('quantity_back' => $one->quantity - $tmpSum));
            $one->update(array('quantity_in' => $tmpSum));
        }
        $this->redrawControl('itemsForInvoice');
    }

    public function handleUnselectAll()
    {
        $tmpItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $this->id);
        foreach ($tmpItems as $key => $one) {
            $tmpSum = $this->SaleItemsManager->findAll()->where('cl_sale_items_id = ?', $key)->sum('-quantity');
            $one->update(array('quantity_back' => 0));
            $one->update(array('quantity_in' => $tmpSum));
        }
        $this->redrawControl('itemsForInvoice');
    }

    public function handleShowEETModalWindow()
    {
        $this->createDocShow = TRUE;
        $this->showModal('showEETModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }


    //aditional processing data after save in listgrid
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        //bdump($name);
        if ($name == 'listgridItemsSelect') {
            //after saving in modalwindow for create correction
            $tmpData = $this->SaleItemsManager->find($dataId);
            if ($tmpData) {
                $tmpData->update(array('price_e2_back' => $tmpData->price_e2_vat_back / (1 + ($tmpData->vat / 100))));
            }
            //}else {
            $tmpData = $this->SaleItemsManager->find($dataId);
            //bdump($tmpData);
            if ($tmpData && ($tmpData->quantity_in + $tmpData->quantity_back) > $tmpData->quantity) {
                $tmpData->update(array('quantity_back' => ($tmpData->quantity - $tmpData->quantity_in)));
            } elseif ($tmpData->quantity_back < 0) {
                $tmpData->update(array('quantity_back' => ($tmpData->quantity - $tmpData->quantity_in)));
            }
        }

        $this['listgridItemsSelect']->redrawControl('editLines');
        $this->redrawControl('itemsForInvoice');
    }


    public function handleCreateCorrection()
    {
        $tmpDataMain = $this->DataManager->find($this->id);
        $tmpItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ? AND quantity_back > 0', $this->id);
        $newCorrection = $this->makeCorrection($tmpDataMain);

        if ($newCorrection->sale_number === NULL) {
            //1. generate document number
            $this->getNumberSeries();
            $newCorrection->update($this->defValues);
        }
        //now items
        foreach ($tmpItems as $key => $one) {
            $tmpArr = $one->toArray();
            unset($tmpArr['id']);
            $tmpArr['cl_sale_id'] = $newCorrection->id;
            $tmpArr['quantity'] = -$one->quantity_back;
            $tmpArr['quantity_back'] = 0;
            $tmpArr['quantity_in'] = 0;
            //$tmpArr['price_e2']      = -$one->price_e2_back;
            $tmpArr['price_e2_vat'] = -($one->price_e2_vat_back * $one->quantity_back);
            $tmpArr['price_e2'] = -($one->price_e2_back * $one->quantity_back);
            $tmpArr['cl_store_move_id'] = NULL;
            $tmpArr['cl_sale_items_id'] = $key;
            if ($one->price_e_type == 0) {
                //price_e is without VAT
                $tmpArr['price_e'] = -($one->price_e2_back);
            } else {
                //price_e is with VAT
                $tmpArr['price_e'] = -($one->price_e2_vat_back);
            }

            $this->SaleItemsManager->insert($tmpArr);
            $one->update(array('quantity_in' => $one->quantity_in + $one->quantity_back));
        }
        $this->DataManager->updateSaleSum($newCorrection->id);


        //2. send to EET

        //3. give in to store
        $this->giveIn($newCorrection->id);

        //4. generate PDF
        //$this->handleSavePDF($newCorrection->id);
        //$this->redirect(':default', $newCorrection->id);
        $this->id = $newCorrection->id;
        $this->bscId = $newCorrection->id;
        $this->redrawControl('content');

    }

    private function makeCorrection($dataMain)
    {

        $defValues = array('inv_date' => new \Nette\Utils\DateTime,
            'vat_date' => new \Nette\Utils\DateTime,
            'sale_type' => 1,
            'correction_cl_sale_id' => $dataMain->id,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'currency_rate' => $this->settings->cl_currencies->fix_rate,
            'cl_payment_types_id' => $this->settings->cl_payment_types_id_sale,
            'price_e_type' => $this->settings->price_e_type,
            'vat_active' => $this->settings->platce_dph,
            'cl_partners_book_id' => $dataMain->cl_partners_book_id,
            'cl_company_branch_id' => $dataMain->cl_company_branch_id,
            'discount' => $dataMain->discount,
            'cl_storage_id' => $dataMain->cl_storage_id,
            'cl_users_id' => $this->user->getId());

        if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', 'sale', 1)->fetch()) {
            $defValues['cl_status_id'] = $nStatus->id;
        }

        //if there is no sale record, create one
        $tmpCorrection = $this->DataManager->insert($defValues);
        return $tmpCorrection;

    }

    public function getNumberSeries($data = '', $idNs = NULL)
    {
        if (isset($this->numberSeries['use'])) {
            //if data is given, we use it for numberseries
            if ($data != '') {
                $nSeries = $this->NumberSeriesManager->getNewNumber($data);
            } else {
                if (is_null($this->branchNumberSeriesCorrectionId)) {
                    $nSeries = $this->NumberSeriesManager->getNewNumber($this->numberSeries['use']);
                } else {
                    $nSeries = $this->NumberSeriesManager->getNewNumber($this->numberSeries['use'], $this->branchNumberSeriesCorrectionId);
                }
            }

            $this->defValues[$this->numberSeries['table_key']] = $nSeries['id'];
            $this->defValues[$this->numberSeries['table_number']] = $nSeries['number'];
            if ($data != '')
                $tmpStatus = $data;
            else
                $tmpStatus = $this->numberSeries['use'];

            if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?', $tmpStatus, 1)->fetch())
                $this->defValues['cl_status_id'] = $nStatus->id;
        } else {

        }
        return $data;
    }

    private function giveIn($dataId)
    {
        $docId = $this->StoreDocsManager->createStoreDoc(0, $dataId, $this->DataManager);
        //$arrDataItems = json_decode($dataItems, true);
        $arrDataItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $dataId);
        foreach ($arrDataItems as $key => $one) {
            //$oorderItem = $this->OrderItemsManager->find($one);
            //2. store in current item
            /*$arrOne = $one->toArray();
            $arrOne['quantity']         = -$arrOne['quantity'];
            $arrOne['quantity_back']    = 0;
            $arrOne['quantity_in']      = 0;
            $arrOne['price_e2']         = -$arrOne['price_e2'];
            $arrOne['price_e2_vat']     = -$arrOne['price_e2_vat'];
            */
            $dataIdtmp = $this->StoreManager->giveInItem($docId, $key, $this->SaleItemsManager);
        }
        $this->StoreManager->UpdateSum($docId);
        $this->flashMessage($this->translator->translate('Vrácené_položky_byly_naskladněny'), 'success');
        $this->redrawControl('content');
    }

    /* public function handleSendEET()
     {
         $tmpData = $this->DataManager->find($this->id);
         if ($tmpData['price_e2'] == 0 && $tmpData['price_e2_vat'] == 0){
             $this->flashMessage('Doklad má nulovou částku. Není možné jej odeslat do EET.', 'danger');
         }elseif (!is_null($tmpData->cl_payment_types_id) && ($tmpData->cl_payment_types->payment_type == 1  || $tmpData->cl_payment_types->eet_send == 1)) {
             //send to EET
             $dataForSign = $this->CompaniesManager->getDataForSignEET($tmpData['cl_company_branch_id']);
             try {
                 $arrRet = $this->EETService->sendEET($tmpData, $dataForSign);
                 $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                 $tmpData->update(array('cl_eet_id' => $tmpId));
             } catch (\Exception $e) {
                 $this->flashMessage('Chyba certifikátu. Zkontrolujte nahraný certifikát a heslo', 'danger');
                 $this->flashMessage($e->getMessage(), 'danger');
             }
         }else {
             $this->flashMessage('Doklad není hrazen v hotovosti. Není možné jej odeslat do EET.', 'danger');
         }
         $this->redrawControl('content');
     }*/


    public function handleSendEET()
    {
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData['price_e2'] == 0 && $tmpData['price_e2_vat'] == 0) {
            $this->flashMessage($this->translator->translate('Doklad_má_nulovou_částku_Není_možné_jej_odeslat_do_EET.'), 'danger');
        } elseif (!is_null($tmpData->cl_payment_types_id) && ($tmpData->cl_payment_types->payment_type == 1 || $tmpData->cl_payment_types->eet_send == 1)) {
            //send to EET
            $dataForSign = $this->CompaniesManager->getDataForSignEET($tmpData['cl_company_branch_id']);
            try {
                if ($dataForSign && $dataForSign['eet_ghost'] == 0) {
                    $arrRet = $this->EETService->sendEET($tmpData, $dataForSign);
                    $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                    $tmpData->update(array('cl_eet_id' => $tmpId));
                } elseif ($dataForSign && $dataForSign['eet_ghost'] == 1) {
                    $arrRet = array();
                    $arrRet['id'] = $tmpData['cl_eet_id'];
                    $arrRet['UUID'] = "";
                    $arrRet['FIK'] = "2ef5347e-0165-4927-bb8e-047d9572d720-02";
                    $arrRet['BKP'] = "00d4c3b5-d8b9127e-bb0d9aed-8d9bf025-3bfe7a86";
                    $arrRet['PKP'] = "cAISIzXwZiqS8oBgpuU/JKE2EJCwR1xdlbgF9PKGG3MefyAk+FFsBZOIdYI2wZ/Xhuwn9vBEvv9/ewo4Il6BduQSNvUJYqJaj5JLTctbnG+FfLNme+c9A4xUcgNnwvIM0D6FbfsKVUdCHkSzyGWZ4sZFTzpKAfq636jurHOLVosQfo5h1pJbR5YONL5hOwPTslL0uWwKohmfwzJj31gdi/s2Qpd59mYpstL1dTWqWaf79wR7jzyLiyWRLlSb2z1mk3pB/GnLw63vmvk0zcRYBKgZ/XA6NwhsMDFr8j9o+wKJOauBwz+wgFk2KrOW5HHp3nvDla59pG5Z/YkjEZUWSQ==";
                    $arrRet['Error'] = "";
                    $arrRet['Warnings'] = array();
                    $arrRet['eet_id'] = $dataForSign['eet_id_provoz'];
                    $arrRet['eet_idpokl'] = $dataForSign['eet_id_poklad'];
                    $arrRet['dat_trzby'] = new \DateTime();
                    $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                    //$row = $this->EETManager->insert($tmpNew);
                    $tmpData->update(array('cl_eet_id' => $tmpId));
                }
            } catch (\Exception $e) {
                $this->flashMessage($this->translator->translate('Chyba_certifikátu_Zkontrolujte_nahraný_certifikát_a_heslo'), 'danger');
                $this->flashMessage($e->getMessage(), 'danger');
            }
        } else {
            $this->flashMessage($this->translator->translate('Doklad_není_hrazen_v_hotovosti_Není_možné_jej_odeslat_do_EET'), 'danger');
        }
        $this->redrawControl('content');
    }


    public function getTotalBack()
    {
        $tmpItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $this->id)->sum('quantity_back');
        return ($tmpItems);

    }

    public function getSumBack()
    {
        $tmpItems = $this->SaleItemsManager->findAll()->where('cl_sale_id = ?', $this->id)->sum('quantity_back * price_e2_vat_back');
        return ($tmpItems);

    }


}
