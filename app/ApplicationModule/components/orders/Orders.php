<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class OrdersControl extends Control
{

    private $showData,$displayName,$templateFile;
    /** @var \App\Model\Base*/
    private $PartnersEventManager;
	
	/** @var \App\Model\Base*/
	private $ArraysManager;

    /** @var Nette\Database\Context */
    public $StoreManager;
    /** @var Nette\Database\Context */
    public $StorageManager;
    /** @var Nette\Database\Context */
    public $OrderManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;



    public function __construct($showData, $displayName, $templateFile, \App\Model\StoreManager $StoreManager, \App\Model\StorageManager $StorageManager,
                                \App\Model\OrderManager $OrderManager, \App\Model\ArraysManager $ArraysManager, Nette\Localization\Translator $translator)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData = $showData;
        $this->displayName = $displayName;
        $this->templateFile = $templateFile;
        $this->StoreManager = $StoreManager;
        $this->StorageManager = $StorageManager;
        $this->OrderManager = $OrderManager;
		$this->ArraysManager = $ArraysManager;
        $this->translator = $translator;
	
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
		//$this->MessagesManager->findAll()->where('closed = ?',0)->count();	
        // required to enable form access in snippets
        //$this->template->_form = $this['eventForm'];	
        $this->template->data = $this->showData;
        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->presenter->settings;
        $this->template->render();
    }


    public function handleGenAutoOrder($cl_storage_id)
    {
        if ($tmpData = $this->StorageManager->find($cl_storage_id))
        {
            $now = new \Nette\Utils\DateTime;
            if (!is_null($tmpData->order_date))
            {
                $data['date_from'] = $tmpData->order_date;
            }else {
                $data['date_from'] = $now->modifyClone('-' . $tmpData->order_period . ' days');
            }
            $data['date_to'] = $now->modifyClone('-1 days');
            //bdump($data);
            $dataMove = $this->StoreManager->getMovesPeriod($data['date_from'], $data['date_to'], array($cl_storage_id), array(), TRUE);
			$dataMove = $this->ArraysManager->select2array($dataMove);
			//add cl_store.quantity_to_order
            $dataMove = $this->StoreManager->addToOrder($dataMove);
			$dataMove = $this->OrderManager->prepareDataMove($dataMove);

            $tmpDate_from   = date('d.m.Y', strtotime($data['date_from']));
            $tmpDate_to     = date('d.m.Y', strtotime($data['date_to']));
            if (count($dataMove) > 0) {
                $arrDocId = $this->OrderManager->createOrder($dataMove, $this->translator->translate('objednávka_dle_pohybů_od_') . $tmpDate_from . ' do ' . $tmpDate_to,NULL, array(NULL), $cl_storage_id);
                foreach($arrDocId as $one)
                {
                    $this->OrderManager->update(array('id' => $one, 'cl_storage_id' => $cl_storage_id));
                }
                foreach($dataMove as $key => $one)
                {
                    $this->StoreManager->update(array('id' => $one['cl_store_id'], 'quantity_to_order' => 0, 'supplier_sum' => 0));
                }
                $tmpData->update(array('order_date' => $now));
                $this->flashMessage($this->translator->translate('Objednávky_byly_vytvořeny.'), 'success');
            } else{
                $this->flashMessage($this->translator->translate('Objednávky_nebyly_vytvořeny.'), 'success');
            }
        }

        $this->redrawControl('orderscontent');


    }


       

}