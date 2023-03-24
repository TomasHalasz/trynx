<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncStoragePresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\StorageManager
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

       //         file_put_contents( __DIR__."/../../../log/logxml.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['csklad'][0]))
		{
		//	dump('neni pole');
			$array['csklad'] = array($array['csklad']);
		}
		//dump($array['partner']);
		foreach ($array['csklad'] as $key1 => $oneRecord)
		{
			foreach ($oneRecord as $key => $one)
			{
				if ( is_array($oneRecord[$key]))
				{
					$oneRecord[$key] = '';
				}
			}

			$tmpCis_record = $oneRecord['sklad_id'];
			$oneRecord['cl_company_id'] = $this->cl_company_id;
            //find cl_storage.id if already exists
            if ($tmpStorage = $this->DataManager->findAllTotal()->where('cl_company_id = ? AND name = ?', $this->cl_company_id, $oneRecord['name'])->fetch()) {
                $oneRecord['id'] = $tmpStorage->id;
            }


			unset($oneRecord['sklad_id']);
			//unset($onePricelist['int_cis']);
			
			/*main method for save new data*/
			$row = $this->syncData($oneRecord);
			
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

		
		$xml = Array2XML::createXML('storage', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_storage.cl_company_id' => $this->cl_company_id))->
								where('cl_storage.changed >= ? OR cl_storage.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_storage.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_storage.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_storage'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('storage', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
