<?php

namespace App\ApplicationModule\Presenters;

use mysql_xdevapi\Exception;
use Nette\Application\UI\Form,
    Nette\Image;

use App\Controls;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Utils\DateTime;
use App\EDI;
use Nette\Utils\Json;

class OrderPresenter extends \App\Presenters\BaseListPresenter
{
    const
        DEFAULT_STATE = 'Czech Republic';

    public $newId = NULL, $headerModalShow = FALSE, $pairedDocsShow = FALSE, $createDocShow = FALSE;

    public $filterStoreUsed = [];

    /** @persistent */
    public $page_b;

    /** @persistent */
    public $filter;

    /** @persistent */
    public $showMistakes = FALSE;

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
     * @var \App\Model\OrderManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\CommissionManager
     */
    public $CommissionManager;

    /**
     * @inject
     * @var \App\Model\CommissionItemsManager
     */
    public $CommissionItemsManager;

    /**
     * @inject
     * @var \App\Model\CommissionItemsSelManager
     */
    public $CommissionItemsSelManager;

    /**
     * @inject
     * @var \App\Model\OrderItemsManager
     */
    public $OrderItemsManager;

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
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;


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
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\TextsManager
     */
    public $TextsManager;

    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;


    protected function startup()
    {
        parent::startup();

        //$this->translator->setPrefix(['applicationModule.Order']);
        $this->formName = $this->translator->translate("Objednávky");
        $this->mainTableName = 'cl_order';
        //$settings = $this->CompaniesManager->getTable()->fetch();
        if ($this->settings->platce_dph == 1)
            $arrData = ['od_number' => $this->translator->translate('Číslo_objednávky'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean'],
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'format' => 'text', 'show_clink' => true],
                'od_date' => [$this->translator->translate('Datum_objednávky'), 'format' => 'date'],
                'req_date' => [$this->translator->translate('Požadované_dodání'), 'format' => 'date'],
                'rea_date' => [$this->translator->translate('Skutečné_dodání'), 'format' => 'date'],
                'od_title' => [$this->translator->translate('Popis'), 'format' => 'text'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                's_on_store' => [$this->translator->translate('Naskladněno'), 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                'price_e2_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text'],
                'cl_company_branch.name' => [$this->translator->translate('Pobočka'), 'format' => 'text'],
                'currency_rate' => [$this->translator->translate('Kurz'), 'format' => 'text'],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'text'],
                'delivery_place' => [$this->translator->translate('Místo_dodání'), 'format' => 'text'],
                'delivery_method' => [$this->translator->translate('Způsob_dodání'), 'format' => 'text'],
                'inv_numbers' => [$this->translator->translate('Faktury'), 'format' => 'text'],
                'dln_numbers' => [$this->translator->translate('Dodací_listy'), 'format' => 'text'],
                'com_numbers' => [$this->translator->translate('Zakázky'), 'format' => 'text'],
                'memo_txt' => [$this->translator->translate('Poznámka'), 'format' => 'text'],
                'cl_users.name' => $this->translator->translate('Objednal'),
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        else
            $arrData = ['od_number' => $this->translator->translate('Číslo_objednávky'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean'],
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'format' => 'text', 'show_clink' => true],
                'od_date' => [$this->translator->translate('Datum_objednávky'), 'format' => 'date'],
                'req_date' => [$this->translator->translate('Požadované_dodání'), 'format' => 'date'],
                'rea_date' => [$this->translator->translate('Skutečné_dodání'), 'format' => 'date'],
                'od_title' => [$this->translator->translate('Popis'), 'format' => 'text'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                's_on_store' => [$this->translator->translate('Naskladněno'), 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                'price_e2_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text'],
                'cl_company_branch.name' => [$this->translator->translate('Pobočka'), 'format' => 'text'],
                'currency_rate' => [$this->translator->translate('Kurz'), 'format' => 'text'],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'text'],
                'delivery_place' => [$this->translator->translate('Místo_dodání'), 'format' => 'text'],
                'delivery_method' => [$this->translator->translate('Způsob_dodání'), 'format' => 'text'],
                'inv_numbers' => [$this->translator->translate('Faktury'), 'format' => 'text'],
                'dln_numbers' => [$this->translator->translate('Dodací_listy'), 'format' => 'text'],
                'com_numbers' => [$this->translator->translate('Zakázky'), 'format' => 'text'],
                'memo_txt' => [$this->translator->translate('Poznámka'), 'format' => 'text'],
                'cl_users.name' => $this->translator->translate('Objednal'),
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];

        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = ['od_number' => 'autocomplete', 'cl_partners_book.company' => 'autocomplete',
            'inv_numbers' => 'autocomplete', 'dln_numbers' => 'autocomplete', 'com_numbers' => 'autocomplete',
            'memo_txt' => 'autocomplete', 'delivery_place' => 'autocomplete', 'delivery_method' => 'autocomplete',
            'cl_status.status_name' => 'autocomplete', 'price_e2' => 'autocomplete', 'price_e2_vat' => 'autocomplete',
            'od_title' => 'autocomplete', 'cl_users.name' => 'autocomplete', 'od_date' => '', 'req_date' => '', 'rea_date' => ''];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['od_number', 'od_title', 'com_numbers', 'cl_partners_book.company', 'memo_txt', 'inv_numbers', 'dln_numbers', 'com_numbers', 'price_e2_vat', 'price_e2'];

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_order_items.item_label', ':cl_order_items.cl_pricelist.identification', ':cl_order_items.cl_pricelist.item_label',
            ':cl_order_items.note', ':cl_order_items.note2'];

        $this->DefSort = 'od_date DESC';


        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;

        //30.09.2019 - default storage for company branch
        $tmpCompanyBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $cl_storage_id = NULL;
        if (!is_null($tmpCompanyBranchId)) {
            if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('id = ?', $tmpCompanyBranchId)->limit(1)->fetch())
                $cl_storage_id = $tmpBranch->cl_storage_id;
        } else {
            $cl_storage_id = $this->settings->cl_storage_id;
        }

        $this->defValues = array('od_date' => new \Nette\Utils\DateTime,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'currency_rate' => $this->settings->cl_currencies->fix_rate,
            'header_show' => $this->settings->header_show_ord,
            'header_txt' => $this->settings->header_txt_ord,
            'cl_storage_id' => $cl_storage_id,
            'cl_users_id' => $this->user->getId(),
            'cl_company_branch_id' => $this->user->getIdentity()->cl_company_branch_id);

