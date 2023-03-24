<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;

class CommissionPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\StaffManager
     */
    public $StaffManager;

    /**
     * @inject
     * @var \App\Model\StaffAttendanceManager
     */
    public $StaffAttendanceManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

    /**
     * @inject
     * @var \App\Model\WorksTypesManager
     */
    public $WorksTypesManager;


    /**
     * @inject
     * @var \App\Model\RentalManager
     */
    public $RentalManager;

    /**
     * @inject
     * @var \App\Model\EstateManager
     */
    public $EstateManager;

    /**
     * @inject
     * @var \App\Model\RentalEstateManager
     */
    public $RentalEstateManager;

    /**
    * @inject
    * @var \App\Model\CommissionManager
    */
    public $DataManager;    


    protected function startup()
    {
        parent::startup();
        $this->mainTableName = "cl_commission";
        $this->formName = "Zakázky";
        $arrData = ['cm_number' => ['Číslo zakázky', 'format' => 'text'],
            'cl_status.status_name' => ['Stav', 'format' => 'text'],
            'cl_center.name' => ['Středisko', 'format' => 'text'],
            'cm_title' => ['Popis', 'format' => 'textoneline'],
            'cm_date' => ['Datum zahájení', 'format' => 'date'],
            'delivery_date' => ['Datum ukončení', 'format' => 'date'],
            'cl_users.name' => ['Vedoucí', 'format' => 'text'],
            'cl_users_id2' => ['Zástupce', 'format' => 'text', 'function' => 'getUserName', 'function_param' => ['cl_users_id2']],
            'created' => ['Vytvořeno', 'format' => 'datetime'], 'create_by' => 'Vytvořil', 'changed' => ['Změněno', 'format' => 'datetime'], 'change_by' => 'Změnil'];

        $this->dataColumns = $arrData;

        $this->filterColumns = ['cm_number' => 'autocomplete', 'cl_center.name' => 'autocomplete', 'cm_title' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['cm_number', 'cl_center.name', 'cm_title'];
        //$this->filterColumns = array();
        $this->DefSort = 'cm_number DESC';
        $this->numberSeries = ['use' => 'commission', 'table_key' => 'cl_number_series_id', 'table_number' => 'cm_number'];
        $this->readOnly = ['cm_number' => TRUE];
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = [];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'],
                                2 => ['group' =>
                                    [0 => ['url' => $this->link('report!', ['index' => 1]), 'rightsFor' => 'report', 'label' => 'Zakázky', 'title' => 'Zakázky',
                                        'class' => 'ajax', 'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'iconfa-print'],
                                        1 => ['url' => $this->link('report!', ['index' => 2]), 'rightsFor' => 'report', 'label' => 'Zakázky - docházka', 'title' => 'Zakázky - docházka',
                                        'class' => 'ajax', 'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'iconfa-print'],
                                        2 => ['url' => $this->link('report!', ['index' => 3]), 'rightsFor' => 'report', 'label' => 'Zakázky - zápůjčky', 'title' => 'Zakázky - zápůjčky',
                                            'class' => 'ajax', 'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'iconfa-print'],
                                    ],
                                    'group_settings' => ['group_label' => 'Tisk', 'group_class' => 'btn btn-primary dropdown-toggle btn-sm', 'group_title' => 'tisk', 'group_icon' => 'iconfa-print']
                                ]
        ];
        $this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/Commission/rptCommissionSet.latte',
                                            'reportName' => 'Zakázky'],
                            2 => ['reportLatte' => __DIR__ . '/../templates/Commission/rptCommission2Set.latte',
                                            'reportName' => 'Zakázky - docházka'],
                            3 => ['reportLatte' => __DIR__ . '/../templates/Commission/rptCommission3Set.latte',
                                            'reportName' => 'Zakázky - zápůjčky']
        ];
        $this->bscOff = FALSE;
        $this->bscEnabled = FALSE;

    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
	        parent::renderEdit($id,$copy,$modal);

    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);

        $form->addHidden('id', NULL);
        $form->addText('cm_number', 'Číslo zakázky', 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', 'Číslo zakázky');
        $form->addText('cm_date', 'Datum přijetí', 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder','Datum přijetí');
        $form->addText('delivery_date', 'Datum ukončení', 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', 'Datum ukončení');
        $form->addTextArea('cm_title', 'Popis zakázky', 100, 4)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', 'Popis zakázky');
        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', "Středisko", $arrCenter)
            ->setHtmlAttribute('data-placeholder','Zvolte středisko')
            ->setPrompt('Zvolte středisko');
        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'commission')->order('s_new DESC,s_work DESC,s_fin DESC,s_storno DESC,status_name ASC')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id',"Stav", $arrStatus)
            ->setHtmlAttribute('data-placeholder', 'Zvolte stav zakázky')
            ->setPrompt('Zvolte stav zakázky');

        $arrUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addSelect('cl_users_id', "Vedoucí", $arrUsers)
            ->setHtmlAttribute('data-placeholder','Zvolte vedoucího')
            ->setPrompt('Zvolte vedoucího');

        //$arrUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addSelect('cl_users_id2', "Zástupce", $arrUsers)
            ->setHtmlAttribute('data-placeholder','Zvolte zástupce')
            ->setPrompt('Zvolte zástupce');


        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'Zpět')
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		$form->onSuccess[] = array($this,'SubmitEditSubmitted');
            return $form;
    }

    public function stepBack()
    {	    
	    $this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
	    $data=$form->values;
	    $data = $this->removeFormat($data);
        if ($form['send']->isSubmittedBy())
        {
            if (!empty($data->id))
            $this->DataManager->update($data, TRUE);
            else
            $this->DataManager->insert($data);
        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
    }


    protected function createComponentAttendance()
    {
        $arrStaff = $this->StaffManager->findAll()->
                                        select('id, CONCAT(surname," ", name, " ", personal_number) AS name')->
                                        where('end_date IS NULL')->order('surname ASC')->fetchPairs('id', 'name');
        $str = " - počet hodin: ";
        $arrWorksTypes = $this->WorksTypesManager->findAll()->
                                        select("id, IF(hours > 0, CONCAT(name, ?, hours), name) AS name", $str)->
                                        order('name ASC')->fetchPairs('id', 'name');
        $arrData = [
            'dtm_work' => ['Datum práce', 'format' => 'date', 'size' => 6],
            'in_works_types.name' => ['Typ práce', 'format' => 'chzn-select', 'size' => 10, 'values' => $arrWorksTypes],
            'hours' => ['Hodiny', 'format' => 'number', 'size' => 4],
            'in_staff.surname' => ['Příjmení', 'format' => 'chzn-select', 'size' => 12, 'values' => $arrStaff],
            'in_staff.name' => ['Jméno', 'format' => 'text', 'size' => 8, 'readonly' => true],
            'in_staff.personal_number' => ['Osobní číslo',  'format' => 'text', 'size' => 5, 'readonly' => true],
            'description' => ['Poznámka', 'format' => 'textarea', 'size' => 80, 'rows' => 4, 'newline' => true, 'classMy' => 'form-control input-sm newline description_txt'],
        ];
        $tmpNow = new DateTime();
        $control = new \App\Controls\ListgridControl(
            $this->translator,
            $this->StaffAttendanceManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            ['dtm_work' => $tmpNow], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'dtm_work DESC', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '', //txtSEarchcondition
            [], //toolbar
            FALSE, //forceEnable
            FALSE //paginator off
        );
        $control->setPaginatorOff();
        $control->setContainerHeight('auto');
        //$control->setFilter('dt_detectors_id')
        return $control;
    }


    protected function createComponentRentalEstate()
    {
        $arrEstate = [];
        $arrEstate['Dostupné'] = $this->EstateManager->findAll()
            ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
            ->where('cl_status.s_new = 1')
            ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Vypůjčeno'] = $this->EstateManager->findAll()
            ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
            ->where('cl_status.s_fin = 1')
            ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Oprava'] = $this->EstateManager->findAll()
            ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
            ->where('cl_status.s_repair = 1')
            ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Vyřazeno'] = $this->EstateManager->findAll()
            ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
            ->where('cl_status.s_storno = 1')
            ->order('est_name')->fetchPairs('id', 'name');

        $arrStaff = [];
        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                            order('cl_center.location,cl_center.name,in_staff.surname')->
                            where('in_staff.end = 0')->
                            fetchPairs('id','fullname');
        $arrStaff['Neaktivní'] =  $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                            order('cl_center.location,cl_center.name,in_staff.surname')->
                            where('in_staff.end = 1')->
                            fetchPairs('id','fullname');
        //bdump($arrStaff);
        $arrData = [
            'in_estate.est_number' => ['Ev. číslo', 'format' => 'chzn-select', 'size' => 7, 'values' => $arrEstate],
            'in_estate.est_name' => ['Název', 'format' => 'text', 'size' => 10, 'readonly' => true],
            'in_estate.s_number' => ['Sériové číslo', 'format' => 'text', 'size' => 8, 'readonly' => true],
            'in_staff.personal_number' => ['Osobní číslo', 'format' => 'chzn-select', 'size' => 7, 'values' => $arrStaff],
            'in_staff.surname' => ['Příjmení', 'format' => 'text', 'size' => 10, 'readonly' => true],
            'in_staff.name' => ['Jméno', 'format' => 'text', 'size' => 10, 'readonly' => true],
            'dtm_rent' => ['Vypůjčeno od', 'format' => 'datetime2', 'size' => 9],
            'dtm_return' => ['Vypůjčeno do', 'format' => 'datetime2', 'size' => 9],
            'returned' => ['Vráceno', 'format' => 'boolean', 'size' => 7],
            'description' => ['Poznámka', 'format' => 'textarea', 'size' => 100, 'rows' => 3, 'newline' => true]
        ];
        $tmpNow = new DateTime();
        $control = new \App\Controls\ListgridControl(
            $this->translator,
            $this->RentalEstateManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            ['dtm_rent' => $tmpNow], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            FALSE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            '', //orderColumn
            FALSE, //selectMode
            [], //quickSearchm
            "", //fontsize
            'in_rental.cl_commission_id', //parentcolumnname
            FALSE, //pricelistbottom
            TRUE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            'in_estate.est_number LIKE ? OR in_estate.est_name LIKE ? OR in_estate.s_number ?', //txtSEarchcondition
            [], //toolbar
            FALSE, //forceEnable
            TRUE //paginator off
        );
        //$control->setFilter('dt_detectors_id')
        $control->setContainerHeight('auto');

        $control->onChange[] = function () {
            $this->updateSum();
        };

        $control->onCustomFunction[] = function ($itemId, $type)
        {
            if ($type == 'returnItem') {
                $this->returnItem($itemId);
            }
        };

        return $control;
    }



    protected function createComponentReportCommission($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('cm_date_from', 'Zahájení od', 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', 'Zahájení od');

        $form->addText('cm_date_to', 'Zahájení do', 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', 'Zahájení do');

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', 'Střediska', $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte střediska pro tisk');

        $form->addSubmit('save', 'Tisk')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xls', 'XLS')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', 'Návrat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBackReportCommission'];
        $form->onSuccess[] = [$this, 'SubmitReportCommissionSubmitted'];
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function stepBackReportCommission()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportCommissionSubmitted(Form $form)
    {
        $data = $form->values;

        if ($form['save']->isSubmittedBy() || $form['save_xls']->isSubmittedBy()) {

            if ($data['cm_date_to'] == "")
                $data['cm_date_to'] = NULL;
            else {
                $data['cm_date_to'] = date('Y-m-d H:i:s', strtotime($data['cm_date_to']) + 86400 - 1);
            }

            if ($data['cm_date_from'] == "")
                $data['cm_date_from'] = NULL;
            else
                $data['cm_date_from'] = date('Y-m-d H:i:s', strtotime($data['cm_date_from']));

            $dataReport = $this->DataManager->findAll()->
                where('cm_date >= ? AND cm_date <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
                order('cm_number DESC, cm_date DESC');

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_commission.cl_center_id' => $data['cl_center_id']));
            }

            //bdump($data);
            $dataOther = [];//$this->CommissionTaskManager->find($itemId);
            $dataSettings = $data;
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(['id' => $data['cl_center_id']])->order('name');
            $dataOther['settings'] = $this->settings;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Commission/rptCommission.latte', $dataOther, $dataSettings, 'Přehled zakázek');
            $tmpDate1 = new \DateTime($data['cm_date_from']);
            $tmpDate2 = new \DateTime($data['cm_date_to']);
            if ($form['save']->isSubmittedBy()) {
                $this->pdfCreate($template, 'Přehled zakázek ' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_xls']->isSubmittedBy()) {
                //bdump('ted');
                $dataReport = $this->DataManager->findAll()->
                                select('cm_number,cl_center.name AS center,cl_status.status_name,cm_title,cm_date,delivery_date,( SELECT SUM(hours) AS hours FROM in_staff_attendance WHERE cl_commission_id = cl_commission.id) AS hours')->
                                where('cm_date >= ? AND cm_date <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
                                order('cm_number DESC, cm_date DESC');

                if (count($data['cl_center_id']) > 0) {
                    $dataReport = $dataReport->where(['cl_commission.cl_center_id' => $data['cl_center_id']]);
                }
                if ( $dataReport->count()>0)
                {
                    $filename ="Zakázky";
                    //bdump($filename);
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename."-" .date('Ymd-Hi').".xls",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
        }
    }

    protected function createComponentReportCommission2($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('cm_date_from', 'Docházka od', 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', 'Docházka od');

        $form->addText('cm_date_to', 'Docházka do', 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', 'Docházka do');

        $tmpArrCommission = $this->CommissionManager->findAll()->select('id, CONCAT(cm_number, " " , cm_title) AS name')->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_commission_id', 'Zakázky', $tmpArrCommission)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte zakázky pro tisk');


        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', 'Střediska', $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte střediska pro tisk');

        $form->addSubmit('save', 'Tisk')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xls','XLS')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', 'Návrat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBackReportCommission2'];
        $form->onSuccess[] = [$this, 'SubmitReportCommission2Submitted'];
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportCommission2()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportCommission2Submitted(Form $form)
    {
        $data = $form->values;
        if ($form['save']->isSubmittedBy() || $form['save_xls']->isSubmittedBy()) {

            if ($data['cm_date_to'] == "")
                $data['cm_date_to'] = NULL;
            else {
                $data['cm_date_to'] = date('Y-m-d H:i:s', strtotime($data['cm_date_to']) + 86400 - 1);
            }

            if ($data['cm_date_from'] == "")
                $data['cm_date_from'] = NULL;
            else
                $data['cm_date_from'] = date('Y-m-d H:i:s', strtotime($data['cm_date_from']));

            $dataReport = $this->DataManager->findAll()->
                        where(':in_staff_attendance.dtm_work >= ? AND :in_staff_attendance.dtm_work <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
                        order('cm_number DESC, cm_date DESC');

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(['cl_commission.cl_center_id' => $data['cl_center_id']]);
            }

            if (count($data['cl_commission_id']) > 0) {
                $dataReport = $dataReport->where(['cl_commission.id' => $data['cl_commission_id']]);
            }


            //bdump($data);
            $dataOther = [];//$this->CommissionTaskManager->find($itemId);
            $dataSettings = $data;
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(['id' => $data['cl_center_id']])->order('name');
            $dataOther['dataSettingsCommission'] = $this->CommissionManager->findAll()->where(['id' => $data['cl_commission_id']])->order('cm_number');
            $dataOther['settings'] = $this->settings;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Commission/rptCommission2.latte', $dataOther, $dataSettings, 'Zakázky docházka');
            $tmpDate1 = new \DateTime($data['cm_date_from']);
            $tmpDate2 = new \DateTime($data['cm_date_to']);
            if ($form['save']->isSubmittedBy()) {
                $this->pdfCreate($template, 'Zakázky docházka ' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_xls']->isSubmittedBy()) {
                //bdump('ted');
                $dataReport = $this->DataManager->findAll()->
                    select('cm_number AS cislo_zakazky,cl_center.name AS stredisko,cl_status.status_name AS stav, cm_title AS popis, cm_date AS datum_zahajeni, delivery_date AS datum_ukonceni,
                                    :in_staff_attendance.in_works_types.name AS typ_prace, (SELECT SUM(hours) AS hours FROM in_staff_attendance AS xxx WHERE cl_commission_id = cl_commission.id AND in_works_types_id = :in_staff_attendance.in_works_types_id ) AS hodiny,
                                    (SELECT SUM(hours * :in_staff_attendance.in_works_types.price) AS naklad FROM in_staff_attendance AS xxx WHERE cl_commission_id = cl_commission.id  AND in_works_types_id = :in_staff_attendance.in_works_types_id ) AS naklad')->
                    where(':in_staff_attendance.dtm_work >= ? AND :in_staff_attendance.dtm_work <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
                    group(':in_staff_attendance.in_works_types_id')->
                    order('cm_number DESC, cm_date DESC, :in_staff_attendance.in_works_types_id');
                if (count($data['cl_center_id']) > 0) {
                    $dataReport = $dataReport->where(['cl_commission.cl_center_id' => $data['cl_center_id']]);
                }
                if (count($data['cl_commission_id']) > 0) {
                    $dataReport = $dataReport->where(['cl_commission.id' => $data['cl_commission_id']]);
                }

                if ( $dataReport->count()>0)
                {
                    $filename ="Zakázky - docházka";
                    //bdump($filename);
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename."-" .date('Ymd-Hi').".xls",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
        }
    }

    public function getUserName($array)
    {
        $retVal = "";
        if (!is_null($array['cl_users_id2'])) {
            $tmpData = $this->UserManager->getUserById($array['cl_users_id2']);
            if ($tmpData) {
                $retVal = $tmpData['name'];
            }
        }
        return $retVal;
    }

    protected function createComponentReportCommission3($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('cm_date_from', 'Zápůjčky od', 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', 'Zápůjčky od');

        $form->addText('cm_date_to', 'Zápůjčky do', 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', 'Zápůjčky do');

        $tmpArrCommission = $this->CommissionManager->findAll()->select('id, CONCAT(cm_number, " " , cm_title) AS name')->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_commission_id', 'Zakázky', $tmpArrCommission)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte zakázky pro tisk');


        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', 'Střediska', $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte střediska pro tisk');

        $form->addSubmit('save','Tisk')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xls', 'XLS')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', 'Návrat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBackReportCommission3'];
        $form->onSuccess[] = [$this, 'SubmitReportCommission3Submitted'];
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportCommission3()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportCommission3Submitted(Form $form)
    {
        $data = $form->values;
        if ($form['save']->isSubmittedBy() || $form['save_xls']->isSubmittedBy()) {

            if ($data['cm_date_to'] == "")
                $data['cm_date_to'] = NULL;
            else {
                $data['cm_date_to'] = date('Y-m-d H:i:s', strtotime($data['cm_date_to']) + 86400 - 1);
            }

            if ($data['cm_date_from'] == "")
                $data['cm_date_from'] = NULL;
            else
                $data['cm_date_from'] = date('Y-m-d H:i:s', strtotime($data['cm_date_from']));

            $dataReport = $this->RentalManager->findAll()->
                                where('(DATE(:in_rental_estate.dtm_rent) >= DATE(?) AND DATE(:in_rental_estate.dtm_rent) <= DATE(?) ) 
                                                OR (DATE(:in_rental_estate.dtm_return) >= DATE(?) AND  DATE(:in_rental_estate.dtm_return) <= DATE(?))
                                                OR (DATE(:in_rental_estate.dtm_rent) <= DATE(?) AND :in_rental_estate.returned = 0 )',
                                                        $data['cm_date_from'], $data['cm_date_to'],
                                                        $data['cm_date_from'], $data['cm_date_to'],
                                                        $data['cm_date_to'])->
                                order('cl_commission.cm_number DESC, cl_commission.cm_date DESC');

            //where(':in_rental_estate.dtm_rent >= ? AND :in_rental_estate.dtm_rent <= ? ', $data['cm_date_from'], $data['cm_date_to'])->

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(['cl_commission.cl_center_id' => $data['cl_center_id']]);
            }

            if (count($data['cl_commission_id']) > 0) {
                $dataReport = $dataReport->where(['cl_commission.id' => $data['cl_commission_id']]);
            }


            //bdump($data);
            $dataOther = [];//$this->CommissionTaskManager->find($itemId);
            $dataSettings = $data;
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(['id' => $data['cl_center_id']])->order('name');
            $dataOther['dataSettingsCommission'] = $this->CommissionManager->findAll()->where(['id' => $data['cl_commission_id']])->order('cm_number');
            $dataOther['settings'] = $this->settings;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Commission/rptCommission3.latte', $dataOther, $dataSettings, 'Zakázky zápůjčky');
            $tmpDate1 = new \DateTime($data['cm_date_from']);
            $tmpDate2 = new \DateTime($data['cm_date_to']);
            if ($form['save']->isSubmittedBy()) {
                $this->pdfCreate($template, 'Zakázky zápůjčky ' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_xls']->isSubmittedBy()) {
                //bdump('ted');
                $dataReport = $this->RentalManager->findAll()->
                            select('cl_commission.cm_number AS cislo_zakazky, cl_commission.cl_center.name AS stredisko, cl_commission.cl_status.status_name AS stav, cl_commission.cm_title AS popis, :in_rental_estate.in_estate.est_number AS ev_cislo, :in_rental_estate.in_estate.est_name AS nazev, :in_rental_estate.dtm_rent AS datum_od, :in_rental_estate.dtm_return AS datum_do,
                                                :in_rental_estate.in_estate.rent_price AS sazba')->
                                            where('(DATE(:in_rental_estate.dtm_rent) >= DATE(?) AND DATE(:in_rental_estate.dtm_rent) <= DATE(?) ) 
                                                                            OR (DATE(:in_rental_estate.dtm_return) >= DATE(?) AND  DATE(:in_rental_estate.dtm_return) <= DATE(?))
                                                                            OR (DATE(:in_rental_estate.dtm_rent) <= DATE(?) AND :in_rental_estate.returned = 0 )',
                                                $data['cm_date_from'], $data['cm_date_to'],
                                                $data['cm_date_from'], $data['cm_date_to'],
                                                $data['cm_date_to'])->
                                            order('cl_commission.cm_number DESC, cl_commission.cm_date DESC');
                if (count($data['cl_center_id']) > 0) {
                    $dataReport = $dataReport->where(['cl_commission.cl_center_id' => $data['cl_center_id']]);
                }
                if ( $dataReport->count()>0)
                {
                    $filename ="Zakázky - zápůjčky";
                    //bdump($filename);
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename."-" .date('Ymd-Hi').".xls",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
        }
    }

}
