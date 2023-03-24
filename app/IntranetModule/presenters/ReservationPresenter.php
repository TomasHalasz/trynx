<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class ReservationPresenter extends \App\Presenters\BaseListPresenter {

    

    
    /** @persistent */
    public $page_b;
    
    /** @persistent */
    public $filter;        

    /** @persistent */
    public $filterColumn;            

    /** @persistent */
    public $filterValue;       
    
    /** @persistent */
    public $sortKey;        

    /** @persistent */
    public $sortOrder;


    /**
     * @inject
     * @var \App\Model\EstateReservationManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\UsersManager
     */
    public $UsersManager;

    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;

    /**
     * @inject
     * @var \App\Model\EstateManager
     */
    public $EstateManager;

    /**
     * @inject
     * @var \App\Model\StaffManager
     */
    public $StaffManager;

    /**
     * @inject
     * @var \App\Model\UserManager
     */
    public $UserManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\RentalEstateManager
     */
    public $rentalEstateManager;



    protected function startup()
    {
        parent::startup();
        $this->formName = "Rezervace majetku";
        $this->mainTableName = 'in_estate_reservation';
        ////$this->translator->setPrefix(['applicationModule.In']);
        //$arrStaffRole = $this->StaffRoleManager->getStaffRoleTreeNotNested();
        $arrData = array(
            'in_estate.est_number' => array('Ev. číslo', 'format' => 'text', 'size' => 8),
            'in_estate.est_name' => array('Označení', 'format' => 'text', 'size' => 12),
            'dtm_start' => array('Od', 'format' => 'datetime', 'size' => 10),
            'dtm_end' => array('Do', 'format' => 'datetime', 'size' => 10),
            'in_staff.surname' => array('Příjmení ','format' => 'chzn-select-req', 'size' => 10),
            'in_staff.name' => array('Jméno','format' => 'text', 'size' => 20, 'readonly' => TRUE),
            'cl_commission.cm_number' => array('Zakázka','format' => 'chzn-select-req', 'size' => 10),
            'description' => array('Poznámka','format' => 'textarea', 'size' => 80, 'rows' => 4, 'newline' => true),
        );

        $this->dataColumns = $arrData;

        /*array( 'name' => array('Název typu práce', 'format' => 'text', 'size' => 30),
                                    'hours' => array('Počet hodin', 'format' => 'number', 'size' => 15),
                                    'price' => array('Hodinová sazba', 'format' => 'currency', 'size' => 15),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');*/


        $this->filterColumns = array('in_estate.est_number' => 'autocomplete', 'in_estate.est_name' => 'autocomplete', 'dtm_start', 'dtm_end',
                                        'in_staff.surname' => 'autocomplete','in_staff.name' => 'autocomplete', 'in_staff.personal_number' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('name', 'est_number', 'est_name', 'surname', 'cl_commission.cm_number');
        $this->DefSort = 'dtm_start DESC';
        $now = new DateTime();
        $this->defValues = array('dtm_start' => $now);
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));

        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);
        $testDate2 = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate2->setTime(23, 59, 59);

        $this->conditionRows = array(array('dtm_start', '>=', $testDate, 'color:red', 'notlastcond'),
                                    array('dtm_start', '<=', $testDate2, 'color:red', 'lastcond'),
                                    array('dtm_start', '>', $testDate2, 'color:green', 'lastcond'));

        /*predefined filters*/
        $this->pdFilter = array(0 => array('url' => $this->link('pdFilter!', array('index' => 0, 'pdFilterIndex' => 0)),
                                            'filter' => 'DATE(dtm_start) >= DATE(NOW()) AND DATE(dtm_start) <= DATE(NOW())',
                                            'sum' => array(),
                                            'rightsFor' => 'read',
                                            'label' => ' - dnešní rezervace',
                                            'title' => 'Dnešní rezervace',
                                            'data' => array('data-ajax="true"', 'data-history="true"'),
                                            'class' => 'ajax', 'icon' => 'iconfa-filter'),
                                1 => array('url' => $this->link('pdFilter!', array('index' => 1, 'pdFilterIndex' => 1)),
                                    'filter' => 'DATE(dtm_start) > DATE(NOW())',
                                    'sum' => array(),
                                    'rightsFor' => 'read',
                                    'label' => ' - budoucí rezervace',
                                    'title' => 'Budoucí rezervace',
                                    'data' => array('data-ajax="true"', 'data-history="true"'),
                                    'class' => 'ajax', 'icon' => 'iconfa-filter'),
                                2 => array('url' => $this->link('pdFilter!', array('index' => 2, 'pdFilterIndex' => 2)),
                                    'filter' => 'DATE(dtm_start) < DATE(NOW())',
                                    'sum' => array(),
                                    'rightsFor' => 'read',
                                    'label' => ' - minulé rezervace',
                                    'title' => 'Minulé rezervace',
                                    'data' => array('data-ajax="true"', 'data-history="true"'),
                                    'class' => 'ajax', 'icon' => 'iconfa-filter'),
        );
