<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class CountriesPresenter extends \App\Presenters\BaseListPresenter {

    
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
    * @var \App\Model\CountriesManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;          
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;        
    
    protected function startup()
    {
	parent::startup();
	$this->dataColumns = array('name' => 'Stát', 'acronym' => 'Kód', 'currency' => 'Měna', 'vat' => array('Plátce DPH',TRUE));
	$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
	$this->DefSort = 'name';
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
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
            $form->addText('name', 'Název:', 20, 20)
			->setRequired('Zadejte prosím název státu')
			->setAttribute('placeholder','Název');
	    $arrCountries = $this->getCountriesCodes();
	    //dump($arrCountries);
	    //die;
            $form->addselect('acronym', 'Kód státu:',$arrCountries)
			->setAttribute('placeholder','Kód');	
	    $arrCurrencies = $this->CurrenciesManager->findAll()->fetchPairs('currency_code','currency_code');
	    $form->addSelect('currency', "Měna:",$arrCurrencies)
		    ->setPrompt('Zvolte měnu');
	    $form->addCheckbox('vat', 'Plátce DPH')
		 ->setAttribute('class', 'items-show');	
	    
            $form->addSubmit('send', 'Uložit')->setAttribute('class','btn btn-primary');
	    $form->addSubmit('back', 'Zpět')
		    ->setAttribute('class','btn btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		$form->onSuccess[] = $this->SubmitEditSubmitted;
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
	    $tmpOk = TRUE;
	    $oldName = $this->DataManager->find($data->id)->name;
	    if ( $oldName != $data->name && $oldName != '')
	    {
		//if there is change of Country2, we must check if the change is possible		
		if ($tmpCountry = $this->PartnersManager->findBy(['country2' => $oldName])->fetch())
		{
		    //this country name is used, prevent before change
		    $tmpOk = FALSE;
		    $this->flashMessage('Změnu názvu státu není možné uložit. Tento stát je použit u partnera '.$tmpCountry->company, 'success');
		    //$this->redirect('default');		    
		}
	    }
	    if ($tmpOk)
	    {
		if (!empty($data->id))
		    $this->DataManager->update($data, TRUE);
		else
		    $this->DataManager->insert($data);
		
		$this->flashMessage('Změny byly uloženy.', 'success');
	    }
	}
	$this->redirect('default');
    }	    

    public function beforeDeleteBaseList($id)
    {
	$tmpCountryName = $this->DataManager->find($id)->name;
	if ($tmpCountry = $this->PartnersManager->findBy(array('country2' => $tmpCountryName))->fetch())
	{
	    $this->flashMessage('Záznam není možné vymazat. Je použitý v knize partnerů','danger');
	    return false; 			    
	}
	else 
	    return TRUE;
    
    }

}
