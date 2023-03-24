<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class HeadersFootersPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\HeadersFootersManager
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
	//$this->translator->setPrefix(['applicationModule.HeadersFooters']);
	$this->dataColumns = array( 'cl_number_series.form_name' => array($this->translator->translate('Použití'), 'size' => 20, 'format' => 'text'),
				    'lang' => array($this->translator->translate('Jazyk'), 'size' => 10, 'arrValues' => $this->ArraysManager->getLanguages()),
				    'header_txt' => array($this->translator->translate('Záhlaví'), 'format' => 'text', 'size' => 40),
				    'footer_txt' => array($this->translator->translate('Zápatí'), 'format' => 'text', 'size' => 40),
				    'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));
	//$this->FilterC = 'UPPER(currency_name) LIKE ?';
	//$this->filterColumns = array();	
	$this->DefSort = 'cl_number_series.form_name';
	//$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');	
	//$this->readOnly = array('identification' => TRUE);	
	//$settings = $this->CompaniesManager->getTable()->fetch();	
	$this->defValues = array('lang' => 'cs');
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
	    $arrNumberSeries = $this->NumberSeriesManager->findAll()->order('form_name')->fetchPairs('id','form_name');
	    $form->addSelect('cl_number_series_id', $this->translator->translate("Pro_číselnou_řadu"),$arrNumberSeries)
		    ->setAttribute('data-placeholder',$this->translator->translate('Zvolte_číselnou_řadu'))
		    ->setAttribute('class','form-control chzn-select input-sm')
            ->setRequired($this->translator->translate('Číselná_řada_musí_být_zvolena'))
		    ->setPrompt($this->translator->translate('Zvolte_číselnou_řadu'));
	    
	    $form->addSelect('lang', $this->translator->translate("Jazyk"),$this->ArraysManager->getLanguages())
		    ->setAttribute('data-placeholder',$this->translator->translate('Jazyk'))
		    ->setAttribute('class','form-control chzn-select input-sm')
		    ->setPrompt($this->translator->translate('Zvolte_jazyk'));
            $form->addTextArea('header_txt', $this->translator->translate('Text_v_záhlaví'), 40, 7)
			->setAttribute('placeholder',$this->translator->translate('Text_v_záhlaví_dokladu'));
            $form->addTextArea('footer_txt', $this->translator->translate('Text_v_zápatí'), 40, 7)
			->setAttribute('placeholder',$this->translator->translate('Text_v_zápatí_dokladu'));

            $form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setAttribute('class','btn btn-warning')
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
		$this->DataManager->update($data);
	    else
		$this->DataManager->insert($data);
	}
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
	$this->redirect('default');
    }	    



}
