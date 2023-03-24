<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Emailing management.
 */
class InEmailingManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_emailing';

}