/*        if ($this->settings->platce_dph == 0){
            $this->pdFilter[0]['sum'] = array('price_e2*currency_rate' => 'celkem');
            $this->pdFilter[1]['sum'] = array('price_e2*currency_rate' => 'celkem');
        }*/


    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
        parent::renderEdit($id,$copy,$modal);
	    $tmpNow = new DateTime();
        $tmpNow->setTime(0, 0, 0);
        $tmpData = $this->DataManager->find($id);
        $strDisabled = "";
        if (!is_null($tmpData['in_estate_id'])) {
            $tmpDisabled = $this->DataManager->findAll()->where('in_estate_id = ? AND dtm_start >= ? AND id != ?', $tmpData['in_estate_id'], $tmpNow, $id);
            foreach ($tmpDisabled as $key => $one) {
                if (is_null($one['dtm_end'])){
                    $tmpEnd = $one['dtm_start']->modifyClone('+1 month');
                }else{
                    $tmpEnd = $one['dtm_end'];
                }
                $days = ceil(abs(strtotime($tmpEnd) - strtotime($one['dtm_start'])) / 60 / 60 / 24);
                for ($i = 1; $i <= $days; $i++) {
                    $disDay = $one['dtm_start']->modifyClone('+' . $i . ' day');
                    $strDay = $disDay->format('d.m.Y');
                    $strDisabled .= '"' . $strDay . '",';
                }
            }
        }



       // bdump($strDisabled);
        $this->template->disabledDates = $strDisabled;
    }
    
    
    protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);
        $form->addText('dtm_start', 'Začátek rezervace', 30, 50)
                                    ->setRequired('Začátek rezervace musí být zadán')
                                    ->setHtmlAttribute('data-validation-mode', 'live')
			                        ->setHtmlAttribute('placeholder','rezervace od');
        $form->addText('dtm_end', 'Konec rezervace', 30, 50)
                                    ->setRequired('Konec rezervace musí být zadán')
                                    ->setHtmlAttribute('data-validation-mode', 'live')
                                    ->setHtmlAttribute('placeholder','rezervace do');
        $form->addTextArea('description', 'Poznámka', 40, 5)
                                     ->setHtmlAttribute('placeholder','');

        $arrStaff = array();
        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                                                order('cl_center.location,cl_center.name,in_staff.surname')->
                                                where('in_staff.end = 0')->
                                                fetchPairs('id','fullname');
        $arrStaff['Neaktivní'] =  $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                                                order('cl_center.location,cl_center.name,in_staff.surname')->
                                                where('in_staff.end = 1')->
                                                fetchPairs('id','fullname');
        $form->addSelect('in_staff_id','Zaměstnanec', $arrStaff)
            ->setRequired('Zaměstnanec musí být vybrán')
            ->setPrompt('vyberte')
            ->setHtmlAttribute('data-validation-mode', 'live')
            ->setHtmlAttribute('placeholder','Zaměstnanec');

