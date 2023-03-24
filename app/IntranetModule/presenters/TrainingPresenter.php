<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class TrainingPresenter extends \App\Presenters\BaseListPresenter {

    
    PUBLIC $createDocShow = FALSE;
    
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

    public 	$filterStaffUsed = array();
    
    /**
    * @inject
    * @var \App\Model\TrainingManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\StaffManager
     */
    public $StaffManager;

    /**
     * @inject
     * @var \App\Model\TrainingTypesManager
     */
    public $TrainingTypesManager;

    /**
     * @inject
     * @var \App\Model\TrainingStaffManager
     */
    public $TrainingStaffManager;



    /**
     * @inject
     * @var \App\Model\LectorsManager
     */
    public $LectorsManager;


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
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;

    /**
     * @inject
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;


    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_training_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }


    protected function createComponentTrainingStaff()
    {
        $arrStaff = array();
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
            'in_staff.cl_center.name' => ['Středisko','format' => 'text', 'size' => 10, 'readonly' => TRUE],
            'in_staff.cl_center.location' => ['Lokalita','format' => 'text', 'size' => 10, 'readonly' => TRUE],
            'in_staff.surname' => ['Příjmení ','format' => 'chzn-select-req', 'size' => 20, 'values' => $arrStaff],
            'in_staff.name' => ['Jméno','format' => 'text', 'size' => 20, 'readonly' => TRUE],
            'title' => ['Titul', 'format' => 'text', 'function' => 'getTitleName', 'function_param' => ['in_staff.title'],
                                                'size' => 10, 'readonly' => TRUE],
            'in_staff.birth_date' => ['Datum narození','format' => 'date', 'size' => 10, 'readonly' => TRUE],
            'in_staff.email' => ['Email','format' => 'text', 'size' => 30, 'readonly' => TRUE],
            'in_staff.phone' => ['Telefon','format' => 'text', 'size' => 30, 'readonly' => TRUE],
            'description' => ['Poznámka','format' => "text",'size' => 40]
        ];
        return new Controls\ListgridControl(
            $this->translator,
            $this->TrainingStaffManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'in_staff.surname', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '', //txtSEarchcondition
            [1 => ['url' => $this->link('createStaffSelectModalWindow!'), 'rightsFor' => 'write', 'label' => 'Hromadný výběr', 'class' => 'btn btn-primary',
                                'data' => ['data-ajax="true"', 'data-history="false"']]] //toolbar
        );
    }

    protected function createComponentListgridStaffSelect()
    {
        $arrData = ['surname' => ['Příjmení', 'format' => 'text', 'size' => 20],
                            'name' => ['Jméno','format' => 'text','size' => 20],
                            'title' => ['Titul','format' => 'text','size' => 5],
                            'birth_date' => ['Datum narození','format' => 'date','size' => 10],
                            'cl_center.location' => ['Lokalita', 'format' => 'text', 'size' => 10],
                            'cl_center.name' => ['Středisko', 'format' => 'text', 'size' => 15]
        ];
        $now = new \Nette\Utils\DateTime;
        $control =  new Controls\ListgridControl(
            $this->translator,
            $this->StaffManager, //data manager
            $arrData, //data columns
            [], //row conditions
            NULL , //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            FALSE,
            FALSE, //add empty row
            [], //custom links
            FALSE, //movable row
            'in_staff.surname', //ordercolumn
            TRUE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '' //txtSEarchcondition
        );


        return $control;

    }



    protected function startup()
    {
        parent::startup();
        $this->mainTableName = 'in_training';
        $this->dataColumns = ['training_date' => ['Datum školení', 'size' => 30, 'format' => 'date'],
                                    'in_training_types.name' => ['Název školení / prohlídky', 'size' => 30],
                                    'in_lectors.full_name' => ['Školitel / lékař - jméno', 'size' => 30],
                                    'time' => ['Čas školení', 'size' => 30],
                                    'place' => ['Místo školení', 'size' => 30],
                                    'description' => ['Poznámka', 'size' => 30],
                                    'created' => ['Vytvořeno','format' => 'datetime'],'create_by' => 'Vytvořil','changed' => ['Změněno','format' => 'datetime'],'change_by' => 'Změnil'];

        $this->filterColumns = ['in_training_types.name' => 'autocomplete', 'in_lectors.full_name' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['name', 'in_lectors.full_name', 'in_training_types.name', 'in_training_types.description'];

       // $this->filterColumns = array();
        $this->DefSort = 'training_date DESC';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = [];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary']];

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
	    $form->addHidden('id',NULL);
        $form->addText('training_date', 'Datum školení / prohlídky:', 0, 10)
			->setHtmlAttribute('placeholder','Datum školení / prohlídky')
            ->setRequired('Datum musí být vyplněn');

        $form->addText('time', 'Čas:', 0, 5)
            ->setHtmlAttribute('placeholder','Čas');
        $form->addText('place', 'Místo:', 0, 5)
            ->setHtmlAttribute('placeholder','Místo');
        $form->addText('duration', 'Trvání (hodin):', 0, 5)
            ->setHtmlAttribute('placeholder','Trvání');

        $arrTrainingTypes = $this->TrainingTypesManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('in_training_types_id', 'Školení / prohlídka:', $arrTrainingTypes)
            ->setHtmlAttribute('placeholder','školení / prohlídka')
            ->setRequired('Typ školení musí být vybrán');


        $arrLectors = $this->LectorsManager->findAll()->order('full_name')->fetchPairs('id','full_name');
        $form->addSelect('in_lectors_id', 'Školitel  / lékař:', $arrLectors)
            ->setHtmlAttribute('placeholder','školitel / lékař')
            ->setPrompt('Zvolte školitele / lékaře');

        $form->addTextArea('description', 'Poznámka:', 30, 8)
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
            if (empty($data['in_lectors_id']))
            {
                if ($tmpTrainingType = $this->TrainingTypesManager->findAll()->where('id = ?', $data['in_training_types_id'])->fetch())
                    $data['in_lectors_id'] = $tmpTrainingType->in_lectors_id;
            }

            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }

        $this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
    }

    public function handleCreateStaffSelectModalWindow()
    {
        $this->createDocShow = TRUE;
        $tmpTrainingStaff = $this->TrainingStaffManager->findAll()->where('in_training_id = ?', $this->id)->fetchPairs('in_staff_id','in_staff_id');
        //bdump($tmpTrainingStaff );
        if (count($tmpTrainingStaff) > 0) {
            $this->filterStaffUsed = array('filter' => 'id  NOT IN (' . implode(',', $tmpTrainingStaff) . ')');
        }else{
            $this->filterStaffUsed = array();
        }
        //bdump($this->filterStaffUsed );
        $this->showModal('createStaffSelectModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleInsertStaff( $dataItemsSel, $dataItems)
    {
        $arrDataItems = json_decode($dataItems, true);
        $arrDataItemsSel = json_decode($dataItemsSel, true);
        $order = $this->TrainingStaffManager->findAll()->where('in_training_id = ?', $this->id )->max('item_order');
        if (is_null($order)){
            $order = 1;
        }

        foreach ($arrDataItemsSel as $key => $one) {
            $arrInsert = array();
            $arrInsert['in_staff_id']       = $one;
            $arrInsert['item_order']        = $order;
            $arrInsert['in_training_id']    = $this->id;
            $arrInsert['created']           = new \Nette\Utils\DateTime;
            $arrInsert['changed']           = new \Nette\Utils\DateTime;
            $this->TrainingStaffManager->insert($arrInsert);
            $order++;
        }
        $this->redrawControl('staff');
        $this->redrawControl('contents');

    }


    public function getTitleName($arr)
    {
        return $this->ArraysIntranetManager->getTitleName($arr['in_staff.title']);
    }

    public function DataProcessListGrid($data)
    {
        unset($data['title']);
        return $data;
    }

    public function handlePrintPresentationList()
    {
        $dataReport = $this->DataManager->find($this->id);
        $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $tmpTitle = 'Prezenční listina';

        $dataOther = array();
        $dataSettings = array();
        $dataOther['subtitle'] = $dataReport->in_training_types->name;


        $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Training/presentionlist.latte', $dataOther, $dataSettings, $tmpTitle);
        $this->pdfCreate($template, $tmpTitle . ' ' . $dataOther['subtitle']);
    }

}
