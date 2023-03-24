<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form;

class ProjectPresenter extends \App\Presenters\BaseListPresenter {

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
    * @var \App\Model\ProjectManager
    */
    public $DataManager;

    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;


   
    protected function startup()
    {
	parent::startup();
    $this->formName = $this->translator->translate("Projekty");
    $this->mainTableName = 'cl_project';

	$this->dataColumns = [
        'dtm_start' => [$this->translator->translate('Začátek_projektu'), 'size' => 10, 'format' => 'date'],
        'label' => [$this->translator->translate('Název_projektu'), 'size' => 10, 'format' => 'text'],
        'dtm_end' => [$this->translator->translate('Konec_projektu'), 'size' => 10, 'format' => 'date'],
        'pr_finished' => [$this->translator->translate('Hotovo'), 'size' => 10, 'format' => 'boolean'],
        'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
        'create_by' => $this->translator->translate('Vytvořil'),
        'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],
        'change_by' => $this->translator->translate('Změnil')];

	//$this->FilterC = 'UPPER(currency_name) LIKE ?';
	//$this->filterColumns = array();	
	$this->DefSort = 'dtm_start DESC';
    $this->filterColumns = array('label' => 'autocomplete');

    $this->userFilterEnabled = TRUE;
    $this->userFilter = ['label', 'dtm_start', 'dtm_end'];

    $this->defValues = ['dtm_start' => new \Nette\Utils\DateTime];

	//$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');	
	//$this->readOnly = array('identification' => TRUE);	
	//$settings = $this->CompaniesManager->getTable()->fetch();	
	$this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];
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

        $form->addText('label', $this->translator->translate('Název_projektu'), 30, 30)
			->setHtmlAttribute('placeholder','Název');
	    
        $form->addText('dtm_start', $this->translator->translate('Začátek_projektu'), 10, 10)
            ->setHtmlAttribute('placeholder','Začátek');

        $form->addText('dtm_end', $this->translator->translate('Konec_projektu'), 10, 10)
            ->setHtmlAttribute('placeholder','Konec');

        $form->addCheckbox('pr_finished', $this->translator->translate('Hotovo'));


        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
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
            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        $this->redirect('default');
    }	    



}
