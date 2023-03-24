<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class TagsPresenter extends \App\Presenters\BaseListPresenter {
    
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
    * @var \App\Model\TagsManager
    */
    public $DataManager;    

    
    protected function startup()
    {
	parent::startup();
	//$this->translator->setPrefix(['applicationModule.Tags']);
	$this->dataColumns = array('tag_name' => $this->translator->translate('Dotační titul'),
				    'create_by' => $this->translator->translate('Vytvořil'), 'created' => $this->translator->translate('Dne'), 'change_by' => $this->translator->translate('Změnil'), 'changed' => $this->translator->translate('Dne') );
	$this->formatColumns = array('created' => 'datetime', 'changed' => 'datetime');			
	//$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
	$this->DefSort = 'tag_name';
	$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový záznam'), 'class' => 'btn btn-primary'));
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
	if ($defData = $this->DataManager->findOneBy(array('id' => $id)))
	    $this['edit']->setValues($defData);
	//if ($copy)
	//    $this['edit']->setValues(array('id' => ''));
    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
            $form->addText('tag_name', $this->translator->translate('Název dotačního titulu'), 60, 60)
			->setRequired($this->translator->translate('Zadejte prosím název dodatčního titulu'))
			->setAttribute('placeholder',$this->translator->translate('Název dotačního titulu'));
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
	$this->flashMessage($this->translator->translate('Změny byly uloženy'), 'success');
	$this->redirect('default');
    }	    


}
