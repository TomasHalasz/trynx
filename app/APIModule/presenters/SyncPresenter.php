<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncPresenter  extends \App\APIModule\Presenters\BaseAPI {
    
   /**
    * main method for syncronize
    * get data to sync
    * return active row if was inserted
    * return true if was updated or nothing happend
    * return false if was not updated and client should download actual data
    * @param type $onePartner
    * @return boolean 
    */
   public function syncData($onePartner)
   {
       /*if ($onePartner['cre_date'] == "    /  /  ")
           $onePartner['cre_date'] = NULL;

       if ($onePartner['lm_date'] == "    /  /  ")
           $onePartner['lm_date'] = NULL;
        */

       if ($onePartner['cre_date'] == "    /  /  ") {
           $tmpCreated = new \Nette\Utils\DateTime;
       }else{
           $tmpCreated = new \Nette\Utils\DateTime($onePartner['cre_date'] . ' ' . $onePartner['cre_time']);
       }

       if ($onePartner['lm_date'] == "    /  /  ") {
           $tmpChanged = new \Nette\Utils\DateTime;
       }else{
           $tmpChanged = new \Nette\Utils\DateTime($onePartner['lm_date'] . ' ' . $onePartner['lm_time']);
       }

	   //prepare created a changed
	   //$onePartner['created'] = new \Nette\Utils\DateTime($onePartner['cre_date'] . ' ' . $onePartner['cre_time']);
       $onePartner['created'] = $tmpCreated;
	   $onePartner['create_by'] = ( !empty($onePartner['cre_user']) ? $onePartner['cre_user'] : 'import F4');
	   //$onePartner['changed'] = new \Nette\Utils\DateTime($onePartner['lm_date'] . ' ' . $onePartner['lm_time']);
       $onePartner['changed'] = $tmpChanged;
	   $onePartner['change_by'] = ( !empty($onePartner['lm_user']) ? $onePartner['lm_user'] : 'import F4');
	   
	   //file_put_contents('created.txt', $onePartner['created'] );
	   unset($onePartner['cre_date']);
	   unset($onePartner['cre_time']);
	   unset($onePartner['cre_user']);
	   unset($onePartner['lm_date']);
	   unset($onePartner['lm_time']);
	   unset($onePartner['lm_user']);

	    //01.10.2017 - solve empty DATE to NULL
	   foreach($onePartner as $key => $one)
	   {
	       if ($one === "0-00-00")
	       {
		        $onePartner[$key] = NULL;
	       }

	   }
	   
		if ($onePartner['id'] == 0 || 
			    !($tmpData = $this->DataManager->findAllTotal()->where('cl_company_id = ? AND id = ?', $this->cl_company_id,$onePartner['id'])->fetch()))
		{
			unset($onePartner['id']);
			//$onePartner['create_by'] = "import F4";
			//$onePartner['created'] = new \Nette\Utils\DateTime;	
			
			//01.10.2017 - if there is empty status_use then set default cl_status_id for new record
			if (isset($onePartner['status_use']))
			{
			    if ($nStatus= $this->StatusManager->findAllTotal()->where('cl_company_id = ? AND status_use = ? AND s_new = ?', $this->cl_company_id,$onePartner['status_use'],1)->fetch())
			    {
				    $onePartner['cl_status_id'] = $nStatus->id;
			    }
			    unset($onePartner['status_use']);
			}
			$result = $this->DataManager->insertForeign($onePartner);
			$row = $result->toArray();
			$row['updated'] = 'true';
		}else{
		    if (isset($onePartner['status_use']))
		    {
			    unset($onePartner['status_use']);
		    }
			//$onePartner['change_by'] = "import F4";
			//$onePartner['changed'] = new \Nette\Utils\DateTime;							
			if ($tmpData['changed'] < $onePartner['changed'])
			{
				$row = array( 'updated' => ($this->DataManager->updateForeign($onePartner)>0 ? 'true':'false')  , 'id' => $tmpData->id );
			}else{
				if ($tmpData['changed'] > $onePartner['changed'])
				{
					$row = array( 'updated' => 'false', 'id' => $tmpData->id );
				}else{
					$row = array( 'updated' => 'true', 'id' => $tmpData->id );
				}
			}
		}	   
		return $row;
   }

   
   
    
}
