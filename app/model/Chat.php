<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Chat management.
 */
class ChatManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_chat';

}

