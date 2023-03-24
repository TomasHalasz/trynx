<?php

namespace App\B2BModule\Presenters;

use Nette\Application\UI\Form;
use Nette\Utils\DateTime;

class BasketPresenter extends \App\B2BModule\Presenters\BasePresenter {

    /** @persistent */
    public $backlink = '';         
    public $id;
    public $page = 1;
    public $parameters;
	
    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;        	

    	
    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;


    /**
     * @inject
     * @var \App\Model\CompaniesAccessManager
     */
    public $CompaniesAccessManager;

    /**
     * @inject
     * @var \App\Model\StatusManager
     */
    public $StatusManager;

    /**
     * @inject
     * @var \App\Model\PartnersBranchManager
     */
    public $PartnersBranchManager;

    /**
     * @inject
     * @var \App\Model\ReportManager
     */
    public $ReportManager;

    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;


    /**
     * @inject
     * @var \App\Model\B2BOrderItemsManager
     */
    public $B2BOrderItemsManager;


    /**
     * @inject
     * @var \App\Model\B2BOrderManager
     */
    public $B2BOrderManager;

    public function startup()
    {
        parent::startup(); // TODO: Change the autogenerated stub
        //$this->translator->setPrefix(['B2BModule.Basket']);
        $this->csv_h        = array('columns' => 'od_number,od_date,req_date,od_title,cl_partners_book.company,cl_partners_book_workers.worker_name,cl_currencies.currency_code,price_e2,price_e2_vat,cl_order.header_txt,cl_order.footer_txt,delivery_place,delivery_method,cl_storage.name AS storage_name');
        $this->csv_i        = array('columns' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_order_items.item_label,cl_pricelist.order_label,cl_order_items.quantity,cl_order_items.units,cl_storage.name AS storage_name,cl_order_items.price_e2,cl_order_items.price_e2_vat',
            'datasource' => 'cl_order_items');

    }


