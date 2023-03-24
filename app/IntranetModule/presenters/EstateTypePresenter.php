<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class EstateTypePresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\EstateTypeManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\EstateTypeParamManager
     */
    public $EstateTypeParamManager;

    /**
     * @inject
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;



    protected function createComponentEstateTypeParam()
    {
        $arrData = array(
            'name' => array('Název parametru','format' => 'text', 'size' => 30),
            'param_type' => array('Typ parametru','format' => 'text', 'size' => 30, 'values' => $this->ArraysIntranetManager->getParamTypes()),
            'param_def_val' => array('Výchozí hodnota','format' => 'text', 'size' => 30)
        );
        return new Controls\ListgridControl(
            $this->translator,
            $this->EstateTypeParamManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array(), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array(), //custom links
            TRUE, //movableRow
            FALSE, //orderColumn
            FALSE, //selectMode
            array(), //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '', //txtSEarchcondition
             array(),
            FALSE, //forceEnable
            TRUE //paginator off
        );
    }

    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_estate_type_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }



    protected function startup()
    {
        parent::startup();
        $this->dataColumns = array( 'type_name' => array('Typ majetku', 'size' => 30),
									'group_type' => array('Druh majetku', 'format' => 'text', 'size' => 20, 'arrValues' => $this->ArraysIntranetManager->getEstateGroup()),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');

        $this->filterColumns = array(	'type_name' => 'autocomplete');
        $this->userFilterEnabled = FALSE;
        $this->userFilter = array('personal_number', 'surname', 'name');

        $this->DefSort = 'type_name';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = array();
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));
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
        $form->addText('type_name', 'Název typu majetku:', 30, 60)
			    ->setHtmlAttribute('placeholder','typ majetku');
	
		$form->addSelect('group_type', 'Druh majetku:', $this->ArraysIntranetManager->getEstateGroup())
			->setHtmlAttribute('placeholder','druh');
        
        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'Zpět')
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
            $data = $this->removeFormat($data);

            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        //$this->redirect('default');
        $this->redrawControl('flash');
        $this->redrawControl('content');
    }


}
