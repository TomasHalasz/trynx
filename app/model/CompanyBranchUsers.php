<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * CompanyBranchUsers management.
 */
class CompanyBranchUsersManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_company_branch_users';


    public function getBranchForUser($user_id)
    {
        $data = $this->getTable()->where('cl_users_id = ?', $user_id)->fetch();
        if ($data){
            $retVal = $data->cl_company_branch_id;
        }else{
            $retVal = NULL;
        }
        return $retVal;
    }

	
}

