<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncPricelistgroupPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\PriceListGroupManager
    */
    public $DataManager;
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;	

    /**
    * @inject
    * @var \App\Model\NumberSeriesManager
    */
    public $NumberSeriesManager;	    
    
	public function actionSet()
	{
		parent::actionSet();

       // file_put_contents( __DIR__."/../../../log/logxml.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['group'][0]))
		{
		//	dump('neni pole');
			$array['group'] = array($array['group']);
		}
		//dump($array['partner']);
		foreach ($array['group'] as $key1 => $onePricelist)
		{
			foreach ($onePricelist as $key => $one)
			{
				if ( is_array($onePricelist[$key]))
				{
					$onePricelist[$key] = '';
				}
			}

			$tmpCis_record = $onePricelist['categ_id'];
			$onePricelist['cl_company_id'] = $this->cl_company_id;
            //find cl_pricelist_group.id if already exists
            if ($tmpPricelistGroup = $this->DataManager->findAllTotal()->where('cl_company_id = ? AND name = ?', $this->cl_company_id, $onePricelist['name'])->fetch()) {
                $onePricelist['id'] = $tmpPricelistGroup->id;
            }

			//15.09.2017 - set cl_number_series_id
			if ($tmpNumberSeries = $this->NumberSeriesManager->findAllTotal()->
							    where('cl_company_id = ? AND form_use = ? AND form_default = ?', $this->cl_company_id, 'pricelist', 1)->fetch())
			{
			    $onePricelist['cl_number_series_id'] = $tmpNumberSeries->id;
			}
			unset($onePricelist['categ_id']);
			unset($onePricelist['int_cis']);
			
			/*main method for save new data*/
			$row = $this->syncData($onePricelist);
			
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

		
		$xml = Array2XML::createXML('cl_pricelist', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_pricelist_group.cl_company_id' => $this->cl_company_id))->
								where('cl_pricelist_group.changed >= ? OR cl_pricelist_group.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_pricelist_group.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_pricelist_group.*');	
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_pricelist_group'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('pricelistgroup', $tmpDataArr);					
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
