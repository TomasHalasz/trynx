<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class StaffPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\NationsManager
     */
    public $NationsManager;

    /**
     * @inject
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;

    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

    /**
     * @inject
     * @var \App\Model\ProfessionManager
     */
    public $ProfessionManager;


    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;

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
     * @var \App\Model\TrainingManager
     */
    public $TrainingManager;

    /**
     * @inject
     * @var \App\Model\TrainingStaffManager
     */
    public $TrainingStaffManager;
	
	/**
	 * @inject
	 * @var \App\Model\EstateStaffManager
	 */
	public $EstateStaffManager;
	
	/**
	 * @inject
	 * @var \App\Model\EstateManager
	 */
	public $EstateManager;

    /**
     * @inject
     * @var \App\Model\StaffAttendanceManager
     */
    public $StaffAttendanceManager;

    /**
     * @inject
     * @var \App\Model\StaffScoreManager
     */
    public $StaffScoreManager;

    /**
     * @inject
     * @var \App\Model\WorksTypesManager
     */
    public $WorksTypesManager;

    /**
     * @inject
     * @var \App\Model\StaffRoleManager
     */
    public $StaffRoleManager;

    protected function createComponentStaffScore()
    {
        $arrData = array(
            'date' => array('Datum hodnocení','format' => 'date', 'size' => 30),
            'description' => array('Poznámka','format' => 'textarea-formated', 'size' => 150, 'rows' => 10)
        );
        return new Controls\ListgridControl(
            $this->translator,
            $this->StaffScoreManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array(), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array(), //custom links
            FALSE, //movableRow
            'date', //orderColumn
            FALSE, //selectMode
            array(), //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '' //txtSEarchcondition
        );
    }

    protected function createComponentTrainingStaff()
    {
		$arrTrainings = $this->TrainingManager->findAll()->select('in_training.id AS training_id, in_training_types.name AS training_name')->
								order('in_training_types.name')->
								fetchPairs('training_id','training_name');
		
        $arrData = array(
                         'in_training.in_training_types.name' => array('Název školení','format' => 'text', 'size' => 30, 'values' => $arrTrainings),
                         'in_training.training_date' => array('Datum školení', 'format' => 'date', 'size' => 40, 'readonly' => TRUE),
                         'next_date' => array('Příští školení', 'format' => 'date', 'readonly' => TRUE, 'function' => 'getNextDate', 'function_param' => array('in_training.training_date', 'in_training.in_training_types.period' )),
                         'description' => array('Poznámka','format' => "text",'size' => 40)
                        );
        return new Controls\ListgridControl(
            $this->translator,
            $this->TrainingStaffManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array(), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array(), //custom links
            FALSE, //movableRow
            'in_training.training_date', //orderColumn
            FALSE, //selectMode
            array(), //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
             '' //txtSEarchcondition
        );
    }
	
	protected function createComponentEstateStaff()
	{
		$arrTypes = $this->EstateManager->findAll()->
							order('est_name')->
							where('in_estate_type.group_type = 1')->
							fetchPairs('id','est_name');
		//bdump($arrTypes);
		$arrData = array(
							'in_estate.est_name' => array('Název','format' => 'text', 'size' => 30,'values' =>  $arrTypes,),
							'description' => array('Poznámka', 'format' => 'text', 'size' => 40),
							'in_estate.est_description' => array('Popis', 'format' => 'text', 'size' => 40, 'readonly' => TRUE)
		);
		return new Controls\ListgridControl(
            $this->translator,
			$this->EstateStaffManager, //data manager
			$arrData, //data columns
			array(), //row conditions
			$this->id, //parent Id
			array(), //default data
			$this->DataManager, //parent data manager
			NULL, //pricelist manager
			NULL, //pricelist partner manager
			TRUE, //enable add empty row
			array(), //custom links
			TRUE, //movableRow
			'', //orderColumn
			FALSE, //selectMode
			array(), //quickSearch
			"", //fontsize
			FALSE, //parentcolumnname
			FALSE, //pricelistbottom
			FALSE, //readonly
			FALSE, //nodelete
			FALSE, //enablesearch
			'' //txtSEarchcondition
		);
	}

    protected function createComponentAttendance()
    {
/*        $arrCommission = $this->CommissionManager->findAll()->
                                    select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
                                    where('delivery_date IS NULL OR delivery_date >= NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
*/
        $arrCommission = array();
        $arrCommission['Aktivní'] = $this->CommissionManager->findAll()->
                            select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
                            where('delivery_date IS NULL OR delivery_date >= NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
        $arrCommission['Neaktivní'] = $this->CommissionManager->findAll()->
                            select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
                            where('delivery_date IS NULL OR delivery_date < NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
        $str = " - počet hodin: ";

        $arrWorksTypes = $this->WorksTypesManager->findAll()->
                                    select("id, IF(hours > 0, CONCAT(name, ?, hours), name) AS name", $str)->
                                    order('name ASC')->fetchPairs('id', 'name');

        $arrData = array(
            'dtm_work' => array('Datum práce', 'format' => 'date', 'size' => 6),
            'in_works_types.name' => array('Typ práce', 'format' => 'chzn-select', 'size' => 10, 'values' => $arrWorksTypes),
            'hours' => array('Hodiny', 'format' => 'number', 'size' => 8),
            'cl_commission.cm_number' => array('Zakázka', 'format' => 'chzn-select', 'size' => 15, 'values' => $arrCommission),
            'cl_commission.cl_center.name' => array('Středisko', 'format' => 'text', 'size' => 15, 'readonly' => true),
            'description' => array('Poznámka', 'format' => 'textarea', 'size' => 80, 'rows' => 4, 'newline' => true, 'classMy' => 'form-control input-sm newline description_txt'),
        );
        $tmpNow = new DateTime();
        $control = new \App\Controls\ListgridControl(
            $this->translator,
            $this->StaffAttendanceManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array('dtm_work' => $tmpNow), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array(), //custom links
            FALSE, //movableRow
            'dtm_work DESC', //orderColumn
            FALSE, //selectMode
            array(), //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '', //txtSEarchcondition
            array(), //toolbar
            FALSE, //forceEnable
            FALSE //paginator off
        );
        //$control->setFilter('dt_detectors_id')
        $control->setPaginatorOff();
        $control->setContainerHeight('auto');
        return $control;
    }



    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_staff_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }



    protected function startup()
    {
        parent::startup();
        $this->formName = "Zaměstnanci";
        $this->mainTableName = 'in_staff';
        $arrLang = $this->ArraysManager->getLanguages();
        $this->dataColumns = array( 'personal_number' => array('Osobní číslo', 'size' => 20),
                                    'surname' => array('Příjmení', 'size' => 30),
                                    'name' => array('Jméno', 'size' => 30),
                                    'title' => array('Titul', 'size' => 30, 'arrValues' => $this->ArraysIntranetManager->getTitles()),
                                    'birth_date' => array('Datum narození', 'format' => 'date', 'size' => 10),
                                    'birth_place' => array('Místo narození', 'format' => 'text', 'size' => 12),
                                    'cl_center.name' => array('Středisko', 'size' => 20),
                                    'in_profession.name' => array('Profese', 'size' => 20),
                                    'in_staff_role.name' => array('Role/skupina', 'size' => 20),
                                    'start_date' => array('Datum nástupu', 'format' => 'date', 'size' => 10),
                                    'end' => array('Ukončen PP', 'format' => 'boolean'),
                                    'end_date' => array('Datum ukončení PP', 'format' => 'date', 'size' => 10),
                                    'phone' => array('Telefon', 'size' => 10),
                                    'email' => array('Email', 'size' => 10),
                                    'gender' => array('Pohlaví', 'size' => 10, 'arrValues' => $this->ArraysIntranetManager->getGenders()),
                                    'in_nations.name' => array('Státní občanství', 'size' => 10),
                                    'lang' => array('Jazyk', 'size' => 10, 'arrValues' => $arrLang),
                                    'street' => array('Bydliště (ulice)', 'size' => 20),
                                    'city' => array('Bydliště (město)', 'size' => 10),
                                    'number' => array('Bydliště (číslo)', 'size' => 10),
                                    'number2' => array('Bydliště (číslo popisné)', 'size' => 10),
                                    'cl_countries.name' => array('Bydliště (stát)', 'size' => 10),
                                    'description_txt' => array('Poznámka', 'size' => 30),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');

        $this->filterColumns = array(	'presonal_number' => 'autocomplete' , 'surname' => 'autocomplete', 'name', 'cl_center.name', 'in_profession.name', 'phone', 'email', 'in_nations.name', 'cl_countries.name');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('personal_number', 'surname', 'name');
        //$this->filterColumns = array();
        $this->DefSort = 'personal_number';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = array();
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'),
                                2 => array('url' => $this->link(':Application:ImportData:', array('modal' => TRUE, 'target' => $this->name)), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary modalClick',
                                'data' => array('data-href', 'data-history="false"',
                                        'data-title = "Import CSV"')),
                                3 => array('group' =>
                                array(0 => array('url' => $this->link('report!', array('index' => 1)), 'rightsFor' => 'report', 'label' => 'Docházka', 'title' => 'Docházka',
                                    'class' => 'ajax', 'data' => array('data-ajax="true"', 'data-history="false"'), 'icon' => 'iconfa-print'),
                                ),
                                'group_settings' => array('group_label' => 'Tisk', 'group_class' => 'btn btn-primary dropdown-toggle btn-sm', 'group_title' => 'tisk', 'group_icon' => 'iconfa-print')
                            )
            );
        $this->report = array(1 => array('reportLatte' => __DIR__ . '/../templates/Staff/rptStaffSet.latte',
                                            'reportName' => 'Docházka'),
                            2 => array('reportLatte' => __DIR__ . '/../templates/Commission/rptCommission2Set.latte',
                                'reportName' => 'Zakázky podrobně'));
        $this->bscOff = FALSE;
        $this->bscEnabled = FALSE;

    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
            $this->template->setFile($this->getCMZTemplate( __DIR__ . '/../templates/Staff/edit.latte'));
	        parent::renderEdit($id,$copy,$modal);

    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    $form->addHidden('id',NULL);
        $form->addText('personal_number', 'Osobní číslo:', 20, 20)
			    ->setHtmlAttribute('placeholder','Osobní číslo');
        $form->addSelect('title', 'Titul', $this->ArraysIntranetManager->getTitles())
            ->setPrompt('Vyberte titul')
                ->setHtmlAttribute('placeholder','titul');

        $arrProfessions = $this->ProfessionManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('in_profession_id', 'Profese:', $arrProfessions)
                ->setPrompt('Vyberte profesi')
                ->setHtmlAttribute('placeholder','profese');

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_center_id', 'Středisko:', $arrCenter)
            ->setPrompt('Vyberte středisko')
            ->setHtmlAttribute('placeholder','Středisko');

        $arrNations = $this->NationsManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('in_nations_id', 'Státní občanství:', $arrNations)
            ->setPrompt('Vyberte občanství')
            ->setHtmlAttribute('placeholder','Státní občanství');

        $arrLang = $this->ArraysManager->getLanguages();
        $form->addSelect('lang', 'Jazyk', $arrLang)
            ->setPrompt('Vyberte jazyk')
            ->setHtmlAttribute('placeholder','Jazyk');

        $arrCountries = $this->CountriesManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_countries_id', 'Bydliště - stát:', $arrCountries)
            ->setPrompt('Vyberte stát')
            ->setHtmlAttribute('placeholder','Bydliště - stát');

        $arrStaffRole = $this->StaffRoleManager->getStaffRoleTreeNotNested();
        $form->addSelect('in_staff_role_id', 'Role/skupina:', $arrStaffRole)
            ->setPrompt('Vyberte skupinu')
            ->setHtmlAttribute('placeholder','Role/skupina');

        $form->addText('name', 'Jméno:', 20, 40)
            ->setHtmlAttribute('placeholder','Jméno');
        $form->addText('surname', 'Příjmení:', 20, 40)
            ->setHtmlAttribute('placeholder','Příjmení');
        $form->addText('birth_date', 'Datum narození:', 20, 20)
            ->setHtmlAttribute('placeholder','Datum narození');
        $form->addText('birth_place', 'Místo narození:', 20, 50)
            ->setHtmlAttribute('placeholder','Místo narození');

        $form->addText('start_date', 'Datum nástupu:', 20, 20)
            ->setHtmlAttribute('placeholder','Datum nástupu');
        $form->addText('end_date', 'Datum ukončení PP:', 20, 20)
            ->setHtmlAttribute('placeholder','Datum ukončení PP');

        $form->addText('phone', 'Telefon:', 20, 40)
            ->setHtmlAttribute('placeholder','Telefon');
        $form->addText('email', 'Email:', 20, 40)
            ->setHtmlAttribute('placeholder','Email');
        $form->addSelect('gender', 'Pohlaví:', $this->ArraysIntranetManager->getGenders())
            ->setPrompt('Vyberte pohlaví')
            ->setHtmlAttribute('placeholder','Pohlaví');

        $form->addText('city', 'Bydliště - město:', 20, 40)
            ->setHtmlAttribute('placeholder','Bydliště - město');
        $form->addText('street', 'Bydliště - ulice:', 20, 40)
            ->setHtmlAttribute('placeholder','Bydliště - ulice');
        $form->addText('number', 'Bydliště - číslo:', 10, 10)
            ->setHtmlAttribute('placeholder','Bydliště - číslo');

        $form->addText('number2', 'Číslo popisné:', 10, 10)
            ->setHtmlAttribute('placeholder','Číslo popisné');

        $form->addText('work_rate', 'Hodinová sazba:', 10, 10)
            ->setHtmlAttribute('placeholder','sazba');

        $form->addText('zip', 'Bydliště - PSČ:', 10, 10)
            ->setHtmlAttribute('placeholder','Bydliště - PSČ');

        $form->addTextArea('description_txt', 'Poznámka:', 30, 8)
            ->setHtmlAttribute('placeholder','Poznámka');

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
        if ($form['send']->isSubmittedBy())
        {
            $data = $this->removeFormat($data);
            
            if ($data['end_date'] != '')
			{
				$data['end'] = 1;
			}else{
				$data['end'] = 0;
			}

            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        //$this->redirect('default');
        $this->redrawControl('content');
    }


    /**called from latte with parameters to calculate
     * return result
     * @param $arrParams
     * @return mixed
     */
    public function getNextDate($arrParams)
    {
    	//bdump($arrParams);
		if (!is_null($arrParams['in_training.training_date'])) {
			$date = $arrParams['in_training.training_date'];
			$period = $arrParams['in_training.in_training_types.period'];
			if (gettype($date) == 'string'){
			    $retVal =    new DateTime();
            }else{
			    $retVal = $date->modify('+' . $period . ' year');
            }
		}else{
			$retVal = new DateTime();
		}
		
        return $retVal;
    }

    /**Data processing before insert/update on listgrid
     * @param $arrData
     * @return mixed
     */
    public function DataProcessListGrid($arrData)
    {
        unset($arrData['next_date']);
        return $arrData;
    }


    protected function createComponentReportStaff($name)
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

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', 'Střediska', $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte střediska pro tisk');

        $form->addSubmit('save', 'Tisk')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xls', 'XLS')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportStaff');
        $form->onSuccess[] = array($this, 'SubmitReportStaffSubmitted');
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

    public function stepBackReportStaff()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportStaffSubmitted(Form $form)
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

            $dataReport = $this->StaffAttendanceManager->findAll()->
                            where('dtm_work >= ? AND dtm_work <= ? AND in_staff_id IS NOT NULL', $data['cm_date_from'], $data['cm_date_to'])->
                            order('in_staff.surname, in_staff.name DESC');

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_commission.cl_center_id' => $data['cl_center_id']));
            }

            //bdump($data);
            $dataOther = array();//$this->CommissionTaskManager->find($itemId);
            $dataSettings = $data;
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(array('id' => $data['cl_center_id']))->order('name');
            $dataOther['settings'] = $this->settings;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Staff/rptStaff.latte', $dataOther, $dataSettings, 'Přehled docházky');
            $tmpDate1 = new \DateTime($data['cm_date_from']);
            $tmpDate2 = new \DateTime($data['cm_date_to']);
            if ($form['save']->isSubmittedBy()) {
                $this->pdfCreate($template, 'Přehled docházky ' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_xls']->isSubmittedBy()) {
                //bdump('ted');
                $dataReport = $this->StaffAttendanceManager->findAll()->select('in_staff.personal_number AS osobni_cislo, in_staff.surname AS prijmeni, in_staff.name AS jmeno, in_staff.work_rate AS sazba,   
                                                            cl_commission.cm_number AS cislo_zakazky,cl_commission.cl_center.name AS stredisko, dtm_work AS datum, hours AS hodiny, (in_staff.work_rate * hours) AS mzda')->
                                        where('dtm_work >= ? AND dtm_work <= ? AND in_staff_id IS NOT NULL', $data['cm_date_from'], $data['cm_date_to'])->
                                        order('in_staff.surname, in_staff.name DESC');


                if (count($data['cl_center_id']) > 0) {
                    $dataReport = $dataReport->where(array('cl_commission.cl_center_id' => $data['cl_center_id']));
                }
                if ( $dataReport->count()>0)
                {
                    $filename ="Docházka";
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
