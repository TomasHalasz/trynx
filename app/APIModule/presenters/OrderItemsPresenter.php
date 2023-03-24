<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class OrderItemsPresenter  extends \App\APIModule\Presenters\BaseAPI{
    

    /**
    * @inject
    * @var \App\Model\OrderItemsManager
    */
    public $DataManager;        
	
	
	public function actionSet()
	{
		parent::actionSet();
		
		$this->terminate();
	}

	public function actionGet()
	{
		parent::actionGet();
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id, 'id' => $this->id))->fetch();
		$tmpDataArr = $tmpData->toArray();
		//append data from foreign tables
		/*if ($tmpCountry = $this->CountriesManager->findAllTotal()->where(array('cl_company_id' => NULL, 'id' => $tmpDataArr['cl_countries_id']))->fetch())
		{
			$tmpDataArr['country'] = $tmpCountry['name'];
		}else{
			$tmpDataArr['country'] = '';
		}
		unset($tmpDataArr['cl_countries_id']);
		*/
		
		$xml = Array2XML::createXML('cl_order_items', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->
										where(array('cl_partners_book.cl_company_id' => $this->cl_company_id))->
										where('cl_partners_book.changed >= ? OR cl_partners_book.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_partners_book.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_partners_book.*, cl_countries.name AS country');	
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_partners_book'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('cl_partners', $tmpDataArr);					
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
	
	public function actionGetAll()
	{
	    parent::actionGetAll();    
	    $data = $this->DataManager->findAllTotal()->select('cl_order_items.id,item_label,quantity')->
							where('cl_order_items.cl_company_id = ? AND cl_order_id = ?',$this->cl_company_id, $this->uri['cl_order_id'])->
							order('item_order')->fetchAll();
	    $arrData = array();
	    foreach ($data as $key => $one)
	    {
		$arrData[] = array('id' => $key, 'ItemLabel' => $one['item_label'], 'Quantity' => $one['quantity']);
	    }
	    $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);

	}	
    
}

