<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Storage management.
 */
class StorageManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_storage';
	
	/** @var Nette\Database\Context */
	public $PriceListManager;				

	
	public $settings;
		

	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					PriceListManager $PriceListManager,
					CompaniesManager $CompaniesManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      $this->PriceListManager = $PriceListManager;
	      $this->settings = $CompaniesManager->getTable()->fetch();	

	  }    		
	
	
	public function getStoreTree()
	{
		$arrStore = [];
		$tmpStorage = $this->findAll()->where('cl_storage_id IS NULL')->order('name');
		foreach($tmpStorage as $key=>$one)
		{
			
			$arr2 = array();
			foreach($one->related('cl_storage') as $key2=>$one2)
			{
				$arr2[$key2] = $one2->name.' - '.$one2->description;
			}
			if (count($arr2) > 0)
			{
				$arrStore[$one->name.' - '.$one->description] = $arr2;
			}else{
				$arrStore[$key] = $one->name.' - '.$one->description;
			}
		}
		//bdump($arrStore,'getStoreTree return');
		return $arrStore;
	}

	
	public function getStoreTree2()
	{
		$arrStore = [];
		$tmpStorage = $this->findAll()->where('cl_storage_id IS NULL')->order('name');
		foreach($tmpStorage as $key=>$one)
		{
			$arr2 = array();
			foreach($one->related('cl_storage') as $key2=>$one2)
			{
				$arr2['text'] = $one->name.' - '.$one2->name.' - '.$one2->description;
				$arr2['value'] = $key2;
				$arrStore[] = $arr2;
			}
			
			//if (count($arr2) > 0)
			//{
			//	$arrStore[] = $arr2;
			//}
		}
		return $arrStore;
	}	
	
	public function getStoreTreeNotNested2Levels()
	{
		$arrStore = [];
		$tmpStorage = $this->findAll()->where('cl_storage_id IS NULL')->order('name');
		foreach($tmpStorage as $key=>$one)
		{
			//$arrStore[$key] = $one->name.' - '.$one->description;
			$arrStore[$key] = \Nette\Utils\Html::el()->
						setText($one->name.' - '.$one->description)->
						setAttribute('class', 'l1');
				
			//102 => \Nette\Utils\Html::el()->setText('Czech republic')->data('lon', ...)->data('lat', ...)
			
			foreach($one->related('cl_storage') as $key2=>$one2)
			{
				//$arrStore[$key2] = $one2->name.' - '.$one2->description;
				$arrStore[$key2] = \Nette\Utils\Html::el()->
						setText($one2->name.' - '.$one2->description)->
						setAttribute('class', 'l2');				
			}

		}
		//bdump($arrStore);
		//die;
		return $arrStore;
	}
	
	public function getStoreTreeNotNested()
	{
		$arrStore = [];
		$tmpStorage = $this->findAll()->where('cl_storage_id IS NULL')->order('name');
		$lvl = 1;
		$level = "l";
		$this->storeTreeLevel($tmpStorage, $lvl, $arrStore);
		return $arrStore;
	}
	
	private function storeTreeLevel($one, &$lvl, &$arrRet)
	{
		foreach($one as $key2=>$one2)
		{
			$arrRet[$key2] = \Nette\Utils\Html::el()->
								setText($one2->name.' - '.$one2->description)->
								setAttribute('class', "l".$lvl);
			
			$lvl++;
			$this->storeTreeLevel($one2->related('cl_storage'), $lvl, $arrRet);
			$lvl--;
		}

	}


	public function getStoragesToAutomaticOrder()
    {
        //objednávky budeme generovat buď když vyprší počet dnů nebo když nastane nastavený den
        //případě nastaveného dne testujeme datum poslední objednávky, musí být menší než aktuální den, aby se
        //více spuštěních v jednom dnu negenerovalo více objednávek
        $data = $this->findAll()->
                    select('DATEDIFF(DATE_ADD(order_date, INTERVAL order_period DAY), NOW())+1 AS day_left, 
                                    DATE_ADD(order_date, INTERVAL order_period+1 DAY) AS next_date, cl_storage.*')->
                    where('auto_order = 1')->
                    order('day_left,name');
        //bdump($data, 'automaticorder');
        //                    where('auto_order = 1 AND (DATE_ADD(order_date, INTERVAL order_period DAY) <= NOW() OR (order_day+1 = DAYOFWEEK(NOW()) AND (order_date < NOW() OR order_date IS NULL)))')->
        return($data);
    }
	
	
}

