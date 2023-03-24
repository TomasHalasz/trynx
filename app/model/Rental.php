<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Rental management.
 */
class RentalManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_rental';


    /** @var App\Model\StatusManager */
    public $StatusManager;

    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                \App\Model\StatusManager $StatusManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->StatusManager = $StatusManager;

    }

	public function updateStatus($id){

        $tmpData = $this->StatusManager->findAll()->where('s_new = 1 AND status_use = ?', "in_rental")->fetch();
        if ($tmpData) {
            $tmpNewId = $tmpData['id'];
        }else {
            $tmpNewId = null;
        }

        $tmpData = $this->StatusManager->findAll()->where('s_fin = 1 AND status_use = ?', "in_rental")->fetch();
        if ($tmpData) {
            $tmpFinishId = $tmpData['id'];
        }else {
            $tmpFinishId = null;
        }

	    $tmpRentalMin = $this->findAll()
                                    ->where('in_rental.id = ?', $id)
                                    ->min(':in_rental_estate.dtm_rent');

        $tmpRentalMax = $this->findAll()
                                    ->where('in_rental.id = ?', $id)
                                    ->max(':in_rental_estate.dtm_return');

        $tmpCount = $this->findAll()
                                    ->where('in_rental.id = ?', $id)
                                    ->where(':in_rental_estate.returned = 0')
                                    ->count(':in_rental_estate.id');

	        if ($tmpCount == 0){
                $dtmMax         = $tmpRentalMax;
                $tmpStatusId    = $tmpFinishId;
            }else{
                $dtmMax         = null;
                $tmpStatusId    = $tmpNewId;
            }
	        $this->update(array('id' => $id, 'dtm_rent' => $tmpRentalMin, 'dtm_return' => $dtmMax, 'cl_status_id' => $tmpStatusId));



    }


	
}

