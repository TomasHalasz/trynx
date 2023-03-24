<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class PartnersGroupsPresenter extends \App\Presenters\BaseListPresenter {

  
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
    * @var \App\Model\PartnersGroupsManager
    */
    public $DataManager;    

    
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.partners']);
        $this->dataColumns = array('name' => $this->translator->translate('Název_skupiny'),
                            'description' => $this->translator->translate('Popis'),
                            'helpdesk_fund' => $this->translator->translate('Fond_helpdesku'),
                            'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil')
                            );
        //$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
        $this->DefSort = 'name';
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
		$form->addText('name', $this->translator->translate('Název_skupiny'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_prosím_název_skupiny'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_skupiny'));
        $form->addText('helpdesk_fund', $this->translator->translate('Fond_helpdesku'), 15, 15)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Zadejte_hodinový_fond_helpdesku'));
        $form->addTextArea('description', $this->translator->translate('Popis_skupiny'),40, 5 )
                ->setHtmlAttribute('placeholder',$this->translator->translate('Popis_skupiny'));
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
