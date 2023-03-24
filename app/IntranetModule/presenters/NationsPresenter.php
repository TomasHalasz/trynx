<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class NationsPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\NationsManager
    */
    public $DataManager;    


    protected function startup()
    {
        parent::startup();
        $this->dataColumns = array( 'name' => array('Státní občanství', 'size' => 30),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');

        $this->filterColumns = array('name' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('name');
        //$this->filterColumns = array();
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
        $form->addText('name', 'Státní občanství:', 30, 50)
			->setHtmlAttribute('placeholder','Státní občanství');

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
