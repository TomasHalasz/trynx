<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class PricesGroupsPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\PricesGroupsManager
    */
    public $DataManager;    

    
   
    protected function startup()
    {
	parent::startup();
	//$this->translator->setPrefix(['applicationModule.PricesGroups']);
	$this->dataColumns = array( 'name' => array($this->translator->translate('Název'), 'size' => 400),
				    'price_surcharge' => array($this->translator->translate('Přirážka_k_nákupní_ceně_%'), 'format' => 'decimal'),
				    'price_change' => array($this->translator->translate('Sleva_z_prodejní_ceny_%'), 'format' => 'decimal'),
				    'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));
	$this->filterColumns = array('name' => '' );
	$this->DefSort = 'name';

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
            $form->addText('name', $this->translator->translate('Název_skupiny'), 0, 20)
			->setAttribute('placeholder',$this->translator->translate('Název_skupiny'));
            $form->addText('price_surcharge', $this->translator->translate('Přirážka_k_nákupní_ceně_v_%'), 0, 20)
			->setAttribute('placeholder',$this->translator->translate('Přirážka_k_nákupní_ceně_v_%'));
            $form->addText('price_change', $this->translator->translate('Sleva_z_prodejní_ceny_%'), 0, 20)
			->setAttribute('placeholder',$this->translator->translate('Sleva_z_prodejní_ceny_%'));
	    
            $form->addSubmit('send', $this->translator->translate($this->translator->translate('Uložit')))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = [$this, 'stepBack'];
		$form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
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
