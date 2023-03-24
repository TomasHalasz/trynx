<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Network management.
 */
class NetworkManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_network';
}

