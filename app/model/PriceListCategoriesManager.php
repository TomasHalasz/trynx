<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PriceListCategories management.
 */
class PriceListCategoriesManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist_categories';

}

