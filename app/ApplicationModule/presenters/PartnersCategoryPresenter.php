<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class PartnersCategoryPresenter extends \App\Presenters\BaseListPresenter {

  
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
    * @var \App\Model\PartnersCategoryManager
    */
    public $DataManager;    

    
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.PartnersCategory']);
        $this->dataColumns = array('category_name' => $this->translator->translate('Název'),
                        'react_time' => $this->translator->translate('Reakční_čas_pro_helpdesk'),
                        'hour_tax' => array($this->translator->translate('Hodinová_sazba_lokálně'), 'format' => 'currency'),
                        'hour_tax_remote' => array($this->translator->translate('Hodinová_sazba_vzdáleně'), 'format' => 'currency'),
                        'deactive' => array($this->translator->translate('Nepoužívat'), 'format' => 'boolean'),
                        'def_cat' => array($this->translator->translate('Výchozí'), 'format' => 'boolean'),
                        'color_hex' => array($this->translator->translate('Stítek'),'format' => 'colortag'));
        //$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
        $this->DefSort = 'category_name';
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
        //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.PartnersCategory']));
      //  $form->setTranslator($this->translator);
	    $form->addHidden('id',NULL);
		$form->addText('category_name', $this->translator->translate('Stav'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_prosím_název_kategorie'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_kategorie'));
		$form->addText('hour_tax', $this->translator->translate('Hodinová_sazba_lokálně'), 20, 20)
			->setRequired(FALSE)
			->addRule(Form::INTEGER,$this->translator->translate('Hodinová_sazba_musí_být_číslo'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_kategorie'));
		$form->addText('hour_tax_remote', $this->translator->translate('Hodinová_sazba_vzdáleně'), 20, 20)
			->setRequired(FALSE)
			->addRule(Form::INTEGER,'Hodinová_sazba_musí_být_číslo.')
			->setHtmlAttribute('placeholder','Název_kategorie');
		$form->addText('react_time', $this->translator->translate('Reakční_čas_pro_helpdesk'), 2, 2  )
			->setRequired(FALSE)
			->addRule(Form::RANGE, 'Reakční_čas_musí_být_v_rozsahu_od_0_do_99_hodin.',
				    array(0,99))
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Čas'));
        $form->addCheckbox('deactive', $this->translator->translate('Neaktivní'));
        $form->addCheckbox('def_cat', $this->translator->translate('Výchozí'));

            $form->addHidden('color_hex');	    
	    
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
