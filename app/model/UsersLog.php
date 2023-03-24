<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Users Log management.
 */
class UsersLogManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_users_log';

}

