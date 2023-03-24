<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class PricelistPresenter extends \App\APIModule\Presenters\BaseAPI{
    

    /**
    * @inject
    * @var \App\Model\PricelistManager
    */
    public $DataManager;
	
	/**
	 * @inject
	 * @var \App\Model\PricelistGroupManager
	 */
	public $PricelistGroupManager;
	
	
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
		if ($tmpGroup = $this->PricelistGroupManager->findAllTotal()->where(array('cl_company_id' => NULL, 'id' => $tmpDataArr['cl_pricelist_group_id']))->fetch())
		{
			$tmpDataArr['pricelist_group'] = $tmpGroup['name'];
		}else{
			$tmpDataArr['pricelist_group'] = '';
		}
		//unset($tmpDataArr['cl_countries_id']);
		
		$xml = Array2XML::createXML('cl_pricelist', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->
										where(array('cl_pricelist.cl_company_id' => $this->cl_company_id))->
										where('cl_pricelist.changed >= ? OR cl_pricelist.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_pricelist.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_pricelist.*, cl_pricelist_group.name AS pricelist_group');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_pricelist = $one->toArray();
			$tmpDataArr['cl_pricelist'][] = $cl_pricelist;
			
		}
		//dump($tmpDataArr);
		//die;
		$xml = Array2XML::createXML('cl_pricelist', $tmpDataArr);
		//dump($xml);
		echo $xml->saveXML();

		$this->terminate();		
	}	
	
	public function actionGetAll()
	{
	    parent::actionGetAll();    
	    $data = $this->DataManager->findAllTotal()->select('cl_pricelist.id,identification,ean_code,item_label')->
							where('cl_pricelist.cl_company_id = ? AND cl_pricelist.not_active = 0',$this->cl_company_id)->order('identification')->fetchAll();
	    $arrData = array();
	    foreach ($data as $key => $one)
	    {
			$arrData[] = array('id' => $key, 'identification' => $one['identification'], 'ean_code' => $one['ean_code'], 'item_label' => $one['item_label']);
	    }
	    $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);

	}	
    
}

