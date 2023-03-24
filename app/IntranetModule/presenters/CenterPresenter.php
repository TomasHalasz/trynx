<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class CenterPresenter extends \App\Presenters\BaseListPresenter {

  
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
    * @var \App\Model\CenterManager
    */
    public $DataManager;    

    
    protected function startup()
    {
		parent::startup();
		$this->dataColumns = array( 'name' => 'Označení střediska',
                        'description' => 'Popis',
                        'location' => 'Lokalita',
                        'email' => 'Email',
					    'public_event' => array('Externí přístup', 'format' => 'boolean'),
					    'created' => array('Vytvořeno','format' => 'datetime'),
					    'create_by' => 'Vytvořil',
					    'changed' => array('Změněno','format' => 'datetime'),
					    'change_by' => 'Změnil');
		$this->DefSort = 'name';
        $this->filterColumns = array(	'name' => 'autocomplete' , 'email' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('name', 'email');

		//$this->relatedTable = '';
		//$this->dataColumnsRelated = 	array();
		//$this->mainFilter = '';			
		
		$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
		parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);
    }
    
     public function renderEdit($id,$copy,$modal){
         $this->template->setFile($this->getCMZTemplate( __DIR__ . '/../templates/Center/edit.latte'));
		parent::renderEdit($id,$copy,$modal);	
    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.center']));
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
		
		$form->addText('name', 'name', 20, 20)
			->setRequired('nameRq')
			->setHtmlAttribute('placeholder','namePh');
        $form->addText('description', 'Popis', 20, 60)
            ->setRequired('popis')
            ->setHtmlAttribute('placeholder','popis');

        $form->addText('location', 'location', 30, 50)
            ->setHtmlAttribute('placeholder','locationPh');

		$form->addText('email', 'email', 30, 50)
			->setHtmlAttribute('placeholder','emailPh');		
		
		$form->addSubmit('send', 'save')->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'back')
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
			//dump($data->id);
			//die;
			if (!empty($data->id))
			$this->DataManager->update($data, TRUE);
			else
			$this->DataManager->insert($data);
		}
		$this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
    }	    
    
    

}