    public function actionDefault()
    {

        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage($this->translator->translate('Zadali_jste_neplatný_odkaz'), 'danger');
            $this->redirect(':B2B:Mainpage:default');
        }
            parent::actionDefault(); // TODO: Change the autogenerated stub
        $this->id = $this->B2BOrderManager->getBasket($this->user->getIdentity()->cl_partners_book_id,
                                                      $this->user->getIdentity()->cl_partners_branch_id)->id;
        $cl_company_id = $this->getUser()->getIdentity()->cl_company_id;
        $this->settings = $this->CompaniesManager->findAll()->where('cl_company.id = ?',$cl_company_id)->fetch();
    }

    public function renderDefault()
    {
        $this->template->locale = $this->translatorMain->getLocale();
        //$this->template->locale = 'cs';
        $this->template->logged = $this->getUser()->isLoggedIn();
        $this->template->modal = FALSE;
        $this->template->lattePath = $this->getLattePath();
        $this->template->settings = $this->settings;
        //$tmpBasket = $this->B2BOrderManager->getBasket($this->getUser()->getId());
        $tmpBasket = $this->B2BOrderManager->getBasket($this->user->getIdentity()->cl_partners_book_id,
                                                        $this->user->getIdentity()->cl_partners_branch_id);
        $this->template->data = $tmpBasket;
        $this->template->basket = $this->B2BOrderItemsManager->findAll()->where('cl_b2b_order_id = ?', $tmpBasket->id);
        //paginator start
        /*$paginator = new \Nette\Utils\Paginator;
        $ItemsOnPage = 40;

        $paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
        $totalItems = $this->template->basket->count();

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
        $this->template->basket = $this->template->basket ->limit($paginator->getLength(), $paginator->getOffset())
            ->order('id');*/
        $this['sendOrder']->setValues($tmpBasket);

    }

    public function handleErase($item_id)
    {
        try{
            $this->B2BOrderItemsManager->findAll()->where('(id = ? OR cl_parent_bond_id = ?) AND cl_b2b_order_id = ?', $item_id, $item_id, $this->id)->delete();
            $this->B2BOrderManager->updateBasket($this->id);
            $this->flashMessage($this->translator->translate('Položka_byla_vymazána'), 'success');
        }catch(\Exception $e){
            $this->flashMessage($this->translator->translate('Položka_nebyla_vymazána'), 'danger');
            $this->flashMessage($this->translator->translate('Chyba:_').$e->getMessage(), 'danger');
        }
        $this->redrawControl('content');
    }

    public function handleChangeQuantity($item_id, $quantity)
    {
        try{
            $tmpData = $this->B2BOrderItemsManager->findAll()->where('id = ? AND cl_b2b_order_id = ?', $item_id, $this->id)->fetch();
            $oldQuantity = $tmpData->quantity;
            if ($tmpData && $tmpData->quantity > 0) {
                $price_e2 = $tmpData->price_e2 / $tmpData->quantity;
                $price_e2_vat = $tmpData->price_e2_vat / $tmpData->quantity;
                $tmpData->update(array('id' => $item_id, 'quantity' => $quantity,
                                    'price_e2' => $price_e2 * $quantity,
                                    'price_e2_vat' => $price_e2_vat * $quantity,
                                ));

                //17.06.2020 - Bonded items
                //find if there are bonds in cl_pricelist_bonds
                $tmpDataB = $this->B2BOrderItemsManager->findAll()->where('cl_parent_bond_id = ?', $item_id);
                foreach($tmpDataB as $key => $one){
                    $one->update(array('id' => $key, 'quantity' => $one->quantity * ($quantity / $oldQuantity) ,
                                        'price_e2' => $one->price_e2 * ($quantity / $oldQuantity),
                                        'price_e2_vat' => $one->price_e2_vat * ($quantity / $oldQuantity),
                                    ));
                }
                $this->B2BOrderManager->updateBasket($this->id);

                $this->flashMessage($this->translator->translate('Množství_bylo_změněno'), 'success');
            }
        }catch(\Exception $e){
            $this->flashMessage($this->translator->translate('Položka_nebyla_změněna'), 'danger');
            $this->flashMessage($this->translator->translate('Chyba').$e->getMessage(), 'danger');
        }
        $this->redrawControl('content');
    }


    protected function createComponentSendOrder($name)
    {
        $form = new Form($this, $name);
        $form->addTextarea('description_txt', $this->translator->translate('Poznámka'), 40,5 )
            ->setHtmlAttribute('placeholder', $this->translator->translate('Prostor_pro_vaši_poznámku_k_objednávce'));
        //$translatorPaymentTypes->setPrefix(['customModule.payment_types']);
        //            ->setTranslator($translatorPaymentTypes)
        $tmpData = $this->B2BOrderManager->find($this->id);
        $tmpPaymentTypesId = is_null($tmpData->cl_payment_types_id) ? 0 : $tmpData->cl_payment_types_id;
        if ( ($this->getUser()->getIdentity()->b2b_transfer == 1 && $this->getUser()->getIdentity()->b2b_cash == 1) || !$this->user->isInRole('b2b')) {
            $arrPay = $this->PaymentTypesManager->findAll()->
                                    where('payment_type = 0 OR payment_type = 1 OR payment_type = 2 OR id = ?', $tmpPaymentTypesId);

        }else if ($this->getUser()->getIdentity()->b2b_transfer == 1) { // only transfer
            $arrPay = $this->PaymentTypesManager->findAll()->
                                    where('payment_type = 0 OR id = ?', $tmpPaymentTypesId);

        }else if ($this->getUser()->getIdentity()->b2b_cash == 1) {// only cash
            $arrPay = $this->PaymentTypesManager->findAll()->
                                    where('payment_type = 1 OR payment_type = 2 OR id = ?', $tmpPaymentTypesId);

        }else{ //non of above, take payment type from cl_partners_book
            $tmpPartnersBookId = $this->getUser()->getIdentity()->cl_partners_book_id;
            $tmpPartnersBook = $this->PartnersManager->find($tmpPartnersBookId);
            $tmpPaymentTypesIdDEF = is_null($tmpPartnersBook->cl_payment_types_id) ? 0 : $tmpPartnersBook->cl_payment_types_id;
            $arrPay = $this->PaymentTypesManager->findAll()->
                        where('id = ? OR id = ?', $tmpPaymentTypesId, $tmpPaymentTypesIdDEF);

        }
        $arrPay = $arrPay->order('name')->fetchPairs('id', 'name');

        $form->addSelect('cl_payment_types_id',$this->translator->translate('Forma_úhrady'),$arrPay)
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setRequired($this->translator->translate('Forma_úhrady_musí_být_vybrána'));

        $tmpPartnersBookId = $this->getUser()->getIdentity()->cl_partners_book_id;
        $arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?', $tmpPartnersBookId)->fetchPairs('id', 'b_name');
        $form->addSelect('cl_partners_branch_id', $this->translator->translate("Pobočka"), $arrBranch)
            ->setPrompt($this->translator->translate('Zvolte_pobočku'))
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_pobočku'));

        $form->addSubmit('save', $this->translator->translate('Uložit'))
            ->setHtmlAttribute('class','btn btn-lg btn-primary');

        $form->addSubmit('send', $this->translator->translate('Objednat'))
            ->setHtmlAttribute('class','btn btn-lg btn-success');

        $form->onSuccess[] = array($this, 'SendOrderFormSubmitted');
        return $form;
    }



    public function SendOrderFormSubmitted(Form $form)
    {
        //try{
            $data=$form->values;
            if ($form['send']->isSubmittedBy())
            {
                $tmpNow = new DateTime();
                $this->B2BOrderManager->update(array('id' => $this->id,
                                                    'date' => $tmpNow,
                                                    'description_txt' => $data['description_txt'],
                                                    'cl_payment_types_id' => $data['cl_payment_types_id']));
                if (isset($data['cl_partners_branch_id'])){
                    $this->B2BOrderManager->update(array('id' => $this->id,
                                                    'cl_partners_branch_id' => $data['cl_partners_branch_id']));
                }

                $this->B2BOrderManager->sendBasket($this->id);
                $this->B2BOrderManager->setWork($this->id);
                //send email to company
                //send email to costumer
                $this->sendEmailOrder();
                $this->flashMessage($this->translator->translate('Objednávka_byla_odeslána'), 'success');
            }elseif ($form['save']->isSubmittedBy())
            {
                $tmpNow = new DateTime();
                $this->B2BOrderManager->update(array('id' => $this->id,
                    'date' => $tmpNow,
                    'description_txt' => $data['description_txt'],
                    'cl_payment_types_id' => $data['cl_payment_types_id']));
                if (isset($data['cl_partners_branch_id'])){
                    $this->B2BOrderManager->update(array('id' => $this->id,
                        'cl_partners_branch_id' => $data['cl_partners_branch_id']));
                }

                $this->flashMessage($this->translator->translate('Objednávka_byla_uložena'), 'success');
            }

       /* }catch(\Exception $e){
            $this->flashMessage('Objednávka nebyla odeslána.', 'danger');
            $this->flashMessage('Chyba: '.$e->getMessage(), 'danger');
        }*/
        $this->redrawControl('content');
    }


    private function sendEmailOrder()
    {
        $this->DataManager = $this->B2BOrderManager;
        $this->mainTableName = "cl_b2b_order";
        $this->docEmail	    = ['template' => __DIR__ .'/../../B2BModule/templates/Basket/emailBasket.latte',
                                    'emailing_text' => 'b2b_order'];
        //17.05.2020 - settings for documents saving and emailing
        $this->docTemplate[1]  =  $this->ReportManager->getReport(__DIR__ . '/../../B2BModule/templates/Basket/b2bBasket.latte');
        //$this->docAuthor    = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $this->docAuthor        = "";
        $this->docTitle[1]	    = ["", "cl_partners_book.company", "cl_commission.cm_number"];

        //bdump('ted');
        $tmpData = $this->B2BOrderManager->find($this->id)->toArray();
        $this->emlPreview = FALSE;
        parent::handleSendDoc($this->id, 1, $tmpData);

        $data = $this->emailData;
        foreach($data['workers'] as $key => $one)
        {

            if ($data['singleEmailTo'] != '') {
                $data['singleEmailTo'] .= ';';
            }
            //$data['singleEmailTo'] .= $one . ' <' . $arrWorkers[$one] . '>';
            $data['singleEmailTo'] .= $one;
        }

        $tmpUsers = $this->CompaniesAccessManager->findAll()->select('cl_users.name, cl_users.email, cl_users.email2')->
                                                    where('cl_users.b2b_manager = 1');

        foreach($tmpUsers as $key => $one)
        {
            $email = $this->validateEmail($one['email']);
            if (empty($tmpEmail))
                $email = $this->validateEmail($one['email2']);

            if ($email != '') {
                if ($data['singleEmailTo'] != '') {
                    $data['singleEmailTo'] .= ';';
                }
                $data['singleEmailTo'] .= $one['name'] . ' <' . $email . '>';
            }
            //$data['singleEmailTo'] .= $one;
        }

        unset($data['workers']);

        $emailTo = str_getcsv($data['singleEmailTo'], ';');

        $emailFrom = $data['singleEmailFrom'];
        $subject = $data['subject'];
        $body = $data['body'];

        $attachment = json_decode($data['attachment'], true);

        try{
            $this->emailService->sendMail($this->settings, $emailFrom, $emailTo, $subject, $body, $attachment);
        }catch (\Exception $e){
            $this->flashMessage($this->translator->translate("Email_nebyl_odeslán."), "danger");
            $this->flashMessage($e->getMessage(), "danger");
        }

        //26.07.2018 - connect parent table
        $data['cl_b2b_order_id'] = $this->id;

        if (is_null($data['attachment']))
            unset($data['attachment']);

        //save to cl_emailing
        $this->EmailingManager->insert($data);

    }


    public function handleNewPage($page)
    {
        $this->page = $page;
        $this->redrawControl('content');
    }


}
