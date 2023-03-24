<?php

namespace App\Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Netpromotion\Profiler\Profiler;
use Nette,
	App\Model;
use Tracy\Debugger;
use Exception;

class ListgridControl extends Control
{


    private $dataColumns,$dataSource,$conditionRows,$editIdLine = NULL,$lastIdLine,$parentId,$defaultData,$EnableAddEmptyRow,$customUrl,$movableRow,$orderColumn, $selectMode, $quickSearch, $fontSize,
		$priceListBottom, $readonly, $nodelete, $enableSearch, $txtSearchCondition, $toolbar, $forceEnable, $paginatorOff, $colours, $pagelength, $newLine = FALSE, $containerHeight, $select2FocusEnabled = true;
    private $priceListButtonEnabled = false;
    private $findTotalEnabled = false;
    private $searchPlaceholder = 'Hledaný text v dokladu';

    private $childRelation = NULL;
    public $showHistory = true;

    private $newLineName = 'Řádek';
    private $newLineTitle = 'vloží_nový_řádek';

    public $filter = [];
    public $show_type = [];

    /** @persistent */
    public $page_lg = 1;

    /** @persistent */
    public $txtSearch = "";

    /** @var \App\Model\Base*/
    private $DataManager;

    /** @var \App\Model\Base*/
    private $ParentManager;
    
    /** @var \App\Model\Base*/
    private $PriceListManager;        
    
    /** @var \App\Model\Base*/
    private $PriceListPartnerManager;            

    /** @var \App\Model\Base*/
    private $saleShortsManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    public $onChange = [];
    
    public $onPrint = [];

    public $onCustomFunction = [];

    public $onNewSubTask = [];

    private $parentColumnName = FALSE;


    private $parentColumnName2 = FALSE, $parentColumnValue2 = NULL;
    private $hideTimestamps = TRUE;

// @param type $dataSource - DB result with data

    /**
     * 
     * @param type $DataManager - model manager for showed data
     * @param array $dataColumns - array(columnName,array(Name,Format))
     * @param array $conditionRows
     * @param type $parentId - parent ID for constraints
     * @param array $defaultData- default data for new record
     * @param type $ParentManager - parent data model manager* 
     * @param type $PriceListManager - pricelist model manager* 
     * @param type $PriceListPartnerManager - pricelistpartner model manager* * 
     * @param boolean $EnableAddEmptyRow - TRUE/FALSE enable add empty row without selecting from pricelist*
     * @param array $customUrl - array of urls in presenter which are used from component*
     * @param boolean $movableRow - FALSE to switchOff moving rows*
     * @param boolean $orderColumn - if movableRow is FALSE use this property for order set*
     * @param boolean $selectMode - TRUE to switch to readonly mode and show checkbox for selecting items*
     * @param array $quickSearch - array with setting for quicksearch*
     * @param type $parentColumnName - FALSE
     * @param boolean $priceListBottom - TRUE for place pricelist search toolbar to the bottom of component
     * @param boolean $readonly - TRUE for readonly mode
     * @param boolean $nodelete - TRUE if we want force disable delete button
     * @param string $enableSearch - TRUE if we want show search input
     * @param boolean $txtSearchCondition - search condition for use in WHERE
     * @param array $toolbar - array with toolbar or NULL when none. The same toolbar array like in baselist
     * @param boolean $forceEnable - set TRUE enables edit when others readonly attributes are TRUE. It has priority before forceRO
     * @param boolean $paginatorOff - set TRUE disables paginator, default is FALSE
     * @param array $colours - array of conditions and colours for rows
     * @param int $pagelength - number of items on one page if paginator is on. Default is 20
	 * @param str $containerHeight - height of container.  Default is '470px'
     * @param array $show_type - [key_number] => name for display on line add button
     */
    public function __construct( Nette\Localization\Translator $translator, $DataManager,$dataColumns,$conditionRows,$parentId,
					$defaultData,$ParentManager,$PriceListManager,$PriceListPartnerManager,$EnableAddEmptyRow = TRUE,$customUrl = array(),
					$movableRow = TRUE, $orderColumn = NULL, $selectMode = FALSE, $quickSearch = array(), $fontSize = "", $parentColumnName = FALSE,
					$priceListBottom = FALSE, $readonly = FALSE, $nodelete = FALSE, $enableSearch = FALSE, $txtSearchCondition = '', $toolbar = NULL, $forceEnable = FALSE,
                    $paginatorOff = FALSE, $colours = array(), $pagelength = 20, $containerHeight = '470px' )
    {
        //profiler::start();
        //bdump($this->dataColumns, 'construct listgrid');
        //parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->translator  = $translator;
        $this->dataColumns = $dataColumns;
        //dump($ParentManager->tableName);
        //die;
        //dump($parentId);
        $this->movableRow = $movableRow;
        $this->orderColumn = $orderColumn;
        if ($parentColumnName != FALSE){
            $this->parentColumnName = $parentColumnName;
        }else{
            $this->parentColumnName = $ParentManager->tableName.'_id';
        }
        //24.02.2019 - changed from findAll() to findAllTotal() - because we have to work with not own records in privatemode
        if (!is_null($parentId)) {
            $this->dataSource = $DataManager->findAllTotal()->where([$this->parentColumnName => $parentId]);
        }else{
            $this->dataSource = $DataManager->findAll();
        }

        $this->setOrder($orderColumn);

        $this->txtSearchCondition = $txtSearchCondition;

            //$dataSource->order('item_order');

        $this->conditionRows=$conditionRows;
        $this->DataManager = $DataManager;
        $this->parentId = $parentId;
        $this->defaultData = $defaultData;
        $this->ParentManager = $ParentManager;
        $this->PriceListManager = $PriceListManager;
        $this->PriceListPartnerManager = $PriceListPartnerManager;
        $this->EnableAddEmptyRow = $EnableAddEmptyRow;
        $this->customUrl = $customUrl;
        $this->selectMode = $selectMode;
        $this->quickSearch = $quickSearch;
        //bdump($quickSearch,$DataManager->tableName);
        $this->fontSize = $fontSize;
        $this->priceListBottom = $priceListBottom;
        $this->readonly = $readonly;
        $this->nodelete = $nodelete;
        $this->enableSearch = $enableSearch;
        $this->toolbar = $toolbar;
        $this->forceEnable = $forceEnable;
        $this->paginatorOff = $paginatorOff;
        $this->colours = $colours;
        $this->pagelength = $pagelength;
		$this->containerHeight = $containerHeight;
		$this->priceListButtonEnabled = !($this->PriceListManager == NULL);

		
        if (count($quickSearch) > 0)
        {
            $this->saleShortsManager = $quickSearch['saleShortsManager'];
        }
        //profiler::finish('listgrid construct');

    }

