<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class CurrenciesPresenter extends \App\Presenters\BaseListPresenter {

    
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
    * @var \App\Model\CurrenciesManager
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
        //$this->translator->setPrefix(['applicationModule.Currencies']);
        $this->dataColumns = array('currency_name' => $this->translator->translate('Měna'),
                                'currency_code' => $this->translator->translate('Kód'),
                                'fix_rate' => $this->translator->translate('Pevný_kurz'),
                                'rate' => $this->translator->translate('Kurz_ČNB'),
                                'amount' => $this->translator->translate('Počet_jednotek'),
                                'decimal_places_cash' => $this->translator->translate('Počet_des_míst_-_hotovost'),
                                'decimal_places' => $this->translator->translate('Počet_des_míst_-_ostatní'),
                                'create_by' => $this->translator->translate('Vytvořil'), 'created' => array($this->translator->translate('Dne'),'format' => 'datetime2linesec'),
                                'change_by' => $this->translator->translate('Změnil'), 'changed' => array($this->translator->translate('Dne'),'format' => 'datetime2linesec') );
        $this->FilterC = 'UPPER(currency_name) LIKE ?';
        $this->DefSort = 'currency_code';
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
        //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.CurrenciesPresenter']));

	    $form->addHidden('id',NULL);

	    $arrCurrencies = $this->CurrenciesManager->findAll()
									->select("currency_code AS code, CONCAT(currency_code, ' - ', currency_name) AS code2")
									->order('currency_name')
									->fetchPairs('code','code2');
        $arrCurrencies = $this->ArraysManager->getCurrenciesCodes();
	    //bdump($arrCurrencies);
	    $form->addSelect('currency_code', $this->translator->translate("Kód_měny"),$arrCurrencies)
            ->setTranslator(NULL)
		    ->setPrompt($this->translator->translate('Zvolte_měnu'));

		$form->addText('currency_name', $this->translator->translate('Název_měny'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_měny'));
		$form->addText('fix_rate', $this->translator->translate('Pevný_kurz'), 0, 20)
			->setRequired(FALSE)
			->setHtmlAttribute('placeholder',$this->translator->translate('Pevný_kurz'))
			->addRule(Form::FLOAT,$this->translator->translate('Musí_být_zadáno_číslo.'))
			->setDefaultValue('0');
	
		$form->addText('rate', $this->translator->translate('Kurz_ČNB'), 0, 20)
			->setRequired(FALSE)
			->setDisabled(TRUE)
			->setHtmlAttribute('placeholder',$this->translator->translate('Pevný_kurz'))
			->setDefaultValue('0');
		
		$form->addText('amount', $this->translator->translate('Počet_jednotek_pro_přepočet'), 0, 20)
			->setRequired(FALSE)				
			->setHtmlAttribute('placeholder',$this->translator->translate('Počet_jednotek'))
			->addRule(Form::INTEGER,$this->translator->translate('Musí_být_zadáno_číslo.'))
			->setDefaultValue('0');				
		$form->addText('decimal_places', $this->translator->translate('Počet_des_míst_-_ostatní'), 0, 20)
			->setRequired(FALSE)
			->setHtmlAttribute('placeholder',$this->translator->translate('Počet_des_míst'))
			->addRule(Form::INTEGER,$this->translator->translate('Musí_být_zadáno_číslo.'))
			->setDefaultValue('2');

        $form->addText('decimal_places_cash', $this->translator->translate('Počet_des_míst_-_hotovost'), 0, 20)
            ->setRequired(FALSE)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Počet_des_míst'))
            ->addRule(Form::INTEGER,$this->translator->translate('Musí_být_zadáno_číslo.'))
            ->setDefaultValue('0');

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
