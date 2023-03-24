<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class PaymentTypesPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\PaymentTypesManager
    */
    public $DataManager;    

 
    protected function startup()
    {
	    parent::STARTUP();
        //$this->translator->setPrefix(['applicationModule.PaymentTypes']);
        $this->mainTableName = 'cl_payment_types';
        $this->dataColumns = array('name' => $this->translator->translate('Název_platby'),
                       'description' => $this->translator->translate('Popis'),
                       'short_desc' => $this->translator->translate('Zkratka'),
                       'payment_type' => array($this->translator->translate('Druh_platby'), FALSE, 'function' => 'getPaymentTypeAppName'),
                       'eet_send'   => array($this->translator->translate('Odesílat_do_EET'), 'format' => 'boolean'),
                       'dn_to_cash'  => array($this->translator->translate('Platba_přímo_do_pokladny'), 'format' => 'boolean'),
                       'no_invoice'  => array($this->translator->translate('Nevytvářet_fakturu_z_DL'), 'format' => 'boolean'),
                       'cl_pricelist.item_label' => array($this->translator->translate('Vázaná_položka_ceníku'), 'format' => 'text'),
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
        //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.PaymentTypes']));
	    $form->addHidden('id',NULL);
		$form->addText('name', $this->translator->translate('Název_platby'), 20, 60)
			->setRequired($this->translator->translate('Zadejte_prosím_název_platby'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_platby'));
		$form->addText('description', $this->translator->translate('Popis'), 40, 40)
			->setHtmlAttribute('placeholder',$this->translator->translate('Popis_platby'));
		$form->addText('short_desc', $this->translator->translate('Zkratka_platby'), 10, 10)
			->setHtmlAttribute('placeholder',$this->translator->translate('Zkratka_platby'));
		$form->addCheckbox('eet_send', $this->translator->translate('Odesílat_do_EET'));
        $form->addCheckbox('dn_to_cash', $this->translator->translate('Úhrada_dodacího_listu_přímo_do_pokladny'));
        $form->addCheckbox('no_invoice', $this->translator->translate('Nevytvářet_fakturu_z_dodacího_listu'));

		$form->addSelect('payment_type', $this->translator->translate('Druh_platby'), $this->getPaymentTypesApp());

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
	$this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
	$this->redirect('default');
    }	    


}
