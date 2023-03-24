<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Task Workers management.
 */
class TaskWorkersManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_task_workers';

    public function validateEmail($eml)
    {
        if (!filter_var($eml, FILTER_VALIDATE_EMAIL)) {
            $eml = "";
        }
        return $eml;
    }

    public function makeFinalEmail($dataId)
    {
        $tmpData = $this->find($dataId);

        if ($tmpData && $tmpData['final_email'] == '') {
            //if (isset($tmpData->cl_users['email']) && $tmpData->cl_users['email'] != '' && $this->checkEmailEnabled($tmpData->cl_users['email']) && ($this->validateEmail($tmpData->cl_users['email']) != ''))
            if (isset($tmpData->cl_users['email']) && $tmpData->cl_users['email'] != '' && ($this->validateEmail($tmpData->cl_users['email']) != ''))
                $email = $tmpData->cl_users['email'];
            elseif (isset($tmpData->cl_users['email2']) && $tmpData->cl_users['email2'] != '' && ($this->validateEmail($tmpData->cl_users['email2']) != ''))
                $email = $tmpData->cl_users['email2'];
            else
                $email = '';

            $tmpData->update(['final_email' => $email]);

        }

    }


}

