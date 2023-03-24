<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class CashDefPresenter extends \App\Presenters\BaseListPresenter {

    
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
    * @var \App\Model\CashDefManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\CurrenciesRatesManager
    */
    public $CurrenciesRatesManager;        
    
   
    protected function startup()
    {
	parent::startup();
        //$this->translator->setPrefix(['applicationModule.CashDef']);
	$this->dataColumns = array('def_cash' => array($this->translator->translate('Výchozí'), 'format' => 'boolean'), 'name' => $this->translator->translate('Název_pokladny'),'short_name' => $this->translator->translate('Zkratka'), 'cl_currencies.currency_code' => $this->translator->translate('Měna'),
							'create_by' => $this->translator->translate('Vytvořil'), 'created' => array($this->translator->translate('Dne'),'format' => 'datetime2linesec'), 
							'change_by' => $this->translator->translate('Změnil'), 'changed' => array($this->translator->translate('Dne'),'format' => 'datetime2linesec') );		
	$this->FilterC = 'UPPER(name) LIKE ?';
	$this->DefSort = 'name';
	$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'));
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	    parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);
    }
    
    public function renderEdit($id,$copy,$modal){
        parent::renderEdit($id,$copy,$modal);
        if ($defData = $this->DataManager->findOneBy(array('id' => $id)))
            $this['edit']->setValues($defData);
      //  if ($copy)
       //     $this['edit']->setValues(array('id' => ''));
    }
    
    
    protected function createComponentEdit($name)
    {	
		$form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    $arrCurrencies = $this->CurrenciesManager->findAllTotal()->fetchPairs('id','currency_code');
	    $form->addSelect('cl_currencies_id', $this->translator->translate("Měna_pokladny:"),$arrCurrencies)
		    ->setPrompt($this->translator->translate('Zvolte_měnu'));

        $form->addCheckbox('def_cash', $this->translator->translate('Výchozí_pokladna'));

		$form->addText('name', $this->translator->translate('Název'), 40, 60)
			->setRequired(TRUE)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_pokladny'));

        $form->addText('short_name', $this->translator->translate('Zkratka'), 5, 5)
            ->setRequired(TRUE)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Zkratka_názvu'));
		

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
        if ($form['send']->isSubmittedBy())
	{
	    if (!empty($data->id))
		    $this->DataManager->update($data, TRUE);
	    else
		    $this->DataManager->insert($data);
	}
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
        $this->redirect('default');
    }	    


}
