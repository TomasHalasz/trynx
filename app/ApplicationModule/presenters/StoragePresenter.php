<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;

class StoragePresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\StorageManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\StoreMoveManager
     */
    public $StoreMoveManager;

    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;

    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\InvoiceItemsBackManager
     */
    public $InvoiceItemsBackManager;

    /**
     * @inject
     * @var \App\Model\InvoiceItemsManager
     */
    public $InvoiceItemsManager;

    /**
     * @inject
     * @var \App\Model\SaleItemsManager
     */
    public $SaleItemsManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteItemsManager
     */
    public $DeliverNoteItemsManager;

    /**
     * @inject
     * @var \App\Model\StoragePlacesManager
     */
    public $StoragePlacesManager;



    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;


    protected function createComponentStoragePlacesGrid()
    {
        $arrData = array(
            'rack' => array($this->translator->translate('Stojan/regál'),'format' => 'text','size' => 30),
            'shelf' => array($this->translator->translate('Police'),'format' => 'text','size' => 30),
            'place' => array($this->translator->translate('Místo'),'format' => "text",'size' => 20));
        return new Controls\ListgridControl(
            $this->translator,
            $this->StoragePlacesManager, //data manager
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
            NULL //orderColumn
        );
    }



    protected function startup()

    {
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.Storage']);

		$this->dataColumns = array(
					   'name' => array($this->translator->translate('Označení_skladu'), 'format' => 'text', 'size' => 10),
					   'description' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 10),
					   'cl_storage.name' => array($this->translator->translate('Nadřazený_sklad'), 'format' => 'text', 'size' => 10),
					   'price_method' => array($this->translator->translate('Metoda'), 'format' => 'text', 'size' => 10, 'arrValues' => $this->ArraysManager->getPriceMethod()),
					   'public' => array($this->translator->translate('Veřejný'),'format' => 'boolean'),
                       'for_return_package' => array($this->translator->translate('Pro_vratné_obaly'),'format' => 'boolean'),
                       'b2b_store' => array($this->translator->translate('Pro_B2B'),'format' => 'boolean'),
					   'email_notification' => $this->translator->translate('Email_pro_zprávy'),
                       'auto_order' => array($this->translator->translate('Automatická_objednávka'), 'format' => 'boolean'),
                       'order_period' => array($this->translator->translate('Dnů_mezi_objednávkami'), 'format' => 'integer'),
					   'order_date' => array($this->translator->translate('Poslední_objednávka'), 'format' => 'date'),
					   'order_day' => array($this->translator->translate('Objednací_den'), 'format' => 'text', 'size' => 10, 'arrValues' => $this->ArraysManager->getDaysOfWeek()),
					   'id__' => array($this->translator->translate('Počet_umístění'), 'format' => 'integer', 'size' => 7, 'function' => 'getStoragePlaces', 'function_param' => array('id')),
					   'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
					   'create_by' => $this->translator->translate('Vytvořil'),
					   'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),
					   'change_by' => $this->translator->translate('Změnil'));
		$this->FilterC = ' ';
		$this->DefSort = 'cl_storage_id,cl_storage.name,name';
		$this->relatedTable = 'cl_storage';
		$this->dataColumnsRelated = array(
						    'name' => array($this->translator->translate('Označení_skladu'), 'format' => 'text', 'size' => 10),
						    'description' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 10),
						    'cl_storage.name' => array($this->translator->translate('Nadřazený_sklad'), 'format' => 'text', 'size' => 10),
						    'price_method' => array($this->translator->translate('Metoda'), 'format' => 'text', 'size' => 10, 'arrValues' => $this->ArraysManager->getPriceMethod()),
						    'public' => array($this->translator->translate('Veřejný'),'format' => 'boolean'),
                            'for_return_package' => array($this->translator->translate('Pro_vratné_obaly'),'format' => 'boolean'),
                            'b2b_store' => array($this->translator->translate('Pro_B2B'),'format' => 'boolean'),
						    'email_notification' => $this->translator->translate('Email_pro_zprávy'),
							'auto_order' => array($this->translator->translate('Automatická_objednávka'), 'format' => 'boolean'),
							'order_period' => array($this->translator->translate('Dnů_mezi_objednávkami'), 'format' => 'integer'),
							'order_date' => array($this->translator->translate('Poslední_objednávka'), 'format' => 'date'),
							'order_day' => array($this->translator->translate('Objednací_den'), 'format' => 'text', 'size' => 10, 'arrValues' => $this->ArraysManager->getDaysOfWeek()),
							'id__' => array($this->translator->translate('Počet_umístění'), 'format' => 'integer', 'size' => 7, 'function' => 'getStoragePlaces', 'function_param' => array('id')),
						    'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
						    'create_by' => $this->translator->translate('Vytvořil'),
						    'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),
						    'change_by' => $this->translator->translate('Změnil'));
		$this->mainFilter = 'cl_storage.cl_storage_id IS NULL';	
		$this->filterColumns = array('name' => 'autocomplete', 'description' => 'autocomplete', 'cl_storage.name' => 'autocomplete', 
				     'email_notification' => 'autocomplete');
		$this->defValues = array();		
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
		$form->addText('name', $this->translator->translate('Označení_skladu'), 20, 20)
			->setRequired($this->translator->translate('Zadejte_prosím_označení_skladu'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Označení_skladu'));
		$form->addText('description', $this->translator->translate('Popis'), 40, 40)
			->setHtmlAttribute('placeholder',$this->translator->translate('Popis_skladu'));
        $form->addCheckbox('for_return_package',$this->translator->translate('Sklad_pro_vratné_obaly'));
        $form->addCheckbox('auto_order',$this->translator->translate('Automatická_objednávka'));
        $form->addCheckbox('b2b_store',$this->translator->translate('Sklad_pro_B2B'));
        $form->addText('order_period', $this->translator->translate('Počet_dní_mezi_objednávkami'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('počet_dnů'))
            ->addConditionOn($form['auto_order'], Form::EQUAL, true)
            ->addRule(Form::NOT_EQUAL, $this->translator->translate('Musí_být_větší_než_0'), '0')
            ->setRequired($this->translator->translate('Zadejte_počet_dní'));


        $form->addText('order_date', $this->translator->translate('Datum_poslední_objednávky'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('datum'));

        $form->addSelect('order_day', $this->translator->translate('Objednací_den'), $this->ArraysManager->getDaysOfWeek())
            ->setHtmlAttribute('placeholder',$this->translator->translate('vyberte_den_objednávky'))
            ->addConditionOn($form['auto_order'], Form::EQUAL, true)
            ->setRequired($this->translator->translate('Objednací_den_musí_být_zvolen'));


		$form->addText('email_notification', $this->translator->translate('Email_pro_zprávy'), 40, 40)
				->setHtmlAttribute('placeholder',$this->translator->translate('Email_pro_zprávy_o_změnách_stavu'))
				->addCondition(Form::FILLED)
				->addRule(Form::EMAIL, $this->translator->translate('Zadaný_email_nemá_platný_formát'));
		
		/*$arrStorage = $this->DataManager->findAll()->where('cl_storage_id IS NULL AND name != ""')->
							select('CONCAT(name," - ",description) AS name,id')->order('name')->fetchPairs('id','name');
		*/
		$arrStorage = $this->DataManager->getStoreTreeNotNested();

		$form->addSelect('cl_storage_id',$this->translator->translate('Nadřazený_sklad'),$arrStorage)
			->setPrompt($this->translator->translate('Žádný'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Nadřazený_sklad'));

		$arrPriceMethod = $this->ArraysManager->getPriceMethod();
		$form->addSelect('price_method',$this->translator->translate('Metoda_výpočtu_skladové_ceny'),$arrPriceMethod)
			->setRequired($this->translator->translate('Metoda_výpočtu_musí_být_zvolena'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Metoda_výpočtu'));
		
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
        $data = $this->removeFormat($data);

        if (!empty($data->id))
		    $this->DataManager->update($data, TRUE);
        else
		    $this->DataManager->insert($data);
	}
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
	$this->redirect('default');
    }	    

    public function getStoragePlaces($arrData)
    {
        $retCnt = $this->StoragePlacesManager->findAll()->where('cl_storage_id = ?', $arrData['id'])->count('id');
        return $retCnt;
    }
	
    public function handlePublicStorage($value)
    {
        if ($this->isAllowed($this->name,'edit')) {
            $arrData = array();
            $arrData['id'] = $this->id;
            $arrData['public'] = $value;
            if ($value == 1) {
                $newToken = '';
                while ($this->DataManager->findAllTotal()->where(array('public' => $newToken))->fetch() || $newToken == '') {
                    $newToken = \Nette\Utils\Random::generate(16, 'A-Za-z0-9');
                }
                $arrData['public_token'] = $newToken;
            } else
                $arrData['public_token'] = "";

            //Debugger::fireLog($arrData);
            $this->DataManager->update($arrData);
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
		$this->redrawControl('publiclink');
    }

    //**erase content of store
    public function handleDeleteContent()
    {
        if ($this->isAllowed($this->name,'erase')) {
            //find all records in cl_store for current storage_id
            //cl_store_move and cl_store_out will be erased due to foreing constraints
            $tmpDel = $this->StoreManager->findAll()->where('cl_storage_id = ?', $this->id);
            foreach ($tmpDel as $key => $one) {
                $this->StoreManager->delete($key);
                //now is neccessary recalculate pricelist item
                $this->StoreManager->updateQuantity(array('cl_pricelist_id' => $one->cl_pricelist_id));
            }
            //then get every empty cl_store_docs where is set cl_storage_id to current id or is NULL and empty
            $tmpDel = $this->StoreDocsManager->findAll()->where('cl_store_docs.cl_storage_id IS NULL OR cl_store_docs.cl_storage_id = ?', $this->id);

            //                                            ->group('cl_store_docs.id')
            //                                          ->having('COUNT(:cl_store_move.id) = 0');
            //bdump($tmpDel);
            foreach ($tmpDel as $key => $one) {
                if (count($one->related('cl_store_move')) == 0)
                    $this->StoreDocsManager->delete($key);

            }
            $this->flashMessage($this->translator->translate('Obsah_skladu_byl_vymazán'), 'success');
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
        $this->redrawControl('content');

    }


    public function handleDeleteToOrder()
    {
        if ($this->isAllowed($this->name,'erase')){
            $tmpData = $this->StoreManager->findAll()->where('cl_storage_id = ?', $this->id);
            foreach ($tmpData as $key => $one) {
                $one->update(array('quantity_to_order' => 0));
            }
            $this->flashMessage($this->translator->translate('Množství_k_objednání_v_další_obratové_objednávce_bylo_vymazáno'), 'success');
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
        $this->redrawControl('content');

    }

    public function handleRecalcStores($id)
    {
        //bdump($id);
        if ($this->isAllowed($this->name,'erase')) {
            $result = $this->StoreManager->repairBalance(null, $id);

            if (empty($result)) {
                $this->flashMessage($this->translator->translate('Bylo_přepočítáno'), 'success');
            } else {
                $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebylo_přepočítáno') . $result->getMessage(), 'error');
            }
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
        $this->redrawControl('content');
    }
	
	public function handleRecalcOnStores($id)
	{
		//bdump($id);
        if ($this->isAllowed($this->name,'erase')) {
            $tmpStoreMain = $this->StoreManager->findAll()->where('cl_storage_id = ?', $id);
            $result = $this->StoreManager->updateStoreAll($tmpStoreMain);
            $tmpPricelist = $this->StoreManager->findAll()->select('DISTINCT cl_pricelist.id');
            $tmpPricelist = $tmpPricelist->where('cl_store.cl_storage_id = ?', $id);
            $result = $this->StoreManager->updatePricelistAll($tmpPricelist);

            if (empty($result)) {
                $this->flashMessage($this->translator->translate('Bylo_přepočítáno'), 'success');
            } else {
                $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebylo_přepočítáno') . $result->getMessage(), 'error');
            }
        }else{
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
		$this->redrawControl('content');
	}

 

}
