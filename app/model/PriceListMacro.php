<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PriceListMacro management.
 */
class PriceListMacroManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist_macro';
	
	public function getQuantity($pricelist_macro_id, $amount){
	    $data = $this->findBy(array('id' => $pricelist_macro_id))->fetch();
	    if ($data){
            //21.01.2018 - there will be recalculation according to units
            $newQuantity = $data->quantity*$amount + $data->waste*$amount;
            return $newQuantity;
	    }else{
		    return 0;
	    }
	}

	
}

