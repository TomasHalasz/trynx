<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class CenterPresenter extends \App\Presenters\BaseListPresenter {

  
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
    * @var \App\Model\CenterManager
    */
    public $DataManager;    

    
    protected function startup()
    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.Center']);
		$this->dataColumns = ['name' => $this->translator->translate('Název_střediska'),
                        'description' => $this->translator->translate('Popis'),
                        'short_desc' => $this->translator->translate('Zkratka_pro_export'),
                        'location' => $this->translator->translate('Místo'),
					    'email' => $this->translator->translate('Email'),
                        'default_center' => [$this->translator->translate('Výchozí_středisko'), 'format' => 'boolean'],
					    'public_event' => [$this->translator->translate('Externí_přístup'), 'format' => 'boolean'],
					    'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
					    'create_by' => $this->translator->translate('Vytvořil'),
					    'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],
					    'change_by' => $this->translator->translate('Změnil')];
		//$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
		$this->DefSort = 'name';
		//$this->relatedTable = '';
		//$this->dataColumnsRelated = 	array();
		//$this->mainFilter = '';			
		
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
	    $form->addHidden('id',NULL);
		
		$form->addText('name', $this->translator->translate('name'), 20, 20)
			->setRequired($this->translator->translate('nameRq'))
			->setHtmlAttribute('placeholder',$this->translator->translate('name'));
        $form->addText('description', $this->translator->translate('Popis'), 20, 60)
            ->setRequired($this->translator->translate('popisRq'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('popis'));

        $form->addText('short_desc', $this->translator->translate('Zkratka_pro_export'), 10, 10)
            ->setRequired($this->translator->translate('Zkratka'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Zkratka'));

        $form->addText('location', $this->translator->translate('location'), 30, 50)
            ->setHtmlAttribute('placeholder',$this->translator->translate('location'));

        $form->addCheckbox('default_center', $this->translator->translate('Výchozí_středisko'))
            ->setHtmlAttribute('class', '');
		
		$form->addText('email', $this->translator->translate('Email'), 30, 50)
			->setHtmlAttribute('placeholder',$this->translator->translate('Email'));
		
		$form->addSubmit('send', $this->translator->translate('save'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('back'))
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

        if ($form['send']->isSubmittedBy())
		{
			//dump($data->id);
			//die;
			if (!empty($data->id))
			$this->DataManager->update($data, TRUE);
			else
			$this->DataManager->insert($data);
		}
		$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
		$this->redirect('default');
    }	    
    
    
    public function handlePublicEvent($value,$name)
    {
		$arrData = array();
		$arrData['id'] = $this->id;
		$arrData['public_event'] = $value;
		$arrData['name'] = urldecode($name);
		if ($value == 1)
		{
			$newToken = '';
			while ($this->DataManager->findAllTotal()->where(array('public_event_token' => $newToken))->fetch() || $newToken == '')
			{
				$newToken = \Nette\Utils\Random::generate(16,'A-Za-z0-9');
			}
			$arrData['public_event_token'] = $newToken;
		}
		else
			$arrData['public_event_token'] = "";

		//Debugger::fireLog($arrData);
		$this->DataManager->update($arrData);	
		$this->redrawControl('publiceventlink');
    }
    

}
