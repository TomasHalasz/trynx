<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * InvoiceArrived management.
 */
class InvoiceArrivedManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_invoice_arrived';

	
	/** @var Nette\Database\Context */
	public $InvoiceItemsManager;			
	/** @var Nette\Database\Context */
	public $RatesVatManager;				
	/** @var Nette\Database\Context */
	public $InvoiceArrivedPaymentsManager;				
	/** @var Nette\Database\Context */
	public $InvoiceArrivedCommissionManager;
    /** @var Nette\Database\Context */
    public $CashManager;
    /** @var \App\Model\StatusManager */
    public $StatusManager;
    /** @var \App\Model\PairedDocsManager */
    public $PairedDocsManager;
    /** @var \App\Model\CompaniesManager */
    public $CompaniesManager;
    /** @var \App\Model\BankAccountsManager */
    public $BankAccountsManager;
	
	public $settings;
	
	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					RatesVatManager $RatesVatManager, InvoiceArrivedPaymentsManager $InvoiceArrivedPaymentsManager,CompaniesManager $CompaniesManager, BankAccountsManager $BankAccountsManager,
					InvoiceArrivedCommissionManager $InvoiceArrivedCommissionManager, CashManager $CashManager, StatusManager $StatusManager, PairedDocsManager $pairedDocsManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      $this->RatesVatManager                    = $RatesVatManager;
	      $this->InvoiceArrivedPaymentsManager      = $InvoiceArrivedPaymentsManager;
	      $this->InvoiceArrivedCommissionManager    = $InvoiceArrivedCommissionManager;
          $this->BankAccountsManager                = $BankAccountsManager;
	      $this->CashManager                        = $CashManager;
          $this->CompaniesManager                   = $CompaniesManager;
	      $this->settings                           = $CompaniesManager->getTable()->fetch();
          $this->StatusManager                      = $StatusManager;
          $this->PairedDocsManager                  = $pairedDocsManager;
	  }

	public function updateInvoiceSum($id)
	{
	    //$this->id = $id;
	    $tmpData = $this->find($id);	  
	    $parentData = new \Nette\Utils\ArrayHash;
	    $parentData['id'] = $id;
  	    /*$parentData['price_payed'] = $this->InvoiceArrivedPaymentsManager->findAll()->where(array('cl_invoice_arrived_id' => $id,
    										       'pay_type' => 0))->sum('pay_price');

	    if ($this->settings->platce_dph == 1)
	    {
			$tmpPrice = $tmpData['price_e2_vat'];
	    }else{
			$tmpPrice = $tmpData['price_e2'];
	    }

	    if ($parentData['price_payed'] == $tmpPrice)
	    {
		    $parentData['pay_date'] = $this->InvoiceArrivedPaymentsManager->findAll()->where(array('cl_invoice_arrived_id' => $id,
												   'pay_type' => 0))->max('pay_date');
	    }else{
		    $parentData['pay_date'] = NULL;
	    }*/

        $this->paymentUpdate($id);

	    //update paired commission
  	    $parentData['price_on_commission'] = $this->InvoiceArrivedCommissionManager->findAll()->where(array('cl_invoice_arrived_id' => $id,
										       ))->sum('amount');	    
	    

	    //Debugger::fireLog($parentData);

	    $this->update($parentData);



	}



    /**update of invoice payment
     * @param $id
     */
    public function paymentUpdate($id)
    {
        $parentData = [];
        $tmpData = $this->find($id);
        //zaokrouhleni
        $decimal_places = $tmpData->cl_currencies->decimal_places;
        $parentData['price_payed'] = $this->InvoiceArrivedPaymentsManager->findAll()->where(['cl_invoice_arrived_id' => $id,
                                        'pay_type' => 0])->sum('pay_price');

        if ($this->settings->platce_dph == 1) {
            // $tmpPrice = round($tmpData['price_e2_vat'],$decimal_places);
            $tmpPrice = $tmpData['price_e2_vat'];
        }else{
            //$tmpPrice = round($tmpData['price_e2'],$decimal_places);
            $tmpPrice = $tmpData['price_e2'];
        }

        if ($parentData['price_payed'] == $tmpPrice) {
            $parentData['pay_date'] = $this->InvoiceArrivedPaymentsManager->findAll()->where(['cl_invoice_arrived_id' => $id,
                                            'pay_type' => 0])->max('pay_date');
        }else{
            $parentData['pay_date'] = NULL;
        }
        $tmpData->update($parentData);

        //25.05.2019 - cl_cash in case of cl_payment_types == 2
        //get every cl_invoice_payments and for each cl_payment_types make record in cl_cash

        foreach($tmpData->related('cl_invoice_arrived_payments') as $key=>$one)
        {
            bdump($one, 'payment cash');
            if (!is_null($one['cl_payment_types_id']) && $one->cl_payment_types->payment_type == 1){
                $tmpCashData = [];
                $tmpCashData['cl_invoice_arrived_id']   = $tmpData->id;
                $tmpCashData['cl_company_branch_id']    = $tmpData->cl_company_branch_id;
                $tmpCashData['cl_cash_id']              = $one->cl_cash_id;
                $tmpCashData['cl_partners_book_id']     = $tmpData->cl_partners_book_id;
                $tmpCashData['inv_date']                = $one->pay_date;
                $tmpCashData['title']                   = 'Úhrada faktury '.$tmpData->inv_number;
                $tmpCashData['cash']                    = -abs($one['pay_price']);
                $tmpCashData['cl_currencies_id']        = $one->cl_currencies_id;
                //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
                $tmpCashData['currency_rate']           = $tmpData->currency_rate;
                bdump($tmpCashData);
                $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);
                if (!is_null($tmpRetCashId)) {
                    $retDat = $this->CashManager->find($tmpRetCashId);
                    $arrData['cl_cash_id'] = $tmpRetCashId;
                    if (empty($one['pay_doc'])) {
                        $arrData['pay_doc'] = $retDat->cash_number;
                    }
                    $one->update($arrData);
                    $this->PairedDocsManager->insertOrUpdate(['cl_invoice_arrived_id' => $one->cl_invoice_arrived_id, 'cl_cash_id' => $tmpRetCashId]);
                }
                //TODO: dořešit odeslání částečné hotovostní úhrady do EET
            }


        }


       // $this->makePayment($id);
    }

    public function makePayment($id){
        $arrRet = [];
        $tmpData = $this->find($id);
       // bdump($tmpData, 'makePayment');
        if ($tmpData) {
            if ($tmpData->cl_company['platce_dph'] == 1)
                $priceCol = 'price_e2_vat';
            else
                $priceCol = 'price_e2';

            if ($tmpData['pay_date'] == NULL || ($tmpData[$priceCol] > $tmpData['price_payed'])) {
                $max = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_invoice_arrived_id = ?', $id)->max('item_order');
                $arrData                            = [];
                $arrData['cl_invoice_arrived_id']   = $id;
                $arrData['item_order']          = $max + 1;
                $arrData['cl_currencies_id']    = $tmpData['cl_currencies_id'];
                $arrData['cl_payment_types_id'] = $tmpData['cl_payment_types_id'];
                $arrData['pay_price']           = $tmpData[$priceCol] - $tmpData['price_payed'];
                $arrData['pay_date']            = new Nette\Utils\DateTime();
                $row = $this->InvoiceArrivedPaymentsManager->insert($arrData);

                $arrData                = [];
                if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?','invoice_arrived',1)->fetch())
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

    public function exportStereo($dataReport, $dataForm)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $arrData = [];
        foreach($dataReport as $key => $one) {
            $lcTyp = ($one['pdp'] == 1) ? 'M' : 'F';
            $lcTypDPH = ($one['pdp'] == 1) ? 'URP' : 'P';
            $lcForma_uh = (!is_null($one['cl_payment_types_id'])) ? $one->cl_payment_types['short_desc'] : '';
            if (empty($lcForma_uh) && !is_null($one['cl_payment_types_id'])) {
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
            if (empty($lcDruh)) {
                $lcDruh = $dataForm['short_desc'];
            }

            //$items = $one->related('cl_invoice_items')->order('price_e2_vat DESC')->limit(1)->fetch();
            //$lcText = ($items) ? $items['item_label'] : $dataForm['item_label'];
            $lcText = $one['inv_title'];

            $lcText = str_replace(';', '', $lcText);

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



            if ($this->settings['platce_dph'] == 1) {
                $sumTotalF = $one['price_e2_vat'];
                $workVAT = 'PRAVDA';
            } else {
                $sumTotalF = $one['price_e2'];
                $workVAT = 'NEPRAVDA';
            }
            //$exportImport = ($one['export'] == 1) ? 'PRAVDA' : 'NEPRAVDA';
            //$lcStorno = ($one['storno'] == 1) ? 'PRAVDA' : 'NEPRAVDA';

            /*if (!is_null($one['cl_bank_accounts_id'])) {
                $lcAccount = $one->cl_bank_accounts['account_number'];
                $lcBank = $one->cl_bank_accounts['bank_code'];
            } elseif (!is_null($one['cl_currencies_id']) && $tmpBank = $this->BankAccountsManager->findAll()->where('cl_currencies_id = ? AND default_account = 1', $one['cl_currencies_id'])->limit(1)->fetch()) {
                $lcAccount = $tmpBank['account_number'];
                $lcBank = $tmpBank['bank_code'];
            } elseif ($tmpBank = $this->BankAccountsManager->findAll()->limit(1)->fetch()) {
                $lcAccount = $tmpBank['account_number'];
                $lcBank = $tmpBank['bank_code'];
            } else {
                $lcAccount = '';
                $lcBank = '';
            }*/
            $lcAccount = '';
            $lcBank = '';

            $lcVat1 = 0;
            $lcVat2 = 0;
            $lcVat3 = 0;
            $tmpRates = $this->RatesVatManager->findAllValid($one['inv_date'], $one->cl_company['cl_countries_id']);
            foreach ($tmpRates as $keyR => $oneR) {
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
                'Agenda' => 'PF',
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
                //'Dovoz/ vývoz' => $exportImport,
                'Způsob zaúčtování' => 'N',
                //'Stornováno' => $lcStorno,
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
                'Směr platby' => 'V',
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
                'Evidenční číslo' => str_replace('.',',',(string)$one['rinv_number']), //TH 15.01.2023 - Jugová - 'Evidenční číslo' => str_replace('.',',',(string)$one['var_symb']),
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



}

