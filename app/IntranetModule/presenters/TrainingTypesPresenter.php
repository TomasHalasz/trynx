<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class TrainingTypesPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\TrainingTypesManager
    */
    public $DataManager;

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
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;


    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_training_types_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }


    protected function startup()
    {
        parent::startup();
        $this->dataColumns = array( 'name' => array('Název školení / prohlídky', 'size' => 30),
                                    'in_lectors.full_name' => array('Školitel / lékař - jméno', 'size' => 30),
                                    'in_lectors.phone' => array('Školitel / lékař - telefon', 'size' => 30),
                                    'in_lectors.email' => array('Školitel / lékař - email', 'size' => 30),
                                    'period' => array('Opakování jak často (roky)', 'size' => 10),
                                    'description_txt' => array('Poznámka', 'size' => 30),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');

        $this->filterColumns = array(	'name' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('name', 'in_lectors.full_name', 'in_lectors.email');

       // $this->filterColumns = array();
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
        $form->addText('name', 'Název školení / prohlídky:', 0, 50)
			->setHtmlAttribute('placeholder','Název školení / prohlídky');
        $form->addText('period', 'Opakování (roky):', 0, 5)
            ->setHtmlAttribute('placeholder','opakování');

        $arrLectors = $this->LectorsManager->findAll()->order('full_name')->fetchPairs('id','full_name');
        $form->addSelect('in_lectors_id', 'Školitel  / lékař:', $arrLectors)
            ->setHtmlAttribute('placeholder','školitel / lékař');
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
	    if (!empty($data->id))
		$this->DataManager->update($data, TRUE);
	    else
		$this->DataManager->insert($data);
	}
	$this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
        $this->redirect('default');
    }	    



}
