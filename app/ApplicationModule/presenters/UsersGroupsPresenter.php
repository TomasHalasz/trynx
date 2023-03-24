<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class UsersGroupsPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\UsersGroupsManager
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
     * @var \App\Model\PlacesManager
     */
    public $PlacesManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;



    protected function startup()
    {
        parent::startup();
        $this->dataColumns = ['name' => ['Skupina uživatelů', 'size' => 30],
                                'places__' => ['Počet míst', 'size' => 5, 'format' => 'integer', 'function' => 'getPlaceCount', 'function_param' => ['id']],
                                'created' => ['Vytvořeno','format' => 'datetime'],'create_by' => 'Vytvořil','changed' => ['Změněno','format' => 'datetime'],'change_by' => 'Změnil'];

        $this->filterColumns = ['name' => 'autocomplete'];
        $this->userFilterEnabled = FALSE;
        $this->userFilter = ['name'];

        $this->DefSort = 'name';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = [];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary']];
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
        parent::renderEdit($id,$copy,$modal);
        $defData = $this->DataManager->findOneBy(['id' => $id]);
        if ($defData)
        {
            $defDataNew = $defData->toArray();
            $defDataNew['in_places_id'] = json_decode($defData['in_places_id'], true);
            $this['edit']->setValues($defDataNew);
        }
    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    $form->addHidden('id',NULL);
        $form->addText('name', 'Název skupiny uživatelů:', 30, 60)
			    ->setHtmlAttribute('placeholder','skupina uživatelů');

        $arrPlaces = $this->PlacesManager->findAll()->fetchPairs('id', 'place_name');
        $form->addMultiSelect('in_places_id', $this->translator->translate("Místa"),$arrPlaces)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_místa'))
            ->setHtmlAttribute('class','chzn-select');


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
            $data['in_places_id'] = json_encode($data['in_places_id']);

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

    public function getPlaceCount($arrData){
        if ($tmpData = $this->DataManager->find($arrData['id'])){
            $result = count(json_decode($tmpData['in_places_id'], true));
        }else{
            $result = '';
        }
        return $result;
    }

}
