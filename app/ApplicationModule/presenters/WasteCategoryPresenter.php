<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class WasteCategoryPresenter extends \App\Presenters\BaseListPresenter {

  
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
    * @var \App\Model\WasteCategoryManager
    */
    public $DataManager;                         

    
    protected function startup()
    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.KdbCategory']);
        $this->dataColumns = array(
                        'waste_code' => $this->translator->translate('Kód_odpadu'),
                        'name' => $this->translator->translate('Název_kategorie'),
                        'category' => $this->translator->translate('Kategorie'),
						'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));
		//$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
		$this->DefSort = 'waste_code';
		//$this->mainFilter = '';		
        $this->filterColumns = ['waste_code' => 'autocomplete', 'name' => 'autocomplete', 'category' => 'autocomplete'];        	
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['waste_code', 'name', 'cl_partners_book.company'];        
		
		$this->toolbar = array( 1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'),
                                2 => array('url' => $this->link('genWasteData!'), 'rightsFor' => 'read', 'label' => $this->translator->translate('Generovat_data'), 'title' => $this->translator->translate('Vygeneruje_výchozí_data_pro_kategorie_odpadů'), 'class' => 'btn btn-warning'));
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

		$form->addText('waste_code', $this->translator->translate('Kód_odpadu'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_kód_odpadu'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Kód_odpadu'));

        $form->addText('name', $this->translator->translate('Název_kategorie'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_název_kategorie'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_kategorie'));

		$form->addSelect('category',$this->translator->translate('Kategorie'),['O','N'])
        ->setRequired($this->translator->translate('Zadejte_kód_kategorie'))        
			->setHtmlAttribute('placeholder',$this->translator->translate('Kategorie'));
		
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

        if ($form['send']->isSubmittedBy()) {

			if (!empty($data->id))
			    $this->DataManager->update($data, TRUE);
			else
			    $this->DataManager->insert($data);
		}
		$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
		$this->redirect('default');
    }	    

    public function handleGenWasteData(){
        $this->DataManager->genDefaultData();
        $this->flashMessage($this->translator->translate('Data_byla_vytvořena'), 'success');
        $this->redrawControl('content');
    }

}
