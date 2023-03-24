<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Notifications Languages management.
 */
class NotificationsLangManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_notifications_lang';

}

