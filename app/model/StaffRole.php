<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * StaffRole management.
 */
class StaffRoleManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_staff_role';


    public function getStaffRoleTreeNotNested()
    {
        $arrRet = [];
        $tmpData = $this->findAll()->where('in_staff_role_id IS NULL')->order('name');
        foreach($tmpData as $key=>$one)
        {
            $arrRet[$key] = \Nette\Utils\Html::el()->
                                                    setText($one->name)->
                                                    setAttribute('class', 'l1');

            foreach($one->related('in_staff_role') as $key2=>$one2)
            {
                $arrRet[$key2] = \Nette\Utils\Html::el()->
                                                        setText($one2->name)->
                                                        setAttribute('class', 'l2');
            }
        }

        return $arrRet;
    }


}

