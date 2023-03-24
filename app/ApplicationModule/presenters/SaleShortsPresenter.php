<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class SaleShortsPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\SaleShortsManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $PriceListManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchManager
     */
    public $CompanyBranchManager;


    protected function startup()
    {
	parent::startup();
	//$this->translator->setPrefix(['applicationModule.SaleShorts']);
	$this->dataColumns = array( 'cl_company_branch.name'    => $this->translator->translate('Firemní_pobočka'),
                                'cl_sale_shorts.name'    => $this->translator->translate('Nadřazená_skupina'),
                                'name'		    => array($this->translator->translate('Název_rychlé_volby'), 'format' => 'text'),
                                'color_hex'		    => array($this->translator->translate('Stítek'),'format' => 'colortag'),
                                'cl_pricelist.identification'   => array($this->translator->translate('Kód_z_ceníku'), 'format' => 'text'),
                                'cl_pricelist.item_label'	    => array($this->translator->translate('Název_z_ceníku'), 'format' => 'text'),
                                'created'		    => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
                                'create_by'		    => array($this->translator->translate('Vytvořil'), 'format' => 'text'),
                                'changed'		    => array($this->translator->translate('Změněno'),'format' => 'datetime'),
                                'change_by'		    => array($this->translator->translate('Změnil'), 'format' => 'text'));
	$this->relatedTable = 'cl_sale_shorts';
	$this->dataColumnsRelated = $this->dataColumns;
	
	$this->mainFilter = 'cl_sale_shorts.cl_sale_shorts_id IS NULL';		
	
	$this->FilterC = ' ';
	$this->DefSort = 'cl_company_branch.name, cl_sale_shorts.name, cl_sale_shorts.id';
	$this->defValues = array('cl_company_branch_id' => $this->user->getIdentity()->cl_company_branch_id);
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
        $form->addText('name', $this->translator->translate('Název_rychlé_volby'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_název_rychlé_volby'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_rychlé_volby'));
        $form->addHidden('color_hex');
	    
	    $arrPriceList = $this->PriceListManager->findAll()->select('id, CONCAT(identification," ",item_label) AS item')->order('identification')->fetchPairs('id', 'item');
	    //dump($arrCountries);
	    //die;
	    $form->addselect('cl_pricelist_id', $this->translator->translate('Položka_ceníku'),$arrPriceList)
		    ->setPrompt($this->translator->translate('Zvolte_položku_ceníku'))
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_položku_ceníku'))
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Položka_ceníku'));
	    
	    $arrSaleGroup = $this->DataManager->findAll()->where('cl_sale_shorts_id IS NULL AND name != ""')->
						    select('CONCAT(name) AS name,id')->order('name')->fetchPairs('id','name');

	    $form->addSelect('cl_sale_shorts_id',$this->translator->translate('Nadřazená_skupina'),$arrSaleGroup)
		    ->setPrompt($this->translator->translate('Žádný'))
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Nadřazená_skupina'));

        $arrBranch = $this->CompanyBranchManager->findAll()->fetchPairs('id','name');

        $form->addSelect('cl_company_branch_id',$this->translator->translate('Firemní_pobočka'),$arrBranch)
            ->setPrompt($this->translator->translate('Žádná'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Firemní_pobočka'));



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
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
	$this->redirect('default');
    }	    


}
