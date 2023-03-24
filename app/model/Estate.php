<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Nette\Utils\DateTime;

/**
 * Estate management.
 */
class EstateManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'in_estate';

    /** @var App\Model\StatusManager */
    public $StatusManager;

    /** @var App\Model\CenterManager */
    public $CenterManager;

    /** @var App\Model\PlacesManager */
    public $PlacesManager;

    /** @var App\Model\EstateDiaryManager */
    public $EstateDiaryManager;

    /** @var App\Model\EstateMovesManager */
    public $EstateMovesManager;

    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                \App\Model\StatusManager $StatusManager,
                                \App\Model\PlacesManager $PlacesManager,
                                \App\Model\EstateDiaryManager $EstateDiaryManager,
                                \App\Model\EstateMovesManager $EstateMovesManager,
                                \App\Model\CenterManager $CenterManager
                                )
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->StatusManager        = $StatusManager;
        $this->PlacesManager        = $PlacesManager;
        $this->EstateDiaryManager   = $EstateDiaryManager;
        $this->EstateMovesManager   = $EstateMovesManager;
        $this->CenterManager        = $CenterManager;


    }



    public function updateStatus($id){

        $tmpData = $this->StatusManager->findAll()->where('s_new = 1 AND status_use = ?', "estate")->fetch();
        if ($tmpData) {
            $tmpNewId = $tmpData['id'];
        }else {
            $tmpNewId = null;
        }

        $tmpData = $this->StatusManager->findAll()->where('s_fin = 1 AND status_use = ?', "estate")->fetch();
        if ($tmpData) {
            $tmpRentId = $tmpData['id'];
        }else {
            $tmpRentId = null;
        }

        $tmpData = $this->findAll()->where(':in_rental_estate.returned = 0 AND in_estate.id = ?', $id)->count(':in_rental_estate.id');
        //bdump($tmpData);
        if ($tmpData > 0){
            $this->update(array('id' => $id, 'cl_status_id' => $tmpRentId ));
        }else{
            $this->update(array('id' => $id, 'cl_status_id' => $tmpNewId ));
        }
    }


    public function updateEstate($tmpOldData, $data){

        $this->database->beginTransaction();
        try {
            if (is_null($tmpOldData['old_in_estate_type_id']))
                $data['old_in_estate_type_id'] = $tmpOldData['in_estate_type_id'];

            if (!is_null($tmpOldData['in_places_id'])) {
                $oldPlace = $this->PlacesManager->find($tmpOldData['in_places_id']);
            }else{
                $oldPlace = FALSE;
            }

            if (!is_null($tmpOldData['cl_center_id'])) {
                $oldCenter = $this->CenterManager->find($tmpOldData['cl_center_id']);
            }else{
                $oldCenter = FALSE;
            }

            if (!empty($data->id))
                $this->update($data, TRUE);
            else
                $this->insert($data);

            if (($tmpOldData['in_places_id'] != $data['in_places_id']) || ($tmpOldData['cl_center_id'] != $data['cl_center_id'])) {
                $newPlace = $this->PlacesManager->find($data['in_places_id']);
                $newPlaceName = ($newPlace) ? $newPlace['place_name'] : '';
                $maxOrder = $this->EstateDiaryManager->findAll()->where('in_estate_id = ?', $data['id'])->max('item_order');
                $arrDiary = [];
                $arrDiary['in_estate_id'] = $data['id'];
                $arrDiary['item_order'] = $maxOrder + 1;
                if (!is_null($tmpOldData['in_places_id']) && ($tmpOldData['in_places_id'] != $data['in_places_id']) ) {
                    $arrDiary['description_short'] = $oldPlace['place_name'] . ' >>> ' . $newPlaceName;
                }elseif (is_null($tmpOldData['in_places_id']))  {
                    $arrDiary['description_short'] = 'pořízení >>> ' . $newPlaceName;
                }

                if (!is_null($data['cl_center_id'])){
                    $newCenter = $this->CenterManager->find($data['cl_center_id']);
                }else{
                    $newCenter = '';
                }
                if (!is_null($tmpOldData['cl_center_id']) && ($tmpOldData['cl_center_id'] != $data['cl_center_id'])) {
                    if (strlen($arrDiary['description_short']) > 0)
                        $arrDiary['description_short'] .= ' / ';
                    $arrDiary['description_short'] .= 'středisko: ';
                    $arrDiary['description_short'] .= $oldCenter['name'] . ' >>> ' . $newCenter['name'];
                }elseif ($tmpOldData['cl_center_id'] != $data['cl_center_id']){
                    if (strlen($arrDiary['description_short']) > 0)
                        $arrDiary['description_short'] .= ' / ';
                    $arrDiary['description_short'] .= ' středisko: ';
                    $arrDiary['description_short'] .=  $newCenter['name'];
                }

                $arrDiary['date'] = new DateTime();
                $arrDiary['event_type'] = 4;
                $this->EstateDiaryManager->insert($arrDiary);
            }

            //bdump($tmpOldData['in_places_id']);
            //bdump($data['in_places_id']);
            if (($tmpOldData['in_places_id'] != $data['in_places_id']) || ($tmpOldData['cl_center_id'] != $data['cl_center_id'])) {
                //bdump($tmpOldData);
                //bdump($data);
                $arrMove = [];
                $maxOrder = $this->EstateMovesManager->findAll()->where('in_estate_id = ?', $data['id'])->max('item_order');
                $arrMove['in_estate_id'] = $data['id'];
                $arrMove['item_order'] = $maxOrder + 1;
                $arrMove['move_date'] = new DateTime();
                $arrMove['est_name'] = $tmpOldData['est_name']; //save old est_name
                $arrMove['host_name'] = $tmpOldData['host_name']; //save old host_name
                if (!is_null($tmpOldData['cl_center_id']))
                    if ($oldCenter = $this->CenterManager->find($tmpOldData['cl_center_id']))
                        $arrMove['center_name'] = $oldCenter['name']; //save old center_name
                    else
                        $arrMove['center_name'] = 'nenalezeno';

                $arrMove['move_type'] = 1;
                if (!is_null($tmpOldData['in_places_id'])) {
                    $arrMove['in_places_id'] = $tmpOldData['in_places_id'];
                    $arrMove['cl_center_id'] = $tmpOldData['cl_center_id'];
                    $this->EstateMovesManager->insert($arrMove);
                }

                $arrMove['move_type'] = 0;
                $arrMove['in_places_id'] = $data['in_places_id'];
                $arrMove['cl_center_id'] = $data['cl_center_id'];
                $arrMove['item_order'] = $maxOrder + 2;
                $arrMove['est_name'] = $data['est_name']; //save new est_name
                $arrMove['host_name'] = $data['host_name']; //save new host_name
                $newData = $this->find($data['id']);
                $arrMove['center_name'] = $newData->cl_center['name']; //save new center_name
                $this->EstateMovesManager->insert($arrMove);
            }
            $this->database->commit();
        }catch(Exception $e){
            Debugger::log($e->getMessage());
            $this->database->rollBack();
        }

    }

	
}

