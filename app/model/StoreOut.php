<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * StoreOut management.
 */
class StoreOutManager extends Base
{
	const COLUMN_ID = 'id',
	      COLUMN_CL_STORE_MOVE_IN_ID = 'cl_store_move_in_id';
	
	public $tableName = 'cl_store_out';

	
	public function getOutsForIn($id)
	{
	    //bdump($id,'getOutsForIn');
	    return $this->findAll()->where(array(self::COLUMN_CL_STORE_MOVE_IN_ID => $id))->fetch();
	}

	
}

