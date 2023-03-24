<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Notifications management.
 */
class NotificationsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_notifications';


    /**
     * @return Nette\Database\Table\Selection
     * Find all active notifications and return them
     */
	public function findValid() : Nette\Database\Table\Selection
    {
        $retData = $this->findAll()->where('valid_from <= NOW() AND valid_to >= NOW()')->order('created DESC');
        return $retData;
    }


	
}

