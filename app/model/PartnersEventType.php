<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Partners Event Type management.
 */
class PartnersEventTypeManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_partners_event_type';

	
    /**
     * Find next event type for given event_type_id
     * @param type $cl_partners_event_type_id
     * @return type
     */
    public function getNextType($cl_partners_event_type_id)
    {
	/*musime najít následující typ události pro předaný typ*/
	if ($tmpTypes = $this->findAll()->order('event_order'))
	{
		$tmpArr = $tmpTypes->fetchPairs('id','id');
		$tmpMain = array_search($cl_partners_event_type_id, $tmpArr);
		$tmpNext = (next($tmpArr));
	}  else {
		$tmpNext = NULL;
	}
	return $tmpNext;
    }


	
}

