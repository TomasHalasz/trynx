<?php

namespace App\Model;

use MainServices\QrService;
use Nette,
    Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Invoice management.
 */
class InvoiceManager extends Base
{
    const COLUMN_ID = 'id';
    public $tableName = 'cl_invoice';


    /** @var \App\Model\InvoiceItemsManager */
    public $InvoiceItemsManager;
    /** @var \App\Model\InvoiceItemsBackManager */
    public $InvoiceItemsBackManager;
    /** @var \App\Model\RatesVatManager */
    public $RatesVatManager;
    /** @var \App\Model\InvoicePaymentsManager */
    public $InvoicePaymentsManager;
    /** @var \App\Model\InvoiceTypesManager */
    public $InvoiceTypesManager;
    /** @var \App\Model\CashManager */
    public $CashManager;
    /** @var \App\Model\BankAccountsManager */
    public $BankAccountsManager;
    /** @var \App\Model\CompaniesManager */
    public $CompaniesManager;
    /** @var \App\Model\PartnersManager */
    public $PartnersManager;
    /** @var \App\Model\NumberSeriesManager */
    public $NumberSeriesManager;
    /** @var \App\Model\StatusManager */
    public $StatusManager;
    /** @var App\Model\HeadersFootersManager */
    public $HeadersFootersManager;
    /** @var \App\Model\PairedDocsManager */
    public $PairedDocsManager;

    public $qrService;


