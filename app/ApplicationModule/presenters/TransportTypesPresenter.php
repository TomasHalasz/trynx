<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class TransportTypesPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\TransportTypesManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;


    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $PriceListManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.TransportTypes']);
        $this->dataColumns = array('name' => $this->translator->translate('Název_dopravy'),
                       'description' => $this->translator->translate('Popis'),
                       'transport' => array($this->translator->translate('Dopravce'), FALSE, 'function' => 'getTransportTypeName'),
                       'no_insert'   => array($this->translator->translate('Nevkládat_automaticky'), 'format' => 'boolean'),
                       'deactive'   => array($this->translator->translate('Neaktivní'), 'format' => 'boolean'),
                        'cl_pricelist.item_label' => array($this->translator->translate('Vázaná_položka_ceníku'), 'format' => 'text'),
                        'package_type' => array($this->translator->translate('Typ_balíku'), 'format' => 'text', 'arrValues' => $this->ArraysManager->getPackageTypes()),
                        'package_descr' => array($this->translator->translate('Popis_balíku'), 'format' => 'text'),
                       'price_km' => array($this->translator->translate('Cena_za_kilometr'), 'format' => 'number'),
                       'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
                       'create_by' => $this->translator->translate('Vytvořil'),
                       'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),
                       'change_by' => $this->translator->translate('Změnil'));
        $this->FilterC = ' ';
        $this->DefSort = 'name';
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
		$form->addText('name', $this->translator->translate('Název_dopravy'), 40, 60)
			->setRequired($this->translator->translate('Zadejte_prosím_název_dopravy'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_dopravy'));
		$form->addText('description', $this->translator->translate('Popis'), 80, 250)
			->setHtmlAttribute('placeholder',$this->translator->translate('Popis_dopravy'));
        $form->addText('package_descr', $this->translator->translate('Popis_balíku'), 80, 120)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Popis_balíku'));
        $form->addText('price_km', $this->translator->translate('Cena_za_kilometr'), 15, 15)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Cena_za_kilometr'));

        $form->addSelect('package_type', $this->translator->translate('Typ_balíku'), $this->ArraysManager->getPackageTypes())
                ->setPrompt( $this->translator->translate('Vyberte_typ_balíku'));
		$form->addCheckbox('deactive', $this->translator->translate('Neaktivní'));
        $form->addCheckbox('no_insert', $this->translator->translate('Nevkládat_faktury_automaticky'));

		$form->addSelect('transport', $this->translator->translate('Dopravce'), $this->getTransportTypes());

        $arrPricelist = $this->PriceListManager->findAll()->select('id, CONCAT(identification, " " , item_label) AS item_label')->order('item_label')->fetchPairs('id', 'item_label');
        $form->addSelect('cl_pricelist_id', $this->translator->translate('Vázaná_položka_ceníku'), $arrPricelist)
            ->setPrompt( $this->translator->translate('Vyberte_položku_ceníku'));
		$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
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
        //dump($data);
        //	die;
            if ($form['send']->isSubmittedBy())
        {
            //dump($data->id);
            //die;
            if (!empty($data->id))
            $this->DataManager->update($data);
            else
            $this->DataManager->insert($data);
        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        $this->redirect('default');
    }	    

    public function getTransportTypes()
    {
       return $this->ArraysManager->getTransportTypes();
    }

    public function getTransportTypeName($type)
    {
       return $this->ArraysManager->getTransportTypeName($type);
    }

}
