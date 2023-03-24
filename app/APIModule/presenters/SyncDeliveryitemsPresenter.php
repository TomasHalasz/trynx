<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncDeliveryitemsPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\DeliveryNoteItemsManager
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
		
		if (!isset($array['delivery_note_items'][0]))
		{
		//	dump('neni pole');
			$array['delivery_note_items'] = array($array['delivery_note_items']);
		}
		//dump($array['partner']);
		foreach ($array['delivery_note_items'] as $key1 => $onePricelist)
		{
			foreach ($onePricelist as $key => $one)
			{
				if ( is_array($onePricelist[$key]))
				{
					$onePricelist[$key] = '';
				}
			}

			$tmpCis_record = $onePricelist['cis_int2'];
			$onePricelist['cl_company_id'] = $this->cl_company_id;

            if ($onePricelist['cl_invoice_id'] == "" || $onePricelist['cl_invoice_id'] == 0 ) {
                $onePricelist['cl_invoice_id'] = NULL;
            }

			unset($onePricelist['cis_int2']);
			
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

		
		$xml = Array2XML::createXML('cl_invoice_items', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_invoice_items.cl_company_id' => $this->cl_company_id))->
								where('cl_invoice_items.changed >= ? OR cl_invoice_items.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_invoice_items.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_invoice_items.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_invoice_items'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('cl_invoice_items', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
