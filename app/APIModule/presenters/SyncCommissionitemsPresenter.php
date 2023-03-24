<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncCommissionitemsPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\CommissionItemsSelManager
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

        //file_put_contents( __DIR__."/../../../log/logxml.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['zakobs'][0]))
		{
		//	dump('neni pole');
			$array['zakobs'] = array($array['zakobs']);
		}
		//dump($array['partner']);
		foreach ($array['zakobs'] as $key1 => $onePricelist)
		{
			foreach ($onePricelist as $key => $one)
			{
				if ( is_array($onePricelist[$key]))
				{
					$onePricelist[$key] = '';
				}
			}

			$tmpCis_record = $onePricelist['cis_int'];
			$onePricelist['cl_company_id'] = $this->cl_company_id;
			
			unset($onePricelist['cis_int']);
			
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

		
		$xml = Array2XML::createXML('cl_commission_items_sel', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_commission_items_sel.cl_company_id' => $this->cl_company_id))->
								where('cl_commission_items_sel.changed >= ? OR cl_offer_items.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_commission_items_sel.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_commission_items_sel.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_commission_items_sel'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('cl_commission_items_sel', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