    /*19.02.2023 - for TaskPresenter cl_partners_event_id*/
    public function setDataSource($parentId){
        if (!is_null($parentId)) {
            $this->dataSource = $this->DataManager->findAllTotal()->where([$this->parentColumnName => $parentId]);
        }else{
            $this->dataSource = $this->DataManager->findAllTotal()->where('TRUE = FALSE');
        }
    }

    public function setSearchPlaceholder($txt)
    {
        $this->searchPlaceholder = $txt;
    }

    public function setFilter($arrFilter)
    {
        $this->filter = $arrFilter;
        //bdump('ted ');
        //bdump($this->filter);
    }

    public function setShowType($arrShowType)
    {
        $this->show_type = $arrShowType;
    }

    public function setOrder($orderColumn){
        $this->orderColumn = $orderColumn;

        if ($this->orderColumn == NULL ) //$this->movableRow
        {
            $this->dataSource->order('item_order');
        }else{
            $this->dataSource->order($this->orderColumn);
        }

    }

    public function setPricelistEnabled($bool)
    {
        $this->priceListButtonEnabled = $bool;
    }
    
    public function render($filter = [], $checkedValues = FALSE, $readonly = FALSE)
    {
        $this->template->setTranslator($this->translator);
        $this->template->show_type = $this->show_type;
        ////profiler::start();
//        $this->template->select2Focus   = $this->select2Focus;
        $this->template->childRelation = $this->childRelation;
        $mySection = $this->presenter->getSession($this->name . '-tabbsc');
        if (isset($mySection['select2Focus']))
        {
            $this->template->select2Focus = $mySection['select2Focus'];
            //bdump($this->template->select2Focus);
            unset($mySection['select2Focus']);
        }else{
            $this->template->select2Focus = '';
        }
        $this->template->newLineName = $this->translator->translate($this->newLineName);
        $this->template->newLineTitle = $this->translator->translate($this->newLineTitle);
        $this->template->select2Focus = '';
        $this->template->b2b                = $this->parent->user->isInRole('b2b');
        $this->template->hideTimestamps = $this->hideTimestamps;
        $this->template->containerHeight = $this->containerHeight;
        $this->template->colours        = $this->colours;
        $this->template->toolbar        = $this->toolbar;
        $this->template->enableSearch   = $this->enableSearch;
        $this->template->forceEnable    = $this->forceEnable;
        $this->template->readonly       = ($this->readonly) ? $this->readonly : $readonly;
        $this->template->paginatorOff   = $this->paginatorOff;
        $this->template->showHistory    = $this->showHistory;
        //bdump( $this->template->forceEnable, 'ted render');
        $this->template->nodelete       = $this->nodelete;
        $this->template->checkedValues  = $checkedValues;
        if ( is_object($this->parent->settings) && !$this->parent->user->isInRole('b2b')) {
            $this->template->isPrivateTable = $this->parent->UserManager->isPrivate($this->DataManager->tableName, $this->parent->settings->id, $this->parent->getUser()->id);
            $this->template->userId = $this->parent->getUser()->id;
        }else{
            $this->template->isPrivateTable = FALSE;
            $this->template->userId = NULL;
        }
        $this->template->cmpName        = $this->name;
        $this->template->setFile(__DIR__ . '/Listgrid.latte');
        /*if (count($filter) > 0)
        {

            $tmpDataSource = $this->dataSource->where($filter['filter']);
        }else*/
        //bdump($this->filter);
        if (count($this->filter) > 0){
            if (isset($this->filter['values'])){
                $tmpDataSource = $this->dataSource->where($this->filter['filter'], $this->filter['values']);
            }else{
                $tmpDataSource = $this->dataSource->where($this->filter['filter']);
            }

        }else{
            $tmpDataSource = $this->dataSource;
        }
        //if ($this->DataManager->tableName == 'cl_pricelist_partner')
        //    $tmpDataSource->select('cl_pricelist_partner.*, cl_pricelist.cl_partners_book_id AS clp2');

        //bdump($this->txtSearch, 'ted');
        if ($this->txtSearch != ''){
            $count = substr_count($this->txtSearchCondition,'?');
            $params = [];
            while ($count >= 1){
                $params[] = '%'.$this->txtSearch.'%';
                $count--;
            }
            $tmpDataSource = $this->dataSource->where($this->txtSearchCondition, $params);
            //15.08.2019 - PLK - if is founded just one record, make open to edit
			//12.02.2020 - for now switched off, because it's useless for other costumers
            /*if ($tmpDataSource->count() == 1 && is_null($this->editIdLine))
            {
                $this->editIdLine = $tmpDataSource->fetch()->id;
                $this->txtSearch = '';
            }*/
        }

        //dump($this['search']->render());
        //die;
        $this['search']->setValues(['searchTxt' => $this->txtSearch]);
        $this->template->txtSearch = $this->txtSearch;
        $this->template->quickSearchEnabled = count($this->quickSearch) > 0;

        //bdump($this['search']);
        //paginator start
        $paginator = new \Nette\Utils\Paginator;
        if ($this->template->quickSearchEnabled || $this->paginatorOff) {
            $ItemsOnPage = 99999;
        }else{
            $ItemsOnPage = $this->pagelength;
        }
            $paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
            $totalItems = $tmpDataSource->count();
            //$totalCount = clone $tmpDataSource;
            //$totalItems = $totalCount->select('COUNT(1) AS count')->fetch();
            //if ($totalItems)
            //    $totalItems = $totalItems['count'];
            //else
            //    $totalItems = 0;

            $paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
            //$paginator->setPage($page); // číslo aktuální stránky, číslováno od 1
            $pages = ceil($totalItems / $ItemsOnPage);

            if (is_null($this->page_lg))
                $this->page_lg = 1;

            if ($this->page_lg > $pages)
                $this->page_lg = $pages;

            //bdump($this->newLine, 'newLine');
            //bdump($this->page_lg, 'page_lg 1');
            if ($this->newLine) {
                $this->page_lg = $paginator->getLastPage();
                $this->newLine = FALSE;
                $mySection = $this->presenter->getSession($this->name . '-pagelg');
                $mySection['page_lg'] = $this->page_lg;
            }else {
                $mySection = $this->presenter->getSession($this->name . '-pagelg');
                if (isset( $mySection['page_lg'] ) &&  $mySection['page_lg'] > 0) {
                    $this->page_lg = $mySection['page_lg'];
                    $mySection['page_lg'] = 0 ;
                }
            }
            //bdump($mySection['page_lg'], 'mysection page_lg');
            //bdump($this->page_lg, 'page_lg 2');

            $paginator->setPage($this->page_lg);


            $this->template->paginator = $paginator;
            $steps = array();
            for ($i = 1; $i <= $pages; $i++) {
                $steps[] = $i;
            }
            $this->template->steps = $steps;
            $finalData = $tmpDataSource->limit($paginator->getLength(), $paginator->getOffset());



        $this->template->dataSource = $finalData;



        $this->template->dataColumns	= $this->dataColumns;
        $this->template->conditionRows	= $this->conditionRows;
        $this->template->customUrl	= $this->customUrl;
        $this->template->movableRow	= $this->movableRow;
        $this->template->selectMode	= $this->selectMode;
        $this->template->PriceListBottom = $this->priceListBottom;
        //bdump($this->quickSearch,$this->DataManager->tableName);

        if ($this->template->quickSearchEnabled)
        {
            //$tmpBranchId = $this->presenter->CompanyBranchUsersManager->getBranchForUser($this->presenter->getUser()->id);
            //$tmpBranchId = $this->getUser()->cl_company_branch_id;
            $tmpBranchId = $this->presenter->user->getIdentity()->cl_company_branch_id;
            //$tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
            if (!is_null($tmpBranchId))
                $this->template->saleShorts = $this->saleShortsManager->findAll()->where('cl_company_branch_id = ?', $tmpBranchId)->order('id');
            else
                $this->template->saleShorts = $this->saleShortsManager->findAll()->where('cl_company_branch_id IS NULL')->order('id');
        }
        $this->template->fontSize	= $this->fontSize;

        if ($this->findTotalEnabled){
            $defData1 = $this->DataManager->findAllTotal()->where(array($this->DataManager->tableName.'.id' => $this->editIdLine))->limit(1)->fetch();
        }else{
            $defData1 = $this->DataManager->findOneBy(array($this->DataManager->tableName.'.id' => $this->editIdLine));
        }

        if ($defData1)
        {
            //25.11.2018 - fill selectbox which values is managed by defData
            foreach($this['editLine']->components as $one)
            {
                //if is type of form input selectbox and constrained from other table
                if ($one->options['type'] == 'select' && strpos($one->name, '_id'))
                {
                    //create key to refer in dataColumns
                    $tmpKeyTest = substr($one->name, 0, strpos($one->name, '_id'));
                    //bdump($one);
                    bdump($one->control->attrs);
                    $tmpKeyTest2 = $tmpKeyTest.'.name';
                    if (!isset($this->dataColumns[$tmpKeyTest2])){
                        $tmpKeyTest2 = $tmpKeyTest.'.dn_number';
                    }
                    if (!isset($this->dataColumns[$tmpKeyTest2])){
                        $tmpKeyTest2 = $tmpKeyTest.'.identification';
                    }
                    if (isset($one->control->attrs['datasource'])){
                        $tmpKeyTest2 = $one->control->attrs['datasource'];
                    }
                    bdump($tmpKeyTest2);

                    if (isset($this->dataColumns[$tmpKeyTest2]['valuesFunction']))
                    {
                        //if there is valuesFunction to get current set of array to selectbox
                        //bdump($this->dataColumns[$tmpKeyTest2]['valuesFunction'],'valuesFunction');

                        eval($this->dataColumns[$tmpKeyTest2]['valuesFunction']);
                        bdump($valuesToFill,'valuesToFill');
                        $one->setItems($valuesToFill);
                    }
                }
            }


            //
            //15.01.2017 - manage values from table for selectboxes which aren't in  available list
            $defData = $defData1->toArray();
            foreach($this['editLine']->components as $one)
            {

                //if is type of form input selectbox
                if ($one->options['type'] == 'select')
                {
                    //check if value is not string, in this case convert from any number to int
                    //it's because we are searching in array's key
                    $testValue = $defData[$one->name];
                    if (!is_string($testValue))
                    {
                        $testValue = (int)$testValue;
                    }

                    if (!array_key_exists($testValue, $one->getItems()))
                    {
                        //if defaultvalue from row isn't in list ov available values, use NULL
                        $defData[$one->name] = NULL;
                    }
                }
            }

            if (!$this['editLine']->hasErrors())
            {
                $this['editLine']->setValues($defData);
            }

            //bdump($defData,"defData");

            //format of date inputs
            $arrDateFormat = array();
            foreach($defData1 as $key => $one)
            {
                //bdump($key,'key');
                //bdump($one,'one');
                if (isset($this->dataColumns[$key]['format']) && $defData1[$key] != NULL && $this->dataColumns[$key]['format'] == 'date' )
                {
                    $arrDateFormat[$key] = $defData1[$key]->format('d.m.Y');
                }
                if (isset($this->dataColumns[$key]['format']) && $defData1[$key] != NULL && $this->dataColumns[$key]['format'] == 'datetime' )
                {
                    $arrDateFormat[$key] = $defData1[$key]->format('d.m.Y H:i:s');
                }
                if (isset($this->dataColumns[$key]['format']) && $defData1[$key] != NULL && $this->dataColumns[$key]['format'] == 'datetime2' )
                {
                    $arrDateFormat[$key] = $defData1[$key]->format('d.m.Y H:i');
                }

                //15.08.2019 - use default value for edit
                if (isset($this->dataColumns[$key]['defvalue']) && $defData1[$key] != NULL )
                {
                    $arrDateFormat[$key] = $this->dataColumns[$key]['defvalue'];
                }


            }

            //Debugger::fireLog($defData);
            //Debugger::fireLog($arrDateFormat);
            $this['editLine']->setValues($arrDateFormat);
        }else{
            $defData = NULL;
        }

            //readonly fields settings
            foreach ($this['editLine']->getControls() as $control) {
                //bdump($control->name, 'control name');
                $tmpKey = str_replace('__','.',$control->name);
                //bdump($tmpKey);
                if (isset($this->dataColumns[$tmpKey]) && isset($this->dataColumns[$tmpKey]['readonly']) && $this->dataColumns[$tmpKey]['readonly'])
				//if (isset($this->dataColumns[$tmpKey]) && isset($this->dataColumns[$tmpKey]['readonly']) || $this->dataColumns[$tmpKey]['readonly'])
                {
                    $control->controlPrototype->readonly = 'readonly';

                    //selectbox solution redonly
                    if ($control->options['type'] == 'select')
                    {
                        //fill selectbox with only one value which is default
                        $arrOne = array($control->value => $control->items[$control->value]);
                        $control->items = $arrOne;
                    }elseif($control->options['type'] == 'checkbox'){
                        $control->setDisabled(TRUE);
                        if (isset($defData[$control->name]) && $defData[$control->name] == 1) {
                            $control->value = TRUE;
                            //TODO: 05.07.2019 there is danger, nette sends disabled checkbox always as empty -> FALSE
                            //$control->controlPrototype->checked = 'checked';
                        }else {
                            $control->value = FALSE;
                        }
                    }

                    //bdump($control);

                    //if ($control->options['type'] == 'checkbox'){
                    //$control->setDisabled(TRUE);
                    //}

                }

                //Debugger::fireLog($control->name);

                //readonly for constraints from parent tables
//                bdump(strpos($control->name, '_id'));
//                bdump(strpos($control->name, '_id') && !is_null($defData)) ;
                //bdump($control->name);
                if (strpos($control->name, '_id') && !is_null($defData)) {
                    $tmpKeyTest = substr($control->name, 0, strpos($control->name, '_id'));
                    //Debugger::fireLog($tmpKeyTest);
                    bdump($tmpKeyTest);
                    //if (isset($this->dataColumns[$tmpKeyTest.'.name']) && isset($this->dataColumns[$tmpKeyTest.'.name']['roCondition']))
                    //$testDef = array_search('$tmpKeyTest', $this->dataColumns);
                    $testDef = $this->array_partial_search($this->dataColumns, $tmpKeyTest);
                    //if (isset($this->dataColumns[$tmpKeyTest . '.name']) && isset($this->dataColumns[$tmpKeyTest . '.name']['roCondition'])) {
                    if (isset($this->dataColumns[$testDef]) && isset($this->dataColumns[$testDef]['roCondition'])) {
                        $result = 0;
                        //$tmpEval = "\$result = " . $this->dataColumns[$tmpKeyTest . '.name']['roCondition'] . ";";
                        bdump($testDef);
                        $tmpEval = "\$result = " . $this->dataColumns[$testDef]['roCondition'] . ";";
                        bdump($tmpEval);
                        //if (strpos($tmpEval, 'defData') && is_null($defData)){
                        //  $result = FALSE;
                        //}else{
                        eval($tmpEval);
                        //}
                        //bdump($result,'tmpEval-result');
                        if ($result) {
                            $control->controlPrototype->readonly = 'readonly';
                            //selectbox solution redonly
                            if ($control->options['type'] == 'select') {
                                //fill selectbox with only one value which is default
                                //25.11.2018 - and only if default value is not NULL
                                if (!is_null($control->value)) {
                                    $arrOne = [$control->value => $control->items[$control->value]];
                                } else {
                                    $arrOne = [];
                                }
                                $control->items = $arrOne;
                            }
                        }
                    }
                    } elseif (!is_null($defData)){
                    //$tmpKeyTest = substr($control->name,0,  strpos($control->name, '_id'));
                    //bdump($tmpKey, 'tmpKey');
                    if (isset($this->dataColumns[$tmpKey]) && isset($this->dataColumns[$tmpKey]['roCondition']))
                    {
                        $result = 0;
                        $tmpEval = "\$result = ".$this->dataColumns[$tmpKey]['roCondition'].";";
                        bdump($tmpEval,'tmpEval');
                        //if (strpos($tmpEval, 'defData') && is_null($defData)){
                         //$result = FALSE;
                        //}else{
                            eval($tmpEval);
                        //}
                        //bdump($result,'tmpEval-result');
                        if ($result)
                        //if (isset($this->dataColumns[$tmpKey]['roCondition']) && $this->dataColumns[$tmpKey]['roCondition'])
                        {
                            $control->controlPrototype->readonly = 'readonly';
                            //selectbox solution redonly
                            if ($control->options['type'] == 'select')
                            {
                                //fill selectbox with only one value which is default
                                $arrOne = array($control->value => $control->items[$control->value]);
                                $control->items = $arrOne;
                            }

                        }
                    }
                }
                //default values of readonly constraints from parent tables
                 //Debugger::fireLog($tmpKey);
                if (isset($this->dataColumns[$tmpKey]) && isset($this->dataColumns[$tmpKey]['readonly']) && $this->dataColumns[$tmpKey]['readonly'])
                {
                    if (isset($this->dataColumns[$tmpKey]['function'])){
                        $funRet = NULL;
                        //= $this->parent->DataProcessListGrid($data);
                        $funName = '$funRet = $this->parent->'.$this->dataColumns[$tmpKey]['function'];
                        if (isset($this->dataColumns[$tmpKey]['function_param'])){
                            $paramF = array();
                            foreach ($this->dataColumns[$tmpKey]['function_param'] as $keyF => $oneF)
                            {
                                $csvF = str_getcsv($oneF,'.');
                                $counterF = count($csvF);
                                if ($counterF == 2 && isset($defData1[$csvF[0]][$csvF[1]])) {
                                    $valueF = $defData1[$csvF[0]][$csvF[1]];
                                }elseif ($counterF == 3 && isset($defData1[$csvF[0]][$csvF[1]][$csvF[2]])) {
                                    $valueF = $defData1[$csvF[0]][$csvF[1]][$csvF[2]];
                                }elseif (isset($defData1[$oneF])){
                                    $valueF = $defData1[$oneF];
                                }else{
                                    $valueF = "";
                                }
                                if (!is_null($valueF))
                                    $paramF[$oneF] = $valueF;
                                //$value = $this->$funName($paramF);
                                //if there is function to get current value
                                //bdump($this->dataColumns[$tmpKeyTest2]['valuesFunction'],'valuesFunction');

                            }
                            //bdump($paramF);
                            if (count($paramF) > 0) {
                                $funName .= '($paramF);';
                                eval($funName);
                            }
                            if ($this->dataColumns[$tmpKey]['format'] == 'date' && !is_null($funRet)){
                                $funRet = $funRet->format('d.m.Y');
                            }

                            $this['editLine']->setValues(array($tmpKey => $funRet));
                        }




                    }


                    if (strpos($tmpKey,'.')) {


                        $csv = str_getcsv($tmpKey,'.');
                        $counter = count($csv);

                        if ($counter == 2 && isset($defData1[$csv[0]][$csv[1]]))
                            $value = $defData1[$csv[0]][$csv[1]];
                        elseif ($counter == 3 && isset($defData1[$csv[0]][$csv[1]][$csv[2]]))
                            $value = $defData1[$csv[0]][$csv[1]][$csv[2]];
                        else
                            $value = NULL;

/*                        $tmpParent = substr($tmpKey, 0, strpos($tmpKey, '.'));
                        $tmpKey2 = substr($tmpKey, strpos($tmpKey, '.') + 1);
                        $tmpParent2 = $tmpParent . "_id";

                        $tmpParent3 = substr($tmpKey2, 0, strpos($tmpKey2, '.'));
                        $tmpKey3 = substr($tmpKey2, strpos($tmpKey2, '.') + 1);
                        $tmpParent31 = $tmpParent3 . "_id";

                        $arrDateFormat = array();
                        if (!is_null($defData1[$tmpParent][$tmpParent31])) {
                            $value = $defData1->$tmpParent->$tmpParent3->$tmpKey3;

                        } elseif (!is_null($defData1[$tmpParent2])){
                            $value = $defData1->$tmpParent->$tmpKey2;
                        } else{
                            $value = NULL;
                            //bdump('nic jsme nenesli');
                        }*/


                        if (!is_null($value) && isset($this->dataColumns[$tmpKey]) && isset($this->dataColumns[$tmpKey]['format']))
                        {
                            if ($this->dataColumns[$tmpKey]['format'] == 'date'){
                                $value = $value->format('d.m.Y');
                            }

                        }
                        $arrDateFormat[str_replace('.','__',$tmpKey)]  = $value;
                        $this['editLine']->setValues($arrDateFormat);
                    }
                }
            }



        $this->template->editIdLine = $this->editIdLine;
        $this->template->lastIdLine = $this->lastIdLine;
        //$this->template->pricelist = $this->PriceListManager->findAll();
        $this->template->PriceListEnabled  = !($this->PriceListManager == NULL);
        $this->template->priceListButtonEnabled = $this->priceListButtonEnabled;
        if (($this->presenter->isAllowed($this->presenter->name.':'.$this->name,'write') &&
            $this->presenter->isAllowed($this->presenter->name,'write')) ||  $this->parent->user->isInRole('b2b')) //enable add empty row
        {
            $this->template->EnableAddEmptyRow = $this->EnableAddEmptyRow;
        }else{
            $this->template->EnableAddEmptyRow = FALSE;
        }
        //bdump($this->presenter->name.':'.$this->name);
        $this->template->EnableErase  = !$this->selectMode && $this->presenter->isAllowed($this->presenter->name.':'.$this->name,'erase')
                                                            && $this->presenter->isAllowed($this->presenter->name,'erase')  || $this->parent->user->isInRole('b2b'); //enable erase row
        $this->template->EnableEdit  = !$this->selectMode && $this->presenter->isAllowed($this->presenter->name.':'.$this->name,'edit')
                                                            && $this->presenter->isAllowed($this->presenter->name,'edit') || $this->parent->user->isInRole('b2b')
                                                            ; //enable edit row
        //bdump($this->template->EnableEdit);
        //if (isset($this->parent->myReadOnly)) {
            $this->template->myReadOnly = $this->parent->myReadOnly;
        //}

        $this->template->render();
        ////profiler::finish('listgrid '.$this->getName());
    }


