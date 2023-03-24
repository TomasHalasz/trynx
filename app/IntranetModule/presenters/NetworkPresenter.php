<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class NetworkPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\NetworkManager
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
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_places_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }




    protected function startup()
    {
        parent::startup();
        $this->dataColumns = array( 'domain_name' => array('Doménové jméno', 'size' => 30),
                                    'network_adr' => array('Adresa sítě', 'size' => 30),
                                    'public_adr' => array('Veřejná adresa', 'size' => 30),
                                    'description_txt' => array('Popis', 'size' => 30),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');
        $this->relatedTable = 'in_places';
        $this->filterColumns = array('domain_name' => 'autocomplete' , 'network_adr' => 'autocomplete', 'public_adr' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('domain_name', 'network_adr', 'public_adr');
        $this->DefSort = 'domain_name';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = array();
        $this->dataColumnsRelated = array();
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
        $form->addText('domain_name', 'Doménové jméno', 80, 80)
			->setHtmlAttribute('placeholder','Doménové jméno');
        $form->addText('network_adr', 'Adresa sítě', 33, 33)
            ->setHtmlAttribute('placeholder','Adresa sítě');
        $form->addText('public_adr', 'Veřejná adresa', 33, 33)
            ->setHtmlAttribute('placeholder','Veřejná adresa');
        $form->addTextArea('description_txt', 'Popis', 40, 5 )
            ->setHtmlAttribute('placeholder','Popis');
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
