<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * InvoiceInternal management.
 */
class InvoiceInternalManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_invoice_internal';

	
	/** @var Nette\Database\Context */
	public $InvoiceInternalItemsManager;
	/** @var Nette\Database\Context */
	public $RatesVatManager;				
	/** @var Nette\Database\Context */
	public $InvoiceInternalPaymentsManager;
    /** @var Nette\Database\Context */
    public $CashManager;

    public $settings;
	
	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					InvoiceInternalItemsManager $InvoiceInternalItemsManager, RatesVatManager $RatesVatManager,
                    InvoiceInternalPaymentsManager $InvoiceInternalPaymentsManager,CompaniesManager $CompaniesManager,
                    CashManager $CashManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      $this->InvoiceInternalItemsManager = $InvoiceInternalItemsManager;
	      $this->RatesVatManager = $RatesVatManager;
	      $this->InvoiceInternalPaymentsManager = $InvoiceInternalPaymentsManager;
	      $this->CashManager = $CashManager;
	      $this->settings = $CompaniesManager->getTable()->fetch();	

	  }    	
		
	  
    public function updateInvoiceProfit($id)
	{
		$tmpData = $this->find($id);
		$price_s			= $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_s * quantity');
		$price_e2			= $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_e2');
		$price_e2_vat		= $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_e2_vat');

		/*$price_s_back		= $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_s * quantity');
		$price_e2_back		= $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2');
		$price_e2_vat_back	= $this->InvoiceItemsBackManager->findBy(array('cl_invoice_id' => $id))->sum('price_e2_vat');*/

		$parentData					= new \Nette\Utils\ArrayHash;
		$parentData['id']			= $id;
		$parentData['price_s']		= $price_s;         // - $price_s_back;
		$parentData['price_e2']		= $price_e2;        // - $price_e2_back;
		$parentData['price_e2_vat'] = $price_e2_vat;    // - $price_e2_vat_back;
		
		$parentData['profit_abs']	= $parentData['price_e2'] - $parentData['price_s'];
		
		if ($parentData['price_e2'] != 0){
			//$parentData['profit'] 	= (($parentData['price_e2'] / $parentData['price_s']) - 1) * 100;
			$parentData['profit'] 	= 100 - (($parentData['price_s'] / $parentData['price_e2']) * 100);
			//$profit = 100 - (($priceS / $tmpBondItem->price_e2) * 100);
		}else{
			$parentData['profit'] 	= 100;
		}
		
		$this->update($parentData);
		
	}
	
	public function updateInvoiceSum($id)
	{
	    //$this->id = $id;
	    $tmpData = $this->find($id);
	    //PDP 04.11.2015
	    if ($tmpData->pdp == 1)
	    {
		    $recalcItems = $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id));
		    foreach($recalcItems as $one)
		    {
				$data['price_e2_vat'] = 0;
				$one->update($data);
		    }
			/*$recalcItems = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_internal_id' => $id));
			foreach($recalcItems as $one)
			{
				$data['price_e2_vat'] = 0;
				$one->update($data);
			}*/
		}else
	    {
		    $recalcItems = $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id));
		    foreach($recalcItems as $one)
		    {
				$calcVat = round($one['price_e2'] * (  $one['vat'] / 100 ), 2);
				$data['price_e2_vat'] = $one['price_e2'] + $calcVat;
				$one->update($data);
		    }
			/*$recalcItems = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_internal_id' => $id));
			foreach($recalcItems as $one)
			{
				$calcVat = round($one['price_e2'] * (  $one['vat'] / 100 ), 2);
				$data['price_e2_vat'] = $one['price_e2'] + $calcVat;
				$one->update($data);
			}*/
	    }
	    
	    if ($tmpData['export'] == 1){
			$recalcItems = $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id));
			foreach($recalcItems as $one)
			{
				$data['price_e2_vat'] = $one['price_e2'];
				$data['vat'] = 0;
				$one->update($data);
			}
			/*$recalcItems = $this->InvoiceItemsBackManager->findBy(array('cl_invoice_internal_id' => $id));
			foreach($recalcItems as $one)
			{
				$data['price_e2_vat'] = $one['price_e2'];
				$data['vat'] = 0;
				$one->update($data);
			}*/
		}

        $price_s			= $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_s * quantity');
	    $price_e2			= $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_e2');
	    $price_e2_vat		= $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_e2_vat');
	    //if ($tmpData->cl_invoice_types->inv_type != 2) {
        /*$price_s_back		= $this->InvoiceItemsBackManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_s * quantity');
        $price_e2_back		= $this->InvoiceItemsBackManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_e2');
        $price_e2_vat_back	= $this->InvoiceItemsBackManager->findBy(array('cl_invoice_internal_id' => $id))->sum('price_e2_vat');*/
	    //}else{
		    //$price_e2_back		= 0;
		    //$price_e2_vat_back	= 0;
	    //}
	    $parentData			= new \Nette\Utils\ArrayHash;
	    $parentData['id']		= $id;
        $parentData['price_s']	= $price_s; // - $price_s_back;
	    $parentData['price_e2']	= $price_e2;    // - $price_e2_back;
	    $parentData['price_e2_vat'] = $price_e2_vat;    // - $price_e2_vat_back;

        $parentData['profit_abs']	= $parentData['price_e2'] - $parentData['price_s'];

        if ($parentData['price_e2'] != 0){
            //$parentData['profit'] = (($parentData['price_e2'] / $parentData['price_s']) - 1) * 100;
			$parentData['profit'] 	= 100 - (($parentData['price_s'] / $parentData['price_e2']) * 100);
        }else{
            $parentData['profit'] = 100;
        }


        $RatesVatValid = $this->RatesVatManager->findAllValid($tmpData->inv_date);
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
	    $parentData['base_payed0'] =0;
	    $parentData['base_payed1'] =0;
	    $parentData['base_payed2'] =0;
	    $parentData['base_payed3'] =0;
	    $parentData['vat_payed1'] =0;
	    $parentData['vat_payed2'] =0;
	    $parentData['vat_payed3'] =0;	
	    $parentData['advance_payed'] =0;
	    //Debugger::fireLog($parentData);	    	
	    foreach($RatesVatValid as $key => $one)
	    {
			$totalBase = $this->InvoiceInternalItemsManager->findBy(array('cl_invoice_internal_id' => $id, 'vat' => $one['rates']))->sum('price_e2');
		    //$totalBase = $totalBase - $this->InvoiceItemsBackManager->findBy(array('cl_invoice_internal_id' => $id, 'vat' => $one['rates']))->sum('price_e2');

			if ($totalBase != 0)
			{
				if ($parentData['vat1'] == 0)
				{
					$parentData['price_base1'] = $totalBase * ( $tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1 );
					//$parentData['price_vat1'] = $totalBase * ($one['rates']/100);
					$parentData['vat1'] = $one['rates'];
				}elseif ($parentData['vat2'] == 0){
					$parentData['price_base2'] = $totalBase * ( $tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1 );
					//$parentData['price_vat2'] = $totalBase * ($one['rates']/100);		
					$parentData['vat2'] = $one['rates'];
				}elseif ($parentData['vat3'] == 0){
					$parentData['price_base3'] = $totalBase * ( $tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1 );
					//$parentData['price_vat3'] = $totalBase * ($one['rates']/100);		
					$parentData['vat3'] = $one['rates'];
				}  else {
					$parentData['price_base0'] = $totalBase * ( $tmpData['export'] != 1 ? $tmpData['currency_rate'] : 1 );
				}
			}

	    //Debugger::fireLog($parentData);	    
	    }

	    //odecteni zalohy
	    $price_payed = $this->InvoiceInternalPaymentsManager->findAll()->where(array('cl_invoice_internal_id' => $id,
											       'pay_type' => 1));
	    foreach($price_payed as $key => $one)
	    {
            if ($one->pay_vat == 1)
            { //danova zaloha
                //PDP 04.11.2015
                if ($tmpData->pdp == 1)
                {
                    $price = $one->pay_price;
                }else{
                    $price = $one->pay_price / (1+($one->vat/100));
                }

                if ($parentData['vat1'] == $one->vat)
                {
                    $parentData['base_payed1'] = $parentData['base_payed1'] + $price;
                    $parentData['vat_payed1'] = ($tmpData->pdp == 0) ? $price * ($one->vat/100) : 0;
                }elseif ($parentData['vat2'] == $one->vat)
                {
                    $parentData['base_payed2'] = $parentData['base_payed2'] + $price;
                    $parentData['vat_payed2'] =  ($tmpData->pdp == 0) ? $price * ($one->vat/100) : 0;
                }elseif ($parentData['vat3'] == $one->vat)
                {
                    $parentData['base_payed3'] = $parentData['base_payed3'] + $price;
                    $parentData['vat_payed3'] =  ($tmpData->pdp == 0) ? $price * ($one->vat/100) : 0;
                }else
                {
                    $parentData['base_payed0'] = $parentData['base_payed0'] + $price;
                }
            }else{ //nedanova zaloha
                $parentData['advance_payed'] = $parentData['advance_payed'] + $one->pay_price;
            }
	    }
	    //odecteni danove zalohy
	    $parentData['price_base1'] = $parentData['price_base1'] - $parentData['base_payed1'];
	    $parentData['price_base2'] = $parentData['price_base2'] - $parentData['base_payed2'];
	    $parentData['price_base3'] = $parentData['price_base3'] - $parentData['base_payed3'];
	    $parentData['price_base0'] = $parentData['price_base0'] - $parentData['base_payed0'];

	    //vypocet DPH
	    $parentData['price_vat1'] =  ($tmpData->pdp == 0) ? round($parentData['price_base1'] * ($parentData['vat1']/100),2) : 0;
	    $parentData['price_vat2'] =  ($tmpData->pdp == 0) ? round($parentData['price_base2'] * ($parentData['vat2']/100),2) : 0;
	    $parentData['price_vat3'] =  ($tmpData->pdp == 0) ? round($parentData['price_base3'] * ($parentData['vat3']/100),2) : 0;
		
	    //PDP 04.11.2015
		$parentData['pdp'] = $tmpData->pdp;
	    if ($tmpData->pdp == 1)
	    {
			$parentData['price_vat1'] = 0;		
			$parentData['price_vat2'] = 0;			
			$parentData['price_vat3'] = 0;	
			$parentData['vat_payed1'] = 0;	    
			$parentData['vat_payed2'] = 0;
			$parentData['vat_payed3'] = 0;
	    }	

	    //celkova castka pokud jde o platce DPH
	    if ($this->settings->platce_dph == 1)
	    {
			$parentData['price_e2']	    = $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'] + $parentData['price_base0'];
			$parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'];
	    }

	    //zaokrouhleni
	    //$decimal_places = $tmpData->cl_currencies->decimal_places;
        if ($tmpData->cl_payment_types->payment_type == 1) {
            //10.09.2020 - decimal places in case of cash payment
            $decimal_places = $tmpData->cl_currencies->decimal_places_cash;
        }else{
            //other payment types  transfer, credit, delivery
            $decimal_places = $tmpData->cl_currencies->decimal_places;
        }

	    if ($this->settings->platce_dph == 1)
	    {
            $parentData['price_correction'] = 0;
            if ($tmpData->cl_payment_types->payment_type == 1) {
                //17.10.2019 - rounding from whole price in case of cash payment
                if ($tmpData['export'] == 0){
                    $parentData['price_e2']	        = $parentData['price_e2'] / $tmpData['currency_rate'];
                    $parentData['price_e2_vat']	    = $parentData['price_e2_vat'] / $tmpData['currency_rate'];
                }
                $parentData['price_correction'] = round($parentData['price_e2_vat'], $decimal_places) - $parentData['price_e2_vat'];
                $parentData['price_e2_vat'] = round($parentData['price_e2_vat'], $decimal_places);
            }else{//other payment types  transfer, credit, delivery
                $parentData = $this->correctionInBase($parentData,$tmpData, $decimal_places);
                bdump($parentData);
            }
        }else{
            $parentData['price_correction'] = round($parentData['price_e2'],$decimal_places) - $parentData['price_e2'];
            $parentData['price_e2']		= round($parentData['price_e2'],$decimal_places);
        }

	    /*$parentData['price_payed'] = $this->InvoiceInternalPaymentsManager->findAll()->where(array('cl_invoice_internal_id' => $id,
											       'pay_type' => 0))->sum('pay_price');

	    if ($this->settings->platce_dph == 1)
	    {
			$tmpPrice = $parentData['price_e2_vat'];
	    }else{
			$tmpPrice = $parentData['price_e2'];
	    }

	    if ($parentData['price_payed'] == $tmpPrice)
	    {
		$parentData['pay_date'] = $this->InvoiceInternalPaymentsManager->findAll()->where(array('cl_invoice_internal_id' => $id,
												   'pay_type' => 0))->max('pay_date');
	    }else{
			$parentData['pay_date'] = NULL;
	    }*/

	    $this->paymentUpdate($id);


	    //Debugger::fireLog($parentData);
		bdump($parentData);
	    $this->update($parentData);

        //25.05.2019 - cl_cash in case of cl_payment_types == 2
        //get every cl_invoice_internal_payments and for each cl_payment_types make record in cl_cash
        foreach($tmpData->related('cl_invoice_internal_payments') as $key=>$one)
        {
            if (!is_null($one['cl_payment_types_id']) && $one->cl_payment_types->payment_type == 1){
                $tmpCashData = array();
                $tmpCashData['cl_invoice_internal_id']           = $tmpData->id;
                $tmpCashData['cl_company_branch_id']    = $tmpData->cl_company_branch_id;
                $tmpCashData['cl_cash_id']              = $one->cl_cash_id;
                $tmpCashData['cl_partners_book_id']     = $tmpData->cl_partners_book_id;
                $tmpCashData['inv_date']                = $one->pay_date;
                $tmpCashData['title']                   = 'Úhrada faktury '.$tmpData->inv_number;
                $tmpCashData['cash']                    = $one['pay_price'];
                $tmpCashData['cl_currencies_id']        = $one->cl_currencies_id;
                //TODO: dořešit kurz, který je použit při úhradě faktury. Zatím předpokládáme, že bude stejný jako u faktury
                $tmpCashData['currency_rate']           = $tmpData->currency_rate;

                $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);
                if (!is_null($tmpRetCashId)) {
                    $retDat = $this->CashManager->find($tmpRetCashId['id']);
                    $one->update(array('cl_cash_id' => $tmpRetCashId, 'pay_doc' => $retDat->cash_number));
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
        $parentData = array();
        $tmpData = $this->find($id);
        //zaokrouhleni
        $decimal_places = $tmpData->cl_currencies->decimal_places;
        $parentData['price_payed'] = $this->InvoiceInternalPaymentsManager->findAll()->where(array('cl_invoice_internal_id' => $id,
            'pay_type' => 0))->sum('pay_price');

        if ($this->settings->platce_dph == 1) {
           // $tmpPrice = round($tmpData['price_e2_vat'],$decimal_places);
            $tmpPrice = $tmpData['price_e2_vat'];
        }else{
            //$tmpPrice = round($tmpData['price_e2'],$decimal_places);
            $tmpPrice = $tmpData['price_e2'];
        }

        if ($parentData['price_payed'] == $tmpPrice) {
            $parentData['pay_date'] = $this->InvoiceInternalPaymentsManager->findAll()->where(array('cl_invoice_internal_id' => $id,
                'pay_type' => 0))->max('pay_date');
        }else{
            $parentData['pay_date'] = NULL;
        }
        $tmpData->update($parentData);
    }



    /** calculate price correction for all used price_base
     * @param $parentData
     * @param $tmpData
     * @return mixed
     */
	private function correctionInBase_2($parentData, $tmpData)
    {
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
        $testArr = array('0' => array( 'value' => $parentData['price_base0'], 'rate' => 0, 'base_name' => 'price_base0', 'vat_name' => '', 'correction_name' => 'correction_base0'),
                         '1' => array( 'value' => $parentData['price_base1'], 'rate' => $parentData['vat1'], 'base_name' => 'price_base1', 'vat_name' => 'price_vat1', 'correction_name' => 'correction_base1'),
                         '2' => array( 'value' => $parentData['price_base2'], 'rate' => $parentData['vat2'], 'base_name' => 'price_base2', 'vat_name' => 'price_vat2', 'correction_name' => 'correction_base2'),
                         '3' => array( 'value' => $parentData['price_base3'], 'rate' => $parentData['vat3'], 'base_name' => 'price_base3', 'vat_name' => 'price_vat3', 'correction_name' => 'correction_base3'));
        $biggest = array();
        $testVal = 0;
        foreach ($testArr as $key => $one)
        {
            //set correction to 0 for all. Correct value will be calculated in next step
            $parentData[$one['correction_name']] = 0;
            if ($one['value'] > $testVal){
                $biggest = $one;
                $testVal = $one['value'];
            }
        }

        //3.
        $baseCorrection = round($totalCorrection / (1 + ($biggest['rate'] / 100)),2);
        $parentData[$biggest['correction_name']] = $baseCorrection;

        //4.
        $parentData[$biggest['base_name']] = round($parentData[$biggest['base_name']] + $baseCorrection,2);

        //5.
        if ($biggest['vat_name'] != '')
        {
            $parentData[$biggest['vat_name']] =  round($parentData[$biggest['base_name']] * ($biggest['rate'] / 100),2);
        }

        //6.
        $parentData['price_e2'] = $parentData['price_base0'] + $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'];
        $parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'];

        $newTotal = $parentData['price_e2_vat'];

        $difference = $total - $newTotal;
        bdump($difference);
        if ($difference != 0)
        {
            $parentData[$biggest['base_name']] = $parentData[$biggest['base_name']] + $difference;
            $parentData[$biggest['correction_name']] = $parentData[$biggest['correction_name']] +  $difference;
            $parentData['price_e2'] =  $parentData['price_e2'] + $difference;
            $parentData['price_e2_vat'] =  $parentData['price_e2_vat'] + $difference;
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
        $totalCorrection = round($total - $parentData['price_e2_vat'],2);
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
            bdump($parentData);
            bdump($totalCorrection, 'totalCorrection');
            bdump($totalCorrection2, 'totalCorrection2');
            bdump($totalCorrection - $totalCorrection2, 'ooo');
            if ($testArr['0']['value'] != 0) {
                $parentData[$testArr['0']['correction_name']] = $totalCorrection - $totalCorrection2;
                $parentData[$testArr['0']['base_name']] = round($parentData[$testArr['0']['base_name']] + $totalCorrection - $totalCorrection2, 2);
            }


            $parentData['price_e2'] = $parentData['price_base0'] + $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'];
			//PDP 04.11.2015
			if ($parentData['pdp'] == 1)
			{
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


	
}

