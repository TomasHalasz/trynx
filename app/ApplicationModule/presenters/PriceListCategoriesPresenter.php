<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class PriceListCategoriesPresenter extends \App\Presenters\BaseListPresenter {

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
    * @var \App\Model\PriceListCategoriesManager
    */
    public $DataManager;    

   
    protected function startup()
    {
	parent::startup();
        //$this->translator->setPrefix(['applicationModule.PriceListGroup']);
	$this->dataColumns = ['name' => [$this->translator->translate('Název'), 'size' => 15],
                        'alcohol_oro' => [$this->translator->translate('Líh'), 'format'=> 'boolean', 'size' => 15],
				    'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],'create_by' => $this->translator->translate('Vytvořil'),
                     'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
	$this->FilterC = 'name LIKE ?';
	$this->filterColumns = [];
	$this->userFilterEnabled = TRUE;
	$this->userFilter = ['name'];

    $this->DefSort = 'name';
	$this->defValues = [];
	$this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];
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
        $form->addText('name', $this->translator->translate('Název_kategorie'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_kategorie'));
        $form->addCheckbox('alcohol_oro', $this->translator->translate('Obsahuje_líh'));
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
                $this->DataManager->update($data);
            else
                $this->DataManager->insert($data);
        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        $this->redirect('default');
    }	    


}
