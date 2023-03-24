<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncInvoicearrivedpayPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\InvoiceArrivedPaymentsManager
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
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    
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
		
		if (!isset($array['invoice_payments'][0]))
		{
		//	dump('neni pole');
			$array['invoice_payments'] = array($array['invoice_payments']);
		}
		//dump($array['partner']);
		foreach ($array['invoice_payments'] as $key1 => $oneSync)
		{
			foreach ($oneSync as $key => $one)
			{
				if ( is_array($oneSync[$key]))
				{
                    $oneSync[$key] = '';
				}
			}

			$tmpCis_record = $oneSync['int_cis'];
            $oneSync['cl_company_id'] = $this->cl_company_id;

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


            unset($oneSync['payment_type']);
            unset($oneSync['currency_code']);
            unset($oneSync['int_cis']);
			
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

		
		$xml = Array2XML::createXML('cl_invoice_arrived_payments', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_invoice_arrived_payments.cl_company_id' => $this->cl_company_id))->
								where('cl_invoice_arrived_payments.changed >= ? OR cl_invoice_arrived_payments.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_invoice_arrived_payments.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_invoice_arrived_payments.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_invoice_arrived_payments'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('cl_invoice_arrived_payments', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
