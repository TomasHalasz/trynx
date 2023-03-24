<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class WorkplacesPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\WorkplacesManager
    */
    public $DataManager;    

 
    protected function startup()
    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.Workplaces']);
        $this->mainTableName = 'cl_workplaces';
		$this->dataColumns = array(
						'workplace_name' => $this->translator->translate('Název_pracoviště'),
						'remote_address' => $this->translator->translate('IP_adresa'),
						'remote_host' => $this->translator->translate('DNS_název'),
						'user_agent' => $this->translator->translate('Prohlížeč'),
						'last_activity' => array($this->translator->translate('Poslední_aktivita'), 'format' => 'datetime'),
						'disabled' => array($this->translator->translate('Zakázáno'), 'format' => 'boolean'),
						'trynx_id' => array($this->translator->translate('ID_zařízení'), 'format' => 'text'),
						'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
						'create_by' => $this->translator->translate('Vytvořil'),
						'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),
						'change_by' => $this->translator->translate('Změnil'));
		$this->FilterC = '';
		$this->DefSort = 'workplace_name';
		$this->defValues = array();
		$this->userFilterEnabled = TRUE;
		$this->userFilter = array('workplace_name', 'remote_address', 'user_agent');
		$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'));
		//$this->toolbar = array();
		$this->rowFunctions = array('copy' => 'disabled');
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
		$form->addText('workplace_name', $this->translator->translate('Název_pracoviště'), 20, 60)
					->setHtmlAttribute('placeholder',$this->translator->translate('název_pracoviště'));
		$form->addText('remote_address', $this->translator->translate('IP_adresa'), 47, 47)
					->setHtmlAttribute('readonly','readonly');
		$form->addText('remote_host', $this->translator->translate('DNS_název'), 50, 60)
					->setHtmlAttribute('readonly','readonly');
		$form->addText('user_agent', $this->translator->translate('Prohlížeč'), 60, 128)
					->setHtmlAttribute('readonly','readonly');
		$form->addText('last_activity', $this->translator->translate('Poslední_aktivita'), 15, 15)
					->setHtmlAttribute('readonly','readonly');
		$form->addText('trynx_id', $this->translator->translate('ID_zařízení'), 60, 128)
					->setHtmlAttribute('placeholder',$this->translator->translate('ID_zařízení'));
		
	    $form->addCheckbox('disabled', $this->translator->translate('Pracoviště_zakázáno'))
					->setHtmlAttribute('class', 'items-show');
		   
	    
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
			$data = $this->removeFormat($data);
			if (!empty($data->id))
				$this->DataManager->update($data, TRUE);
			else
				$this->DataManager->insert($data);
		}
		$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
		$this->redirect('default');
    }	    


}
