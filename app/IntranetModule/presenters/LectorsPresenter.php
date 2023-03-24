<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class LectorsPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\LectorsManager
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


    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;

        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_lectors_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }




    protected function startup()
    {
        parent::startup();
        $this->mainTableName = 'in_lectors';
        $this->dataColumns = array( 'full_name' => array('Příjmení a jméno školitele', 'size' => 30),
                                    'title_before' => array('Titul před', 'size' => 15),
                                    'title_after' => array('Titul za', 'size' => 15),
                                    'phone' => array('Telefon', 'size' => 30),
                                    'email' => array('Email', 'size' => 40),
                                    'description_txt' => array('Poznámka', 'size' => 30),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');


        $this->filterColumns = array(	'full_name' => 'autocomplete' , 'phone' => 'autocomplete', 'email' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('full_name', 'phone', 'email');


        $this->DefSort = 'full_name';
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
        $form->addText('full_name', 'Jméno a příjmení:', 30, 50)
			->setHtmlAttribute('placeholder','Jméno a příjmení');
        $form->addText('title_before', 'Titul před:', 10, 10)
            ->setHtmlAttribute('placeholder','');
        $form->addText('title_after', 'Titul za:', 10, 10)
            ->setHtmlAttribute('placeholder','');
        $form->addText('phone', 'Telefon:', 30, 30)
            ->setHtmlAttribute('placeholder','Telefon');
        $form->addText('email', 'Email:', 30, 60)
            ->setHtmlAttribute('placeholder','Email');
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
    }	    



}
