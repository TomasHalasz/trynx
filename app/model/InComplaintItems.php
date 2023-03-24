<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * InComplaintItems management.
 */
class InComplaintItemsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_complaint_items';

}

