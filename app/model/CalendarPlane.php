<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Calendar plane management.
 */
class CalendarPlaneManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_calendar_plane';
}

