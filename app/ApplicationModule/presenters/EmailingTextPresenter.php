<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class EmailingTextPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\EmailingTextManager
    */
    public $DataManager;    

 
    protected function startup()
    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.EmailingText']);
		$this->dataColumns = ['email_name' => $this->translator->translate('Název_šablony'),
									'email_use' => [$this->translator->translate('Použití'),FALSE,'function' => 'getStatusName'],
									'email_subject' => $this->translator->translate('Předmět_emailu'),
                                    'active' => [$this->translator->translate('Aktivní'), 'format' => 'boolean'],
									'attach_pdf' => [$this->translator->translate('Vkládat_PDF'), 'format' => 'boolean'],
                                    'attach_files' => [$this->translator->translate('Vkládat_soubory'), 'format' => 'boolean'],
                                    'attach_csv_h' => [$this->translator->translate('Vkládat_CSV_dokladu'), 'format'  => 'boolean'],
                                    'attach_csv_i' => [$this->translator->translate('Vkládat_CSV_položek'), 'format'  => 'boolean'],
									'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
									'create_by' => $this->translator->translate('Vytvořil'),
									'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],
									'change_by' => $this->translator->translate('Změnil')];
		$this->FilterC = ' ';
		$this->DefSort = 'email_name';
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
        //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.EmailingText']));
	    $form->addHidden('id',NULL);
        $form->addText('email_name', $this->translator->translate('Název_šablony'), 60, 60)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_šablony'));
	    $arrStatus_use = $this->getStatusAll();
        $form->addselect('email_use', $this->translator->translate('Použití'),$arrStatus_use)
			->setPrompt($this->translator->translate('Zvolte_použití'))
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_použití'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Použití'));

        $form->addText('email_subject', $this->translator->translate('Předmět_emailu'), 40, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Předmět_emailu'));
        $form->addCheckbox('active', $this->translator->translate('Aktivní'))
            ->setHtmlAttribute('class', '');
	    $form->addCheckbox('attach_pdf', $this->translator->translate('Vkládat_PDF'))
			    ->setHtmlAttribute('class', '');
        $form->addCheckbox('attach_files', $this->translator->translate('Vkládat_soubory'))
            ->setHtmlAttribute('class', '');
        $form->addCheckbox('attach_csv_h', $this->translator->translate('Vkládat_CSV_dokladu'))
            ->setHtmlAttribute('class', '');
        $form->addCheckbox('attach_csv_i', $this->translator->translate('Vkládat_CSV_položek'))
            ->setHtmlAttribute('class', '');
	    $form->addTextArea('email_body', $this->translator->translate('Tělo_emailu'), 50,8);
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
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
	
	public function beforeDeleteBaseList($lineId) {
		//dump($lineId);
		//die;
		///check if email template is used in company settings
		if ($this->settings->hd1_emailing_text_id == $lineId)
			return FALSE;
		if ($this->settings->hd2_emailing_text_id == $lineId)
			return FALSE;		
		if ($this->settings->hd3_emailing_text_id == $lineId)
			return FALSE;		
		if ($this->settings->hd4_emailing_text_id == $lineId)
			return FALSE;		
		if ($this->settings->hd5_emailing_text_id == $lineId)
			return FALSE;		
		if ($this->settings->hd6_emailing_text_id == $lineId)
			return FALSE;		
		
	}


}
