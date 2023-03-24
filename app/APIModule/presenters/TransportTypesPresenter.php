<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class TransportTypesPresenter  extends \App\APIModule\Presenters\BaseAPI{

    /**
     * @inject
     * @var \App\Model\TransportTypesManager
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

		$xml = Array2XML::createXML('cl_partners_book', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();

		$tmpData = $this->DataManager->findAllTotal()->
										where(array('cl_company_id' => $this->cl_company_id))->
										where('cl_transport_types.changed >= ? OR cl_transport_types.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_partners_book.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_transport_types.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$arrData = $one->toArray();
			$tmpDataArr['cl_transport_types'][] = $arrData;

		}
        $xml = Array2XML::createXML('cl_transport_types', $tmpDataArr);
        echo $xml->saveXML();

		$this->terminate();		
	}	
	
	public function actionGetAll()
	{
	    parent::actionGetAll();    
	    $data = $this->DataManager->findAllTotal()->select('cl_transport_types.id,cl_transport_types.name')->
							where('cl_transport_types.cl_company_id = ? AND deactive != 1',$this->cl_company_id)->order('name')->fetchAll();
	    $arrData = array();
	    foreach ($data as $key => $one)
	    {
		    $arrData[] = array('id' => $key, 'Name' => $one['name']);
	    }
	    $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
	}
    
}

