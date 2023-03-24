<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Transport Docs management.
 */
class TransportDocsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_transport_docs';

	

    public function insertDN($cl_transport_id, $cl_delivery_note_id){

        $maxOrder = $this->findAll()->where('cl_transport_id = ?', $cl_transport_id)->max('item_order');
        $test = $this->findAll()->where('cl_transport_id = ? AND cl_delivery_note_id = ?', $cl_transport_id, $cl_delivery_note_id)->fetch();
        if (!$test) {
            $arrData = ['cl_transport_id' => $cl_transport_id,
                        'cl_delivery_note_id' => $cl_delivery_note_id,
                        'item_order' => $maxOrder + 1,
                        'package_count' => 1,
                        'package_type' => 0];

            $this->insert($arrData);
        }

    }
	
}

