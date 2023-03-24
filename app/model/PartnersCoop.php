<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Tracy\Debugger;
use Exception;

/**
 * Partners Coop management.
 */
class PartnersCoopManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_partners_coop';
	public $PriceListManager;    	

	public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
				    \App\Model\PriceListManager $PriceListManager)
	{
	    parent::__construct($db, $userManager, $user, $session, $accessor);
	    $this->PriceListManager = $PriceListManager;

	}    
    
	/*
	 * create copy of important data from master company, called by child company when enabling partnership
	 */
	public function createCoopData($coopData)
	{
	    //Debugger::fireLog($coopData);
	    //record from master cl_company -> cl_partners_book
	    if ($master = $this->database->table('cl_company')->where('id = ?',$coopData->master_cl_company_id)->fetch())
	    {
		//try if exist by ic
		if ($tmpCl_partners_book = $this->database->table('cl_partners_book')
							  ->where('cl_company_id = ? AND ico = ?', $coopData->cl_company_id,$master->ico)->fetch())
		{
		    //exist, use it
		    $cl_partners_book_id = $tmpCl_partners_book->id;
		}else{
		    //not exist, create new one
		    $arrData = new Nette\Utils\ArrayHash;
		    $arrData['cl_company_id'] = $coopData->cl_company_id;
		    $arrData['company'] = $master->name;
		    $arrData['street'] = $master->street;
		    $arrData['zip'] = $master->zip;
		    $arrData['city'] = $master->city;
		    $arrData['ico'] = $master->ico;
		    $arrData['dic'] = $master->dic;		    
		    $arrData['master_cl_company_id'] = $master->id;		    
		    $arrData['create_by'] = $this->user->getIdentity()->name;
		    $arrData['created'] = new \Nette\Utils\DateTime;		    
		    
		    $tmpInsert = $this->database->table('cl_partners_book')->insert($arrData);
		    $cl_partners_book_id = $tmpInsert->id;
		}
		//update cl_partners_coop.cl_partners_book_id to real id of partner in cl_partners_book
		$arrData = new Nette\Utils\ArrayHash;
		$arrData['id'] = $coopData->id;
		$arrData['cl_partners_book_id'] = $cl_partners_book_id;
		//Debugger::fireLog($arrData);
		$this->updateForeign($arrData);
		
		//records from master cl_pricelist -> cl_pricelist
		//if there is cl_pricelist_partner use his data, otherwise whole cl_pricelist
		if ($masterPartners = $this->database->table('cl_partners_book')->where('cl_company_id=? AND id=?',
									    $coopData->master_cl_company_id,$coopData->child_cl_partners_book_id)->fetch())
		{
		    
		    $masterData = $this->database->table('cl_pricelist_partner')->where('cl_company_id=? AND cl_partners_book_id=?',
										$coopData->master_cl_company_id,$coopData->child_cl_partners_book_id);
		    $i = 0;
		    if ($masterData->count() > 0 && $masterPartners->pricelist_partner == 1)
		    {
			$arrMasterData =  new Nette\Utils\ArrayHash;
			foreach($masterData as $one)
			{
			    //each row from cl_pricelist_partner
			    $arrRow = new Nette\Utils\ArrayHash;
			    foreach($one->cl_pricelist as $key => $oneColumn)
			    {
				//each column from cl_pricelist
				$arrRow[$key] = $oneColumn;
			    }
			    //update partners pricelist values
			    $arrRow['price']			= $one['price'];
			    $arrRow['price_vat']		= $one['price_vat'];
			    $arrRow['vat']			= $one['vat'];
			    $arrRow['cl_currencies_id']		= $one['cl_currencies_id'];
			    $arrRow['master_id']		= $one->cl_pricelist['id'];
			    $arrRow['master_cl_company_id']	= $coopData->master_cl_company_id;
			    $arrRow['cl_company_id']		= $coopData->cl_company_id;
			    if ($this->userManager->getUserById($this->user->getId())->cl_company->platce_dph)
			    {
				$arrRow['price_s'] = $one['price']; //stock price of master mustbe set to price	
			    }
			    else
			    {
				$arrRow['price_s'] = $one['price_vat']; //stock price of master mustbe set to price	
			    }
			    
			    $arrMasterData[$i++] = $arrRow;
			}
			//Debugger::fireLog($arrMasterData);
			//dump($arrMasterData);
			//die;

		    }else
		    {
			$masterData = $this->database->table('cl_pricelist')->where('cl_company_id=?',$coopData->master_cl_company_id);
			foreach($masterData as $one)
			{
			    //each row from cl_pricelist_partner
			    $arrRow = new Nette\Utils\ArrayHash;
			    foreach($one as $key => $oneColumn)
			    {
				//each column from cl_pricelist
				$arrRow[$key] = $oneColumn;
			    }			
			    $arrRow['master_id']		= $one['id'];
			    $arrRow['master_cl_company_id']	= $coopData->master_cl_company_id;
			    $arrRow['cl_company_id']		= $coopData->cl_company_id;							    
			    if ($this->userManager->getUserById($this->user->getId())->cl_company->platce_dph)
			    {
				$arrRow['price_s'] = $one['price']; //stock price of master mustbe set to price	
			    }
			    else
			    {
				$arrRow['price_s'] = $one['price_vat']; //stock price of master mustbe set to price	
			    }
			    $arrMasterData[$i++] = $arrRow;
			}
		    }

		    //dump($this->PricelistManager);
		    //die;
		    foreach($arrMasterData as $one)
		    {

			if ($tmpData = $this->PriceListManager->findOneBy(array('master_id' => $one['master_id'])))
			{//exist, must do update
			    $one['id'] = $tmpData['id'];
			    unset($one['price']); //for case of update, we must not overwrite prices
			    unset($one['price_vat']);
			    $this->PriceListManager->updateForeign($one);
			}else
			{//not exist, insert new one
			    unset($one['id']);
			    $this->PriceListManager->insertForeign($one);
			}
		    }
		}
		
		
	    }
	}
	
	


	
}

