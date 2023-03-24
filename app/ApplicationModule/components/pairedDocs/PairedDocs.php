<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class PairedDocsControl extends Control
{

    /** @persistent */
    public $id;

    /** @var \App\Model\Base*/
    private $dataManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    /** @var \App\Model\Base*/
    private $PairedDocsManager;
    
    
    public $pairedDocsShow;
    
    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct($dataManager,$parent_id, \App\Model\PairedDocsManager $pairedDocsManager, Nette\Localization\Translator $translator )
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->dataManager = $dataManager;
        $this->id = $parent_id;
        $this->PairedDocsManager = $pairedDocsManager;
        $this->translator = $translator;
    }        

    public function action()
    {
	
    }
    public function render()
    {
        //10.07.2018 - this is have to do solution for situation when we need redraw control without redrawing whole content snippet.
        //in this case we don't get property from paren template {control pairedDocs $data->id}
        //so we define default value NULL and use $id only if it is not NULL
        //if (!is_null($id))
        //{
          //  $this->id = $id;
        //}
       $this->template->setFile(__DIR__ . '/PairedDocs.latte');

       $mainTableName			        = $this->dataManager->getTableName() . '_id';
       $this->template->mainTableName	= $mainTableName;
       $this->template->dataDocs        = $this->PairedDocsManager->findBy([$mainTableName => $this->id])->
                                                order('created DESC,cl_invoice_id,cl_commission_id,cl_offer_id,cl_store_docs_id,cl_delivery_note_id,cl_sale_id,cl_cash_id,cl_transport_id');

        //bdump($this->template->dataDocs);
        //$this->template->pairedDocsShow = $pairedDocsShow;
        $this->template->render();
    }
    
    
    public function handleDeletePaired($id,$type)
    {
        if ($type=='cl_store_docs')
        {
            if ($data = $this->dataManager->find($id))
            {
            $data->update(['id' => $id, 'cl_store_docs_id' => NULL]);
            $this->flashMessage($this->translator->translate('Vazba_na_výdejku_byla_zrušena._Výdejka_však_stále_existuje.'),'success');
            }
        }elseif ($type=='cl_invoice')
        {
            if ($data = $this->dataManager->find($id))
            {
            $data->update(['id' => $id, 'cl_invoice_id' => NULL]);
            $this->flashMessage($this->translator->translate('Vazba na fakturu byla zrušena. Faktura však stále existuje.'),'success');
            }
        }
        //$this->pairedDocsShow = TRUE;
        //$this->redirect('this', array('id'=>$id));
        $this->redrawControl('docs');
        $this->parent->redrawControl('baselistArea');	    //main listgrid
        $this->parent->redrawControl('baselist');	    //main listgrid
        $this->parent->redrawControl('baselistScripts');    //scripts needed for listgrid
	
    }
	        

        

}