        //$this->numberSeries = 'commission';
        $this->numberSeries = ['use' => 'order', 'table_key' => 'cl_number_series_id', 'table_number' => 'od_number'];
        $this->readOnly = ['od_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE];
        //$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));
        //$this->showChildLink = 'PartnersEvent:default';
        //Condition for color highlit rows
        //$testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-30 day');
        //$this->conditionRows = array( 'cdate','<=',$testDate);
        //$this->rowFunctions = array('copy' => 'disabled');
        $this->rowFunctions = [];

        //04.07.2018 - settings for documents saving and emailing
        $this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../templates/Order/orderv1.latte');
        $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        //$this->docTitle	    = array("Objednávka ", "od_number");
        $this->docTitle = ["", "cl_partners_book.company", "od_number"];
        //22.07.2018 - settings for sending doc by email
        $this->docEmail = ['template' => __DIR__ . '/../templates/Order/emailOrder.latte',
            'emailing_text' => 'order'];
        //settings for CSV attachments
        $this->csv_h = ['columns' => 'od_number,od_date,req_date,od_title,cl_partners_book.company,cl_partners_book_workers.worker_name,cl_currencies.currency_code,price_e2,price_e2_vat,cl_order.header_txt,cl_order.footer_txt,delivery_place,delivery_method,cl_storage.name AS storage_name'];
        $this->csv_i = ['columns' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_order_items.item_label,cl_pricelist.order_label,cl_order_items.quantity,cl_order_items.units,cl_storage.name AS storage_name,cl_order_items.price_e2,cl_order_items.price_e2_vat',
            'datasource' => 'cl_order_items'];


        $this->toolbar = [0 => ['group_start' => ''],
            1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nová_objednávka'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-plus'],
            2 => $this->getNumberSeriesArray('order'),
            3 => ['group_end' => '']];

        //27.08.2018 - filter for show only not used items and tasks to create store in
        $this->filterStoreUsed = ['filter' => 'cl_store_docs_id IS NULL AND quantity_rcv > 0 AND rea_date IS NOT NULL'];
        $this['orderlistgridSelect']->setFilter($this->filterStoreUsed);
        //array('filter' => 'cl_store_docs_id IS NULL');

        /*baselist child section
         * all bellow is for master->child show
         */
        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        //$this->translator->setPrefix(['applicationModule.Order']);
        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Order\card.latte'],
            'items' => ['active' => true, 'name' => $this->translator->translate('položky'), 'lattefile' => $this->getLattePath() . 'Order\items.latte'],
            'header' => ['active' => false, 'name' => $this->translator->translate('záhlaví'), 'lattefile' => $this->getLattePath() . 'Order\header.latte'],
            'assignment' => ['active' => false, 'name' => $this->translator->translate('zápatí'), 'lattefile' => $this->getLattePath() . 'Order\footer.latte'],
            'memos' => ['active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'Order\description.latte'],
            'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'Order\files.latte']
        ];
        $this->bscSums = ['lattefile' => $this->getLattePath() . 'Order\sums.latte'];

