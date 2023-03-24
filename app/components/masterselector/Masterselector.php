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

class MasterselectorControl extends Control
{

    private $dataColumns,$dataSource,$editIdLine = NULL,$parentId,$defaultData,$customUrl,$movableRow,$orderColumn, $selectMode, $quickSearch, $fontSize,
		$priceListBottom, $readonly, $nodelete, $enableSearch, $txtSearchCondition, $toolbar, $forceEnable, $paginatorOff, $colours, $pagelength = 20, $newLine = FALSE, $containerHeight, $select2FocusEnabled = true;
    private $priceListButtonEnabled = false;
    private $findTotalEnabled = false;
    private $searchPlaceholder = 'Hledaný text v dokladu';

    private $childRelation = NULL;
    public $showHistory = true;

    /** @var  */
    private $itemsToInsert = null;

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

    public $onInsertBtn = [];

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
     * @param type $parentId - parent ID for constraints
     * @param array $defaultData- default data for new record
     * @param type $ParentManager - parent data model manager*
     * @param int $pagelength - number of items on one page if paginator is on. Default is 20
	 * @param str $containerHeight - height of container.  Default is '470px'
     */
    public function __construct( Nette\Localization\Translator $translator, $DataManager, $dataColumns, $parentId,
					$defaultData, $ParentManager )
    {
        //parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->translator       = $translator;
        $this->dataColumns      = $dataColumns;
        $this->dataSource       = $DataManager->findAll();
        $this->DataManager      = $DataManager;
        $this->parentId         = $parentId;
        $this->defaultData      = $defaultData;
        $this->ParentManager    = $ParentManager;
    }

    public function render($filter = [], $checkedValues = FALSE, $readonly = FALSE)
    {
        $this->template->setTranslator($this->translator);
        $this->template->containerHeight = $this->containerHeight;
        $this->template->fontSize	= $this->fontSize;
        $this->template->checkedValues  = $checkedValues;
        $this->template->cmpName        = $this->name;

        if (count($this->filter) > 0){
            if (isset($this->filter['values'])){
                $tmpDataSource = $this->dataSource->where($this->filter['filter'], $this->filter['values']);
            }else{
                $tmpDataSource = $this->dataSource->where($this->filter['filter']);
            }
        }else{
            $tmpDataSource = $this->dataSource;
        }

        if ($this->txtSearch != ''){
            $count = substr_count($this->txtSearchCondition,'?');
            $params = [];
            while ($count >= 1){
                $params[] = '%'.$this->txtSearch.'%';
                $count--;
            }
            $tmpDataSource = $this->dataSource->where($this->txtSearchCondition, $params);
        }
        $this['search']->setValues(['searchTxt' => $this->txtSearch]);
        $this->template->txtSearch = $this->txtSearch;

        if (!is_null($this->orderColumn))
            $tmpDataSource = $this->dataSource->order($this->orderColumn);

        //paginator start
        $paginator = new \Nette\Utils\Paginator;
        $ItemsOnPage = $this->pagelength;
        $paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
        $totalItems = $tmpDataSource->count();
        $paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
        $pages = ceil($totalItems / $ItemsOnPage);
        if (is_null($this->page_lg))
            $this->page_lg = 1;

        if ($this->page_lg > $pages)
            $this->page_lg = $pages;

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

        $this->template->setFile(__DIR__ . '/Masterselector.latte');
        $this->template->render();
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
    

    public function handleNewPage($page_lg)
    {
        $this->page_lg = $page_lg;
        //$this->redrawControl('baselist');
        $this->redrawControl('paginator');
        $this->redrawControl('editLines');
    }

    public function handleInsertItems($dataItems, $name){
        $this->onInsertBtn($dataItems, $name);
    }

    protected function createComponentSearch($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addText('searchTxt', '',20)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Hledaný_text'));

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

    public function setPaginatorOff()
    {
        $this->paginatorOff = TRUE;
    }

    public function setPaginatorOn()
    {
        $this->paginatorOff = FALSE;
    }

    public function setPageLength($pl = 20)
    {
        $this->pagelength = (int)$pl;
    }

    public function setTxtSearch($txtSearch){
        $this->txtSearch = $txtSearch;
    }

    public function setFilter($arrFilter)
    {
        $this->filter = $arrFilter;
    }

    public function setOrder($orderColumn){
        $this->orderColumn = $orderColumn;
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




}

