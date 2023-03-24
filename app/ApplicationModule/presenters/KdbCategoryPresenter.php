<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class KdbCategoryPresenter extends \App\Presenters\BaseListPresenter {

  
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
    * @var \App\Model\KdbCategoryManager
    */
    public $DataManager;                         

    
    protected function startup()
    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.KdbCategory']);
        $this->dataColumns = array('name' => $this->translator->translate('Název_kategorie'),
									'cl_kdb_category.name' => $this->translator->translate('Nadřazená_kategorie'),
						'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));
		//$this->FilterC = 'UPPER(name) LIKE ? OR UPPER(acronym) LIKE ? ';
		$this->DefSort = 'name';
		$this->relatedTable = 'cl_kdb_category';
		$this->dataColumnsRelated = 	array(
						'name' => $this->translator->translate('Název_kategorie'),
						'cl_kdb_category.name' => $this->translator->translate('Nadřazená_kategorie'),
					   'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
					   'create_by' => $this->translator->translate('Vytvořil'),
					   'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),
					   'change_by' => $this->translator->translate('Změnil'));
		$this->mainFilter = 'cl_kdb_category.cl_kdb_category_id IS NULL';			
		
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
        $form->addHidden('access_key');
		$form->addText('name', $this->translator->translate('Název_kategorie'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_prosím_název_kategorie'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_kategorie'));
        $form->addCheckbox('public', $this->translator->translate('Veřejně_přístupné'));

		$arrCategory = $this->DataManager->findAll()->where('cl_kdb_category_id IS NULL AND name != ""')->
							select('name, id')->order('name')->fetchPairs('id','name');

		$form->addSelect('cl_kdb_category_id',$this->translator->translate('Nadřazená_kategorie'),$arrCategory)
			->setPrompt($this->translator->translate('Žádná'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Nadřazená_kategorie'));
		
		$form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
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

        if ($form['send']->isSubmittedBy()) {
            //dump($data->id);
            //die;
            if ($data['public'] == 0){
                $data['access_key'] = "";
            }elseif (empty($data->id) || ($data['public'] == 1 && $data['access_key'] == "")) {
                $data['access_key'] =  \Nette\Utils\Random::generate(64,'A-Za-z0-9');
            }

			if (!empty($data->id))
			    $this->DataManager->update($data, TRUE);
			else
			    $this->DataManager->insert($data);
		}
		$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
		$this->redirect('default');
    }	    

}
