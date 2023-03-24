<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;

use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class TransportPresenter extends \App\Presenters\BaseListPresenter
{

    const
        DEFAULT_STATE = 'Czech Republic';


    public $newId = NULL;
    public $paymentModalShow = FALSE, $headerModalShow = FALSE, $footerModalShow = FALSE, $pairedDocsShow = FALSE, $createDocShow = FALSE, $checkedValues = FALSE;


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

    public $filterDeliveryNoteUsed = array();

    /**
     * @inject
     * @var \App\Model\CashManager
     */
    public $CashManager;

    /**
     * @inject
     * @var \App\Model\TransportItemsBackManager
     */
    public $TransportItemsBackManager;


    /**
     * @inject
     * @var \App\Model\TransportTypesManager
     */
    public $TransportTypesManager;

    /**
     * @inject
     * @var \App\Model\TransportDocsManager
     */
    public $TransportDocsManager;

    /**
     * @inject
     * @var \App\Model\TransportManager
     */
    public $DataManager;

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
     * @var \App\Model\DeliveryNoteItemsManager
     */
    public $DeliveryNoteItemsManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteItemsBackManager
     */
    public $DeliveryNoteItemsBackManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNotePaymentsManager
     */
    public $DeliveryNotePaymentsManager;

    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;

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
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;


    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;


    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;


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
     * @var \App\Model\InvoiceTypesManager
     */
    public $InvoiceTypesManager;


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
     * @var \App\Model\TransportCashManager
     */
    public $TransportCashManager;

    /**
     * @inject
     * @var \App\Model\PriceListBondsManager
     */
    public $PriceListBondsManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\NumberSeriesManager
     */
    public $NumberSeriesManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $InvoiceArrivedManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedPaymentsManager
     */
    public $InvoiceArrivedPaymentsManager;


    protected function createComponentEditTextDescription()
    {
        return new Controls\EditTextControl(
            $this->translator, $this->DataManager, $this->id, 'description_txt');
    }

    protected function createComponentPairedDocs()
    {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }

    protected function createComponentTextsUse()
    {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new TextsUseControl($this->DataManager, $this->id, 'transport', $this->TextsManager, $this->translator);
    }


    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        return new Controls\FilesControl(
            $this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_transport_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    public function createComponentDeliveryNoteBacklistgrid()
    {

        $tmpParentData = $this->DataManager->find($this->id);
        //$this->translator->setPrefix(['applicationModule.Transport']);
        $tmpProdej = $this->translator->translate("Prodej_bez_DPH");


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
        $arrPartners = $this->TransportDocsManager->findAll()->where('cl_transport_docs.cl_transport_id = ?', $tmpParentData->id)->
        select('cl_delivery_note.cl_partners_book_id AS id, CONCAT(cl_delivery_note.cl_partners_book.company, " - ", cl_delivery_note.cl_partners_branch.b_name) AS company')->fetchPairs('id', 'company');

        $arrStore = $this->StorageManager->getStoreTreeNotNested();
        if ($this->settings->platce_dph == 1) {
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20,
                    'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                    'values' => $arrStore,
                    'size' => 10, 'roCondition' => '$defData["changed"] != NULL'],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 7],
                'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'size' => 12, 'values' => $arrPartners],
                'commission' => [$this->translator->translate('Komise'), 'format' => 'boolean', 'size' => 10, 'readonly' => TRUE],
                'price_s' => [$this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE],
                'price_e' => [$tmpProdej, 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
                'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 8],
                'vat' => [$this->translator->translate('DPH_%'), 'format' => "number", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 5],
                'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 8],
                'cl_delivery_note.dn_number' => [$this->translator->translate('Dodací_list'), 'format' => "url", 'size' => 9, 'url' => 'deliverynote', 'value_url' => 'cl_delivery_note_id'],
                'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_delivery_note.cl_currencies_id', 'cl_pricelist.price']],
                'description1' => [$userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                'description2' => [$userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                'arrTools' => ['tools', [1 => ['url' => 'Report!', 'rightsFor' => 'enable', 'label' => 'DL', 'title' => 'aktualizovat dodací list', 'class' => 'btn btn-success btn-xs',
                    'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-wrench']
                ]
                ]
            ];
        } else {
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20,
                    'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                    'values' => $arrStore,
                    'size' => 10, 'roCondition' => '$defData["changed"] != NULL'],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 7],
                'cl_partners_book.company' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'size' => 12, 'values' => $arrPartners],
                'commission' => [$this->translator->translate('Komise'), 'format' => 'boolean', 'size' => 10, 'readonly' => TRUE],
                'price_s' => [$this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_mj, 'readonly' => TRUE],
                'price_e' => [$this->translator->translate('Prodej'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena],
                'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
                'price_e2' => [$this->translator->translate('Celkem'), 'format' => "number", 'size' => 8],
                'cl_delivery_note.dn_number' => [$this->translator->translate('Dodací_list'), 'format' => "url", 'size' => 9, 'url' => 'deliverynote', 'value_url' => 'cl_delivery_note_id'],
                'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_delivery_note.cl_currencies_id', 'cl_pricelist.price']],
                'description1' => [$userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                'description2' => [$userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE]];
        }

        /*if (!$this->user->isInRole('admin'))
        {
            unset($arrData['arrTools']);
        }*/

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->TransportItemsBackManager,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba, 'cl_storage_id' => $this->settings->cl_storage_id_back),
            $this->DataManager,
            $this->PriceListManager,
            $this->PriceListPartnerManager,
            TRUE,
            array('pricelist2' => $this->link('RedrawPriceList2!')
            ), //custom links
            TRUE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->onChange[] = function () {
            $this->updateSum();

        };
        $control->onPrint[] = function ($itemId) {
            $tmpData = $this->TransportItemsBackManager->findAll()->where('id = ?', $itemId)->fetch();
            if ($tmpData) {
                $this->handleRepairDN($tmpData['cl_delivery_note_id'], $tmpData['cl_partners_book_id'], $tmpData['cl_transport_id']);
                $this->flashMessage('DL byl opraven', 'success');
            } else {
                $this->flashMessage('Položka zpět nebyla nalezena, DL nebyl opraven', 'error');
            }

        };

        return $control;

    }

    protected function createComponentDeliveryNoteCashlistgrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //$this->translator->setPrefix(['applicationModule.Transport']);
        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id', 'currency_code');
        $arrData = array(
            'date' => array($this->translator->translate('Datum_nákupu'), 'format' => 'date', 'size' => 30),
            'amount' => array($this->translator->translate('Částka'), 'format' => 'currency', 'size' => 10),
            'cl_currencies.currency_name' => array($this->translator->translate('Měna'), 'format' => "text", 'size' => 10, 'values' => $arrCurrencies),
            'description' => array($this->translator->translate('Popis'), 'format' => 'textarea', 'size' => 50, 'rows' => 3),
            'cl_cash.cash_number' => array($this->translator->translate('Pokladní_doklad'), 'format' => "url", 'size' => 9, 'url' => 'cash', 'value_url' => 'cl_cash_id'),
        );

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->TransportCashManager,
            $arrData,
            array(),
            $this->id,
            array('cl_currencies_id' => $tmpParentData->cl_currencies_id, 'date' => $tmpParentData->transport_date),//default values
            $this->DataManager,
            NULL,
            NULL,
            TRUE,
            array() //custom links
        );
        $control->setContainerHeight("auto");
        $control->onChange[] = function () {
            //$this->updateSum();
        };

        return $control;

    }


    public function getDNValues($currId)
    {
        $tmpParentData = $this->DataManager->find($this->id);
        if (!$arrDN = $tmpParentData->related('cl_transport_docs')->
        select('cl_transport_docs.id,cl_transport_docs.cl_delivery_note_id')->
        where('cl_delivery_note_id IS NOT NULL')
            ->fetchPairs('cl_delivery_note_id', 'cl_delivery_note_id')) {
            $arrDN[0] = [0];
        }
        $tmpData = $this->TransportDocsManager->find($currId);
        //bdump($arrDN, 'arrDN');
        //bdump($currId, 'currId');
        $currDNId = (is_null($tmpData['cl_delivery_note_id'])) ? 0 : $tmpData['cl_delivery_note_id'];

        $arrPartners = $this->DeliveryNoteManager->findAll()
            ->select('cl_delivery_note.id,CONCAT(cl_delivery_note.dn_number," - ", IFNULL(cl_partners_book.company,""), "-" , IFNULL(cl_partners_branch.b_name,"")) AS dn_number')
            ->where('((cl_status.s_fin != 1 AND cl_status.s_storno != 1 AND cl_delivery_note.cl_transport_id IS NULL) AND cl_delivery_note.id NOT IN (?)) OR cl_delivery_note.id = ? ', $arrDN, $currDNId)
            ->order('issue_date DESC, id DESC')
            ->fetchPairs('id', 'dn_number');

        return $arrPartners;
    }

    protected function createComponentTransportDocslistgrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //$this->translator->setPrefix(['applicationModule.Transport']);
        $arrDN = $tmpParentData->related('cl_transport_docs')->
        select('cl_transport_docs.id,cl_transport_docs.cl_delivery_note_id')->
        where('cl_delivery_note_id IS NOT NULL')->fetchPairs('cl_delivery_note_id', 'cl_delivery_note_id');
        //bdump($arrDN);
        $arrPartners = $this->DeliveryNoteManager->findAll()
            ->select('cl_delivery_note.id,CONCAT(cl_delivery_note.dn_number," - ",cl_partners_book.company) AS dn_number')
            ->where('((cl_status.s_fin != 1 AND cl_status.s_storno != 1 AND cl_delivery_note.cl_transport_id IS NULL) OR cl_delivery_note.id IN (?)) ', $arrDN)
            ->order('issue_date DESC, id DESC');
        //->where('((cl_status.s_fin != 1 AND cl_status.s_storno != 1) OR cl_delivery_note.id IN (?)) ', $arrDN)

        //AND cl_delivery_note.cl_transport_id IS NULL
        /*if (!is_null($this->id)){
            $arrPartners = $arrPartners->where('cl_partners_book_id = ?', $this->id);
        }*/
        $arrPartners = $arrPartners->fetchPairs('id', 'dn_number');
        $arrData = array('cl_delivery_note.dn_number' => array($this->translator->translate('Dodací_list'), 'format' => 'select', 'size' => 25,
            'values' => $arrPartners, 'required' => 'Vyberte dodací list',
            'valuesFunction' => '$valuesToFill = $this->presenter->getDNValues($defData1["id"]);',
            'valuesFunctionName' => 'dn_number'),
            'cl_delivery_note.cl_partners_book.company' => array($this->translator->translate('Odběratel'), 'format' => 'noinput', 'size' => 15),
            'cl_delivery_note.cl_partners_branch.b_name' => array($this->translator->translate('Pobočka'), 'format' => 'noinput', 'size' => 15),
            'cl_delivery_note.price_e2_vat' => array($this->translator->translate('Částka'), 'format' => 'number', 'readonly' => true, 'size' => 8),
            'cl_delivery_note.cl_payment_types.name' => array($this->translator->translate('Druh_platby'), 'format' => "text", 'readonly' => true, 'size' => 9),
            'cl_delivery_note.price_payed' => array($this->translator->translate('Uhrazeno'), 'format' => 'number', 'readonly' => true, 'size' => 8),
            'price_payed' => array($this->translator->translate('Platba'), 'format' => 'number', 'readonly' => false, 'size' => 8),
            'delivered' => array($this->translator->translate('Dodáno'), 'format' => 'boolean', 'size' => 8),
            'main_dn' => array($this->translator->translate('Hlavní_dl'), 'format' => 'boolean', 'size' => 8),
            'only_for_pay' => array($this->translator->translate('Jen_platba'), 'format' => 'boolean', 'size' => 8),
            'weight' => array($this->translator->translate('Váha'), 'format' => 'number', 'size' => 8),
            'package_count' => array($this->translator->translate('Počet_balíků'), 'format' => 'number', 'size' => 8),
            'package_descr' => array($this->translator->translate('Popis_balíků'), 'format' => 'text', 'size' => 15),
            'package_type' => array($this->translator->translate('Typ_balíků'), 'format' => 'text', 'size' => 8, 'values' => $this->ArraysManager->getPackageTypes()),
            'cl_delivery_note.cl_invoice.inv_number' => array($this->translator->translate('Faktura'), 'format' => "url", 'size' => 9, 'url' => 'invoice', 'value_url' => 'cl_delivery_note.cl_invoice_id'),
            'dn_number___' => array($this->translator->translate('Dodací_list'), 'format' => "url", 'size' => 9, 'url' => 'deliverynote', 'value_url' => 'cl_delivery_note_id', 'function' => 'getDnNumber', 'function_param' => array('cl_delivery_note_id')),
            'note' => array($this->translator->translate('Poznámka'), 'format' => 'textarea', 'size' => 50, 'rows' => 3, 'newline' => true),
        );
        //'payed' =>array('Placeno', 'format' => 'boolean'),
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->TransportDocsManager,
            $arrData,
            array(),
            $this->id,
            array(),//default values
            $this->DataManager,
            NULL,
            NULL,
            TRUE,
            array() //custom links
        );
        $control->setContainerHeight("auto");
        $control->onChange[] = function () {
            //$this->updateSum();
        };

        return $control;

    }


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Transport']);
        $this->formName = $this->translator->translate("Doprava");
        $this->mainTableName = 'cl_transport';
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $arrData = array('tn_number' => $this->translator->translate('Číslo_závozu'),
            'cl_status.status_name' => array($this->translator->translate('Stav'), 'format' => 'colortag'),
            'transport_date' => array($this->translator->translate('Datum_závozu'), 'format' => 'date'),
            'transport_end_date' => array($this->translator->translate('Datum_ukončení'), 'format' => 'date'),
            'cl_transport_types.name' => array($this->translator->translate('Doprava'), 'format' => 'text'),
            'cl_users.name' => $this->translator->translate('Zpracoval'),
            'given_cash' => array($this->translator->translate('Předaná_hotovost'), 'format' => 'currency'),
            'recieved_cash' => array($this->translator->translate('Vrácená_hotovost'), 'format' => 'currency'),
            'cl_currencies.currency_name' => $this->translator->translate('Měna'),
            'created' => array($this->translator->translate('Vytvořeno'), 'format' => 'datetime'), 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => array($this->translator->translate('Změněno'), 'format' => 'datetime'), 'change_by' => $this->translator->translate('Změnil'));


        $this->dataColumns = $arrData;

        $this->filterColumns = array('tn_number' => '', 'cl_transport_types.name' => 'autocomplete',
            'cl_status.status_name' => 'autocomplete',
            'cl_users.name' => 'autocomplete', 'transport_date' => 'none');

        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('tn_number', 'cl_transport_types.name', 'cl_users.name');

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = array(':cl_transport_docs.cl_delivery_note.dn_number', ':cl_transport_docs.note', ':cl_transport_items_back.cl_pricelist.item_label',
            ':cl_transport_items_back.cl_pricelist.identification');


        $this->DefSort = 'transport_date DESC';

        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);

        $defDateDate = new \Nette\Utils\DateTime;

        $this->defValues = array('transport_date' => new \Nette\Utils\DateTime,
            'cl_company_branch_id' => $this->user->getIdentity()->cl_company_branch_id,
            'cl_users_id' => $this->user->getId(),
            'cl_currencies_id' => $this->settings->cl_currencies_id);
        //$this->numberSeries = 'commission';
        $this->numberSeries = array('use' => 'transport', 'table_key' => 'cl_number_series_id', 'table_number' => 'tn_number');

        $this->readOnly = array('tn_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE);


        $this->toolbar = array(0 => array('group_start' => ''),
            1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nová_doprava'), 'class' => 'btn btn-primary'),
            2 => $this->getNumberSeriesArray('transport'),
            3 => array('group_end' => ''));
        //bdump($this->toolbar);

        $this->rowFunctions = array('copy' => 'disabled');

        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = array('card' => array('active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Transport\card.latte'),
            'items' => array('active' => true, 'name' => $this->translator->translate('Dodací_listy'), 'lattefile' => $this->getLattePath() . 'Transport\deliverynotes.latte'),
            'itemsback' => array('active' => false, 'name' => $this->translator->translate('položky_zpět'), 'lattefile' => $this->getLattePath() . 'Transport\itemsback.latte'),
            'cash' => array('active' => false, 'name' => $this->translator->translate('nákupy'), 'lattefile' => $this->getLattePath() . 'Transport\cash.latte'),
            'memos' => array('active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'Transport\description.latte'),
            'files' => array('active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'Transport\files.latte')
        );

        //'items' => array('active' => false, 'name' => 'položky výdej', 'lattefile' => $this->getLattePath(). 'DeliveryNote\items.latte'),
//						'itemsback' => array('active' => false, 'name' => 'položky zpět', 'lattefile' => $this->getLattePath(). 'DeliveryNote\itemsback.latte'),

        $this->bscToolbar = array(
            1 => array('url' => 'closeTransport!', 'rightsFor' => 'write', 'label' => $this->translator->translate('ukončení_dopravy'), 'class' => 'btn btn-success',
                'data' => array('data-ajax="true"', 'data-history="false"', 'data-confirm="' . $this->translator->translate("Ano") . '"', 'data-cancel="' . $this->translator->translate("Ne") . '"', 'data-prompt="' . $this->translator->translate("Uzavřít_dopravu?_Provede_se_příjem_a_výdej_hotovosti_a_naskladnění_vrácených_položek.") . '"'),
                'icon' => 'glyphicon glyphicon-ok'),
            2 => array('url' => 'exportTransportData!', 'rightsFor' => 'read', 'label' => $this->translator->translate('export_dopravci'), 'class' => 'btn btn-success',
                'data' => array('data-ajax="false"', 'data-history="false"',),
                'icon' => 'glyphicon glyphicon-list-alt'),
            3 => array('url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('časté_texty'), 'class' => 'btn btn-success showTextsUse',
                'data' => array('data-ajax="true"', 'data-history="false"', 'data-not-check="1"'), 'icon' => 'glyphicon glyphicon-list'),
            4 => array('url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                'data' => array('data-ajax="true"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-list-alt'),
            5 => array('url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                'data' => array('data-ajax="false"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-print'),
            6 => array('url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                'data' => array('data-ajax="false"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-save'),
        );

        $this->bscTitle = array('tn_number' => $this->translator->translate('Číslo dopravy'), 'cl_transport_types.name' => $this->translator->translate('Doprava'));

        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData && $tmpData->cl_status['s_fin'] == 1) {
            unset($this->bscToolbar[1]);
        }

        //17.08.2018 - settings for documents saving and emailing
        //$this->docTemplate  =  __DIR__ . '/../templates/DeliveryNote/DeliveryNotev1.latte';
        $this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../templates/Transport/TransportNote.latte'); //Precistec

        $this->docAuthor = $this->user->getIdentity()->name . $this->translator->translate("z") . $this->settings->name;
        $this->docTitle = array($this->translator->translate("Závozová_karta"), "tn_number");

        //17.08.2018 - settings for sending doc by email
        $this->docEmail = array('template' => __DIR__ . '/../templates/DeliveryNote/emailDeliveryNote.latte',
            'emailing_text' => 'delivery_note');

        //$this->filterDeliveryNoteUsed	= array('filter' => 'cl_invoice_id IS NULL');

        /*$this->quickFilter = array('cl_invoice_types.name' => array('name' => 'Zvolte filtr zobrazení',
                                        'values' =>  $this->InvoiceTypesManager->findAll()->where('inv_type != ?',4)->fetchPairs('id','name'))
                        );	*/

        if ($this->isAllowed($this->presenter->name, 'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
        }

    }

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);


    }

    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);
        //bdump($id);
        //$this->template->RatesVatValid = $this->RatesVatManager->findAllValid($this->DataManager->find($id)->vat_date);
        //$this->template->arrInvoiceVat = $this->getArrInvoiceVat();

        //$this->template->paymentModalShow = $this->paymentModalShow;


    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addText('tn_number', $this->translator->translate('Číslo_závozu'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_závozu'));

        $form->addText('transport_date', $this->translator->translate('Datum_závozu'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_závozu'));

        $form->addText('transport_end_date', $this->translator->translate('Datum_ukončení'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_ukončení_dopravy'));

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_code');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_měnu'));

        $form->addText('given_cash', $this->translator->translate('Předaná_hotovost'), 15, 15)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Hotovost'));

        $form->addText('recieved_cash', $this->translator->translate('Vrácená_hotovost'), 15, 15)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Hotovost'));

        $arrTransportTypes = $this->TransportTypesManager->findAll()->where('deactive = 0')->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_transport_types_id', $this->translator->translate("Doprava:"), $arrTransportTypes)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_typ_dopravy'))
            ->setRequired($this->translator->translate('Vyberte_prosím_dopravu'))
            ->setPrompt($this->translator->translate('Zvolte_dopravu'));

        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'transport')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_dopravy'))
            ->setRequired($this->translator->translate('Vyberte_prosím_stav_dopravy'))
            ->setPrompt($this->translator->translate('Zvolte_stav_dopravy'));

        //28.12.2018 - have to set $tmpId for found right record it could be bscId or id
        if ($this->id == NULL) {
            $tmpId = $this->bscId;
        } else {
            $tmpId = $this->id;
        }


        $arrUsers = array();
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $form->addSelect('cl_users_id', $this->translator->translate("Zpracoval"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_pracovníka'))
            ->setPrompt($this->translator->translate('Zvolte pracovníka'));

        $form->onValidate[] = array($this, 'FormValidate');
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
        $form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        return $form;
    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function FormValidate(Form $form)
    {
        /*$data=$form->values;
        if ($data['cl_partners_book_id'] == NULL)
        {
            bdump($data,'validation');
            $form->addError($this->translator->translate('Partner musí být vybrán'));

        }*/
        if (!$this->bscOff) {
            $this->redrawControl('bscArea');
            $this->redrawControl('formedit');
            $this->redrawControl('baselistScripts');
        } else {
            $this->redrawControl('content');
        }

    }

    public function SubmitEditSubmitted(Form $form)
    {

        $data = $form->values;

        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {

            $data = $this->RemoveFormat($data);

            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {
                    $this->DataManager->update($data, TRUE);
                    $tmpData = $this->DataManager->find($data['id']);
                    /*05.07.2020 - make outcome cash for given cash*/
                    $tmpCashData = array();
                    $tmpCashData['cl_cash_id'] = $tmpData['given_cash_id'];
                    $tmpCashData['cl_company_branch_id'] = $tmpData['cl_company_branch_id'];
                    $tmpCashData['cl_transport_id'] = $data['id'];
                    $tmpCashData['inv_date'] = $data['transport_date'];
                    $tmpCashData['title'] = $this->translator->translate('Rozvoz_-_předaná_hotovost:_') . $tmpData['cl_transport_types']['name'];
                    $tmpCashData['cash'] = -$data['given_cash'];
                    $tmpCashData['cl_currencies_id'] = $data['cl_currencies_id'];
                    //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
                    //$tmpCashData['currency_rate']       = $one->cl_delivery_note['currency_rate'];
                    if ($data['given_cash'] != 0 || !is_null($tmpCashData['cl_cash_id'])) {
                        $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);
                    } else {
                        $tmpRetCashId = NULL;
                    }

                    if (!is_null($tmpRetCashId)) {
                        //$retDat = $this->CashManager->find($tmpRetCashId);
                        //$tmpDocs = $this->TransportCashManager->findAll()->where('cl_transport_id = ?', $id);
                        //$tmpDocs->update(array('cl_cash_id' => $tmpRetCashId));
                        $tmpData->update(array('given_cash_id' => $tmpRetCashId));
                    }


                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                }
            }

            //$this->redirect('default');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redrawControl('content');
            //$this->redirect('default');

        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redirect('default');

        }

    }


    public function emailSetStatus()
    {
        $this->setStatus($this->id, array('status_use' => 'transport',
            's_new' => 0,
            's_eml' => 1));
    }


    public function DataProcessMain($defValues, $data)
    {
        //$defValues['var_symb'] = preg_replace('/\D/', '', $defValues['inv_number']);

        //20.12.2018 - headers and footers
        //19.10.2019 - solved in BaseListPresenter->getNumberSeries
        //if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $defValues['cl_number_series_id']))->fetch()){
        //    $defValues['header_txt'] = $hfData['header_txt'];
        //    $defValues['footer_txt'] = $hfData['footer_txt'];
        //}

        return $defValues;
    }

    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }


    public function afterCopy($newLine, $oldLine)
    {
        //parent::afterCopy($newLine, $oldLine);
        $tmpItems = $this->DeliveryNoteItemsManager->findAll()->where('cl_delivery_note_id = ?', $oldLine);
        foreach ($tmpItems as $one) {
            $tmpOne = $one->toArray();
            $tmpOne['cl_delivery_note_id'] = $newLine;
            unset($tmpOne['id']);
            $this->DeliveryNoteItemsManager->insert($tmpOne);
        }

    }


    public function handlePairedDocs()
    {
        $this->pairedDocsShow = TRUE;
        $this->redrawControl('pairedDocs');
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
        $this->redrawControl('textUseControl');
        $this->redrawControl('textsUse');
        $this->pairedDocsShow = TRUE;
        $this->showModal('textsUseModal');

        //$this->redrawControl('contents');
    }


    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
        //dump($cl_partners_book_id);
        $arrUpdate = new \Nette\Utils\ArrayHash;
        $arrUpdate['id'] = $this->id;
        $arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL : $cl_partners_book_id);

        //dump($arrUpdate);
        //die;
        $this->DataManager->update($arrUpdate);

        $this['deliveryNotelistgrid']->redrawControl('pricelist2');
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
        $arrPrice['price_s'] = $sourceData['price_s'];
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
        } else {
            $arrPrice['price'] = $sourceData->price;
            $arrPrice['price_vat'] = $sourceData->price_vat;
            $arrPrice['discount'] = 0;
            $arrPrice['price_e2'] = $sourceData->price;
            $arrPrice['price_e2_vat'] = $sourceData->price_vat;
            $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
        }


        $arrPrice['vat'] = $sourceData->vat;

        $arrData = new \Nette\Utils\ArrayHash;
        $arrData[$this->DataManager->tableName . '_id'] = $this->id;

        $arrData['cl_pricelist_id'] = $sourcePriceData->id;
        $arrData['item_order'] = $this->TransportItemsBackManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;

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


        //prepocet kurzem
        //potrebujeme kurz ceníkove polozky a kurz zakazky
        /*		if ($sourceData->cl_currencies_id != NULL)
                    $ratePriceList = $sourceData->cl_currencies->fix_rate;
                else
                    $ratePriceList = 1;

                if ($tmpOrder = $this->DataManager->find($this->id))
                    $rateOrder = $tmpOrder->currency_rate;
                else
                    $rateOrder = 1;*/

        $ratePriceList = 1;
        $rateOrder = 1;

        //$arrData['price_s'] = $arrData['price_s'] * $ratePriceList / $rateOrder;

        $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOrder;
        $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOrder;
        $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOrder;


        //20.10.2020 - cl_storage_id
        if (!is_null($sourcePriceData['cl_storage_id'])) {
            $arrData['cl_storage_id'] = $sourcePriceData['cl_storage_id'];
        } else {
            $arrData['cl_storage_id'] = $tmpData->cl_company['cl_storage_id_back'];
        }

        if ($dataManager->tableName == 'cl_transport_items_back') {
            $tmpDefData = $this['deliveryNoteBacklistgrid']->getDefaultData();
        } elseif ($dataManager->tableName == 'cl_transport_items') {
            $tmpDefData = $this['deliveryNotelistgrid']->getDefaultData();
        }
        if (isset($tmpDefData['cl_storage_id']) && !is_null($tmpDefData['cl_storage_id']) && is_null($arrData['cl_storage_id'])) {
            $arrData['cl_storage_id'] = $tmpDefData['cl_storage_id'];
        }


        $row = $dataManager->insert($arrData);
        $this->updateSum($this->id, $this);
        return ($row);

    }

    //aditional processing data after save in listgrid
    //
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId, $name);
        //bdump($name);
        if ($name == "transportDocslistgrid") {
            $tmpItems = $this->TransportDocsManager->find($dataId);
            if ($tmpItems) {
                $this->DeliveryNoteManager->find($tmpItems->cl_delivery_note_id)->update(array('cl_transport_id' => $this->id));
                $this->PairedDocsManager->insertOrUpdate(array('cl_delivery_note_id' => $tmpItems->cl_delivery_note_id, 'cl_transport_id' => $this->id));

                $tmpPartnersBookId = $tmpItems->cl_delivery_note->cl_partners_book_id;
                //bdump($tmpItems['main_dn']);
                if ($tmpItems['main_dn'] == 1) {
                    $tmpDN = $this->TransportDocsManager->findAll()->
                    where('cl_transport_docs.cl_transport_id = ? AND cl_delivery_note.cl_partners_book_id = ? AND main_dn = 1', $this->id, $tmpPartnersBookId)->fetch();
                    //bdump($tmpDN);
                    if ($tmpDN) {
                        $tmpDN = $this->TransportDocsManager->findAll()->
                        where('cl_transport_docs.cl_transport_id = ? AND cl_delivery_note.cl_partners_book_id = ? AND cl_transport_docs.id != ?', $this->id, $tmpPartnersBookId, $dataId);

                        foreach ($tmpDN as $key => $one) {
                            $one->update(array('id' => $key, 'main_dn' => 0));
                        }
                    }
                } else {
                    $tmpPartnersBookId = (is_null($tmpPartnersBookId)) ? 0 : $tmpPartnersBookId;
                    $tmpDN = $this->TransportDocsManager->findAll()->
                    where('cl_transport_docs.cl_transport_id = ? AND cl_delivery_note.cl_partners_book_id = ? AND main_dn = 1', $this->id, $tmpPartnersBookId)->fetch();
                    //bdump($tmpDN);
                    if (!$tmpDN) {
                        $tmpItems->update(array('main_dn' => 1));
                    }
                }
            }
        } elseif ($name == "deliveryNoteBacklistgrid") {

            $tmpInvoiceItem = $this->TransportItemsBackManager->find($dataId);
            //bdump($tmpInvoiceItem);
            if (!is_null($tmpInvoiceItem->cl_pricelist_id)) {
                //find if there are bonds in cl_pricelist_bonds
                $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpInvoiceItem->cl_pricelist_id, $tmpInvoiceItem->quantity);
                bdump($tmpBonds);
                foreach ($tmpBonds as $key => $oneBond) {
                    //found in cl_invoice_items if there already is bonded item

                    $tmpInvoiceItemBond = $this->TransportItemsBackManager->findBy(array('cl_parent_bond_id' => $tmpInvoiceItem->id,
                        'cl_pricelist_id' => $oneBond->cl_pricelist_id))->fetch();

                    $newItem = $this->PriceListBondsManager->getBondData($oneBond, $tmpInvoiceItem);
                    $newItem['cl_transport_id'] = $this->id;

                    if (!$tmpInvoiceItemBond) {
                        $tmpNew = $this->TransportItemsBackManager->insert($newItem);
                        $tmpId = $tmpNew->id;
                    } else {
                        $newItem['id'] = $tmpInvoiceItemBond->id;
                        $tmpNew = $this->TransportItemsBackManager->update($newItem);
                        $tmpId = $tmpInvoiceItemBond->id;
                    }
                }
            }
        }
    }

    public function handleCloseTransport($id)
    {
        $tmpTransport = $this->DataManager->find($id);
        $cl_company_id = $this->settings->id;
        if ($tmpTransport) {
            $totalSum = $this->DataManager->transportSum($tmpTransport->cl_transport_types->name, $cl_company_id, $id);
            //bdump($totalSum);
            $arrData = array();
            $arrData['id'] = $id;
            $arrData['recieved_cash'] = $totalSum;
            $tmpStatus = 'transport';
            $dateEnd = new DateTime();
            if (is_null($tmpTransport['transport_end_date'])) {
                $arrData['transport_end_date'] = $dateEnd;
            }


            if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?', $tmpStatus, 1)->fetch())
                $arrData['cl_status_id'] = $nStatus->id;

            $this->DataManager->update($arrData);

            //now make moves to cl_cash
            $this->createCash($id);

            /*05.07.2020 - make income cash for returned cash which is exactly recieved_cash minus payed dl_notes */
            $tmpData = $this->DataManager->find($id);
            $sumData = $this->TransportDocsManager->findAll()->where('cl_transport_id = ?', $id)->
            where('cl_company_id = ?', $cl_company_id)->
            select('SUM(price_payed) AS price_payed')->fetch();
            $tmpCashData = array();
            $tmpCashData['cl_cash_id'] = $tmpData['recieved_cash_id'];
            $tmpCashData['cl_company_branch_id'] = $tmpData['cl_company_branch_id'];
            $tmpCashData['cl_transport_id'] = $tmpData['id'];
            //$tmpCashData['inv_date']            = $tmpData['transport_date'];
            //22.11.2020 - better current date
            $tmpCashData['inv_date'] = new \Nette\Utils\DateTime();
            $tmpCashData['title'] = 'Rozvoz - vrácená hotovost: ' . $tmpData['cl_transport_types']['name'];
            $tmpCashData['cash'] = $tmpData['recieved_cash'] - $sumData['price_payed'];
            $tmpCashData['cl_currencies_id'] = $tmpData['cl_currencies_id'];
            //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
            //$tmpCashData['currency_rate']       = $one->cl_delivery_note['currency_rate'];
            $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);

            if (!is_null($tmpRetCashId)) {
                //$retDat = $this->CashManager->find($tmpRetCashId);
                //$tmpDocs = $this->TransportCashManager->findAll()->where('cl_transport_id = ?', $id);
                //$tmpDocs->update(array('cl_cash_id' => $tmpRetCashId));
                $tmpData->update(array('recieved_cash_id' => $tmpRetCashId));
            }
            /*end of  make income cash for returned cash* */

            //at least make income of itemsback to store
            $this->createIncome($id);
            $tmpStatusId = NULL;
            $tmpStatus = 'delivery_note';
            if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?', $tmpStatus, 1)->fetch())
                $tmpStatusId = $nStatus->id;

            //set status of delivery_note to finished if is delivered
            foreach ($tmpTransport->related('cl_transport_docs')->where('delivered = 1') as $key => $one) {
                $tmpDN = $this->DeliveryNoteManager->find($one->cl_delivery_note_id);
                if ($tmpDN) {
                    $tmpDN->update(array('cl_status_id' => $tmpStatusId));
                }
            }

        }
        $this->flashMessage($this->translator->translate('Doprava_byla_ukončena'), 'success');
        //$this->redrawControl('bsc-child');
        //$this->redrawControl('content');
        $this->redirect('default');

    }

    private function createCash($id)
    {
        //íncome from cl_transport_docs
        $tmpData = $this->TransportDocsManager->findAll()->where('cl_transport_id = ? AND delivered = 1 AND price_payed > 0', $id);
        foreach ($tmpData as $key => $one) {
            $tmpCashData = array();
            $tmpCashData['cl_cash_id'] = $one->cl_cash_id;
            //$tmpCashData['cl_users_id']         = $one->cl_transport['cl_users_id'];
            $tmpCashData['cl_users_id'] = $this->user->getId();
            $tmpCashData['cl_company_branch_id'] = $one->cl_transport['cl_company_branch_id'];
            $tmpCashData['cl_delivery_note_id'] = $one['cl_delivery_note_id'];
            $tmpCashData['cl_partners_book_id'] = $one->cl_delivery_note->cl_partners_book_id;
            $tmpCashData['inv_date'] = $one->cl_transport->transport_end_date;
            if (!is_null($one->cl_delivery_note['cl_invoice_id'])) {
                $tmpCashData['title'] = $this->translator->translate('Úhrada_faktury') . $one->cl_delivery_note->cl_invoice['inv_number'];
            } else {
                $tmpCashData['title'] = $this->translator->translate('Úhrada_dodacího_listu') . $one->cl_delivery_note['dn_number'];
            }
            $tmpCashData['title'] = $tmpCashData['title'] . ' - ' . $one->cl_transport->cl_transport_types['name'];
            //$tmpCashData['cash']                = $one->cl_delivery_note['price_e2_vat'];
            $tmpCashData['cash'] = $one->price_payed;
            $tmpCashData['cl_currencies_id'] = $one->cl_delivery_note['cl_currencies_id'];
            //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
            $tmpCashData['currency_rate'] = $one->cl_delivery_note['currency_rate'];
            $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);

            if (!is_null($tmpRetCashId)) {
                //$retDat = $this->CashManager->find($tmpRetCashId);
                $one->update(array('cl_cash_id' => $tmpRetCashId));
                //bdump($one->cl_cash->cash_number);
            }
            //cl_cash
            //15.05.2020 - make payment to cl_delivery_note_pay and update total sum on cl_delivery_note.price_payed
            $tmpDnPay = array();
            $tmpDnPay['cl_cash_id'] = $tmpRetCashId;
            $tmpDnPay['cl_transport_docs_id'] = $one['id'];
            $tmpDnPay['cl_delivery_note_id'] = $one['cl_delivery_note_id'];
            $arrPay = $this->PaymentTypesManager->findAll()->where('payment_type = 1')->order('name')->limit(1)->fetch();
            if ($arrPay) {
                $tmpDnPay['cl_payment_types_id'] = $arrPay->id;
            }
            $tmpDnPay['pay_doc'] = $one->cl_cash->cash_number;
            $tmpDnPay['pay_date'] = $one->cl_transport->transport_end_date;
            $tmpDnPay['pay_price'] = $one->price_payed;
            $tmpDnPay['cl_currencies_id'] = $one->cl_delivery_note['cl_currencies_id'];
            //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
            //$tmpDnPay['currency_rate']          = $one->cl_delivery_note['currency_rate'];
            if ($tmpDnp = $this->DeliveryNotePaymentsManager->findAll()->where('cl_transport_docs_id = ? ', $one['id'])->fetch()) {
                $tmpDnPay['id'] = $tmpDnp->id;
                $this->DeliveryNotePaymentsManager->update($tmpDnPay);
                unset($tmpDnPay['id']);
            } else {
                $this->DeliveryNotePaymentsManager->insert($tmpDnPay);
            }
            $this->DeliveryNoteManager->updateSum($one['cl_delivery_note_id']);

            //20.05.2020 - next step is to write the same payment to cl_invoice_payments
            if (!is_null($one->cl_delivery_note->cl_invoice_id)) {
                unset($tmpDnPay['cl_delivery_note_id']);
                $tmpDnPay['cl_invoice_id'] = $one->cl_delivery_note->cl_invoice_id;
                if ($tmpInvp = $this->InvoicePaymentsManager->findAll()->where('cl_transport_docs_id = ? ', $one['id'])->fetch()) {
                    $tmpDnPay['id'] = $tmpInvp->id;
                    $this->InvoicePaymentsManager->update($tmpDnPay);
                    unset($tmpDnPay['id']);
                } else {
                    $this->InvoicePaymentsManager->insert($tmpDnPay);
                }
                $this->InvoiceManager->paymentUpdate($tmpDnPay['cl_invoice_id']);
            }
        }
        //

        //outcome from cl_transport_items_back
        /*        $tmpData = $this->TransportItemsBackManager->findAll()->
                                            select('SUM(price_e2_vat) AS price_e2_vat,cl_transport.cl_users_id, cl_partners_book_id, cl_cash_id,
                                                                                            cl_transport.transport_date, cl_transport.cl_currencies_id, cl_transport.id AS cl_transport_id')->
                                            where('cl_transport_id = ?', $id)->
                                            group('cl_partners_book_id');
                foreach ($tmpData as $key => $one) {
                    $tmpCashData = array();
                    $tmpCashData['cl_cash_id']          = $one->cl_cash_id;
                    $tmpCashData['cl_users_id']         = $one->cl_users_id;
                    $tmpCashData['cl_transport_id']     = $one->cl_transport_id;
                    $tmpCashData['cl_partners_book_id'] = $one->cl_partners_book_id;
                    $tmpCashData['inv_date']            = $one->transport_date;
                    $tmpCashData['title']               = 'Vrácené položky z rozvozu';
                    $tmpCashData['cash']                = -$one['price_e2_vat'];
                    $tmpCashData['cl_currencies_id']    = $one->cl_currencies_id;
                    //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
                    $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);

                    if (!is_null($tmpRetCashId) && !is_null($one->cl_partners_book_id)) {
                        $tmpDocs = $this->TransportItemsBackManager->findAll()->where('cl_transport_id = ? AND cl_partners_book_id =  ?', $id, $one->cl_partners_book_id);
                        $tmpDocs->update(array('cl_cash_id' => $tmpRetCashId));
                    }
                }
        */


        //outcome from cl_transport_cash
        $tmpData = $this->TransportCashManager->findAll()->where('cl_transport_id = ?', $id);
        foreach ($tmpData as $key => $one) {
            //make cl_invoice_arrived
            $tmpInvoiceArrived = $this->createInvoiceArrived($one);

            $tmpCashData = array();
            $tmpCashData['cl_cash_id'] = $one->cl_cash_id;
            $tmpCashData['cl_users_id'] = $one->cl_users_id;
            $tmpCashData['cl_company_branch_id'] = $one->cl_transport['cl_company_branch_id'];
            $tmpCashData['cl_transport_id'] = $one->cl_transport_id;
            $tmpCashData['cl_transport_cash_id'] = $one->id;
            $tmpCashData['inv_date'] = $one['date'];
            $tmpCashData['title'] = $this->translator->translate('Nákup_při_rozvozu') . $one['description'];
            $tmpCashData['cash'] = -$one['amount'];
            $tmpCashData['cl_invoice_arrived_id'] = $tmpInvoiceArrived['id'];
            $tmpCashData['cl_currencies_id'] = $one->cl_currencies_id;
            //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury.
            //$tmpCashData['currency_rate']       = $one->cl_delivery_note['currency_rate'];

            $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);

            if (!is_null($tmpRetCashId)) {
                //$retDat = $this->CashManager->find($tmpRetCashId);
                //$tmpDocs = $this->TransportCashManager->findAll()->where('cl_transport_id = ?', $id);
                //$tmpDocs->update(array('cl_cash_id' => $tmpRetCashId));
                $one->update(array('cl_cash_id' => $tmpRetCashId, 'cl_invoice_arrived_payments_id' => $tmpInvoiceArrived['cl_invoice_arrived_payments_id']));
                $tmpPayment = $this->InvoiceArrivedPaymentsManager->findAll()->where('id = ?', $tmpInvoiceArrived['cl_invoice_arrived_payments_id'])->fetch();
                if ($tmpPayment) {
                    $tmpPayment->update(array('cl_cash_id' => $tmpRetCashId));
                }
            }
            //create paired docs
            $this->PairedDocsManager->insertOrUpdate(array('cl_cash_id' => $tmpRetCashId, 'cl_invoice_arrived_id' => $tmpInvoiceArrived['id']));
        }
    }

    public function createInvoiceArrived($one)
    {
        //find cl_store_docs record
        //$tmpNow = new \Nette\Utils\DateTime;
        //'inv_date' => $tmpNow->format('Y-m-d H:i:s'),
        $arrInvoice = array(
            'inv_date' => $one['date'],
            'vat_date' => $one['date'],
            'arv_date' => $one['date'],
            'inv_title' => $one['description'],
            'cl_currencies_id' => $one['cl_currencies_id'],
            'currency_rate' => 1);
        $numberSeries = array('use' => 'invoice_arrived', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number');
        $nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
        $arrInvoice[$numberSeries['table_key']] = $nSeries['id'];
        $arrInvoice[$numberSeries['table_number']] = $nSeries['number'];

        $tmpStatus = $numberSeries['use'];
        $nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch();
        if ($nStatus) {
            $arrInvoice['cl_status_id'] = $nStatus->id;
        }
        $tmpInvoiceTypes = $this->InvoiceTypesManager->findAll()->where('inv_type = ?', '4')->order('default_type DESC')->fetch();
        if ($tmpInvoiceTypes) {
            $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceTypes->id;
        } else {
            $arrInvoice['cl_invoice_types_id'] = NULL;
        }
        $tmpPaymentTypeId = $this->PaymentTypesManager->findAll()->where('payment_type IN (1,3)')->limit(1)->order('payment_type')->fetch();
        $arrInvoice['cl_payment_types_id'] = $tmpPaymentTypeId;
        $arrInvoice['due_date'] = $one['date'];
        //$arrInvoice['cl_store_docs_id' ]    = $tmpStoreDoc->id;
        $arrInvoice['cl_company_branch_id'] = $this->user->getIdentity()->cl_company_branch_id;
        if (is_null($one['cl_invoice_arrived_id'])) {
            $invoice = $this->InvoiceArrivedManager->insert($arrInvoice);
            $arrInvoice['id'] = $invoice->id;
        } else {
            $arrInvoice['id'] = $one['cl_invoice_arrived_id'];
            $invoice = $this->InvoiceArrivedManager->update($arrInvoice);
        }


        //20.02.2019 - update cl_store_docs with cl_invoice_arrived_id
        $one->update(array('cl_invoice_arrived_id' => $arrInvoice['id']));
        //   }else {

        //$arrInvoice['price_e2'] = $arrInvoice['price_base3'] + $arrInvoice['price_base2'] + $arrInvoice['price_base1'] + $arrInvoice['price_base0'];
        $validRates = $this->RatesVatManager->findAllValid($one['date']);
        $arrInvoiceVat = array();
        $arrInvoice['price_base0'] = 0;
        $arrInvoice['price_base1'] = 0;
        $arrInvoice['price_base2'] = 0;
        $arrInvoice['price_base3'] = 0;
        $arrInvoice['price_vat1'] = 0;
        $arrInvoice['price_vat2'] = 0;
        $arrInvoice['price_vat3'] = 0;
        $arrInvoice['price_e2'] = 0;
        $arrInvoice['price_e2_vat'] = 0;
        foreach ($validRates as $keyR => $oneR) {
            $arrInvoiceVat[$oneR['rates']] = array('base' => 0,
                'vat' => 0,
                'payed' => 0,
                'vatpayed' => 0);
        }

        if (isset(array_keys($arrInvoiceVat)[0])) {
            $arrInvoice['vat1'] = array_keys($arrInvoiceVat)[0];
        }
        if (isset(array_keys($arrInvoiceVat)[1])) {
            $arrInvoice['vat2'] = array_keys($arrInvoiceVat)[1];
        }
        if (isset(array_keys($arrInvoiceVat)[2])) {
            $arrInvoice['vat3'] = array_keys($arrInvoiceVat)[2];
        }
        $arrInvoice['price_base1'] = $one['amount'] / (1 + ($arrInvoice['vat1'] / 100));
        $arrInvoice['price_vat1'] = $one['amount'] * ($arrInvoice['vat1'] / 100);
        $arrInvoice['price_e2'] = $arrInvoice['price_base1'];
        $arrInvoice['price_e2_vat'] = $one['amount'];
        //bdump($arrInvoice);
        $updatedInvoice = $this->InvoiceArrivedManager->update($arrInvoice);


        //create pairedocs record
        $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_arrived_id' => $arrInvoice['id'], 'cl_transport_id' => $one->cl_transport_id));


        //total sums of invoice
        $this->InvoiceArrivedManager->updateInvoiceSum($arrInvoice['id']);

        //create payment
        $arrPayment = array();
        $arrPayment['cl_invoice_arrived_id'] = $arrInvoice['id'];
        $arrPayment['cl_cash_id'] = $one->cl_cash_id;
        $arrPayment['cl_currencies_id'] = $arrInvoice['cl_currencies_id'];
        $arrPayment['cl_payment_types_id'] = $arrInvoice['cl_payment_types_id'];
        $arrPayment['pay_date'] = $arrInvoice['inv_date'];
        $arrPayment['pay_price'] = $arrInvoice['price_e2_vat'];
        $arrPayment['pay_doc'] = $this->translator->translate('úhrada');
        if (!is_null($one->cl_invoice_arrived_payments_id)) {
            $tmpInvA = $this->InvoiceArrivedPaymentsManager->find($one->cl_invoice_arrived_payments_id);
            if (!$tmpInvA) {

            } else {
                $tmpInvA->update($arrPayment);
                $tmpInvoiceArrivedPaymentId = $tmpInvA->id;
            }
        } else {
            $tmp = $this->InvoiceArrivedPaymentsManager->insert($arrPayment);
            $tmpInvoiceArrivedPaymentId = $tmp->id;
        }
        $this->InvoiceArrivedManager->paymentUpdate($arrInvoice['id']);
        return array('id' => $arrInvoice['id'], 'cl_invoice_arrived_payments_id' => $tmpInvoiceArrivedPaymentId);
    }


    private function createIncome($id)
    {
        //only items given back without cl_delivery_note_id which means items recieved back from common delivery_notes
        //in another step we have to solve items recived back from commission delivery_note
        $tmpDataParents = $this->TransportItemsBackManager->findAll()->
        select('cl_partners_book_id, cl_transport.cl_currencies.currency_code, cl_transport.tn_number, cl_transport.create_by')->
        where('cl_transport_id = ? ', $id)->
        group('cl_partners_book_id');
//AND cl_transport_items_back.cl_delivery_note_id IS NULL

        foreach ($tmpDataParents as $key => $one) {
            $companyId = $one->cl_partners_book_id;
            $cl_company_branch_id = $this->user->getIdentity()->cl_company_branch_id;

            $tmpStorage = $this->StorageManager->findAll()->where('for_return_package = 1')->fetch();
            if ($tmpStorage) {
                $cl_storage_id_return = $tmpStorage->id;
            } else {
                $cl_storage_id_return = NULL;
            }
            $tmpNow = new DateTime();
            $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(array('cl_company_id' => $this->settings->id,
                'doc_date' => $tmpNow,  //$tmpOutgoing->doc_date
                'cl_partners_book_id' => $companyId,
                'currency_code' => $one->currency_code,
                'cl_storage_id' => NULL,
                'cl_company_branch_id' => $cl_company_branch_id,
                'doc_title' => $this->translator->translate('příjem_z_rozvozu') . ' ' . $one->tn_number,
                'doc_type' => 'store_in',
                'create_by' => $one->create_by));


            $tmpData = $this->TransportItemsBackManager->findAll()->
                                where('cl_transport_id = ? AND cl_partners_book_id = ?', $id, $one->cl_partners_book_id);
            foreach ($tmpData as $key1 => $one1) {
                $data = $one1->toArray();
                $s_in = $data['quantity'];
                $data['s_in'] = 0;
                $data['s_end'] = 0;
                $data['s_out'] = 0;
                $data['s_out_fin'] = 0;
                $data['s_total'] = 0;
                $data['price_in'] = $data['price_s'];
                $data['price_in_vat'] = $data['price_s'] * (1 + ($data['vat'] / 100));
                $data['price_e2'] = 0;
                $data['price_e2_vat'] = 0;
                //if (!is_null($one1->cl_pricelist->cl_pricelist_group_id) && $one1->cl_pricelist->cl_pricelist_group->is_return_package == 1)
                if ($data['cl_storage_id'] == 0) {
                    $data['cl_storage_id'] = $cl_storage_id_return;
                }

                $data['cl_store_id'] = NULL;
                $data['cl_invoice_items_id'] = NULL;
                $data['cl_invoice_items_back_id'] = NULL;
                $data['cl_store_docs_id'] = $tmpDocs->id;
                unset($data['id']);
                unset($data['cl_delivery_note_items_back_id']);
                unset($data['cl_store_docs_in_id']);
                unset($data['cl_delivery_note_id']);
                unset($data['cl_partners_book_id']);
                unset($data['cl_transport_id']);
                unset($data['quantity']);
                unset($data['cl_cash_id']);
                unset($data['item_label']);
                unset($data['units']);
                unset($data['price_e_type']);
                unset($data['cl_partners_event_id']);
                unset($data['description1']);
                unset($data['description2']);
                unset($data['cl_invoice_id']);
                unset($data['cl_store_move_id']);
                unset($data['commission']);
                unset($data['cl_parent_bond_id']);
                unset($data['item_type']);
                $data['change_by'] = '';
                $data['changed'] = NULL;
                $data['create_by'] = 'apk';
                $data['created'] = new \Nette\Utils\DateTime;

                $row = $this->StoreMoveManager->insert($data);
                $data['id'] = $row->id;
                $data['s_in'] = $s_in;
                $data['s_end'] = $s_in;
                $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocs);
                $this->StoreMoveManager->update($data2);

                $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
            }
            $this->StoreManager->UpdateSum($tmpDocs->id);
            $this->PairedDocsManager->insertOrUpdate(['cl_store_docs_id' => $tmpDocs->id, 'cl_delivery_note_id' => $one1->cl_delivery_note_id]);
        }

        //items recived back from commission delivery_note
        $tmpDataParents = $this->TransportItemsBackManager->findAll()->select('cl_delivery_note.cl_partners_book_id, cl_transport.cl_currencies.currency_code, 
                                                                                        cl_transport.tn_number, cl_transport.create_by, 
                                                                                        cl_transport_items_back.cl_delivery_note_id, cl_pricelist_id')->
        where('cl_transport_items_back.cl_transport_id = ? AND cl_transport_items_back.cl_delivery_note_id IS NOT NULL AND cl_delivery_note.commission = 1', $id)->
        group('cl_delivery_note.cl_partners_book_id');
        foreach ($tmpDataParents as $key => $one) {
            $tmpData = $this->TransportItemsBackManager->findAll()->
            where('cl_transport_items_back.cl_transport_id = ? AND cl_delivery_note.cl_partners_book_id = ? AND cl_delivery_note_id IS NOT NULL AND cl_transport_items_back.commission = 1', $id, $one->cl_partners_book_id);
            foreach ($tmpData as $key1 => $one1) {
                $tmpData = $this->DeliveryNoteItemsBackManager->find($one1['cl_delivery_note_items_back_id']);
                //1. prepare data for item
                $newItem = new \Nette\Utils\ArrayHash;
                $quantityName = 'quantity';
                $newItem['price_e_type'] = $this->settings->price_e_type;
                $newItem['item_label'] = $one1->cl_pricelist->item_label;
                $newItem['units'] = $one1->cl_pricelist->unit;
                $newItem['cl_pricelist_id'] = $one1->cl_pricelist_id;
                $newItem['cl_storage_id'] = $one1->cl_storage_id;
                $newItem[$quantityName] = $one1->quantity;

                $newItem['price_s'] = $one1->cl_pricelist->price_s;
                $newItem['price_e'] = $one1->cl_pricelist->price;
                $newItem['discount'] = $one1->discount;
                $newItem['price_e2'] = ($one1->cl_pricelist->price * (1 - ($one1->discount / 100))) * ($one1->quantity);
                $newItem['vat'] = $one1->cl_pricelist->vat;
                $newItem['price_e2_vat'] = $one1->cl_pricelist->price_vat * (1 - ($one1->discount / 100)) * ($one1->quantity);
                $docId = $one1->cl_delivery_note['cl_store_docs_id'];
                $newItem['cl_delivery_note_id'] = $one1->cl_delivery_note_id;
                if (!$tmpData) {
                    $i = $this->DeliveryNoteItemsBackManager->findAll()->where('cl_delivery_note_id = ?', $one1['cl_delivery_note_id'])->max('item_order') + 1;
                    $newItem['item_order'] = $i;
                    //back items - store in
                    //$docId = $this->StoreDocsManager->createStoreDoc(0, $one1['cl_delivery_note_id'], $this->DeliveryNoteManager);
                    $tmpNew = $this->DeliveryNoteItemsBackManager->insert($newItem);
                    $one1->update(array('cl_delivery_note_items_back_id' => $tmpNew['id']));
                    $newItem['id'] = $tmpNew['id'];
                } else {
                    //update
                    $newItem['id'] = $one1['cl_delivery_note_items_back_id'];
                    $tmpNew = $this->DeliveryNoteItemsBackManager->update($newItem);

                }
                $dataIdtmp = $this->StoreManager->giveInItem($docId, $newItem['id'], $this->DeliveryNoteItemsBackManager);

                //2. store in current item


                /* old way
                //find item on delivery_note and make minus returned quantity
                $item = $this->DeliveryNoteItemsManager->findAll()->
                                    where('cl_delivery_note_id = ? AND cl_pricelist_id = ?',
                                                $one->cl_delivery_note_id, $one->cl_pricelist_id)->
                                    limit(1)->fetch();
                //update quantity
                $item->update(array('quantity' => $item->quantity - $one1->quantity));

                $dataIdtmp = $this->StoreManager->giveOutItem($item->cl_store_move->cl_store_docs_id , $item->id, $this->DeliveryNoteItemsManager);
                */
            }
        }

    }


    public function getDnNumber($arr)
    {
        $ret = $this->DeliveryNoteManager->find($arr['cl_delivery_note_id']);
        if ($ret) {
            $ret = $ret['dn_number'];
        }
        return $ret;
    }

    public function handleRepairDN($cl_delivery_note_id, $cl_partners_book_id, $cl_transport_id)
    {
        $itemsBack = $this->TransportItemsBackManager->findAllTotal()->where(array('cl_partners_book_id ' => $cl_partners_book_id, 'commission' => 0,
            'cl_transport_id' => $cl_transport_id, 'cl_delivery_note_id' => $cl_delivery_note_id));
        //echo($cl_partners_book_id);
        //echo($cl_transport_id);
        //bdump($itemsBack);
        foreach ($itemsBack as $keyI => $oneI) {

            $arrData = $oneI->toArray();
            unset($arrData['id']);
            unset($arrData['cl_transport_id']);
            unset($arrData['cl_parent_bond_id']);
            unset($arrData['cl_cash_id']);
            unset($arrData['commission']);
            unset($arrData['item_type']);
            $arrData['cl_transport_items_back_id'] = $oneI->id;
            //$arrData['cl_delivery_note_id'] = $mainDnId;
            //bdump($arrData);
            $tmpDNBItem = $this->DeliveryNoteItemsBackManager->findAll()->where(array('cl_transport_items_back_id' => $oneI->id))->fetch();
            if ($tmpDNBItem) {
                $arrData['id'] = $tmpDNBItem['id'];
                $this->DeliveryNoteItemsBackManager->updateForeign($arrData);
            } else {
                $this->DeliveryNoteItemsBackManager->insertForeign($arrData);
            }
        }

        $this->DeliveryNoteManager->updateSum($cl_delivery_note_id);
        $this->PairedDocsManager->insertOrUpdate(array('cl_delivery_note_id' => $cl_delivery_note_id, 'cl_transport_id' => $cl_transport_id));

        //create income to store

        $tmpDataParents = $this->TransportItemsBackManager->findAll()->
        select('cl_partners_book_id, cl_transport.cl_currencies.currency_code, cl_transport.tn_number, cl_transport.create_by')->
        where('cl_transport_id = ? AND cl_transport_items_back.cl_delivery_note_id = ? ', $cl_transport_id, $cl_delivery_note_id)->
        group('cl_partners_book_id');
//AND cl_transport_items_back.cl_delivery_note_id IS NULL

        foreach ($tmpDataParents as $key => $one) {
            $companyId = $one->cl_partners_book_id;
            $cl_company_branch_id = $this->user->getIdentity()->cl_company_branch_id;

            $tmpStorage = $this->StorageManager->findAll()->where('for_return_package = 1')->fetch();
            if ($tmpStorage) {
                $cl_storage_id_return = $tmpStorage->id;
            } else {
                $cl_storage_id_return = NULL;
            }
            $tmpNow = new DateTime();
            $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(array('cl_company_id' => $this->settings->id,
                'doc_date' => $tmpNow,  //$tmpOutgoing->doc_date
                'cl_partners_book_id' => $companyId,
                'currency_code' => $one->currency_code,
                'cl_storage_id' => NULL,
                'cl_company_branch_id' => $cl_company_branch_id,
                'doc_title' => $this->translator->translate('příjem_z_rozvozu') . $one->tn_number,
                'doc_type' => 'store_in',
                'create_by' => $one->create_by));


            $tmpData = $this->TransportItemsBackManager->findAll()->
            where('cl_transport_id = ? AND cl_transport_items_back.cl_delivery_note_id = ? AND cl_partners_book_id = ?', $cl_transport_id, $cl_delivery_note_id, $cl_partners_book_id);
            //where('cl_transport_id = ? AND cl_partners_book_id = ?', $id, $one->cl_partners_book_id);
            foreach ($tmpData as $key1 => $one1) {
                $data = $one1->toArray();
                $s_in = $data['quantity'];
                $data['s_in'] = 0;
                $data['s_end'] = 0;
                $data['s_out'] = 0;
                $data['s_out_fin'] = 0;
                $data['s_total'] = 0;
                $data['price_in'] = $data['price_s'];
                $data['price_in_vat'] = $data['price_s'] * (1 + ($data['vat'] / 100));
                $data['price_e2'] = 0;
                $data['price_e2_vat'] = 0;
                //if (!is_null($one1->cl_pricelist->cl_pricelist_group_id) && $one1->cl_pricelist->cl_pricelist_group->is_return_package == 1)
                if ($data['cl_storage_id'] == 0) {
                    $data['cl_storage_id'] = $cl_storage_id_return;
                }

                $data['cl_store_id'] = NULL;
                $data['cl_invoice_items_id'] = NULL;
                $data['cl_invoice_items_back_id'] = NULL;
                $data['cl_store_docs_id'] = $tmpDocs->id;
                unset($data['id']);
                unset($data['cl_delivery_note_items_back_id']);
                unset($data['cl_store_docs_in_id']);
                unset($data['cl_delivery_note_id']);
                unset($data['cl_partners_book_id']);
                unset($data['cl_transport_id']);
                unset($data['quantity']);
                unset($data['cl_cash_id']);
                unset($data['item_label']);
                unset($data['units']);
                unset($data['price_e_type']);
                unset($data['cl_partners_event_id']);
                unset($data['description1']);
                unset($data['description2']);
                unset($data['cl_invoice_id']);
                unset($data['cl_store_move_id']);
                unset($data['commission']);
                unset($data['cl_parent_bond_id']);
                unset($data['item_type']);
                $data['change_by'] = '';
                $data['changed'] = NULL;
                $data['create_by'] = 'apk';
                $data['created'] = new \Nette\Utils\DateTime;

                $row = $this->StoreMoveManager->insert($data);
                $data['id'] = $row->id;
                $data['s_in'] = $s_in;
                $data['s_end'] = $s_in;
                $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocs);
                $this->StoreMoveManager->update($data2);

                $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
            }
            $this->StoreManager->UpdateSum($tmpDocs->id);
            $this->PairedDocsManager->insertOrUpdate(array('cl_store_docs_id' => $tmpDocs->id, 'cl_delivery_note_id' => $one1->cl_delivery_note_id));
        }


    }

    public function beforeDelete($lineId, $name = "")
    {
        $result = TRUE;
        //bdump($name);
        if ($name == 'transportDocslistgrid') {
            if ($tmpLine = $this->TransportDocsManager->find($lineId)) {
                if (!is_null($tmpLine->cl_delivery_note_id)) {
                    $this->DeliveryNoteManager->find($tmpLine->cl_delivery_note_id)->update(array('cl_transport_id' => NULL));
                    $tmpLine->update(array('cl_delivery_note_id' => NULL));
                }
            }
        }

        return $result;
    }

    public function handleExportTransportData()
    {
        $dn = $this->TransportDocsManager->findAll()->where('cl_transport_id = ?', $this->id);
        $parent = $this->DataManager->find($this->id);
        if ($dn && $parent && $parent->cl_transport_types['transport'] == 6) {
            //DHL
            $filename = $parent->cl_transport_types['name'];
            $arrData = $this->DataManager->prepareDHL($dn);
            $this->sendResponse(new \CsvResponse\NCsvResponse($arrData, $filename . "-" . date('Ymd-Hi') . ".csv", true, ';', NULL, NULL, [], FALSE, ''));
        }elseif ($dn && $parent && $parent->cl_transport_types['transport'] == 7) {
            //PPL
            $filename = $parent->cl_transport_types['name'];
            $arrData = $this->DataManager->preparePPL($dn);
            $this->sendResponse(new \CsvResponse\NCsvResponse($arrData, $filename . "-" . date('Ymd-Hi') . ".csv", false, ';', 'CP1250', NULL, [], FALSE,''));
        }
    }

}

    


