<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncDeliveryPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\DeliveryNoteManager
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
     * @var \App\Model\StatusManager
     */
    public $StatusManager;

    /**
     * @inject
     * @var \App\Model\InvoiceTypesManager
     */
    public $InvoiceTypesManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;

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

    public function actionSet()
	{
		parent::actionSet();

	    file_put_contents( __DIR__."/../../../log/delivery.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);

				
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['delivery_note'][0]))
		{
			$array['delivery_note'] = array($array['delivery_note']);
		}

		foreach ($array['delivery_note'] as $key1 => $oneSync)
		{
			foreach ($oneSync as $key => $one)
			{
				if ( is_array($oneSync[$key]))
				{
					$oneSync[$key] = '';
				}
			}

			if (isset($oneSync['cis_dint']))
			    $tmpCis_record = $oneSync['cis_dint'];

			$oneSync['cl_company_id'] = $this->cl_company_id;
			if ($oneSync['cl_partners_book_id'] == "0")
            {
                $oneSync['cl_partners_book_id'] = NULL;
            }

			//set currency
			/*if ($tmpCurrencies = $this->CurrenciesManager->findAllTotal()->
					    where('cl_company_id = ? AND currency_code = ?', $this->cl_company_id, $oneSync['currency_code'])->fetch())
			{
			    $oneSync['cl_currencies_id'] = $tmpCurrencies->id;
			}else{*/
			    $oneSync['cl_currencies_id'] = $this->settings->cl_currencies_id;			    
			//}

			//cl_users_id
            /*if (isset($oneSync['dealer2'])) {
                if ($tmpUsers = $this->UsersManager->findAllTotal()->
                            where('cl_company_id = ? AND name =  ?', $this->cl_company_id, $oneSync['dealer2'])->fetch()) {
                    $oneSync['cl_users_id'] = $tmpUsers->id;
                } else {
                    $oneSync['cl_users_id'] = NULL;
                }
            }*/

            //status dokladu
            if ($tmpStatus = $this->StatusManager->findAllTotal()->where('cl_company_id = ? AND status_use = ? AND status_name = ?', $this->cl_company_id, 'delivery_note', 'Importováno')->fetch())
            {
                $oneSync['cl_status_id'] = $tmpStatus->id;
            }else{
                $arrStatus = ['cl_company_id' => $this->cl_company_id,
                    'status_name' => 'Importováno',
                    's_new' => 0,
                    's_fin' => 0,
                    's_storno' => 0,
                    'status_use' => 'delivery_note',
                    'color_hex' => '#0B61E9'];

                $newStatus = $this->StatusManager->insertForeign($arrStatus);
                $oneSync['cl_status_id'] = $newStatus->id;
            }

            /*if ($oneSync['invoice_type'] == 'correction'){
                $invType = $this->InvoiceTypesManager->findAllTotal()->where('cl_company_id = ? AND inv_type = ?', $this->cl_company_id,2)->fetch();

            }elseif($oneSync['invoice_type'] == 'tax'){
                $invType = $this->InvoiceTypesManager->findAllTotal()->where('cl_company_id = ? AND inv_type = ?', $this->cl_company_id,1)->fetch();

            }elseif($oneSync['invoice_type'] == 'advance'){
                $invType = $this->InvoiceTypesManager->findAllTotal()->where('cl_company_id = ? AND inv_type = ?', $this->cl_company_id,3)->fetch();

            }
            if ($invType){
                $oneSync['cl_invoice_types_id'] = $invType->id;
            }*/

            /*if ( $oneSync['payment_type'] != "" && $tmpPayment = $this->PaymentTypesManager->findAllTotal()->where('cl_company_id = ? AND payment_type = ?', $this->cl_company_id, $oneSync['payment_type'])->fetch())
            {
                $oneSync['cl_payment_types_id'] = $tmpPayment->id;
            }else{
                $oneSync['cl_payment_types_id'] = NULL;
            }*/


            /*if ( $oneSync['stredisko'] != "" && $tmpCenter = $this->CenterManager->findAllTotal()->where('cl_company_id = ? AND name = ?', $this->cl_company_id, $oneSync['stredisko'])->fetch())
            {
                $oneSync['cl_center_id'] = $tmpCenter->id;
            }else{
                $oneSync['cl_center_id'] = NULL;
            }*/


            if ( $oneSync['inv_number'] != "" && $tmpInvoice = $this->InvoiceManager->findAllTotal()->where('cl_company_id = ? AND inv_number = ?', $this->cl_company_id, $oneSync['inv_number'])->fetch())
            {
                $oneSync['cl_invoice_id'] = $tmpInvoice->id;
            }else{
                $oneSync['cl_invoice_id'] = NULL;
            }


            if ($oneSync['issue_date'] == '    /  /  ')
                $oneSync['issue_date'] = NULL;

            if (isset($oneSync['delivery_date']) && $oneSync['delivery_date'] == '    /  /  ')
                $oneSync['delivery_date'] = NULL;
            elseif(!isset($oneSync['delivery_date']))
                $oneSync['delivery_date'] = NULL;

            /*if ($oneSync['due_date'] == '    /  /  ')
                $oneSync['due_date'] = NULL;
            if ($oneSync['pay_date'] == '    /  /  ')
                $oneSync['pay_date'] = NULL;
*/

            $oneSync['header_txt'] = nl2br($oneSync['header_txt']);
            $oneSync['footer_txt'] = nl2br($oneSync['footer_txt']);
            $oneSync['description_txt'] = nl2br($oneSync['description_txt']);

            /*unset($oneSync['stredisko']);
            unset($oneSync['payment_type']);
            unset($oneSync['invoice_type']);
            unset($oneSync['dealer']);
            unset($oneSync['dealer2']);*/
			unset($oneSync['inv_number']);
			unset($oneSync['cis_dint']);
            unset($oneSync['cis_zfint']);


            //dump($oneSync);

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

		
		$xml = Array2XML::createXML('cl_delivery_note', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_delivery_note.cl_company_id' => $this->cl_company_id))->
								where('cl_delivery_note.changed >= ? OR cl_delivery_note.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_delivery_note.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_delivery_note.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			//$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_delivery_note'][] = $one->toArray();

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('delivery_note', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
