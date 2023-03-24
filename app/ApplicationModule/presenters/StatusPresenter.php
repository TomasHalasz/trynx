<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class StatusPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\StatusManager
    */
    public $DataManager;    

 
    protected function startup()
    {
	parent::startup();
	//$this->translator->setPrefix(['applicationModule.Status']);
	$this->dataColumns = array('status_use' => array($this->translator->translate('Použití'),FALSE,'function' => 'getStatusName'),
				    'status_name' => $this->translator->translate('Stav'),
				    's_new' => array($this->translator->translate('Nový_záznam'),'format' => 'boolean'),
				    's_work' => array($this->translator->translate('Rozpracovaný'),'format' => 'boolean'),
				    's_storno' => array($this->translator->translate('Stornovaný'),'format' => 'boolean'),
				    's_fin' => array($this->translator->translate('Ukončený'),'format' => 'boolean'),
				    's_eml' => array($this->translator->translate('Odeslán_email'),'format' => 'boolean'),
				    's_pdf' => array($this->translator->translate('Generován_PDF'),'format' => 'boolean'),
                    's_eshop' => array($this->translator->translate('Z_eshopu'),'format' => 'boolean'),
                    's_delivered' => array($this->translator->translate('Dodáno'),'format' => 'boolean'),
                    's_repair' => array($this->translator->translate('Oprava'),'format' => 'boolean'),
                    's_exp' => array($this->translator->translate('Expedice'),'format' => 'boolean'),
                    's_exp_ok' => array($this->translator->translate('Expedováno'),'format' => 'boolean'),
				    'color_hex' => array($this->translator->translate('Stítek'),'format' => 'colortag'));
	$this->FilterC = ' ';
	$this->DefSort = 'status_use';
    $this->filterColumns = array('status_use' => 'autocomplete' , 'status_name' => 'autocomplete');
    $this->userFilterEnabled = TRUE;
    $this->userFilter = array('status_use', 'status_name');
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
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
            $form->addText('status_name', $this->translator->translate('Stav'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_prosím_název_stavu'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_stavu'));
            $form->addHidden('color_hex');	    
	    
	    $arrStatus_use = $this->getStatusAll();
	    //dump($arrCountries);
	    //die;
		$form->addselect('status_use', $this->translator->translate('Použití'),$arrStatus_use)
			->setPrompt($this->translator->translate('Zvolte_použití'))
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_použití'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Použití'));
	    $form->addCheckbox('s_new', $this->translator->translate('Nový_záznam'))
			->setHtmlAttribute('class', 'items-show');	
	    $form->addCheckbox('s_work', $this->translator->translate('Rozpracovaný_záznam'))
			->setHtmlAttribute('class', 'items-show');			
	    $form->addCheckbox('s_storno', $this->translator->translate('Stornovaný_záznam'))
			->setHtmlAttribute('class', 'items-show');		    
	    $form->addCheckbox('s_fin', $this->translator->translate('Ukončený_záznam'))
			->setHtmlAttribute('class', 'items-show');		    	    
	    $form->addCheckbox('s_eml', $this->translator->translate('Odeslán_emailem'))
			->setHtmlAttribute('class', 'items-show');		    	    	    
	    $form->addCheckbox('s_pdf', $this->translator->translate('Generován_PDF'))
			->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('s_eshop', $this->translator->translate('Z_eshopu'))
            ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('s_delivered', $this->translator->translate('Dodáno'))
            ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('s_repair', $this->translator->translate('Oprava'))
            ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('s_exp', $this->translator->translate('Expedice'))
            ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('s_exp_ok', $this->translator->translate('Expedováno'))
            ->setHtmlAttribute('class', 'items-show');

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
            return $form;
    }

    public function stepBack()
    {	    
	$this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
	$data=$form->values;
	//dump($data);
	//	die;
        if ($form['send']->isSubmittedBy())
	{
	    //dump($data->id);
	    //die;
	    if (!empty($data->id))
		$this->DataManager->update($data, TRUE);
	    else
		$this->DataManager->insert($data);
	}
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
	$this->redirect('default');
    }	    


}
