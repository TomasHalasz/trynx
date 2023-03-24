<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * History management.
 */
class HistoryManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_history';

    public function getChanges(string $getTableName)
    {
        $tmpData = $this->findAll()->where('to_send = 1 AND table_name = ?', $getTableName);
        return $tmpData;
    }

    public function changesSend($tmpChange)
    {
        foreach($tmpChange as $key => $one)
        {
            $one->update(['to_send' => 0]);
        }
    }

}

