<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncPartnersbranchPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\PartnersBranchManager
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
		
		if (!isset($array['partner2'][0]))
		{
		//	dump('neni pole');
			$array['partner2'] = array($array['partner2']);
		}
		//dump($array['partner']);
		foreach ($array['partner2'] as $key1 => $onePartner) {
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

            if (empty($onePartner['country']))
			{
				$onePartner['cl_countries_id'] = NULL;
			}else{			
				if ($tmpCountry = $this->CountriesManager->findAllTotal()->where(array('name' => $onePartner['country']))->order('cl_company_id ASC')->fetch())
				{
					$onePartner['cl_countries_id'] = $tmpCountry->id;
                    file_put_contents('logjson.txt', $onePartner['country']);
				}else{
					$onePartner['cl_countries_id'] = NULL;
				}
			}
			
			unset($onePartner['country']);
			unset($onePartner['cis_part']);
			
			/*main method for save new data*/
			$row = $this->syncData($onePartner);

            $arrRet[$tmpCis_part] = array('id' => $row['id'], 'updated' => $row['updated']);

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