/*        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'in_rental')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', 'Stav', $arrStatus)
            ->setHtmlAttribute('placeholder','Stav');
*/

        $arrCommission = array();
        $arrCommission['Aktivní'] = $this->CommissionManager->findAll()->
                                                select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
                                                where('delivery_date IS NULL OR delivery_date >= NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
        $arrCommission['Neaktivní'] = $this->CommissionManager->findAll()->
                                                select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
                                                where('delivery_date IS NULL OR delivery_date < NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
        $form->addSelect('cl_commission_id','Zakázka', $arrCommission)
            ->setRequired('Zakázka musí být vybrána')
            ->setPrompt('vyberte')
            ->setHtmlAttribute('data-validation-mode', 'live')
            ->setHtmlAttribute('placeholder','Zakázka');

        $arrEstate = array();
        $arrEstate['Dostupné'] = $this->EstateManager->findAll()
                                ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                ->where('cl_status.s_new = 1 AND cl_status.s_fin = 0')
                                ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Oprava'] = $this->EstateManager->findAll()
                                ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                ->where('cl_status.s_repair = 1')
                                ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Vyřazeno'] = $this->EstateManager->findAll()
                                ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                ->where('cl_status.s_storno = 1')
                                ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Vypůjčeno'] = $this->EstateManager->findAll()
                                ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                ->where('cl_status.s_fin = 1')
                                ->order('est_name')->fetchPairs('id', 'name');


        $form->addSelect('in_estate_id','Rezervovat', $arrEstate)
                                ->setRequired('Majetek k rezervaci musí být vybrán')
                                ->setPrompt('vyberte')
                                ->setHtmlAttribute('data-urlajax', $this->link('updateEstate!', array('id' => $this->id)))
                                ->setHtmlAttribute('data-validation-mode', 'live')
                                ->setHtmlAttribute('placeholder','Rezervovat');
        $arrEstate_dis = $this->EstateManager->findAll()
            ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
            ->where('cl_status.s_repair = 1 OR cl_status.s_storno = 1 OR cl_status.s_fin = 1')
            ->fetchPairs('id', 'id');


        $form['in_estate_id']->setDisabled($arrEstate_dis);
       // $form->onValidate[] = array($this, 'FormValidate');
        $tmpData = $this->DataManager->find($this->id);
        if (is_null($tmpData['in_estate_id'])) {
            $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class', 'btn btn-success')
                ->setValidationScope([]);
        }else{
            $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class', 'btn btn-success');
        }

	    $form->addSubmit('back', 'Zpět')
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');
        $form->onValidate[] = array($this, 'FormValidate');
		$form->onSuccess[] = array($this,'SubmitEditSubmitted');
            return $form;
    }

    public function FormValidate(Form $form)
    {
        $data=$form->values;
        //$data = $this->updatePartnerId($data);
        if ($data['in_staff_id'] == NULL || $data['in_staff_id'] == 0)
        {
            $form->addError($this->translator->translate('Zaměstnanec musí být vybrán'));
        }
        if ($data['cl_commission_id'] == NULL || $data['cl_commission_id'] == 0)
        {
            $form->addError($this->translator->translate('Zakázka musí být vybrána'));
        }
        if ($data['in_estate_id'] == NULL || $data['in_estate_id'] == 0)
        {
            $form->addError($this->translator->translate('Majetek musí být vybrán'));
        }

        //validate if estate isn't available in chosen period
        $testDate =  date('Y-m-d H:i:s', strtotime($data['dtm_start']));
        $tmpTest = $this->rentalEstateManager->findAll()->
                        where('in_estate_id = ? AND (dtm_rent <= ? AND dtm_return IS NULL)', $data['in_estate_id'], $testDate)->
                        fetch();
       // bdump($tmpTest);
        if ($tmpTest)
        {
            $form->addError($this->translator->translate('Ve_zvoleném_termínu_není_majetek_dostupný'));
        }

        $this->redrawControl('content');

    }

    public function stepBack()
    {	    
	    $this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        $data= $this->removeFormat($data);
        $tmpOld = $this->DataManager->find($data['id']);
        if ($form['send']->isSubmittedBy())
        {
            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
       //send email if there are major changes
        $newData = $this->DataManager->find($data['id']);
        if ($tmpOld['in_estate_id'] != $newData['in_estate_id'] ||
            $tmpOld['in_staff_id'] != $newData['in_staff_id'] ||
            $tmpOld['cl_commission_id'] != $newData['cl_commission_id'] ||
            $tmpOld['dtm_start'] != $newData['dtm_start'] ||
            $tmpOld['dtm_end'] != $newData['dtm_end'] ||
            $tmpOld['description'] != $newData['description']) {

                $email1 = $this->UsersManager->findAll()->where('estate_manager = 1 AND email LIKE "%@%"')->fetchPairs('id', 'email');
                $email2 = $this->UsersManager->findAll()->where('estate_manager = 1 AND email2 LIKE "%@%"')->fetchPairs('id', 'email2');
                $emailTo = array_merge($email1, $email2);
                $data = array();
                $emails = implode(';', $emailTo);
                $data['singleEmailTo'] = $emails;
                $data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email . '>';
                $tmpEmlText = $this->EmailingTextManager->getEmailingText('reservation', '', $newData, NULL);
                $data['subject'] = $tmpEmlText['subject'];
                $template = $this->createTemplate()->setFile(__DIR__ . '/../../templates/Emailing/email.latte');
                $template->body = $tmpEmlText['body'];
                $data['body'] = $template;
                //bdump($data);
                //send email
                $this->emailService->sendMail2($data);
                //save to cl_emailing
                $this->EmailingManager->insert($data);
        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
        if (!is_null($tmpOld['in_estate_id'])) {
            $this->redirect('default');
        }

    }

    public function handleUpdateEstate($id, $in_estate_id){
        $this->DataManager->update(array('id' => $id, 'in_estate_id' => $in_estate_id));
        $this->redrawControl('content');
    }

}
