<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class EshopsPresenter extends \App\Presenters\BaseListPresenter {

  
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
    * @var \App\Model\EshopsManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    protected function startup()
    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.Eshops']);
        $arrEshopNames = $this->ArraysManager->getEshopType();
		$this->dataColumns = array('name' => [$this->translator->translate('Název_eshopu'), 'format' => 'text'],
                        'url' => $this->translator->translate('Url'),
                        'local_file' => [$this->translator->translate('Lokální_soubor'), 'format' => 'boolean'],
                        'eshop_type' => [$this->translator->translate('Typ'), 'format' => 'text', 'arrValues' => $arrEshopNames],
                        'sync_activ' => [$this->translator->translate('Active'), 'format' => 'boolean'],
                        'sync_at_login' => [$this->translator->translate('Sync_at_login'), 'format' => 'boolean'],
                        'not_update_pricelist' => [$this->translator->translate('Neaktualizovat_ceník'), 'format' => 'boolean'],
                        'token' => $this->translator->translate('Token'),
					    'import_script' => $this->translator->translate('Import_script'),
                        'confirm_script' => $this->translator->translate('Confirm_script'),
                        'confirm_disabled' => [$this->translator->translate('Potvrzení_zakázáno'), 'format' => 'boolean'],
					    'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
					    'create_by' => $this->translator->translate('Vytvořil'),
					    'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],
					    'change_by' => $this->translator->translate('Změnil'));
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
		
		$form->addText('name', $this->translator->translate('Název_eshopu'), 20, 60)
			->setRequired($this->translator->translate('popisNázev_eshopu'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_eshopu'));

        $form->addCheckbox('local_file', $this->translator->translate('Lokální_soubor'));

        $form->addCheckbox('not_update_pricelist', $this->translator->translate('Neaktualizovat_ceník'));

        $form->addText('url', $this->translator->translate('Url'), 100, 200)
            ->setHtmlAttribute('placeholder',$this->translator->translate('popisURL'))
            ->addConditionOn($form['local_file'],$form::NOT_EQUAL, 1)
            ->setRequired($this->translator->translate('popisURL'));

        $form->addText('token', $this->translator->translate('Token'), 30, 200)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Token'));

		$form->addText('import_script', $this->translator->translate('Import_script'), 30, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('Import_script'));

        $form->addText('confirm_script', $this->translator->translate('Confirm_script'), 30, 200)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Confirm_script'));

        $form->addCheckbox('sync_at_login', $this->translator->translate('Sync_at_login'));
        $form->addCheckbox('sync_activ', $this->translator->translate('Active'));
        $form->addCheckbox('confirm_disabled', $this->translator->translate('Potvrzení_zakázáno'));
        $form->addCheckbox('cm_number_to_invoice', $this->translator->translate('Číslo_zakázky_přenášet_do_faktury'));

        $arrEshopNames = $this->ArraysManager->getEshopType();
        $form->addSelect('eshop_type',  $this->translator->translate('Typ'), $arrEshopNames)
                ->setRequired($this->translator->translate('popisTyp'))
                ->setHtmlAttribute('placeholder', $this->translator->translate('Typ'));

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
		$this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
		$this->redirect('default');
    }	    

    

}
