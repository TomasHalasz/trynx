<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * StoreMove management.
 */
class StoreMoveManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_store_move';






    public function updatePriceS(array $storages, $dateFrom)
    {
        $tmpData = $this->findAll()->where('s_out > 0 AND price_s = 0 AND cl_storage_id IN ? AND (created >= ? OR changed >= ?)', $storages, $dateFrom, $dateFrom);
        $updateData = array();
        foreach ($tmpData as $key => $one)
        {
        	if (!is_null($one->cl_store_id) && !is_null($one->cl_pricelist_id)) {
				if ($tmpPrice = $this->findAll()->where('(cl_store_id = ? OR cl_pricelist_id = ? ) AND price_s != 0', $one->cl_store_id, $one->cl_pricelist_id)->order('cl_store_id DESC')->fetch()) {
					$priceS = $tmpPrice->price_s;
					//}elseif($tmpPrice = $this->findAll()->where('cl_pricelist_id = ? AND price_s != 0', $one->cl_pricelist_id)->fetch()){
					//$priceS = $tmpPrice->price_s;
				} else {
					$priceS = 0;
				}
				if ($priceS > 0) {
					$one->update(array('price_s' => $priceS));
				}
			}

            //   $updateData[]= array('id' => $key, 'price_s' => $priceS );
        }
        //if (count($updateData) > 0)
        //  $this->update($updateData);
    }


    public function getVatSum($id)
    {
        $ret = $this->findAll()->where('cl_store_docs_id = ?', $id)
                                ->select('SUM(s_in * price_in) AS price_in, SUM(s_out * price_s) AS price_s, vat')
                                ->group('vat');
        return $ret;
    }

	
}

