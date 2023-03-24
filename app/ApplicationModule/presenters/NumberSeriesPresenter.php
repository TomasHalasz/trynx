<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class NumberSeriesPresenter extends \App\Presenters\BaseListPresenter {

    
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
    * @var \App\Model\NumberSeriesManager
    */
    public $DataManager;    

 
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.NumberSeries']);
        $this->dataColumns = ['form_name' => $this->translator->translate('Název'),
                       'form_use' => [$this->translator->translate('Použití'),FALSE,'function' => 'getStatusName'],
                       'formula' => $this->translator->translate('Vzorec'),
                       'last_number' => $this->translator->translate('Poslední_číslo'),
                        'stereo_md' => $this->translator->translate('MD_Stereo'),
                        'stereo_dal' => $this->translator->translate('Dal_Stereo'),
                        'stereo_number' => $this->translator->translate('Číslování_Stereo'),
                        'last_use' => ['format' => 'date', $this->translator->translate('Poslední_použití')],
                       'form_default' => ['format' => 'boolean',$this->translator->translate('Výchozí'),TRUE]];
        $this->FilterC = ' ';
        $this->filterColumns = ['form_name' => 'autocomplete' , 'form_use' => 'autocomplete', 'formula' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['form_name', 'form_use'];
        $this->DefSort = 'form_use';
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
	    //$form->setMethod('POST');
        //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.NumberSeries']));
	    $form->addHidden('id',NULL);
	    $form->addText('form_name', $this->translator->translate('Název'), 30, 30)
		    ->setRequired($this->translator->translate('Zadejte_prosím_název_číselné_řady'))
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Název_číselné_řady'));
	    $form->addText('formula', $this->translator->translate('Vzorec'), 20, 20)
		    ->setRequired($this->translator->translate('Vzorec_pro_vytvoření_čísla_dokladu'))
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Vzorec_pro_vytvoření_čísla_dokladu'));
	    $form->addText('last_number', $this->translator->translate('Poslední_pořadové_číslo'), 20, 20)
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Poslední_pořadové_číslo'));
        $form->addText('stereo_md', $this->translator->translate('MD_Stereo'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('MD_Stereo'));
        $form->addText('stereo_dal', $this->translator->translate('Dal_Stereo'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Dal_Stereo'));
        $form->addText('stereo_number', $this->translator->translate('Číslování_Stereo'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Číslování_Stereo'));
	    $form->addCheckbox('form_default', $this->translator->translate('Výchozí'))
			 ->setHtmlAttribute('class', 'items-show');		    
	    $arrStatus_use = $this->getStatusAll();
	    $form->addselect('form_use', $this->translator->translate('Použití'),$arrStatus_use)
		    ->setPrompt($this->translator->translate('Zvolte_použití'))
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_použití'))
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Použití'));

	    $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		->setHtmlAttribute('class','btn btn-warning')
		->setValidationScope([])
		->onClick[] = [$this, 'stepBack'];
	    $form->onSuccess[] = [$this,'SubmitEditSubmitted'];
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
