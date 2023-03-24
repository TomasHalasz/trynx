<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncPartnersPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $DataManager;        
	
    /**
    * @inject
    * @var \App\Model\CountriesManager
    */
    public $CountriesManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\UsersManager
     */
    public $UsersManager;

    public function actionSet()
	{
		parent::actionSet();

       //        file_put_contents( __DIR__."/../../../log/logxml.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);

				
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['partner'][0]))
		{
		//	dump('neni pole');
			$array['partner'] = array($array['partner']);
		}
		//dump($array['partner']);
		foreach ($array['partner'] as $key1 => $onePartner) {
            //	dump($key1);
            //	die;
            foreach ($onePartner as $key => $one) {
                if (is_array($onePartner[$key])) {
                    $onePartner[$key] = '';
                }
            }
            //file_put_contents('logarr'.$key1, $onePartner['country']);
            $tmpCis_part = $onePartner['cis_part'];
            $onePartner['cl_company_id'] = $this->cl_company_id;

            //find cl_partners_book.id if already exists
            if (!is_null($onePartner['partner_code']))
            {
                $tmpPartner = $this->DataManager->findAllTotal()->where('cl_company_id = ? AND company = ? AND partner_code = ?', $this->cl_company_id, $onePartner['company'], $onePartner['partner_code'] )->fetch();
            }else{
                $tmpPartner = $this->DataManager->findAllTotal()->where('cl_company_id = ? AND company = ?', $this->cl_company_id, $onePartner['company'])->fetch();
            }
            if ($tmpPartner) {
                $onePartner['id'] = $tmpPartner->id;
            }


            if (empty($onePartner['country']))
			{
				$onePartner['cl_countries_id'] = NULL;
			}else{			
				if ($tmpCountry = $this->CountriesManager->findAllTotal()->where(array('cl_company_id' => NULL, 'name' => $onePartner['country']))->fetch())
				{
					$onePartner['cl_countries_id'] = $tmpCountry->id;
				}else{
					$onePartner['cl_countries_id'] = NULL;
				}
			}
			
			if (empty($onePartner['country2']))
			{
				$onePartner['country2'] = NULL;
			}
				
			if ($tmpCountry = $this->CountriesManager->findAllTotal()->where(array('cl_company_id' => NULL, 'name' => $onePartner['country2']))->fetch())
			{
			}else{
				$onePartner['country2'] = NULL;
			}

            if ( $onePartner['payment_type'] != "" && $tmpPayment = $this->PaymentTypesManager->findAllTotal()->where('cl_company_id = ? AND payment_type = ?', $this->cl_company_id, $onePartner['payment_type'])->fetch())
            {
                $onePartner['cl_payment_types_id'] = $tmpPayment->id;
            }else{
                $onePartner['cl_payment_types_id'] = NULL;
            }

            if ( $onePartner['dealer'] != "" && $tmpUsers = $this->UsersManager->findAllTotal()->where('cl_company_id = ? AND name = ?', $this->cl_company_id, $onePartner['dealer'])->fetch())
            {
                $onePartner['cl_users_id'] = $tmpUsers->id;
            }else{
                $onePartner['cl_users_id'] = NULL;
            }

            unset($onePartner['dealer']);
            unset($onePartner['payment_type']);
			unset($onePartner['country']);
			unset($onePartner['cis_part']);
			
			/*main method for save new data*/
			$row = $this->syncData($onePartner);
			
			//if (isset($row['id']))
			//{
			
				$arrRet[$tmpCis_part] = array('id' => $row['id'], 'updated' => $row['updated']);			
				
			//}else{
			//	$arrRet[$tmpCis_part] = ($row ? 'true':'false') ;
			//}
			
		}
		$strRet = "";
		foreach($arrRet as $key => $one)
		{
			$strRet .= $key.";".$one['id'].";".$one['updated'].PHP_EOL;
		}
		
		
		echo($strRet);
		$this->terminate();		
	}

	public function actionGet()
	{
		parent::actionGet();
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id, 'id' => $this->id))->fetch();
		$tmpDataArr = $tmpData->toArray();
		//append data from foreign tables
		if ($tmpCountry = $this->CountriesManager->findAllTotal()->where(array('cl_company_id' => NULL, 'id' => $tmpDataArr['cl_countries_id']))->fetch())
		{
			$tmpDataArr['country'] = $tmpCountry['name'];
		}else{
			$tmpDataArr['country'] = '';
		}
		//unset($tmpDataArr['cl_countries_id']);
		
		$xml = Array2XML::createXML('cl_partners_book', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->
										where(array('cl_partners_book.cl_company_id' => $this->cl_company_id))->
										where('cl_partners_book.changed >= ? OR cl_partners_book.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_partners_book.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_partners_book.*, cl_countries.name AS country');	
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_partners_book'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('cl_partners', $tmpDataArr);					
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
