<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PriceListLimits management.
 */
class PriceListLimitsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist_limits';

	

	public function getLimit($cl_pricelist_id, $cl_storage_id, $cl_company_id = NULL){
		if (is_null($cl_company_id)) {
			$tmpLimits = $this->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?', $cl_pricelist_id, $cl_storage_id)->fetch();
		}else{
			$tmpLimits = $this->findAllTotal()->
										where('cl_company_id = ?', $cl_company_id)->
										where('cl_pricelist_id = ? AND cl_storage_id = ?', $cl_pricelist_id, $cl_storage_id)->fetch();
		}
		if ($tmpLimits) {
			$retVal = $tmpLimits['id'];
		}else{
			$retVal = NULL;
		}
		return $retVal;
	}
	
}

