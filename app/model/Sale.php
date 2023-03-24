<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Sale management.
 */
class SaleManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_sale';

	
	/** @var Nette\Database\Context */
	public $SaleItemsManager;			
	/** @var Nette\Database\Context */
	public $RatesVatManager;
    /** @var Nette\Database\Context */
    public $CashManager;
	
	public $settings;
	
	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					SaleItemsManager $SaleItemsManager, RatesVatManager $RatesVatManager, CompaniesManager $CompaniesManager,
                    CashManager $CashManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      $this->SaleItemsManager = $SaleItemsManager;
	      $this->RatesVatManager = $RatesVatManager;
          $this->CashManager = $CashManager;
	      $this->settings = $CompaniesManager->getTable()->fetch();	

	  }    	
		
	
	public function updateSaleSum($id)
	{
	    //$this->id = $id;
	    $tmpData = $this->find($id);
	    //PDP 04.11.2015
	    if ($tmpData->pdp == 1)
	    {
		    $recalcItems = $this->SaleItemsManager->findBy(array('cl_sale_id' => $id));
		    foreach($recalcItems as $one)
		    {
                //$data = new \Nette\Utils\ArrayHash;
                //$data['price_s'] = $one['price_s'] * $oldrate / $rate;

                //if ($this->settings->platce_dph == 1)
                $data['price_e2_vat'] = 0;
                $one->update($data);
		    }	    
	    }else
	    {
		    $recalcItems = $this->SaleItemsManager->findBy(array('cl_sale_id' => $id));
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
	    }

	    $price_e2 = $this->SaleItemsManager->findBy(array('cl_sale_id' => $id))->sum('price_e2');
	    $price_e2_vat = $this->SaleItemsManager->findBy(array('cl_sale_id' => $id))->sum('price_e2_vat');
	    $parentData = array();
	    $parentData['id'] = $id;
	    $parentData['price_e2'] = $price_e2;
	    $parentData['price_e2_vat'] = $price_e2_vat;		


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
			$totalBase = $this->SaleItemsManager->findBy(array('cl_sale_id' => $id, 'vat' => $one['rates']))
									->sum('price_e2');
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
	    
	    //vypocet DPH poprve
	    $parentData['price_vat1'] = round($parentData['price_base1'] * ($parentData['vat1']/100),2);
	    $parentData['price_vat2'] = round($parentData['price_base2'] * ($parentData['vat2']/100),2);			
	    $parentData['price_vat3'] = round($parentData['price_base3'] * ($parentData['vat3']/100),2);	    

	    //celkova castka pokud jde o platce DPH
	    if ($this->settings->platce_dph == 1)
	    {
            $parentData['price_e2']	    = $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'] + $parentData['price_base0'];
            $parentData['price_e2_vat'] = $parentData['price_e2'] + $parentData['price_vat1'] + $parentData['price_vat2'] + $parentData['price_vat3'] ;
	    }
	    

	    //zohlednime slevu, pokud je v procentech, vypočteme z procent absolutní částku slevy
	    //slevu budeme odečítat tak, že ji odečteme poměrnou částí z jednotlivých základů
	    $tmpDisc = $tmpData['discount_abs'];
	    if ($tmpData['discount'] <> 0){
            if ($this->settings->platce_dph == 1)
            {
               $tmpDisc = $parentData['price_e2_vat'] * ($tmpData['discount'] / 100);
            }else{
               $tmpDisc = $parentData['price_e2'] * ($tmpData['discount'] / 100);
            }
		  $parentData['discount_abs'] = $tmpDisc;
	    }
	    if ($tmpDisc <> 0 && $parentData['price_e2_vat'] != 0){
            $tmpRate1 =  $parentData['price_base1'] / $parentData['price_e2_vat'];
            $tmpRate2 =  $parentData['price_base2'] / $parentData['price_e2_vat'];
            $tmpRate3 =  $parentData['price_base3'] / $parentData['price_e2_vat'];
            $tmpRate0 =  $parentData['price_base0'] / $parentData['price_e2_vat'];

            $parentData['price_base1'] = $parentData['price_base1'] - ($tmpDisc * $tmpRate1);
            $parentData['price_base2'] = $parentData['price_base2'] - ($tmpDisc * $tmpRate2);
            $parentData['price_base3'] = $parentData['price_base3'] - ($tmpDisc * $tmpRate3);
            $parentData['price_base0'] = $parentData['price_base0'] - ($tmpDisc * $tmpRate0);
	    }

	    //vypocet DPH podruhe, protoze jiz byla aplikovana sleva
	    $parentData['price_vat1'] = round($parentData['price_base1'] * ($parentData['vat1']/100),2);		
	    $parentData['price_vat2'] = round($parentData['price_base2'] * ($parentData['vat2']/100),2);			
	    $parentData['price_vat3'] = round($parentData['price_base3'] * ($parentData['vat3']/100),2);			
		
	    //PDP 04.11.2015
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
			$parentData['price_e2'] = $parentData['price_base1'] + $parentData['price_base2'] + $parentData['price_base3'] + $parentData['price_base0'];
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
                $parentData['price_correction'] = round($parentData['price_e2_vat'],$decimal_places) - $parentData['price_e2_vat'];
            }else{
                $parentData['price_correction'] = round($parentData['price_e2'],$decimal_places) - $parentData['price_e2'];
            }
	    $parentData['price_e2_vat'] = round($parentData['price_e2_vat'],$decimal_places);


	    if ($this->settings->platce_dph == 1)
	    {
		    $tmpPrice = $parentData['price_e2_vat'];
	    }else{
		    $tmpPrice = $parentData['price_e2'];
	    }

	    $parentData['pay_date'] = new Nette\Utils\DateTime();




	    //Debugger::fireLog($parentData);

	    $this->update($parentData);

        //25.05.2019 - if cl_payment_types.payment_type is cash - make update or new record on cl_cash
        //15.08.2019 - record in cl_cash should be made only when is not empty sale_number
        if ( !empty($tmpData->sale_number) && !is_null($tmpData['cl_payment_types_id']) && $tmpData->cl_payment_types->payment_type == 1){
            $tmpCashData = array();

            $tmpCashData['cl_sale_id']           = $parentData->id;
            $tmpCashData['cl_cash_id']           = $tmpData->cl_cash_id;
            $tmpCashData['cl_partners_book_id']  = $tmpData->cl_partners_book_id;
            $tmpCashData['inv_date']             = $tmpData->inv_date;
            $tmpCashData['cl_company_branch_id'] = $tmpData->cl_company_branch_id;
            $tmpCashData['title']               = 'Úhrada prodejky '.$tmpData->sale_number;
            if ($this->settings->platce_dph == 1)
                $tmpCashData['cash'] = $parentData['price_e2_vat'];
            else
                $tmpCashData['cash'] = $parentData['price_e2'];

            if ($tmpData->sale_type == 1){
                if (!is_null($tmpData->cl_company_branch_id) && !is_null($tmpData->cl_company_branch->cl_number_series_id_cashout))
                {
                    $tmpCashData['cl_number_series_id'] = $tmpData->cl_company_branch->cl_number_series_id_cashout;
                }
                $tmpCashData['cash'] = -abs($tmpCashData['cash']);
            }else{
                if (!is_null($tmpData->cl_company_branch_id) && !is_null($tmpData->cl_company_branch->cl_number_series_id_cashin))
                {
                    $tmpCashData['cl_number_series_id'] = $tmpData->cl_company_branch->cl_number_series_id_cashin;
                }
            }
            $tmpCashData['cl_currencies_id'] = $tmpData->cl_currencies_id;
            $tmpCashData['currency_rate']    = $tmpData->currency_rate;
            //bdump($tmpCashData);
            //die;
            $tmpRetCashId = $this->CashManager->makeCash($tmpCashData);
            if (!is_null($tmpRetCashId))
                $this->update(array('id' => $parentData->id, 'cl_cash_id' => $tmpRetCashId));
        }

	}

	
}

