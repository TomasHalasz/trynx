<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Tracy\Debugger;

/**
 * Delivery note in management.
 */
class DeliveryNoteInManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_delivery_note_in';

	/** @var App\Model\DeliveryNoteInItemsManager */
	public $DeliveryNoteInItemsManager;
    /** @var App\Model\DeliveryNoteInItemsBackManager */
    public $DeliveryNoteInItemsBackManager;
	/** @var App\Model\InvoiceTypesManager */
    public $InvoiceTypesManager;
    /** @var Nette\Database\Context */
	public $RatesVatManager;
    /** @var Nette\Database\Context */
    public $NumberSeriesManager;
    /** @var Nette\Database\Context */
    public $StatusManager;
    /** @var Nette\Database\Context */
    public $InvoiceArrivedManager;
    /** @var Nette\Database\Context */
    public $HeadersFootersManager;
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
					DeliveryNoteInItemsManager $DeliveryNoteInItemsManager, DeliveryNoteInItemsBackManager $DeliveryNoteInItemsBackManager,
                    PartnersManager $PartnersManager, StoreManager $StoreManager, StoreMoveManager $StoreMoveManager,
                    InvoiceTypesManager $InvoiceTypesManager, InvoiceArrivedManager $InvoiceArrivedManager, StatusManager $StatusManager,
                    NumberSeriesManager $NumberSeriesManager,
                    PairedDocsManager $PairedDocsManager, PriceListBondsManager $PriceListBondsManager, CashManager $cashManager,
                    RatesVatManager $RatesVatManager, CompaniesManager $CompaniesManager,
                  StoreDocsManager $storeDocsManager,
                  HeadersFootersManager $hfManager, TransportManager $transportManager, TransportDocsManager $transportDocsManager)
	  {
	        parent::__construct($db, $userManager, $user, $session, $accessor);
	        $this->DeliveryNoteInItemsManager       = $DeliveryNoteInItemsManager;
            $this->DeliveryNoteInItemsBackManager   = $DeliveryNoteInItemsBackManager;
	        $this->RatesVatManager                  = $RatesVatManager;
            $this->InvoiceTypesManager              = $InvoiceTypesManager;
            $this->NumberSeriesManager              = $NumberSeriesManager;
            $this->StatusManager                    = $StatusManager;
            $this->InvoiceArrivedManager            = $InvoiceArrivedManager;
            $this->PartnersManager                  = $PartnersManager;
            $this->StoreMoveManager                 = $StoreMoveManager;
            $this->StoreManager                     = $StoreManager;
            $this->PairedDocsManager                = $PairedDocsManager;
            $this->PriceListBondsManager            = $PriceListBondsManager;
	        //$this->settings                   = $CompaniesManager->getTable()->fetch();
            $this->CompaniesManager                 = $CompaniesManager;
            $this->StoreDocsManager                 = $storeDocsManager;
            $this->CashManager                      = $cashManager;
            $this->HeadersFootersManager            = $hfManager;
            $this->TransportManager                 = $transportManager;
            $this->TransportDocsManager             = $transportDocsManager;

	  }    	
		
	
	public function updateSum($id)
	{
        $this->settings = $this->CompaniesManager->getTable()->fetch();
	    $tmpData = $this->find($id);
	    $recalcItems = $this->DeliveryNoteInItemsManager->findBy(['cl_delivery_note_in_id' => $id]);
	    foreach($recalcItems as $one)
	    {
			$calcVat = round($one['price_e2'] * (  $one['vat'] / 100 ), 2);
			$data['price_e2_vat'] = $one['price_e2'] + $calcVat;
			$one->update($data);
	    }	    	    


	    $price_e2			= $this->DeliveryNoteInItemsManager->findBy(['cl_delivery_note_in_id' => $id])->sum('price_e2');
	    $price_e2_vat		= $this->DeliveryNoteInItemsManager->findBy(['cl_delivery_note_in_id' => $id])->sum('price_e2_vat');

        $price_e2_back		= $this->DeliveryNoteInItemsBackManager->findBy(['cl_delivery_note_in_id' => $id])->sum('price_e2');
        $price_e2_vat_back	= $this->DeliveryNoteInItemsBackManager->findBy(['cl_delivery_note_in_id' => $id])->sum('price_e2_vat');

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
			$totalBase = $this->DeliveryNoteInItemsManager->findBy(['cl_delivery_note_in_id' => $id, 'vat' => $one['rates']])->sum('price_e2');
            $totalBase = $totalBase - $this->DeliveryNoteInItemsBackManager->findBy(['cl_delivery_note_in_id' => $id, 'vat' => $one['rates']])->sum('price_e2');
			
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

	    $this->update($parentData);

    }
	

    public function createInvoice($dataItems, $id)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        //$arrDataItems = json_decode($dataItems, true);
        //if ($tmpData = $this->find(current($arrDataItems))) {
        if ($tmpData = $this->find($id)) {
                if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
                    $tmpInvoiceType = $tmpInvoiceType->id;
                } else {
                    $tmpInvoiceType = NULL;
                }
                //default values for invoice
                $defDueDate = new \Nette\Utils\DateTime;
                //$arrInvoice = new \Nette\Utils\ArrayHash;
                $arrInvoice                             = [];
                $arrInvoice['cl_company_id']            = $this->settings->id;
                $arrInvoice['cl_partners_book_id']      = $tmpData->cl_partners_book_id;
                $arrInvoice['cl_currencies_id']         = $tmpData->cl_currencies_id;
                $arrInvoice['currency_rate']            = $tmpData->currency_rate;
                $arrInvoice['cl_delivery_note_in_id']   = $tmpData->id;

                $arrInvoice['price_e_type']             = $tmpData->price_e_type;
                $arrInvoice['cl_store_docs_id']         = $tmpData->cl_store_docs_id;
                $arrInvoice['cl_store_docs_id_in']      = $tmpData->cl_store_docs_id_in;

                $tmpStatus2 = '';

                $arrInvoice['konst_symb']               = $this->settings->konst_symb;
                $arrInvoice['cl_invoice_types_id']      = $tmpInvoiceType;

                if (!is_null($tmpData->cl_partners_book['cl_payment_types_id'])) {
                    $clPayment = $tmpData->cl_partners_book['cl_payment_types_id'];
                    $spec_symb = $tmpData->cl_partners_book['spec_symb'];
                } else {
                    $clPayment = $this->settings['cl_payment_types_id'];
                    $spec_symb = "";
                }
                $arrInvoice['cl_payment_types_id'] = $clPayment;
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
                    $arrInvoice['delivery_number'] = $tmpData['rdn_number'];
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

                    $row = $this->InvoiceArrivedManager->insert($arrInvoice);


                    //$arrInvoice2['id'] = $tmpData->cl_invoice_id;
                    //$row2 = $this->InvoiceArrivedManager->update($arrInvoice2);

                    $this->update(['id' => $id, 'cl_invoice_id' => $row->id]);
                    $invoiceId = $row->id;
                    $tmpStatus = "created";
                } else {
                    //update invoice
                    $arrInvoice['id'] = $tmpData->cl_invoice_id;
                    $arrInvoice['delivery_number'] = $tmpData['rdn_number'];
                    unset($arrInvoice['inv_date']);
                    unset($arrInvoice['vat_date']);
                    unset($arrInvoice['vat_active']);
                    unset($arrInvoice['konst_symb']);

                    $this->InvoiceArrivedManager->update($arrInvoice);
                    $invoiceId = $tmpData->cl_invoice_id;
                    $tmpStatus = "updated";
                }
                $this->PairedDocsManager->insertOrUpdate(['cl_invoice_id' => $invoiceId, 'cl_delivery_note_in_id' => $tmpData['id']]);

                $this->InvoiceArrivedManager->updateInvoiceSum($invoiceId);



                //create pairedocs record
                //$this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_invoice_id' => $invoiceId));
                return (['status' => 'OK', 'data' => ['tmpStatus' => $tmpStatus, 'invoiceId' => $invoiceId, 'tmpStatus2' => $tmpStatus2]]);

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
                $invoiceId = $tmpData['cl_invoice_arrived_id'];
                $this->update(['id' => $id, 'cl_invoice_arrived_id' => NULL]);
                if ($tmpPaired = $this->PairedDocsManager->findAll()->where('cl_delivery_note_in_id = ? AND cl_invoice_arrived_id IS NOT NULL', $id)->limit(1)->fetch()) {
                    $tmpPaired->delete();
                }
                //bdump($tmpData);
                if (!is_null($invoiceId) && $tmpInvoice = $this->InvoiceArrivedManager->find($invoiceId)){
                    $this->InvoiceArrivedManager->update(['id' => $invoiceId, 'cl_delivery_note_in_id' => NULL]);
                }
                if (!is_null($invoiceId) && $tmpStoreDocs = $this->StoreDocsManager->findAll()->where('cl_invoice_arrived_id = ?', $invoiceId)){
                    foreach($tmpStoreDocs as $key => $one) {
                        $this->StoreDocsManager->update(['id' => $key, 'cl_invoice_arrived_id' => NULL]);
                        if ($tmpPaired = $this->PairedDocsManager->findAll()->where('cl_store_docs_id = ? AND cl_invoice_arrived_id = ? ', $key, $invoiceId)->limit(1)->fetch()) {
                            $tmpPaired->delete();
                        }
                    }
                }

                $arrRet['success'] = 'Vazba_byla_zrušena';
            }
        }catch (Exception $e) {
            $arrRet['error'] = 'Došlo_k_chybě';
            Debugger::log($e->getMessage());
        }
        return $arrRet;
    }


}

