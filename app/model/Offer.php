<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Offer management.
 */
class OfferManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_offer';

	
	
	/** @var Nette\Database\Context */
	public $OfferItemsManager;			
	/** @var Nette\Database\Context */
	public $OfferTaskManager;				
	
	
	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					OfferItemsManager $OfferItemsManager, OfferTaskManager $OfferTaskManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      $this->OfferItemsManager = $OfferItemsManager;
	      $this->OfferTaskManager = $OfferTaskManager;

	  }    	
			
	
	
	/*public function updateSum($id)
	{

	    $price_s = $this->OfferItemsManager->findBy(array('cl_offer_id' => $id))->sum('price_s*quantity');
	    $price_e2 = $this->OfferItemsManager->findBy(array('cl_offer_id' => $id))->sum('price_e2');
	    $price_w2 = $this->OfferTaskManager->findBy(array('cl_offer_id' => $id))->sum('work_rate*work_time');
	    $price_e2_vat = $this->OfferItemsManager->findBy(array('cl_offer_id' => $id))->sum('price_e2_vat');
	    $parentData = new \Nette\Utils\ArrayHash;
	    $parentData['id'] = $id;
	    $parentData['price_s'] = $price_s;
	    $parentData['price_w2'] = $price_w2;
	    $parentData['price_e2'] = $price_e2;
	    $parentData['price_e2_base'] = $price_e2 + $price_w2;	
	    $vatRate = $this->find($id)->vat;
		//dump($vatRate);
		$calcVat = round(($price_w2) * ($vatRate / 100), 2);
	    $parentData['price_e2_vat'] = $price_e2_vat + $price_w2 + $calcVat;		
	    $this->update($parentData);		    
	} */   
	
	public function updateSum($id)
	{

	    $parentData = array();
	    $parentData['id'] = $id;	    
	    if ($this->find($id)->total_sum_off == 0)
	    {
            $price_s = $this->OfferItemsManager->findBy(array('cl_offer_id' => $id))->sum('price_s*quantity');
            $price_e2 = $this->OfferItemsManager->findBy(array('cl_offer_id' => $id))->sum('price_e2');
            $price_w = $this->OfferTaskManager->findBy(array('cl_offer_id' => $id))->sum('work_rate*work_time');
            $price_w2 = $this->OfferTaskManager->findBy(array('cl_offer_id' => $id))->sum('(work_rate*work_time)*(1+(profit/100))');
            $price_e2_vat = $this->OfferItemsManager->findBy(array('cl_offer_id' => $id))->sum('price_e2_vat');

            $parentData['price_s'] = $price_s;		    //costs for items
            $parentData['price_w'] = $price_w;		    //costs for work
            $parentData['price_w2'] = $price_w2;		    //sell for work
            $parentData['price_e2'] = $price_e2;		    //sell for items without vat
            $tmpDelivery_price = $this->find($id)->delivery_price;
            $parentData['price_e'] = $price_s + $price_w + $tmpDelivery_price ;	    //total costs works+items+delivery
            $parentData['price_e2_base'] = $price_e2 + $price_w2 + $tmpDelivery_price ; //total sell works+items+delivery
            $vatRate = $this->find($id)->vat;
                //dump($vatRate);
            $calcVat = round(($price_w2+$tmpDelivery_price) * ($vatRate / 100), 2);
            $parentData['price_e2_vat'] = $price_e2_vat + $price_w2 + $tmpDelivery_price + $calcVat;	//total sell with vat
	    }else{
            $parentData['price_s'] = 0;		//costs for items
            $parentData['price_w'] = 0;		//costs for work
            $parentData['price_w2'] = 0;		//sell for work
            $parentData['price_e2'] = 0;		//sell for items without vat
            $parentData['price_e'] = 0 ;		//total costs works+items+delivery
            $parentData['price_e2_base'] = 0 ;	//total sell works+items+delivery
            $parentData['price_e2_vat'] = 0;	//total sell with vat
	    }
	    bdump($parentData,'updateSum');
	    $this->update($parentData);		    
	    
	}
	
	
	/**
	 * Called from api
	 * @param type $data - array(	'cl_company_id',
	 *				'offer_date',
	 *				'cl_partners_name',
	 *				'currency_code',
	 *				'cm_title',
	 *				'cl_partners_book_id',
	 *							)
	 */
	public function ApiCreateDoc($dataApi)
	{	
		$data = new Nette\Utils\ArrayHash;
		$data['cl_company_id']	= $dataApi['cl_company_id'];
		$data['offer_date']	= $dataApi['offer_date'];
		$data['cm_title']	= $dataApi['cm_title'];
		if (!isset($dataApi['create_by']))
		{
		    $data['create_by']	= 'automat';
		}
		
		if (isset($dataApi['cl_partners_book_id'])){
		    $tmpPartners = $this->PartnersManager->findAllTotal()->
				    where('cl_company_id = ?', $dataApi['cl_company_id'])->
				    where('id = ?', $dataApi['cl_partners_book_id'])->fetch();
		}else{
		    $tmpPartners = $this->PartnersManager->findAllTotal()->
				    where('cl_company_id = ?', $dataApi['cl_company_id'])->
				    where('company LIKE ?', $dataApi['cl_partners_name'].'%')->fetch();		    
		}
		
		if ($tmpPartners)
		{
			$data['cl_partners_book_id'] = $tmpPartners['id'];

			if ($tmpCurrencies = $this->CurrenciesManager->findAllTotal()->
						where('cl_company_id = ?', $dataApi['cl_company_id'])->
						where('currency_code LIKE ?', $dataApi['currency_code'])->fetch())
			{
				$data['cl_currencies_id']	 = $tmpCurrencies['id'];		
			}
			

			$nSeries = $this->NumberSeriesManager->getNewNumber('order',NULL,NUll,$dataApi['cl_company_id']);
			$data['cl_number_series_id'] = $nSeries['id'];
			$data['doc_number']	     = $nSeries['number'];

			$tmpStatus = 'order';
			if ($nStatus = $this->StatusManager->findAllTotal()->
						where('cl_company_id = ?', $dataApi['cl_company_id'])->
						where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
			{
				$data['cl_status_id']	= $nStatus->id;		
			}
		
	
			return $this->insertForeign($data);
		}
		
	}	
	    


	
}

