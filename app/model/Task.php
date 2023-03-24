<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Task management.
 */
class TaskManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_task';

    /**
     * @inject
     * @var \App\Model\PartnersEventManager
     */
    public $PartnersEventManager;

    /**
     * @inject
     * @var \App\Model\NumberSeriesManager
     */
    public $NumberSeriesManager;

    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;

    /**
     * @inject
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;


    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor,
                                \Nette\Http\Session $session, PartnersEventManager $partnersEventManager, NumberSeriesManager $numberSeriesManager,
                                FilesManager $filesManager, CompaniesManager $companiesManager, ArraysManager $arraysManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->PartnersEventManager = $partnersEventManager;
        $this->NumberSeriesManager = $numberSeriesManager;
        $this->FilesManager = $filesManager;
        $this->CompaniesManager = $companiesManager;
        $this->ArraysManager = $arraysManager;
    }

    public function CreateTaskFromHD($id, $taskUser = NULL)
    {
            $newRow = FALSE;
            if ($tmpEvent = $this->PartnersEventManager->find($id)){
                $arrData = [];
                $arrData['cl_partners_event_id']    = $id;
                $arrData['cl_partners_book_id']     = $tmpEvent['cl_partners_book_id'];
                $arrData['cl_task_category_id']     = $tmpEvent['cl_task_category_id'];
                $arrData['task_date']               = $tmpEvent['date_rcv'];
                $arrData['payment']                 = $tmpEvent['payment'];
                $arrData['description']             = $tmpEvent['work_label'] . PHP_EOL . PHP_EOL . $tmpEvent['description_original'];
                $arrData['cl_users_id']             = $taskUser;
                //$arrData['cl_partners_category_id'] = $tmpEvent['cl_partners_category_id'];
                $nSeries = $this->NumberSeriesManager->getNewNumber('task',NULL,NULL, NULL);
                $arrData['cl_number_series_id']     = $nSeries['id'];
                $arrData['task_number']             = $nSeries['number'];
                $newRow = $this->insert($arrData);

                $tmpFiles = $this->FilesManager->findAllTotal()->
                                                    where(['cl_partners_event_id' => $id]);
                foreach ($tmpFiles as $one) {
                    $newData = $one->toArray();
                    unset($newData['id']);
                    $newData['cl_partners_event_id'] = null;
                    $newData['cl_task_id'] = $newRow->id;
                    //28.02.2022 - move physical files

                    $dataFolder  = $this->CompaniesManager->getDataFolder($newRow->cl_company_id);
                    $subFolder   = $this->ArraysManager->getSubFolder([], 'cl_partners_event_id');
                    $subFolder2  = $this->ArraysManager->getSubFolder([], 'cl_task_id');
                    $srcFile     =  $dataFolder . '/' . $subFolder . '/' . $one['file_name'];
                    $destFile    =  $dataFolder . '/' . $subFolder2 . '/' . $one['file_name'];
                    copy($srcFile, $destFile);
                    //unlink($srcFile);
                    //$file->move($destFile);
                    $this->FilesManager->insert($newData);
                }


            }
        return $newRow;
    }


}

