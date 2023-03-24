<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Nette\Utils\DateTime;

/**
 * EstateDiary management.
 */
class EstateDiaryManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_estate_diary';

	public function newRecord($data){
        if (!is_null( $data['id'])) {
            $maxOrder = $this->findAll()->where('in_estate_id = ?', $data['id'])->max('item_order');
            $arrDiary = array();
            $arrDiary['in_estate_id'] = $data['id'];
            $arrDiary['item_order'] = $maxOrder + 1;
            $arrDiary['description_short'] = $data['description'];
            $arrDiary['date'] = new DateTime();
            $arrDiary['event_type'] = $data['event_type'];
            $this->insert($arrDiary);
        }

    }

}

