<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Contributte\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
    App\Model;
use Tracy\Debugger;

class StorereviewControl extends Control
{
    private $data = NULL;
    private $pricelistId = NULL, $storageId = NULL;
    private $session;
    public $page = 1;
    public $itemId = NULL;
    private $storage_price_in = 0,  $storage_price_out = 0, $storage_price_out2 = 0;

    /** @var \App\Model\StoreManager */
    private $StoreManager;

    /** @var \App\Model\StorageManager */
    private $StorageManager;

    /** @var \App\Model\PriceListManager */
    private $PriceListManager;

    /** @var \App\Model\StoragePlacesManager */
    private $StoragePlacesManager;

    /** @var \App\Model\StoreMoveManager */
    private $StoreMoveManager;

    /** @var \App\Model\StoreOutManager */
    private $StoreOutManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    /** @var Nette\Localization\ITranslator @inject */
    public $translatorMain;

    public function __construct( Translator $translatorMain, Nette\Localization\Translator $translator, \App\Model\StoreManager $storeManager, \App\Model\StoragePlacesManager $storagePlacesManager,
                                \App\Model\StoreMoveManager $storeMoveManager, Nette\Http\Session $session, $priceListManager, \App\Model\StorageManager $storageManager, \App\Model\StoreOutManager $storeOutManager)
    {
        //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->StoreManager         = $storeManager;
        $this->StorageManager       = $storageManager;
        $this->StoreOutManager       = $storeOutManager;
        $this->StoreMoveManager     = $storeMoveManager;
        $this->StoragePlacesManager = $storagePlacesManager;
        $this->PriceListManager     = $priceListManager;
        $this->session              = $session;
        $this->translator           = $translator;
        $this->translatorMain       = $translatorMain;

    }

    protected function startup()
    {
        parent::startup();
        ////$this->translator->setPrefix(['components']);
    }


    protected function createComponentStoreDetailShow()
    {
        return new StoreDetailShowControl($this->translatorMain->createPrefixedTranslator('components.StoreDetailShow'),
                                            $this->StoreManager, $this->PriceListManager);
    }

    protected function createComponentStorePlaceShow()
    {
        return new StorePlaceShowControl($this->translatorMain->createPrefixedTranslator('components.StorePlaceShow'),
                                        $this->StoreManager, $this->PriceListManager, $this->StoreMoveManager, $this->StoragePlacesManager);
    }


    public function render($cl_storage_id = NULL, $search_txt = "", $public_token = NULL, $show_minimum = false, $show_not_active = false)
    {
        $section = $this->session->getSection('storereview_data');
        $this->data['cl_storage_id']    = $cl_storage_id;
        $this->data['search_txt']       = $search_txt;
        $this->data['public_token']     = $public_token;
        $this->data['show_minimum']     = $show_minimum;
        $this->data['show_not_active']     = $show_not_active;

        $this->template->show_not_active = $show_not_active;
        $this->template->storeSum['storage_price_in'] = $this->storage_price_in;
        $this->template->storeSum['storage_price_out'] = $this->storage_price_out;
        $this->template->storeSum['storage_price_out2'] = $this->storage_price_out2;

        $this->template->setTranslator($this->translator);
		$this->template->search_txt = $this->data['search_txt'];
		//bdump($this->data);
		$this->template->setFile(__DIR__ . '/Storereview.latte');
		$this->template->settings = $this->presenter->settings;

    /*    $tmpData = $this->StoreManager->findAll()->
                        select('SUM(:cl_store_move.s_in - :cl_store_move.s_out) AS quantity_x, cl_pricelist.id AS cl_pricelist_id, cl_pricelist_limits.quantity_min, cl_pricelist_limits.quantity_req, cl_store.exp_date, cl_store.price_s, cl_store.cl_storage_id')->
                        group('cl_pricelist.id'); */

        $tmpData = $this->StoreMoveManager->findAll()->
            select('SUM(s_in - s_out) AS quantity_x, cl_store_move.cl_pricelist_id AS cl_pricelist_id, cl_store.cl_pricelist_limits.quantity_min, cl_store.cl_pricelist_limits.quantity_req, cl_store.exp_date, cl_store.price_s, cl_store.cl_storage_id')->
            group('cl_pricelist.id');


		if (!is_null($this->data['cl_storage_id'])) {
            $this->template->storage = $this->StorageManager->findAll()->where('id = ?', $this->data['cl_storage_id'])->fetch();
            $tmpData = $tmpData->where('cl_store.cl_storage_id = ?', $this->data['cl_storage_id']);
        }else{
            $this->template->storage = false;
        }

        if (!$this->data['show_not_active']){
            $tmpData = $tmpData->where('cl_pricelist.not_active = 0');
        }else{
            $tmpData = $tmpData->where('cl_pricelist.not_active = 1');
        }

		if (!$this->data['search_txt'] == '') {
            /*$tmpData = $tmpData->where('MATCH(cl_pricelist.identification, cl_pricelist.item_label) AGAINST (? IN BOOLEAN MODE) OR cl_pricelist.cl_partners_book.company LIKE ?', $this->data['search_txt'], '%' . $this->data['search_txt'] . '%')->
                                                order('5 * MATCH(cl_pricelist.identification) AGAINST (?) + MATCH(cl_pricelist.item_label) AGAINST (?) DESC', $this->data['search_txt'], $this->data['search_txt']);*/
            $tmpData = $tmpData->where('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ?  OR cl_pricelist.cl_partners_book.company LIKE ?', '%' . $this->data['search_txt'] . '%', '%' . $this->data['search_txt'] . '%', '%' . $this->data['search_txt'] . '%')->
                                        order('cl_pricelist.item_label');

        } else {
            $tmpData = $tmpData->order('cl_pricelist.item_label');
        }



		if (isset($this->data['show_minimum']) && $this->data['show_minimum']) {
            $tmpData = $tmpData->having('cl_store.cl_pricelist_limits.quantity_min > SUM(cl_store.quantity)');
        }
		$this->template->data = $tmpData;

		//paginator start
		$paginator = new \Nette\Utils\Paginator;
		$ItemsOnPage = 40;

		$paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
		$totalItems = $this->template->data->count();

		$paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
		$pages = ceil($totalItems / $ItemsOnPage);
		if (is_null($this->page))
            $this->page = 1;
		if ($this->page > $pages)
            $this->page = $pages;

		$paginator->setPage($this->page);

		$this->template->paginator = $paginator;
		$steps = array();
		for ($i = 1; $i <= $pages; $i++) {
            $steps[] = $i;
        }
		$this->template->steps = $steps;
		$this->template->data = $this->template->data->limit($paginator->getLength(), $paginator->getOffset());


		$this->template->exp_on = $this->presenter->settings->exp_on;
		$this->template->batch_on = $this->presenter->settings->batch_on;
		if ($this->StoragePlacesManager->findAll()->count() > 0) {
            $this->template->storage_places_on = TRUE;
        } else {
            $this->template->storage_places_on = FALSE;
        }


		$this->template->public_token = $this->data['public_token'];

		//bdump($this->template->storeData);
		$this->template->render();
	}

