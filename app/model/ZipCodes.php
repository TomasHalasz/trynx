<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * ZIP codes management.
 */
class ZipCodesManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_zip_codes';

	
    /**
     * Vrací table bez filtru na vlastnickou firmu
     * @return type
     */
    protected function getTable() {
	    return $this->database->table($this->tableName);
    }
	


	
}
