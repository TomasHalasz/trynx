<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class EstatePresenter extends \App\Presenters\BaseListPresenter
{


    public $createDocShow = FALSE;

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

    public $filterStaffUsed = array();


    /**
     * @inject
     * @var \App\Model\EstateManager
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

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);

    }

    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);

    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy()) {
            $tmpOldData = $this->DataManager->find($this->id);
            $data = $this->removeFormat($data);
            $this->DataManager->updateEstate($tmpOldData->toArray(), $data);
            //18.08.2019 - fill type params
            if (($tmpOldData->related('in_estate_param')->count() == 0)) {
                $this->updateParam($data);
            }
        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        //$this->redirect('default');
        $this->redrawControl('content');
    }

    private function updateParam($data)
    {
        if (!is_null($data['in_estate_type_id'])) {
            $tmpTypeParam = $this->EstateTypeParamManager->findAll()->where('in_estate_type_id = ?', $data['in_estate_type_id']);
            foreach ($tmpTypeParam as $key => $one) {
                $arrPar = ['in_estate_type_param_id' => $key,
                    'in_estate_id' => $data['id'],
                    'param_value' => $one->param_def_val,
                    'item_order' => $one->item_order];
                $this->EstateParamManager->insert($arrPar);
            }
        }
    }

    public function handleEraseProp()
    {
        $this->EstateParamManager->findAll()->where('in_estate_id = ?', $this->id)->delete();
        $tmpData = $this->DataManager->find($this->id);
        $tmpData->update(['old_in_estate_type_id' => NULL]);
        $this->updateParam($tmpData);
        $this->redrawControl('content');
    }

    public function handleLeftProp()
    {
        $tmpData = $this->DataManager->find($this->id);
        $tmpData->update(['old_in_estate_type_id' => NULL]);
        $this->redrawControl('content');
    }

    public function handleCreateStaffSelectModalWindow()
    {
        $this->createDocShow = TRUE;
        $tmpEstateStaff = $this->EstateStaffManager->findAll()->where('in_estate_id = ?', $this->id)->fetchPairs('in_staff_id', 'in_staff_id');
        //bdump($tmpTrainingStaff );
        if (count($tmpEstateStaff) > 0) {
            $this->filterStaffUsed = ['filter' => 'id  NOT IN (' . implode(',', $tmpEstateStaff) . ')'];
        } else {
            $this->filterStaffUsed = [];
        }
        //bdump($this->filterStaffUsed );
        $this->showModal('createStaffSelectModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleInsertStaff($dataItemsSel, $dataItems)
    {
        $arrDataItems = json_decode($dataItems, true);
        $arrDataItemsSel = json_decode($dataItemsSel, true);
        $order = $this->EstateStaffManager->findAll()->where('in_estate_id = ?', $this->id)->max('item_order');
        if (is_null($order)) {
            $order = 1;
        }

        foreach ($arrDataItemsSel as $key => $one) {
            $arrInsert = [];
            $arrInsert['in_staff_id'] = $one;
            $arrInsert['item_order'] = $order;
            $arrInsert['in_estate_id'] = $this->id;
            $arrInsert['created'] = new \Nette\Utils\DateTime;
            $arrInsert['changed'] = new \Nette\Utils\DateTime;
            $this->EstateStaffManager->insert($arrInsert);
            $order++;
        }
        $this->redrawControl('staff');
        $this->redrawControl('contents');

    }

    public function getTitleName($arr)
    {
        //bdump($arr['in_staff.title']);
        return $this->ArraysIntranetManager->getTitleName($arr['in_staff.title']);
    }

    public function DataProcessListGrid($data)
    {
        unset($data['title']);
        return $data;
    }

    public function getRental($arrData)
    {
        $ret = '';
        if (!is_null($arrData['id'])) {
            $retVal = $this->RentalEstateManager->findAll()->where('in_estate_id = ? AND returned = 0', $arrData['id'])
                ->order('dtm_rent DESC')->limit(1)->fetch();
            if ($retVal) {
                if (!is_null($retVal['dtm_return'])) {
                    $strRet = $retVal['dtm_return']->format('d.m.Y');
                } else {
                    $strRet = "není";
                }
                $ret = $retVal->in_rental->in_staff['personal_number'] . ' ' . $retVal->in_rental->in_staff['surname'] . ' ' . $retVal->in_rental->in_staff['name'] . ' do: ' . $strRet;
            } else {
                $ret = '';
            }
        }
        return $ret;
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

    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator, $this->FilesManager, $this->UserManager, $this->id, 'in_estate_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    protected function createComponentEditTextDescription()
    {
        return new Controls\EditTextControl(
            $this->translator, $this->DataManager, $this->id, 'description_txt');
    }

    protected function createComponentEstateParam()
    {
        $tmpData = $this->DataManager->find($this->id);
        $arrTypes = [];
        if (!is_null($tmpData->in_estate_type_id)) {
            $typesParams = $this->EstateTypeParamManager->findAll()->select('id, name')->
            order('item_order,name')->
            where('in_estate_type_id = ?', $tmpData->in_estate_type_id)->
            fetchPairs('id', 'name');

            $usedParams = [];
            $oldTypeName = "";
            foreach ($tmpData->related('in_estate_param')->where('in_estate_type_param_id IS NOT NULL') as $key => $one) {
                if (!array_key_exists($key, $typesParams)) {
                    $usedParams[$one['in_estate_type_param_id']] = $one->in_estate_type_param['name'];
                }
                $oldTypeName = $one->in_estate_type_param->in_estate_type['type_name'];
            }
            $arrTypes[$tmpData->in_estate_type['type_name']] = $typesParams;
            $arrTypes[$oldTypeName] = $usedParams;

        }

        bdump($arrTypes);
        $arrData = [
            'in_estate_type_param.name' => ['Parametr', 'format' => 'chzn-select', 'size' => 20,
                'values' => $arrTypes,
                'roCondition' => '!is_null($defData["in_estate_type_param_id"])'],
            'param_value' => ['Hodnota', 'format' => 'text', 'size' => 40]
        ];
        return new Controls\ListgridControl(
            $this->translator,
            $this->EstateParamManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            TRUE, //movableRow
            'in_estate_param.item_order', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            'in_estate_type_param.name = ?', //txtSEarchcondition
            [], //toolbar
            FALSE, //forceEnable
            TRUE //paginator off
        );
    }

    protected function createComponentEstateStaff()
    {
        $arrStaff = [];
        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 0')->
        fetchPairs('id', 'fullname');

        $arrStaff['Neaktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 1')->
        fetchPairs('id', 'fullname');
        //bdump($arrStaff);
        $arrStaffRole = $this->StaffRoleManager->getStaffRoleTreeNotNested();
        $arrData = [
            'in_staff.surname' => ['Příjmení ', 'format' => 'chzn-select-req', 'size' => 20, 'values' => $arrStaff],
            'in_staff.name' => ['Jméno', 'format' => 'text', 'size' => 20, 'readonly' => TRUE],
            'title' => ['Titul', 'format' => 'text', 'function' => 'getTitleName', 'function_param' => ['in_staff.title'],
                'size' => 10, 'readonly' => TRUE],
            'in_staff.cl_center.name' => ['Středisko', 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
            'in_staff.cl_center.location' => ['Lokalita', 'format' => 'text', 'size' => 10, 'readonly' => TRUE],

            'in_staff.email' => ['Email', 'format' => 'text', 'size' => 30, 'readonly' => TRUE],
            'in_staff.phone' => ['Telefon', 'format' => 'text', 'size' => 30, 'readonly' => TRUE]
        ];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->EstateStaffManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'in_staff.surname', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            'in_staff.surname LIKE ? OR in_staff.name LIKE ? OR in_staff.cl_center.location LIKE ? OR in_staff.email LIKE ? OR in_staff.phone LIKE ?', //txtSEarchcondition
            [1 => ['url' => $this->link('createStaffSelectModalWindow!'), 'rightsFor' => 'write', 'label' => 'Hromadný výběr', 'class' => 'btn btn-primary',
                'data' => ['data-ajax="true"', 'data-history="false"']]], //toolbar
            FALSE, //forceEnable
            FALSE //paginator off
        );
        $control->setPageLength(20);
        $control->setContainerHeight('auto');
        return $control;
    }

    protected function createComponentListgridStaffSelect()
    {
        $arrData = ['surname' => ['Příjmení', 'format' => 'text', 'size' => 20],
            'name' => ['Jméno', 'format' => 'text', 'size' => 20],
            'title' => ['Titul', 'format' => 'text', 'size' => 5],
            'birth_date' => ['Datum narození', 'format' => 'date', 'size' => 10],
            'cl_center.location' => ['Lokalita', 'format' => 'text', 'size' => 10],
            'cl_center.name' => ['Středisko', 'format' => 'text', 'size' => 15]
        ];
        $now = new \Nette\Utils\DateTime;
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->StaffManager, //data manager
            $arrData, //data columns
            [], //row conditions
            NULL, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            FALSE,
            FALSE, //add empty row
            [], //custom links
            FALSE, //movable row
            'in_staff.surname', //ordercolumn
            TRUE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '', //txtSEarchcondition
            [],
            FALSE, //forceEnable
            TRUE //paginator off
        );


        return $control;

    }

    protected function createComponentEstateDiary()
    {

        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 0')->
        fetchPairs('id', 'fullname');

        $arrStaff['Neaktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 1')->
        fetchPairs('id', 'fullname');

        $arrEventTypes = $this->ArraysIntranetManager->getEventTypes();

        $arrData = [
            'date' => ['Datum a čas ', 'format' => 'datetime', 'size' => 12],
            'event_type' => ['Typ', 'format' => 'chzn-select-req', 'values' => $arrEventTypes,
                'size' => 15],
            'description_short' => ['Poznámka', 'format' => 'text', 'size' => 20],
            'in_staff.surname' => ['Obsluha', 'format' => 'chzn-select-req', 'size' => 10, 'values' => $arrStaff],
            'description' => ['Zápis', 'format' => 'textarea-formated', 'size' => 250, 'rows' => 3, 'newline' => TRUE]
        ];
        $tmpNow = new DateTime();
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->EstateDiaryManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            ['date' => $tmpNow], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'date DESC', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            'description LIKE ?', //txtSEarchcondition
            [], //toolbar
            FALSE, //forceEnable
            TRUE //paginator off
        );
        $control->setContainerHeight('auto');
        return $control;
    }

    protected function createComponentEstateReservation()
    {
        $arrStaff = [];
        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 0')->
        fetchPairs('id', 'fullname');

        $arrStaff['Neaktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 1')->
        fetchPairs('id', 'fullname');

        $arrCommission = [];
        $arrCommission['Aktivní'] = $this->CommissionManager->findAll()->
        select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
        where('delivery_date IS NULL OR delivery_date >= NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
        $arrCommission['Neaktivní'] = $this->CommissionManager->findAll()->
        select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
        where('delivery_date IS NULL OR delivery_date < NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');

        //$arrStaffRole = $this->StaffRoleManager->getStaffRoleTreeNotNested();
        $arrData = [
            'dtm_start' => ['Od', 'format' => 'datetime', 'size' => 10],
            'dtm_end' => ['Do', 'format' => 'datetime', 'size' => 10],
            'in_staff.surname' => ['Příjmení ', 'format' => 'chzn-select-req', 'size' => 10, 'values' => $arrStaff],
            'in_staff.name' => ['Jméno', 'format' => 'text', 'size' => 20, 'readonly' => TRUE],
            'cl_commission.cm_number' => ['Zakázka', 'format' => 'chzn-select-req', 'size' => 10, 'values' => $arrCommission],
            'description' => ['Poznámka', 'format' => 'textarea', 'size' => 80, 'rows' => 4, 'newline' => true],
        ];
        $tmpNow = new DateTime();
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->EstateReservationManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            ['dtm_start' => $tmpNow, 'dtm_end' => $tmpNow], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'dtm_start DESC', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '', //txtSEarchcondition
            [], //toolbar
            FALSE, //forceEnable
            TRUE //paginator off
        );
        $control->setContainerHeight('auto');

        return $control;
    }

    protected function createComponentEstateMoves()
    {

        $arrEstate = $this->DataManager->findAll()->select('CONCAT(in_estate.est_number," ",in_estate.est_name) AS fullname, in_estate.id AS id')->
        order('in_estate.est_name')->
        fetchPairs('id', 'fullname');

        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 0')->
        fetchPairs('id', 'fullname');

        $arrStaff['Neaktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
        order('cl_center.location,cl_center.name,in_staff.surname')->
        where('in_staff.end = 1')->
        fetchPairs('id', 'fullname');

        $arrEventTypes = $this->ArraysIntranetManager->getEventTypes();
        $arrMoveTypes = $this->ArraysIntranetManager->getMoveTypes();

        $arrPlaces = $this->PlacesManager->findAll()->order('place_name')->fetchPairs('id', 'place_name');
        $arrCenters = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');

        $arrData = [
            'move_date' => ['Datum pohybu', 'format' => 'date', 'size' => 10],
            'in_places.place_name' => ['Místo', 'format' => 'chzn-select-req', 'values' => $arrPlaces,
                'size' => 15],
            'cl_center.name' => ['Středisko', 'format' => 'chzn-select-req', 'values' => $arrCenters,
                'size' => 15],
            'move_type' => ['Pohyb', 'format' => 'chzn-select-req', 'size' => 10, 'values' => $arrMoveTypes],
            'note' => ['Poznámka', 'format' => 'text', 'size' => 15]
        ];
        $tmpNow = new DateTime();
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->EstateMovesManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            ['move_date' => $tmpNow], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'move_date DESC, item_order DESC', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            'note LIKE ? OR in_places.place_name LIKE ? OR in_estate_moves.create_by LIKE ?', //txtSEarchcondition
            [], //toolbar
            FALSE, //forceEnable
            TRUE //paginator off
        );
        $control->setContainerHeight('auto');
        $control->showHistory(true);
        $control->setHideTimestamps(false);
        return $control;
    }

    protected function startup()
    {
        parent::startup();
        $this->formName = 'Majetek';
        $this->mainTableName = 'in_estate';
        $this->dataColumns = ['est_number' => ['Číslo majetku', 'format' => 'text', 'size' => 10],
            'cl_status.status_name' => ['Stav', 'size' => 20, 'format' => 'colortag'],
            'est_name' => ['Název', 'format' => 'text', 'size' => 20],
            'dtm_purchase' => ['Datum pořízení', 'format' => 'date', 'size' => 10],
            'invoice' => ['Doklad', 'format' => 'text', 'size' => 10],
            's_number' => ['Výrobní číslo', 'format' => 'text', 'size' => 10],
            'est_price' => ['Nákupní cena', 'format' => 'currency', 'size' => 10],
            'rent_price' => ['Denní sazba', 'format' => 'currency', 'size' => 10],
            'producer' => ['Výrobce', 'format' => 'text', 'size' => 20],
            'est_description' => ['Popis', 'format' => 'text', 'size' => 20],
            'in_estate_type.type_name' => ['Typ', 'format' => 'text', 'size' => 20],
            'cl_center.name' => ['Středisko', 'format' => 'text', 'size' => 20],
            'cl_company_branch.name' => ['Pobočka', 'format' => 'text', 'size' => 20],
            'in_places.place_name' => ['Umístění', 'format' => 'text', 'size' => 20],
            'host_name' => ['Síťový název', 'format' => 'text', 'size' => 20],
            'net_address' => ['Síťová adresa', 'format' => 'text', 'size' => 20],
            'ip_address' => ['IP adresa', 'format' => 'text', 'size' => 20],
            'cl_center_id' => ['Zapůjčeno', 'format' => 'text', 'size' => 10, 'function' => 'getRental', 'function_param' => ['id']],
            'id' => ['interni', 'format' => 'hidden'],
            'created' => ['Vytvořeno', 'format' => 'datetime'],
            'create_by' => ['Vytvořil', 'format' => 'text'],
            'changed' => ['Změněno', 'format' => 'datetime'],
            'change_by' => ['Změnil', 'format' => 'text']];

        //'rental__' => ['Zapůjčeno', 'format' => 'text', 'size' => 10, 'function' => 'getRental', 'function_param' => ['id']],
        $this->filterColumns = ['est_number' => 'autocomplete', 'est_name' => 'autocomplete', 'est_description', 'cl_center.name' => 'autocomplete', 'in_estate_type.type_name' => 'autocomplete',
            'cl_company_branch.name' => 'autocomplete', 'cl_status.status_name' => 'autocomplete', 'invoice' => 'autocomplete', 's_number' => 'autocomplete', 'in_places.place_name' => 'autocomplete',
            'host_name' => 'autocomplete', 'net_address' => 'autocomplete', 'ip_address' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['est_number', 'est_name', 'in_places.place_name', 's_number', 'invoice', 'host_name', 'ip_address'];

        $this->DefSort = 'est_number';

        $arrUserPlaces = $this->UserManager->getUserPlaces($this->user->getIdentity());;
        if (is_array($arrUserPlaces) && count($arrUserPlaces) > 0) {
            $this->mainFilter = 'in_estate.in_places_id IN (' . implode(',', $arrUserPlaces) . ')';
        }

        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $tmpEstateType = $this->EstateTypeManager->findAll()->limit(1)->fetch();
        if ($tmpEstateType) {
            $this->defValues = ['in_estate_type_id' => $tmpEstateType->id];
        }
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'],
            2 => ['url' => $this->link(':Application:ImportData:', ['modal' => TRUE, 'target' => $this->name]), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary modalClick',
                'data' => ['data-href', 'data-history="false"',
                    'data-title = "Import CSV"']],
        ];
        $this->bscOff = FALSE;
        $this->bscEnabled = FALSE;


    }

    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        $form->addText('est_number', 'Číslo majetku:', 20, 20)
            ->setHtmlAttribute('placeholder', 'číslo majetku');

        $form->addText('est_name', 'Název majetku:', 30, 60)
            ->setHtmlAttribute('placeholder', 'název majetku');

        $form->addText('s_number', 'Sériové číslo:', 30, 30)
            ->setHtmlAttribute('placeholder', 'Sériové číslo');
        $form->addText('producer', 'Výrobce:', 30, 30)
            ->setHtmlAttribute('placeholder', 'Výrobce');

        $form->addText('dtm_purchase', 'Datum nákupu:', 10, 10)
            ->setHtmlAttribute('placeholder', 'Datum nákupu');

        $form->addText('est_price', 'Nákupní cena:', 10, 10)
            ->setHtmlAttribute('placeholder', 'Nákupní cena');

        $form->addText('rent_price', 'Denní sazba:', 10, 10)
            ->setHtmlAttribute('placeholder', 'Denní sazba');

        $form->addText('invoice', 'Nákupní doklad:', 30, 30)
            ->setHtmlAttribute('placeholder', 'Nákupní doklad');

        $form->addText('host_name', 'Síťové jméno:', 30, 60)
            ->setHtmlAttribute('placeholder', 'Síťové jméno');

        $form->addText('net_address', 'Síťová adresa:', 30, 30)
            ->setHtmlAttribute('placeholder', 'Síťová adresa');

        $form->addText('ip_address', 'IP adresa:', 30, 30)
            ->setHtmlAttribute('placeholder', 'IP adresa');

        $form->addTextArea('est_description', 'Popis:', 20, 5)
            ->setHtmlAttribute('placeholder', 'popis majetku');

        $form->addSelect('in_estate_type_id', 'Typ', $this->EstateTypeManager->findAll()->order('type_name')->fetchPairs('id', 'type_name'))
            ->setHtmlAttribute('placeholder', 'Název');


        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'estate')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', 'Stav', $arrStatus)
            ->setHtmlAttribute('placeholder', 'Stav');

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', 'Středisko:', $arrCenter)
            ->setHtmlAttribute('placeholder', 'Středisko');

        $arrCenter = $this->PlacesManager->findAll()->order('place_name')->fetchPairs('id', 'place_name');
        $form->addSelect('in_places_id', 'Místo:', $arrCenter)
            ->setHtmlAttribute('placeholder', 'Místo');

        $form->addTextArea('description_txt', 'Poznámka:', 30, 8)
            ->setHtmlAttribute('placeholder', 'Poznámka');

        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class', 'btn btn-success');
        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class', 'btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBack'];
        $form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
        //$form->onValidate[] = array($this, 'FormValidate');
        return $form;
    }

}