        //$tmpLink = $this->link('ImportData:', array('modal' => true, 'target' => $this->name));
        $this->bscToolbar = [
            1 => ['url' => 'importEDI!', 'urlparams' => ['keyname' => 'bscId', 'key' => 'id'], 'rightsFor' => 'write', 'label' => 'EDI', 'title' => $this->translator->translate('Import_z_formátu_EDI'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-scroll-to="false"'], 'icon' => 'iconfa-plus'],
            2 => ['url' => 'ImportData:', 'urlparams' => ['keyname' => 'id', 'key' => 'id'],
                'urlparams2' => ['modal' => true, 'target' => $this->name],
                'rightsFor' => 'write', 'label' => 'CSV', 'title' => $this->translator->translate('Import CSV'),
                'class' => 'btn btn-success modalClick',
                'data' => ['data-href', 'data-history="false"',
                    'data-title = "Import CSV"']],
            3 => ['url' => 'bulkInsert!', 'urlparams' => ['keyname' => 'bscId', 'key' => 'id'], 'rightsFor' => 'write', 'label' => $this->translator->translate('H_položky'), 'title' => $this->translator->translate('Hromadné_dodání_položek'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-barcode'],
            4 => ['url' => 'createStoreInModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('naskladnit'), 'title' => $this->translator->translate('naskladní_dodané_položky_objednávky'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            5 => ['url' => 'genContentReq!', 'rightsFor' => 'write', 'label' => $this->translator->translate('generovatreq'), 'title' => $this->translator->translate('vloží_položky_dodavatele_se_stavem_pod_požadovaným_množstvím'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-confirm="ano"', 'data-cancel="ne"', 'data-prompt="' . $this->translator->translate("Opravdu_generovat_obsah_objednávky") . '"'],
                'icon' => 'glyphicon glyphicon-list'],
            6 => ['url' => 'genContent!', 'rightsFor' => 'write', 'label' => $this->translator->translate('generovat'), 'title' => $this->translator->translate('vloží_položky_dodavatele_se_stavem_pod_minimálním_množstvím'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-confirm="ano"', 'data-cancel="ne"', 'data-prompt="' . $this->translator->translate("Opravdu_generovat_obsah_objednávky") . '"'],
                'icon' => 'glyphicon glyphicon-list'],
            7 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('texty'), 'title' => $this->translator->translate('šablony_často_používaných_textů'), 'class' => 'btn btn-success showTextsUse',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'], 'icon' => 'glyphicon glyphicon-list'],
            8 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'title' => $this->translator->translate('zobrazí_spárované_doklady'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
            9 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'title' => $this->translator->translate('Zobrazí_náhled'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'cl_partners_book_id', 'condition' => '!=', 'value' => NULL]],
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            10 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'cl_partners_book_id', 'condition' => '!=', 'value' => NULL]],
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
            11 => ['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => 'e-mail', 'title' => $this->translator->translate('odešle_doklad_emailem'), 'class' => 'btn btn-success',
                'showCondition' => [['column' => 'cl_partners_book_id', 'condition' => '!=', 'value' => NULL]],
                'icon' => 'glyphicon glyphicon-send'],

        ];
        $this->bscTitle = ['od_number' => $this->translator->translate('Číslo_objednávky'), 'cl_partners_book.company' => $this->translator->translate('Odběratel'), '_variable' => ['name' => 'orderPriceState', 'class' => 'status_doc']];
        /*end of bsc section
         *
         */

        $this->quickFilter = ['cl_status.status_name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
            'values' => $this->StatusManager->findAll()->where('status_use = ?', 'commission')->order('s_new DESC,s_work DESC,s_fin DESC,s_storno DESC,status_name ASC')->fetchPairs('id', 'status_name')]
        ];

        if ($this->isAllowed($this->presenter->name, 'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
        }


    }


    protected function createComponentBulkInsert()
    {
        return new Controls\BulkInsertControl($this->translator,
            $this->session,
            $this->DataManager,
            $this->id,
            $this->PriceListManager,
            TRUE,
            $this->translator->translate('Nákupní_cena'),
            TRUE,
            ['quantity_ord' => ['name' => $this->translator->translate('Objednané_množství')]],
            [1 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '==', 'right' => 'quantity_ord']],
                'colour' => $this->presenter->RGBtoHex(151, 255, 151)],
                2 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '<', 'right' => 'quantity_ord'],
                    2 => ['left' => 'quantity', 'condition' => '>', 'right' => 0]],
                    'colour' => $this->presenter->RGBtoHex(255, 151, 151)],
                3 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '>', 'right' => 'quantity_ord']],
                    'colour' => $this->presenter->RGBtoHex(255, 255, 151)],
            ]
        );
    }

    protected function createComponentEditTextFooter()
    {
        return new Controls\EditTextControl($this->translator, $this->DataManager, $this->id, 'footer_txt');
    }

    protected function createComponentEditTextHeader()
    {
        return new Controls\EditTextControl($this->translator, $this->DataManager, $this->id, 'header_txt');
    }

    protected function createComponentEditTextDescription()
    {
        return new Controls\EditTextControl($this->translator, $this->DataManager, $this->id, 'description_txt');
    }

    protected function createComponentPairedDocs()
    {
        return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }

    protected function createComponentTextsUse()
    {
        return new TextsUseControl($this->DataManager, $this->id, 'order', $this->TextsManager, $this->translator);
    }

    protected function createComponentSumOnDocs()
    {
        //$this->translator->setPrefix(['applicationModule.Order']);
        if ($data = $this->DataManager->findBy(['id' => $this->id])->fetch()) {
            if ($data->cl_currencies) {
                $tmpCurrencies = $data->cl_currencies->currency_name;
            }


            $tmpPriceE2 = $data->price_e2;
            $tmpPriceE2Vat = $data->price_e2_vat;

            $tmpPriceE2_rcv = $data->price_e2_rcv;
            $tmpPriceE2Vat_rcv = $data->price_e2_vat_rcv;


            if ($this->settings->platce_dph) {
                $tmpPriceNameBase = $this->translator->translate("Objednáno_bez_DPH");
                $tmpPriceNameVat = $this->translator->translate("Objednáno_s_DPH");
                $tmpPriceNameBase2 = $this->translator->translate("Dodáno_bez_DPH");
                $tmpPriceNameVat2 = $this->translator->translate("Dodáno_s_DPH");
            } else {
                $tmpPriceNameBase = $this->translator->translate("Objednáno");
                $tmpPriceNameVat = "";
                $tmpPriceNameBase2 = $this->translator->translate("Dodáno");
                $tmpPriceNameVat2 = "";
            }


            $dataArr = [
                ['name' => $tmpPriceNameBase, 'value' => $tmpPriceE2, 'currency' => $tmpCurrencies],
                ['name' => $tmpPriceNameVat, 'value' => $tmpPriceE2Vat, 'currency' => $tmpCurrencies],
                ['name' => 'separator'],
                ['name' => $tmpPriceNameBase2, 'value' => $tmpPriceE2_rcv, 'currency' => $tmpCurrencies],
                ['name' => $tmpPriceNameVat2, 'value' => $tmpPriceE2Vat_rcv, 'currency' => $tmpCurrencies],
            ];
            //bdump($dataArr);
        } else {
            $dataArr = [];
        }
        // $translator = clone $this->translator->setPrefix([]);
        return new SumOnDocsControl($this->translator, $this->DataManager, $this->id, $this->settings, $dataArr);
    }

    protected function createComponentEmail()
    {
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\EmailControl($this->translator, $this->EmailingManager, $this->mainTableName, $this->id);
    }

    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl($this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_order_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }


    protected function createComponentOrderlistgrid()
    {
        //if ($this->settings->platce_dph == 1)
        $arrStore = $this->StorageManager->getStoreTreeNotNested();
        $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 9, 'readonly' => TRUE],
            'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 15],
            'quantity' => [$this->translator->translate('Objednáno'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
            'units' => ['', 'format' => 'text', 'size' => 7],
            'price_e' => [$this->translator->translate('Cena_bez_DPH'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
            'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 8],
            'vat' => [$this->translator->translate('DPH_%'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 6],
            'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 8],
            'cl_storage.name' => [$this->translator->translate('Cílový_sklad'), 'format' => 'chzn-select-req',
                'values' => $arrStore, 'required' => $this->translator->translate('Vyberte_sklad'),
                'size' => 8],
            'quantity_rcv' => [$this->translator->translate('Dodáno'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
            'rea_date' => [$this->translator->translate('Datum_dodání'), 'format' => 'date', 'size' => 9],
            'price_e_rcv' => [$this->translator->translate('Dodací_cena'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
            'reminder' => [$this->translator->translate('Upomenuto'), 'format' => 'boolean', 'size' => 5],
            'cl_store_docs.doc_number' => [$this->translator->translate('Příjemka'), 'format' => "url", 'size' => 9, 'url' => 'storein', 'value_url' => 'cl_store_docs_id'],
            'note2' => [$this->translator->translate('Poznámka_2'), 'format' => "text", 'size' => 20],
            'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE]
        ];
        //else
        $tmpData = $this->DataManager->find($this->id);
        // $translator = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->OrderItemsManager,
            $arrData,
            [],
            $this->id,
            ['units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba, 'cl_storage_id' => $tmpData->cl_storage_id],
            $this->DataManager,
            $this->PriceListManager,
            $this->PriceListPartnerManager,
            TRUE,
            ['pricelist2' => $this->link('RedrawPriceList2!'),
                'activeTab' => 1
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
            'cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?',
            NULL, //toolbar
            FALSE, //forceEnable
            FALSE, //paginatorOff
            [1 => ['conditions' => [1 => ['left' => 'price_e', 'condition' => '<', 'right' => 'price_e_rcv',
                'lfunc' => ['name' => 'round', 'param' => 0],
                'rfunc' => ['name' => 'round', 'param' => 0]],
                2 => ['left' => 'quantity_rcv', 'condition' => '>', 'right' => '0']
            ],
                'colour' => $this->RGBtoHex(255, 151, 151)], //red1
            ], //colours conditions
            20, //pagelength
            'auto' //$containerHeight
        );
        /* $control->setToolbar(array(	0 => array('group_start' => ''),
                             1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Chybně dodáno', 'class' => 'btn btn-primary', 'icon' => 'iconfa-filter'),
                             2 => $this->getNumberSeriesArray('order'),
                             3 => array('group_end' => '')));
     */

        // 'cl_pricelist.identification'
        //3 => array('url' => $this->link('SortItems!'), 'rightsFor' => 'write', 'data' => array('data-history=false'),
        //                       'label' => 'Seřadit', 'title' => 'Seřadí podle kódu', 'class' => 'btn btn-success', 'icon' => 'iconfa-sort'),
        //$this->translator->setPrefix(['applicationModule.Order']);
        $control->setToolbar([
            1 => ['url' => $this->link('ShowMistakes!'), 'rightsFor' => 'read', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Chybně_dodáno'), 'title' => $this->translator->translate('Zobrazí_jen_položky_s_rozdílem_dodaného_množství_nebo_ceny_oproti_objednávce'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-filter'],
            2 => ['url' => $this->link('ShowAll!'), 'rightsFor' => 'read', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Vše'), 'title' => $this->translator->translate('Zobrazí_všechny_položky'), 'class' => 'btn btn-success', 'icon' => 'iconfa-filter'],

            3 => ['group' =>
                [0 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.identification', 'cmpName' => 'orderlistgrid']),
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('Kód_zboží'),
                    'title' => $this->translator->translate('Seřadí_podle_kódu_zboží'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => ''],
                    1 => ['url' => $this->link('SortItems!', ['sortBy' => 'item_label', 'cmpName' => 'orderlistgrid']),
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Název'),
                        'title' => $this->translator->translate('Seřadí_podle_názvu_položky'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                    2 => ['url' => $this->link('SortItems!', ['sortBy' => 'id', 'cmpName' => 'orderlistgrid']),
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
            4 => ['url' => $this->link('ResetAll!'), 'rightsFor' => 'write', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Vynulovat'), 'title' => $this->translator->translate('Vynuluje_údaje_o_dodaných_položkách_pokud_ještě_nebyly_naskladněny'), 'class' => 'btn btn-warning', 'icon' => 'iconfa-eraser'],
        ]);

        if ($this->showMistakes) {
            $control->setFilter(['filter' => 'quantity != quantity_rcv OR ROUND(price_e,0) != ROUND(price_e_rcv,0)']);
            $control->setToolbar([
                1 => ['class' => 'btn btn-success'],
                2 => ['class' => 'btn btn-primary']]);
        } else {
            $control->setFilter([]);
            $control->setToolbar([
                1 => ['class' => 'btn btn-primary'],
                2 => ['class' => 'btn btn-success']]);
        }

        $control->setContainerHeight("auto");
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }

    public function handleResetAll()
    {
        $items = $this->OrderItemsManager->findAll()->where('cl_order_id = ? AND cl_store_docs_id IS NULL', $this->id);
        $i = 0;
        foreach ($items as $key => $one) {
            $one->update(['quantity_rcv' => 0, 'rea_date' => NULL, 'price_e_rcv' => 0]);
            $i++;
        }
        $this->flashMessage($this->translator->translate('Vynulováno') . $i . $this->translator->translate('záznamů'), 'success');
        $this['orderlistgrid']->redrawControl('paginator');
        $this['orderlistgrid']->redrawControl('editLines');
    }

    public function handleShowMistakes()
    {
        $this->showMistakes = TRUE;

        $this['orderlistgrid']->redrawControl('paginator');
        $this['orderlistgrid']->redrawControl('editLines');
    }

    public function handleShowAll()
    {
        $this->showMistakes = FALSE;

        $this['orderlistgrid']->redrawControl('paginator');
        $this['orderlistgrid']->redrawControl('editLines');
    }


    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);

        $this->template->orderPriceState = $this->DataManager->checkPrices((is_null($this->id) ? $this->bscId : $this->id)); // empty - ok, fill - filled with message
        //dump($this->conditionRows);
        //die;

    }

    public function renderEdit($id, $copy = FALSE, $modal = FALSE)
    {
        parent::renderEdit($id, $copy, $modal);


        $this->template->orderPriceState = $this->DataManager->checkPrices((is_null($this->id) ? $this->bscId : $this->id)); // empty - ok, fill - filled with message
        //if ($defData = $this->DataManager->findOneBy(array('id' => $id)))
        //{
        //	$this['headerEdit']->setValues($defData);
        //}
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$this->translator->setPrefix(['applicationModule.Order']);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addText('od_number', $this->translator->translate('Číslo_objednávky'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_objednávky'));
        $form->addText('off_numbers', $this->translator->translate('Číslo_nabídky'), 10, 60)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_nabídky'));
        $form->addText('od_date', $this->translator->translate('Datum_objednání'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_objednání'));
        $form->addText('req_date', $this->translator->translate('Požadované_dodání'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Požadovaný_datum_dodání'));
        $form->addText('rea_date', $this->translator->translate('Skutečné_dodání'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Skutečný_datum_dodání'));
        $form->addText('od_title', $this->translator->translate('Popis_objednávky'), 150, 150)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis_objednávky'));
        $form->addTextArea('memo_txt', $this->translator->translate('Poznámka'), 40, 5)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Poznámka'));
        $form->addText('inv_numbers', $this->translator->translate('Faktura'), 64, 64)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('čísla_faktur'));
        $form->addText('dln_numbers', $this->translator->translate('Dodací_list'), 64, 64)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('čísla_dod._listů'));
        $form->addText('com_numbers', $this->translator->translate('Zakázka'), 64, 64)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('čísla_zakázek'));
        $form->addText('delivery_place', $this->translator->translate('Místo_dodání'), 128, 128)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('místo_dodání'));
        $form->addText('delivery_method', $this->translator->translate('Způsob_dodání'), 128, 128)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('způsob_dodání'));

        $arrStorage = $this->StorageManager->getStoreTreeNotNested();
        $form->addSelect('cl_storage_id', $this->translator->translate("Sklad"), $arrStorage)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_sklad'));

        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'order')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_objednávky'))
            ->setPrompt($this->translator->translate('Zvolte_stav_objednávky'));

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
//	    $arrPartners = $this->PartnersManager->findAll()->fetchPairs('id','company');
        $form->addSelect('cl_partners_book_id', $this->translator->translate("Dodavatel"), $arrPartners)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_dodavatele'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($this->translator->translate('Zvolte_dodavatele'));
        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_měnu'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setPrompt($this->translator->translate('Zvolte_měnu'));
        $form->addText('currency_rate', 'Kurz:', 7, 7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kurz'));

        $arrUsers = [];
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $form->addSelect('cl_users_id', $this->translator->translate("Objednal"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_objednávajícího'))
            ->setPrompt($this->translator->translate('Zvolte_objednávajícího'));

        $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
        $form->addSelect('cl_partners_book_workers_id', $this->translator->translate("Kontakt"), $arrWorkers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kontaktní_osobu'))
            ->setPrompt($this->translator->translate('Zvolte_kontaktní_osobu'));


        $form->onValidate[] = [$this, 'FormValidate'];
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class', 'btn btn-primary');

        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
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

        $this->redrawControl('content');

    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        //isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1

        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() || $form['send_fin']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy()) {
            $data = $this->RemoveFormat($data);

            //if (isset($defData->cl_status_id) && $defData->cl_status->s_fin == 1)
            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {
                    $this->DataManager->update($data, TRUE);
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
                $tmpTemplate = __DIR__ . '/../templates/Order/orderv1.latte';
                $tmpTitle = $this->translator->translate("Objednávka") . $data->od_number;
                $tmpAuthor = $this->user->getIdentity()->name . "z" . $this->settings->name;
                $orderTemplate = $this->createMyTemplate($data, $tmpTemplate, $tmpTitle, $tmpAuthor);

            }

            if ($form['send_fin']->isSubmittedBy()) {
                $data = $this->DataManager->find($data['id']);
                //send by email order, in case of partner coop save to partners commission
                if ($tmpCoopPartner = $this->PartnersCoopManager->findBy(array('cl_partners_book_id' => $data->cl_partners_book_id))->fetch()) {
                    //we have coop partner  $tmpCoopPartner->master_cl_company_id
                    /*next steps:
                     * 1. new commission
                     * - find cl_status for new commission
                     * - make new commission number
                     * 2. items to commission
                     */
                    $tmpOrder = $data;
                    //$this->DataManager->find($data->id);
                    $arrMasterData = new \Nette\Utils\ArrayHash;
                    foreach ($tmpOrder as $key => $one) {
                        $arrMasterData[$key] = $one;
                    }
                    $arrMasterData['cl_company_id'] = $tmpCoopPartner->master_cl_company_id;
                    $arrMasterData['cl_partners_book_id'] = $tmpCoopPartner->child_cl_partners_book_id;
                    //commission number
                    $nSeries = $this->NumberSeriesManager->getNewNumber('commission', NULL, NULL, $tmpCoopPartner->master_cl_company_id);
                    $arrMasterData['cl_number_series_id'] = $nSeries['id'];
                    $arrMasterData['cm_number'] = $nSeries['number'];
                    //find cl_status
                    if ($nStatus = $this->StatusManager->findAllTotal()
                        ->where('status_use = ? AND s_new = ? AND cl_company_id = ?', 'commission', 1, $tmpCoopPartner->master_cl_company_id)
                        ->fetch())
                        $arrMasterData['cl_status_id'] = $nStatus;
                    else
                        unset($arrMasterData['cl_status_id']);

                    $arrMasterData['cm_date'] = $arrMasterData['od_date'];
                    $arrMasterData['cm_title'] = $arrMasterData['od_title'];
                    $arrMasterData['cm_order'] = $arrMasterData['od_number'];
                    $arrMasterData['price_e2_base'] = $arrMasterData['price_e2'];
                    if ($tmpMasterCurrencies = $this->CurrenciesManager->findAllTotal()->
                    where(['cl_company_id' => $tmpCoopPartner->master_cl_company_id,
                        'currency_code' => $data->cl_currencies->currency_code])->fetch()) {
                        $arrMasterData['cl_currencies_id'] = $tmpMasterCurrencies->id;
                    } else {
                        $arrMasterData['cl_currencies_id'] = NULL;
                    }
                    unset($arrMasterData['id']);
                    //unset($arrMasterData['cl_status_id']);
                    unset($arrMasterData['od_number']);
                    unset($arrMasterData['od_date']);
                    unset($arrMasterData['od_title']);
                    //unset($arrMasterData['cl_number_series_id']);
                    //unset($arrMasterData['cm_number']);
                    $masterData = $this->CommissionManager->insertForeign($arrMasterData);

                    //2. now items
                    //$arrMasterData =  new \Nette\Utils\ArrayHash;
                    $arrMasterData = [];
                    $i = 1;
                    foreach ($tmpOrder->related('cl_order_items') as $oneRow) {
                        $tmpRow = [];
                        foreach ($oneRow as $key => $one) {
                            $tmpRow[$key] = $one;
                        }
                        $tmpRow['cl_commission_id'] = $masterData->id;
                        unset($tmpRow['id']);
                        unset($tmpRow['cl_order_id']);
                        $tmpRow['cl_company_id'] = $tmpCoopPartner->master_cl_company_id;
                        $tmpRow['cl_pricelist_id'] = $oneRow->cl_pricelist->master_id;
                        $arrMasterData[] = $tmpRow;
                        $i++;
                    }
                    //dump($arrMasterData);
                    //die;
                    $this->CommissionItemsManager->insertForeign($arrMasterData);
                }
                //show modal window
                $this->emailModalShow = TRUE;
                $template = $this->createTemplate();

                $data = $this->DataManager->find($data['id']);
                $template->data = $data;
                $template->url = $this->link('//:Application:Documents:Show', $data->cl_company_id, $data->cl_documents->key_document);
                $template->setFile(__DIR__ . '/../templates/Order/emailOrder.latte');
                $emailingText = $this->getEmailingText('order', $template->url, $data);
                $template->emailBody = $emailingText['body'];

                //$this->emailData = array('singleEmailFrom' => $this->settings->name.' <'.$this->settings->email.'>',
                //			'singleEmailTo' => $data->cl_partners_book->company.' <'.$data->cl_partners_book->email.'>',
                //			'subject' => $emailingText['subject'],
                //			'body' => (string)$template);

                //$this->redrawControl('Controls\EmailControl');


                $this->flashMessage('Objednávka byla odeslána', 'success');


            } elseif ($form['save_pdf']->isSubmittedBy()) {
                //save pdf
                //$data = $this->DataManager->find($data['id']);

                //$template->data = $data;

                $pdf = new \PdfResponse\PdfResponse($orderTemplate, $this->context);
                //$pdf->mPDF->OpenPrintDialog();
                // Všechny tyto konfigurace jsou volitelné:
                // Orientace stránky
                $pdf->pageOrientation = \PdfResponse\PdfResponse::ORIENTATION_PORTRAIT;
                // Formát stránky
                //$pdf->pageFormat = "A4-L";
                $pdf->pageFormat = "A4";
                // Okraje stránky
                $pdf->pageMargins = "5,5,5,5,20,60";
                // Způsob zobrazení PDF
                //$pdf->displayLayout = "continuous";
                // Velikost zobrazení
                //$pdf->displayZoom = "fullwidth";
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
                $pdf->onBeforeComplete[] = [$this, 'pdfBeforeComplete'];

                //$pdf->mPDF->IncludeJS("app.alert('This is alert box created by JavaScript in this PDF file!',3);");
                //$pdf->mPDF->IncludeJS("app.alert('Now opening print dialog',1);");
                //$pdf->mPDF->OpenPrintDialog();

                // Ukončíme presenter -> předáme řízení PDFresponse
                //$this->terminate($pdf);
                //$pdf->OpenPrintDialog();
                $this->sendResponse($pdf);

            } else {
                //$this->redirect('default');
                $this->redrawControl('flash');
                $this->redrawControl('formedit');
                $this->redrawControl('timestamp');
                $this->redrawControl('items');
                //$this->redirect('default');
                $this->redrawControl('content');
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


    /*public function handleGetCurrencyRate($idCurrency)
    {
	if ($rate = $this->CurrenciesManager->findOneBy(array('id' => $idCurrency))->fix_rate)
	    echo($rate);
	else {
	    echo(0);
	}
	//in future there can be another work with rates

	$this->terminate();
    }*/


    public function handleMakeRecalc($idCurrency, $rate, $oldrate, $recalc)
    {
        //in future there can be another work with rates
        //dump($this->editId);
        if ($rate > 0) {
            if ($recalc == 1) {
                $recalcItems = $this->OrderItemsManager->findBy(['cl_order_id', $this->id]);
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


            $this->UpdateSum();

        }
        $this->redrawControl('items');

    }

    public function UpdateSum()
    {

        $this->DataManager->updateSum($this->id);
        parent::updateSum();
        //$this->redrawControl('baselistArea');
        //$this->redrawControl('bscArea');
        //$this->redrawControl('bsc-child');


        $this['orderlistgrid']->redrawControl('editLines');
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
        //$arrPrice['price'] = $sourceData->price;
        //$arrPrice['price_vat'] = $sourceData->price_vat;
        //dump($sourceData);
        //die;
        $arrPrice['price'] = $sourceData->price_s;
        $arrPrice['price_vat'] = $sourceData->price_s * (1 + ($sourceData->vat / 100));
        $arrPrice['vat'] = $sourceData->vat;
        $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;


        $arrData = new \Nette\Utils\ArrayHash;
        $arrData[$this->DataManager->tableName . '_id'] = $this->id;

        $arrData['cl_pricelist_id'] = $sourcePriceData->id;
        $arrData['item_order'] = $this->OrderItemsManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;

        $arrData['item_label'] = $sourcePriceData->item_label;
        $arrData['quantity'] = 1;

        $arrData['units'] = $sourcePriceData->unit;

        $arrData['vat'] = $arrPrice['vat'];
        $arrData['price_e'] = $arrPrice['price'];
        $arrData['price_e2'] = $arrPrice['price'];
        $arrData['price_e2_vat'] = $arrPrice['price_vat'];

        if ($arrPrice['price'] == 0) {
            if ($tmpPriceS = $sourcePriceData->related('cl_store_move')->where('price_s > 0')->order('id DESC')->fetch()) {
                $lastPriceS = $tmpPriceS->price_s;
            } else {
                $lastPriceS = 0;
            }
            $arrData['price_e_rcv'] = $lastPriceS;

        } else {
            $arrData['price_e_rcv'] = $arrPrice['price'];
        }


        //prepocet kurzem
        //potrebujeme kurz ceníkove polozky a kurz zakazky
        if ($sourceData->cl_currencies_id != NULL)
            $ratePriceList = $sourceData->cl_currencies->fix_rate;
        else
            $ratePriceList = 1;

        if ($tmpOrder = $this->DataManager->find($this->id)) {
            $rateOrder = $tmpOrder->currency_rate;
            //01.09.2019 - default cl_storage
            $arrData['cl_storage_id'] = $tmpOrder->cl_storage_id;
        } else {
            $rateOrder = 1;
        }

        //$arrData['price_s'] = $arrData['price_s'] * $ratePriceList / $rateOrder;
        $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOrder;
        $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOrder;
        $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOrder;

        $row = $this->OrderItemsManager->insert($arrData);
        $this->updateSum();
        return ($row);

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

        $this['orderlistgrid']->redrawControl('pricelist2');
    }


    public function DataProcessListGrid($data)
    {
        if ($data['quantity_rcv'] > 0 && is_null($data['rea_date'])) {
            $now = new  \Nette\Utils\DateTime;
            $data['rea_date'] = $now;
        }

        return $data;
    }


    //control method to determinate if we can delete
    public function beforeDelete($lineId, $name = NULL)
    {
        /*	$tmpParentData = $this->DataManager->find($this->id);
        //	Debugger::fireLog($tmpParentData);
            if ($tmpParentData->doc_type == 0)
            {
                //incoming
                //test if there outgoing moves from this income
                if($result = $this->StoreOutManager->findBy(array('cl_store_move_in_id' => $lineId))->sum('s_out'))
                {
                $result = TRUE;
                $this->flashMessage('Z příjemky již bylo vydáváno, záznam není možné vymazat', 'danger');
                $this->redrawControl('flash');
                }
                else
                $result = FALSE;

            }elseif ($tmpParentData->doc_type == 1)
            {
                //outgoing
                //check if there is another document created from this outgoing eg. invoice
                $result = FALSE;
            }
            //Debugger::fireLog($result);*/
        $result = TRUE;
        if ($name == "orderlistgrid") {
            //delete constraint from cl_commission_items_sel and cl_commission_items for deleted line
            $tmpData = $this->OrderItemsManager->find($lineId);
            if ($tmpData) {
                $this->CommissionItemsManager->findAll()->where('cl_order_id = ? AND cl_order_items_id = ?', $tmpData['cl_order_id'], $tmpData['id'])->
                update(array('cl_order_id' => NULL));
                $this->CommissionItemsSelManager->findAll()->where('cl_order_id = ? AND cl_order_items_id = ?', $tmpData['cl_order_id'], $tmpData['id'])->
                update(array('cl_order_id' => NULL));
            }
        }

        return $result;
    }

    public function emailSetStatus()
    {
        $this->setStatus($this->id, ['status_use' => 'order',
            's_new' => 0,
            's_eml' => 1]);
    }


    protected function createComponentHeaderEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        //$form->addCheckbox('header_show', 'Tiskount záhlaví');
        $form->addTextArea('header_txt', $this->translator->translate('Záhlaví'), 100, 10)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Záhlaví'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepHeaderBack'];
        $form->onSuccess[] = [$this, 'SubmitEditHeaderSubmitted'];
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
        $data = $form->values;
        //dump($data);
        //die;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {
            $this->DataManager->update($data);
        }
        $this->headerModalShow = FALSE;
        $this->activeTab = 2;
        $this->redrawControl('items');
        $this->redrawControl('headerModalControl');
    }

    public function handleOrderHeaderShow($value)
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

    public function handleHeaderShow()
    {
        $this->headerModalShow = TRUE;
        $this->redrawControl('headerModalControl');
    }

    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
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


    public function handleShowTextsUse()
    {
        //bdump('ted');
        $this->pairedDocsShow = TRUE;
        $this->showModal('textsUseModal');
        $this->redrawControl('textsUse');
        //$this->redrawControl('contents');
    }

    public function handleGenContentReq()
    {
        $this->handleGenContent(1);
    }

    public function handleGenContent($type = 0)
    {
        $tmpData = $this->DataManager->findOneBy(['id' => $this->id]);
        if ($tmpData) {
            if ($tmpData->cl_status->s_new == 1) {
                if ($type == 1)
                    $dataMove = $this->StoreManager->getUnderLimitsReq([], [$tmpData->cl_partners_book_id]);
                else
                    $dataMove = $this->StoreManager->getUnderLimits([], [$tmpData->cl_partners_book_id]);



                $dataMove = $this->ArraysManager->select2array($dataMove);
                $this->DataManager->createOrder($dataMove, $this->translator->translate('objednávka_do_množství'), $this->id, [$tmpData->cl_partners_book_id]);
                $tmpStatusId = $this->StatusManager->findAll()->where('status_use = ? AND s_work = ?', 'order', 1)->fetch();
                if ($tmpStatusId) {
                    $tmpData->update(['cl_status_id' => $tmpStatusId->id]);
                }
                $this->flashMessage($this->translator->translate('Obsah_byl_vygenerován'), 'success');
            } else {
                $this->flashMessage($this->translator->translate('Obsah_je_možné_generovat_jen_u_nové_objednávky'), 'danger');
            }
        } else {
            $this->flashMessage($this->translator->translate('Obsah_nebyl_vygenerován'), 'danger');
        }
        $this->redrawControl('flash');
        $this->redrawControl('content');
    }


    protected function createComponentOrderlistgridSelect()
    {
        if (!is_null($this->bscId))
            $parentId = $this->bscId;
        elseif (!is_null($this->id))
            $parentId = $this->id;
        else
            $parentId = 0;

        $tmpParentData = $this->DataManager->find($parentId);
        $arrStore = $this->StorageManager->getStoreTreeNotNested();
        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        //$userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id',$userTmp->cl_company_id)->fetch();

        $arrData = ['item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 30],
            'quantity' => [$this->translator->translate('Objednáno'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
            'units' => ['', 'format' => 'text', 'size' => 7],
            'price_e' => [$this->translator->translate('Cena_bez_DPH'), 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena],
            'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 12],
            'vat' => [$this->translator->translate('DPH_%'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 7],
            'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 10],
            'cl_storage.name' => [$this->translator->translate('Cílový_sklad'), 'format' => 'chzn-select-req',
                'values' => $arrStore, 'required' => $this->translator->translate('Vyberte_sklad'),
                'size' => 10],
            'quantity_rcv' => [$this->translator->translate('Dodáno'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
            'rea_date' => [$this->translator->translate('Datum_dodání'), 'format' => 'date', 'size' => 10],
            'price_e_rcv' => [$this->translator->translate('Dodací_cena'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
            'cl_store_docs.doc_number' => [$this->translator->translate('Příjemka'), 'format' => "url", 'size' => 12, 'url' => 'storein', 'value_url' => 'cl_store_docs_id'],
        ];

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->OrderItemsManager,
            $arrData,
            [],
            $parentId,
            ['units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba],
            $this->DataManager,
            NULL, //pricelist manager
            $this->PriceListPartnerManager,
            FALSE, //add emtpy row
            [], //custom links,
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
        return $control;

    }

    public function handleCreateStoreInModalWindow()
    {
        $this->filterStoreUsed = ['filter' => 'cl_store_docs_id IS NULL AND quantity_rcv > 0 AND rea_date IS NOT NULL'];
        $this['orderlistgridSelect']->setFilter($this->filterStoreUsed);
        $this->createDocShow = TRUE;
        $this->showModal('createStoreInModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
        $this->redrawControl('itemsForStoreIn');
        //bdump( $this->filterStoreUsed );
    }


    public function handleShowStoreInUsed()
    {
        $this->filterStoreUsed = ['filter' => 'quantity_rcv > 0 AND rea_date IS NOT NULL AND cl_store_docs_id IS NOT NULL'];
        $this['orderlistgridSelect']->setFilter($this->filterStoreUsed);
        $this->redrawControl('bscAreaEdit');

        $this->redrawControl('itemsForStoreIn');
    }

    public function handleShowStoreInNotUsed()
    {
        $this->filterStoreUsed = ['filter' => 'cl_store_docs_id IS NULL AND quantity_rcv > 0 AND rea_date IS NOT NULL'];
        $this['orderlistgridSelect']->setFilter($this->filterStoreUsed);
        $this->redrawControl('bscAreaEdit');

        $this->redrawControl('itemsForStoreIn');
    }


    public function handleCreateStoreIn($dataItems)
    {

        //back items - store in
        $docId = $this->StoreDocsManager->createStoreDoc(0, $this->id, $this->DataManager);
        //30.09.2019 - update cl_company_branch_id according to destination cl_order.cl_storage_id
        //$this->StoreDocsManager->update('id' => $docId, 'cl_company_branch_id' => );
        $arrDataItems = json_decode($dataItems, true);
        foreach ($arrDataItems as $key => $one) {
            //$oorderItem = $this->OrderItemsManager->find($one);
            //2. store in current item
            $dataIdtmp = $this->StoreManager->giveInItem($docId, $one, $this->OrderItemsManager, TRUE);

        }
        $this->StoreManager->UpdateSum($docId);
        $this->flashMessage($this->translator->translate('Objednávka_byla_naskladněna'), 'success');

        if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_fin = 1', 'order')->fetch()) {
            if ($tmpData = $this->DataManager->find($this->id)) {
                $tmpData->update(['cl_status_id' => $nStatus->id]);
            }
        }

        $this->CreateInvoiceArrived($docId);
        $this->DataManager->updateSum($this->id);
        $this->payload->id = $docId;
        $this->redrawControl('flash');
        $this->redrawControl('content');

    }

    public function CreateInvoiceArrived($bscId)
    {
        try {
            $result = $this->StoreManager->createInvoiceArrived($bscId);
            if (!$result) {
                $this->flashmessage($this->translator->translate('Chyba_při_vytváření_faktury_přijaté'), 'warning');
            } else {
                $this->flashmessage($this->translator->translate('Faktura_přijatá_byla_vytvořena'), 'success');
            }
        } catch (\Exception $e) {
            $this->flashmessage($this->translator->translate('Chyba_při_vytváření_faktury_přijaté') . $e->getMessage(), 'warning');
        }
    }

    public function handleBulkInsert()
    {
        //$tmpData = $this->DataManager->find($this->id);
        //14.09.2019 - prepare data for bulkInsert
        $mySection = $this->getSession('bulkInsert-' . $this->DataManager->getTableName());
        //bdump($mySection);
        $tmpData = $this->OrderItemsManager->findAll()->where('cl_order_id = ? AND quantity_rcv < quantity AND cl_pricelist_id IS NOT NULL', $this->id)->order('item_order ASC');
        $tmpArr = [];
        foreach ($tmpData as $key => $one) {
            $tmpArr[$one->cl_pricelist_id] = ['item_order' => $one->item_order, 'id' => $one->cl_pricelist_id,
                'identification' => $one->cl_pricelist->identification,
                'item_label' => $one->cl_pricelist->item_label,
                'quantity_ord' => $one->quantity,
                'quantity' => 0,
                'input_value' => $one->price_e];
        }

        //bdump($tmpData);
        $mySection['data'] = $tmpArr;

        $this->createDocShow = TRUE;
        $this->showModal('bulkInsert');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');

    }

    public function insertBulkData($data)
    {
        $tmpData = $this->DataManager->find($this->id);

        $this->createDocShow = FALSE;
        $this->hideModal('bulkInsert');

        //bdump($data);
        //15.09.2019 - write do cl_orders_items
        foreach ($data as $key => $one) {
            if (!is_null($one['id'])) {
                $tmpOrderItem = $this->OrderItemsManager->findAll()->where('cl_order_id = ? AND cl_pricelist_id = ?', $this->id, $one['id'])->fetch();
                if ($one['quantity'] > 0) {
                    $tmpUpdate = [];
                    $tmpUpdate['id'] = $tmpOrderItem->id;
                    $tmpUpdate['quantity_rcv'] = $one['quantity'];
                    $tmpUpdate['price_e_rcv'] = $one['input_value'];
                    $now = new DateTime();
                    $tmpUpdate['rea_date'] = $now;
                    $tmpOrderItem->update($tmpUpdate);
                }
            }
        }

        $mySection = $this->session->getSection('bulkInsert-' . $this->DataManager->getTableName());
        $mySection['data'] = [];
        $mySection['lastId'] = 0;
        $this->redrawControl('bulkInsertMain');

        $this->flashMessage($this->translator->translate('Položky_byly_zapsány_do_dokladu') . $tmpData->od_number, 'success');
        $this['orderlistgrid']->redrawControl('editLines');
        $this->redrawControl('contents');

    }


    //aditional function called before insert copied record
    public function beforeCopy($data)
    {
        unset($data['inv_numbers']);
        unset($data['dln_numbers']);
        unset($data['com_numbers']);
        unset($data['cl_documents_id']);
        unset($data['cl_store_docs_id_in']);
        unset($data['rea_date']);
        unset($data['od_date']);
        unset($data['req_date']);
        unset($data['cl_status_id']);
        unset($data['locked']);
        $dtmNow = new DateTime();
        $data['od_date'] = $dtmNow;

        if ($tmpStatus = $this->StatusManager->findAll()->where(['status_use' => 'order', 's_new' => 1])->fetch()) {
            $data['cl_status_id'] = $tmpStatus->id;
        }

        return $data;
    }

    //aditional function called after inserted copied record
    public function afterCopy($newLine, $oldLine)
    {
        $tmpOld = $this->DataManager->find($oldLine);
        $tmpNew = $this->DataManager->find($newLine);
        if ($tmpOld && $tmpNew) {
            //solve cl_order_items
            $tmpItems = $this->OrderItemsManager->findAll()->where('cl_order_id = ?', $tmpOld['id']);
            foreach ($tmpItems as $key => $one) {
                $newArr = $one->toArray();
                $newArr['cl_order_id'] = $tmpNew['id'];
                unset($newArr['id']);
                unset($newArr['cl_store_docs_id']);
                unset($newArr['reminder']);
                unset($newArr['price_e_rcv']);
                unset($newArr['rea_date']);
                unset($newArr['quantity_rcv']);
                $this->OrderItemsManager->insert($newArr);
            }


        }

        return TRUE;
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
        $form->onSuccess[] = [$this, "ImportEDIFormSubmited"];
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
            $this->flashMessage($this->translator->translate('Importováno_bylo') . $result . $this->translator->translate('položek'), 'success');
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
        try {
            $json_result = EDI\INH::import($file);
            $arrResult = json_decode($json_result, TRUE);
            //bdump($arrResult);
            //die;
            if (array_key_exists('error', $arrResult)) {
                throw new \Exception($arrResult['error']);
            }
            $counter = 0;
            foreach ($arrResult['success'] as $key => $one) {
                if ($one[1] == 'HDR') {
                    //header
                } elseif ($one[1] == 'HDD') {
                    //header2
                } elseif ($one[1] == 'LIN') {
                    //record
                    $orderItem = $this->OrderItemsManager->findAll()->where('cl_order_id = ? AND rea_date IS NULL AND quantity_rcv = 0', $this->id)->
                    where('(cl_pricelist.identification = ? OR cl_pricelist.ean_code = ?)', $one['identification'], $one['ean_code']);
                    $quantRcv = $one['quantity'];
                    $counterI = 0;
                    foreach ($orderItem as $keyI => $oneI) {
                        $tmpQuant = $oneI['quantity'] >= $quantRcv ? $quantRcv : $oneI['quantity'];
                        $quantRcv = $quantRcv - $tmpQuant;
                        $oneI->update(['quantity_rcv' => $tmpQuant, 'rea_date' => $one['rea_date'], 'price_e_rcv' => $one['price_in']]);
                        $counterI++;
                    }
                    $counter = $counter + $counterI;
                }
            }
            $this->DataManager->updateSum($this->id);
            return ($counter);
        } catch (\Exception $ex) {
            $this->flashMessage($this->translator->translate('Import_skončil_s_chybou') . $ex->getMessage(), 'warning');
            return 0;
        }
    }


}
