<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class BankAccountsPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\BankAccountsManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\CurrenciesManager
     */
    public $CurrenciesManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.BankAccounts']);
        $this->dataColumns = array('account_number' => $this->translator->translate('Číslo_účtu'),
                       'bank_code' => $this->translator->translate('Kód_banky'),
                       'bank_name' => $this->translator->translate('Název_banky'),
                       'cl_currencies.currency_code' => array($this->translator->translate('Měna_účtu'), 'format' => 'text'),
                       'show_invoice' => array('format' => 'boolean',$this->translator->translate('Vždy_zobrazovat') ,TRUE),
                       'default_account' => array('format' => 'boolean',$this->translator->translate('Výchozí_účet_měny') ,TRUE),
                       'iban_code' => 'IBAN',
                       'swift_code' => 'SWIFT',
                        'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
                       'create_by' => $this->translator->translate('Vytvořil'),
                       'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),
                       'change_by' => $this->translator->translate('Změnil'));
        $this->FilterC = ' ';
        $this->DefSort = 'bank_code';
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
        //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.BankAccounts']));
	    $form->addHidden('id',NULL);
		$form->addText('account_number', $this->translator->translate('Číslo_účtu'), 30, 30)
			->setHtmlAttribute('placeholder',$this->translator->translate('Číslo_účtu'));
		$form->addText('bank_code', $this->translator->translate('Kód_banky'), 5, 5)
			->setHtmlAttribute('placeholder',$this->translator->translate('Kód_banky'));
		$form->addText('bank_name', $this->translator->translate('Název_banky'), 30, 30)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_banky'));
		$form->addText('iban_code', 'IBAN:', 30, 30)
			->setHtmlAttribute('placeholder','IBAN');    
		$form->addText('swift_code', 'SWIFT:', 30, 30)
			->setHtmlAttribute('placeholder','SWIFT');
	    $form->addCheckbox('default_account', $this->translator->translate('Výchozí_účet_měny'))
			->setHtmlAttribute('class', '');		    	    
	    $form->addCheckbox('show_invoice', $this->translator->translate('Vždy_zobrazovat'))
            ->setHtmlAttribute('title', $this->translator->translate('Vždy_zobrazovat_na_tištěných_dokladech'))
			->setHtmlAttribute('class', '');

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id','currency_code');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna_účtu"),$arrCurrencies)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setTranslator(NULL)
            ->setPrompt($this->translator->translate('Zvolte_měnu'));


        $form->addTextArea('info', $this->translator->translate('Poznámka'), 50,5);
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
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
	$this->redirect('default');
    }	    


}