    public $settings;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                InvoiceItemsManager     $InvoiceItemsManager, InvoiceItemsBackManager $InvoiceItemsBackManager, RatesVatManager $RatesVatManager,
                                InvoicePaymentsManager  $InvoicePaymentsManager, CompaniesManager $CompaniesManager, InvoiceTypesManager $InvoiceTypesManager, PartnersManager $PartnersManager,
                                CashManager             $CashManager, BankAccountsManager $BankAccountsManager, QrService $qrService, NumberSeriesManager $NumberSeriesManager, StatusManager $StatusManager, HeadersFootersManager $hfManager,
                                PairedDocsManager       $pairedDocsManager    )
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->InvoiceItemsManager = $InvoiceItemsManager;
        $this->InvoiceItemsBackManager = $InvoiceItemsBackManager;
        $this->RatesVatManager = $RatesVatManager;
        $this->InvoicePaymentsManager = $InvoicePaymentsManager;
        $this->InvoiceTypesManager = $InvoiceTypesManager;
        $this->CashManager = $CashManager;
        $this->CompaniesManager = $CompaniesManager;
        $this->BankAccountsManager = $BankAccountsManager;
        $this->qrService = $qrService;
        $this->PartnersManager = $PartnersManager;
        $this->NumberSeriesManager = $NumberSeriesManager;
        $this->StatusManager = $StatusManager;
        $this->HeadersFootersManager = $hfManager;
        $this->PairedDocsManager = $pairedDocsManager;

    }


    /**
     * @param $id
     */
    public function updateInvoiceProfit($id)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $tmpData = $this->find($id);
        $price_s = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id))->sum('price_s * quantity');
        $price_e2 = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2');
        $price_e2_vat = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2_vat');
        //$price_e2           = $tmpData['price_e2'];
        //$price_e2_vat       = $tmpData['price_e2_vat'];

        $price_s_back = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_s * quantity');
        $price_e2_back = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2');
        $price_e2_vat_back = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2_vat');
        //$price_e2_back           = $tmpData['price_e2_back'];
        //$price_e2_vat_back       = $tmpData['price_e2_vat_back'];

        $parentData = new \Nette\Utils\ArrayHash;
        $parentData['id'] = $id;
        $parentData['price_s'] = $price_s - $price_s_back;
        //14.05.2021 - TH has to be still same price_e2 and price_e2_vat because the only change is on price_s
        //$parentData['price_e2']		= $price_e2 - $price_e2_back;
        //$parentData['price_e2_vat'] = $price_e2_vat - $price_e2_vat_back;

        $parentData['profit_abs'] = $price_e2 - $parentData['price_s'];

        if ($parentData['price_s'] > 0) {
            //$parentData['profit'] 	= (($parentData['price_e2'] / $parentData['price_s']) - 1) * 100;
            $parentData['profit'] = 100 - (($parentData['price_s'] / $price_e2) * 100);
            //$profit = 100 - (($priceS / $tmpBondItem->price_e2) * 100);
        } else {
            $parentData['profit'] = 100;
        }

        $this->update($parentData);

    }

    public function updateInvoiceSum($id)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        //$this->id = $id;
        $tmpData = $this->find($id);
        //PDP 04.11.2015
        if ($tmpData->pdp == 1) {
            $recalcItems = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id));
            foreach ($recalcItems as $one) {
                $data['price_e2_vat'] = 0;
                $one->update($data);
            }
            $recalcItems = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id));
            foreach ($recalcItems as $one) {
                $data['price_e2_vat'] = 0;
                $one->update($data);
            }
        } else {
            $recalcItems = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id));
            foreach ($recalcItems as $one) {
                $calcVat = round($one['price_e2'] * ($one['vat'] / 100), 2);
                $data['price_e2_vat'] = $one['price_e2'] + $calcVat;
                $one->update($data);
            }
            $recalcItems = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id));
            foreach ($recalcItems as $one) {
                $calcVat = round($one['price_e2'] * ($one['vat'] / 100), 2);
                $data['price_e2_vat'] = $one['price_e2'] + $calcVat;
                $one->update($data);
            }
        }

        if ($tmpData['export'] == 1) {
            $recalcItems = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id));
            foreach ($recalcItems as $one) {
                $data['price_e2_vat'] = $one['price_e2'];
                $data['vat'] = 0;
                $one->update($data);
            }
            $recalcItems = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id));
            foreach ($recalcItems as $one) {
                $data['price_e2_vat'] = $one['price_e2'];
                $data['vat'] = 0;
                $one->update($data);
            }
        }

        $tmpInvoiceItemsPackage = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id))->
        where('cl_pricelist.cl_pricelist_group.is_return_package = 1');

        $price_s = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id))->sum('price_s * quantity');
        $price_e2 = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2');
        $price_e2_vat = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2_vat');

        $price_s = $price_s - $tmpInvoiceItemsPackage->sum('cl_invoice_items.price_s * cl_invoice_items.quantity');
        $price_e2 = $price_e2 - $tmpInvoiceItemsPackage->sum('cl_invoice_items.price_e2');
        $price_e2_vat = $price_e2_vat - $tmpInvoiceItemsPackage->sum('cl_invoice_items.price_e2_vat');
        //bdump($price_s, 'price_s');

        $tmpInvoiceItemsBackPackage = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->
        where('cl_pricelist.cl_pricelist_group.is_return_package = 1');

        $price_s_back = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_s * quantity');
        $price_e2_back = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2');
        $price_e2_vat_back = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2_vat');

        $price_s_back = $price_s_back - $tmpInvoiceItemsBackPackage->sum('cl_invoice_items_back.price_s * cl_invoice_items_back.quantity');
        $price_e2_back = $price_e2_back - $tmpInvoiceItemsBackPackage->sum('cl_invoice_items_back.price_e2');
        $price_e2_vat_back = $price_e2_vat_back - $tmpInvoiceItemsBackPackage->sum('cl_invoice_items_back.price_e2_vat');
        //bdump($price_s_back, 'price_s_back');
        //bdump($price_e2, 'price_e2');

        //bdump($price_e2_back, 'price_e2_back');

        $parentData = new \Nette\Utils\ArrayHash;
        $parentData['id'] = $id;
        $parentData['price_s'] = $price_s - $price_s_back;
        $parentData['price_e2'] = $price_e2 - $price_e2_back;
        $parentData['price_e2_vat'] = $price_e2_vat - $price_e2_vat_back;

        $parentData['profit_abs'] = $parentData['price_e2'] - $parentData['price_s'];

        if ($parentData['price_s'] > 0) {
            //$parentData['profit'] = (($parentData['price_e2'] / $parentData['price_s']) - 1) * 100;
            if ($parentData['price_e2'] > 0) {
                $parentData['profit'] = 100 - (($parentData['price_s'] / $parentData['price_e2']) * 100);
            } else {
                $parentData['profit'] = 0;
            }
        } else {
            $parentData['profit'] = 100;
        }


        $RatesVatValid = $this->RatesVatManager->findAllValid($tmpData->vat_date);
        $parentData['vat1'] = 0;
        $parentData['vat2'] = 0;
        $parentData['vat3'] = 0;
        $parentData['price_base0'] = 0;
        $parentData['price_base1'] = 0;
        $parentData['price_base2'] = 0;
        $parentData['price_base3'] = 0;
        $parentData['price_vat1'] = 0;
        $parentData['price_vat2'] = 0;
        $parentData['price_vat3'] = 0;
        $parentData['base_payed0'] = 0;
        $parentData['base_payed1'] = 0;
        $parentData['base_payed2'] = 0;
        $parentData['base_payed3'] = 0;
        $parentData['vat_payed1'] = 0;
        $parentData['vat_payed2'] = 0;
        $parentData['vat_payed3'] = 0;
        $parentData['advance_payed'] = 0;
        //Debugger::fireLog($parentData);
        bdump($RatesVatValid,'RatesVatValid 1');
        foreach ($RatesVatValid as $key => $one) {
            bdump($one,'RatesVatValid');
            $totalBase = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $id, 'vat' => $one['rates']))->sum('price_e2');
            $totalBase = $totalBase - $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id, 'vat' => $one['rates']))->sum('price_e2');

            if ($totalBase != 0) {
                if ($parentData['vat1'] == 0) {
                    $parentData['price_base1'] = $totalBase * ($tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1);
                    //$parentData['price_vat1'] = $totalBase * ($one['rates']/100);
                    $parentData['vat1'] = $one['rates'];
                } elseif ($parentData['vat2'] == 0) {
                    $parentData['price_base2'] = $totalBase * ($tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1);
                    //$parentData['price_vat2'] = $totalBase * ($one['rates']/100);
                    $parentData['vat2'] = $one['rates'];
                } elseif ($parentData['vat3'] == 0) {
                    $parentData['price_base3'] = $totalBase * ($tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1);
                    //$parentData['price_vat3'] = $totalBase * ($one['rates']/100);
                    $parentData['vat3'] = $one['rates'];
                } else {
                    $parentData['price_base0'] = $totalBase * ($tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1);
                }
            }

            //Debugger::fireLog($parentData);
        }

        //odecteni zalohy
        $price_payed = $this->InvoicePaymentsManager->findAll()->where(array('cl_invoice_id' => $id,
            'pay_type' => 1));
        foreach ($price_payed as $key => $one) {
            if ($one->pay_vat == 1) { //danova zaloha
                //PDP 04.11.2015
                if ($tmpData->pdp == 1) {
                    $price = $one->pay_price;
                } else {
                    $price = $one->pay_price / (1 + ($one->vat / 100));
                }

                if ($parentData['vat1'] == $one->vat) {
                    $parentData['base_payed1'] = $parentData['base_payed1'] + $price;
                    $parentData['vat_payed1'] = ($tmpData->pdp == 0) ? $price * ($one->vat / 100) : 0;
                } elseif ($parentData['vat2'] == $one->vat) {
                    $parentData['base_payed2'] = $parentData['base_payed2'] + $price;
                    $parentData['vat_payed2'] = ($tmpData->pdp == 0) ? $price * ($one->vat / 100) : 0;
                } elseif ($parentData['vat3'] == $one->vat) {
                    $parentData['base_payed3'] = $parentData['base_payed3'] + $price;
                    $parentData['vat_payed3'] = ($tmpData->pdp == 0) ? $price * ($one->vat / 100) : 0;
                } else {
                    $parentData['base_payed0'] = $parentData['base_payed0'] + $price;
                }
            } else { //nedanova zaloha
                $parentData['advance_payed'] = $parentData['advance_payed'] + $one->pay_price;
            }
        }
        //odecteni danove zalohy
        $parentData['price_base1'] = $parentData['price_base1'] - $parentData['base_payed1'];
        $parentData['price_base2'] = $parentData['price_base2'] - $parentData['base_payed2'];
        $parentData['price_base3'] = $parentData['price_base3'] - $parentData['base_payed3'];
        $parentData['price_base0'] = $parentData['price_base0'] - $parentData['base_payed0'];

        //vypocet DPH
        $parentData['price_vat1'] = ($tmpData->pdp == 0) ? round($parentData['price_base1'] * ($parentData['vat1'] / 100), 2) : 0;
        $parentData['price_vat2'] = ($tmpData->pdp == 0) ? round($parentData['price_base2'] * ($parentData['vat2'] / 100), 2) : 0;
        $parentData['price_vat3'] = ($tmpData->pdp == 0) ? round($parentData['price_base3'] * ($parentData['vat3'] / 100), 2) : 0;

        //PDP 04.11.2015
        $parentData['pdp'] = $tmpData->pdp;
        if ($tmpData->pdp == 1) {
            $parentData['price_vat1'] = 0;
            $parentData['price_vat2'] = 0;
            $parentData['price_vat3'] = 0;
            $parentData['vat_payed1'] = 0;
            $parentData['vat_payed2'] = 0;
            $parentData['vat_payed3'] = 0;
        }

        //celkova castka pokud jde o platce DPH
        if ($this->settings->platce_dph == 1) {
            $parentData['price_e2'] = $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'] + $parentData['price_base0'];
            $parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'];
        }else{
            $parentData['price_e2'] = $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'] + $parentData['price_base0'];
            $parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'];
        }

        //zaokrouhleni
        $parentData = $this->makeCorrection($parentData, $tmpData);
        $this->update($parentData);
        $this->paymentUpdate($id);
        $this->cashUpdate($id);


    }

    public function cashUpdate($invoiceId, $invoicePaymentId = NULL){
        //25.05.2019 - cl_cash in case of cl_payment_types == 2
        //get every cl_invoice_payments and for each cl_payment_types make record in cl_cash

        $tmpData = $this->find($invoiceId);
        if (is_null($invoicePaymentId)){
            $tmpPayments = $tmpData->related('cl_invoice_payments');
        }else{
            $tmpPayments = $tmpData->related('cl_invoice_payments')->where('cl_invoice_payments.id = ?', $invoicePaymentId);
        }

        foreach ($tmpPayments as $key => $one) {
            if (!is_null($one['cl_payment_types_id']) && $one->cl_payment_types->payment_type == 1 && is_null($one['cl_transport_docs_id'])) {
                //20.05.2020 - make cash income only if invoice payment is not created from transport module
                $tmpCashData = [];
                $tmpCashData['cl_invoice_id']           = $tmpData->id;
                $tmpCashData['cl_cash_id']              = $one->cl_cash_id;
                $tmpCashData['cl_company_branch_id']    = $tmpData->cl_company_branch_id;
                $tmpCashData['cl_partners_book_id']     = $tmpData->cl_partners_book_id;
                $tmpCashData['inv_date']                = $one->pay_date;
                $tmpCashData['title']                   = 'Úhrada faktury ' . $tmpData->inv_number;
                $tmpCashData['cash']                    = $one['pay_price'];
                $tmpCashData['cl_currencies_id']        = $one->cl_currencies_id;
                //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
                $tmpCashData['currency_rate']           = $tmpData->currency_rate;

                $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);
                if (!is_null($tmpRetCashId)) {
                    $retDat = $this->CashManager->find($tmpRetCashId);
                    $arrData['cl_cash_id'] = $tmpRetCashId;
                    if (empty($one['pay_doc'])) {
                        $arrData['pay_doc'] = $retDat->cash_number;
                    }
                    $one->update($arrData);
                    $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $one->cl_invoice_id, 'cl_cash_id' => $tmpRetCashId]);
                    //  $one->update(array('cl_cash_id' => $tmpRetCashId, 'pay_doc' => $retDat->cash_number));
                }
                //TODO: dořešit odeslání částečné hotovostní úhrady do EET
            }
        }
    }

    /**update of invoice payment
     * @param $id
     */
    public function paymentUpdate($id)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $parentData = [];
        $tmpData = $this->find($id);
        //zaokrouhleni
        $decimal_places = $tmpData->cl_currencies['decimal_places'];
        //31.08.2021 - calculate every payment
        $parentData['price_payed'] = $this->InvoicePaymentsManager->findAll()->where(['cl_invoice_id' => $id, 'pay_vat' => 0])->sum('pay_price');

        if ($this->settings->platce_dph == 1) {
            // $tmpPrice = round($tmpData['price_e2_vat'],$decimal_places);
            $tmpPrice = $tmpData['price_e2_vat'];
        } else {
            //$tmpPrice = round($tmpData['price_e2'],$decimal_places);
            $tmpPrice = $tmpData['price_e2'];
        }

        if ($parentData['price_payed'] == $tmpPrice) {
            //31.08.2021 - calculate every payment
            $parentData['pay_date'] = $this->InvoicePaymentsManager->findAll()->where(['cl_invoice_id' => $id, 'pay_vat' => 0])->max('pay_date');
            //bdump($parentData['pay_date']);
        } else {
            $parentData['pay_date'] = NULL;
        }
        $tmpData->update($parentData);
    }

    /**
     * @param Nette\Database\Table\ActiveRow $tmpData
     * @param int $type
     * @return mixed|Nette\Database\Table\ActiveRow
     */
    public function createInvoiceFromAdvance(Nette\Database\Table\ActiveRow $tmpData, $type = 1)
    {
        $arrData = [];
        if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
            $tmpInvoiceType = $tmpInvoiceType->id;
        } else {
            $tmpInvoiceType = NULL;
        }
        //default values for invoice
        $defDueDate = new \Nette\Utils\DateTime;
        //$arrInvoice = new \Nette\Utils\ArrayHash;
        $arrInvoice = [];

        $arrInvoice['cl_company_id'] = $tmpData->cl_company['id'];
        $arrInvoice['cl_partners_book_id'] = $tmpData['cl_partners_book_id'];
        $arrInvoice['cl_users_id'] = $tmpData['cl_users_id'];
        $arrInvoice['cl_currencies_id'] = $tmpData['cl_currencies_id'];
        $arrInvoice['cl_partners_branch_id'] = $tmpData['cl_partners_branch_id'];
        $arrInvoice['cl_partners_book_workers_id'] = $tmpData['cl_partners_book_workers_id'];

        $tmpWorkers = $this->PartnersManager->findAll()->select(':cl_partners_book_workers.*')->
        where('cl_partners_book.id = ? AND :cl_partners_book_workers.use_cl_invoice = ?', $tmpData['cl_partners_book_id'], 1)->limit(1)->fetch();
        if ($tmpWorkers) {
            $arrInvoice['cl_partners_book_workers_id'] = $tmpWorkers['id'];
        }

        $arrInvoice['cl_company_branch_id'] = $tmpData['cl_company_branch_id'];
        $arrInvoice['currency_rate'] = $tmpData['currency_rate'];
        $arrInvoice['vat_active'] = $tmpData->cl_company['platce_dph'];
        $arrInvoice['header_txt'] = $tmpData['header_txt'];
        $arrInvoice['footer_txt'] = $tmpData['footer_txt'];

        $arrInvoice['price_e_type'] = $tmpData['price_e_type'];
        //$arrInvoice['cl_store_docs_id'] = $tmpData['cl_store_docs_id'];
        //$arrInvoice['cl_store_docs_id_in'] = $tmpData['cl_store_docs_id_in'];

        $arrInvoice['vat_date'] = $tmpData['pay_date'];
        $arrInvoice['pay_date'] = $tmpData['pay_date'];
        ///$arrInvoice['price_payed']  = $tmpData['price_payed'];
        $arrInvoice['base_payed0'] = $tmpData['base_payed0'];
        $arrInvoice['base_payed1'] = $tmpData['base_payed1'];
        $arrInvoice['base_payed2'] = $tmpData['base_payed2'];
        $arrInvoice['base_payed3'] = $tmpData['base_payed3'];
        $arrInvoice['vat_payed1'] = $tmpData['vat_payed1'];
        $arrInvoice['vat_payed2'] = $tmpData['vat_payed2'];
        $arrInvoice['vat_payed3'] = $tmpData['vat_payed3'];


        $arrInvoice['konst_symb'] = $tmpData->cl_company['konst_symb'];
        $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;


        if (is_null($tmpData['cl_payment_types_id'])) {
            if (!is_null($tmpData->cl_partners_book['cl_payment_types_id'])) {
                $clPayment = $tmpData->cl_partners_book['cl_payment_types_id'];
                $spec_symb = $tmpData->cl_partners_book['spec_symb'];
            } else {
                $clPayment = $tmpData->cl_company['cl_payment_types_id'];
                $spec_symb = "";
            }
            $arrInvoice['cl_payment_types_id'] = $clPayment;
        } else {
            $arrInvoice['cl_payment_types_id'] = $tmpData['cl_payment_types_id'];
            $spec_symb = $tmpData->cl_partners_book['spec_symb'];
        }
        $arrInvoice['spec_symb'] = $spec_symb;

        //create/update invoice
        $tmpStatus = "";
        if ($tmpData['cl_invoice_id'] == NULL) {
            //new number
            if ($type == 1) {
                //tax invoice for payed advance
                $nSeries = $this->NumberSeriesManager->getNewNumber('invoice_tax');
            } elseif ($type == 2) {
                // invoice from payed advance
                $nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
            }
            $arrInvoice['inv_number'] = $nSeries['number'];
            $arrInvoice['cl_number_series_id'] = $nSeries['id'];
            $arrInvoice['var_symb'] = preg_replace('/\D/', '', $arrInvoice['inv_number']);
            $arrInvoice['inv_date'] = new \Nette\Utils\DateTime; //invoice date


            //03.11.2021 - headers and footers
            if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $nSeries['id'], 'lang' => $tmpData->cl_partners_book['lang']))->fetch()) {
                $arrInvoice['header_txt'] = $hfData['header_txt'];
                $arrInvoice['footer_txt'] = $hfData['footer_txt'];
            }


            if (is_null($tmpData['due_date'])) {
                //settings for specific partner
                if ($tmpData->cl_partners_book['due_date'] > 0)
                    $strModify = '+' . $tmpData->cl_partners_book['due_date'] . ' day';
                else
                    $strModify = '+' . $tmpData->cl_company['due_date'] . ' day';

                $arrInvoice['due_date'] = $defDueDate->modify($strModify);
            } else {
                $arrInvoice['due_date'] = $tmpData['due_date'];
            }

            $tmpStatus = 'invoice';
            if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
                $arrInvoice['cl_status_id'] = $nStatus['id'];

            $row = $this->insert($arrInvoice);

            $invoice_id = $row['id'];
            //$this->update(array('id' => $this->id, 'cl_invoice_id' => $row->id));

        } else {
            $this->update($arrInvoice);
            $invoice_id = $tmpData['cl_invoice_id'];
        }


        //delete invoice items
        $tmpInvoiceItems = $this->InvoiceItemsManager->findAll()->where('cl_invoice_id = ?', $invoice_id);
        foreach ($tmpInvoiceItems as $key => $one) {
            $one->delete();
        }

        //delete invoice payments
        $tmpInvoicePayments = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $invoice_id);
        foreach ($tmpInvoicePayments as $key => $one) {
            $one->delete();
        }

        if ($type == 1) {
            //tax invoice for payed advance
            $order = 1;
            $arrItem = [];
            $arrItem['cl_invoice_id'] = $invoice_id;
            $arrItem['item_order'] = $order;
            $arrItem['item_label'] = 'Daňový doklad k zaplacené záloze č. ' . $tmpData['inv_number'];
            $this->InvoiceItemsManager->insert($arrItem);

            if ($tmpData['price_base1'] > 0) {
                $order++;
                $arrItem = [];
                $arrItem['cl_invoice_id'] = $invoice_id;
                $arrItem['item_order'] = $order;
                $arrItem['quantity'] = 1;
                $arrItem['price_e'] = $tmpData['price_base1'];
                $arrItem['price_e2'] = $tmpData['price_base1'];
                $arrItem['vat'] = $tmpData['vat1'];
                $arrItem['price_e2_vat'] = $tmpData['price_base1'] + $tmpData['price_vat1'];
                $this->InvoiceItemsManager->insert($arrItem);
            }

            if ($tmpData['price_base2'] > 0) {
                $order++;
                $arrItem = [];
                $arrItem['cl_invoice_id'] = $invoice_id;
                $arrItem['item_order'] = $order;
                $arrItem['quantity'] = 1;
                $arrItem['price_e'] = $tmpData['price_base2'];
                $arrItem['price_e2'] = $tmpData['price_base2'];
                $arrItem['vat'] = $tmpData['vat2'];
                $arrItem['price_e2_vat'] = $tmpData['price_base2'] + $tmpData['price_vat2'];
                $this->InvoiceItemsManager->insert($arrItem);
            }

            if ($tmpData['price_base3'] > 0) {
                $order++;
                $arrItem = [];
                $arrItem['cl_invoice_id'] = $invoice_id;
                $arrItem['item_order'] = $order;
                $arrItem['quantity'] = 1;
                $arrItem['price_e'] = $tmpData['price_base3'];
                $arrItem['price_e2'] = $tmpData['price_base3'];
                $arrItem['vat'] = $tmpData['vat3'];
                $arrItem['price_e2_vat'] = $tmpData['price_base3'] + $tmpData['price_vat3'];
                $this->InvoiceItemsManager->insert($arrItem);
            }

            if ($tmpData['price_base0'] > 0) {
                $order++;
                $arrItem = [];
                $arrItem['cl_invoice_id'] = $invoice_id;
                $arrItem['item_order'] = $order;
                $arrItem['quantity'] = 1;
                $arrItem['price_e'] = $tmpData['price_base0'];
                $arrItem['price_e2'] = $tmpData['price_base0'];
                $arrItem['vat'] = 0;
                $arrItem['price_e2_vat'] = $tmpData['price_base0'];
                $this->InvoiceItemsManager->insert($arrItem);
            }
            //payment for  invoice
            $arrPayment = [];
            $arrPayment['cl_invoice_id'] = $invoice_id;
            $arrPayment['item_order'] = 1;
            $arrPayment['cl_currencies_id'] = $tmpData['cl_currencies_id'];
            $arrPayment['cl_payment_types_id'] = $tmpData['cl_payment_types_id'];
            $arrPayment['pay_date'] = $tmpData['pay_date'];
            $arrPayment['pay_price'] = $tmpData['price_e2_vat'];
            $arrPayment['pay_doc'] = 'Záloha č. ' . $tmpData['inv_number'];
            $arrPayment['pay_type'] = 0;
            $this->InvoicePaymentsManager->insert($arrPayment);


        } elseif ($type == 2) {
            //final invoice
            foreach ($tmpData->related('cl_invoice_advance_items') as $key => $one) {
                $arrItem = $one->toArray();
                $arrItem['cl_invoice_id'] = $invoice_id;
                unset($arrItem['id']);
                unset($arrItem['cl_invoice_advance_id']);
                $this->InvoiceItemsManager->insert($arrItem);
            }
            //payment for final invoice
            $arrPayment = [];
            $arrPayment['cl_invoice_id'] = $invoice_id;
            $arrPayment['item_order'] = 1;
            $arrPayment['cl_currencies_id'] = $tmpData['cl_currencies_id'];
            $arrPayment['cl_payment_types_id'] = $tmpData['cl_payment_types_id'];
            $arrPayment['pay_date'] = $tmpData['pay_date'];
            $arrPayment['pay_price'] = $tmpData['price_e2_vat'];
            $arrPayment['pay_doc'] = 'Záloha č. ' . $tmpData['inv_number'];
            $arrPayment['pay_type'] = 1;

            $this->InvoicePaymentsManager->insert($arrPayment);

        }

        $this->updateInvoiceSum($invoice_id);

        return $invoice_id;
    }

    public function exportStereo($dataReport, $dataForm)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $arrData = [];
        foreach($dataReport as $key => $one){
            $lcTyp = ($one['pdp'] == 1) ? 'M' : 'F';
            $lcTypDPH = ($one['pdp'] == 1) ? 'URP' : 'U';
            $lcForma_uh = (!is_null($one['cl_payment_types_id'])) ? $one->cl_payment_types['short_desc'] : '';
            if (empty($lcForma_uh) && !is_null($one['cl_payment_types_id'])){
                if ($one->cl_payment_types['payment_type'] == 0) //převod
                    $lcForma_uh = 'B';
                elseif ($one->cl_payment_types['payment_type'] == 1) //hotovost
                    $lcForma_uh = 'H';
                elseif ($one->cl_payment_types['payment_type'] == 2) //dobírka
                    $lcForma_uh = 'D';
                elseif ($one->cl_payment_types['payment_type'] == 3) //karta
                    $lcForma_uh = 'K';
                else
                    $lcForma_uh = 'N';
            }

            $lcDruh = (!is_null($one['cl_center_id'])) ? $one->cl_center['short_desc'] : '';
            if (empty($lcDruh)){
                $lcDruh = $dataForm['short_desc'];
            }

            $items = $one->related('cl_invoice_items')->order('price_e2_vat DESC')->limit(1)->fetch();
            $lcText = ($items) ? $items['item_label'] : $dataForm['item_label'];

            $lcText = str_replace(';','', $lcText);

            $lcRDok = $one->cl_number_series['stereo_number'];
            if (empty($lcRDok)){
                $lcRDok = $dataForm['txt_rada'];
            }
            if (strpos($one['inv_number'], $lcRDok) === FALSE) {
                $lcDok = $lcRDok . $one['inv_number'];
            }else{
                $lcDok = $one['inv_number'];
            }


            $tmpMd = $one->cl_number_series['stereo_md'];
            if (empty($tmpMd)) {
                $tmpMd = $dataForm['ma_dati'];
            }
            $tmpDal = $one->cl_number_series['stereo_dal'];
            if (empty($tmpDal)) {
                $tmpDal = $dataForm['dal'];
            }



            if ($this->settings['platce_dph'] == 1){
                $sumTotalF = $one['price_e2_vat'];
                $workVAT = 'PRAVDA';
            }else{
                $sumTotalF = $one['price_e2'];
                $workVAT = 'NEPRAVDA';
            }
            $exportImport = ($one['export'] == 1) ? 'PRAVDA' : 'NEPRAVDA';
            $lcStorno = ($one['storno'] == 1) ? 'PRAVDA' : 'NEPRAVDA';

            if (!is_null($one['cl_bank_accounts_id'])){
                $lcAccount = $one->cl_bank_accounts['account_number'];
                $lcBank = $one->cl_bank_accounts['bank_code'];
            }elseif (!is_null($one['cl_currencies_id']) && $tmpBank = $this->BankAccountsManager->findAll()->where('cl_currencies_id = ? AND default_account = 1', $one['cl_currencies_id'])->limit(1)->fetch()){
                    $lcAccount = $tmpBank['account_number'];
                    $lcBank = $tmpBank['bank_code'];
            }elseif ($tmpBank = $this->BankAccountsManager->findAll()->limit(1)->fetch()){
                $lcAccount = $tmpBank['account_number'];
                $lcBank = $tmpBank['bank_code'];
            }else{
                $lcAccount = '';
                $lcBank = '';
            }

            $lcVat1 = 0;
            $lcVat2 = 0;
            $lcVat3 = 0;
            $tmpRates = $this->RatesVatManager->findAllValid($one['inv_date'], $one->cl_company['cl_countries_id']);
            foreach($tmpRates as $keyR => $oneR){
                if ($oneR['code_name'] == 'high')
                    $lcVat1 = $oneR['rates'];
                elseif ($oneR['code_name'] == 'low')
                    $lcVat2 = $oneR['rates'];
                elseif ($oneR['code_name'] == 'third')
                    $lcVat3 = $oneR['rates'];
            }

            $priceBase1 = 0;
            $priceBase2 = 0;
            $priceBase3 = 0;
            $priceVat1 = 0;
            $priceVat2 = 0;
            $priceVat3 = 0;

            if ($one['vat1'] == $lcVat1){
                $priceBase1 = $one['price_base1'];
                $priceVat1 = $one['price_vat1'];
            }elseif ($one['vat2'] == $lcVat1){
                $priceBase1 = $one['price_base2'];
                $priceVat1 = $one['price_vat2'];
            }elseif ($one['vat3'] == $lcVat1) {
                $priceBase1 = $one['price_base3'];
                $priceVat1 = $one['price_vat3'];
            }
            if ($one['vat1'] == $lcVat2){
                $priceBase2 = $one['price_base1'];
                $priceVat2 = $one['price_vat1'];
            }elseif ($one['vat2'] == $lcVat2){
                $priceBase2 = $one['price_base2'];
                $priceVat2 = $one['price_vat2'];
            }elseif ($one['vat3'] == $lcVat2) {
                $priceBase2 = $one['price_base3'];
                $priceVat2 = $one['price_vat3'];
            }
            if ($one['vat1'] == $lcVat3){
                $priceBase3 = $one['price_base1'];
                $priceVat3 = $one['price_vat1'];
            }elseif ($one['vat2'] == $lcVat3){
                $priceBase3 = $one['price_base2'];
                $priceVat3 = $one['price_vat2'];
            }elseif ($one['vat3'] == $lcVat3) {
                $priceBase3 = $one['price_base3'];
                $priceVat3 = $one['price_vat3'];
            }
            $arrData[] = [
                'Doklad' => $lcDok,
                'Agenda' => 'VF',
                'Párovací znak' => $lcDok,
                'Variabilní symbol' => $one['var_symb'],
                'Konstantní symbol' => $one['konst_symb'],
                'Typ dokladu' => $lcTyp,
                'Datum splatnosti' => $one['due_date']->format('d.m.Y'),
                'Text' => $lcText,
	            'Druh' => $lcDruh,
                'Celkem v cizí měně' => str_replace('.',',',(string)$sumTotalF),
                'Zálohy v cizí měně' => str_replace('.',',',(string)$one['advance_payed']),
                'Celkem' => str_replace('.',',',(string)($sumTotalF * $one['currency_rate'])),
                'Zálohy' => str_replace('.',',',(string)($one['advance_payed'] * $one['currency_rate'])),
                'Měna' => $one->cl_currencies['currency_name'],
                'Kurz' => str_replace('.',',',(string)$one['currency_rate']),
                'Množství' => 1,
                'Typ DPH' => $lcTypDPH,
                'Datum DPH' => $one['vat_date']->format('d.m.Y'),
                'Zpracovat DPH' => $workVAT,
	            'Základ DPH zákl.' => str_replace('.',',',(string)$priceBase1),
                'DPH zákl.' => str_replace('.',',',(string)$priceVat1),
                'Základ DPH sníž.' => str_replace('.',',',(string)$priceBase2),
                'DPH sníž.' => str_replace('.',',',(string)$priceVat2),
                'Bez daně' =>  str_replace('.',',',(string)$one['price_base0']),
                'Způsob úhrady' => $lcForma_uh,
                'Penále %' => 0,
                'Dovoz/ vývoz' => $exportImport,
                'Způsob zaúčtování' => 'N',
                'Stornováno' => $lcStorno,
                'Počet položek' => 0,
                'Bankovní účet' => $lcAccount,
                'Kód banky' => $lcBank,
                'Firma' => $one->cl_partners_book['company'],
                'Název firmy' => $one->cl_partners_book['company'],
                'Ulice' => $one->cl_partners_book['street'],
                'PSČ' => $one->cl_partners_book['zip'],
                'Obec' => $one->cl_partners_book['city'],
                'Stát' => (!is_null($one->cl_partners_book['cl_countries_id'])) ? $one->cl_partners_book->cl_countries['name'] : '',
                'IČO' =>  $one->cl_partners_book['ico'],
                'DIČ' =>  $one->cl_partners_book['dic'],
                'Ceny s DPH' => 'NEPRAVDA',
                'Změněno' => '',
                'Uhrazeno vše' => 'NEPRAVDA',
                'Směr platby' => 'P',
                'Účtováno' => 'N',
                'Celkem zaokrouhleno' => -1, //str_replace('.',',',(string)$one['price_correction'])
                'Celkem bez označení DPH' => 0,
                'Ukončeno' => 'NEPRAVDA',
                'Má dáti' => $tmpMd,
                'Dal' => $tmpDal,
                'Zaokrouhlení' => 0,
                'Úprava DPH zákl. sazba' => 0,
                'Úprava DPH sníž. sazba' => 0,
                'Zaokrouhlení DPH' => 0,
                'Okamžik uskutečnění' => $one['vat_date']->format('d.m.Y'),
                'Okamžik vystavení' => $one['inv_date']->format('d.m.Y'),
                'Souhrnný doklad' => 'NEPRAVDA',
                'Sazba DPH zákl.' =>  (string)$lcVat1,
                'Sazba DPH sníž.' => (string)$lcVat2,
                'DPH z položek' => 'NEPRAVDA',
                'Rozpočet zaokr.' => 'NEPRAVDA',
                'Rozpočet zaokrouhlení celkem' => 'PRAVDA',
                'Základ DPH 2.sníž.' =>  str_replace('.',',',(string)$priceBase3),
                'DPH 2.sníž.' =>  str_replace('.',',',(string)$priceVat3),
                'Úprava DPH 2.sníž. sazba' => 0,
                'Sazba DPH 2.sníž.' => (string)$lcVat3,
                'Kód režimu plnění' => 0,
                'Evidenční číslo' => str_replace('.',',',(string)$one['inv_number']), //TH 15.01.2023 - Jugová - 'Evidenční číslo' => str_replace('.',',',(string)$one['var_symb']),
                'Kód předmětu plnění' => '',
                'Řada dokladu' => $lcRDok,
                'Číslo dokladu' => $one['inv_number'],
                'Rezervovat' => 'NEPRAVDA',
                'ZakladDPHzJ' => str_replace('.',',',(string)$priceBase1),
                'DPHzJ' => str_replace('.',',',(string)$priceVat1),
                'ZakladDPHsJ' => str_replace('.',',',(string)$priceBase2),
                'DPHsJ' => str_replace('.',',',(string)$priceVat2),
                'BezDaneJ' => str_replace('.',',',(string)$one['price_base0']),
                'Celkem pro odpocet ze skladu' => 0,
                'Operace' => 'T',
                'ZakladDPHtJ' => str_replace('.',',',(string)$priceBase3),
                'DPHtJ' => str_replace('.',',',(string)$priceVat3)
            ];
        }
        $arrReplace = ['Text', 'Druh', 'Firma', 'Název firmy', 'Ulice', 'PSČ', 'Obec', 'Stát', 'Měna'];
        foreach($arrData as $key => $one){
            foreach($arrReplace as $key2 => $one2){
                $arrData[$key][$one2] = iconv("utf-8", "windows-1250", $one[$one2]);
            }
        }
        $arrDataNew = [];
        //convert keynames to cp1250
        foreach($arrData as $key => $one){
            $arrOne = [];
            foreach($one as $key2 => $one2){
                $newKey2 =  iconv("utf-8", "windows-1250", $key2);
                $arrOne[$newKey2] = $one2;
            }
            $arrDataNew[] = $arrOne;
        }


        return $arrDataNew;
    }

    public function getDuplicateInvoices()
    {
        $table = $this->getDatabase();
        $user = $this->user->getIdentity()->getData();
        $result = $table->query('SELECT DISTINCT inv_number FROM cl_invoice WHERE cl_invoice.cl_company_id = ? AND EXISTS(SELECT id FROM cl_invoice AS xx 
                                                    WHERE xx.inv_number=cl_invoice.inv_number AND xx.inv_date=cl_invoice.inv_date AND xx.price_e2=cl_invoice.price_e2 
                                                            AND xx.id != cl_invoice.id AND xx.cl_company_id=cl_invoice.cl_company_id)', $user['cl_company_id']);
        return $result;
    }

    /*08.01.2022 - update sum of used amount for selected tax cl_invoice_advance*/
    public function updateAdvancePriceE2Used($used_cl_invoice_id)
    {
        if (!is_null($used_cl_invoice_id)) {
            $tmpUsed = $this->InvoicePaymentsManager->findAll()->where('used_cl_invoice_id = ?', $used_cl_invoice_id)->sum('pay_price');
            $tmpParentAdvance = $this->findAll()->where('id = ?', $used_cl_invoice_id)->fetch();
            //bdump($tmpUsed);
            if ($tmpParentAdvance) {
                $tmpParentAdvance->update(['price_e2_used' => $tmpUsed]);
            }
        }
    }

    /** calculate price correction for all used price_base
     * @param $parentData
     * @param $tmpData
     * @return mixed
     */
    private function correctionInBase_2($parentData, $tmpData)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        //1. calculate rounding difference
        //2. find biggest price_base
        //3. calculate base for rounding difference
        //4. add base of rounding difference to biggest price_base
        //5. new calculation of vat
        //6. check total sum, if there is slight difference +-0.01 add this diff to base
        //7. return

        //1.
        $decimal_places = $tmpData->cl_currencies->decimal_places;
        $total = round($parentData['price_e2_vat'], $decimal_places);
        $totalCorrection = $total - $parentData['price_e2_vat'];

        //2.
        $testArr = array('0' => array('value' => $parentData['price_base0'], 'rate' => 0, 'base_name' => 'price_base0', 'vat_name' => '', 'correction_name' => 'correction_base0'),
            '1' => array('value' => $parentData['price_base1'], 'rate' => $parentData['vat1'], 'base_name' => 'price_base1', 'vat_name' => 'price_vat1', 'correction_name' => 'correction_base1'),
            '2' => array('value' => $parentData['price_base2'], 'rate' => $parentData['vat2'], 'base_name' => 'price_base2', 'vat_name' => 'price_vat2', 'correction_name' => 'correction_base2'),
            '3' => array('value' => $parentData['price_base3'], 'rate' => $parentData['vat3'], 'base_name' => 'price_base3', 'vat_name' => 'price_vat3', 'correction_name' => 'correction_base3'));
        $biggest = array();
        $testVal = 0;
        foreach ($testArr as $key => $one) {
            //set correction to 0 for all. Correct value will be calculated in next step
            $parentData[$one['correction_name']] = 0;
            if ($one['value'] > $testVal) {
                $biggest = $one;
                $testVal = $one['value'];
            }
        }

        //3.
        $baseCorrection = round($totalCorrection / (1 + ($biggest['rate'] / 100)), 2);
        $parentData[$biggest['correction_name']] = $baseCorrection;

        //4.
        $parentData[$biggest['base_name']] = round($parentData[$biggest['base_name']] + $baseCorrection, 2);

        //5.
        if ($biggest['vat_name'] != '') {
            $parentData[$biggest['vat_name']] = round($parentData[$biggest['base_name']] * ($biggest['rate'] / 100), 2);
        }

        //6.
        $parentData['price_e2'] = $parentData['price_base0'] + $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'];
        $parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'];

        $newTotal = $parentData['price_e2_vat'];

        $difference = $total - $newTotal;
        bdump($difference);
        if ($difference != 0) {
            $parentData[$biggest['base_name']] = $parentData[$biggest['base_name']] + $difference;
            $parentData[$biggest['correction_name']] = $parentData[$biggest['correction_name']] + $difference;
            $parentData['price_e2'] = $parentData['price_e2'] + $difference;
            $parentData['price_e2_vat'] = $parentData['price_e2_vat'] + $difference;
        }


        //7.
        return $parentData;
    }

    /** calculate price correction for all used price_base
     * @param $parentData
     * @param $tmpData
     * @param $decimal_places
     * @return mixed
     */
    private function correctionInBase($parentData, $tmpData, $decimal_places)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        //1. calculate rounding difference
        //2. calculate ratio for every base
        //3. calculate base for rounding difference
        //4. add base of rounding difference to all price_base
        //5. new calculation of vat
        //6. check total sum, if there is slight difference +-0.01 add this diff to base
        //7. return

        //1.
        //$decimal_places = $tmpData->cl_currencies->decimal_places;
        $total = round($parentData['price_e2_vat'], $decimal_places);
        $totalCorrection = round($total - $parentData['price_e2_vat'], 2);
        $totalBase = $parentData['price_e2'];
        $totalCorrection2 = 0;
        bdump($totalCorrection);
        //2.
        if ($totalBase != 0) {
            $testArr = array('0' => array('value' => $parentData['price_base0'], 'rate' => 0, 'base_name' => 'price_base0', 'vat_name' => '',
                'correction_name' => 'correction_base0', 'ratio' => round($parentData['price_base0'] / $totalBase, 3)),
                '1' => array('value' => $parentData['price_base1'], 'rate' => $parentData['vat1'], 'base_name' => 'price_base1', 'vat_name' => 'price_vat1',
                    'correction_name' => 'correction_base1', 'ratio' => round($parentData['price_base1'] / $totalBase, 3)),
                '2' => array('value' => $parentData['price_base2'], 'rate' => $parentData['vat2'], 'base_name' => 'price_base2', 'vat_name' => 'price_vat2',
                    'correction_name' => 'correction_base2', 'ratio' => round($parentData['price_base2'] / $totalBase, 3)),
                '3' => array('value' => $parentData['price_base3'], 'rate' => $parentData['vat3'], 'base_name' => 'price_base3', 'vat_name' => 'price_vat3',
                    'correction_name' => 'correction_base3', 'ratio' => round($parentData['price_base3'] / $totalBase, 3)));
            //3.
            bdump($testArr);
            $biggest = array();
            $testVal = 0;
            foreach ($testArr as $key => $one) {
                $parentData[$one['correction_name']] = 0;
                if ($one['rate'] != 0) {
                    $baseCorrection = round(($totalCorrection * $one['ratio']) / (1 + ($one['rate'] / 100)), 6);
                    $totalCorrection2 = round($totalCorrection2 + ($baseCorrection * (1 + ($one['rate'] / 100))), 2);
                } else {
                    // $baseCorrection = ($totalCorrection * $one['ratio']);
                    $baseCorrection = 0;
                }
                $parentData[$one['correction_name']] = $baseCorrection;
                //4.
                $parentData[$one['base_name']] = round($parentData[$one['base_name']] + $baseCorrection, 2);
                //5.
                if ($one['vat_name'] != '') {
                    $parentData[$one['vat_name']] = round($parentData[$one['base_name']] * ($one['rate'] / 100), 2);
                }
                if (abs($one['value']) > $testVal) {
                    $biggest = $one;
                    $testVal = abs($one['value']);
                }
            }
            bdump($parentData, 'parentDAta');
            //die;
            bdump($totalCorrection, 'totalCorrection');
            bdump($totalCorrection2, 'totalCorrection2');
            bdump($totalCorrection - $totalCorrection2, 'ooo');
            if ($testArr['0']['value'] != 0) {
                $parentData[$testArr['0']['correction_name']] = $totalCorrection - $totalCorrection2;
                $parentData[$testArr['0']['base_name']] = round($parentData[$testArr['0']['base_name']] + $totalCorrection - $totalCorrection2, 2);
            }


            $parentData['price_e2'] = $parentData['price_base0'] + $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'];
            //PDP 04.11.2015
            if ($parentData['pdp'] == 1) {
                $parentData['price_vat1'] = 0;
                $parentData['price_vat2'] = 0;
                $parentData['price_vat3'] = 0;
                $parentData['vat_payed1'] = 0;
                $parentData['vat_payed2'] = 0;
                $parentData['vat_payed3'] = 0;
            }
            $parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'];

            $newTotal = $parentData['price_e2_vat'];

            $difference = $total - $newTotal;
            bdump($difference);
            bdump($biggest);
            if ($difference != 0) {
                //            $parentData[$biggest['base_name']] = round($parentData[$biggest['base_name']] + $difference,2);
                //            $parentData[$biggest['correction_name']] = $parentData[$biggest['correction_name']] +  $difference;
                $parentData['price_correction'] = $difference;
            }


            //6.
            $parentData['price_e2'] = $parentData['price_base0'] + $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'];
            $parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'] + $parentData['price_correction'];
            if ($tmpData['export'] == 0) {
                $parentData['price_e2'] = $parentData['price_e2'] / $tmpData['currency_rate'];
                $parentData['price_e2_vat'] = $parentData['price_e2_vat'] / $tmpData['currency_rate'];
            }

        }


        //7.
        return $parentData;
    }


    public function preparePDFData($id)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $data = $this->find($id);
        if ($data->cl_invoice_types->inv_type == 1 || $data->cl_invoice_types->inv_type == 0) //faktura
        {
            //$tmpTemplateFile =  __DIR__ . '/../templates/Invoice/invoicev1.latte';
            $latteIndex = 1;
        } elseif ($data->cl_invoice_types->inv_type == 2) //opravny danovy doklad
        {
            //$tmpTemplateFile =  __DIR__ . '/../templates/Invoice/correctionv1.latte';
            $latteIndex = 2;
        } elseif ($data->cl_invoice_types->inv_type == 3) //zálohová faktura
        {
            //$tmpTemplateFile =  __DIR__ . '/../templates/Invoice/advancev1.latte';
            $latteIndex = 3;
        }
        //$tmpTitle = "Faktura ".$data->inv_number;
        //$tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        if ($this->settings->platce_dph) {
            $tmpPrice = $data->price_e2_vat;
        } else {
            $tmpPrice = $data->price_e2;
        }
        if (!empty($data->inv_title)) {
            $tmpMsg = $data->inv_title;
        } else {
            if ($tmpContent = $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $data->id))->order('price_e2_vat DESC')->limit(1)->fetch()) {
                $tmpMsg = $tmpContent->item_label;
            } else {
                $tmpMsg = '';
            }
        }
        if ($data->pay_date >= $data->vat_date) {
            $tmpDppd = $data->vat_date;
        } else {
            $tmpDppd = $data->pay_date;
        }
        if ($data->advance_payed > 0) {
            $tmpSA = 1;
        } else {
            $tmpSA = 0;
        }
        if ($data->cl_invoice_types->inv_type == 2) {
            $tmpTd = 1;
        } elseif ($data->cl_invoice_types->inv_type == 3) {
            $tmpTd = 0;
        } else {
            $tmpTd = 9;
        }

        $tmpBank = [];
        if (!is_null($data['cl_bank_accounts_id'])) {
            $tmpBankAcc = $data->cl_bank_accounts->iban_code;
            $tmpBank[$data->cl_bank_accounts_id] = $data->cl_bank_accounts;
        }elseif ($tmpBank2 = $this->BankAccountsManager->findBy(['default_account' => 1, 'cl_currencies_id' => $data->cl_currencies_id])->limit(1)->fetch()) {
            $tmpBankAcc = $tmpBank2['iban_code'];
            $tmpBank2 = $this->BankAccountsManager->findBy(['show_invoice' => 1, 'cl_currencies_id' => $data->cl_currencies_id])->order('default_account DESC');
            foreach ($tmpBank2 as $key => $one) {
                $tmpBank[$key] = $one;
            }
        } else {
            if ($tmpBank2 = $this->BankAccountsManager->findAll()->order('default_account DESC')->limit(1)->fetch()) {
                $tmpBankAcc = $tmpBank2['iban_code'];
            } else {
                $tmpBankAcc = NULL;
            }
            $tmpBank2 = $this->BankAccountsManager->findBy(['show_invoice' => 1])->order('default_account DESC');
            foreach ($tmpBank2 as $key => $one) {
                $tmpBank[$key] = $one;
            }
        }

      //  bdump($tmpBankAcc);
        if (!is_null($tmpBankAcc) && $this->settings->print_qr) {
            //$this->qrService->setSize(15);
            $nowDate = new \Nette\Utils\DateTime;
            $qrCode = $this->qrService->getQrInvoice([
                'am' => (int)$tmpPrice,
                'vs' => $data->var_symb,
                'dt' => $nowDate,
                'cc' => $data->cl_currencies->currency_code,
                'acc' => $tmpBankAcc,
                'id' => $data->inv_number,
                'msg' => $tmpMsg,
                'dd' => $data->inv_date,
                'tp' => 0,
                'vii' => $this->settings->dic,
                'ini' => $this->settings->ico,
                'vir' => $data->cl_partners_book->dic,
                'inr' => $data->cl_partners_book->ico,
                'duzp' => $data->vat_date,
                'dppd' => $tmpDppd,
                'on' => $data->od_number,
                'sa' => $tmpSA,
                'td' => $tmpTd,
                'tb0' => $data->price_base1,
                't0' => $data->price_vat1,
                'tb1' => $data->price_base2,
                't1' => $data->price_vat2,
                'tb2' => $data->price_base3,
                't2' => $data->price_vat3,
                'ntb' => $data->price_base0,
                'fx' => $data->currency_rate,
                'fxa' => $data->cl_currencies->amount
            ]);
        } else {
            $qrCode = NULL;
        }

        $arrData = array('settings' => $this->settings,
            'stamp' => $this->CompaniesManager->getStamp(),
            'logo' => $this->CompaniesManager->getLogo(),
            'RatesVatValid' => $this->RatesVatManager->findAllValid($this->find($data['id'])->vat_date),
            'arrInvoiceVat' => $this->getArrInvoiceVat($id),
            'arrInvoicePay' => $this->getArrInvoicePay($id),
            'bankAccounts' => $tmpBank,
            'taxAdvancePayments' => $this->InvoicePaymentsManager->findAll()->where(array('cl_invoice_id' => $id,
                'pay_vat' => 1)),
            'qrCode' => $qrCode,
            'latteIndex' => $latteIndex);
