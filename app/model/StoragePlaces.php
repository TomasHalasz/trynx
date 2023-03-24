<?php

namespace App\Model;

use Nette,
    Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Storage Places management.
 */
class StoragePlacesManager extends Base
{
    const COLUMN_ID = 'id';
    public $tableName = 'cl_storage_places';
	
	/**Return Rack, Shelf, Place for given cl_storage_places_id
	 * @param $cl_storage_places_id
	 * @return mixed|\Nette\Database\Table\ActiveRow|string
	 */
	public function getStoragePlaceName($arrData){
		if (!empty($arrData['cl_storage_places'])) {
			$tmpArr = json_decode($arrData['cl_storage_places'], true);
			//bdump($arrData);
            $tmpArr2 = array();
			foreach ($tmpArr as $key => $one)
			{
				$tmpArr2[] = $key;
			}
			//bdump($tmpArr2);
			$tmpPlaces = $this->findAllTotal()->
										where('id IN ?', $tmpArr2)->
										select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');
		}else{
			$tmpPlaces = array();
		}
		if (count($tmpPlaces) > 0){
			$strPlace = implode(', ', $tmpPlaces);
			//$strPlace = $tmpPlace->rsp;
		}else{
			$strPlace = "";
		}
		//bdump($strPlace);
		return ($strPlace);
		
	}
 
}