<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class EstateMovesPresenter extends \App\Presenters\BaseListPresenter {


    PUBLIC $createDocShow = FALSE;

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

    public 	$filterStaffUsed = array();


    /**
    * @inject
    * @var \App\Model\EstateMovesManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\EstateParamManager
     */
    public $EstateParamManager;

    /**
     * @inject
     * @var \App\Model\EstateTypeManager
     */
    public $EstateTypeManager;

    /**
     * @inject
     * @var \App\Model\EstateReservationManager
     */
    public $EstateReservationManager;

    /**
     * @inject
     * @var \App\Model\RentalEstateManager
     */
    public $RentalEstateManager;

    /**
     * @inject
     * @var \App\Model\EstateStaffManager
     */
    public $EstateStaffManager;

    /**
     * @inject
     * @var \App\Model\EstateMovesManager
     */
    public $EstateMovesManager;

    /**
     * @inject
     * @var \App\Model\EstateDiaryManager
     */
    public $EstateDiaryManager;


    /**
     * @inject
     * @var \App\Model\StaffManager
     */
    public $StaffManager;


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

    /**
     * @inject
     * @var \App\Model\PlacesManager
     */
    public $PlacesManager;

    /**
     * @inject
     * @var \App\Model\StaffRoleManager
     */
    public $StaffRoleManager;



    protected function startup()
    {
        parent::startup();
        $this->formName = 'Pohyby majetku';
        $this->mainTableName = 'in_estate_moves';
        $this->dataColumns = ['move_date' => ['Datum pohybu', 'size' => 20,'format' => 'date'],
                                    'in_estate.est_number' => ['Číslo majetku', 'format' => 'text', 'size' => 10],
                                    'est_name' => ['Název', 'format' => 'text','size' => 30],
                                    'host_name' => ['Síťový název','format' => 'text', 'size' => 20],
                                    'in_places.place_name' => ['Umístění','format' => 'text', 'size' => 20],
                                    'center_name' => ['Středisko', 'format' => 'text', 'size' => 20],
                                    'move_type' => ['Pohyb', 'format' => 'text', 'size' => 10, 'arrValues' => $this->ArraysIntranetManager->getMoveTypes()],
                                    'note' => ['Poznámka', 'format' => 'text', 'size' => 10],
                                    'in_estate.s_number' => ['Výr.číslo', 'format' => 'text','size' => 20],
                                    'in_estate.in_estate_type.type_name' => ['Typ', 'format' => 'text', 'size' => 20],
                                    'created' => ['Vytvořeno','format' => 'datetime'],
                                    'create_by' => ['Vytvořil', 'format' => 'text'],
                                    'changed' => ['Změněno','format' => 'datetime'],
                                    'change_by' => ['Změnil', 'format' => 'text']];

        $this->filterColumns = ['move_date' => 'range', 'in_estate.est_number' => 'autocomplete' , 'est_name' => 'autocomplete','in_estate.in_estate_type.type_name' => 'autocomplete', 'note' => 'autocomplete',
                                      'in_estate.s_number' => 'autocomplete', 'in_places.place_name' => 'autocomplete', 'center_name' => 'autocomplete', 'host_name' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['est_number', 'in_estate_moves.est_name', 'in_estate_moves.center_name', 'in_estate_moves.host_name', 'in_places.place_name', 's_number', 'in_estate.in_estate_type.type_name'];

        $this->DefSort = 'move_date DESC';

        $arrUserPlaces = $this->UserManager->getUserPlaces($this->user->getIdentity());;
        if (is_array($arrUserPlaces) && count($arrUserPlaces) > 0) {
            $this->mainFilter = 'in_estate_moves.in_places_id IN (' . implode(',', $arrUserPlaces) . ')';
        }

        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
/*        $tmpEstateType = $this->EstateTypeManager->findAll()->limit(1)->fetch();
        if ($tmpEstateType) {
            $this->defValues = array('in_estate_type_id' => $tmpEstateType->id);
        }*/
        $this->rowFunctions = ['copy' => 'disabled', 'edit' => 'disabled'];
        $this->toolbar = [
        ];



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
        /*$form->addText('est_number', 'Číslo majetku:', 20, 20)
                ->setHtmlAttribute('readonly', TRUE )
			    ->setHtmlAttribute('placeholder','číslo majetku');

        $form->addText('est_name', 'Název majetku:', 30, 60)
            ->setHtmlAttribute('readonly', TRUE )
            ->setHtmlAttribute('placeholder','název majetku');

        $form->addText('s_number', 'Sériové číslo:', 30, 30)
            ->setHtmlAttribute('readonly', TRUE )
            ->setHtmlAttribute('placeholder','Sériové číslo');


        $form->addSelect('in_estate_type_id', 'Typ', $this->EstateTypeManager->findAll()->order('type_name')->fetchPairs('id','type_name'))
                ->setHtmlAttribute('readonly', TRUE )
                ->setHtmlAttribute('placeholder','Název');


        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'estate')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', 'Stav', $arrStatus)
            ->setHtmlAttribute('readonly', TRUE )
            ->setHtmlAttribute('placeholder','Stav');

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_center_id', 'Středisko:', $arrCenter)
            ->setHtmlAttribute('readonly', TRUE )
            ->setHtmlAttribute('placeholder','Středisko');*/

        $arrPlaces = $this->PlacesManager->findAll()->order('place_name')->fetchPairs('id', 'place_name');
        $form->addSelect('in_places_id', 'Místo', $arrPlaces)
            ->setHtmlAttribute('placeholder','Místo');

        $form->addText('move_date', 'Datum', 10, 10)
            ->setHtmlAttribute('placeholder','Datum');

        $arrMoveTypes = $this->ArraysIntranetManager->getMoveTypes();
        $form->addSelect('move_type', 'Druh',  $arrMoveTypes)
            ->setHtmlAttribute('placeholder','Druh');

        $form->addText('note', 'Poznámka', 30, 30)
            ->setHtmlAttribute('placeholder','Poznámka');

        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'Zpět')
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');
		$form->onSuccess[] = array($this,'SubmitEditSubmitted');
        //$form->onValidate[] = array($this, 'FormValidate');
        return $form;
    }


/*    public function FormValidate(Form $form)
    {
        $data=$form->values;
        if ($data['cl_partners_book_id'] == NULL)
        {
            bdump($data,'validation');
            $form->addError($this->translator->translate('Partner musí být vybrán'));

        }
        $this->redrawControl('content');

    }*/


    public function stepBack()
    {
	$this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
	    $data= $form->values;
        if ($form['send']->isSubmittedBy())
        {
            $tmpOldData = $this->DataManager->find($this->id);

            $data = $this->removeFormat($data);
            $this->DataManager->update($data);

        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        //$this->redirect('default');
        $this->redrawControl('content');
    }


    public function DataProcessListGrid($data)
    {
        unset($data['title']);
        return $data;
    }



}
