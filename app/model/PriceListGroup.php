<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * PriceListGroup management.
 */
class PriceListGroupManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist_group';



    public function getGroupTree()
    {
        $arrGroups = [];
        $tmpGroups = $this->findAll()->where('cl_pricelist_group_id IS NULL')->order('name');
        $lvl = 1;
        $level = "l";
        $this->groupTreeLevel($tmpGroups, $lvl, $arrGroups);
        return $arrGroups;
    }

    private function groupTreeLevel($one, &$lvl, &$arrRet)
    {
        foreach($one as $key2=>$one2)
        {
            $arrRet[$key2] = \Nette\Utils\Html::el()->
                                    setText($one2->name)->
                                    setAttribute('class', "l".$lvl);

            $lvl++;
            $this->groupTreeLevel($one2->related('cl_pricelist_group'), $lvl, $arrRet);
            $lvl--;
        }

    }
	
}

