<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncOrderitemsPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\OrderItemsManager
    */
    public $DataManager;

    /**
    * @inject
    * @var \App\Model\OrderManager
    */
    public $OrderManager;
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;	

    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $PriceListManager;	    
    
    
	public function actionSet()
	{
		parent::actionSet();

       //        file_put_contents( __DIR__."/../../../log/logxmlorderitems.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);

				
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['oobsah'][0]))
		{
			$array['oobsah'] = array($array['oobsah']);
		}

		foreach ($array['oobsah'] as $key1 => $oneSync)
		{
			foreach ($oneSync as $key => $one)
			{
				if ( is_array($oneSync[$key]))
				{
					$oneSync[$key] = '';
				}
			}

			$tmpCis_record = $oneSync['cis_oint'];
			$oneSync['cl_company_id'] = $this->cl_company_id;

			
			//set cl_pricelist_id
			//file_put_contents(__DIR__.'/../../../log/logOrder.txt', $oneSync['category']);
			//if ($tmpPriceList = $this->PriceListManager->findAllTotal()->
			//		    where(array('cl_company_id' => $this->cl_company_id, 'identification' => $oneSync['cl_pricelist_identification']))->fetch())
			//{
			//    $oneSync['cl_pricelist_id'] = $tmpPriceList->id;
			//}else{
			//    $oneSync['cl_pricelist_id'] = NULL;
			//}
			//file_put_contents(__DIR__.'/../../../log/logOrder2.txt', $oneSync['cl_pricelist_group_id']);

			//unset($oneSync['cl_pricelist_identification']);
            if ($oneSync['rea_date'] == '    /  /  ')
                $oneSync['rea_date'] = NULL;

            if ($oneSync['cl_pricelist_id'] == 0)
                $oneSync['cl_pricelist_id'] = NULL;

			unset($oneSync['cis_oint']);
			
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

		
		$xml = Array2XML::createXML('cl_order_items', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_order_items.cl_company_id' => $this->cl_company_id))->
								where('cl_order_items.changed >= ? OR cl_order_items.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_order_items.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_order_items.*');	
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			//$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_order_items'][] = $one->toArray();

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('order', $tmpDataArr);					
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
