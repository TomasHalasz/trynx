<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class InventoryPresenter extends \App\Presenters\BaseListPresenter
{

    public $srchData = array(), $srchCount = array();

    /** @persistent */
    public $arrSrch = array();

    /** @persistent */
    public $showDiff = FALSE;

    /** @persistent */
    public $showOnStore = FALSE;

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
     * @var \App\Model\InventoryManager
     */
    public $DataManager;


    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\PriceListGroupManager
     */
    public $PriceListGroupManager;

    /**
     * @inject
     * @var \App\Model\StoreMoveManager
     */
    public $StoreMoveManager;

    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;

    /**
     * @inject
     * @var \App\Model\InventoryItemsManager
     */
    public $InventoryItemsManager;


    /**
     * @inject
     * @var \App\Model\PricesManager
     */
    public $PricesManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;
	
	/**
	 * @inject
	 * @var \App\Model\WorkplacesManager
	 */
	public $WorkplacesManager;
	
	/**
	 * @inject
	 * @var \App\Model\InventoryWorkplacesManager
	 */
	public $InventoryWorkplacesManager;


	protected function createComponentWorkplacesListGrid()
	{
		$arrData = [
			'cl_workplaces.workplace_name' => [$this->translator->translate('Pracoviště_/_zařízení'), 'format' => 'text', 'size' => 25,
            'values' => $this->WorkplacesManager->findAllTotal()->order('disabled, workplace_name')->fetchPairs('id', 'workplace_name')],
			'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime', 'size' => 20, 'readonly' => TRUE],
			'create_by' => [$this->translator->translate('Vytvořil'), 'format' => 'text', 'size' => 20, 'readonly' => TRUE],
			'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime', 'size' => 20, 'readonly' => TRUE],
			'change_by' => [$this->translator->translate('Změnil'), 'format' => 'text', 'size' => 20, 'readonly' => TRUE]

        ];
		$control = new Controls\ListgridControl(
            $this->translator,
			$this->InventoryWorkplacesManager, //data manager
			$arrData, //data columns
			[], //row conditions
			$this->id, //parent Id
			[], //default data
			$this->DataManager, //parent data manager
			NULL, //pricelist manager
			NULL, //pricelist partner manager
			TRUE, //enable add empty row
			[] //custom links
		);

		return $control;
	}
		
		
		
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Inventory']);
        //Debugger::log('test', 'inventory');
        $this->formName = $this->translator->translate("Inventury");
        $this->mainTableName = 'cl_inventory';
        $this->dataColumns = [
            'date' => [$this->translator->translate('Datum_inventury'), 'format' => 'date'],
            'cl_status.status_name' => [$this->translator->translate('Stav'),'format' => 'colortag'],
            'name' => [$this->translator->translate('Popis'), 'format' => 'text'],
            'cl_users.name' => [$this->translator->translate('Zodpovědná_osoba'), 'format' => 'text'],
            'cl_storage.name' => [$this->translator->translate('Sklad'), 'format' => 'text'],
            'cl_pricelist_group.name' => [$this->translator->translate('Skupina_ceníku'), 'format' => 'text'],
            'total_count' => [$this->translator->translate('Počet_položek'), 'format' => 'integer', 'readonly' => TRUE],
            'finished_count' => [$this->translator->translate('Hotovo_položek'), 'format' => 'integer', 'readonly' => TRUE],
			'device_count' => [$this->translator->translate('Počet_pracovišť'), 'format' => 'integer', 'readonly' => TRUE, 'function' => 'getWorkplaceCount', 'function_param' => ['id']],
            'active' => [$this->translator->translate('Aktivní'), 'format' => 'boolean'],
            'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'],
            'create_by' => $this->translator->translate('Vytvořil'),
            'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'],
            'change_by' => $this->translator->translate('Změnil')];

        /*
            'toStore_count' => array($this->translator->translate('Připraveno položek'), 'format' => 'integer', 'readonly' => TRUE, 'function' => 'getToStoreCount', 'function_param' => array('id')),
            'onStore_count' => array($this->translator->translate('Naskladněno a vydáno'), 'format' => 'integer', 'readonly' => TRUE, 'function' => 'getOnStoreCount', 'function_param' => array('id')),*/
        $this->FilterC = ' ';
        $this->DefSort = 'cl_inventory.date,cl_inventory.name';
        //$this->relatedTable = 'cl_storage';
        //$this->mainFilter = 'cl_storage.cl_storage_id IS NULL';
        $this->filterColumns = ['name' => 'autocomplete', 'date' => '', 'cl_storage.name' => 'autocomplete', 'cl_pricelist_group.name' => 'autocomplete','cl_status.status_name' => 'autocomplete'];
        $this->defValues = ['date' => new \Nette\Utils\DateTime];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];

        $this->docTemplate[1]  =  $this->ReportManager->getReport(__DIR__ . '/../templates/Inventory/inventoryProtocol.latte');
        $this->docAuthor    = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $this->docTitle[1]	= ["", "cl_company.name"];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['cl_storage.name', 'cl_status.status_name', 'cl_users.name', 'cl_inventory.name'];
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        /*6 => array('url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => 'Tisk', 'class' => 'btn btn-success',
        'data' => array('data-ajax="true"', 'data-history="false"'),'icon' => 'glyphicon glyphicon-print'),
                  7 => array('url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => 'PDF', 'class' => 'btn btn-success',
        */

    }


    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);

    }

    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);
        $tmpData = $this->DataManager->find($id);
        $cl_storage_id = NULL;
        $tmpCompanyBranchId = $this->user->getIdentity()->cl_company_branch_id;
        if (!is_null($tmpCompanyBranchId)) {
            if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('id = ?', $tmpCompanyBranchId)->limit(1)->fetch()) {
                $cl_storage_id = $tmpBranch->cl_storage_id;

                //if (!is_null($cl_storage_id) && $cl_storage_id != $tmpData->cl_storage_id) {
                if (!is_null($cl_storage_id) && !is_null($tmpData->cl_storage_id) && $cl_storage_id != $tmpData->cl_storage_id) {
                    $tmpBranchStoreName = (!is_null($tmpBranch->cl_storage_id)) ? $tmpBranch->cl_storage->name : $this->translator->translate('není_definován');
                    $this->flashMessage($this->translator->translate('Pro_práci_s_inventurou_skladu') . $tmpData->cl_storage->name . $this->translator->translate('musíte_být_přepnuti_do_pobočky_tohoto_skladu._Nyní_jste_v_pobočce') . $tmpBranch->name . $this->translator->translate('která_má_sklad') . $tmpBranchStoreName, 'danger');
                    $this->redirect('Default');

                }
            }
        }

        $tmpFinishedCount = $this->getFinishedCount(['id' => $id]);
        $tmpTotalCount = $this->getTotalCount(['id' => $id]);
        $tmpData2 = [];
        $tmpData2['id'] = $id;
        $tmpData2['finished_count'] = $tmpFinishedCount;
        $tmpData2['total_count'] = $tmpTotalCount;
        $tmpData->update($tmpData2);


        $this->findSearched();

        //$this->showDiff = $showDiff;
        //$this->showOnStore = $showOnStore;
        //bdump($this->showOnStore);
        $this->template->srchData = $this->srchData;
        $this->template->dataCount = $this->srchCount;
        $this->template->showDiff = $this->showDiff;
        $this->template->showOnStore = $this->showOnStore;

    }


    private function findSearched()
    {
        $dataCount = [];

        if (count($this->arrSrch) > 0) {
            $data = $this->InventoryItemsManager->findAll()->
                                        select('cl_pricelist.id AS id, cl_pricelist.ean_code AS ean_code, cl_pricelist.identification AS identification, cl_pricelist.item_label AS item_label')->
                                        where('cl_inventory_items.cl_inventory_id = ?', $this->id)->
                                        where('cl_pricelist.ean_code IN ? OR cl_pricelist.identification IN ?', $this->arrSrch, $this->arrSrch);

            $counter = 0;
            $lastId = 0;
            asort($this->arrSrch);
            //bdump($this->arrSrch);
            foreach ($this->arrSrch as $one) {
                if ($counter == 0)
                    $lastId = $one;

                $counter++;
                if ($lastId != $one) {
                    $lastId = $one;
                    $counter = 1;
                }
                //bdump($lastId, 'lastId');
                //bdump($one, 'one');
                //bdump($counter, 'counter');
                $dataCount[$one] = $counter;
                //bdump('cycle end');
            }
            //bdump($dataCount);

            $this->srchData = $data;
            $this->srchCount = $dataCount;
        }
    }

    protected function createComponentInventoryItems()
    {
        /*'cl_store.cl_pricelist.identification' => array('Kód','format' => 'text', 'size' => 20, 'readonly' => TRUE),
            'cl_store.cl_pricelist.ean_code' => array('EAN','format' => 'text', 'size' => 20,  'readonly' => TRUE),
            'cl_store.cl_pricelist.item_label' => array('Název','format' => 'text', 'size' => 20,  'readonly' => TRUE),
        */
        $arrData = [
            'cl_pricelist.identification' => [$this->translator->translate('Kód'),'format' => 'text', 'size' => 20, 'readonly' => TRUE],
            'cl_pricelist.ean_code' => [$this->translator->translate('EAN'),'format' => 'text', 'size' => 20,  'readonly' => TRUE],
            'cl_pricelist.item_label' => [$this->translator->translate('Název'),'format' => 'text', 'size' => 20,  'readonly' => TRUE],
            'pricelistquantity' => [$this->translator->translate('Počet_aktuální'),'format' => 'number', 'size' => 10, 'readonly' => TRUE,  'function' => 'getRealQuant', 'function_param' => ['cl_pricelist_id','cl_inventory_id']],
            'quantity' => [$this->translator->translate('Počet_inventární'),'format' => 'number', 'size' => 10, 'readonly' => TRUE],
            'quantity_real' => [$this->translator->translate('Skutečnost'),'format' => 'number', 'size' => 10, 'defvalue' => 0],
            'finished' => [$this->translator->translate('Hotovo') , 'size' => 20, 'format' => 'boolean'],
            'difference' => [$this->translator->translate('Důvod'), 'format' => 'text', 'size' => 10, 'values' => $this->ArraysManager->getInventoryDifference()],
            'qr' => [$this->translator->translate('Celkem'),'format' => 'html', 'readonly' => TRUE, 'function' => 'getTotal', 'function_param' => ['id']],
            'input_history' => [$this->translator->translate('Historie_zadání'), 'format' => 'html', 'readonly' => TRUE, 'function' => 'getHistory', 'function_param' => ['id']],
            'on_store' => [$this->translator->translate('Výdej/Příjem') , 'format' => 'boolean', 'readonly' => TRUE]
        ];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->InventoryItemsManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            FALSE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'cl_pricelist.identification', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE, //pricelistbottom
            FALSE, //readonly
            TRUE, //nodelete
            TRUE, //enablesearch
            'identification LIKE ? OR item_label LIKE ? OR ean_code LIKE ?', //txtSEarchcondition
            NULL, //toolbar
            FALSE, //forceEnable
            FALSE, //paginatorOff
            [1 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '==', 'right' => 0],
                                                   2 => ['left' => 'quantity_real', 'condition' => '>', 'right' => 0]],
                                'colour' => $this->RGBtoHex(255,255,151)], //yellow  -
                  2 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '>', 'right' => 0],
                                                   2 => ['left' => 'quantity_real', 'condition' => '==', 'right' => 0]],
                                'colour' => $this->RGBtoHex(255,151,151)], //red
                  3 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '>', 'right' => 0],
                                                   2 => ['left' => 'quantity_real', 'condition' => '<', 'right' => 'quantity']],
                                'colour' => $this->RGBtoHex(151,151,255)], //blue
                  4 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '>', 'right' => 0],
                                                    2 => ['left' => 'quantity_real', 'condition' => '>', 'right' => 'quantity']],
                                'colour' => $this->RGBtoHex(151,255,151)], //green
                  5 => ['conditions' => [1 => ['left' => 'quantity', 'condition' => '<', 'right' => 0]],
                                'colour' => $this->RGBtoHex(252,176,92)], //orange
            ], //colours conditions
                /*- cervene - pocet je vetsi nez 0, skutecnost je 0 - toto zustava
                *- modre - pocet je vetsi nez 0, skutecnost je mensi od pocet
                *- zelene - pocet je vetsi nez 0, skutecnost je vetsi od pocet
                *- oranzove - pocet je mensi nez 0
                *- zlute - pocet je 0, skutecnost je vetsi nez 0 - toto zustava*/
            200 //pagelength
        );

        $control->setContainerHeight('auto');
        $control->onChange[] = function ()
        {
            $this->updateSum();
        };

        return $control;
    }

    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
		$form->addText('name', $this->translator->translate('Označení_inventury'), 40, 100)
                    ->setRequired($this->translator->translate('Zadejte_označení_inventury'))
                    ->setHtmlAttribute('placeholder',$this->translator->translate('Označení_inventury'));

		$form->addText('date', $this->translator->translate('Datum_inventury'), 10, 10)
			        ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_inventury'));

        $arrStatus= $this->StatusManager->findAll()->where('status_use = ?','inventory')->fetchPairs('id','status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"),$arrStatus)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_stav_inventury'))
            ->setPrompt($this->translator->translate('Zvolte_stav_inventury'));


        $arrSuppliers = $this->PartnersManager->findAll()->where('supplier = 1')->order('company')->fetchPairs('id','company');
        $form->addSelect('cl_partnersbook_id', $this->translator->translate("Dodavatel"), $arrSuppliers)
                ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_dodavatele'))
                ->setPrompt($this->translator->translate('Zvolte_dodavatele'));


        $arrUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addSelect('cl_users_id', $this->translator->translate("Zodpovědná_osoba"),$arrUsers)
                    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_zodpovědnou_osobu'))
                    ->setPrompt($this->translator->translate('Zvolte_zodpovědnou_osobu'));

        $arrStorage = $this->StorageManager->findAll()->where('cl_storage_id IS NULL AND name != ""')->
							select('CONCAT(name," - ",description) AS name,id')->order('name')->fetchPairs('id','name');
		$form->addSelect('cl_storage_id',$this->translator->translate('Sklad_inventury'),$arrStorage)
            ->setRequired($this->translator->translate('Sklad_musí_být_zvolen'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Sklad_inventury'));

        $arrGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_pricelist_group_id',$this->translator->translate('Skupina_ceníku'),$arrGroup)
            ->setPrompt($this->translator->translate('zvolte_skupinu'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Ceníková_skupina'));
	
		$form->addCheckbox('active', $this->translator->translate('Aktivní'))
			->setHtmlAttribute('class', 'items-show');

        $form->addTextArea('description_txt', $this->translator->translate('Poznámka'), 50,7);


		$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = [$this, 'stepBack'];
		$form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
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
        $new = false;
        $oldData = $this->DataManager->find($data->id);
        if (is_null($oldData->changed))
            $new = true;

        if ($form['send']->isSubmittedBy())
        {
            //dump($data->id);
            //die;
            $data = $this->removeFormat($data);
            $tmpFinishedCount = $this->getFinishedCount(['id' => $this->id]);
            $tmpTotalCount = $this->getTotalCount(['id' => $this->id]);
            $data['finished_count'] = $tmpFinishedCount;
            $data['total_count'] = $tmpTotalCount;
            if (!empty($data->id)) {
                $this->DataManager->update($data, TRUE);
            }else {
                $newData = $this->DataManager->insert($data);
            }
        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
//        bdump($new);
//        if (!$new){
//            $this->redirect('default');
//        }else{
            $this->redrawControl('flash');
            $this->redrawControl('content');
//        }
    }	    

    public function handleGenContent()
    {
        $tmpIventory = $this->DataManager->find($this->id);
/*        $store = $this->StoreManager->findAll()->
                            select('SUM(cl_store.quantity) AS quantity, cl_pricelist_id')->
							where('cl_store.cl_storage_id = ?', $tmpIventory->cl_storage_id)->
                            group('cl_pricelist_id')->
							order('cl_pricelist.identification');*/

        $store = $this->StoreManager->findAll()->
                            select('SUM(:cl_store_move.s_in - :cl_store_move.s_out ) AS quantity, cl_store.cl_pricelist_id')->
                            where('cl_store.cl_storage_id = ?', $tmpIventory->cl_storage_id)->
                            group('cl_pricelist_id');
        //->
          //                  order('cl_pricelist.identification');

       // if ($tmpIventory['order_by'] == )

        if (!is_null($tmpIventory->cl_pricelist_group_id))
                $store->where('cl_pricelist.cl_pricelist_group_id = ?', $tmpIventory->cl_pricelist_group_id)->order('cl_pricelist.identification');

        if (!is_null($tmpIventory->cl_partnersbook_id))
                $store->where('cl_pricelist.cl_partners_book_id = ?', $tmpIventory->cl_partnersbook_id)->order('cl_pricelist.identification');

        $i = 1;
        session_write_close();
        $maxCount = count($store);
        foreach($store as $key => $one)
        {
            $this->UserManager->setProgressBar( $i, $maxCount, $this->user->getId(), $this->translator->translate('Obsah_inventury'));
            if ($oneRec = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ? AND cl_pricelist_id = ?', $tmpIventory->id, $one->cl_pricelist_id)->fetch())
            {
                $oneRec->update(['quantity' => $one->quantity]);
            }else {
                //'cl_store_id' => $one->id,
                $arrData = ['cl_inventory_id' => $tmpIventory->id,
                    'cl_pricelist_id' => $one->cl_pricelist_id,
                    'quantity' => is_null($one->quantity) ? 0 : $one->quantity,
                    'item_order' => $i];
                if ($arrData['quantity'] == 0)
                     $arrData['finished'] = 1;

                $this->InventoryItemsManager->insert($arrData);
                $i++;
            }
        }
        $this->UserManager->resetProgressBar( $this->user->getId());

        $this->flashMessage($this->translator->translate('Obsah_inventury_byl_vygenerován_/_zaktualizován.'), 'success');
        $this->redrawControl('flash');
        $this->redrawControl('inventorycontent');
    }

    public function handleEraseContent()
    {

        $data = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ?', $this->id);
        foreach($data as $key => $one)
        {
            $data->delete();
        }

        $this->flashMessage($this->translator->translate('Obsah_inventury_byl_vymazán'), 'success');
        $this->redrawControl('flash');
        $this->redrawControl('content');
    }


    public function handleQuickSearch($qs)
    {
        $qs = str_replace(PHP_EOL, ' ', $qs);
        $arrSrch = str_getcsv($qs,' ');
        foreach($arrSrch as $key => $value)
        {
            if ($value == "")
                unset($arrSrch[$key]);
        }
        $this->arrSrch = $arrSrch;
        $this->redrawControl('searchresult');
    }

    public function handleSave()
    {
        $this->findSearched();
        foreach($this->srchData as $key => $one)
        {
            //bdump($this->srchCount, 'srchCount');
            //if ($tmpItem = $this->InventoryItemsManager->findAll()->where('cl_store_id = ? AND cl_inventory_id = ?', $one['cl_store_id'], $this->id)->fetch())
            if ($tmpItem = $this->InventoryItemsManager->findAll()->where('cl_pricelist_id = ? AND cl_inventory_id = ?', $one['id'], $this->id)->fetch())
            {
                $arrData = array();

                $tmpInput = json_decode($tmpItem['quantity_input'], true);
                $now = new \Nette\Utils\DateTime();
                $tmpInput[] = array('date' => $now,
                                    'user' => $this->user->getIdentity()->name,
                                    'quantity' => $this->srchCount[$one['ean_code']]);
                $arrData['quantity_input']  = json_encode($tmpInput);
                //bdump($tmpInput);
                $count = 0;
                foreach($tmpInput as $oneQuant)
                {
                    $count = $count + $oneQuant['quantity'];
                }
                $arrData['quantity_real'] = $count;
                if ($arrData['quantity_real'] == $tmpItem['quantity'])
                    $arrData['finished'] = 1;
                else
                    $arrData['finished'] = 0;

                $tmpItem->update($arrData);
            }

        }
        $this->arrSrch = [];
        $this->srchCount = [];
        $this->srchData = [];
        $this->redrawControl('searchresult');
        $this->redrawControl('inventorycontent');
    }
	
	public function getWorkPlaceCount($arr)
	{
		$total = $this->InventoryWorkplacesManager->findAll()->where('cl_inventory_id = ?', $arr['id'])->count('id');
		return $total;
	}
    
    public function getTotalCount($arr)
    {
        $total = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ?', $arr['id'])->count('id');
        return $total;
    }

    public function getFinishedCount($arr)
    {
        $total = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ? AND FINISHED = 1 ', $arr['id'])->count('id');
        return $total;
    }

    public function getOnStoreCount($arr)
    {
        $total = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ? AND on_store = 1 ', $arr['id'])->count('id');
        return $total;
    }

    public function getToStoreCount($arr)
    {
        $total = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ? AND on_store = 0 AND finished = 1 AND  quantity != quantity_real', $arr['id'])->count('id');
        return $total;
    }

    public function getTotal($arr)
    {
        $item = $this->InventoryItemsManager->find($arr['id']);
        $arrJson = json_decode($item->quantity_input, true);
        $str = "";
        $count = 0;
        if (!is_null($arrJson)) {
            foreach ($arrJson as $one) {
                $count += $one['quantity'];
            }
        }
        $str = number_format($count,2, '.', ' ');
        return $str;
    }

    public function getHistory($arr)
    {

        $item = $this->InventoryItemsManager->find($arr['id']);
        $arrJson = json_decode($item->quantity_input, true);
        $str = "";
        if (!is_null($arrJson)) {

            foreach ($arrJson as $one) {
                $tmpDate = date('d.m.Y h:i:s', strtotime($one['date']));
                $str .= " " . $tmpDate."<br>";
                $str .= " " . $one['user'] . " :";
                $str .= " " . $one['quantity']."<br>" ;
            }
        }
        return $str;
    }

    /**Data processing before insert/update on listgrid
     * @param $arrData
     * @return mixed
     */
    public function DataProcessListGrid($arrData)
    {
	
		if (!isset($arrData['qr'])){
			return $arrData;
		}
        unset($arrData['input_history']);
        unset($arrData['qr']);
        unset($arrData['on_store']);
        unset($arrData['pricelistquantity']);
        
        //bdump($arrData);
        //if ($arrData['quantity_real'] == 0)
        //{
           // $arrData['quantity_input'] = "";
        //}else {

            if ($oldData = $this->InventoryItemsManager->find($arrData['id'])) {
                //$diff = $arrData['quantity_real'] - $oldData['quantity_real'];
                //if ($diff != 0) {
                    $tmpInput = json_decode($oldData['quantity_input'], true);
                    if ($arrData['quantity_real'] != 0) {
                        $now = new \Nette\Utils\DateTime();
                        $tmpInput[] = ['date' => $now,
                            'user' => $this->user->getIdentity()->name,
                            'quantity' => $arrData['quantity_real']];
                    }
                    $arrData['quantity_input'] = json_encode($tmpInput);

                //bdump($tmpInput);
                $count = 0;
                if (!is_null($tmpInput)) {
                    foreach ($tmpInput as $oneQuant) {
                        $count = $count + $oneQuant['quantity'];
                    }
                }
                $arrData['quantity_real'] = $count;

                //}
            }
        //}
        if ($arrData['quantity_real'] == $arrData['quantity'])
        {
            $arrData['finished'] = 1;
        }

        return $arrData;
    }


    /**find partner from cl_partners_book with the same name as main company
     * if there is no one, create it
     * @return mixed|\Nette\Database\Table\ActiveRow|null
     */
    private function getOwnPartner()
    {
        $companyId = NULL;
        if ($tmpPartner = $this->PartnersManager->findAll()->where('company LIKE ?', '%'.$this->settings->name.'%')->fetch())
        {
            $companyId = $tmpPartner->id;
        }else{
            $arrPartner = ['company' => $this->settings->name];
            $tmpPartner = $this->PartnersManager->insert($arrPartner);
            $companyId = $tmpPartner->id;
        }
        return $companyId;
    }

    public function handleStoreRepair()
    {
        $tmpInventory = $this->DataManager->find($this->id);
        //1. create income
        //$tmpData = $this->InventoryItemsManager->findAll()->select('SUM(cl_iventory_items.quantity) AS quantity, SUM(cl_iventory_items.quantity_real) AS quantity_real_sum, quantity_real, finished, on_store')->
//                                        where('cl_inventory_id = ? AND finished = 1 AND on_store = 1', $this->id)->
//                                        group('cl_store.cl_pricelist_id, cl_store.cl_storage_id');

        //2. create outgoing
        $tmpData = $this->InventoryItemsManager->findAll()->select('SUM(cl_inventory_items.quantity) AS quantity, SUM(quantity_real) AS quantity_real_sum, quantity_real,
                                                                            finished, on_store, cl_store.cl_pricelist_id, cl_store_id, cl_store.cl_storage_id, cl_store.cl_pricelist.vat, cl_store.cl_pricelist.price_s')->
                                        where('cl_inventory_id = ? AND finished = 1 AND on_store = 1', $this->id)->
                                        group('cl_store.cl_pricelist_id, cl_store.cl_storage_id');

        if (count($tmpData) > 0) {
            $companyId = $this->getOwnPartner();
            $doc_date = new DateTime();
            $tmpDocsIn = $this->StoreDocsManager->ApiCreateDoc(['cl_company_id' => $this->settings->id,
                'cl_company_branch_id' =>  $this->user->getIdentity()->cl_company_branch_id,
                'doc_date' => $doc_date,
                'cl_partners_book_id' => $companyId,
                'currency_code' => $this->settings->cl_currencies->currency_code,
                'cl_storage_id' => $tmpInventory->cl_storage_id,
                'doc_title' => $this->translator->translate('příjem_z_inventury_oprava'),
                'doc_type' => 'store_in',
                'create_by' => $doc_date]);

            $tmpDocsOut = $this->StoreDocsManager->ApiCreateDoc(['cl_company_id' => $this->settings->id,
                'cl_company_branch_id' =>  $this->user->getIdentity()->cl_company_branch_id,
                'doc_date' => $doc_date,
                'cl_partners_book_id' => $companyId,
                'currency_code' => $this->settings->cl_currencies->currency_code,
                'cl_storage_id' => $tmpInventory->cl_storage_id,
                'doc_title' => $this->translator->translate('výdej_z_inventury_oprava'),
                'doc_type' => 'store_out',
                'create_by' => $doc_date]);

            /*$actualQuantity = $this->StoreManager->findAll()->
                                        where('id = ? AND cl_storage_id = ? AND cl_pricelist_id = ?', $one->cl_store_id, $one->cl_store->cl_storage_id, $one->cl_store->cl_pricelist_id)->
                                        sum('quantity');*/
                //$difference = $one->quantity_real - ($one->quantity_real_sum - $one->quantity);
            session_write_close();
            $maxCount = count($tmpData);
            $counter = 0;
            foreach ($tmpData as $key => $one) {
                $this->UserManager->setProgressBar( $counter, $maxCount, $this->user->getId(), $this->translator->translate('Příjem_a_výdej'));
                if ($one->quantity_real_sum > $one->quantity_real && !is_null($one->cl_store_id) && !is_null($one->cl_pricelist_id)) {

                    //if ($one->quantity >= 0) {
//                        $difference = $one->quantity_real - ($one->quantity_real_sum - $one->quantity);
//                    }else{
                        $difference = $one->quantity_real - ($one->quantity_real_sum - $one->quantity) - $one->quantity;
                    //}

                    if ($difference < 0){
                        $data = array();
                        $data['cl_store_docs_id'] = $tmpDocsOut->id;
                        $data['cl_pricelist_id'] = $one->cl_pricelist_id;
                        $data['cl_store_id'] = $one->cl_store_id;
                        $data['cl_storage_id'] = $one->cl_storage_id;
                        $data['s_in'] = 0;
                        $data['s_end'] = 0;
                        $data['s_out'] = 0;
                        $data['s_total'] = 0;
                        $data['price_e2'] = 0;
                        $data['price_e2_vat'] = 0;
                        $data['price_s'] = 0;
                        $data['cl_store_id'] = NULL;
                        $data['cl_invoice_items_id'] = NULL;
                        $data['cl_invoice_items_back_id'] = NULL;
                        unset($data['id']);

                        $tmpPrice = $this->PricesManager->getPrice($tmpDocsOut->cl_partners_book,
                            $one->cl_pricelist_id,
                            $tmpDocsOut->cl_currencies_id,
                            $tmpInventory->cl_storage_id);
                        if ($this->settings->platce_dph == 1) {
                            $data['price_e'] = $tmpPrice['price'];
                            $data['vat'] = $one->vat;
                        } else {
                            $data['price_e'] = $tmpPrice['price_vat'];
                            $data['vat'] = 0;
                        }
                        $data['price_e2'] = $tmpPrice['price'];
                        $data['price_e2_vat'] = $tmpPrice['price_vat'];

                        $row = $this->StoreMoveManager->insert($data);
                        $data['id'] = $row->id;
                        $data['s_in'] = 0;
                        $data['s_end'] = 0;
                        $data['s_out'] = abs($difference);
                        $data['s_out_fin'] = 0;
                        //$this->StoreOutManager->findBy(array('cl_store_move_id' => $data['id']))->sum('s_out');
                        $data['description']        = $this->translator->translate('oprava_inventury');

                        $data2 = $this->StoreManager->GiveOutStore($data, $row, $tmpDocsOut);
                        $this->StoreMoveManager->update($data2);
                        //23.07.2021 - removed because VAP should be changed only on income
                        //$this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocsOut['doc_date']);
                    }else{
                        $data = array();
                        $data['cl_store_docs_id']	= $tmpDocsIn->id;
                        $data['cl_pricelist_id']    = $one->cl_pricelist_id;
                        $data['cl_store_id']        = $one->cl_store_id;
                        $data['cl_storage_id']      = $one->cl_storage_id;
                        $data['s_in']               = 0;
                        $data['s_end']              = 0;
                        $data['s_out']              = 0;
                        $data['price_e2']	        = 0;
                        $data['price_e2_vat']       = 0;
                        $data['vat']                = $one->vat;
                        $data['price_in']	        = $one->price_s;
                        $data['price_in_vat']       = $one->price_s*(1+($one->vat/100));

                        $row = $this->StoreMoveManager->insert($data);
                        $data['id']	                = $row->id;
                        $data['s_in']	            = abs($difference);
                        $data['s_end']	            = abs($difference);
                        $data['s_out_fin'] = 0;
                        //$this->StoreOutManager->findBy(array('cl_store_move_id' => $data['id']))->sum('s_out');
                        $data['description']        = $this->translator->translate('oprava_inventury');

                        $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocsIn);
                        $this->StoreMoveManager->update($data2);
                        //Debugger::fireLog($data2['cl_store_id']);
                        $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocsIn['doc_date']);
                        //$one->update(array('on_store' => 1));
                        //$this->StoreManager->UpdateSum($tmpDocs->id);
                    }

                    //$one->update(array('on_store' => 1));
                }
                $counter++;
            }
            $this->UserManager->resetProgressBar( $this->user->getId());
            $this->StoreManager->UpdateSum($tmpDocsIn->id);
            $this->StoreManager->UpdateSum($tmpDocsOut->id);

        }

        $this->redrawControl('searchresult');
        $this->redrawControl('inventorycontent');





    }

    public function handleStore()
    {
        $tmpInventory = $this->DataManager->find($this->id);
        //1. create income
        $tmpData = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ? AND finished = 1 AND quantity_real > quantity AND on_store = 0', $this->id);
        if (count($tmpData) > 0)
        {
            $companyId = $this->getOwnPartner();
            $doc_date = new DateTime();
            $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(['cl_company_id' => $this->settings->id,
																	'cl_company_branch_id' =>  $this->user->getIdentity()->cl_company_branch_id,
                                                                    'doc_date' => $tmpInventory->date ,
                                                                    'cl_partners_book_id' => $companyId,
                                                                    'currency_code' => $this->settings->cl_currencies->currency_code,
                                                                    'cl_storage_id' => $tmpInventory->cl_storage_id,
                                                                    'doc_title' => $this->translator->translate('příjem_z_inventury_'),
                                                                    'doc_type' => 'store_in',
                                                                    'create_by' => $doc_date]);

            $itemOrder = 1;
            foreach($tmpData as $key => $one)
            {
                $data = [];
                $data['item_order']         = $itemOrder;
                $data['cl_store_docs_id']	= $tmpDocs->id;
                $data['cl_pricelist_id']    = $one->cl_pricelist_id;
                //$data['cl_store_id']        = $one->cl_store_id;
                $data['cl_storage_id']      = $tmpInventory->cl_storage_id;
                $data['s_in']               = 0;
                $data['s_end']              = 0;
                $data['s_out']              = 0;
                $data['price_e2']	        = 0;
                $data['price_e2_vat']       = 0;
                $data['vat']                = $one->cl_pricelist->vat;
                $data['price_in']	        = $one->cl_pricelist->price_s;
                $data['price_in_vat']       = $one->cl_pricelist->price_s*(1+($one->cl_pricelist->vat/100));
                $data['s_out_fin'] = 0;
                //$this->StoreOutManager->findBy(array('cl_store_move_id' => $data['id']))->sum('s_out');

                $row = $this->StoreMoveManager->insert($data);
                $data['id']	                = $row->id;
                $data['s_in']	            = $one->quantity_real - $one->quantity;
                $data['s_end']	            = $one->quantity_real - $one->quantity;
                $data['description']        = $this->ArraysManager->getInventoryDifferenceName($one->difference);
                $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocs);
                $this->StoreMoveManager->update($data2);
                //Debugger::fireLog($data2['cl_store_id']);
                $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
                $one->update(['on_store' => 1]);
                $itemOrder++;
            }
            $this->StoreManager->UpdateSum($tmpDocs->id);

        }

        //2. create outgoing
        $tmpData = $this->InventoryItemsManager->findAll()->where('cl_inventory_id = ? AND finished = 1 AND quantity_real < quantity AND on_store = 0', $this->id);
        if (count($tmpData) > 0) {
            $companyId = $this->getOwnPartner();
            $doc_date = new DateTime();
            $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(['cl_company_id' => $this->settings->id,
																	'cl_company_branch_id' =>  $this->user->getIdentity()->cl_company_branch_id,
																	'doc_date' => $tmpInventory->date ,
																	'cl_partners_book_id' => $companyId,
																	'currency_code' => $this->settings->cl_currencies->currency_code,
																	'cl_storage_id' => $tmpInventory->cl_storage_id,
																	'doc_title' => $this->translator->translate('výdej_z_inventury'),
																	'doc_type' => 'store_out',
																	'create_by' => $doc_date]);

            $itemOrder = 1;
            foreach ($tmpData as $key => $one) {
                $data = [];
                $data['item_order']                 = $itemOrder;
                $data['cl_store_docs_id']	        = $tmpDocs->id;
                $data['cl_pricelist_id']            = $one->cl_pricelist_id;
                //$data['cl_store_id']                = $one->cl_store_id;
                $data['cl_storage_id']	            = $tmpInventory->cl_storage_id;
                $data['s_in']		                = 0;
                $data['s_end']		                = 0;
                $data['s_out']		                = 0;
                $data['s_total']		            = 0;
                $data['price_e2']		            = 0;
                $data['price_e2_vat']	            = 0;
                $data['price_s']		            = 0;
                $data['cl_store_id']		        = NULL;
                $data['cl_invoice_items_id']	    = NULL;
                $data['cl_invoice_items_back_id']	= NULL;
                unset($data['id']);

                $tmpPrice = $this->PricesManager->getPrice($tmpDocs->cl_partners_book,
                                                                $one->cl_pricelist_id,
                                                                $tmpDocs->cl_currencies_id,
                                                                $tmpInventory->cl_storage_id);

                if ($this->settings->platce_dph == 1){
                    $data['price_e']    = $tmpPrice['price'];
                    $data['vat']	    = $one->cl_pricelist->vat;
                }else{
                    $data['price_e']    = $tmpPrice['price_vat'];
                    $data['vat']	    = 0;
                }
                //$data['price_e']        = $one->cl_pricelist['price_s'];

                $data['price_e2']	    = $tmpPrice['price'];
                $data['price_e2_vat']   = $tmpPrice['price_vat'];

                $row = $this->StoreMoveManager->insert($data);
                $data['id']		= $row->id;
                $data['s_in']	= 0;
                $data['s_end']	= 0;
                $data['s_out']	= $one->quantity - $one->quantity_real;
                $data['s_out_fin'] = 0;
                    //$this->StoreOutManager->findBy(array('cl_store_move_id' => $data['id']))->sum('s_out');

                $data2 = $this->StoreManager->GiveOutStore($data,$row,$tmpDocs);
                //sale price set to equal price_s
                $data2['price_e'] = $data2['price_s'];
                $data2['price_e2'] = $data2['price_s'] * $data2['s_out'];
                $data2['profit'] = 0;
                $data2['price_e2_vat'] = $data2['price_e2'] * (1 + ($data2['vat'] / 100));
                $this->StoreMoveManager->update($data2);
                //23.07.2021 - removed because VAP should be changed only on income
                //$this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
                $one->update(['on_store' => 1]);
                $itemOrder++;
            }
            $this->StoreManager->UpdateSum($tmpDocs->id);
        }

        $this->redrawControl('searchresult');
        $this->redrawControl('inventorycontent');

    }

    public function updateSum()
    {
        $this->redrawControl('inventorycontent');
    }

    public function handleShowDiff()
    {
        $this->showDiff = TRUE;
        $this->showOnStore = FALSE;
        $this['inventoryItems']->setFilter(['filter' => 'cl_inventory_items.quantity <> cl_inventory_items.quantity_real']);
        $this->redrawControl('inventorycontent');
    }

    public function handleShowDiff2()
    {
        $this->showDiff = TRUE;
        $this->showOnStore = FALSE;
        $this['inventoryItems']->setFilter(['filter' => 'cl_inventory_items.quantity_real <> (SELECT SUM(cl_store.quantity) FROM cl_store WHERE cl_store.cl_storage_id = cl_inventory.cl_storage_id AND cl_store.cl_pricelist_id = cl_inventory_items.cl_pricelist_id)']);
        $this->redrawControl('inventorycontent');
    }


    public function handleShowOnStore()
    {
        $this->showOnStore = TRUE;
        $this->showDiff = FALSE;
        $this['inventoryItems']->setFilter(['filter' => ['on_store' => 1]]);
        $this->redrawControl('inventorycontent');
    }

    public function handleShowAll()
    {
        $this->showDiff = FALSE;
        $this->showOnStore = FALSE;
        $this['inventoryItems']->setFilter([]);
        $this->redrawControl('inventorycontent');
    }


    public function handleSetAll()
    {
        $tmpItems = $this->InventoryItemsManager->findAll()->where('finished = 0 AND cl_inventory_id = ?', $this->id)->update(array('finished' => '1'));
        //foreach($tmpItems as $key => $one)
        //{
          //  $one->update(array('id' => $key, 'finished' => 1));
        //}

        $this->redrawControl('inventorycontent');
    }

    public function handleGiveOutAll()
    {
        $tmpInventory = $this->DataManager->find($this->id);
        if (!$tmpInventory)
            return;

        Debugger::timer();

        $companyId = $this->getOwnPartner();
        $doc_date = new DateTime();
        $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(['cl_company_id' => $this->settings->id,
            'cl_company_branch_id' =>  $this->user->getIdentity()->cl_company_branch_id,
            'doc_date' => $tmpInventory->date ,
            'cl_partners_book_id' => $companyId,
            'currency_code' => $this->settings->cl_currencies->currency_code,
            'cl_storage_id' => $tmpInventory->cl_storage_id,
            'doc_title' => $this->translator->translate('výdej_z_inventury'),
            'doc_type' => 'store_out',
            'create_by' => $doc_date]);

        $tmpItems = $this->InventoryItemsManager->findAll()->where('cl_pricelist_id IS NOT NULL AND cl_inventory_id = ? AND on_store = 1 AND finished = 1 AND quantity_real <> quantity', $tmpInventory->id);
        session_write_close();
        $counter = 0;
        $counterMax = count($tmpItems);
        foreach($tmpItems as $key => $one) {
                $this->UserManager->setProgressBar($counter, $counterMax, $this->user->getId(), $this->translator->translate('Výdej_do_nuly'));
                /*$tmpSumIn = $this->StoreMoveManager->findAll()->
                                where('cl_pricelist_id = ? AND cl_storage_id = ? AND s_in > 0 AND cl_store_docs.doc_date < ?', $one->cl_pricelist_id, $one->cl_inventory->cl_storage_id, $tmpInventory->date )->
                                sum('s_in');
                $tmpSumOut = $this->StoreMoveManager->findAll()->
                                where('cl_pricelist_id = ? AND cl_storage_id = ? AND s_out > 0 AND cl_store_docs.doc_date < ?', $one->cl_pricelist_id, $one->cl_inventory->cl_storage_id, $tmpInventory->date )->
                                sum('s_out');*/
                $tmpSumIn = $this->StoreMoveManager->findAll()->
                                where('cl_pricelist_id = ? AND cl_store_move.cl_storage_id = ? AND s_in > 0 AND (cl_store_docs.doc_date < ? OR (cl_store_docs.doc_date = ? AND cl_store_docs.cl_sale_id IS NULL AND cl_store_docs.cl_invoice_id IS NULL))', $one->cl_pricelist_id, $one->cl_inventory->cl_storage_id, $tmpInventory->date, $tmpInventory->date )->
                                sum('s_in');
                $tmpSumOut = $this->StoreMoveManager->findAll()->
                                where('cl_pricelist_id = ? AND cl_store_move.cl_storage_id = ? AND s_out > 0 AND (cl_store_docs.doc_date < ? OR (cl_store_docs.doc_date = ? AND cl_store_docs.cl_sale_id IS NULL AND cl_store_docs.cl_invoice_id IS NULL))', $one->cl_pricelist_id, $one->cl_inventory->cl_storage_id, $tmpInventory->date, $tmpInventory->date )->
                                sum('s_out');
                if ($tmpSumIn > $tmpSumOut) {
                    Debugger::log('cl_pricelist_id: ' . dump($one->cl_pricelist_id) . ' identification:' . dump($one->cl_pricelist->identification), 'inventory');
                    Debugger::log('tmpSumIn: ' . dump($tmpSumIn), 'inventory');
                    Debugger::log('tmpSumOut: ' . dump($tmpSumOut), 'inventory');
                    $data = [];
                    $data['item_order']         = $counter + 1;
                    $data['cl_store_docs_id']   = $tmpDocs->id;
                    $data['cl_pricelist_id']    = $one->cl_pricelist_id;
                    //$data['cl_store_id']                = $one->cl_store_id;
                    $data['cl_storage_id']      = $tmpInventory->cl_storage_id;
                    $data['s_in']               = 0;
                    $data['s_end']              = 0;
                    $data['s_out']              = 0;
                    $data['s_total']            = 0;
                    $data['price_e2']           = 0;
                    $data['price_e2_vat']       = 0;
                    $data['price_s']            = 0;
                    $data['cl_store_id']        = NULL;
                    $data['cl_invoice_items_id']        = NULL;
                    $data['cl_invoice_items_back_id']   = NULL;
                    unset($data['id']);

                    $tmpPrice = $this->PricesManager->getPrice($tmpDocs->cl_partners_book,
                        $one->cl_pricelist_id,
                        $tmpDocs->cl_currencies_id,
                        $tmpInventory->cl_storage_id);
                    if ($this->settings->platce_dph == 1) {
                        $data['price_e'] = $tmpPrice['price'];
                        $data['vat'] = $one->cl_pricelist->vat;
                    } else {
                        $data['price_e'] = $tmpPrice['price_vat'];
                        $data['vat'] = 0;
                    }
                    $data['price_e2'] = $tmpPrice['price'];
                    $data['price_e2_vat'] = $tmpPrice['price_vat'];

                    $row = $this->StoreMoveManager->insert($data);
                    $data['id'] = $row->id;
                    $data['s_in'] = 0;
                    $data['s_end'] = 0;
                    $data['s_out'] = $tmpSumIn - $tmpSumOut;
                    $data['s_out_fin'] = 0;

                    $data2 = $this->StoreManager->GiveOutStore($data, $row, $tmpDocs);
                    //sale price set to equal price_s
                    $data2['price_e'] = $data2['price_s'];
                    $data2['price_e2'] = $data2['price_s'] * $data2['s_out'];
                    $data2['profit'] = 0;
                    $data2['price_e2_vat'] = $data2['price_e2'] * (1 + ($data2['vat'] / 100));

                    $this->StoreMoveManager->update($data2);
                    //23.07.2021 - removed because VAP should be changed only on income
                    //$this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
                    $one->update(['on_store' => 1]);


                }
                $counter++;

        }
        $this->UserManager->resetProgressBar( $this->user->getId());
        $this->StoreManager->UpdateSum($tmpDocs->id);
        $timeOutcome = Debugger::timer();

        Debugger::timer();
        //27.02.2020 - giveIn everything what is on inventory
        $companyId = $this->getOwnPartner();
        $doc_date = new DateTime();
        $tmpDocs = $this->StoreDocsManager->ApiCreateDoc(['cl_company_id' => $this->settings->id,
            'cl_company_branch_id' =>  $this->user->getIdentity()->cl_company_branch_id,
            'doc_date' => $tmpInventory->date ,
            'cl_partners_book_id' => $companyId,
            'currency_code' => $this->settings->cl_currencies->currency_code,
            'cl_storage_id' => $tmpInventory->cl_storage_id,
            'doc_title' => $this->translator->translate('příjem_z_inventury'),
            'doc_type' => 'store_in',
            'create_by' => $doc_date]);

        $tmpItems = $this->InventoryItemsManager->findAll()->where('cl_pricelist_id IS NOT NULL AND cl_inventory_id = ? AND on_store = 1 AND finished = 1 AND quantity_real <> quantity', $tmpInventory->id);

        $counter = 0;
        $counterMax = count($tmpItems);
        foreach($tmpItems as $key => $one) {
            if ($one->quantity_real > 0) {
                $this->UserManager->setProgressBar($counter, $counterMax, $this->user->getId(), $this->translator->translate('Příjem_inventury'));
                Debugger::log('cl_pricelist_id: ' . dump($one->cl_pricelist_id) . ' identification:' . dump($one->cl_pricelist->identification), 'inventory');
                Debugger::log('quantity_real: ' . dump($one->quantity_real), 'inventory');

                $data = array();
                $data['item_order']         = $counter + 1;
                $data['cl_store_docs_id']   = $tmpDocs->id;
                $data['cl_pricelist_id']    = $one->cl_pricelist_id;
                //$data['cl_store_id']        = $one->cl_store_id;
                $data['cl_storage_id']      = $tmpInventory->cl_storage_id;
                $data['s_in']               = 0;
                $data['s_end']              = 0;
                $data['s_out']              = 0;
                $data['price_e2']           = 0;
                $data['price_e2_vat']       = 0;
                $data['vat']                = $one->cl_pricelist->vat;
                $data['price_in']           = $one->cl_pricelist->price_s;
                $data['price_in_vat']       = $one->cl_pricelist->price_s * (1 + ($one->cl_pricelist->vat / 100));
                $data['s_out_fin']          = 0;
                //$this->StoreOutManager->findBy(array('cl_store_move_id' => $data['id']))->sum('s_out');

                $row = $this->StoreMoveManager->insert($data);
                $data['id']                 = $row->id;
                $data['s_in']               = $one->quantity_real;
                $data['s_end']              = $one->quantity_real;
                $data['description']        = $this->ArraysManager->getInventoryDifferenceName($one->difference);
                $data2 = $this->StoreManager->GiveInStore($data, $row, $tmpDocs);
                $this->StoreMoveManager->update($data2);
                //Debugger::fireLog($data2['cl_store_id']);
                $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpDocs['doc_date']);
                $one->update(['on_store' => 1]);
            }
            $counter++;
        }
        $this->UserManager->resetProgressBar( $this->user->getId());
        $this->StoreManager->UpdateSum($tmpDocs->id);
        $timeIncome = Debugger::timer();

        Debugger::timer();
        //recalc of everything on storage
        $result = $this->StoreManager->repairBalance(null, $tmpInventory->cl_storage_id);
        Debugger::log('result UpdateSum: ' . dump($result), 'inventory');

        $tmpStoreMain = $this->StoreManager->findAll()->where('cl_storage_id = ?', $tmpInventory->cl_storage_id);
        $result = $this->StoreManager->updateStoreAll($tmpStoreMain);
        $tmpPricelist = $this->StoreManager->findAll()->select('DISTINCT cl_pricelist.id');
        $tmpPricelist = $tmpPricelist->where('cl_store.cl_storage_id = ?', $tmpInventory->cl_storage_id);
        $result = $this->StoreManager->updatePricelistAll($tmpPricelist);
        $timeRecalc = Debugger::timer();

        Debugger::log('result UpdatePricelistAll: ' . dump($result), 'inventory');
        Debugger::log('Outcome runtime(sec): ' . $timeOutcome, 'inventory');
        Debugger::log('Income runtime(sec): ' . $timeIncome, 'inventory');
        Debugger::log('Recalc runtime(sec): ' . $timeRecalc, 'inventory');
        $this->UserManager->resetProgressBar($this->user->getId());

        if (empty($result)) {
            $this->flashMessage($this->translator->translate('Bylo_vydáno_a_přijato'), 'success');
        }else{
            $this->flashMessage($this->translator->translate('Došlo_k_chybě').$result->getMessage(), 'error');
        }
        $this->redrawControl('content');
    }

    public function handleSavePDF($id, $latteIndex = NULL, $arrData = [], $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->prepareData($id);
        //$this->ArraysManager->getInventoryDifference()
        $arrData = [
                        'date' => $tmpData->date,
                        'cl_storage_name' => $tmpData->cl_storage->name . ' ' . $tmpData->cl_storage->description,
                        'cl_pricelist_group' => (!is_null($tmpData->cl_pricelist_group_id) ? $tmpData->cl_pricelist_group->name : ''),
                        'difference' => $this->ArraysManager->getInventoryDifference(),
                        'latteIndex' => $latteIndex];
        return parent::handleSavePDF($id, $latteIndex, $arrData, $noDownload, $noPreview);
    }

    private function prepareData($id)
    {
        return $this->DataManager->find($id);
    }

    public function getRealQuant($arr)
    {
        $str = "Nenalezeno";
        if (!empty($arr['cl_inventory_id'])){
            $tmpIventory = $this->DataManager->find($arr['cl_inventory_id']);
            if (!empty($arr['cl_pricelist_id'])) {
                /*$store = $this->StoreManager->findAll()->
                        select('SUM(cl_store.quantity) AS quantity')->
                        where('cl_store.cl_storage_id = ? AND cl_store.cl_pricelist_id = ?', $tmpIventory['cl_storage_id'], $arr['cl_pricelist_id'])->fetch();*/
                $store = $this->StoreMoveManager->findAll()->
                                select('SUM(s_in - s_out) AS quantity')->
                                where('cl_storage_id = ? AND cl_pricelist_id = ?', $tmpIventory['cl_storage_id'], $arr['cl_pricelist_id'])->fetch();
                if ($store) {
                    $str = $store['quantity'];
                }
            }
        }
        return $str;
    }
}
