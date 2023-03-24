<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncInvoicearrivedPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\InvoiceArrivedManager
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
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

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
		
		if (!isset($array['invoice_arrived'][0]))
		{
			$array['invoice_arrived'] = array($array['invoice_arrived']);
		}

		foreach ($array['invoice_arrived'] as $key1 => $oneSync)
		{
			foreach ($oneSync as $key => $one)
			{
				if ( is_array($oneSync[$key]))
				{
					$oneSync[$key] = '';
				}
			}

			$tmpCis_record = $oneSync['cis_pfint'];
			$oneSync['cl_company_id'] = $this->cl_company_id;
            if ($oneSync['cl_partners_book_id'] == "0")
            {
                $oneSync['cl_partners_book_id'] = NULL;
            }
			//set currency
			if ($tmpCurrencies = $this->CurrenciesManager->findAllTotal()->
					    where('cl_company_id = ? AND currency_code = ?', $this->cl_company_id, $oneSync['currency_code'])->fetch())
			{
			    $oneSync['cl_currencies_id'] = $tmpCurrencies->id;
			}else{
			    $oneSync['cl_currencies_id'] = $this->settings->cl_currencies_id;			    
			}

            if ( $oneSync['payment_type'] != "" && $tmpPayment = $this->PaymentTypesManager->findAllTotal()->where('cl_company_id = ? AND payment_type = ?', $this->cl_company_id, $oneSync['payment_type'])->fetch())
            {
                $oneSync['cl_payment_types_id'] = $tmpPayment->id;
            }else{
                $oneSync['cl_payment_types_id'] = NULL;
            }

            if ( $oneSync['stredisko'] != "" && $tmpCenter = $this->CenterManager->findAllTotal()->where('cl_company_id = ? AND name = ?', $this->cl_company_id, $oneSync['stredisko'])->fetch())
            {
                $oneSync['cl_center_id'] = $tmpCenter->id;
            }else{
                $oneSync['cl_center_id'] = NULL;
            }


            if ($tmpStatus = $this->StatusManager->findAllTotal()->where('cl_company_id = ? AND status_use = ? AND status_name = ?', $this->cl_company_id, 'invoice', 'Importováno')->fetch())
            {
                $oneSync['cl_status_id'] = $tmpStatus->id;
            }else{
                $arrStatus = array('cl_company_id' => $this->cl_company_id,
                    'status_name' => 'Importováno',
                    's_new' => 0,
                    's_fin' => 0,
                    's_storno' => 0,
                    'status_use' => 'invoice',
                    'color_hex' => '#0B61E9');

                $newStatus = $this->StatusManager->insertForeign($arrStatus);
                $oneSync['cl_status_id'] = $newStatus->id;
            }





            if ($oneSync['inv_date'] == '    /  /  ')
                $oneSync['inv_date'] = NULL;
            if ($oneSync['arv_date'] == '    /  /  ')
                $oneSync['arv_date'] = NULL;
            if ($oneSync['vat_date'] == '    /  /  ')
                $oneSync['vat_date'] = NULL;
            if ($oneSync['due_date'] == '    /  /  ')
                $oneSync['due_date'] = NULL;
            if ($oneSync['pay_date'] == '    /  /  ')
                $oneSync['pay_date'] = NULL;

            unset($oneSync['stredisko']);
            unset($oneSync['payment_type']);
			unset($oneSync['currency_code']);
			unset($oneSync['cis_pfint']);



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

		
		$xml = Array2XML::createXML('cl_invoice_arrived', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_invoice_arrived.cl_company_id' => $this->cl_company_id))->
								where('cl_invoice_arrived.changed >= ? OR cl_invoice_arrived.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_invoice_arrived.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_invoice_arrived.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			//$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_invoice_arrived'][] = $one->toArray();

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('invoice_arrived', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
