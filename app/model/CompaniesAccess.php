<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Companies Access management.
 */
class CompaniesAccessManager extends  Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_access_company';

//    $this->tableName = 'cl_partners_book';

    public function deleteByUser($id,$cl_users_id) {
	$this->database->table($this->tableName)->where(array('id' => $id, 'cl_users_id' => $cl_users_id))->delete();
	    //$this->getTable()->where('id',$id)->delete();
    }    
    	
	
}

