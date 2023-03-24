<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Commission Items Sel management.
 */
class CommissionItemsSelManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_commission_items_sel';

	
	public function updatePrice_s($id, $newPrice_s)
	{
	    $tmpData = $this->find($id);

	    if ($tmpData){
		$data = array();
		$data['id']		= $id;
		$data['price_s']	= $newPrice_s;
		$data['price_e']	= $newPrice_s * (1+($tmpData['profit']/100));
		$data['price_e2']	= $data['price_e'] * $tmpData['quantity'] * (1-($tmpData['discount']/100));
		$data['price_e2_vat']	= $data['price_e2'] * (1+($tmpData['vat']/100));
		$tmpData->update($data);
	    }
	}	


	
}

