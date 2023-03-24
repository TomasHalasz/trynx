<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncErasedPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;
	
    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $PriceListManager;

    /**
    * @inject
    * @var \App\Model\ErasedSyncManager
    */
    public $ErasedSyncManager;    

    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;    
    
	public function actionSet()
	{
		parent::actionSet();

          //     file_put_contents( __DIR__."/../../../log/logxml.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);

				
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		//file_put_contents( __DIR__.'/../../../log/logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['erased'][0]))
		{
		//	dump('neni pole');
			$array['erased'] = array($array['erased']);
		}
		//file_put_contents( __DIR__.'/../../../log/dump.txt', count($array['erased']) );
		foreach ($array['erased'] as $key1 => $oneToErase)
		{
		//	dump($key1);
		//	die;			
		//file_put_contents( __DIR__.'/../../../log/dump2.txt', $key1 );
		//file_put_contents( __DIR__.'/../../../log/dump3.txt', $oneToErase['src_id'] );
			foreach ($oneToErase as $key => $one)
			{
				if ( is_array($oneToErase[$key]))
				{
					$oneToErase[$key] = '';
				}
			}
		//file_put_contents('logarr'.$key1, $onePartner['country']);
			$tmpSrc_id = $oneToErase['src_id'];
			$oneToErase['cl_company_id'] = $this->cl_company_id;
			
			if (isset($oneToErase['src_id']))
			{
			    /*main method for erase data*/
			    $row = $this->eraseData($oneToErase);
			    //file_put_contents( __DIR__.'/../../../log/dump1.txt', $row );
			}
			
		}
		
		//12.09.2017 - prepare return values with erased on server side
		if (is_null($this->settings->sync_last))
		{
		    $tmpDate = \Nette\Utils\DateTime::from('2017-01-01 00:00:00');
		}else{
		    $tmpDate = $this->settings->sync_last;
		}
		   
		$arrRet = $this->ErasedSyncManager->findAllTotal()->where('created >= ? AND cl_company_id = ?', $tmpDate, $this->cl_company_id);
		//file_put_contents( __DIR__.'/../../../log/dump.txt', count($arrRet) );
		
		//$arrRet[$tmpCis_part] = array('id' => $row['id'], 'updated' => $row['updated']);			
		$strRet = "";
		foreach($arrRet as $key => $one)
		{
			$strRet .= $one['src_table'].";".$one['src_id'].";".PHP_EOL;
		}
		echo($strRet);
		
		//13.09.2017 - erase is last action of each call of synchronization, so we must save datetime of this moment
		$this->CompaniesManager->setSyncEnd($this->cl_company_id);
		
		$this->terminate();		
	}
	
	public function eraseData($arrErase)
	{
	    if ($arrErase['src_table'] == "tbl_partneri")
	    {
		$dataManager = $this->PartnersManager;
	    }
	    elseif ($arrErase['src_table'] == "tbl_cenik")
	    {
		$dataManager = $this->PriceListManager;
	    }
	    //file_put_contents( __DIR__.'/../../../log/dump.txt', $arrErase['src_table'] );
	    //file_put_contents( __DIR__.'/../../../log/dump2.txt', $arrErase['src_id'] );
	
	    return($dataManager->deleteAPI($arrErase['src_id'], $arrErase['cl_company_id']));
	    
	}

	

}