    public function getDefaultData()
    {
        return $this->defaultData;
    }

    public function handleEditLine($editIdLine,$page_lg, $txtSearch)
    {
        $this->txtSearch = $txtSearch;
		$this->editIdLine = $editIdLine;
		$this->page_lg = $page_lg;
		$this->redrawControl('editLines');
		//$this->redrawControl();
    }
    
    public function handleAddLine($id, $show_type = 0)
    {
        if ($this->presenter->isAllowed($this->presenter->name, 'write') || $this->presenter->isAllowed($this->presenter->name, 'edit')) {
            $data = [];
           // $data[$this->parentColumnName] = $id; //$this->parentId
            $data[$this->parentColumnName] = $this->parentId;

            if ($show_type > 0)
                $data['show_type'] = $show_type;

            $data['item_order'] = $this->DataManager->findAll()->where($this->parentColumnName . ' = ?', $id)->max('item_order') + 1;
            foreach ($this->defaultData as $key => $one) {
                $data[$key] = $one;
            }
            $data['control_name'] = $this->name;
            //$this->flashMessage($this->name);
            $data = $this->parent->beforeAddLine($data);
            if ($data) {
                unset($data['control_name']);
                if ($newLine = $this->DataManager->insert($data)) {
                    $this->editIdLine = $newLine->id;
                }

                //15.9.2019 -erase potencial search
                $this->txtSearch = "";

                $this->newLine = TRUE;
            }
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
        $this->redrawControl('paginator');
		$this->redrawControl('editLines');
	}    

    public function handleOrderLine($idLine,$newOrder,$oldOrder,$page_lg)
    {
		$oldOrder = $oldOrder + 1;
		$newOrder = $newOrder + 1;
		$dataToOrder = $this->DataManager->findAll()->
					where($this->parentColumnName.' = ? AND item_order <= ? AND id != ?', $this->parentId, $newOrder,$idLine)->
					order('item_order');
		$orderTmp = 1;

		foreach($dataToOrder as $one)
		{

			$one->update(array('item_order' => $orderTmp));
			$orderTmp++;
		}

		$dataToOrder = $this->DataManager->findAll()->
					where($this->parentColumnName.' = ? AND item_order >= ? AND id != ?', $this->parentId, $newOrder,$idLine)->
					order('item_order');		    	    

		$orderTmp = $newOrder + 1;

		foreach($dataToOrder as $one)
		{

			$one->update(array('item_order' => $orderTmp));
			$orderTmp++;
		}

		$data = array();
		$data['id'] = $idLine;
		$data['item_order'] = $newOrder;

		$this->DataManager->update($data);
		$this->page_lg = $page_lg;
		$this->redrawControl('editLines');	
		//$this->presenter->redrawControl('baselistScripts');
    }
    
    public function handleRemoveLine($lineId)
    {
        if ($this->presenter->isAllowed($this->presenter->name, 'erase') ) {
            //bdump($lineId, 'removeline');
            if ($this->parent->beforeDelete($lineId, $this->name))
            {
                try{
                    $line = $this->DataManager->find($lineId);
                    if ($line){
                        $line = $line->toArray();
                        $this->DataManager->delete($lineId);
                        $this->parent->afterDelete($line);//update parent records if needed
                    }
                    $this->updateParentSum($lineId);
                }catch (Exception $e) {
                    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451)
                        $errorMess = 'Záznam nebylo možné vymazat, protože k němu existují podřízené záznamy.';
                    else
                        $errorMess = $e->getMessage();
                    $this->parent->flashMessage($errorMess,'danger');
                }


            }else{

            }
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
        $this->redrawControl('editLines');
        //$this->parent->redrawControl('bscAreaEdit');

		//$this->parent->redrawControl('items');
		//$this->parent->redrawControl('formedit');		
		$this->parent->redrawControl('flash');		
    }        
    
    protected function createComponentEditLine($name)
    {
        //if ($this->editIdLine == 0)
//            $this->txtSearch = "";

        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);

	    //$form->addHidden('editId',$this->parentId);
		$tabIndex = -50;
	    foreach($this->dataColumns as $key=>$one)
	    {
		//dump($one);
		//dump($key);
			$e100p = "";

			if (isset($one['e100p'])){
				$e100p = $one['e100p'];
			}
			if(!isset($one['editdisabled']) && $key!='arrTools')
			{
				if ( (isset($one['values']) || isset($one['valuesFunction'])) && $one['format'] != 'hidden-data-values')
				{
					//made select input
					if (strpos($key,'.'))
					{
						$tmpKey = substr($key,0,strpos($key,'.'))."_id";
					}
					else
						$tmpKey= $key;

					if (isset($one['required']))
					{
					    $tmpRequired = $one['required'];
					}else{
					    $tmpRequired = false;
					}
					$valuesToFill = $one['values'];
					if (isset($one['getValues']))
                    {
                        $valuesToFill = $one['getValues'](1);
                    }
					//bdump($valuesToFill);
					if ($tmpRequired){
					      $form->addSelect($tmpKey, $one[0], $valuesToFill)
						  ->setPrompt($this->translator->translate('vyberte'))
						  ->setRequired($one['required'])
                          ->setHtmlAttribute('datasource', $key)
						  ->setHtmlAttribute('class','form-control input-sm chzn-select')
						  ->setHtmlAttribute('placeholder',$one[0]);
					}else{
					      $form->addSelect($tmpKey, $one[0], $valuesToFill)
						  ->setPrompt($this->translator->translate('vyberte'))
                          ->setHtmlAttribute('datasource', $key)
						  ->setHtmlAttribute('class','form-control input-sm chzn-select')
						  ->setHtmlAttribute('placeholder',$one[0]);
					 }
                    if ($e100p == 'false'){
                        $form[$tmpKey]->setHtmlAttribute('data-e100p', $tabIndex);
                        $tabIndex = $tabIndex + 1;
                    }


                }  else {
					//made text input
					if (isset($one['format']) && $one['format'] == 'hidden')
					{
						$form->addHidden($key,NULL);						
					}else{
					
						if (strpos($key,'.'))
							$tmpKey = str_replace ('.', '__', $key);
						else
							$tmpKey = $key;

						
						if ($one['format'] == 'boolean')
						{
						    $form->addCheckbox($tmpKey)
							        ->setHtmlAttribute('class','form-control input-sm  newline checkboxListGrid');
						}elseif ($one['format'] == 'textarea-formated')
						{
						    $form->addTextArea($tmpKey, $one[0],$one['size'], $one['rows'])
							    ->setHtmlAttribute('class','form-control input-sm trumbowyg-edit')
							    ->setHtmlAttribute('placeholder',$one[0]);		    						    
						}elseif ($one['format'] == 'textarea')
						{
						    $form->addTextArea($tmpKey, $one[0],$one['size'], $one['rows'])
							    ->setHtmlAttribute('class','form-control input-sm')
							    ->setHtmlAttribute('placeholder',$one[0]);		    						    
						}elseif ($one['format'] == 'html')
                        {
                            $form->addTextArea($tmpKey, $one[0])
                                ->setHtmlAttribute('class','form-control dd input-sm')
                                ->setHtmlAttribute('placeholder',$one[0]);
                        }
						else{

						    if (isset($one['size']))
							    $form->addText($tmpKey, $one[0],$one['size'])
								    ->setHtmlAttribute('class','form-control input-sm')
								    ->setHtmlAttribute('placeholder',$one[0]);		    
						    else
							    $form->addText($tmpKey, $one[0])
								    ->setHtmlAttribute('class','form-control input-sm')
								    ->setHtmlAttribute('placeholder',$one[0]);		    

						    if (isset($one['decplaces']))
						    {
							    //Debugger::fireLog($one['autonumeric-m-dec']);
							    $form[$tmpKey]->setHtmlAttribute('data-m-dec',$one['decplaces']);
						    }

                            if (isset($one['autocomplete']))
                            {
                                $form[$tmpKey]->setHtmlAttribute('autocomplete',$one['autocomplete']);
                            }

                            //if (isset($one['defval'])) {
//                                $form[$tmpKey]->setHtmlAttribute('data-m-dec',$one['decplaces']);
  //                          }

						}
						if ($e100p == 'false'){
							$form[$tmpKey]->setHtmlAttribute('data-e100p', $tabIndex);
							$tabIndex = $tabIndex + 1;
						}
						
						 if (isset($one['rules'])){
						    foreach($one['rules'] as $oneRule)
						    {
						        bdump($oneRule, 'onerule');
                                /* 16.12.2018 - not used for now, because I was not able to manage it to correct functionality
                                */
                                if (isset($oneRule['condition'])){
                                    $form[$tmpKey]->addCondition($oneRule['condition'][0], $oneRule['condition'][1]);

                                }elseif (isset($oneRule['conditionOn'])){
                                    $form[$tmpKey]->addConditionOn($form[$oneRule['conditionOn'][0]], $oneRule['conditionOn'][1], $oneRule['conditionOn'][2]);

                                }
                                /*
                                 *
                                 */
                                if (isset($oneRule['rule'])){
                                   $form[$tmpKey]->addRule($oneRule['rule'][0], $oneRule['rule'][1], $oneRule['rule'][2]);
                                   //$form[$tmpKey]->setRequired(TRUE);
                                }
							
						    }
						}
					}
				}
			}
			
	    }
	    
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setValidationScope([])
		    ->setHtmlAttribute('class','btn btn-sm btn-warning')
		    ->onClick[] = array($this, 'stepBack');
	    $form->addSubmit('sendLine', $this->translator->translate('Uložit'))
		    ->setHtmlAttribute('class','btn btn-success btn-sm');
        $form->onValidate[] = array($this, 'LineValidate');
	    $form->onSuccess[] = array($this, 'LineSubmitted');
		return $form;
    }
    
