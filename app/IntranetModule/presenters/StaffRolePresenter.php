<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class StaffRolePresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\StaffRoleManager
     */
    public $DataManager;


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
     * @var \App\Model\TrainingTypesManager
     */
    public $TrainingTypesManager;



    protected function startup()
    {
        parent::startup();
       // $this->formName = "Role a skupiny zaměstnanců";
        $this->dataColumns = array( 'name' => array('Název role / skupiny', 'size' => 30),
                                    'in_training_types.name' => array('Nutné školení / prohlídka ', 'size' => 30),
                                    'description' => array('Popis', 'size' => 30),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');
        $this->relatedTable = 'in_staff_role';
        $this->dataColumnsRelated = array(
                                    'name' => array('Název místa', 'size' => 30),
                                    'in_training_types.name' => array('Nutné školení / prohlídka ', 'size' => 30),
                                    'description' => array('Popis', 'size' => 30),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');
        $this->mainFilter = 'in_staff_role.in_staff_role_id IS NULL';

        $this->filterColumns = array('name' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('name');


        $this->DefSort = 'name';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = array();
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));
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
        $form->addText('name', 'Název role / skupiny:', 30, 50)
			->setHtmlAttribute('placeholder','Název role nebo skupiny');
        $form->addTextArea('description', 'Popis:', 40, 5)
            ->setHtmlAttribute('placeholder','doplňte popis');

        $arrRoles = $this->DataManager->findAll()->where('in_staff_role_id IS NULL AND name != ""')->
                                                    select('name, id')->order('name')->fetchPairs('id','name');

        $form->addSelect('in_staff_role_id','Nadřazená role',$arrRoles)
            ->setPrompt('Žádné')
            ->setHtmlAttribute('placeholder','Nadřazená role');

        $arrTrainingTypes = $this->TrainingTypesManager->findAll()->
                                                            select('name, id')->order('name')->fetchPairs('id','name');

        $form->addSelect('in_training_types_id','Nutné školení / prohlídka',$arrTrainingTypes)
            ->setPrompt('Žádné')
            ->setHtmlAttribute('placeholder','Nutné školení / prohlídka');



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
	    if (!empty($data->id))
		$this->DataManager->update($data, TRUE);
	    else
		$this->DataManager->insert($data);
	}
	$this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
    }	    



}
