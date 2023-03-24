<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncOrderPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\OrderManager
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
     * @var \App\Model\StatusManager
     */
    public $StatusManager;
    
    
	public function actionSet()
	{
		parent::actionSet();

       // file_put_contents( __DIR__."/../../../log/logxmlorder.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);

				
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['objednavka'][0]))
		{
			$array['objednavka'] = array($array['objednavka']);
		}

		foreach ($array['objednavka'] as $key1 => $oneSync)
		{
			foreach ($oneSync as $key => $one)
			{
				if ( is_array($oneSync[$key]))
				{
					$oneSync[$key] = '';
				}
			}

			$tmpCis_record = $oneSync['cis_int'];
			$oneSync['cl_company_id'] = $this->cl_company_id;
			//set currency
			//file_put_contents( __DIR__."/../../../log/currency_code.txt", $oneSync['currency_code']);			
			if ($tmpCurrencies = $this->CurrenciesManager->findAllTotal()->
					    where('cl_company_id = ? AND currency_code = ?', $this->cl_company_id, $oneSync['currency_code'])->fetch())
			{
			    $oneSync['cl_currencies_id'] = $tmpCurrencies->id;
			}else{
			    $oneSync['cl_currencies_id'] = $this->settings->cl_currencies_id;			    
			}
			
			//set cl_partners_book
            if ($oneSync['cl_partners_book_id'] == "0") {
                if ($tmpPartners = $this->PartnersManager->findAllTotal()->
                                        where('cl_company_id = ? AND company = ?', $this->cl_company_id, $oneSync['cl_partners_book_company'])->fetch()) {
                    $oneSync['cl_partners_book_id'] = $tmpPartners->id;
                } else {
                    $oneSync['cl_partners_book_id'] = NULL;
                }
            }

            //status dokladu
            if ($tmpStatus = $this->StatusManager->findAllTotal()->where('cl_company_id = ? AND status_use = ? AND status_name = ?', $this->cl_company_id, 'order', 'Importováno')->fetch())
            {
                $oneSync['cl_status_id'] = $tmpStatus->id;
            }else{
                $arrStatus = array('cl_company_id' => $this->cl_company_id,
                    'status_name' => 'Importováno',
                    's_new' => 0,
                    's_fin' => 0,
                    's_storno' => 0,
                    'status_use' => 'order',
                    'color_hex' => '#0B61E9');

                $newStatus = $this->StatusManager->insertForeign($arrStatus);
                $oneSync['cl_status_id'] = $newStatus->id;
            }


            if ($oneSync['od_date'] == '    /  /  ')
                $oneSync['od_date'] = NULL;
            if ($oneSync['req_date'] == '    /  /  ')
                $oneSync['req_date'] = NULL;
            if ($oneSync['rea_date'] == '    /  /  ')
                $oneSync['rea_date'] = NULL;

            $oneSync['header_txt'] = nl2br($oneSync['header_txt']);
            $oneSync['memo_txt'] = nl2br($oneSync['memo_txt']);

			unset($oneSync['cl_partners_book_company']);
			unset($oneSync['currency_code']);
			unset($oneSync['cis_int']);
			
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

		
		$xml = Array2XML::createXML('cl_order', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_order.cl_company_id' => $this->cl_company_id))->
								where('cl_order.changed >= ? OR cl_order.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_order.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_order.*');	
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			//$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_order'][] = $one->toArray();

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('order', $tmpDataArr);					
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
