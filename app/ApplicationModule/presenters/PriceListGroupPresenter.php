<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class PriceListGroupPresenter extends \App\Presenters\BaseListPresenter {

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
    * @var \App\Model\PriceListGroupManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\NumberSeriesManager
    */
    public $NumberSeriesManager;

    /**
     * @inject
     * @var \App\Model\PriceListCategoriesManager
     */
    public $PriceListCategoriesManager;

    protected function startup()
    {
	parent::startup();
        //$this->translator->setPrefix(['applicationModule.PriceListGroup']);
	$this->dataColumns = array( 'name' => array($this->translator->translate('Název'), 'size' => 15),
                    'cl_pricelist_group.name' => array($this->translator->translate('Nadřazená_skupina'), 'format' => 'text', 'size' => 10),
				    'cl_number_series.form_name' => array($this->translator->translate('Číselná_řada'), 'size' => 30),
                    'cl_pricelist_categories.name' => array($this->translator->translate('Kategorie'), 'format' => 'text', 'size' => 10),
				    'is_product' => array($this->translator->translate('Výrobek'),'format' => 'boolean'),
				    'is_component' => array($this->translator->translate('Materiál'),'format' => 'boolean'),
                    'is_return_package' => array($this->translator->translate('Vratný_obal'),'format' => 'boolean'),
                    'request_exp_date' => array($this->translator->translate('Vyžadovat_datum_expirace'),'format' => 'boolean'),
                    'request_batch' => array($this->translator->translate('Vyžadovat_šarži'),'format' => 'boolean'),
                    'b2b_show' => array($this->translator->translate('B2B_zobrazení'),'format' => 'boolean'),
				    'order_on_docs' =>array($this->translator->translate('Pořadí_na_dokladech'), 'format' => 'numeric'),
				    'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),
                     'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));
	$this->FilterC = 'UPPER(currency_name) LIKE ?';
	$this->filterColumns = array();
	$this->userFilterEnabled = TRUE;
	$this->userFilter = array('name', 'cl_number_series.form_name');

    $this->relatedTable = 'cl_pricelist_group';
    $this->dataColumnsRelated = array( 'name' => array($this->translator->translate('Název'), 'size' => 15),
        'cl_pricelist_group.name' => array($this->translator->translate('Nadřazená_skupina'), 'format' => 'text', 'size' => 10),
        'cl_number_series.form_name' => array($this->translator->translate('Číselná_řada'), 'size' => 30),
        'is_product' => array($this->translator->translate('Výrobek'),'format' => 'boolean'),
        'is_component' => array($this->translator->translate('Materiál'),'format' => 'boolean'),
        'is_return_package' => array($this->translator->translate('Vratný_obal'),'format' => 'boolean'),
        'request_exp_date' => array($this->translator->translate('Vyžadovat_datum_expirace'),'format' => 'boolean'),
        'request_batch' => array($this->translator->translate('Vyžadovat_šarži'),'format' => 'boolean'),
        'b2b_show' => array($this->translator->translate('B2B_zobrazení'),'format' => 'boolean'),
        'order_on_docs' =>array($this->translator->translate('Pořadí_na_dokladech'), 'format' => 'numeric'),
        'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),'create_by' => $this->translator->translate('Vytvořil'),
        'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),'change_by' => $this->translator->translate('Změnil'));

        $this->mainFilter = 'cl_pricelist_group.cl_pricelist_group_id IS NULL';
        $this->DefSort = 'name';
	//$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');	
	//$this->readOnly = array('identification' => TRUE);	
	//$settings = $this->CompaniesManager->getTable()->fetch();	
	$this->defValues = array();
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
	    $arrNumberSeries = $this->NumberSeriesManager->findAll()->where('form_use = ?','pricelist')->fetchPairs('id','form_name');
	    $form->addSelect('cl_number_series_id', $this->translator->translate("Číselná_řada:"),$arrNumberSeries)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_číslování'))
		    ->setHtmlAttribute('class','form-control chzn-select input-sm')
		    ->setPrompt($this->translator->translate('Zvolte_číslování'));
        $form->addText('name', $this->translator->translate('Název_skupiny'), 0, 20)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_skupiny'));
		$form->addText('order_on_docs', $this->translator->translate('Pořadí_na_dokladech'), 0, 2)
			->setHtmlAttribute('placeholder',$this->translator->translate('Pořadí_0_-_99'));
	    $form->addCheckbox('is_product',$this->translator->translate('Výrobek'));
	    $form->addCheckbox('is_component',$this->translator->translate('Materiál'));
        $form->addCheckbox('is_return_package',$this->translator->translate('Vratný_obal'));
        $form->addCheckbox('request_exp_date',$this->translator->translate('Vyžadovat_datum_expirace'));
        $form->addCheckbox('request_batch',$this->translator->translate('Vyžadovat_šarži'));
        $form->addCheckbox('b2b_show',$this->translator->translate('B2B_zobrazení'));
        //$arrGroups = $this->PriceListGroupManager->getGroupTree();
        $arrGroups = $this->DataManager->findAll()->where('cl_pricelist_group_id IS NULL')->fetchPairs('id','name');
        $form->addSelect('cl_pricelist_group_id', $this->translator->translate("Nadřazená_skupina"),$arrGroups)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_skupinu'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_skupinu'));

        $arrCategories = $this->PriceListCategoriesManager->findAll()->select('id, name')->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_pricelist_categories_id', $this->translator->translate("Kategorie"), $arrCategories)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_kategorii'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_kategorii'));

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
