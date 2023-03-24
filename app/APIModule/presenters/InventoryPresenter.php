<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\Arrays;
use Tracy\Debugger;

class InventoryPresenter extends \App\APIModule\Presenters\BaseAPI{
	
	
	
	/**
	 * @inject
	 * @var \App\Model\InventoryManager
	 */
	public $DataManager;
	
	/**
	 * @inject
	 * @var \App\Model\InventoryItemsManager
	 */
	public $InventoryItemsManager;
	
    /**
    * @inject
    * @var \App\Model\PricelistManager
    */
    public $PricelistManager;
	
	/**
	 * @inject
	 * @var \App\Model\PricelistGroupManager
	 */
	public $PricelistGroupManager;
	
	/**
	 * @inject
	 * @var \App\Model\WorkplacesManager
	 */
	public $WorkplacesManager;
	
	
	public function actionSet()
	{
		parent::actionSet();
		try{
			//dump($this->uri);
			//$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);
			
			//$json = stripslashes($this->dataxml);
			$json = $this->dataxml;
			//dump($json);
			//$json2 = urldecode($json);
			//file_put_contents('logjson.txt', $json);
			$array = json_decode($json,TRUE);
			
			$str="";
			foreach($array as $key => $one)
			{
				$str.= $key.'=>' . PHP_EOL;
				if (is_array($one)) {
			//		Debugger::log('key = '. $key . ' is array =>' . PHP_EOL);
					foreach ($one as $key2 => $one2) {
						$str .= $key2 . '=>' . $one2 . PHP_EOL;
			//			Debugger::log('key = '. $key2 . ' is value' . $one2 . PHP_EOL);
					}
				}else{
			//		Debugger::log('key = '. $key . ' is value' . $one . PHP_EOL);
				}
			}
			Debugger::log(' ' . $str);

			$arrRet = array();
			//dump($array);
			$tmpTrynx_id = $this->uri['trynx_id'];
			
			//dump($tmpTrynx_id);
			$tmpWorkplace = $this->WorkplacesManager->findAllTotal()->
										where(array('cl_company_id' => $this->cl_company_id, 'trynx_id' => $tmpTrynx_id, 'disabled' => 0))->
										fetch();
			if ($tmpWorkplace)
			{
                Debugger::log(' cl_workplace.id: ' . $tmpWorkplace->id);
				//find active inventory for this workplace / device
				$tmpInventory = $this->DataManager->findAllTotal()->
											where(array('cl_inventory.cl_company_id' => $this->cl_company_id, 'active' => 1, ':cl_inventory_workplaces.cl_workplaces_id' => $tmpWorkplace->id))->
											fetch();
				if ($tmpInventory){
					foreach($array as $key => $one)
					{
						//dump($one['Id']);
						//dump($one['Quantity']);
/*						$oneSource = $this->InventoryItemsManager->findAllTotal()->select('cl_inventory_items.*')->
												where(array('cl_inventory_items.cl_company_id' => $this->cl_company_id, 'cl_store.cl_pricelist.id' => $one['Id'], 'cl_inventory_id' => $tmpInventory->id))->
												group('cl_inventory_items.id');*/
                        $oneSource = $this->InventoryItemsManager->findAllTotal()->select('cl_inventory_items.*')->
                                                    where(array('cl_inventory_items.cl_company_id' => $this->cl_company_id, 'cl_pricelist_id' => $one['Id'], 'cl_inventory_id' => $tmpInventory->id))->
                                                    limit(1)->
                                                    group('cl_inventory_items.id');
						foreach($oneSource as $oneItem){
								//dump($oneItem->quantity_real);
								//$diff = $arrData['quantity_real'] - $oldData['quantity_real'];
								//if ($diff != 0) {
								$arrData = Array();
								$tmpInput = json_decode($oneItem['quantity_input'], true);
								if ($one['Quantity'] != 0) {
									$now = new \Nette\Utils\DateTime();
									$tmpInput[] = array('date' => $now,
										'user' => $tmpWorkplace->workplace_name,
										'quantity' => $one['Quantity']);
								}
								$arrData['quantity_input'] = json_encode($tmpInput);
								//dump($tmpInput);
								
								$count = 0;
								if (!is_null($tmpInput)) {
									foreach ($tmpInput as $oneQuant) {
										$count = $count + $oneQuant['quantity'];
									}
								}
								$arrData['quantity_real'] = $count;
								$arrData['id'] = $oneItem['id'];
								//$oneItem->update($arrData);
								//dump($arrData);
								
								$this->InventoryItemsManager->findAllTotal()->where('id = ? ', $oneItem['id'])->limit(1)->update($arrData);
							//die;
						}
					}
				}
			}
			echo('OK');
			$this->terminate();
		}catch (\Exception $e) {
			//$this->database->rollback();
			echo($e->getMessage());
			$this->terminate();
		}
		
}
	
	
	
}

