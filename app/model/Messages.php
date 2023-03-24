<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Messages management.
 */
class MessagesManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_messages';

//    $this->tableName = 'cl_partners_book';

	public function setClose($id,$cl_users_id)
	{
	    $data = new \Nette\Utils\ArrayHash;
	    $data['closed'] = 1;
	    $data['changed'] = new \Nette\Utils\DateTime;
	    $data['change_by'] = $this->user->getIdentity()->name;
		//'cl_users_id' => $cl_users_id,
	    $this->findBy(array( 'id' => $id))->update($data);	    
	}
	
	
	public function insertMessage($dataMess,$userId = NULL)
	{
	    if (!$userId == NULL)
	    {
		if (!$this->findBy(array('message' => $dataMess['message'], 
							  'cl_users_id' => $userId,
							  'closed' => 0))->fetch())
		    $this->insert($dataMess);		
	    }else{
		//message for all users in company
		
	    }
	    
	}
	
	public function insertMessagePublic($dataMess,$userId = NULL)
	{
	    if (!$userId == NULL)
	    {
		if (!$this->findBy(array('message' => $dataMess['message'], 
							  'cl_users_id' => $userId,
							  'closed' => 0))->fetch())
		    $this->insertPublic($dataMess);		
	    }else{
		//message for all users in company
		
	    }
	    
	}	
	
}

