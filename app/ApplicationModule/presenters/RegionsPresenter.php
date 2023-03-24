<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class RegionsPresenter extends \App\Presenters\BaseListPresenter {


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
    * @var \App\Model\RegionsManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\CountriesManager
    */
    public $CountriesManager;        
    
   
    protected function startup()
    {
	parent::startup();
	//$this->translator->setPrefix(['applicationModule.Regions']);
	$this->dataColumns = array('region_name' => $this->translator->translate('Název regionu'), 'cl_countries.name' => $this->translator->translate('Stát'));
	//$this->formatColumns = array('valid_to' => "date");	
	$this->FilterC = '';
	$this->DefSort = 'cl_countries.name,region_name';
	$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový záznam'), 'class' => 'btn btn-primary'));
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);
    }
    
    public function renderEdit($id,$copy,$modal){
        if ($defData = $this->DataManager->findOneBy(array('id' => $id)))
        {
            $this['edit']->setValues($defData);

        }
        //if ($copy)
         //   $this['edit']->setValues(array('id' => ''));
    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);

	    $arrCountries = $this->CountriesManager->findAll()->fetchPairs('id','name');	    
	    $form->addSelect('cl_countries_id', $this->translator->translate("Stát"),$arrCountries)
		    ->setPrompt($this->translator->translate('Zvolte stát'));
            $form->addText('region_name', $this->translator->translate('Název kraje'), 0, 20)
			->setAttribute('placeholder',$this->translator->translate('Název kraje'));
            $form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
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
	    if (!empty($data->id))
		$this->DataManager->update($data, TRUE);
	    else
		$this->DataManager->insert($data);
	}
	$this->flashMessage($this->translator->translate('Změny byly uloženy'), 'success');
	$this->redirect('default');
    }	    


}
