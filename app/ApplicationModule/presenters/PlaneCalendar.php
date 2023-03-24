<?php
namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class PlaneCalendarPresenter extends \App\Presenters\BaseAppPresenter {

    public $myReadOnly,$txtSearch = NULL;

    public $scope_type = "month";

    public $scope_start = 0;

    /** @persistent */
    public $id;

    /**
     * @inject
     * @var \App\Model\CalendarPlaneManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\CommissionTaskManager
     */
    public $CommissionTaskManager;

    public function actionDefault()
    {

    }

    public function renderDefault() {

        $this->template->modal = FALSE;

        $mySection = $this->session->getSection('calendar');

        if (!isset($mySection['scope_type']))
            $mySection['scope_type'] = 'month';

        if (!isset($mySection['scope_start'])) {
            $today = new DateTime();
            $today->setTime(0,0,0);
            $mySection['scope_start'] = $this->setScope( $today, $mySection['scope_type']);
        }



        $this->scope_start  = $mySection['scope_start'];
        $this->scope_type   = $mySection['scope_type'];
        if ($this->scope_type == "month") {
            $this->template->maxcels = $this->scope_start->format('t');
        }elseif($this->scope_type == "year") {
            $this->template->maxcels = 12;
        }elseif($this->scope_type == "week") {
            $this->template->maxcels = 7;
        }

        $this->template->scope_start = $this->scope_start;
        $this->template->scope_type = $this->scope_type;
        bdump($this->scope_start);
        //prepare data to show
        $this->template->tasks = $this->CommissionTaskManager->findAll()->
                                    where('cl_commission.cl_status.s_fin = 0 AND cl_commission.cl_status.s_storno = 0 ')->
                                    where('done = 0 AND cl_calendar_plane_id IS NULL')->
                                    order('cl_commission.cm_number, item_order');

        $this->template->calendar_data = $this->DataManager->findAll()->
                                    where('start_date >= ?', $this->scope_start)->
                                    order('cl_users.name ASC, start_date ASC');
    }


    public function handleSetScopePrev()
    {
        $mySection = $this->session->getSection('calendar');
        $tmpDate = $mySection['scope_start'];
        $this->scope_start = $tmpDate->modify('-1 '.$mySection['scope_type']);
        $mySection['scope_start'] = $this->scope_start;
        $this->redrawControl('content');
    }
    public function handleSetScopeNext()
    {
        $mySection = $this->session->getSection('calendar');
        $tmpDate = $mySection['scope_start'];
        $this->scope_start = $tmpDate->modify('+1 '.$mySection['scope_type']);
        $mySection['scope_start'] = $this->scope_start;
        $this->redrawControl('content');
    }
    public function handleSetScopeNow()
    {
        $mySection = $this->session->getSection('calendar');
        $tmpDate =  new DateTime();

        $tmpScope_start = $this->setScope($tmpDate, $mySection['scope_type']);
        $this->scope_start = $tmpScope_start;
        $mySection['scope_start'] = $this->scope_start;

        $this->redrawControl('content');
    }

    public function handleSetScope($type)
    {
        $mySection = $this->session->getSection('calendar');
        $this->scope_type = $type;
        $mySection['scope_type'] = $this->scope_type;


        //$tmpDate = $mySection['scope_start'];
        $tmpDate =  new DateTime();
        $tmpScope_start = $this->setScope($tmpDate, $mySection['scope_type']);
        $this->scope_start = $tmpScope_start;

        $mySection['scope_start'] = $this->scope_start;


        $this->redrawControl('content');
    }

    public function setScope($tmpDate, $scope_type)
    {
        $scope_start =  new DateTime();
        if ($scope_type == "week") {
            $scope_start = $tmpDate->modify('Monday this week');
        }
        if ($scope_type == "month") {
            $scope_start = $tmpDate->modify('first day of this month');
        }
        if ($scope_type == "year") {
            $tmpDate = $tmpDate->setDate($tmpDate->format('Y'), 1, 1);
            //$scope_start = $tmpDate;
            $scope_start = $tmpDate->setTime(0,0,0);
            //$scope_start = date_create_from_format('d/m/Y', '01/01/'.$tmpDate->format('Y'));
        }
        return $scope_start;
    }

    public function handleCalUpdate($calendar_plane_id, $cl_commission_task_id, $date_start, $date_end)
    {
        bdump($calendar_plane_id);
        bdump($date_start);
        bdump(strtotime($date_end));
        bdump($cl_commission_task_id);
        $testDate = strtotime($date_end);
        if ($testDate && $testDate > 0) {
            $arrData = array('id' => $calendar_plane_id, 'cl_commission_task_id' => $cl_commission_task_id,
                'start_date' => $date_start, 'end_date' => $date_end);
            $tmpData = $this->DataManager->find($calendar_plane_id);
            if ($tmpData) {
                $tmpData->update($arrData);
                $retId = NULL;
            } else {
                unset($arrData['id']);
                $newRow = $this->DataManager->insert($arrData);
                $tmpTask = $this->CommissionTaskManager->find($cl_commission_task_id);
                if ($tmpTask) {
                    $tmpTask->update(array('cl_calendar_plane_id' => $newRow->id));
                }
                $retId = $newRow->id;
            }
            $this->payload->retId = $retId;
        }else{
            $tmpData = $this->DataManager->find($calendar_plane_id);
            if ($tmpData){
                $tmpData->delete();
            }

        }
        $this->sendPayload();
    }
}
