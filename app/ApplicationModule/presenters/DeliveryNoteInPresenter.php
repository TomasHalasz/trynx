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

class DeliveryNoteInPresenter extends \App\Presenters\BaseListPresenter
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
     * @var \App\Model\DeliveryNoteInManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteInManager
     */
    public $DeliveryNoteInManager;


    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteInItemsManager
     */
    public $DeliveryNoteInItemsManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteInItemsBackManager
     */
    public $DeliveryNoteInItemsBackManager;

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
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;


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
        // $translator = clone $this->translator;
        // $translator->setPrefix([]);
        // $translator = clone $this->translator->setPrefix([]);
        return new TextsUseControl($this->DataManager, $this->id, 'delivery_note_in', $this->TextsManager, $this->translator);
    }


    protected function createComponentSumOnDocs()
    {
        //$this->translator->setPrefix(['applicationModule.DeliveryNote']);
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

                    array('name' => $tmpPriceNameVat, 'value' => $data->price_e2_vat, 'currency' => $tmpCurrencies),
                );
            } else {
                $dataArr = array(
                    array('name' => $tmpPriceNameBase, 'value' => $data->price_e2, 'currency' => $tmpCurrencies),
                );
            }
        } else {
            $dataArr = array();
        }
        //$translator = clone $this->translator->setPrefix([]);
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
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl($this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_delivery_note_in_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    public function createComponentDeliveryNotelistgrid()
    {

        $tmpParentData = $this->DataManager->find($this->id);
        //$this->translator->setPrefix(['applicationModule.DeliveryNote']);
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
        //bdump($arrStore);
        //bdump($this['invoicelistgrid'],"test");
        //$arrStore = $this->StorageManager->getStoreTreeNotNestedAmount($this['invoicelistgrid']["editLine"]["cl_pricelist_id"]->value);
        if ($this->settings->platce_dph == 1) {
            //'values' => $arrStore,
            //'valuesFunction' => '$valuesToFill = $this->presenter->StoreManager->getStoreTreeNotNestedAmount($this["editLine"]["cl_pricelist_id"]->value);',
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                        'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20,
                        'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                        'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                        'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                                                'values' => $arrStore,
                                                'size' => 10, 'roCondition' => '$defData["changed"] != NULL'],
                        'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                        'units' => ['', 'format' => 'text', 'size' => 7, 'e100p' => "false", 'readonly' => TRUE],
                        'price_in' => [$this->translator->translate('Nákupní_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                        'price_in_vat' => [$this->translator->translate('Nákupní_cena_s_dph'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                        'price_e2' => [$this->translator->translate('Celkem_bez_dph'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                        'vat' => [$this->translator->translate('DPH_%'), 'format' => "chzn-select-req", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 6, 'e100p' => "false"],
                        'price_e2_vat' => [$this->translator->translate('Celkem_s_dph'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                        'cl_pricelist.price' => ['Prodej bez DPH', 'format' => 'number', 'size' => 8, 'readonly' => TRUE, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                        'profit' => ['Zisk %', 'format' => 'number', 'size' => 8, 'readonly' => TRUE, 'e100p' => "false", 'function' => 'getProfitStoreIn', 'function_param' => ['cl_pricelist.price', 'price_s']],
                        'order_number' => [$this->translator->translate('Objednávka'), 'format' => 'text', 'size' => 10, 'e100p' => "false"],
                        'exp_date' => ['Expirace', 'format' => 'date', 'size' => 15, 'required' => 'Expirace musí být zadána', 'newline' => TRUE, 'e100p' => "false"],
                        'batch' => ['Šarže', 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"],
                        'description' => ['Poznámka', 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"]];
            // array( 'quantity' => 100, 'price' => 45),array( 'quantity' => 200, 'price' => 40), array( 'quantity' => 300, 'price' => 30)
            //'values' => array('100' => '45', '200' => '40', '300' => '30')
        } else {
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20],
                'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                    'values' => $arrStore,
                    'size' => 10, 'roCondition' => '$defData["changed"] != NULL'],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 7, 'e100p' => "false", 'readonly' => TRUE],
                'price_in' => [$this->translator->translate('Nákupní_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'price_e2' => [$this->translator->translate('Celkem'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                'cl_pricelist.price' => ['Prodej bez DPH', 'format' => 'number', 'size' => 8, 'readonly' => TRUE, 'decplaces' => $this->settings->des_cena, 'e100p' => "false"],
                'profit' => ['Zisk %', 'format' => 'number', 'size' => 8, 'readonly' => TRUE, 'e100p' => "false", 'function' => 'getProfitStoreIn', 'function_param' => ['cl_pricelist.price', 'price_s']],
                'order_number' => [$this->translator->translate('Objednávka'), 'format' => 'text', 'size' => 10, 'e100p' => "false"],
                'exp_date' => ['Expirace', 'format' => 'date', 'size' => 15, 'required' => 'Expirace musí být zadána', 'newline' => TRUE, 'e100p' => "false"],
                'batch' => ['šarže', 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"],
                'description' => ['poznámka', 'format' => "text", 'size' => 50, 'newline' => TRUE, 'e100p' => "false"]];

        }
        /*if ($this->settings->invoice_to_store == 0) {
            unset($arrData['cl_storage.name']);
            unset($arrData['price_s']);
        }*/
        if (!$this->settings->exp_on) {
            unset($arrData['exp_date']);
        }
        if (!$this->settings->batch_on) {
            unset($arrData['batch']);
        }

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->DeliveryNoteInItemsManager,
            $arrData,
            [],
            $this->id,
            ['units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba],
            $this->DataManager,
            $this->PriceListManager,
            $this->PriceListPartnerManager,
            FALSE,
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
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }

    public function createComponentDeliveryNoteBacklistgrid()
    {
        //$this->translator->setPrefix(['applicationModule.DeliveryNote']);
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
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                    'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20,
                    'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                    'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                    'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                                            'values' => $arrStore,
                                            'size' => 10, 'roCondition' => '$defData["changed"] != NULL'],
                    'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                    'units' => ['', 'format' => 'text', 'size' => 7],
                    'price_s' => [$this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'readonly' => TRUE],
                    'price_e' => [$this->translator->translate('Výdej_bez_DPH'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                    'price_e2' => [$this->translator->translate('Celkem_bez_dph'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                    'vat' => [$this->translator->translate('DPH_%'), 'format' => "chzn-select-req", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 6, 'e100p' => "false"],
                    'price_e2_vat' => [$this->translator->translate('Celkem_s_dph'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                    'description1' => [$userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                    'description2' => [$userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE]];
        } else {
            $arrData = ['cl_pricelist.identification' => ['Kód', 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                    'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 20,
                    'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0'],
                    'cl_pricelist_id' => [$this->translator->translate('Položka_ceníku'), 'format' => 'hidden'],
                    'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'chzn-select-req',
                        'values' => $arrStore,
                        'size' => 10, 'roCondition' => '$defData["changed"] != NULL'],
                    'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                    'units' => ['', 'format' => 'text', 'size' => 7],
                    'price_s' => [$this->translator->translate('Skladová_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'readonly' => TRUE],
                    'price_e' => [$this->translator->translate('Výdej_cena'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                    'price_e2' => [$this->translator->translate('Celkem_bez_dph'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena, 'e100p' => 'true'],
                    'description1' => [$userTmpAdapt['cl_invoice_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                    'description2' => [$userTmpAdapt['cl_invoice_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE]];
        }


        $control = new Controls\ListgridControl(
            $this->translator,
            $this->DeliveryNoteInItemsBackManager,
            $arrData,
            [],
            $this->id,
            ['units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba, 'cl_storage_id' => $this->settings->cl_storage_id_back],
            $this->DataManager,
            $this->PriceListManager,
            $this->PriceListPartnerManager,
            FALSE,
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
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }

    public function handleRedrawDueDate2(){

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


    protected function startup()
    {
        parent::startup();
        ////$this->translator->setPrefix(['applicationModule.deliverynote']);
        //$this->translator->setPrefix(['applicationModule.DeliveryNote']);
        $this->formName = $this->translator->translate("Dodací_listy_přijaté");
        $this->mainTableName = 'cl_delivery_note_in';

        //$settings = $this->CompaniesManager->getTable()->fetch();
        if ($this->settings->platce_dph == 1) {
            $arrData = ['dn_number' => $this->translator->translate('Interní_číslo_dod.listu'),
                'rdn_number' => $this->translator->translate('Externí_číslo_dod.listu'),
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'issue_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                'delivery_date' => [$this->translator->translate('Dodáno'), 'format' => 'date'],
                'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'format' => 'text', 'show_clink' => true],
                'delivery_date' => [$this->translator->translate('Dodáno'), 'format' => 'date'],
                'dn_title' => [$this->translator->translate('Poznámka'), 'format' => 'text'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_bez_DPH'), 'format' => 'currency'],
                'price_e2_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => 'currency'],
                'cl_storage.name' => $this->translator->translate('Sklad'),
                'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'od_number' => $this->translator->translate('Číslo_objednávky'),
                'cl_invoice_arrived.rinv_number' => $this->translator->translate('Číslo_faktury_přijaté'),
                'cl_store_docs.doc_number' => $this->translator->translate('Číslo_příjemky'),
                'vehicle_plate' => ['RZ vozidla', 'format' => "text", 'size' => 8],                                
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        } else {
            $arrData = ['dn_number' => $this->translator->translate('Interní_číslo_dod.listu'),
                'rdn_number' => $this->translator->translate('Externí_číslo_dod.listu'),
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'issue_date' => [$this->translator->translate('Vystaveno'), 'format' => 'date'],
                'delivery_date' => [$this->translator->translate('Dodáno'), 'format' => 'date'],
                'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'format' => 'text', 'show_clink' => true],
                'delivery_date' => [$this->translator->translate('Dodáno'), 'format' => 'date'],
                'dn_title' => [$this->translator->translate('Poznámka'), 'format' => 'text'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'price_e2' => [$this->translator->translate('Cena_celkem'), 'format' => 'currency'],
                'price_payed' => [$this->translator->translate('Zaplaceno'), 'format' => 'currency'],
                'cl_storage.name' => $this->translator->translate('Sklad'),
                'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'od_number' => $this->translator->translate('Číslo_objednávky'),
                'cl_invoice_arrived.rinv_number' => $this->translator->translate('Číslo_faktury_přijaté'),
                'cl_store_docs.doc_number' => $this->translator->translate('Číslo_příjemky'),
                'vehicle_plate' => ['RZ vozidla', 'format' => "text", 'size' => 8],                                
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        }
        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = ['dn_number' => '', 'cl_partners_book.company' => 'autocomplete',
            'cl_status.status_name' => 'autocomplete','vehicle_plate' => 'autocomplete',
            'cl_users.name' => 'autocomplete', 'inv_date' => 'none', 'cl_payment_types.name' => 'autocomplete',
            'issue_date' => 'none', 'delivery_date' => '', 'cl_invoice.inv_number' => 'autocomplete', 'cl_store_docs.doc_number' => 'autocomplete', 'cl_partners_branch.b_name' => 'autocomplete',
            'price_e2' => '', 'price_e2_vat' => ''];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['cl_delivery_note_in.dn_number', 'cl_delivery_note_in.od_number', 'cl_invoice.inv_number', 'cl_partners_book.company', 'dn_title', 'cl_delivery_note_in.price_e2_vat', 'cl_delivery_note_in.price_e2'];


        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_delivery_note_in_items.item_label', ':cl_delivery_note_in_items.cl_pricelist.identification', ':cl_delivery_note_in_items.cl_pricelist.item_label',
            ':cl_delivery_note_in_items.description1', ':cl_delivery_note_in_items.description2'];

        $this->DefSort = 'issue_date DESC';

        /*$testDate = new \Nette\Utils\DateTime;
        $testDate = $testDate->modify('-1 day');
        $this->conditionRows = array( array('due_date','<=',$testDate, 'color:red', 'lastcond'), array('price_payed','<=','price_e2_vat', 'color:green'));
         *
         */
        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);

        /*$this->conditionRows = array( array('due_date','<',$testDate, 'color:red', 'notlastcond'),
                          array('pay_date','==',NULL, 'color:red', 'lastcond'),
                          array('due_date','>=',$testDate, 'color:green', 'notlastcond'),
                          array('pay_date','==',NULL, 'color:green', 'lastcond'));	*/


        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;

        //08.10.2019 - default storage for company branch
        $cl_storage_id = NULL;
        $tmpCompanyBranchId = $this->user->getIdentity()->cl_company_branch_id;
        if (!is_null($tmpCompanyBranchId)) {
            if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('id = ?', $tmpCompanyBranchId)->limit(1)->fetch())
                $cl_storage_id = $tmpBranch->cl_storage_id;
        } else {
            $cl_storage_id = $this->settings->cl_storage_id;
        }


        $defDueDate = new \Nette\Utils\DateTime;

        $this->defValues = ['issue_date' => new \Nette\Utils\DateTime,
            'delivery_date' => new \Nette\Utils\DateTime,
            'cl_company_branch_id' => $this->user->getIdentity()->cl_company_branch_id,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'currency_rate' => $this->settings->cl_currencies->fix_rate,
            'cl_payment_types_id' => $this->settings->cl_payment_types_id,
            'cl_storage_id' => $cl_storage_id,
            'price_e_type' => $this->settings->price_e_type,
            'price_off' => $this->settings->dn_price_off,
            'cl_users_id' => $this->user->getId()];
        //$this->numberSeries = 'commission';
        $this->numberSeries = ['use' => 'delivery_note_in', 'table_key' => 'cl_number_series_id', 'table_number' => 'dn_number'];
        $this->readOnly = ['dn_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE];


        $this->toolbar = [0 => ['group_start' => ''],
            1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_dodací_list'), 'class' => 'btn btn-primary'],
            2 => $this->getNumberSeriesArray('delivery_note_in'),
            3 => ['group_end' => ''],
            9 => ['group' =>
                [0 => ['url' => $this->link('report!', ['index' => 1]),
                    'rightsFor' => $this->translator->translate('report'),
                    'label' => $this->translator->translate('Kniha_dodacích_listů'),
                    'title' => $this->translator->translate('Dodací_listy_přijaté_ve_zvoleném_období'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => 'iconfa-print'],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Tisk'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
            ]
        ];
        $this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/DeliveryNoteIn/ReportDNBookSettings.latte',
            'reportName' => 'Kniha dodacích listů']
        ];

        $this->rowFunctions = ['copy' => 'disabled'];


        //settings for CSV attachments
        $this->csv_h = ['columns' => 'dn_number,issue_date,delivery_date,dn_title,cl_partners_book.company,cl_partners_book_workers.worker_name,cl_currencies.currency_code,price_e2,price_e2_vat,price_correction,price_base0,price_base1,price_base2,price_base3,
                                            price_vat1,price_vat2,price_vat3,vat1,vat2,vat3,price_payed,cl_delivery_note_in.header_txt,cl_delivery_note_in.footer_txt,storno'];
        $this->csv_i = ['columns' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_delivery_note_in_items.item_label,cl_pricelist.order_label,cl_delivery_note_in_items.quantity,cl_delivery_note_in_items.units,cl_storage.name AS storage_name,cl_delivery_note_in_items.price_e,cl_delivery_note_in_items.discount,cl_delivery_note_in_items.price_e2,cl_delivery_note_in_items.price_e2_vat,cl_delivery_note_in_items.vat',
            'datasource' => 'cl_delivery_note_in_items'];

        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'DeliveryNoteIn\card.latte'],
            'items' => ['active' => true, 'name' => $this->translator->translate('položky_příjem'), 'lattefile' => $this->getLattePath() . 'DeliveryNoteIn\items.latte'],
            'itemsback' => ['active' => false, 'name' => $this->translator->translate('položky_zpět'), 'lattefile' => $this->getLattePath() . 'DeliveryNoteIn\itemsback.latte'],
            'memos' => ['active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'DeliveryNoteIn\description.latte'],
            'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'DeliveryNoteIn\files.latte']
        ];

        $this->bscSums = ['lattefile' => $this->getLattePath() . 'DeliveryNoteIn\sums.latte'];
        $this->bscToolbar = [0 => ['group' =>
            [
                1 => ['url' => 'removeInvoiceBond!',
                    'rightsFor' => 'report',
                    'label' => $this->translator->translate('Zrušit_vazbu_na_fakturu'),
                    'title' => '',
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => 'glyphicon glyphicon-edit']
            ],
            'group_settings' =>
                ['group_label' => $this->translator->translate('Nástroje'),
                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                    'group_title' => $this->translator->translate('Práce_s_fakturou'), 'group_icon' => 'glyphicon glyphicon-wrench']
        ],
            1 => ['url' => 'createInvoiceArrived!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Faktura'), 'title' => $this->translator->translate('vytvoří_nebo_zaktualizuje_fakturu_přijatou'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            2 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('časté_texty'), 'class' => 'btn btn-success showTextsUse',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'], 'icon' => 'glyphicon glyphicon-list'],
            3 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
            4 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            5 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
            6 => ['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => $this->translator->translate('E-mail'), 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send'],
        ];
        $this->bscTitle = ['dn_number' => $this->translator->translate('Číslo_dod_listu'), 'cl_partners_book.company' => $this->translator->translate('Dodavatel')];

        /*8 => array('url' => 'createInvoiceModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Faktura'), 'title' => $this->translator->translate('vytvoří_nebo_zaktualizuje_fakturu'), 'class' => 'btn btn-success',
        'data' => array('data-ajax="true"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-edit'),
        */
        //17.08.2018 - settings for documents saving and emailing
        //$this->docTemplate  =  __DIR__ . '/../templates/DeliveryNote/DeliveryNotev1.latte';
        $this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../templates/DeliveryNoteIn/DeliveryNoteInv2.latte'); //Precistec

        $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
//			$this->docTitle = array($this->translator->translate("Dodací_list"), "dn_number");
        if ($this->settings['pdf_name_type'] == 0) {
            $this->docTitle = ["", "cl_partners_book.company", "dn_number"];

        } elseif ($this->settings['pdf_name_type'] == 1) {
            $this->docTitle = ["", "dn_number", "cl_partners_book.company"];
        }

        //17.08.2018 - settings for sending doc by email
        $this->docEmail = ['template' => __DIR__ . '/../templates/DeliveryNoteIn/emailDeliveryNote.latte',
            'emailing_text' => 'delivery_note_in'];

        $this->filterDeliveryNoteUsed = ['filter' => 'cl_invoice_id IS NULL'];

        $pdCount = count($this->pdFilter);
        $pdCount2 = $pdCount;

        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => '(cl_invoice_id IS NOT NULL AND cl_invoice.dn_is_origin = 1 AND (cl_invoice.price_e2_vat != ROUND(cl_delivery_note_in.price_e2_vat,2) OR cl_invoice.price_e2 != ROUND(cl_delivery_note_in.price_e2,2)))',
            'sum' => ['(cl_delivery_note_in.price_e2_vat) * cl_delivery_note_in.currency_rate' => 's DPH'],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Rozdíl_proti_faktuře'),
            'title' => $this->translator->translate('Všechny_dodací_listy_s_rozdílem_proti_faktuře'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];

        $pdCount2 = $pdCount;
        if ($this->settings->platce_dph == 0) {
            $this->pdFilter[++$pdCount2]['sum'] = ['(price_e2) * currency_rate' => 'celkem'];
        }
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
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData && !is_null($tmpData->cl_invoice_id)) {
            $this->checkedValues = $this->DataManager->findAll()->where('cl_invoice_id = ? ', $tmpData->cl_invoice_id)->select('id')->fetchPairs('id', 'id');
        } else {
            $this->checkedValues = $this->DataManager->findAll()->where('id = ? ', $this->id)->select('id')->fetchPairs('id', 'id');
        }
    }

    protected function getArrInvoiceVat()
    {
        $tmpData = $this->DataManager->find($this->id);
        $arrRatesVatValid = $this->RatesVatManager->findAllValid($tmpData->issue_date);
        $arrInvoiceVat = [];
        foreach ($arrRatesVatValid as $key => $one) {
            if ($tmpData->vat1 == $one['rates']) {
                $baseValue = $tmpData->price_base1;
                $vatValue = $tmpData->price_vat1;
            } elseif ($tmpData->vat2 == $one['rates']) {
                $baseValue = $tmpData->price_base2;
                $vatValue = $tmpData->price_vat2;
            } elseif ($tmpData->vat3 == $one['rates']) {
                $baseValue = $tmpData->price_base3;
                $vatValue = $tmpData->price_vat3;

            } else {
                $baseValue = 0;
                $vatValue = 0;

            }

            $arrInvoiceVat[$one['rates']] = array('base' => $baseValue,
                'vat' => $vatValue);
        }
        return ($arrInvoiceVat);

    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addText('dn_number', $this->translator->translate('Interní_číslo_dod.listu'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Interní_číslo_dod.listu'));
        $form->addText('od_number', $this->translator->translate('Číslo_objednávky'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_objednávky'));
        $form->addText('rdn_number', $this->translator->translate('Externí_číslo_dod.listu'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Externí_číslo_dod.listu'));
        $form->addText('rinv_number', $this->translator->translate('Číslo_faktury_přijaté'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_faktury_přijaté'))
            ->addConditionOn($form['rdn_number'], $form::BLANK)
                ->setRequired($this->translator->translate( 'Číslo_faktury_nebo_dodacího_listu_musí_být_vyplněno'));
        $form->addText('dn_title', $this->translator->translate('Poznámka'), 60, 200)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Poznámka'));
        $form->addText('issue_date', $this->translator->translate('Vystavení'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vystavení'));
        $form->addText('delivery_date', $this->translator->translate('Dodání'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_dodání'));
        $form->addText('vehicle_plate', $this->translator->translate('RZ_vozidla'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('RZ_vozidla'));            

        $arrStorage = $this->StorageManager->getStoreTreeNotNested();
        $form->addSelect('cl_storage_id', $this->translator->translate("Sklad"), $arrStorage)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-url-change_storage', $this->link('changeStorage!'))
            ->setPrompt($this->translator->translate('Zvolte_sklad'));

        $tmpId = (is_null($this->bscId) ? $this->id : $this->bscId);

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate("Středisko"), $arrCenter)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
            ->setPrompt($this->translator->translate('Zvolte_středisko'));

        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'delivery_note_in')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_dodacího_listu'))
            ->setRequired($this->translator->translate('Vyberte_prosím_stav_dodacího_listu'))
            ->setPrompt($this->translator->translate('Zvolte_stav_dodacího_listu'));

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

        $form->addSelect('cl_partners_book_id', $this->translator->translate("Dodavatel"), $arrPartners)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_dodavastele'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($this->translator->translate('Zvolte_dodavatele'));

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

        //$form->addTextArea('footer_txt', 'Zápatí:', 100,3 )
        //	->setHtmlAttribute('placeholder','Text v zápatí faktury');
        $form->onValidate[] = array($this, 'FormValidate');
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('store_out', $this->translator->translate('Vydat_ze_skladu'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
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

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        /*02.12.2020 - cl_partners_book_id required and prepare data for just created partner
        */
        $data = $this->updatePartnerId($data);
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0) {
            $form->addError('Partner musí být vybrán');
        }

        $this->redrawControl('content');


    }

    public function SubmitEditSubmitted(Form $form)
    {

        $data = $form->values;

        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() || $form['send_fin']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy() || $form['store_out']->isSubmittedBy()) {

            $data = $this->RemoveFormat($data);

            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {

                    $this->DataManager->update($data, TRUE);
                    $this->DeliveryNoteInManager->updateSum($this->id);

                    if ($tmpData = $this->DataManager->find($data['id'])) {
                        //unvalidate document for downloading
                        $tmpDocuments = array();
                        $tmpDocuments['id'] = $tmpData->cl_documents_id;
                        $tmpDocuments['valid'] = 0;
                        $newDocuments = $this->DocumentsManager->update($tmpDocuments);
                        //update cl_store_docs.doc_date if there is outgoing document
                        if (!is_null($tmpData['cl_store_docs_id'])) {
                            $this->StoreDocsManager->update(['id' => $tmpData['cl_store_docs_id'],
                                                            'doc_date' => $tmpData['delivery_date'],
                                                            'invoice_number' => $tmpData['rinv_number'],
                                                            'delivery_number' => $tmpData['rdn_number']]);

                        }
                        if (!is_null($tmpData['cl_store_docs_id_in'])) {
                            $this->StoreDocsManager->update(['id' => $tmpData['cl_store_docs_id_in'],
                                                            'doc_date' => $tmpData['delivery_date'],
                                                            'invoice_number' => $tmpData['rinv_number'],
                                                            'delivery_number' => $tmpData['rdn_number']]);

                        }
                        $this->PairedDocsManager->insertOrUpdate(['cl_delivery_note_in_id' => $this->id,
                                                                    'cl_invoice_arrived_id' => $tmpData['cl_invoice_arrived_id']]);
                    }
                   // $this->UpdatePairedDocs($data);


                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                } else {
                    //$row=$this->DataManager->insert($data);
                    //$this->newId = $row->id;
                    //$this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            } else {
                //$this->flashMessage('Změny nebyly uloženy.', 'success');
            }

            //$this->redirect('default');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redrawControl('content');

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

    public function UpdatePairedDocs($data){
        $tmpPaired = $this->PairedDocsManager->findAll()->where('cl_invoice_arrived_id IS NOT NULL AND cl_delivery_note_in_id = ?', $data['id'])->fetch();
        if ($tmpPaired && !is_null($tmpPaired['cl_invoice_in_id'])){
            $this->InvoiceArrivedManager->update(['id' => $tmpPaired['cl_invoice_id'],
                                            'cl_partners_book_id' => $data['cl_partners_book_id'],
                                            'cl_center_id' => $data['cl_center_id'],
                                            'cl_partners_branch_id' => $data['cl_partners_branch_id'],
                                            'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                            'cl_users_id' => $data['cl_users_id']
                                        ]);
        }

        $tmpPaired = $this->PairedDocsManager->findAll()->where('cl_delivery_note_in_id = ? AND cl_store_docs_id IS NOT NULL', $data['id']);
        foreach ($tmpPaired as $key => $one){
            $this->StoreDocsManager->update(['id' => $one['cl_store_docs_id'],
                                            'cl_partners_book_id' => $data['cl_partners_book_id'],
                                            'cl_center_id' => $data['cl_center_id'],
                                            'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                            'cl_users_id' => $data['cl_users_id']
            ]);
        }
//        if ($tmpPaired && !is_null($tmpPaired['cl_store_docs_id'])) {
//        }
    }



    /*public function handleGetCurrencyRate($idCurrency)
    {
        if ($rate = $this->CurrenciesManager->findOneBy(array('id' => $idCurrency)))
            echo($rate->fix_rate);
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
                $recalcItems = $this->DeliveryNoteInItemsManager->findBy(array('cl_delivery_note_in_id' => $this->id));
                //$recalcItems = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => array($this->id,827,829,799,769,770,757,740,741,742,721,718)));
                foreach ($recalcItems as $one) {
                    //$data = array();
                    //$data['price_s'] = $one['price_s'] * $oldrate / $rate;

                    //if ($this->settings->platce_dph == 1)
                    $data['price_e'] = $one['price_e'] * $oldrate / $rate;
                    $data['price_e2'] = $one['price_e2'] * $oldrate / $rate;
                    $data['price_e2_vat'] = $one['price_e2_vat'] * $oldrate / $rate;

                    $one->update($data);
                }
            }

            //we must save parent data
            $parentData = array();
            $parentData['id'] = $this->id;
            if ($rate <> $oldrate)
                $parentData['currency_rate'] = $rate;

            $parentData['cl_currencies_id'] = $idCurrency;
            $this->DataManager->update($parentData);


            $this->UpdateSum($this->id, $this);

        }
        $this->redrawControl('items');

    }


    public function UpdateSum()
    {
        $this->DeliveryNoteInManager->updateSum($this->id);
        parent::UpdateSum();
        $this['deliveryNotelistgrid']->redrawControl('editLines');

        //$this['sumOnDocs']->redrawControl();
    }

    public function beforeAddLine($data)
    {
        //parent::beforeAddLine($data);
        //dump($data['control_name']);
        if ($data['control_name'] == "deliveryNotelistgrid") {
            $data['price_e_type'] = $this->settings->price_e_type;
        }
        return $data;
    }

    public function ListGridInsert($sourceData, $dataManager)
    {
        $arrPrice = [];
        //if (isset($sourceData['cl_pricelist_id']))
        if (array_key_exists('cl_pricelist_id', $sourceData->toArray())) {
            $arrPrice['id'] = $sourceData['cl_pricelist_id'];
            $sourcePriceData = $this->PriceListManager->find($sourceData->cl_pricelist_id);
        } else {
            $arrPrice['id'] = $sourceData['id'];
            $sourcePriceData = $this->PriceListManager->find($sourceData->id);
        }
        $arrPrice['price_s'] = $sourcePriceData['price_s'];
        $arrPrice['cl_currencies_id'] = $sourcePriceData['cl_currencies_id'];

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

        }


        $arrPrice['vat'] = $sourceData->vat;

        $arrData = array();
        $arrData[$this->DataManager->tableName . '_id'] = $this->id;

        $arrData['cl_pricelist_id'] = $sourcePriceData->id;
        $arrData['item_order'] = $dataManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;

        $arrData['item_label'] = $sourcePriceData->item_label;
        $arrData['quantity'] = 1;

        $arrData['units'] = $sourcePriceData->unit;

        $arrData['vat'] = $arrPrice['vat'];

        $arrData['price_s'] = $arrPrice['price_s'];

        if ($dataManager->tableName == 'cl_delivery_note_in_items') {
            //income price
            $tmpParentData = $this->DataManager->find($this->id);
            if ($this->settings->platce_dph == 1) {
                //if (isset($sourceData['price_s']))
                if (array_key_exists('price_s', $sourceData->toArray())) {
                    $tmpPrices = $sourceData->price_s;
                } else {
                    $tmpPrices = $sourceData->cl_pricelist->price_s;
                }

                $arrData['price_in'] = $tmpPrices;
                $arrData['price_in_vat'] = ($tmpPrices) * (1 + ($sourceData->vat / 100));
            } elseif ($this->settings->platce_dph == 0) {
                $arrData['price_in_vat'] = $sourceData->price_s;
                $arrData['price_in'] = ($sourceData->price_s) * (1 + ($sourceData->vat / 100));
            }
            $arrData['vat'] = $arrPrice['vat'];
            $arrData['price_e_type'] = $this->settings->price_e_type;
            if ($arrData['price_e_type'] == 1) {
                $arrData['price_e'] = $arrData['price_in'];
            } else {
                $arrData['price_e'] = $arrPrice['price_in'];
            }

            $arrData['discount'] = $arrPrice['discount'];
            $arrData['price_e2'] = $arrData['price_in'] * $arrData['quantity'];
            $arrData['price_e2_vat'] = $arrData['price_in_vat'] * $arrData['quantity'];

        }else{
            $arrData['price_e_type'] = $this->settings->price_e_type;
            if ($arrData['price_e_type'] == 1) {
                $arrData['price_e'] = $arrPrice['price_vat'];
            } else {
                $arrData['price_e'] = $arrPrice['price'];
            }

            $arrData['discount'] = $arrPrice['discount'];
            $arrData['price_e2'] = $arrPrice['price_e2'];
            $arrData['price_e2_vat'] = $arrPrice['price_e2_vat'];

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
        if ( $arrPrice['cl_currencies_id'] != $tmpOrder['cl_currencies_id'] ) {
            $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateOrder;
            $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateOrder;
            $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateOrder;
        }

        if (!is_null($sourcePriceData['cl_storage_id'])) {
            $arrData['cl_storage_id'] = $sourcePriceData['cl_storage_id'];
        } else {
            $arrData['cl_storage_id'] = $tmpData->cl_storage_id;
        }

        //20.10.2019 - only if there is not default storage on presenter
        if ($dataManager->tableName == 'cl_delivery_note_in_items') {
            $tmpDefData = $this['deliveryNotelistgrid']->getDefaultData();
        }
        if (isset($tmpDefData['cl_storage_id']) && !is_null($tmpDefData['cl_storage_id']) && is_null($arrData['cl_storage_id'])) {
            $arrData['cl_storage_id'] = $tmpDefData['cl_storage_id'];
        }




        $row = $dataManager->insert($arrData);
        $this->updateSum($this->id, $this);
        return ($row);

    }

    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
        //dump($cl_partners_book_id);
        $arrUpdate = array();
        $arrUpdate['id'] = $this->id;
        $arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL : $cl_partners_book_id);

        //dump($arrUpdate);
        //die;
        $this->DataManager->update($arrUpdate);

        $this['deliveryNotelistgrid']->redrawControl('pricelist2');
    }


    //control method to determinate if we can delete
    public function beforeDelete($lineId, $name = "")
    {
        if ($name != "paymentListGrid") {
            if ($tmpLine = $this->DeliveryNoteInItemsManager->find($lineId)) {

                $this->StoreManager->deleteItemStoreMove($tmpLine);
                $this->StoreManager->UpdateSum($tmpLine->cl_delivery_note['cl_store_docs_id']);
            }

            if ($tmpLine = $this->DeliveryNoteInItemsBackManager->find($lineId)) {
                $this->StoreManager->deleteItemStoreMove($tmpLine);
                $this->StoreManager->UpdateSum($tmpLine->cl_delivery_note['cl_store_docs_id']);
            }
        }

        $result = TRUE;
        return $result;
    }


    //aditional control before delete from baseList
    public function beforeDeleteBaseList($id)
    {
        return TRUE;
    }

    public function emailSetStatus()
    {
        $this->setStatus($this->id, ['status_use' => 'delivery_note_in',
            's_new' => 0,
            's_eml' => 1]);
    }

    public function handleGetGroupNumberSeries($cl_invoice_types_id)
    {
        //Debugger::fireLog($this->id);
        $arrData = array();
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
        //$this->redrawControl('contents');
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


    //aditional processing data after save in listgrid
    //23.11.2018 - there must be giveout from store and receiving backitems
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId, $name);

        if ($name == "deliveryNotelistgrid") {
            //update price_s because it's needed for input
            $tmpData = $this->DeliveryNoteInItemsManager->find($dataId);
            if ($tmpData){
                $tmpData->update(['price_s' => $tmpData['price_in']]);
            }
            //saled items - give out
            //1. check if cl_store_docs exists if not, create new one
            $docId = $this->StoreDocsManager->createStoreDoc(0, $this->id, $this->DataManager);
            $this->StoreDocsManager->find($docId)->update(['cl_storage_id' => $this->settings->cl_storage_id]);
            //2. store in current item
            $dataIdtmp = $this->StoreManager->giveInItem($docId, $dataId, $this->DeliveryNoteInItemsManager);

        } elseif ($name == "deliveryNoteBacklistgrid" && $this->settings->invoice_to_store == 1) {
            //back items - store in
            $docId = $this->StoreDocsManager->createStoreDoc(1, $this->id, $this->DataManager);
            $this->StoreDocsManager->find($docId)->update(['cl_storage_id' => $this->settings->cl_storage_id_back]);
            //2. give out current item
            $dataIdtmp = $this->StoreManager->giveOutItem($docId, $dataId, $this->DeliveryNoteInItemsBackManager);
        }

        if ($name == "deliveryNotelistgrid" || ($name == "deliveryNoteBacklistgrid" && $this->settings->invoice_to_store == 1)) {
            //14.03.2019 - insert cl_pricelist_bond into cl_delivery_note_items
            if ($name == "deliveryNotelistgrid") {
                $tmpItem = $this->DeliveryNoteInItemsManager->find($dataId);
            } elseif ($name == "deliveryNoteBacklistgrid" && $this->settings->invoice_to_store == 1) {
                $tmpItem = $this->DeliveryNoteInItemsBackManager->find($dataId);
            }

            if (!is_null($tmpItem->cl_pricelist_id)) {
                //find if there are bonds in cl_pricelist_bonds
                $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpItem->cl_pricelist_id, $tmpItem->quantity);
                foreach ($tmpBonds as $key => $oneBond) {
                    //found in cl_invoice_items if there already is bonded item
                    if ($name == "deliveryNotelistgrid") {
                        $tmpItemBond = $this->DeliveryNoteInItemsManager->findBy(['cl_parent_bond_id' => $tmpItem->id,
                            'cl_pricelist_id' => $oneBond->cl_pricelist_id])->fetch();
                    } elseif ($name == "deliveryNoteBacklistgrid" && $this->settings->invoice_to_store == 1) {
                        $tmpItemBond = $this->DeliveryNoteInItemsBackManager->findBy(['cl_parent_bond_id' => $tmpItem->id,
                            'cl_pricelist_id' => $oneBond->cl_pricelist_id])->fetch();
                    }
                    $newItem = $this->PriceListBondsManager->getBondData($oneBond, $tmpItem);
                    $newItem['cl_delivery_note_in_id'] = $this->id;

                    if (!$tmpItemBond) {
                        if ($name == "deliveryNotelistgrid") {
                            $tmpNew = $this->DeliveryNoteInItemsManager->insert($newItem);
                            //give in to store
                            $dataId = $this->StoreManager->giveInItem($docId, $tmpNew['id'], $this->DeliveryNoteInItemsManager, FALSE, $tmpItem['cl_store_move_id']);
                        } elseif ($name == "deliveryNoteBacklistgrid" && $this->settings->invoice_to_store == 1) {
                            $tmpNew = $this->DeliveryNoteInItemsBackManager->insert($newItem);
                            //give out from store
                            $dataIdtmp = $this->StoreManager->giveOutItem($docId, $tmpNew['id'], $this->DeliveryNoteInItemsBackManager);
                        }
                        $tmpId = $tmpNew->id;
                    } else {
                        $newItem['id'] = $tmpItemBond->id;
                        if ($name == "deliveryNotelistgrid") {
                            $tmpNew = $this->DeliveryNoteInItemsManager->update($newItem);
                            //give out from store
                            $dataId = $this->StoreManager->giveInItem($docId, $newItem['id'], $this->DeliveryNoteInItemsManager, FALSE, $tmpItem['cl_store_move_id']);
                        } elseif ($name == "deliveryNoteBacklistgrid" && $this->settings->invoice_to_store == 1) {
                            $tmpNew = $this->DeliveryNoteInItemsBackManager->update($newItem);
                            //give in to store
                            $dataIdtmp = $this->StoreManager->giveOutItem($docId, $newItem['id'], $this->DeliveryNoteInItemsBackManager);
                        }
                        $tmpId = $tmpItemBond->id;
                    }
                }
            }
            $this->StoreManager->UpdateSum($docId);
            //create pairedocs record
            //$this->PairedDocsManager->insertOrUpdate(array('cl_delivery_note_in_id' => $this->id, 'cl_store_docs_id' => $docId));
        }
        //$this->redrawControl('pairedDocs2');
        //$this['pairedDocs']->redrawControl('docs');
        //$this->redrawControl('bscAreaEdit');
    }


    public function afterCopy($newLine, $oldLine)
    {
        //parent::afterCopy($newLine, $oldLine);
        $tmpItems = $this->DeliveryNoteInItemsManager->findAll()->where('cl_delivery_note_in_id = ?', $oldLine);
        foreach ($tmpItems as $one) {
            $tmpOne = $one->toArray();
            $tmpOne['cl_delivery_note_in_id'] = $newLine;
            unset($tmpOne['id']);
            $this->DeliveryNoteInItemsManager->insert($tmpOne);
        }

        $tmpItems = $this->DeliveryNoteInItemsBackManager->findAll()->where('cl_delivery_note_in_id = ?', $oldLine);
        foreach ($tmpItems as $one) {
            $tmpOne = $one->toArray();
            $tmpOne['cl_delivery_note_in_id'] = $newLine;
            unset($tmpOne['id']);
            $this->DeliveryNoteInItemsBackManager->insert($tmpOne);
        }


    }


    protected function createComponentReportDNBook($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        // $form->setTranslator($this->translator);
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        $lcText1 = $this->translator->translate('Přijato_od');
        $lcText2 = $this->translator->translate('Přijato_do');

        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $tmpArrPartners = $this->PartnersManager->findAll()->
                    select('CONCAT(cl_partners_book.id,"-",IFNULL(:cl_partners_branch.id,"")) AS id, CONCAT(cl_partners_book.company," ",IFNULL(:cl_partners_branch.b_name,"")) AS company')->
                    order('company')->fetchPairs('id', 'company');

        //bdump($tmpArrPartners);
        $form->addMultiSelect('cl_partners_book', $this->translator->translate('Dodavatel'), $tmpArrPartners)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_dodavatele'));

        $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'invoice')->order('status_name')->fetchPairs('id', 'status_name');
        $form->addMultiSelect('cl_status_id', $this->translator->translate('Stav_dokladu'), $tmpArrStatus)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', 'Vyberte stav');

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Středisko'), $tmpArrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_středisko'));


        $tmpArrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id', 'currency_code');
        $form->addMultiSelect('cl_currencies_id', $this->translator->translate('Měna'), $tmpArrCurrencies)
            ->setTranslator(NULL)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_měnu'));

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xml', $this->translator->translate('uložit_do_XML'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportDNBook');
        $form->onSuccess[] = array($this, 'SubmitReportDNBookSubmitted');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportDNBook()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportDNBookSubmitted(Form $form)
    {
        $data = $form->values;
        //dump(count($data['cl_partners_book']));
        //die;
        if ($form['save_pdf']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_xml']->isSubmittedBy()) {

            $data['cl_partners_branch'] = array();
            $tmpPodm = array();
            if ($data['after_due_date'] == 1) {
                if ($data['min_difference'] == 1) {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_delivery_note_in.due_date < NOW() AND ROUND(cl_delivery_note_in.price_payed,0) < ROUND(cl_delivery_note_in.price_e2_vat,0)';
                    else
                        $tmpPodm = 'cl_delivery_note_in.due_date < NOW() AND ROUND(cl_delivery_note_in.price_payed,0) < ROUND(cl_delivery_note_in.price_e2,0)';
                } else {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_delivery_note_in.due_date < NOW() AND cl_delivery_note_in.price_payed < cl_delivery_note_in.price_e2_vat';
                    else
                        $tmpPodm = 'cl_delivery_note_in.due_date < NOW() AND cl_delivery_note_in.price_payed < cl_delivery_note_in.price_e2';
                }
            }

            if ($data['not_payed'] == 1) {
                if ($data['min_difference'] == 1) {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'ROUND(cl_delivery_note_in.price_payed,0) < ROUND(cl_delivery_note_in.price_e2_vat,0)';
                    else
                        $tmpPodm = 'ROUND(cl_delivery_note_in.price_payed,0) < ROUND(cl_delivery_note_in.price_e2,0)';
                } else {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_delivery_note_in.price_payed < cl_delivery_note_in.price_e2_vat';
                    else
                        $tmpPodm = 'cl_delivery_note_in.price_payed < cl_delivery_note_in.price_e2';
                }
            }

            if ($data['payed'] == 1) {
                if ($data['min_difference'] == 1) {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'ROUND(cl_delivery_note_in.price_payed,0) >= ROUND(cl_delivery_note_in.price_e2_vat,0)';
                    else
                        $tmpPodm = 'ROUND(cl_delivery_note_in.price_payed,0) >= ROUND(cl_delivery_note_in.price_e2,0)';
                } else {
                    if ($this->settings->platce_dph)
                        $tmpPodm = 'cl_delivery_note_in.price_payed >= cl_delivery_note_in.price_e2_vat';
                    else
                        $tmpPodm = 'cl_delivery_note_in.price_payed >= cl_delivery_note_in.price_e2';
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

            if (count($data['cl_partners_book']) == 0) {
                    $dataReport = $this->DeliveryNoteInManager->findAll()->
                    where($tmpPodm)->
                    where('cl_delivery_note_in.delivery_date >= ? AND cl_delivery_note_in.delivery_date <= ?', $data['date_from'], $data['date_to'])->
                    order('cl_delivery_note_in.dn_number ASC, cl_delivery_note_in.delivery_date ASC');
            } else {
                $tmpPartners = [];
                foreach ($data['cl_partners_book'] as $one) {
                    $arrOne = str_getcsv($one, "-");
                    $tmpPartners[] = $arrOne[0];
                }
                $data['cl_partners_book'] = $tmpPartners;
                $dataReport = $this->DeliveryNoteInManager->findAll()->
                    where($tmpPodm)->
                    where('cl_delivery_note_in.delivery_date >= ? AND cl_delivery_note_in.delivery_date <= ?', $data['date_from'], $data['date_to']);

                $dataReport = $dataReport->where('cl_partners_book_id IN (?)', $data['cl_partners_book']);

                $dataReport = $dataReport->order('cl_delivery_note_in.dn_number ASC, cl_delivery_note_in.delivery_date ASC');
                //bdump($tmpBranches);
            }
            if (count($data['cl_status_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_status_id' => $data['cl_status_id']));
            }

            if (count($data['cl_currencies_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_currencies_id' => $data['cl_currencies_id']));
            }

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_delivery_note_in.cl_center_id' => $data['cl_center_id']));
            }

            if (count($data['cl_users_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_delivery_note_in.cl_users_id' => $data['cl_users_id']));
            }

            if (count($data['cl_payment_types_id']) > 0) {
                $dataReport = $dataReport->
                where(array('cl_delivery_note_in.cl_payment_types_id' => $data['cl_payment_types_id']));
            }


            $dataReport = $dataReport->select('cl_partners_book.company,cl_status.status_name,cl_currencies.currency_code,cl_payment_types.name AS "druh platby",cl_delivery_note_in.*');

            if ($form['save_pdf']->isSubmittedBy()) {
                $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
                $tmpTitle = $this->translator->translate('Kniha_dodacích_listů');
                //$template = $this->createTemplate()->setFile( __DIR__.'/../templates/Invoice/ReportInvoiceBook.latte');
                //$template->data = $dataReport;
                //$template->dataSettings = $data;
                //$template->dataSettingsPartners = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
                //$template->dataSettingsStatus = $this->StatusManager->findAll()->where(array('id' =>$data['cl_status_id']))->order('status_name');

                $dataOther = array();
                $dataSettings = $data;
                $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
                where(array('cl_partners_book.id' => $data['cl_partners_book']))->
                select('cl_partners_book.company AS company')->
                order('company');
                $dataOther['dataSettingsStatus'] = $this->StatusManager->findAll()->where(array('id' => $data['cl_status_id']))->order('status_name');
                $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(array('id' => $data['cl_center_id']))->order('name');
                $dataOther['dataSettingsUsers'] = $this->UserManager->getAll()->where(array('id' => $data['cl_users_id']))->order('name');
                $dataOther['dataSettingsCurrencies'] = $this->CurrenciesManager->findAll()->where(array('id' => $data['cl_currencies_id']))->order('currency_code');

                $tmpArrVat = $this->getVats($dataReport);

                $dataOther['arrVat'] = $tmpArrVat;
                //bdump($tmpArrVat);
                $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/DeliveryNoteIn/ReportDNBook.latte', $dataOther, $dataSettings, 'Kniha dodacích listů přijatých');
                $tmpDate1 = new \DateTime($data['date_from']);
                $tmpDate2 = new \DateTime($data['date_to']);
                $this->pdfCreate($template, $this->translator->translate('Kniha_dodacích_listů_přijatých') . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));

                //$template->settings = $this->settings;
                //$template->title = $tmpTitle;
                //$template->author = $tmpAuthor;
                //$template->today = new \Nette\Utils\DateTime;
                //$this->tmpLogo();


            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Kniha_dodacích_listů_přijatých");
                    $this->sendResponse(new \CsvResponse\NCsvResponse($dataReport, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            } elseif ($form['save_xml']->isSubmittedBy()) {

                if ($dataReport->count() > 0) {
                    $arrResult = array();
                    foreach ($dataReport as $key => $one) {
                        $tmpInv = $one->toArray();
                        $arrResult[$key] = array('id' => $tmpInv['id'], 'dn_number' => $tmpInv['dn_number'], 'issue_date' => $tmpInv['issue_date'],
                            'delivery_date' => $tmpInv['delivery_date'], 'pay_date' => $tmpInv['pay_date'],
                            'dn_title' => $tmpInv['dn_title'],
                            'price_base0' => $tmpInv['price_base0'], 'price_base1' => $tmpInv['price_base1'], 'price_base2' => $tmpInv['price_base2'], 'price_base3' => $tmpInv['price_base3'],
                            'correction_base0' => $tmpInv['correction_base0'], 'correction_base1' => $tmpInv['correction_base1'], 'correction_base2' => $tmpInv['correction_base2'], 'correction_base3' => $tmpInv['correction_base3'],
                            'price_vat1' => $tmpInv['price_vat1'], 'price_vat2' => $tmpInv['price_vat2'], 'price_vat3' => $tmpInv['price_vat3'],
                            'vat1' => $tmpInv['vat1'], 'vat2' => $tmpInv['vat2'], 'vat3' => $tmpInv['vat3'],
                            'price_e2' => $tmpInv['price_e2'], 'price_e2_vat' => $tmpInv['price_e2_vat'], 'price_correction' => $tmpInv['price_correction'], 'price_payed' => $tmpInv['price_payed'],
                            'base_payed0' => $tmpInv['base_payed0'], 'base_payed1' => $tmpInv['base_payed1'], 'base_payed2' => $tmpInv['base_payed2'], 'base_payed3' => $tmpInv['base_payed3'],
                            'vat_payed1' => $tmpInv['vat_payed1'], 'vat_payed2' => $tmpInv['vat_payed2'], 'vat_payed3' => $tmpInv['vat_payed3'], 'advance_payed' => $tmpInv['advance_payed'],
                            'pdp' => $tmpInv['pdp'], 'price_e_type' => $tmpInv['price_e_type'], 'storno' => $tmpInv['storno'], 'correction_inv_number' => $tmpInv['correction_inv_number'],
                            'od_number' => $tmpInv['od_number'],
                            'druh platby' => $tmpInv['druh platby'], 'status_name' => $tmpInv['status_name'], 'currency_code' => $tmpInv['currency_code'], 'currency_rate' => $tmpInv['currency_rate']);
                        $tmpPartnerBook = $one->ref('cl_partners_book');
                        $arrResult[$key]['partners_book'] = array('id' => $tmpPartnerBook['id'], 'company' => $tmpPartnerBook['company'], 'street' => $tmpPartnerBook['street'],
                            'city' => $tmpPartnerBook['city'], 'zip' => $tmpPartnerBook['zip'], 'ico' => $tmpPartnerBook['ico'], 'dic' => $tmpPartnerBook['dic']);
                        $arrResult[$key]['delivery_note_in_items'] = $one->related('cl_delivery_note_in_items')->
                        select('cl_pricelist_id,cl_pricelist.identification,cl_delivery_note_in_items.item_label, cl_delivery_note_in_items.quantity, cl_delivery_note_in_items.units, cl_delivery_note_in_items.price_s, cl_delivery_note_in_items.price_e,  cl_delivery_note_in_items.price_e_type,discount,price_e2, price_e2_vat,cl_delivery_note_in_items.cl_storage_id')->
                        fetchAll();
                        $arrResult[$key]['cl_delivery_note_in_items_back'] = $one->related('cl_delivery_note_in_items_back')->
                        select('cl_pricelist_id,cl_pricelist.identification,cl_delivery_note_in_items_back.item_label, cl_delivery_note_in_items_back.quantity, cl_delivery_note_in_items_back.units, cl_delivery_note_in_items_back.price_s, cl_delivery_note_in_items_back.price_e,  cl_delivery_note_in_items_back.price_e_type,discount,price_e2, price_e2_vat,cl_delivery_note_in_items_back.cl_storage_id')->
                        fetchAll();
                    }
                    $filename = $this->translator->translate("Kniha_dodacích_listů_přijatých");
                    $this->sendResponse(new \XMLResponse\XMLResponse($arrResult, $filename . "-" . date('Ymd-Hi') . ".xml"));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_XML_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
        }
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
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('pairedDocs2');
        $this->showModal('pairedDocsModal');

        //$this->redrawControl('contents');
    }


    //validating of data from listgrid
    public function DataProcessListGridValidate($data)
    {
        $retVal = NULL;
        if (isset($data['cl_pricelist_id']) && isset($data['quantity'])) {
            if ($data['cl_pricelist_id'] > 0 && $data['quantity'] < 0 && $this->settings->invoice_to_store == 1) {
                $retVal = $this->translator->translate('Množství_pro_výdej_nesmí_být_záporné,_pokud_jde_o_položku_ceníku.');
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



    public function handleRemoveInvoiceBond()
    {
        $retArr = $this->DeliveryNoteInManager->RemoveInvoiceBond($this->id);
        if (self::hasError($retArr)) {
            $this->flashmessage($this->translator->translate($retArr['error']), 'warning');
        } else {
            $this->flashmessage($this->translator->translate($retArr['success']), 'success');
            //redirect to deliverynote
        }
        $this->redrawControl('content');
    }


    public function handleCreateInvoiceArrived()
    {
        //at first create cl_store_docs - income
        /*$arrDataItems = $this->DeliveryNoteInItemsManager->findAll()->where('cl_delivery_note_in_id = ?', $this->id)->fetchPairs('id', 'id');
        $docId = $this->StoreDocsManager->createStoreDoc(0, $this->id, $this->DataManager);
        foreach ($arrDataItems as $key => $one) {
            //2. store in current item
            $this->StoreManager->giveInItem($docId, $one, $this->DeliveryNoteInItemsManager, TRUE);

        }*/
        //$this->StoreManager->UpdateSum($docId);

        //$this->flashMessage($this->translator->translate('Položky_byly_naskladněny'), 'success');
        $docId = $this->DataManager->find($this->id);

        if ($docId) {
            $this->CreateInvoiceArrived($docId['cl_store_docs_id_in'], $docId['cl_store_docs_id']);
            $this->flashMessage($this->translator->translate('Faktura_přijatá_byla_vytvořena'), 'success');
        }else{
            $this->flashMessage($this->translator->translate('Faktura_přijatá_nebyla_vytvořena'), 'danger');
        }
        //$this->redrawControl('content');
        //die;
        $tmpMainData = $this->DataManager->find($this->id);
        $this->redirect(':Application:InvoiceArrived:edit', ['id' => $tmpMainData['cl_invoice_arrived_id']]);
    }

    public function CreateInvoiceArrived($bscIdIn, $bscId)
    {
        try {
            bdump($bscIdIn,  'bscIdIn - CreateInvoiceArrived');
            bdump($bscId,  'bscId - CreateInvoiceArrived');

            $tmpData = $this->StoreDocsManager->find($bscIdIn);
            $tmpDNNumber = ($tmpData['invoice_number'] == '') ? $tmpData['delivery_number'] : $tmpData['invoice_number'];
            if ($tmpData->doc_type == 0) {
                bdump($tmpDNNumber, 'DNIN - DNNumber');
                $result = $this->StoreManager->createInvoiceArrived($bscIdIn, $tmpDNNumber, 'cl_delivery_note_in_id', $this->id, $bscId);
                if (!$result) {
                    //$this->translator->setPrefix(['applicationModule.Store']);
                    $this->flashmessage($this->translator->translate('Faktura_přijatá_nebyla_vytvořena_Chybí_číslo_faktury'), 'danger');
                }else{
                    $tmpMainData = $this->DataManager->find($this->id);
                    $tmpMainData->update(['cl_invoice_arrived_id' => $result]);
                }
            }


        } catch (\Exception $e) {
            $this->flashmessage($this->translator->translate('Chyba_při_vytváření_faktury_přijaté') . $e->getMessage(), 'warning');
        }
    }

    public function actionFromInvoiceArrived($cl_invoice_arrived_id)
    {
        //look if store_in for given invoice_arrived exists
        $tmpDeliveryNoteIn = $this->DataManager->findAll()->where('cl_invoice_arrived_id = ?', $cl_invoice_arrived_id)->fetch();
        $tmpInvoice = $this->InvoiceArrivedManager->find($cl_invoice_arrived_id);
        if ($tmpInvoice) {
            if (!$tmpDeliveryNoteIn) {
                $this->flashMessage($this->translator->translate('Dodací_list_přijatý_byl_vytvořen'), 'success');

                $this->handleNew("delivery_note_in",
                                    json_encode(['cl_invoice_arrived_id' => $cl_invoice_arrived_id,
                                                    'cl_partners_book_id' => $tmpInvoice->cl_partners_book_id,
                                                    'rinv_number' => $tmpInvoice->rinv_number,
                                                    'rdn_number' => $tmpInvoice->delivery_number,
                                                    'cl_currencies_id' => $tmpInvoice->cl_currencies_id,
                                                    'currency_rate' => $tmpInvoice->currency_rate,
                                                    'dn_title' => $tmpInvoice->inv_title,
                                                    'issue_date' => $tmpInvoice->arv_date]));
                //create pairedocs record
                //there we don't know cl_store_docs_id, so we have to do it in method $this->afterNew
            } else {
                $this->redirect(':Application:DeliveryNoteIn:edit', ['id' => $tmpDeliveryNoteIn['id']]);
            }
        } else {
            $this->flashMessage($this->translator->translate('Dodací_list_přijatý_nebyl_vytvořen!'), 'warning');
        }
    }


   /* public function handleNew($data = '', $defData)
    {
        bdump($this->numberSeries);
        if (intval($data) > 0) {
            $this->numberSeries['cl_number_series_id'] = $data;
            $data = '';
        } else {
            $this->numberSeries['use'] = $data;
        }
        bdump($this->numberSeries);
        die;
        parent::handleNew($data, $defData);
    }*/



    public function handleShowFlashNow($arrData)
    {
        $arrRet = json_decode($arrData, true);
        if ($arrRet['status'] == 'OK') {
            $type = 'success';
        } elseif ($arrRet['status'] == 'NO_INVOICE') {
            $type = 'info';
        } elseif ($arrRet['status'] == 'ERROR') {
            $type = 'warning';
        } else {
            $type = 'success';
        }
        $this->flashMessage($this->translator->translate($arrRet['data']['tmpStatus']), $type);
        $this->redrawControl('flash');
    }


    public function handleChangeStorage($cl_storage_id)
    {
        if ($storedData = $this->DataManager->find($this->id)) {
            if ($storedData->cl_storage_id != $cl_storage_id) {
                $storedData->update(array('cl_storage_id' => $cl_storage_id));
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

}

    