//'pay_type' => 1,
        //$invoiceTemplate = $this->createMyTemplate($data,$tmpTemplateFile,$tmpTitle,$tmpAuthor,$arrData);
        //$data = $this->DataManager->find($data['id']);
        return $arrData;
    }

    protected function getArrInvoicePay($id)
    {
        $tmpData = $this->find($id);
        $arrInvoicePay = new \Nette\Utils\ArrayHash;
        //foreach($tmpData->related('cl_invoice_payments')->where('pay_type = 0') as $key => $one)
        //31.08.2021 - work with all payments
        foreach ($tmpData->related('cl_invoice_payments') as $key => $one) {
            $arrInvoicePay[$key] = array('pay_date' => $one->pay_date,
                'pay_price' => $one->pay_price,
                'pay_doc' => $one->pay_doc,
                'currency_name' => $one->cl_currencies->currency_name);
        }
        return ($arrInvoicePay);
    }

    public function getArrInvoiceVat($id)
    {
        $tmpData = $this->find($id);
        $arrRatesVatValid = $this->RatesVatManager->findAllValid($tmpData->vat_date);
        $arrInvoiceVat = new \Nette\Utils\ArrayHash;
        foreach ($arrRatesVatValid as $key => $one) {
            if ($tmpData->vat1 == $one['rates']) {
                $baseValue = $tmpData->price_base1;
                $vatValue = $tmpData->price_vat1;
                $basePayedValue = $tmpData->base_payed1;
                $vatPayedValue = $tmpData->vat_payed1;
                $correctionBase = $tmpData->correction_base1;
            } elseif ($tmpData->vat2 == $one['rates']) {
                $baseValue = $tmpData->price_base2;
                $vatValue = $tmpData->price_vat2;
                $basePayedValue = $tmpData->base_payed2;
                $vatPayedValue = $tmpData->vat_payed2;
                $correctionBase = $tmpData->correction_base2;
            } elseif ($tmpData->vat3 == $one['rates']) {
                $baseValue = $tmpData->price_base3;
                $vatValue = $tmpData->price_vat3;
                $basePayedValue = $tmpData->base_payed3;
                $vatPayedValue = $tmpData->vat_payed3;
                $correctionBase = $tmpData->correction_base3;
            } elseif ($one['rates'] == 0) {
                $baseValue = $tmpData->price_base0;
                $vatValue = 0;
                $basePayedValue = $tmpData->base_payed0;
                $vatPayedValue = 0;
                $correctionBase = $tmpData->correction_base0;
            } else {

                $baseValue = 0;
                $vatValue = 0;
                $basePayedValue = 0;
                $vatPayedValue = 0;
                $correctionBase = 0;
            }

            $arrInvoiceVat[$one['rates']] = array('base' => $baseValue,
                'vat' => $vatValue,
                'correction' => $correctionBase,
                'payed' => $basePayedValue,
                'vatpayed' => $vatPayedValue);
        }
        return ($arrInvoiceVat);

    }

    /**update pay_date where is missing
     * @return int[]
     */
    public function RepairInvoicePayment()
    {
        $data = $this->findAll()->where('pay_date IS NULL');
        $i = 0;
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        foreach ($data as $key => $one) {
            $payed = $one->related('cl_invoice_payments')->sum('pay_price');
            $payDate = $one->related('cl_invoice_payments')->max('pay_date');
            if ($this->settings->platce_dph == 1) {
                $price = $one['price_e2_vat'];
            } else {
                $price = $one['price_e2'];
            }
            if (floor($price) == floor($payed)) {
                $one->update(array('pay_date' => $payDate));
                $i++;
            }
        }
        return (array('success' => $i));
    }

    /** create price correction for invoice
     * @param Nette\Utils\ArrayHash $parentData
     * @param Nette\Database\Table\ActiveRow $tmpData
     * @return mixed|Nette\Utils\ArrayHash
     */
    private function makeCorrection(Nette\Utils\ArrayHash $parentData, Nette\Database\Table\ActiveRow $tmpData)
    {

        if ($tmpData->cl_payment_types->payment_type == 1) {
            //10.09.2020 - decimal places in case of cash payment
            $decimal_places = $tmpData->cl_currencies['decimal_places_cash'];
        } else {
            //other payment types  transfer, credit, delivery
            $decimal_places = $tmpData->cl_currencies['decimal_places'];
        }

        if ($this->settings->platce_dph == 1) {
            $parentData['price_correction'] = 0;
            if ($tmpData->cl_payment_types->payment_type == 1) {
                //17.10.2019 - rounding from whole price in case of cash payment
                if ($tmpData['export'] == 0) {
                    $parentData['price_e2'] = $parentData['price_e2'] / $tmpData['currency_rate'];
                    $parentData['price_e2_vat'] = $parentData['price_e2_vat'] / $tmpData['currency_rate'];
                }
                $parentData['price_correction'] = round($parentData['price_e2_vat'], $decimal_places) - $parentData['price_e2_vat'];
                $parentData['price_e2_vat'] = round($parentData['price_e2_vat'], $decimal_places);
            } else {//other payment types  transfer, credit, delivery
                $parentData = $this->correctionInBase($parentData, $tmpData, $decimal_places);
                // bdump($parentData);
            }
        } else {
            $parentData['price_correction'] = round($parentData['price_e2'], $decimal_places) - $parentData['price_e2'];
            $parentData['price_e2'] = round($parentData['price_e2'], $decimal_places);
        }
        return $parentData;
    }


    public function createCorrection($id, $dataNS)
    {
        if ($invoiceData = $this->find($id)) {
            try {
                $data = $invoiceData->toArray();
                unset($data['id']);
                unset($data['created']);
                unset($data['create_by']);
                unset($data['changed']);
                unset($data['change_by']);
                unset($data['header_txt']);
                unset($data['footer_txt']);
                unset($data['pay_date']);
                unset($data['price_payed']);
                unset($data['base_payed0']);
                unset($data['base_payed1']);
                unset($data['base_payed2']);
                unset($data['base_payed3']);
                unset($data['vat_payed1']);
                unset($data['vat_payed2']);
                unset($data['vat_payed3']);
                unset($data['advance_payed']);
                unset($data['cl_documents_id']);
                unset($data['cl_store_docs_id']);
                unset($data['cl_store_docs_id_in']);
                unset($data['cl_offer_id']);
                unset($data['cl_commission_id']);
                unset($data['cl_delivery_note_id']);
                unset($data['dn_is_origin']);
                unset($data['locked']);
                unset($data['storno']);
                unset($data['s_eml']);

                $dtmNow = new Nette\Utils\DateTime();
                $data['inv_date'] = $dtmNow;
                $data['vat_date'] = $dtmNow;
                $dueDays = date_diff($invoiceData['due_date'], $invoiceData['inv_date']);
                $data['due_date'] = $dtmNow->modifyClone('+ ' . $dueDays->d . 'days');
                $data['cl_number_series_id'] = $dataNS['cl_number_series_id'];
                $data['inv_number'] = $dataNS['inv_number'];
                $data['cl_status_id'] = $dataNS['cl_status_id'];
                $data['cl_invoice_types_id'] = $dataNS['cl_invoice_types_id'];
                $data['correction_inv_number'] = $invoiceData['inv_number'];
                $data['var_symb'] = preg_replace('/\D/', '', $data['inv_number']);
                $invoiceData->update(['correction_inv_number' => $data['inv_number']]);

                $row = $this->insert($data);

                $invoiceItems = $invoiceData->related('cl_invoice_items');
                $arrItems = [];
                foreach ($invoiceItems as $key => $one) {
                    $arrItems = $one->toArray();
                    unset($arrItems['id']);
                    unset($arrItems['created']);
                    unset($arrItems['create_by']);
                    unset($arrItems['changed']);
                    unset($arrItems['change_by']);
                    unset($arrItems['cl_store_move_id']);
                    unset($arrItems['cl_delivery_note_id']);
                    unset($arrItems['cl_delivery_note_items_id']);
                    unset($arrItems['cl_commission_id']);
                    $arrItems['quantity'] = $one['quantity'];
                    $arrItems['price_e2'] = $one['price_e2'];
                    $arrItems['price_e2_vat'] = $one['price_e2_vat'];
                    $arrItems['cl_invoice_id'] = $row->id;
                    $this->InvoiceItemsBackManager->insert($arrItems);
                }
                $this->updateInvoiceSum($row->id);
                $arrRet = ['success' => 'Opravný_doklad_byl_vytvořen', 'id' => $row->id];
            } catch (\Exception $e) {
                $arrRet = ['error' => 'Chyba_při_vytváření_opravného_dokladu' . ' ' . $e->getMessage()];
            }
        } else {
            $arrRet = ['error' => 'Chyba_při_vytváření_opravného_dokladu'];
        }


        return $arrRet;
    }

    public function makePayment($id){
        $arrRet = [];
        $tmpData = $this->find($id);
        if ($tmpData) {
            if ($tmpData->cl_company['platce_dph'] == 1)
                $priceCol = 'price_e2_vat';
            else
                $priceCol = 'price_e2';

            if ($tmpData['pay_date'] == NULL || ($tmpData[$priceCol] > $tmpData['price_payed'])) {
                $max = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $id)->max('item_order');
                $arrData                        = [];
                $arrData['cl_invoice_id']       = $id;
                $arrData['item_order']          = $max + 1;
                $arrData['cl_currencies_id']    = $tmpData['cl_currencies_id'];
                $arrData['cl_payment_types_id'] = $tmpData['cl_payment_types_id'];
                $arrData['pay_price']           = $tmpData[$priceCol] - $tmpData['price_payed'];
                $arrData['pay_date']            = new Nette\Utils\DateTime();
                $row = $this->InvoicePaymentsManager->insert($arrData);

                $arrData                = [];
                if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?','invoice',1)->fetch())
                    $arrData['cl_status_id'] = $nStatus['id'];

                $arrData['id']          = $id;
                $arrData['price_payed'] = $tmpData[$priceCol];
                $arrData['pay_date']    = new Nette\Utils\DateTime();
                $this->update($arrData);

                $arrRet = ['success' => 'Faktura_byla_uhrazena', 'id' => $row->id];
            }else{
                $arrRet = ['error' => 'Faktura_již_byla_dříve_uhrazena', 'inv_number' => $tmpData['inv_number']];
            }
        }
        return $arrRet;
    }
}

