<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Tracy\Debugger;

/**
 * Delivery note management.
 */
class DeliveryNoteManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_delivery_note';

	
	/** @var Nette\Database\Context */
	public $DeliveryNoteItemsManager;			
	/** @var Nette\Database\Context */
	public $DeliveryNoteItemsBackManager;
    /** @var Nette\Database\Context */
    public $DeliveryNotePaymentsManager;
	/** @var Nette\Database\Context */
    public $InvoiceTypesManager;
    /** @var Nette\Database\Context */
	public $RatesVatManager;
    /** @var Nette\Database\Context */
    public $NumberSeriesManager;
    /** @var Nette\Database\Context */
    public $StatusManager;
    /** @var Nette\Database\Context */
    public $InvoiceManager;
    /** @var Nette\Database\Context */
    public $InvoiceItemsManager;
    /** @var App\Model\HeadersFootersManager */
    public $HeadersFootersManager;
    /** @var Nette\Database\Context */
    public $InvoiceItemsBackManager;
    /** @var Nette\Database\Context */
    public $PartnersManager;
    /** @var Nette\Database\Context */
    public $StoreManager;
    /** @var Nette\Database\Context */
    public $PairedDocsManager;
    /** @var Nette\Database\Context */
    public $PriceListBondsManager;
    /** @var App\Model\CompaniesManager */
    public $CompaniesManager;
    /** @var App\Model\StoreDocsManager */
    public $StoreDocsManager;
    /** @var App\Model\StoreMoveManager */
    public $StoreMoveManager;
    /** @var App\Model\CashManager */
    public $CashManager;
    /** @var App\Model\InvoicePaymentsManager */
    public $invoicePaymentsManager;
    /** @var App\Model\TransportManager */
    public $TransportManager;
    /** @var App\Model\TransportDocsManager */
    public $TransportDocsManager;

    public $settings;
	
	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					DeliveryNoteItemsManager $DeliveryNoteItemsManager, DeliveryNoteItemsBackManager $DeliveryNoteItemsBackManager,
                    DeliveryNotePaymentsManager $DeliveryNotePaymentsManager, PartnersManager $PartnersManager, StoreManager $StoreManager, StoreMoveManager $StoreMoveManager,
                    InvoiceTypesManager $InvoiceTypesManager, InvoiceManager $InvoiceManager, StatusManager $StatusManager,
                    NumberSeriesManager $NumberSeriesManager, InvoiceItemsManager $InvoiceItemsManager, InvoiceItemsBackManager $InvoiceItemsBackManager,
                    PairedDocsManager $PairedDocsManager, PriceListBondsManager $PriceListBondsManager, CashManager $cashManager,
                    RatesVatManager $RatesVatManager, CompaniesManager $CompaniesManager,
                  StoreDocsManager $storeDocsManager, InvoicePaymentsManager $InvoicePaymentsManager,
                  HeadersFootersManager $hfManager, TransportManager $transportManager, TransportDocsManager $transportDocsManager)
	  {
	        parent::__construct($db, $userManager, $user, $session, $accessor);
	        $this->DeliveryNoteItemsManager     = $DeliveryNoteItemsManager;
	        $this->DeliveryNoteItemsBackManager = $DeliveryNoteItemsBackManager;
            $this->DeliveryNotePaymentsManager  = $DeliveryNotePaymentsManager;
	        $this->RatesVatManager              = $RatesVatManager;
            $this->InvoiceTypesManager          = $InvoiceTypesManager;
            $this->NumberSeriesManager          = $NumberSeriesManager;
            $this->StatusManager                = $StatusManager;
            $this->InvoiceManager               = $InvoiceManager;
            $this->InvoiceItemsManager          = $InvoiceItemsManager;
            $this->InvoiceItemsBackManager      = $InvoiceItemsBackManager;
            $this->InvoicePaymentsManager       = $InvoicePaymentsManager;
            $this->PartnersManager              = $PartnersManager;
            $this->StoreMoveManager             = $StoreMoveManager;
            $this->StoreManager                 = $StoreManager;
            $this->PairedDocsManager            = $PairedDocsManager;
            $this->PriceListBondsManager        = $PriceListBondsManager;
	        //$this->settings                   = $CompaniesManager->getTable()->fetch();
            $this->CompaniesManager             = $CompaniesManager;
            $this->StoreDocsManager             = $storeDocsManager;
            $this->CashManager                  = $cashManager;
            $this->HeadersFootersManager        = $hfManager;
            $this->TransportManager             = $transportManager;
            $this->TransportDocsManager         = $transportDocsManager;

	  }    	
		
	
	public function updateSum($id)
	{
        $this->settings = $this->CompaniesManager->getTable()->fetch();
	    //$this->id = $id;
	    $tmpData = $this->find($id);
	    //PDP 04.11.2015

	    $recalcItems = $this->DeliveryNoteItemsManager->findBy(array('cl_delivery_note_id' => $id));
	    foreach($recalcItems as $one)
	    {
			//$data = new \Nette\Utils\ArrayHash;
			//$data['price_s'] = $one['price_s'] * $oldrate / $rate;

			//if ($this->settings->platce_dph == 1)
			$calcVat = round($one['price_e2'] * (  $one['vat'] / 100 ), 2);

			$data['price_e2_vat'] = $one['price_e2'] + $calcVat;
			//Debugger::fireLog($data);
			$one->update($data);
	    }	    	    


	    $price_e2			= $this->DeliveryNoteItemsManager->findBy(array('cl_delivery_note_id' => $id))->sum('price_e2');
	    $price_e2_vat		= $this->DeliveryNoteItemsManager->findBy(array('cl_delivery_note_id' => $id))->sum('price_e2_vat');

	    $price_e2_back		= $this->DeliveryNoteItemsBackManager->findBy(array('cl_delivery_note_id' => $id))->sum('price_e2');
	    $price_e2_vat_back	= $this->DeliveryNoteItemsBackManager->findBy(array('cl_delivery_note_id' => $id))->sum('price_e2_vat');	    

	    $parentData			= new \Nette\Utils\ArrayHash;
	    $parentData['id']		= $id;
	    $parentData['price_e2']	= $price_e2 - $price_e2_back;
	    $parentData['price_e2_vat'] = $price_e2_vat - $price_e2_vat_back;		


	    $RatesVatValid = $this->RatesVatManager->findAllValid($tmpData->issue_date);
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
        $parentData['advance_payed'] =0;

	    //Debugger::fireLog($parentData);	    	
	    foreach($RatesVatValid as $key => $one)
	    {
			$totalBase = $this->DeliveryNoteItemsManager->findBy(array('cl_delivery_note_id' => $id, 'vat' => $one['rates']))->sum('price_e2');
			$totalBase = $totalBase - $this->DeliveryNoteItemsBackManager->findBy(array('cl_delivery_note_id' => $id, 'vat' => $one['rates']))->sum('price_e2');

			
			if ($totalBase != 0)
			{
				if ($parentData['vat1'] == 0)
				{
					$parentData['price_base1'] = $totalBase;
					//$parentData['price_vat1'] = $totalBase * ($one['rates']/100);
					$parentData['vat1'] = $one['rates'];
				}elseif ($parentData['vat2'] == 0){
					$parentData['price_base2'] = $totalBase;
					//$parentData['price_vat2'] = $totalBase * ($one['rates']/100);		
					$parentData['vat2'] = $one['rates'];
				}elseif ($parentData['vat3'] == 0){
					$parentData['price_base3'] = $totalBase;
					//$parentData['price_vat3'] = $totalBase * ($one['rates']/100);		
					$parentData['vat3'] = $one['rates'];
				}  else {
					$parentData['price_base0'] = $totalBase;
				}
			}

	    //Debugger::fireLog($parentData);	    
	    }

        $price_payed = $this->DeliveryNotePaymentsManager->findAll()->where(array('cl_delivery_note_id' => $id,
            'pay_type' => 1));
        foreach($price_payed as $key => $one) {
            $parentData['advance_payed'] = $parentData['advance_payed'] + $one->pay_price;
        }


	    //vypocet DPH
	    $parentData['price_vat1'] = round($parentData['price_base1'] * ($parentData['vat1']/100),2);		
	    $parentData['price_vat2'] = round($parentData['price_base2'] * ($parentData['vat2']/100),2);			
	    $parentData['price_vat3'] = round($parentData['price_base3'] * ($parentData['vat3']/100),2);			
		
	    //celkova castka pokud jde o platce DPH
	    if ($this->settings->platce_dph == 1)
	    {
			$parentData['price_e2']	    = $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'] + $parentData['price_base0'];
			$parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'];			
	    }

        //zaokrouhleni
        if (!is_null($tmpData->cl_payment_types_id) && $tmpData->cl_payment_types->payment_type == 1) {
            //10.09.2020 - decimal places in case of cash payment
            $decimal_places = $tmpData->cl_currencies->decimal_places_cash;
        }else{
            //other payment types  transfer, credit, delivery
            $decimal_places = $tmpData->cl_currencies->decimal_places;
        }

	    //$decimal_places = $tmpData->cl_currencies->decimal_places;
	    if ($this->settings->platce_dph == 1)
	    {
            $parentData['price_correction'] = round($parentData['price_e2_vat'],$decimal_places) - $parentData['price_e2_vat'];
            $parentData['price_e2_vat']	= round($parentData['price_e2_vat'],$decimal_places);
        }else{
            $parentData['price_correction'] = round($parentData['price_e2'],$decimal_places) - $parentData['price_e2'];
            $parentData['price_e2']		= round($parentData['price_e2'],$decimal_places);
        }


	    if ($this->settings->platce_dph == 1)
	    {
			$tmpPrice = $parentData['price_e2_vat'];
	    }else{
			$tmpPrice = $parentData['price_e2'];
	    }
	    //Debugger::fireLog($parentData);
        $this->paymentUpdate($id);
	    $this->update($parentData);


        //25.05.2019 - cl_cash in case of cl_payment_types == 2
        //get every cl_invoice_payments and for each cl_payment_types make record in cl_cash
        foreach($tmpData->related('cl_delivery_note_payments') as $key=>$one)
        {
            if (!is_null($one['cl_payment_types_id']) && $one->cl_payment_types->payment_type == 1 && is_null($one['cl_transport_docs_id'])){
                //20.05.2020 - make cash income only if invoice payment is not created from transport module
                $tmpCashData                            = array();
                $tmpCashData['cl_delivery_note_id']     = $tmpData->id;
                $tmpCashData['cl_company_branch_id']    = $tmpData->cl_company_branch_id;
                $tmpCashData['cl_cash_id']              = $one->cl_cash_id;
                $tmpCashData['cl_partners_book_id']     = $tmpData->cl_partners_book_id;
                $tmpCashData['inv_date']                = $one->pay_date;
                $tmpCashData['title']                   = 'Úhrada dodacího listu '.$tmpData->dn_number;
                $tmpCashData['cash']                    = $one['pay_price'];
                $tmpCashData['cl_currencies_id']        = $one->cl_currencies_id;
                //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
                $tmpCashData['currency_rate']           = $tmpData->currency_rate;

                $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);
                if (!is_null($tmpRetCashId)) {
                    $retDat = $this->CashManager->find($tmpRetCashId);
                    $one->update(array('cl_cash_id' => $tmpRetCashId, 'pay_doc' => $retDat->cash_number));
                }
                //TODO: dořešit odeslání částečné hotovostní úhrady do EET
            }

        }

	}
	
	/**return string of delivery note numbers for given cl_invoice_id
	 * @param $id
	 * @return string
	 */
	public function getDN($id)
	{
		$result = $this->findAll()->where('cl_invoice_id = ?', $id)->fetchPairs('dn_number', 'dn_number');
		return implode(', ', $result);
	}



    /**update of delivery notes payment
     * @param $id
     */
    public function paymentUpdate($id)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $parentData = array();
        $tmpData = $this->find($id);
        //zaokrouhleni
        $decimal_places = $tmpData->cl_currencies->decimal_places;
        $parentData['price_payed'] = $this->DeliveryNotePaymentsManager->findAll()->where(array('cl_delivery_note_id' => $id,
                                                                                                'pay_type' => 0))->sum('pay_price');

        if ($this->settings->platce_dph == 1) {
            // $tmpPrice = round($tmpData['price_e2_vat'],$decimal_places);
            $tmpPrice = $tmpData['price_e2_vat'];
        }else{
            //$tmpPrice = round($tmpData['price_e2'],$decimal_places);
            $tmpPrice = $tmpData['price_e2'];
        }

        if ($parentData['price_payed'] == $tmpPrice) {
            $parentData['pay_date'] = $this->DeliveryNotePaymentsManager->findAll()->where(array('cl_delivery_note_id' => $id,
                'pay_type' => 0))->max('pay_date');
        }else{
            $parentData['pay_date'] = NULL;
        }
        bdump($tmpData);
        $tmpData->update($parentData);
    }


    public function createInvoice($dataItems, $id)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        //$arrDataItems = json_decode($dataItems, true);
        //if ($tmpData = $this->find(current($arrDataItems))) {
        if ($tmpData = $this->find($id)) {
            if ($tmpData->cl_payment_types['no_invoice'] == 0) {
                if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
                    $tmpInvoiceType = $tmpInvoiceType->id;
                } else {
                    $tmpInvoiceType = NULL;
                }
                //default values for invoice
                $defDueDate = new \Nette\Utils\DateTime;
                //$arrInvoice = new \Nette\Utils\ArrayHash;
                $arrInvoice = [];
                $arrInvoice['dn_is_origin'] = 1;
                $arrInvoice['cl_company_id'] = $this->settings->id;
                $arrInvoice['cl_partners_book_id'] = $tmpData->cl_partners_book_id;
                $arrInvoice['cl_users_id'] = $tmpData->cl_users_id; //15.07.2020 added
                $arrInvoice['cl_currencies_id'] = $tmpData->cl_currencies_id;
                $arrInvoice['cl_partners_branch_id'] = $tmpData->cl_partners_branch_id;
                //$arrInvoice['cl_partners_book_workers_id'] = $tmpData->cl_partners_book_workers_id;

                //16.12.2020 - correct cl_partners_book_workers_id according to settings of workers
                $tmpWorkers = $this->PartnersManager->findAll()->select(':cl_partners_book_workers.*')->
                where('cl_partners_book.id = ? AND :cl_partners_book_workers.use_cl_invoice = ?', $tmpData->cl_partners_book_id, 1)->limit(1)->fetch();
                //fetchPairs('worker_name', 'worker_email');
                if ($tmpWorkers) {
                    $arrInvoice['cl_partners_book_workers_id'] = $tmpWorkers['id'];
                }

                $arrInvoice['cl_company_branch_id'] = $tmpData->cl_company_branch_id;
                $arrInvoice['currency_rate'] = $tmpData->currency_rate;
                $arrInvoice['vat_active'] = $this->settings->platce_dph;
                $arrInvoice['header_txt'] = $tmpData->header_txt;
                $arrInvoice['footer_txt'] = $tmpData->footer_txt;
                $arrInvoice['cl_delivery_note_id'] = $tmpData->id;

                $arrInvoice['price_e_type'] = $tmpData->price_e_type;
                $arrInvoice['cl_store_docs_id'] = $tmpData->cl_store_docs_id;
                $arrInvoice['cl_store_docs_id_in'] = $tmpData->cl_store_docs_id_in;

                $tmpStatus2 = '';
                if ($tmpData['commission'] == 1){
                    $arrInvoice['vat_date'] = new Nette\Utils\DateTime(); //09.04.2021 - it's commission have to take current date
                }else{
                    /*$arrInvoice['vat_date'] = $tmpData['issue_date']; //new \Nette\Utils\DateTime;
                    $tmpNov =  new Nette\Utils\DateTime();
                    if ($tmpData['issue_date']->format('m') != $tmpNov->format('m')){
                        $tmpStatus2 = 'different month';
                    }*/
                    $arrInvoice['vat_date'] = new Nette\Utils\DateTime(); //25.10.2022 - DL are created for example day before
                }


                $arrInvoice['konst_symb'] = $this->settings->konst_symb;
                $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;


                if (is_null($tmpData->cl_payment_types_id)) {
                    if (!is_null($tmpData->cl_partners_book['cl_payment_types_id'])) {
                        $clPayment = $tmpData->cl_partners_book['cl_payment_types_id'];
                        $spec_symb = $tmpData->cl_partners_book['spec_symb'];
                    } else {
                        $clPayment = $this->settings['cl_payment_types_id'];
                        $spec_symb = "";
                    }
                    $arrInvoice['cl_payment_types_id'] = $clPayment;
                } else {
                    $arrInvoice['cl_payment_types_id'] = $tmpData->cl_payment_types_id;
                    $spec_symb = $tmpData->cl_partners_book->spec_symb;
                }
                $arrInvoice['spec_symb'] = $spec_symb;

                //create/update invoice
                $tmpStatus = "";
                if ($tmpData->cl_invoice_id == NULL) {
                    //create invoice
                    //new number
                    $nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
                    $arrInvoice['inv_number'] = $nSeries['number'];
                    $arrInvoice['cl_number_series_id'] = $nSeries['id'];
                    $arrInvoice['var_symb'] = preg_replace('/\D/', '', $arrInvoice['inv_number']);
                    $arrInvoice['inv_date'] = new \Nette\Utils\DateTime; //invoice date

                    if (is_null($tmpData->due_date)) {
                        //settings for specific partner
                        if ($tmpData->cl_partners_book->due_date > 0)
                            $strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
                        else
                            $strModify = '+' . $this->settings->due_date . ' day';

                        $arrInvoice['due_date'] = $defDueDate->modify($strModify);
                    } elseif ($tmpData->cl_payment_types['payment_type'] == 1) {
                        //cash - due_date has to be actual date
                        $arrInvoice['due_date'] = new \Nette\Utils\DateTime;
                    }else{
                        //transfer - due_date from delivery note
                        $arrInvoice['due_date'] = $tmpData->due_date;
                    }

                    $tmpStatus = 'invoice';
                    if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
                        $arrInvoice['cl_status_id'] = $nStatus->id;

                    $row = $this->InvoiceManager->insert($arrInvoice);

                    $arrInvoice2['delivery_number'] = $this->getDN($row->id);
                    $arrInvoice2['id'] = $tmpData->cl_invoice_id;
                    $row2 = $this->InvoiceManager->update($arrInvoice2);

                    $this->update(['id' => $id, 'cl_invoice_id' => $row->id]);
                    $invoiceId = $row->id;
                    $tmpStatus = "created";
                } else {
                    //update invoice
                    $arrInvoice['id'] = $tmpData->cl_invoice_id;
                    $arrInvoice['delivery_number'] = $this->getDN($tmpData->cl_invoice_id);

                    //02.12.2022 - again activated - when is invoice updated it has to stay with same inv_date, vat_date, due_date
                    unset($arrInvoice['inv_date']);
                    unset($arrInvoice['vat_date']);
                    //END 02.12.2022 - again activated

                    unset($arrInvoice['vat_active']);
                    unset($arrInvoice['konst_symb']);
                    $row = $this->InvoiceManager->update($arrInvoice);
                    $invoiceId = $tmpData->cl_invoice_id;
                    $tmpStatus = "updated";
                }

                $this->PartnersManager->useHeaderFooter($invoiceId, $arrInvoice['cl_partners_book_id'], $this->InvoiceManager);


                //now content of invoice
                //at first, delete old content
                //next insert new content
                //, 'cl_delivery_note_id' => $deliveryNote->id
                $tmpItemsToDel = $this->InvoiceItemsManager->findBy(['cl_invoice_id' => $invoiceId]);
                foreach ($tmpItemsToDel as $key => $tmpLine) {
                    //10.03.2019 - before deleting have to delete paired cl_store_move items
                    // if (!is_null($tmpLine['cl_pricelist_id'])) {
                    //$this->StoreManager->deleteItemStoreMove($tmpLine);
                    $tmpLine->delete();
                    //}
                }
                //items back - delete and insert again
                //, 'cl_delivery_note_id' => $deliveryNote->id
                $tmpItemsToDel = $this->InvoiceItemsBackManager->findBy(['cl_invoice_id' => $invoiceId]);
                foreach ($tmpItemsToDel as $key => $tmpLine) {
                    //10.03.2019 - before deleting have to delete paired cl_store_move items
                    //if (!is_null($tmpLine['cl_pricelist_id'])) {
                    //$this->StoreManager->deleteItemStoreMove($tmpLine);
                    $tmpLine->delete();
                    //}
                }

                //04.05.2022 - remove connection from cl_invoice into cl_delivery_note
                $tmpDNUnpair = $this->findAll()->where(['cl_invoice_id' => $invoiceId]);
                foreach ($tmpDNUnpair as $keyDNU => $oneDNU){
                    $tmpDNUOne = $this->PairedDocsManager->findAll()->where(['cl_invoice_id' => $invoiceId, 'cl_delivery_note_id' => $keyDNU])->limit(1)->fetch();
                    if ($tmpDNUOne)
                        $tmpDNUOne->delete();

                    $oneDNU->update(['cl_invoice_id' => NULL]);
                }


                $arrDataItems = json_decode($dataItems, true);
                $lastOrder = 1;
                //1. check if cl_store_docs exists if not, create new one
                //$docId = $this->StoreDocsManager->createStoreDoc(1, $tmpData->id, $this);


                foreach ($arrDataItems as $keyD => $oneD) {
                    $deliveryNote = $this->find($oneD);
                    if ($deliveryNote) {
                        $deliveryNote->update(['id' => $deliveryNote->id, 'cl_invoice_id' => $invoiceId]);

                        if (count($arrDataItems) > 1) {
                            $newItem = new \Nette\Utils\ArrayHash;
                            $newItem['cl_company_id'] = $this->settings->id;
                            $newItem['cl_invoice_id'] = $invoiceId;
                            $newItem['item_order'] = $lastOrder;
                            $newItem['item_label'] = "Dodací list: " . $deliveryNote->dn_number;
                            $newItem['cl_delivery_note_id'] = $deliveryNote->id;
                            $tmpNew = $this->InvoiceItemsManager->insert($newItem);
                            $lastOrder++;
                        }

                        $deliveryNoteItem = $this->DeliveryNoteItemsManager->findAll()->where(['cl_delivery_note_id' => $deliveryNote->id]);
                        $parentBondId = NULL;
                        foreach ($deliveryNoteItem as $key => $one) {
                            $newItem = new \Nette\Utils\ArrayHash;
                            $newItem['cl_company_id'] = $this->settings->id;
                            $newItem['cl_invoice_id'] = $invoiceId;
                            $newItem['item_order'] = $lastOrder;
                            $newItem['cl_pricelist_id'] = $one->cl_pricelist_id;
                            $newItem['item_label'] = $one->item_label;
                            $newItem['quantity'] = $one->quantity;
                            $newItem['units'] = $one->units;
                            $newItem['price_s'] = $one->price_s;
                            $newItem['price_e'] = $one->price_e;
                            $newItem['discount'] = $one->discount;
                            $newItem['price_e2'] = $one->price_e2;
                            $newItem['vat'] = $one->vat;
                            $newItem['price_e2_vat'] = $one->price_e2_vat;
                            $newItem['price_e_type'] = $one->price_e_type;
                            $newItem['cl_parent_bond_id'] = $one->cl_parent_bond_id;
                            $newItem['cl_store_move_id'] = $one->cl_store_move_id;
                            $newItem['cl_delivery_note_id'] = $deliveryNote->id;
                            $newItem['description1'] = $one->description1;
                            $newItem['description2'] = $one->description2;
                            $newItem['cl_storage_id'] = $one->cl_storage_id;
                            if ($newItem['price_s'] > 0)
                                $newItem['profit'] = round((($newItem['price_e'] / $newItem['price_s']) - 1) * 100,2);
                            else
                                $newItem['profit'] = 100;

                            if (is_null($one['cl_parent_bond_id'])) {
                                $parentBondId = NULL;
                            }
                            $newItem['cl_parent_bond_id'] = $parentBondId;

                            $tmpNew = $this->InvoiceItemsManager->insert($newItem);
                            $one->update(['cl_invoice_id' => $invoiceId]);
                            $lastOrder++;

                            //1. check if cl_store_docs exists if not, create new one
                            if (is_null($one->cl_delivery_note['cl_store_docs_id'])) {
                                $docId = $this->StoreDocsManager->createStoreDoc(1, $one->cl_delivery_note_id, $this);
                            } else {
                                $docId = $one->cl_delivery_note['cl_store_docs_id'];
                            }

                            //2. giveout current item
                            $dataIdtmp = $this->StoreManager->giveOutItem($docId, $key, $this->DeliveryNoteItemsManager);

                            //14.03.2019 - bonded items solution
                            //$tmpBonds = $this->PriceListBondsManager->findBy(array('cl_pricelist_bonds_id' => $one->cl_pricelist_id));
                            if (!is_null($one->cl_pricelist_id)) {
                                $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $one->cl_pricelist_id, $one->quantity);

                                if ($tmpBonds) {
                                    $parentBondId = $tmpNew->id;
                                }
                            }

                        }
                        //items back
                        $deliveryNoteItem = $this->DeliveryNoteItemsBackManager->findAll()->where(array('cl_delivery_note_id' => $deliveryNote->id));
                        $parentBondId = NULL;
                        //back items - store in
                        if (count($deliveryNoteItem) > 0) {
                            //if (is_null($one->cl_delivery_note['cl_store_docs_id_in'])) {
                            //    $docIdIn = $this->StoreDocsManager->createStoreDoc(0, $one->cl_delivery_note_id, $this); //$tmpData->id
                            //}else{
                            //    $docIdIn = $one->cl_delivery_note['cl_store_docs_id_in'];
                            //}
                        }

                        foreach ($deliveryNoteItem as $key => $one) {
                            $newItem = new \Nette\Utils\ArrayHash;
                            $newItem['cl_company_id'] = $this->settings->id;
                            $newItem['cl_invoice_id'] = $invoiceId;
                            $newItem['item_order'] = $lastOrder;
                            $newItem['cl_pricelist_id'] = $one->cl_pricelist_id;
                            $newItem['item_label'] = $one->item_label;
                            $newItem['quantity'] = $one->quantity;
                            $newItem['units'] = $one->units;
                            $newItem['price_s'] = $one->price_s;
                            $newItem['price_e'] = $one->price_e;
                            $newItem['discount'] = $one->discount;
                            $newItem['price_e2'] = $one->price_e2;
                            $newItem['vat'] = $one->vat;
                            $newItem['price_e2_vat'] = $one->price_e2_vat;
                            $newItem['price_e_type'] = $one->price_e_type;
                            $newItem['description1'] = $one->description1;
                            $newItem['description2'] = $one->description2;
                            $newItem['cl_storage_id'] = $one->cl_storage_id;
                            //$newItem['cl_parent_bond_id']   = $one->cl_parent_bond_id;
                            $newItem['cl_store_move_id'] = $one->cl_store_move_id;
                            $newItem['cl_delivery_note_id'] = $deliveryNote->id;
                            //if(is_null($one['cl_parent_bond_id'])){
                            //$parentBondId = NULL;
                            //}
                            //$newItem['cl_parent_bond_id'] = $parentBondId;

                            $tmpNew = $this->InvoiceItemsBackManager->insert($newItem);
                            $one->update(['cl_invoice_id' => $invoiceId]);
                            $lastOrder++;

                            if (is_null($one->cl_transport_items_back_id)) {
                                if (is_null($one->cl_delivery_note['cl_store_docs_id_in'])) {
                                    $docIdIn = $this->StoreDocsManager->createStoreDoc(0, $one->cl_delivery_note_id, $this); //$tmpData->id
                                } else {
                                    $docIdIn = $one->cl_delivery_note['cl_store_docs_id_in'];
                                }
                                //2. store in current item
                                $dataIdtmp = $this->StoreManager->giveInItem($docIdIn, $key, $this->DeliveryNoteItemsBackManager);
                            }

                            //14.03.2019 - bonded items solution
                            //$tmpBonds = $this->PriceListBondsManager->findBy(array('cl_pricelist_bonds_id' => $one->cl_pricelist_id));
                            //if ($tmpBonds){
                            //$parentBondId = $tmpNew->id;
                            //}

                        }

                    }
                    $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $invoiceId, 'cl_delivery_note_id' => $oneD]);
                }
                $arrInvoice = [];
                $arrInvoice['id'] = $invoiceId;
                $arrInvoice['delivery_number'] = $this->getDN($invoiceId);
                $row = $this->InvoiceManager->update($arrInvoice);



                if (!is_null($invoiceId)){
                    //18.07.2021 - make invoice payments for cl_delivery_note_payments with origin not in cl_transport_docs
                    $tmpPayments = $this->DeliveryNotePaymentsManager->findAll()->where('cl_delivery_note_id = ? AND cl_transport_docs_id IS NULL', $id );
                    foreach($tmpPayments as $key2 => $one2){
                        $tmpArr = $one2->toArray();
                        unset($tmpArr['cl_delivery_note_id']);
                        unset($tmpArr['cl_transport_docs_id']);
                        $tmpArr['cl_invoice_id'] = $invoiceId;
                        if ($tmpArr['cl_invoice_payments_id'] == NULL){
                            unset($tmpArr['cl_invoice_payments_id']);
                            unset($tmpArr['id']);
                            $id = $this->InvoicePaymentsManager->insert($tmpArr);
                            $one2->update(['cl_invoice_payments_id' => $id]);
                        }else{
                            $tmpArr['id'] = $tmpArr['cl_invoice_payments_id'];
                            unset($tmpArr['cl_invoice_payments_id']);
                            $this->InvoicePaymentsManager->update($tmpArr);
                        }

                    }
                }
                $this->InvoiceManager->updateInvoiceSum($invoiceId);



                //create pairedocs record
                //$this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_invoice_id' => $invoiceId));
                return (['status' => 'OK', 'data' => ['tmpStatus' => $tmpStatus, 'invoiceId' => $invoiceId, 'tmpStatus2' => $tmpStatus2]]);

            }else{
                $tmpStatus2 = "";
                return (['status' => 'NO_INVOICE', 'data' => ['tmpStatus' => 'Vytvoření faktury není povoleno typem úhrady.', 'tmpStatus2' => $tmpStatus2]]);
            }
        }
    }

    /** remove every bond from delivery_note to invoice
     * @param $id
     * @return array
     */
    public function RemoveInvoiceBond($id){
        $arrRet = [];
        try {
            if ($tmpData = $this->findAll()->where('id = ?', $id)->fetch()) {
                $invoiceId = $tmpData['cl_invoice_id'];
                $this->update(['id' => $id, 'cl_invoice_id' => NULL]);
                //$tmpData->update(['cl_invoice_id' => NULL]);
                $items = $this->DeliveryNoteItemsManager->findAll()->where('cl_delivery_note_id = ?', $id);
                foreach ($items as $key => $one) {
                    if ($tmpInvItem = $this->InvoiceItemsManager->findAll()->where('cl_delivery_note_id = ?', $id)){
                        //  $tmpInvItem->update(['cl_delivery_note_items_back_id' => NULL]);
                        $tmpInvItem->delete();
                    }
                    //$this->InvoiceItemsManager->update(['id' => $one['cl_invoice_items_id'], 'cl_delivery_note_items_id' => NULL]);
                    if (!is_null($one['cl_store_move_id'])) {
                        $this->StoreMoveManager->update(['id' => $one['cl_store_move_id'], 'cl_invoice_items_id' => NULL]);
                    }
                    $this->DeliveryNoteItemsManager->update(['id' => $key, 'cl_invoice_items_id' => NULL, 'cl_invoice_id' => NULL]);
                }

                $itemsBack = $this->DeliveryNoteItemsBackManager->findAll()->where('cl_delivery_note_id = ?', $id);
                foreach ($itemsBack as $key => $one) {
                  if ($tmpInvItem = $this->InvoiceItemsBackManager->findAll()->where( 'cl_delivery_note_id = ?', $id)){
                      //  $tmpInvItem->update(['cl_delivery_note_items_back_id' => NULL]);
                      $tmpInvItem->delete();
                    }
                    /*$one->update(['cl_invoice_items_back_id' => NULL]);*/
                    //$this->InvoiceItemsBackManager->update(['id' => $one['cl_invoice_items_back_id'], 'cl_delivery_note_items_back_id' => NULL]);
                    if (!is_null($one['cl_store_move_id'])) {
                        $this->StoreMoveManager->update(['id' => $one['cl_store_move_id'], 'cl_invoice_items_id' => NULL]);
                    }
                    $this->DeliveryNoteItemsBackManager->update(['id' => $key, 'cl_invoice_items_back_id' => NULL, 'cl_invoice_id' => NULL]);
                }

                if ($tmpPaired = $this->PairedDocsManager->findAll()->where('cl_delivery_note_id = ? AND cl_invoice_id IS NOT NULL', $id)->limit(1)->fetch()) {
                    $tmpPaired->delete();
                }
                //bdump($tmpData);
                if (!is_null($invoiceId) && $tmpInvoice = $this->InvoiceManager->find($invoiceId)){
                    $this->InvoiceManager->update(['id' => $invoiceId, 'cl_delivery_note_id' => NULL]);
                }
                $arrRet['success'] = 'Vazba_byla_zrušena';
            }
        }catch (Exception $e) {
            $arrRet['error'] = 'Došlo_k_chybě';
            Debugger::log($e->getMessage());
        }
        return $arrRet;
    }


    /***Create DeliveryNote from given cl_store_docs outgoing
     * @param $bscId
     * @return array
     *
     */
    public function createDelivery($bscId)
    {
        $arrRet = array();
        $tmpStoreDoc = $this->StoreDocsManager->find($bscId);
        if ($tmpStoreDoc){
            //create delivery note
            //at first main record cl_invoice
            $arrDeliveryN = array('cl_partners_book_id'	=> $tmpStoreDoc->cl_partners_book_id,
                                  'cl_currencies_id'    => $tmpStoreDoc->cl_currencies_id,
                                  'currency_rate'		=> $tmpStoreDoc->currency_rate);


            $arrDeliveryN['cl_store_docs_id' ]    = $tmpStoreDoc->id;
            $arrDeliveryN['cl_company_branch_id'] = $this->user->getIdentity()->cl_company_branch_id;

            //if is reference to cl_commission_id then use cl_partners_branch_id,cl_payment_types_id, cl_users_id, cl_center_id
            if (!is_null($tmpStoreDoc->cl_commission_id))
            {
                $arrDeliveryN['cl_partners_branch_id']       = $tmpStoreDoc->cl_commission['cl_partners_branch_id'];
                $arrDeliveryN['cl_payment_types_id']         = $tmpStoreDoc->cl_commission['cl_payment_types_id'];
                $arrDeliveryN['cl_users_id']                 = $tmpStoreDoc->cl_commission['cl_users_id'];
                $arrDeliveryN['cl_center_id']                = $tmpStoreDoc->cl_commission['cl_center_id'];
                //$arrDeliveryN['cl_partners_book_workers_id'] = $tmpStoreDoc->cl_commission['cl_partners_book_workers_id'];
                //13.12.2021 - first step - try find worker for cl_partners_branch from cl_commission

                $tmpWorkers = $this->PartnersManager->findAll()->select(':cl_partners_book_workers.*')->
                            where('cl_partners_book.id = ? ', $tmpStoreDoc->cl_partners_book_id);
                if (!is_null( $arrDeliveryN['cl_partners_branch_id']))
                {
                    $tmpWorkers = $tmpWorkers->where(' :cl_partners_book_workers.cl_partners_branch_id = ?',  $arrDeliveryN['cl_partners_branch_id']);
                }
               $tmpWorkers = $tmpWorkers->limit(1)->fetch();
            }else{
                $tmpWorkers = FALSE;
            }
            //16.12.2020 - correct cl_partners_book_workers_id according to settings of workers
            if (!$tmpWorkers){
                $tmpWorkers = $this->PartnersManager->findAll()->select(':cl_partners_book_workers.*')->
                        where('cl_partners_book.id = ? AND :cl_partners_book_workers.use_' . $this->tableName . ' = ?', $tmpStoreDoc->cl_partners_book_id, 1)->limit(1)->fetch();
            }
            //fetchPairs('worker_name', 'worker_email');
            if ($tmpWorkers){
                $arrDeliveryN['cl_partners_book_workers_id'] = $tmpWorkers['id'];
            }
            //15.11.2020 - update or insert cl_delivery_note
            if (is_null($tmpStoreDoc['cl_delivery_note_id'])){
                $numberSeries = array('use' => 'delivery_note', 'table_key' => 'cl_number_series_id', 'table_number' => 'dn_number');
                $nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
                $arrDeliveryN[$numberSeries['table_key']]		= $nSeries['id'];
                $arrDeliveryN[$numberSeries['table_number']]	= $nSeries['number'];

                $tmpStatus = $numberSeries['use'];
                $nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch();
                if ($nStatus)
                {
                    $arrDeliveryN['cl_status_id'] = $nStatus->id;
                }
                $tmpNow = new \Nette\Utils\DateTime;
                $arrDeliveryN['issue_date']	    = $tmpNow;
                $arrDeliveryN['delivery_date']  = $tmpNow;
                if (!is_null($tmpStoreDoc['cl_invoice_id'])) {
                    $arrDeliveryN['cl_invoice_id'] = $tmpStoreDoc['cl_invoice_id'];
                }

                $deliveryN = $this->insert($arrDeliveryN);
                $deliveryId = $deliveryN['id'];
                //20.02.2019 - update cl_store_docs with cl_invoice_id
                $tmpStoreDoc->update(array('cl_delivery_note_id' => $deliveryN->id));
            }else{
                $arrDeliveryN['id'] = $tmpStoreDoc['cl_delivery_note_id'];
                if (!is_null($tmpStoreDoc['cl_invoice_id'])) {
                    $arrDeliveryN['cl_invoice_id'] = $tmpStoreDoc['cl_invoice_id'];
                }
                $tmp = $this->update($arrDeliveryN);
                $deliveryId = $tmpStoreDoc['cl_delivery_note_id'];
            }
            //12.05.2021 - if there is invoice on cl_store_docs, update cl_delivery_note_id on cl_invoice
            if (!is_null($tmpStoreDoc['cl_invoice_id'])){
                $tmpInvoice = $tmpStoreDoc->cl_invoice;
                //bdump($tmpInvoice);
                $tmpInvoice->update(array('cl_delivery_note_id' => $deliveryId, 'dn_is_origin' => 1));
                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_id' => $tmpStoreDoc['cl_invoice_id'], 'cl_store_docs_id' => $tmpStoreDoc->id));
                $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_id' => $tmpStoreDoc['cl_invoice_id'], 'cl_delivery_note_id' => $deliveryId));
            }


            //create pairedocs record
            $this->PairedDocsManager->insertOrUpdate(array('cl_delivery_note_id' => $deliveryId, 'cl_store_docs_id' => $tmpStoreDoc->id));

            //second cl_delivery_note_items
            $order = 1;
            $arrItems = array();
            foreach($tmpStoreDoc->related('cl_store_move') as $key => $one){

                //20.02.2019 - update price_s at invoice item
                $tmpItem = Array();
                $tmpItem['item_order']          = $order;
                $tmpItem['cl_delivery_note_id']	= $deliveryId;
                $tmpItem['cl_pricelist_id']		= $one->cl_pricelist_id;
                $tmpItem['item_label']		    = $one->cl_pricelist->item_label;
                $tmpItem['units']			    = $one->cl_pricelist->unit;
                $tmpItem['vat']			        = $one->cl_pricelist->vat;
                $tmpItem['cl_store_move_id']	= $key;
                $tmpItem['cl_storage_id']		= $one->cl_storage_id;
                $tmpItem['description1']		= $one->description;
                $tmpItem['order_number']		= $one->order_number;
                $tmpItem['quantity']		    = $one->s_out;
                if ($one->cl_storage->price_method == 0){
                    $tmpItem['price_s']		= $one->price_s;
                }else{
                    $tmpItem['price_s']		= $one->price_vap;
                }
                //$tmpItem['profit']		= $one->profit;
                $tmpItem['discount']		= $one->discount;
                $tmpItem['price_e']			= $one->price_e;
                $tmpItem['price_e2']		= $one->price_e2;
                $tmpItem['price_e2_vat']	= $one->price_e2_vat;

                if (is_null($one['cl_delivery_note_items_id'])){
                    $deliveryNItem = $this->DeliveryNoteItemsManager->insert($tmpItem);
                    $one->update(array('cl_delivery_note_items_id' => $deliveryNItem->id));
                }else{
                    $tmpItem['id'] = $one['cl_delivery_note_items_id'];
                    $deliveryNItem = $this->DeliveryNoteItemsManager->update($tmpItem);
                }
                $arrItems[$key] = $key;
                $order++;
            }

            //15.11.2020 - erase cl_delivery_note_items which are not in cl_store_move
            $tmpToDelete = $this->DeliveryNoteItemsManager->findAll()->where('cl_delivery_note_id = ? AND cl_store_move_id IS NOT NULL AND cl_store_move_id NOT IN (?)', $deliveryId, $arrItems);
            //bdump($arrItems);
            foreach ($tmpToDelete as $key => $one){
              //  bdump($one);
                $one->delete();
            }
            //die;
            //total sums of invoice
            $this->updateSum($deliveryId);
            //redirect to deliverynote
            //$this->redirect(':Application:DeliveryNote:edit', array('id' => $deliveryN->id));
            // }

            //16.01.2022 - insert into cl_transport if is enabled on cl_transport_types
            $tmpDelivery = $this->find($deliveryId);
            if ($tmpDelivery && !is_null($tmpDelivery->cl_store_docs['cl_commission_id'])){
                if (!is_null($tmpDelivery->cl_store_docs->cl_commission['cl_transport_types_id']) && $tmpDelivery->cl_store_docs->cl_commission->cl_transport_types['no_insert'] == 0 ){
                    $cl_transport_id = $this->TransportManager->getOrCreateTransport($tmpDelivery->cl_store_docs->cl_commission['cl_transport_types_id']);
                    $this->TransportDocsManager->insertDN($cl_transport_id, $deliveryId);
                    $this->PairedDocsManager->insertOrUpdate(['cl_delivery_note_id' => $deliveryId, 'cl_transport_id' => $cl_transport_id]);
                }
            }


            $arrRet['success'] = 'Dodací_list_byl_vytvořen';
            $arrRet['deliveryN_id'] = $deliveryId;
        }else{
            $arrRet['error'] = 'Dodací_list_nebyl_vytvořen';
        }
        return $arrRet;
    }


    /***Create DeliveryNote from given cl_invoice_id
     * @param $bscId
     * @return array
     *
     */
    public function createDeliveryFromInvoice($bscId)
    {
        $arrRet = array();
        $tmpInvoice = $this->InvoiceManager->find($bscId);
        if ($tmpInvoice){
            //create delivery note
            //at first main record cl_invoice
            $arrDeliveryN = array('cl_partners_book_id'	=> $tmpInvoice->cl_partners_book_id,
                                    'cl_currencies_id'    => $tmpInvoice->cl_currencies_id,
                                    'currency_rate'		=> $tmpInvoice->currency_rate);


            $arrDeliveryN['cl_invoice_id' ]    = $tmpInvoice->id;
            $arrDeliveryN['cl_company_branch_id'] = $this->user->getIdentity()->cl_company_branch_id;


            $arrDeliveryN['od_number']                   = $tmpInvoice['od_number'];
            $arrDeliveryN['cl_partners_branch_id']       = $tmpInvoice['cl_partners_branch_id'];
            $arrDeliveryN['cl_payment_types_id']         = $tmpInvoice['cl_payment_types_id'];
            $arrDeliveryN['cl_users_id']                 = $tmpInvoice['cl_users_id'];
            $arrDeliveryN['cl_center_id']                = $tmpInvoice['cl_center_id'];
            //$arrDeliveryN['cl_partners_book_workers_id'] = $tmpInvoice->cl_commission['cl_partners_book_workers_id'];
            //13.12.2021 - first step - try find worker for cl_partners_branch from cl_commission

            $tmpWorkers = $this->PartnersManager->findAll()->select(':cl_partners_book_workers.*')->
                                                    where('cl_partners_book.id = ? ', $tmpInvoice->cl_partners_book_id);
            if (!is_null( $arrDeliveryN['cl_partners_branch_id']))
            {
                $tmpWorkers = $tmpWorkers->where(' :cl_partners_book_workers.cl_partners_branch_id = ?',  $arrDeliveryN['cl_partners_branch_id']);
            }
            $tmpWorkers = $tmpWorkers->limit(1)->fetch();

            //16.12.2020 - correct cl_partners_book_workers_id according to settings of workers
            if (!$tmpWorkers){
                $tmpWorkers = $this->PartnersManager->findAll()->select(':cl_partners_book_workers.*')->
                        where('cl_partners_book.id = ? AND :cl_partners_book_workers.use_' . $this->tableName . ' = ?', $tmpInvoice->cl_partners_book_id, 1)->limit(1)->fetch();
            }

            if ($tmpWorkers){
                $arrDeliveryN['cl_partners_book_workers_id'] = $tmpWorkers['id'];
            }
            //15.11.2020 - update or insert cl_delivery_note
            if (is_null($tmpInvoice['cl_delivery_note_id'])){
                $numberSeries = array('use' => 'delivery_note', 'table_key' => 'cl_number_series_id', 'table_number' => 'dn_number');
                $nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
                $arrDeliveryN[$numberSeries['table_key']]		= $nSeries['id'];
                $arrDeliveryN[$numberSeries['table_number']]	= $nSeries['number'];

                //03.11.2021 - headers and footers
                if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $nSeries['id'], 'lang' => $tmpInvoice->cl_partners_book['lang']))->fetch()) {
                    $arrDeliveryN['header_txt'] = $hfData['header_txt'];
                    $arrDeliveryN['footer_txt'] = $hfData['footer_txt'];
                }

                $arrDeliveryN['price_off'] = $tmpInvoice->cl_company['dn_price_off'];

                $tmpStatus = $numberSeries['use'];
                $nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch();
                if ($nStatus) {
                    $arrDeliveryN['cl_status_id'] = $nStatus->id;
                }
                $tmpNow = new \Nette\Utils\DateTime;
                $arrDeliveryN['issue_date']	    = $tmpNow;
                $arrDeliveryN['delivery_date']  = $tmpNow;
                $arrDeliveryN['cl_invoice_id'] = $tmpInvoice['id'];

                $deliveryN = $this->insert($arrDeliveryN);
                $deliveryId = $deliveryN['id'];
                //20.02.2019 - update cl_store_docs with cl_invoice_id
                $tmpInvoice->update(array('cl_delivery_note_id' => $deliveryN->id));
            }else{
                $arrDeliveryN['id'] = $tmpInvoice['cl_delivery_note_id'];
                $arrDeliveryN['cl_invoice_id'] = $tmpInvoice['id'];
                $tmp = $this->update($arrDeliveryN);
                $deliveryId = $tmpInvoice['cl_delivery_note_id'];
            }
            //12.05.2021 - if there is invoice on cl_store_docs, update cl_delivery_note_id on cl_invoice
            /*if (!is_null($tmpInvoice['cl_invoice_id'])){
                $tmpInvoice = $tmpInvoice->cl_invoice;
                //bdump($tmpInvoice);
                $tmpInvoice->update(array('cl_delivery_note_id' => $deliveryId, 'dn_is_origin' => 1));
                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_id' => $tmpInvoice['cl_invoice_id'], 'cl_store_docs_id' => $tmpInvoice->id));
                $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_id' => $tmpInvoice['cl_invoice_id'], 'cl_delivery_note_id' => $deliveryId));
            }*/


            //create pairedocs record
            $this->PairedDocsManager->insertOrUpdate(array('cl_delivery_note_id' => $deliveryId, 'cl_invoice_id' => $tmpInvoice->id));

            //second cl_delivery_note_items
            $order = 1;
            $arrItems = array();
            foreach($tmpInvoice->related('cl_invoice_items') as $key => $one){
                $tmpItem = Array();
                $tmpItem['item_order']          = $order;
                $tmpItem['cl_delivery_note_id']	= $deliveryId;
                $tmpItem['cl_pricelist_id']		= $one['cl_pricelist_id'];
                $tmpItem['item_label']		    = $one['item_label'];
                $tmpItem['units']			    = $one['units'];
                $tmpItem['vat']			        = $one['vat'];
                $tmpItem['cl_invoice_items_id']	= $key;
                $tmpItem['cl_storage_id']		= $one['cl_storage_id'];
                $tmpItem['cl_store_move_id']	        = $one['cl_store_move_id'];
                $tmpItem['cl_parent_bond_id']	        = $one['cl_parent_bond_id'];
                $tmpItem['description1']		        = $one['description1'];
                $tmpItem['description2']		        = $one['description2'];
                $tmpItem['quantity']		    = $one['quantity'];
                $tmpItem['price_s']		        = $one['price_s'];
                //$tmpItem['profit']		= $one->profit;
                $tmpItem['discount']		= $one['discount'];
                $tmpItem['price_e']			= $one['price_e'];
                $tmpItem['price_e2']		= $one['price_e2'];
                $tmpItem['price_e2_vat']	= $one['price_e2_vat'];

                if (is_null($one['cl_delivery_note_items_id'])){
                    $deliveryNItem = $this->DeliveryNoteItemsManager->insert($tmpItem);
                    $one->update(array('cl_delivery_note_items_id' => $deliveryNItem->id));
                }else{
                    $tmpItem['id'] = $one['cl_delivery_note_items_id'];
                    $deliveryNItem = $this->DeliveryNoteItemsManager->update($tmpItem);
                }
                $arrItems[$key] = $key;
                $order++;
            }

            //15.11.2020 - erase cl_delivery_note_items which are not in cl_invoice_items
            $tmpToDelete = $this->DeliveryNoteItemsManager->findAll()->where('cl_delivery_note_id = ? AND cl_invoice_items_id IS NOT NULL AND cl_invoice_items_id NOT IN (?)', $deliveryId, $arrItems);
            //bdump($arrItems);
            foreach ($tmpToDelete as $key => $one){
                //  bdump($one);
                $one->delete();
            }

            //third cl_delivery_note_items_back
            $order = 1;
            $arrItems = array();
            foreach($tmpInvoice->related('cl_invoice_items_back') as $key => $one){
                $tmpItem = Array();
                $tmpItem['item_order']                  = $order;
                $tmpItem['cl_delivery_note_id']	        = $deliveryId;
                $tmpItem['cl_pricelist_id']		        = $one['cl_pricelist_id'];
                $tmpItem['item_label']		            = $one['item_label'];
                $tmpItem['units']			            = $one['units'];
                $tmpItem['vat']			                = $one['vat'];
                $tmpItem['cl_invoice_items_back_id']	= $key;
                $tmpItem['cl_storage_id']		        = $one['cl_storage_id'];
                $tmpItem['cl_store_move_id']	        = $one['cl_store_move_id'];
                $tmpItem['cl_parent_bond_id']	        = $one['cl_parent_bond_id'];
                $tmpItem['description1']		        = $one['description1'];
                $tmpItem['description2']		        = $one['description2'];
                $tmpItem['quantity']		            = $one['quantity'];
                $tmpItem['price_s']		                = $one['price_s'];
                //$tmpItem['profit']		            = $one->profit;
                $tmpItem['discount']		            = $one['discount'];
                $tmpItem['price_e']			            = $one['price_e'];
                $tmpItem['price_e2']		            = $one['price_e2'];
                $tmpItem['price_e2_vat']	            = $one['price_e2_vat'];

                if (is_null($one['cl_delivery_note_items_back_id'])){
                    $deliveryNItem = $this->DeliveryNoteItemsBackManager->insert($tmpItem);
                    $one->update(array('cl_delivery_note_items_back_id' => $deliveryNItem->id));
                }else{
                    $tmpItem['id'] = $one['cl_delivery_note_items_back_id'];
                    $deliveryNItem = $this->DeliveryNoteItemsBackManager->update($tmpItem);
                }
                $arrItems[$key] = $key;
                $order++;
            }

            //15.11.2020 - erase cl_delivery_note_items_back which are not in cl_invoice_items
  //          if ($arrItems) {
                $tmpToDelete = $this->DeliveryNoteItemsBackManager->findAll()->where('cl_delivery_note_id = ? AND cl_invoice_items_back_id IS NOT NULL ', $deliveryId);

//                $tmpToDelete = $this->DeliveryNoteItemsBackManager->findAll()->where('cl_delivery_note_id = ? AND cl_invoice_items_back_id IS NOT NULL AND cl_invoice_items_back_id NOT IN (?)', $deliveryId, $arrItems);

            //bdump($arrItems);
            foreach ($tmpToDelete as $key => $one){
                //  bdump($one);
                $one->delete();
            }
            //die;
            //total sums of invoice
            $this->updateSum($deliveryId);
            //redirect to deliverynote
            //$this->redirect(':Application:DeliveryNote:edit', array('id' => $deliveryN->id));
            // }
            $arrRet['success'] = 'Dodací_list_byl_vytvořen';
            $arrRet['deliveryN_id'] = $deliveryId;
        }else{
            $arrRet['error'] = 'Dodací_list_nebyl_vytvořen';
        }
        return $arrRet;
    }

}

