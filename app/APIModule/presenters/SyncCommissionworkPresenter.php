<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncCommissionworkPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\CommissionWorkManager
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

    /**
     * @inject
     * @var \App\Model\UsersManager
     */
    public $UsersManager;

    /**
     * @inject
     * @var \App\Model\CommissionManager
     */
    public $CommissionManager;

    public function actionSet()
	{
		parent::actionSet();

        file_put_contents( __DIR__."/../../../log/logxml.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['zakprac'][0]))
		{
		//	dump('neni pole');
			$array['zakprac'] = array($array['zakprac']);
		}
		//dump($array['partner']);
		foreach ($array['zakprac'] as $key1 => $oneSync)
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

            //cl_users_id
            if ($tmpUsers = $this->UsersManager->findAllTotal()->
                    where('cl_company_id = ? AND name =  ?', $this->cl_company_id, $oneSync['worker'])->fetch())
            {
                $oneSync['cl_users_id'] = $tmpUsers->id;
            }else{
                $oneSync['cl_users_id'] = NULL;
            }

            //work time  set
            $tmpS = new \Nette\Utils\DateTime($oneSync['work_date_s'] . ' ' . $oneSync['work_time_s']);
            $oneSync['work_date_s'] = $tmpS->format('Y-m-d H:i:s');

            //if ($oneSync['work_time_e'] == ''){
                //$tmpS = new \Nette\Utils\DateTime($oneSync['work_date_s'] . ' ' . $oneSync['work_time_s']);
                $arrTime = str_getcsv($oneSync['work_time'],".");
                $tmpE = $tmpS->modify('+'.$arrTime[0].' hour')->modify('+'.$arrTime[1].' minute');
                $oneSync['work_date_e'] = $tmpE->format('Y-m-d H:i:s');
                $oneSync['work_time_e'] = $tmpE->format('H:i');
            //}

            unset($oneSync['worker']);
			unset($oneSync['cis_int']);
			
			/*main method for save new data*/
			$row = $this->syncData($oneSync);
			
			$arrRet[$tmpCis_record] = array('id' => $row['id'], 'updated' => $row['updated']);			

            $this->CommissionManager->updateSum($oneSync['cl_commission_id'], $this->cl_company_id);
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

		
		$xml = Array2XML::createXML('cl_commission_work', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_commission_work.cl_company_id' => $this->cl_company_id))->
								where('cl_commission_work.changed >= ? OR cl_commission_work.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_commission_work.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_commission_work.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_commission_work'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('cl_commission_work', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
