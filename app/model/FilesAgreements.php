<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Files Agreements management.
 */
class FilesAgreementsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_files_agreements';

	
    public function setToUsers($id, $users){
        $this->database->beginTransaction();
        foreach($users as $key => $one){
            $arrData = array('cl_files_id' => $id, 'cl_users_id' => $key);
            if (!$this->findAll()->where($arrData)->fetch()){
                $maxItemOrder = $this->findAll()->where($arrData)->max('item_order');
                $arrData['item_order'] = $maxItemOrder + 1;
                $this->insert($arrData);
            }
        }
        $this->database->commit();
    }

    public function makeAgree($id, $userId){
        $tmpNow = new Nette\Utils\DateTime();
        $tmpData = $this->findAll()->where(array('cl_files_id' => $id, 'cl_users_id' => $userId))->fetch();
        if ($tmpData){
            $agrId = $tmpData['id'];
            if ($this->update(array('id' => $agrId, 'dtm_agreement' => $tmpNow )))
            {
                $retVal = true;
            }else{
                $retVal = false;
            }
        }else{
            $retVal = false;
        }

        return $retVal;
    }

	
}

