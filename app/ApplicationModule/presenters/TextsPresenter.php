<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class TextsPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\TextsManager
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
	//$this->translator->setPrefix(['applicationModule.Texts']);
	$this->dataColumns = array( 'name' => array($this->translator->translate('Název'), 'size' => 40, 'format' => 'text'),
				    'text' => array($this->translator->translate('Text'), 'size' => 40, 'format' => 'text'),
				    'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));
	//$this->FilterC = 'UPPER(currency_name) LIKE ?';
	//$this->filterColumns = array();	
	$this->DefSort = 'name';
	//$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');	
	//$this->readOnly = array('identification' => TRUE);	
	//$settings = $this->CompaniesManager->getTable()->fetch();	
	$this->defValues = array();
	$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'));
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

            $form->addText('name', $this->translator->translate('Název'), 40, 40)
			->setHtmlAttribute('placeholder','Název');
	    
	    $arrStatus_use = $this->getStatusAll();
	    $form->addselect('text_use', $this->translator->translate('Použití'),$arrStatus_use)
		    ->setPrompt($this->translator->translate('Zvolte_použití'))
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_použití'))
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Použití'));

            $form->addTextArea('text', $this->translator->translate('Text'), 40, 7)
			->setHtmlAttribute('placeholder',$this->translator->translate('Text'));

            $form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
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
	    if (!empty($data->id))
		$this->DataManager->update($data, TRUE);
	    else
		$this->DataManager->insert($data);
	}
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
	$this->redirect('default');
    }	    



}