    public function stepBack()
    {	    
	$this->editIdLine = NULL;
	$this->redrawControl('editLines');
    }		
        
    
    public function LineValidate(Form $form)
    {
        if ($form['sendLine']->isSubmittedBy()) {
            $data = $form->values;
            //$form->addError('Neplatné heslo.');
            $this->editIdLine = $data['id'];
            $retData = $this->parent->DataProcessListGridValidate($data);
            //bdump($retData);
            if ($retData != NULL) {
                $form->addError($retData);
                //$this['editLine']->setValues($data);
                $this->redrawControl('editLines');
            }
        }
		
    }

    public function LineSubmitted(Form $form)
    {
		$data=$form->values;	
		//Debugger::fireLog($data);
			if ($form['sendLine']->isSubmittedBy())
			{
			   //bdump($data);
			//remove format from numbers
			foreach($data as $key => $one) {
                //if (isset($this->dataColumns[$key]['readonly']) && !isset($data[$key]))
               // {
               // }
				//if (isset($this->dataColumns[$key]['readonly']) && $this->dataColumns[$key]['format'] == TRUE)
				//{
				//	unset($data[$key]);
				//}else {
				
				if (strpos($key,'__') || strpos($key,'_function_')) {
					unset($data[$key]);
				}else {
				    //04.03.2021 - readonly must remain in data, because it could be changed by javascript for example in salePresenter.php
                    //if (isset($this->dataColumns[$key]['readonly']) && $this->dataColumns[$key]['readonly']) {
                      //  unset($data[$key]);
                    //}else {
                        if (isset($this->dataColumns[$key]['format']) && ($this->dataColumns[$key]['format'] == 'number' || $this->dataColumns[$key]['format'] == 'currency')) {
                            $data[$key] = str_replace(' ', '', $one);
                            $data[$key] = str_replace(',', '.', $data[$key]);
                        }
                        if (isset($this->dataColumns[$key]['format']) && $this->dataColumns[$key]['format'] == 'date') {
                            if ($data[$key] != '') {
                                $data[$key] = date('Y-m-d', strtotime($data[$key]));
                            } else {
                                $data[$key] = NULL;
                            }
                        }
                        if (isset($this->dataColumns[$key]['format']) && $this->dataColumns[$key]['format'] == 'datetime') {
                            if ($data[$key] != '') {
                                $data[$key] = date('Y-m-d H:i:s', strtotime($data[$key]));
                            } else {
                                $data[$key] = NULL;
                            }
                        }
                        if (isset($this->dataColumns[$key]['format']) && $this->dataColumns[$key]['format'] == 'datetime2') {
                            if ($data[$key] != '') {
                                $data[$key] = date('Y-m-d H:i', strtotime($data[$key]));
                            } else {
                                $data[$key] = NULL;
                            }
                        }
                    //}
					//}
				}
				//unset readonly
				//dump($key);
				//if (strpos($key,'__'))
//					unset($data[$key]);

			}
			//die;
			//send data to parent for another process
			//
			$data = $this->parent->DataProcessListGrid($data);
			//Debugger::fireLog($data);
			if (isset($this->customUrl['activeTab']))
			{
				$this->parent->activeTab = $this->customUrl['activeTab'];
			}

			//save parent Id to stay on correct record while editing new commission
				//$this->presenter->editId = $data['editId'];
				//unset($data['editId']);
			//bdump($data,'2second');
				if (!empty($data->id))
				{
					$this->DataManager->update($data, TRUE);
					$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                    $dataIdNew = $data->id;
				}
				else
				{
				
					$tmpNew = $this->DataManager->insert($data);
                    $dataIdNew = $tmpNew->id;
                    $this->flashMessage($this->translator->translate('Nový_záznam_byl_uložen'), 'success');
				}
				//04.06.2017 - after datasave call custom methods
				//$this->afterDataSaveListGrid($data['id']);
                $this->afterDataSaveListGrid($dataIdNew);
				//$this->redrawControl();
				//update parent data total sums
				$this->updateParentSum($dataIdNew);
				$this->lastIdLine = $this->editIdLine;
				$this->editIdLine = 0;		    
				$this->redrawControl('editLines');
                $this->redrawControl('pricelistTop');
                $this->redrawControl('pricelistBottom');
				//$this->parent->redrawControl('items');
				//$this->parent->redrawControl('formedit');		    


		}else{
			$this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'danger');
			//$this->redirect('default');
            $this->editIdLine = 0;
			$this->redrawControl('editLines');
            $this->redrawControl('pricelistTop');
            $this->redrawControl('pricelistBottom');
			//$this->redrawControl();
			$this->updateParentSum();


			//$this->parent->redrawControl('items');
			//$this->parent->redrawControl('formedit');		
	    }
        $mySection = $this->presenter->getSession($this->name . '-tabbsc');
        if ($this->select2FocusEnabled) {
            $mySection['select2Focus'] = ".select2Pricelist:visible";
        }
        //bdump($mySection['select2Focus']);


	    
    }	        
    
   private function afterDataSaveListGrid($dataId)
   {
	    $this->parent->afterDataSaveListGrid($dataId, $this->name);
    }

    
    private function updateParentSum($lineId = NULL)
    {
	    $this->onChange($lineId);
    }

    protected function createComponentPriceList($name)
    {	
            $form = new Form($this, $name);
            ////$this->translator->setPrefix(['components.listgrid']);
            //$translator = clone $this->translator->getTranslator();
            $translator = $this->translator;
            $partners = $this->ParentManager->find($this->parentId);
            //$numberDecimal = $this->dataSource->cl_company->des_cena;
            if ($this->presenter->settings->price_e_type == 1 && $this->presenter->settings->platce_dph == 1)
            {
                    $priceName = 'cl_pricelist.price_vat';
            }else{
                    $priceName = 'cl_pricelist.price';
            }

           // if ( isset($partners->cl_partners_book_id) && $partners->cl_partners_book_id != NULL && $partners->cl_partners_book->pricelist_partner && $this->presenter->name != 'Application:Order')
           // {
            //        $arrPriceList = $this->PriceListPartnerManager->findAll()->where('cl_partners_book_id = ? ', $partners->cl_partners_book_id)
              //                       ->select('cl_pricelist.id,CONCAT(cl_pricelist.identification," ",cl_pricelist.item_label,?,FORMAT(cl_pricelist_partner.price,cl_company.des_cena)," ",cl_currencies.currency_name,?,FORMAT(cl_pricelist.quantity,cl_company.des_mj)," ",cl_pricelist.unit) AS name',' ',' / ')->order('name')->fetchPairs('id','name');
           // }
           // else {
                //    $arrPriceList = $this->PriceListManager->findAll()
                  //                   ->select('cl_pricelist.id,CONCAT(identification," ",item_label,?,FORMAT('.$priceName.',cl_company.des_cena)," ",cl_currencies.currency_name,?,FORMAT(quantity,cl_company.des_mj)," ",unit) AS name',' ',' / ')->order('name')->fetchPairs('id','name');		
           // }
            $arrPriceList = [];
            $form->addSelect('priceList', $translator->translate('Ceník:'), $arrPriceList)
                    ->setPrompt($translator->translate('Vyberte_z_ceníku_položku_pro_vložení'))
                    ->setHtmlAttribute('class','form-control chzn-select input-sm')
                    ->setHtmlAttribute('data-urlajax', $this->presenter->link('getPriceList!'))                    
                    ->setHtmlAttribute('placeholder',$translator->translate('Vyberte_z_ceníku_položku_pro_vložení'));

            $form->addSubmit('send', $translator->translate('Vložit'))->setHtmlAttribute('class','btn btn-primary btn-sm');
            $form->onSuccess[] = [$this, 'PriceListInsert'];
            return $form;
    }    
    
    public function PriceListInsert(Form $form)
    {
        $data=$form->values;
	    if ($form['send']->isSubmittedBy())
	    {
		
                //work with partners pricelist
                $partners = $this->ParentManager->find($this->parentId);

                if ( isset($partners->cl_partners_book_id) && $partners->cl_partners_book_id != NULL && $partners->cl_partners_book->pricelist_partner && $this->presenter->name != 'Application:Order')
                {
                        $sourceData = $this->PriceListPartnerManager->findAll()->where('cl_pricelist_id = ? AND cl_partners_book_id = ? ',$data['priceList'], $partners->cl_partners_book_id)->fetch();
                }else
                {
                        $sourceData = $this->PriceListManager->find($data['priceList']);
                }
                //bdump($sourceData);
                if ($sourceData)
                {
                        $row = $this->parent->ListGridInsert($sourceData);
                        if (isset($this->customUrl['activeTab']))
                            $this->parent->activeTab = $this->customUrl['activeTab'];

                        $this->flashMessage('Nový záznam byl vložen.', 'success');
                        $this->editIdLine = $row->id;
                        $this->newLine = TRUE;
                        $this->redrawControl('editLines');
                        //$this->parent->redrawControl('items');
                        $this->parent->redrawControl('flashMessage');
                        $this->redrawControl('paginator');
                        //04.12.2017 - odstraněno, protože to překreslovalo formulář, s kterým by se v danou chvíli nemělo hýbat
                        //$this->parent->redrawControl('formedit');		    
                }

	    }

    }
    
    public function handlePriceListInsert($cl_pricelist_id)
    {
        if ($this->presenter->isAllowed($this->presenter->name, 'write') || $this->presenter->isAllowed($this->presenter->name, 'edit')) {
            $partners = $this->ParentManager->find($this->parentId);
            $sourceData = $this->PriceListManager->find($cl_pricelist_id);

            if ($sourceData) {
                //15.9.2019 -erase potencial search
                $this->txtSearch = "";

                $row = $this->parent->ListGridInsert($sourceData, $this->DataManager);
                if ($row) {
                    if (isset($this->customUrl['activeTab']))
                        $this->parent->activeTab = $this->customUrl['activeTab'];

                    $this->flashMessage($this->translator->translate('Nový_záznam_byl_vložen.'), 'success');
                    //$this->parent->redrawControl('flash');

                    $this->editIdLine = $row->id;
                    $this->newLine = TRUE;
                }
                //$this->template->paginator->setPage($this->page_lg);

                //04.12.2017 - odstraněno, protože to překreslovalo formulář, s kterým by se v danou chvíli nemělo hýbat
                //$this->parent->redrawControl('formedit');
            }
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }

        $this->redrawControl('editLines');
        $this->redrawControl('paginator');
        $this->redrawControl('content');
        $this->parent->redrawControl('items');

    }
    
    
    public function redrawPriceList2()
    {
		$this->redrawControl('pricelist2');
    }

    public function handleReport($itemId)
    {
	    $this->onPrint($itemId);
    }

    public function handleNewSubTask($itemId)
    {
        $this->onNewSubTask($itemId);
    }

    public function handleCustomFunction($itemId, $type)
    {
        $this->onCustomFunction($itemId, $type);
    }


    public function handleNewPage($page_lg)
    {
        $this->page_lg = $page_lg;
        //$this->redrawControl('baselist');
        $this->redrawControl('paginator');
        $this->redrawControl('editLines');
    }



    protected function createComponentSearch($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addText('searchTxt', '',20)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Hledaný_text_v_dokladu'));

        $form->addSubmit('send', $this->translator->translate('Hledat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', ' X ')
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'searchBack');

        $form->onSuccess[] = array($this, 'searchSubmitted');
        return $form;
    }

    public function searchBack()
    {
        $this->txtSearch = "";
        $this->redrawControl('paginator');
        $this->redrawControl('editLines');
    }

    public function searchSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            $this->txtSearch = $data['searchTxt'];
        }
        //$this->redirect(this');
        $this->redrawControl('paginator');
        $this->redrawControl('editLines');
    }


    public function setContainerHeight($height){
        $this->containerHeight = $height;
    }

    public function disableSelect2Focus()
    {
        $this->select2FocusEnabled = false;
    }

    public function setPaginatorOff()
    {
        $this->paginatorOff = TRUE;
    }

    public function setReadOnly($val = TRUE)
    {
        $this->readonly = $val;
    }

    public function setPaginatorOn()
    {
        $this->paginatorOff = FALSE;
    }

    public function setPageLength($pl = 20)
    {
        $this->pagelength = (int)$pl;
    }

    public function setForceEnable($boolean)
    {
        $this->forceEnable = $boolean;
    }

    public function setEnableAddEmptyRow($boolean)
    {
        $this->EnableAddEmptyRow = $boolean;
    }

    public function setToolbar($arrToolbar)
    {
        if (is_null($this->toolbar)) {
            $this->toolbar = $arrToolbar;
        }else{
            //bdump($this->toolbar);
            foreach($arrToolbar as $key => $one){
                foreach($one as $key2 => $one2) {
                    $this->toolbar[$key][$key2] = $one2;
                }
            }
            //bdump($this->toolbar);
        }

    }

    public function setEnableSearch($filter){
        if (!empty($filter)) {
            $this->enableSearch = TRUE;
            $this->txtSearchCondition = $filter;
        }
    }

    public function setDisableSearch(){
        $this->enableSearch = FALSE;
        $this->txtSearchCondition = "";
    }

    public function setFindTotalEnabled(){
        $this->findTotalEnabled = TRUE;
    }

    public function showHistory($val){
        $this->showHistory = $val;
    }


    public function handleShowHistory($id)
    {
        $this->presenter['history']->setId($id);
        $this->presenter['history']->setDataColumns($this->dataColumns);
        $this->presenter['history']->setTableName($this->DataManager->tableName);
        $this->presenter->showHistory = TRUE;
        $this->presenter->redrawControl('historyContent');
        $this->presenter->redrawControl('showHistory');
    }

    public function setParentColumnName2($name){
        $this->parentColumnName2 = $name;
    }

    public function setParentColumnValue2($value){
        $this->parentColumnValue2 = $value;
    }

    public function setHideTimestamps($value){
        $this->hideTimestamps = $value;
    }

    public function setEditIdLine($editIdLine){
        $this->editIdLine = $editIdLine;
    }

    public function setTxtSearch($txtSearch){
        $this->txtSearch = $txtSearch;
    }

    public function setNewLine($newLine){
        $this->newLine = $newLine;
    }

    public function setChildRelation($childRelation){
        $this->childRelation = $childRelation;
    }

    public function setColoursCondition($arrCondi){
        $this->colours = $arrCondi;
    }

    public function setNewLineName($string){
        $this->newLineName = $string;
    }

    public function setNewLineTitle($string){
        $this->newLineTitle = $string;
    }

    /**
     * Helper function to do a partial search for string inside array.
     *
     * @param array  $array   Array of strings.
     * @param string $keyword Keyword to search.
     *
     * @return array
     */
    private function array_partial_search( $array, $keyword ) {
        $found = FALSE;
        // Loop through each item and check for a match.
        foreach ( $array as $key => $string ) {
            // If found somewhere inside the string, add.
            if ( strpos( $key, $keyword ) !== false ) {
                $found = $key;
                break;
            }
        }
        return $found;
    }



}

