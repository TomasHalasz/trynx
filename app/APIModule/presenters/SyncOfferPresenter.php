<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncOfferPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\OfferManager
    */
    public $DataManager;
    
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
     * @var \App\Model\UsersManager
     */
    public $UsersManager;

    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    /**
     * @inject
     * @var \App\Model\StatusManager
     */
    public $StatusManager;

    public function actionSet()
	{
		parent::actionSet();

       //        file_put_contents( __DIR__."/../../../log/logxmlorder.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);

				
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['offer'][0]))
		{
			$array['offer'] = array($array['offer']);
		}

		foreach ($array['offer'] as $key1 => $oneSync)
		{
			foreach ($oneSync as $key => $one)
			{
				if ( is_array($oneSync[$key]))
				{
					$oneSync[$key] = '';
				}
			}

			$tmpCis_record = $oneSync['cis_nint'];
			$oneSync['cl_company_id'] = $this->cl_company_id;
			//set currency
			if ($tmpCurrencies = $this->CurrenciesManager->findAllTotal()->
					    where('cl_company_id = ? AND currency_code = ?', $this->cl_company_id, $oneSync['currency_code'])->fetch())
			{
			    $oneSync['cl_currencies_id'] = $tmpCurrencies->id;
			}else{
			    $oneSync['cl_currencies_id'] = $this->settings->cl_currencies_id;			    
			}

			//cl_users_id
            if ($tmpUsers = $this->UsersManager->findAllTotal()->
                                    where('cl_company_id = ? AND nick_name =  ?', $this->cl_company_id, $oneSync['cre_user'])->fetch())
            {
                $oneSync['cl_users_id'] = $tmpUsers->id;
            }else{
                $oneSync['cl_users_id'] = NULL;
            }

            if ($tmpStatus = $this->StatusManager->findAllTotal()->where('cl_company_id = ? AND status_use = ? AND status_name = ?', $this->cl_company_id, 'offer', 'Importováno')->fetch())
            {
                $oneSync['cl_status_id'] = $tmpStatus->id;
            }else{
                $arrStatus = array('cl_company_id' => $this->cl_company_id,
                    'status_name' => 'Importováno',
                    's_new' => 0,
                    's_fin' => 0,
                    's_storno' => 0,
                    'status_use' => 'offer',
                    'color_hex' => '#0B61E9');

                $newStatus = $this->StatusManager->insertForeign($arrStatus);
                $oneSync['cl_status_id'] = $newStatus->id;
            }

            //nezapsaný partner
            if (!isset($oneSync['cl_partners_book_id'])){
                if ($tmpPartners = $this->PartnersManager->findAllTotal()->
                                                        where('cl_company_id = ? AND company = ?', $this->cl_company_id, $oneSync['company'])->fetch()) {
                    $oneSync['cl_partners_book_id'] = $tmpPartners->id;
                }else {

                    if (empty($oneSync['country'])) {
                        $oneSync['cl_countries_id'] = NULL;
                    } else {
                        if ($tmpCountry = $this->CountriesManager->findAllTotal()->where(array('cl_company_id' => NULL, 'name' => $oneSync['country']))->fetch()) {
                            $oneSync['cl_countries_id'] = $tmpCountry->id;
                        } else {
                            $oneSync['cl_countries_id'] = NULL;
                        }
                    }

                    if (empty($oneSync['country2'])) {
                        $oneSync['country2'] = NULL;
                    }

                    if ($tmpCountry = $this->CountriesManager->findAllTotal()->where(array('cl_company_id' => NULL, 'name' => $oneSync['country2']))->fetch()) {
                    } else {
                        $oneSync['country2'] = NULL;
                    }


                    $tmpPartner = array('cl_company_id' => $this->cl_company_id,
                        'ico' => $oneSync['ico'],
                        'dic' => $oneSync['dic'],
                        'company' => $oneSync['company'],
                        'street' => $oneSync['street'],
                        'city' => $oneSync['city'],
                        'zip' => $oneSync['zip'],
                        'cl_countries_id' => $oneSync['cl_countries_id'],
                        'company2' => $oneSync['company2'],
                        'street2' => $oneSync['street2'],
                        'city2' => $oneSync['city2'],
                        'zip2' => $oneSync['zip2'],
                        'country2' => $oneSync['country2'],
                        'email' => $oneSync['email'],
                        'web' => $oneSync['web'],
                        'phone' => $oneSync['phone'],
                        'person' => $oneSync['person'],
                        'customer' => $oneSync['customer']
                    );
                    $oneSync['cl_partners_book_id'] = $this->PartnersManager->insertForeign($tmpPartner);
                }
            }

            $oneSync['header_txt'] = nl2br($oneSync['header_txt']);
            $oneSync['footer_txt'] = nl2br($oneSync['footer_txt']);
            $oneSync['description_txt'] = nl2br($oneSync['description_txt']);

            unset($oneSync['ico']);
            unset($oneSync['dic']);
            unset($oneSync['company']);
            unset($oneSync['street']);
            unset($oneSync['city']);
            unset($oneSync['zip']);
            unset($oneSync['country']);
            unset($oneSync['cl_countries_id']);
            unset($oneSync['company2']);
            unset($oneSync['street2']);
            unset($oneSync['city2']);
            unset($oneSync['zip2']);
            unset($oneSync['country2']);
            unset($oneSync['email']);
            unset($oneSync['web']);
            unset($oneSync['phone']);
            unset($oneSync['person']);
            unset($oneSync['customer']);
            unset($oneSync['cl_partners_book_company']);
			unset($oneSync['currency_code']);
			unset($oneSync['cis_nint']);

			if ($oneSync['cre_date'] == "    /  /  ")
			   $oneSync['cre_date'] = NULL;

            if ($oneSync['lm_date'] == "    /  /  ")
                $oneSync['lm_date'] = NULL;

			/*main method for save new data*/
			$row = $this->syncData($oneSync);
			
			$arrRet[$tmpCis_record] = array('id' => $row['id'], 'updated' => $row['updated']);			
			
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

		
		$xml = Array2XML::createXML('cl_offer', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_offer.cl_company_id' => $this->cl_company_id))->
								where('cl_offer.changed >= ? OR cl_offer.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_offer.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_offer.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			//$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_order'][] = $one->toArray();

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('offer', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
