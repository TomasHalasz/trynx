<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Currencies Rates management.
 */
class CurrenciesRatesManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_currencies_rates';

	
    /**
     * Vrací table bez filtru na vlastnickou firmu
     * @return type
     */
    protected function getTable() {
	    return $this->database->table($this->tableName);
    }

	
}

