<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class InvoiceTypesPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\InvoiceTypesManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\NumberSeriesManager
    */
    public $NumberSeriesManager;           
    
   
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.InvoiceTypes']);
        $this->dataColumns = ['name' => [$this->translator->translate('Název_typu_dokladu'), 'size' => 15],
                        'cl_number_series.form_name' => [$this->translator->translate('Číselná_řada'), 'size' => 30],
                        'default_type' => ['format' => 'boolean',$this->translator->translate('Výchozí'),TRUE],
                        'inv_type' => [$this->translator->translate('Typ_dokladu'), 'size' => 20, 'arrValues' => $this->getInvoiceTypes()],
                        'form_use' => [$this->translator->translate('Použití'),FALSE,'function' => 'getStatusName'],
                        'users_list' => [$this->translator->translate('Uživatelé'), 'size' => 20, 'function' => 'getUsersCount',  'function_param' => ['users_list']],
                        'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],'create_by' => 'Vytvořil','changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
        $this->FilterC = 'UPPER(currency_name) LIKE ?';
        $this->filterColumns = [];
        $this->DefSort = 'name';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = [];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
	    parent::renderEdit($id,$copy,$modal);
        $defData = $this->DataManager->findOneBy(['id' => $id]);
        if ($defData) {
            $defDataNew = $defData->toArray();
            $arrUsersList = json_decode($defData['users_list'], true);

            if (count($arrUsersList) > 0)
                $defDataNew['users_list'] = json_decode($defData['users_list'], true);
            else
                unset($defDataNew['users_list']);

            $this['edit']->setValues($defDataNew);
        }

    }
    
    
    protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
       // $form->setTranslator(//$this->translator->setPrefix(['applicationModule.InvoiceTypes']));
	    $form->addHidden('id',NULL);
	    //->where('form_use = ? OR form_use = ?','invoice', 'invoice_arrived')
	    $arrNumberSeries = $this->NumberSeriesManager->findAll()->order('form_name')->fetchPairs('id','form_name');
	    $form->addSelect('cl_number_series_id', $this->translator->translate("Číselná_řada"),$arrNumberSeries)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_číslování'))
		    ->setHtmlAttribute('class','form-control chzn-select input-sm')
		    ->setPrompt($this->translator->translate('Zvolte_číslování'));
	    
	    $form->addSelect('inv_type', $this->translator->translate("Typ_dokladu"),$this->getInvoiceTypes())
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Typ_dokladu'))
		    ->setHtmlAttribute('class','form-control chzn-select input-sm')
		    ->setPrompt($this->translator->translate('Zvolte typ dokladu'));
        $form->addText('name', $this->translator->translate('Název_druhu_dokladu'), 0, 50)
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_druhu_dokladu'));
        $arrStatus_use = $this->getStatusAll();
        $form->addselect('form_use', $this->translator->translate('Použití'),$arrStatus_use)
            ->setPrompt($this->translator->translate('Zvolte_použití'))
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_použití'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Použití'));

	    $form->addCheckbox('default_type', $this->translator->translate('Výchozí_druh_dokladu'))
		 ->setHtmlAttribute('class', 'items-show');

        $arrUsers = [];
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id','name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id','name');

        $form->addMultiSelect('users_list', $this->translator->translate("Seznam_uživatelů"),$arrUsers)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_uživatele'))
            ->setHtmlAttribute('class','chzn-select');

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
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
        if ($form['send']->isSubmittedBy())
        {
            $data['users_list'] = json_encode($data['users_list']);
            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
        $this->redirect('default');
    }	    

    public function getUsersCount($arr)
    {
       // bdump($arr);
        $total = count(json_decode($arr['users_list'], true));

        return $total;
    }


}
