<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Paired Docs management.
 */
class PairedDocsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_paired_docs';

	

	public function insertOrUpdate($data)
	{
	    bdump($data);
	    if (!$row=$this->findBy($data)->fetch()){
		$this->insert($data);
	    }
	    
	}

	
}

