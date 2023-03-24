<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class StaffPlanPresenter extends \App\Presenters\BaseAppPresenter {


    /**
     * @inject
     * @var \App\Model\StaffManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;


    protected function startup()
    {
		parent::startup();

    }	


    public function renderDefault($only_end = FALSE)
    {
    	$tmpData = $this->DataManager->findAll();
    	if ($only_end){
    		$tmpData = $tmpData->where('end = 1');
		}
        $this->template->staff = $tmpData;
    	$this->template->only_end = $only_end;

    }


    /**called from latte with parameters to calculate
     * return result
     * @param $arrParams
     * @return mixed
     */
    public function getNextDate($arrParams)
    {
        $date = $arrParams['in_training.training_date'];
        $period = $arrParams['in_training.in_training_types.period'];
        $retVal = $date->modify('+'.$period.' month');
        return $retVal;
    }

    public function getTitleName($title)
    {
        return ($this->ArraysIntranetManager->getTitleName($title));
    }

}