    public function handleNewPage($page_lg)
    {
        $this->page = $page_lg;
        //bdump($this->data);
        //$this->redrawControl('baselist');

        $this->presenter->redrawControl('storageList');
        $this->redrawControl('paginator');
        $this->redrawControl('tableLines');
    }


    public function handleShowStoreValues($storageIdShow){

        $tmpStoreIn = $this->StoreMoveManager->findAll()->select('SUM(cl_store_move.price_s * s_in) AS price_s')->where('s_in > 0 AND cl_store_move.cl_storage_id = ? AND cl_store_docs.doc_date <= NOW()',$storageIdShow)->limit(1)->fetch();
        $tmpStoreOut = $this->StoreOutManager->findAll()->select('SUM(cl_store_out.price_s * cl_store_out.s_out) AS price_s')->where('cl_store_move.s_in = 0 AND cl_store_move.s_out > 0 AND cl_store_move.cl_storage_id = ?', $storageIdShow)->limit(1)->fetch();
        $tmpStoreOut2 = $this->StoreMoveManager->findAll()->select('SUM(cl_store_move.price_s * s_out) AS price_s')->where('s_out > 0 AND s_in = 0 AND cl_store_move.cl_storage_id = ? AND cl_store_docs.doc_date <= NOW()', $storageIdShow)->limit(1)->fetch();
        $this->storage_price_in = $tmpStoreIn['price_s'];
        $this->storage_price_out = $tmpStoreOut['price_s'];
        $this->storage_price_out2 = $tmpStoreOut2['price_s'];
        $this->redrawControl('storeSum');

    }

    public function handleShowExp($pricelistId, $storageId)
    {
        //$this->storageId = $storageId;
        //$this->pricelistId = $pricelistId;
        //$this->redrawControl('expirations');
        $this['storeDetailShow']->showDetail($pricelistId, $storageId);

    }

    public function handleShowBatch($storeId)
    {
        $this->presenter->showModal('showBatchModal');
    }

    public function handleShowPlace($pricelistId, $storageId)
    {
        $this['storePlaceShow']->showDetail($pricelistId, $storageId);
    }

    public function handleStoreMoveIn($cl_pricelist_id, $cl_storage_id){
        $mySection = $this->session->getSection('storemove');
        $mySection['cl_storage_id'] = $cl_storage_id;
        $mySection['cl_pricelist_id'] = $cl_pricelist_id;
        $mySection['doc_type'] = 0;
        $this->presenter->redirect(':Application:StoreMoveIn:default', ['modal' => TRUE]);
    }

    public function handleStoreMoveOut($cl_pricelist_id, $cl_storage_id){
        $mySection = $this->session->getSection('storemove');
        $mySection['cl_storage_id'] = $cl_storage_id;
        $mySection['cl_pricelist_id'] = $cl_pricelist_id;
        $mySection['doc_type'] = 1;
        $this->presenter->redirect(':Application:StoreMoveOut:default', ['modal' => TRUE]);
    }



}
