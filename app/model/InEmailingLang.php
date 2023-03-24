<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Emailing Languages management.
 */
class InEmailingLangManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_emailing_lang';

}

