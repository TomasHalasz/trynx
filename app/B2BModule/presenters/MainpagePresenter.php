<?php

namespace App\B2BModule\Presenters;

use Nette\Application\UI\Form;

class MainpagePresenter extends \App\B2BModule\Presenters\BasePresenter {

    /** @persistent */
    public $backlink = '';         
    public $page = 1;
    public $id;
    public $parameters;
    /** @persistent */
    public $searchTxt = NULL;
    /** @persistent */
    public $pricelistGroupId = NULL;
    /** @persistent */
    public $sort_type = 'item_label';
    /** @persistent */
    public $sort_order = 'ASC';

    public $showCard = FALSE;
    public $card_id = NULL;




    public $settings;


    /**
     * @var \App\Model\PriceListManager
     */
    public $activeData;

    /**
     * @var \App\Model\PriceListManager
     */
    public $templateData;

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
     * @var \App\Model\PriceListManager
     */
    public $PriceListManager;

    /**
     * @inject
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;

    /**
     * @inject
     * @var \App\Model\PriceListPartnerGroupManager
     */
    public $PriceListPartnerGroupManager;


    /**
     * @inject
     * @var \App\Model\PriceListGroupManager
     */
    public $PriceListGroupManager;

    /**
     * @inject
     * @var \App\Model\PriceListBondsManager
     */
    public $PriceListBondsManager;

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
        parent::startup();
        //$this->translator->setPrefix(['B2BModule.Mainpage']);
    }

    public function actionDefault($pricelistGroupId = NULL, $searchTxt = NULL, $sort_type = NULL, $sort_order = 'ASC')
    {
        parent::actionDefault();
        $this->sort_order = $sort_order;
        $this->sort_type = $sort_type;
        $this->searchTxt = $searchTxt;
        $this->pricelistGroupId = $pricelistGroupId;
        if ($this->getUser()->isLoggedIn()) {
            $cl_company_id = $this->getUser()->getIdentity()->cl_company_id;
            $cl_partners_book_id = $this->user->getIdentity()->cl_partners_book_id;
            $cl_partners_branch_id = $this->user->getIdentity()->cl_partners_branch_id;
        }else{
            $mySection = $this->session->getSection('company');
            $cl_company_id = $mySection['cl_company_id'];
            $cl_partners_book_id = $mySection['cl_partners_book_id'];
            $cl_partners_branch_id = $mySection['cl_partners_branch_id'];
        }
        //bdump($cl_partners_branch_id,"mainpage::cl_partners_branch_id");
        $tmpPartner = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->fetch();

        //24.06.2020 - set
/*        if (is_null($cl_partners_branch_id))
        {
            $arrBranch       = $this->PartnersBranchManager->findAll()->
                                    where('cl_partners_book_id = ?', $tmpPartner->id)->
                                    order('item_order')->limit(1)->fetch();
            $arrBranchId = NULL;
            if ($arrBranch){
                $cl_partners_branch_id = $arrBranch['id'];
            }
            $this->user['cl_partners_branch_id'] = $cl_partners_branch_id;
        }
*/
        // bdump($cl_company_id);
        $this->settings = $this->CompaniesManager->findAllTotal()->where('id = ?', $cl_company_id)->fetch();
        $this->id = $this->B2BOrderManager->getBasket( $cl_partners_book_id, $cl_partners_branch_id)->id;

        //$cl_partners_book_id = $this->getUser()->getIdentity()->cl_partners_book_id;

        $discount = $tmpPartner->discount;


        if (!$tmpPartner->pricelist_partner || $this->pricelistAll) {
/*            $tmpData = $this->PriceListManager->findAll()->select('cl_pricelist.*, (cl_pricelist.price_vat * ( 1 - ( IF(' . $discount . ' >= IFNULL(cl_prices_groups.price_change,0), ' . $discount . ', cl_prices_groups.price_change ) / 100))) AS price_dis,
                                                                CASE WHEN SUM(:cl_store.quantity) = 0 THEN 0
                                                                WHEN SUM(:cl_store.quantity) > 0 AND :cl_store.quantity <= 10 THEN 2
                                                                WHEN SUM(:cl_store.quantity) > 10 THEN 3
                                                                ELSE NULL
                                                                END AS availability,
                                                                 IF(' . $discount . ' >= IFNULL(cl_prices_groups.price_change,0), ' . $discount . ', cl_prices_groups.price_change ) AS discount,
                                                                (SELECT SUM(:cl_b2b_order_items.quantity) FROM cl_b2b_order_items 
                                                                        WHERE :cl_b2b_order_items.cl_pricelist_id = cl_pricelist.id AND
                                                                           :cl_b2b_order_items.cl_b2b_order_id = ?) AS quantity_basket',
                $this->id)->where('cl_storage.b2b_store = 1')->
            where('cl_pricelist_group.b2b_show = 1 AND cl_pricelist.not_active = 0')->
            group('cl_pricelist.id');*/
            $tmpData = $this->PriceListManager->findAll()->select('cl_pricelist.*, 
                                                                CASE WHEN (IF(:cl_pricelist_partner.price_change = 0, :cl_pricelist_partner.price_vat,cl_pricelist.price_vat * ( 1 - ( IF( TRUE,
                                                                        IF( '.$discount.' >= IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0) AND '.$discount.' >= IFNULL(cl_prices_groups.price_change,0)  AND '.$discount.' >= :cl_pricelist_partner.price_change , 
                                                                            '.$discount.',
                                                                            IF ((:cl_pricelist_partner.price_change > IFNULL(cl_prices_groups.price_change,0) AND :cl_pricelist_partner.price_change > IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), 
                                                                                    :cl_pricelist_partner.price_change,
                                                                                    IF (cl_prices_groups.price_change > cl_pricelist_group:cl_pricelist_partner_group.price_change, cl_prices_groups.price_change, cl_pricelist_group:cl_pricelist_partner_group.price_change )))
                                                                ,0) / 100)))) IS NULL OR TRUE = ? THEN (cl_pricelist.price_vat * ((100 - '.$discount.')/100))
                                                                ELSE (IF(:cl_pricelist_partner.price_change = 0, :cl_pricelist_partner.price_vat,cl_pricelist.price_vat * ( 1 - ( IF( TRUE,
                                                                        IF( '.$discount.' >= IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0) AND '.$discount.' >= IFNULL(cl_prices_groups.price_change,0)  AND '.$discount.' >= :cl_pricelist_partner.price_change , 
                                                                            '.$discount.',
                                                                            IF ((:cl_pricelist_partner.price_change > IFNULL(cl_prices_groups.price_change,0) AND :cl_pricelist_partner.price_change > IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), 
                                                                                    :cl_pricelist_partner.price_change,
                                                                                    IF (cl_prices_groups.price_change > cl_pricelist_group:cl_pricelist_partner_group.price_change, cl_prices_groups.price_change, cl_pricelist_group:cl_pricelist_partner_group.price_change )))
                                                                ,0) / 100))))  
                                                                END AS price_dis,
                                                                CASE WHEN SUM(:cl_store.quantity) = 0 THEN 0
                                                                WHEN SUM(:cl_store.quantity) > 0 AND :cl_store.quantity <= 10 THEN 2
                                                                WHEN SUM(:cl_store.quantity) > 10 THEN 3
                                                                ELSE NULL
                                                                END AS availability,
                                                                CASE WHEN (IF(TRUE,
                                                                    IF( '.$discount.' >= IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0) AND '.$discount.' >= IFNULL(cl_prices_groups.price_change,0)  AND '.$discount.' >= :cl_pricelist_partner.price_change , 
                                                                                    '.$discount.', 
                                                                                    IF ((:cl_pricelist_partner.price_change > IFNULL(cl_prices_groups.price_change,0) AND :cl_pricelist_partner.price_change > IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), 
                                                                                    :cl_pricelist_partner.price_change,
                                                                                   IF (cl_prices_groups.price_change > cl_pricelist_group:cl_pricelist_partner_group.price_change, cl_prices_groups.price_change, cl_pricelist_group:cl_pricelist_partner_group.price_change ))),
                                                                     ROUND((1 - :cl_pricelist_partner.price_vat / cl_pricelist.price_vat) * 100, 0)) IS NULL) OR TRUE = ? THEN '.$discount.'
                                                                    ELSE (IF(TRUE,
                                                                    IF( '.$discount.' >= IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0) AND '.$discount.' >= IFNULL(cl_prices_groups.price_change,0)  AND '.$discount.' >= :cl_pricelist_partner.price_change , 
                                                                                    '.$discount.', 
                                                                                    IF ((:cl_pricelist_partner.price_change > IFNULL(cl_prices_groups.price_change,0) AND :cl_pricelist_partner.price_change > IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), 
                                                                                    :cl_pricelist_partner.price_change,
                                                                                   IF (cl_prices_groups.price_change > cl_pricelist_group:cl_pricelist_partner_group.price_change, cl_prices_groups.price_change, cl_pricelist_group:cl_pricelist_partner_group.price_change ))),
                                                                     ROUND((1 - :cl_pricelist_partner.price_vat / cl_pricelist.price_vat) * 100, 0)))
                                                                 END AS discount,
                                                                0 AS quantity_basket,
                                                                           cl_pricelist_group:cl_pricelist_partner_group.price_change AS price_change,
                                                                :cl_pricelist_partner.price_vat AS price_partner',  (!$tmpPartner->pricelist_partner) ?  TRUE : FALSE,  (!$tmpPartner->pricelist_partner) ?  'TRUE' : 'FALSE'  )->
                                                        where('cl_storage.b2b_store = 1')->
                                                        where('cl_pricelist.b2b_not_show = 0')->
                                                        where('cl_pricelist_group.b2b_show = 1 AND cl_pricelist.not_active = 0 ')->
                                                        joinWhere(':cl_pricelist_partner', ' :cl_pricelist_partner.cl_partners_book_id = ?', $cl_partners_book_id)->
                                                        joinWhere('cl_pricelist_group:cl_pricelist_partner_group', 'cl_pricelist_group:cl_pricelist_partner_group.cl_partners_book_id = ?', $cl_partners_book_id)->
                                                        group('cl_pricelist.id');


        }else{
            $arrPricelistPartnerGroup = $this->PriceListPartnerGroupManager->findAll()->where('cl_partners_book_id = ? AND cl_pricelist_group_id IS NOT NULL', $cl_partners_book_id)->select('cl_pricelist_group_id AS id')->fetchPairs('id', 'id');
            $arrPricelistPartner = $this->PriceListPartnerManager->findAll()->
                                        where('cl_partners_book_id = ? AND cl_pricelist_id IS NOT NULL', $cl_partners_book_id)->
                                        select('cl_pricelist_id AS id')->fetchPairs('id','id');
            //:cl_pricelist_partner.price_vat IS NULL
            //:cl_pricelist_partner.price_vat > 0
            $tmpData = $this->PriceListManager->findAll()->select('cl_pricelist.*,
                                                                (IF(:cl_pricelist_partner.price_change = 0, :cl_pricelist_partner.price_vat,cl_pricelist.price_vat * ( 1 - ( IF( TRUE,
                                                                        IF( '.$discount.' >= IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0) AND '.$discount.' >= IFNULL(cl_prices_groups.price_change,0)  AND '.$discount.' >= :cl_pricelist_partner.price_change , 
                                                                            '.$discount.',
                                                                            IF ((:cl_pricelist_partner.price_change > IFNULL(cl_prices_groups.price_change,0) AND :cl_pricelist_partner.price_change > IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), 
                                                                                    :cl_pricelist_partner.price_change,
                                                                                    IF (cl_prices_groups.price_change > cl_pricelist_group:cl_pricelist_partner_group.price_change, cl_prices_groups.price_change, cl_pricelist_group:cl_pricelist_partner_group.price_change )))
                                                                ,0) / 100)))) AS price_dis,  
                                                                CASE WHEN SUM(:cl_store.quantity) = 0 THEN 0
                                                                WHEN SUM(:cl_store.quantity) > 0 AND :cl_store.quantity <= 10 THEN 2
                                                                WHEN SUM(:cl_store.quantity) > 10 THEN 3
                                                                ELSE NULL
                                                                END AS availability, 
                                                                IF(TRUE,
                                                                    IF( '.$discount.' >= IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0) AND '.$discount.' >= IFNULL(cl_prices_groups.price_change,0)  AND '.$discount.' >= :cl_pricelist_partner.price_change , 
                                                                                    '.$discount.', 
                                                                                    IF ((:cl_pricelist_partner.price_change > IFNULL(cl_prices_groups.price_change,0) AND :cl_pricelist_partner.price_change > IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), 
                                                                                    :cl_pricelist_partner.price_change,
                                                                                   IF (cl_prices_groups.price_change > cl_pricelist_group:cl_pricelist_partner_group.price_change, cl_prices_groups.price_change, cl_pricelist_group:cl_pricelist_partner_group.price_change ))),
                                                                     ROUND((1 - :cl_pricelist_partner.price_vat / cl_pricelist.price_vat) * 100, 0))                   
                                                                     AS discount,
                                                                    0 AS quantity_basket,
                                                                           cl_pricelist_group:cl_pricelist_partner_group.price_change AS price_change,
                                                 :cl_pricelist_partner.price_vat AS price_partner')->
                                                        where('cl_storage.b2b_store = 1 AND cl_pricelist.not_active = 0 AND
                                                                                cl_pricelist_group.b2b_show = 1 AND 
                                                                                ((cl_pricelist_group:cl_pricelist_partner_group.cl_pricelist_group_id IN(?) AND 
                                                                                 cl_pricelist_group:cl_pricelist_partner_group.cl_partners_book_id = ? ) OR
                                                                                 (:cl_pricelist_partner.cl_pricelist_id IN(?) AND 
                                                                                 :cl_pricelist_partner.cl_partners_book_id = ?))',
                $arrPricelistPartnerGroup, $cl_partners_book_id, $arrPricelistPartner, $cl_partners_book_id)->
                                                        where('cl_pricelist.b2b_not_show = 0')->
            joinWhere('cl_pricelist_group:cl_pricelist_partner_group', 'cl_pricelist_group:cl_pricelist_partner_group.cl_pricelist_group_id IN(?)
                                                                                 AND cl_pricelist_group:cl_pricelist_partner_group.cl_partners_book_id = ?', $arrPricelistPartnerGroup, $cl_partners_book_id )->
            joinWhere(':cl_pricelist_partner', ':cl_pricelist_partner.cl_pricelist_id IN(?)
                                                                                 AND :cl_pricelist_partner.cl_partners_book_id = ?', $arrPricelistPartner, $cl_partners_book_id)->

            group('cl_pricelist.id');

            /*                                                                (SELECT SUM(:cl_b2b_order_items.quantity) FROM cl_b2b_order_items
                                                                        WHERE :cl_b2b_order_items.cl_pricelist_id = cl_pricelist.id AND
                                                                           :cl_b2b_order_items.cl_b2b_order_id = ?) */

        }

        //$tmpData = $tmpData->select('cl_pricelist_group:cl_pricelist_partner_group.price_change AS price_change,
        //                                         :cl_pricelist_partner.price_vat AS price_partner');

        if (!is_null($this->searchTxt))
        {
            $sTxt = '%' .$this->searchTxt . '%';
            $tmpData = $tmpData->where('cl_pricelist.item_label LIKE ? OR cl_pricelist.identification LIKE ? OR cl_pricelist.ean_code LIKE ?', $sTxt, $sTxt, $sTxt );
        }
        if (!is_null($this->pricelistGroupId))
        {
            $tmpData = $tmpData->where('cl_pricelist.cl_pricelist_group_id = ? ', $this->pricelistGroupId);
        }

        //$tmpData = $this->PriceListManager->getB2BPricelist();
        $this['search']->setValues(array('searchTxt' => $this->searchTxt, 'pricelistGroupId' => $this->pricelistGroupId));
        //dump($tmpData);

        $this->activeData       = $tmpData;
        $this->templateData    = clone $tmpData;
    }


    public function renderDefault()
    {
        $this->template->showCard = $this->showCard;
        if (!is_null($this->card_id)){
            $this->template->dataItem = $this->activeData->where('cl_pricelist.id = ?', $this->card_id)->fetch();
        }else{
            $this->template->dataItem = FALSE;
        }

        $this->template->locale = $this->translatorMain->getLocale();
        //$this->template->locale = 'cs';
        $this->template->logged = $this->getUser()->isLoggedIn();
        $this->template->modal = FALSE;
        $this->template->lattePath = $this->getLattePath();
        $this->template->sort_order = $this->sort_order;
        $this->template->sort_type = $this->sort_type;

        $this->template->settings = $this->settings;
        $this->template->b2bOrder = $this->B2BOrderManager->findAll()->where('id = ?', $this->id)->fetch();

        //$this->template->data   = $this->activeData;

        //paginator start
        $paginator = new \Nette\Utils\Paginator;
        $ItemsOnPage = 40;

        $paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
        $totalItems = $this->templateData->count();

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
        $this->template->data = $this->templateData->limit($paginator->getLength(), $paginator->getOffset())
                                    ->order($this->sort_type . ' ' . $this->sort_order);

        $this->template->arrAvailability = $this->ArraysManager->getAvailability();
        $tmpBasketLast = $this->B2BOrderItemsManager->findAll()->
                                            where('cl_b2b_order_id = ?', $this->id)->
                                            order('id DESC')->
                                            limit(1)->fetch();
        if ($tmpBasketLast){
            $this->template->lastBasketId = $tmpBasketLast->cl_pricelist_id;
        }else{
            $this->template->lastBasketId = NULL;
        }
        $tmpLastBasketItems = $this->B2BOrderItemsManager->findAll()->where('cl_b2b_order_id = ?', $this->id);
        $arrBasketQuantity = [];
        foreach($tmpLastBasketItems as $key => $one){
            if (array_key_exists($one['cl_pricelist_id'], $arrBasketQuantity))
                $arrBasketQuantity[$one['cl_pricelist_id']] += $one['quantity'];
            else
                $arrBasketQuantity[$one['cl_pricelist_id']] = $one['quantity'];
        }
        $this->template->arrBasketQuantity = $arrBasketQuantity;



    }

    public function handleNewPage($page)
    {
        $this->page = $page;
        $this->redrawControl('content');
    }



    protected function createComponentSearch($name)
    {
        $form = new Form($this, $name);
        $form->setMethod('GET');
        $form->addText('searchTxt', $this->translator->translate('Hledaná_položka'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Hledaná_položka'));
        if ($this->getUser()->isLoggedIn()) {
            $cl_company_id = $this->getUser()->getIdentity()->cl_company_id;
            $cl_partners_book_id = $this->user->getIdentity()->cl_partners_book_id;
            $cl_partners_branch_id = $this->user->getIdentity()->cl_partners_branch_id;
        }else {
            $mySection = $this->session->getSection('company');
            $cl_company_id = $mySection['cl_company_id'];
            $cl_partners_book_id = $mySection['cl_partners_book_id'];
        }
        $tmpPartner = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->fetch();

        if ($tmpPartner->pricelist_partner && !$this->pricelistAll)
        {
            $tmpGroups = $this->PriceListGroupManager->findAll()->where('b2b_show = 1 AND :cl_pricelist_partner_group.cl_partners_book_id = ?', $cl_partners_book_id)->
                                                            order('name ASC')->fetchPairs('id', 'name');
            //$tmpData = $tmpData->where('cl_pricelist_group:cl_pricelist_partner_group.cl_partners_book_id = ? OR
            //                                     :cl_pricelist_partner.cl_partners_book_.id = ?', $cl_partners_book_id, $cl_partners_book_id);
        }else{
            $tmpGroups = $this->PriceListGroupManager->findAll()->where('b2b_show = 1')->order('name ASC')->fetchPairs('id', 'name');
        }
        $tmpGroups[''] = '';
        $form->addSelect('pricelistGroupId', $this->translator->translate('Skupina'), $tmpGroups)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Skupina'))
            ->setPrompt($this->translator->translate('Vyberte_skupinu'));

        $form->addSubmit('send', $this->translator->translate('Hledat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary');
         $form->addSubmit('reset', 'X')
            ->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->onSuccess[] = array($this, 'SearchFormSubmitted');
        return $form;
    }



    public function SearchFormSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            $this->searchTxt = $data->searchTxt;
            $this->pricelistGroupId = $data->pricelistGroupId;
            //$this->redirect('this', array('searchTxt' => $this->searchTxt, 'pricelistGroupId' => $this->pricelistGroupId ));
            $this->redrawControl('content');
        }elseif ($form['reset']->isSubmittedBy())
        {
            $this->searchTxt = NULL;
            $this->pricelistGroupId = NULL;
            $this->redirect('this', array('searchTxt' => NULL, 'pricelistGroupId' => NULL));
        }


    }

    public function handleBuy($cl_pricelist_id, $quantity){
        if (!is_null($cl_pricelist_id)) {
            //$basket = $this->B2BOrderManager->getBasket($this->getUser()->getId());
            $basket= $this->B2BOrderManager->getBasket($this->user->getIdentity()->cl_partners_book_id,
                                                        $this->user->getIdentity()->cl_partners_branch_id);

            $cl_partners_book_id    = $this->getUser()->getIdentity()->cl_partners_book_id;
            $tmpPartner             = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->fetch();
            $discount               = $tmpPartner->discount;

            //$tmpPricelist = $this->PriceListManager->find($cl_pricelist_id);
            //$tmpPricelist           = $this->PriceListManager->findAll()->where('cl_pricelist.id = ?', $cl_pricelist_id)->
            //                            select('cl_pricelist.*, (cl_pricelist.price_vat * ( 1 - ( IF( '.$discount.' > ABS(IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), '.$discount.', ABS(cl_pricelist_group:cl_pricelist_partner_group.price_change))
            //                                                                                        / 100))) AS price_dis');

            //if ($tmpPartner->pricelist_partner)
            //{
                //IF( '.$discount.' > ABS(IFNULL(cl_pricelist_group:cl_pricelist_partner_group.price_change,0)), '.$discount.', ABS(cl_pricelist_group:cl_pricelist_partner_group.price_change)) / 100))) AS price_dis,¨

             //   $tmpPricelist = $tmpPricelist->where('cl_pricelist_group:cl_pricelist_partner_group.cl_partners_book_id = ? OR
             //                                    :cl_pricelist_partner.cl_partners_book_.id = ?', $cl_partners_book_id, $cl_partners_book_id)->fetch();
             //   if ($tmpPricelist){
             //       $tmpPrice       = $tmpPricelist->price_dis / (1 + ($tmpPricelist->vat / 100));;
             //       $tmpPrice_vat   = $tmpPricelist->price_dis;
             //   }
            //}else{
             //   $tmpPricelist   = $tmpPricelist->fetch();
             //   $tmpPrice       = $tmpPricelist->price;
             //   $tmpPrice_vat   = $tmpPricelist->price_vat;
            //}
            $tmpPricelist   = $this->activeData->where(array('cl_pricelist.id' => $cl_pricelist_id))->fetch();
            $tmpPrice       = $tmpPricelist->price_dis / (1 + ($tmpPricelist->vat / 100));
            $tmpPrice_vat   = $tmpPricelist->price_dis;

            if ($this->settings->price_e_type == 0) {
                $price_e = $tmpPricelist->price_vat / (1 + ($tmpPricelist->vat / 100));
                //$price_e2 = $price_e * (1 - ($basket->cl_partners_book->discount / 100));¨
                $price_e2 = $tmpPrice;

            } else {
                $price_e = $tmpPricelist->price_vat;
                $price_e2 = $tmpPrice_vat * (1 - ($basket->cl_partners_book->discount / 100));
                $price_e2 = $price_e2 / (1 + ($tmpPricelist->vat / 100));
            }

            $arrData = array('cl_pricelist_id'  => $cl_pricelist_id,
                            'cl_b2b_order_id'   => $basket->id,
                            'quantity'          => $quantity,
                            'item_label'        => $tmpPricelist->item_label,
                            'units'             => $tmpPricelist->unit,
                            'price_e2'          => $price_e2 * $quantity,
                            'vat'               => $tmpPricelist->vat,
                            'discount'          => round((1 - ($tmpPricelist['price_dis'] / $tmpPricelist['price_vat'])) * 100,0),
                            'price_e2_vat'      => ($price_e2 * $quantity) * (1 + ($tmpPricelist->vat / 100)),
                            'price_e_type'      => $this->settings->price_e_type,
                            'price_e'           => $price_e);

            $newItem = $this->B2BOrderItemsManager->insert($arrData);

            if (!is_null($cl_pricelist_id)) {
                //17.06.2020 - Bonded items
                //find if there are bonds in cl_pricelist_bonds
                //$tmpBonds = $this->PriceListBondsManager->findBy(array('cl_pricelist_bonds_id' => $cl_pricelist_id));
                $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $cl_pricelist_id, $quantity);
                $tmpId = $newItem->id;
                foreach ($tmpBonds as $key => $oneBond) {
                    //found in cl_invoice_items if there already is bonded item
                    //$tmpB2BItem = $this->B2BOrderItemsManager->findBy(array('cl_parent_bond_id' => $tmpId,
                    //                                                                    'cl_pricelist_id' => $oneBond->cl_pricelist_id))->fetch();
                    $newItemB = new \Nette\Utils\ArrayHash;
                    $newItemB['cl_b2b_order_id']     = $basket->id;
                    $newItemB['item_order']          = $newItem->item_order + 1;
                    $newItemB['cl_pricelist_id']     = $oneBond->cl_pricelist_id;
                    $newItemB['item_label']          = $oneBond->cl_pricelist->item_label;
                    $newItemB['quantity']            = $oneBond->quantity * ($oneBond->multiply == 1) ? $newItem->quantity : 1 ;
                    $newItemB['units']               = $oneBond->cl_pricelist->unit;
                    $newItemB['price_s']             = $oneBond->cl_pricelist->price_s;
                    $newItemB['price_e']             = $oneBond->cl_pricelist->price;
                    $newItemB['discount']            = $oneBond->discount;
                    $newItemB['price_e2']            = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $newItem->quantity);
                    $newItemB['vat']                 = $oneBond->cl_pricelist->vat;
                    $newItemB['price_e2_vat']        = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $newItem->quantity);
                    $newItemB['price_e_type']        = $newItem->price_e_type;
                    $newItemB['cl_parent_bond_id']   = $newItem->id;

                    $tmpNew = $this->B2BOrderItemsManager->insert($newItemB);
                    $tmpId = $tmpNew->id;
                }
            }

            $this->B2BOrderManager->updateBasket($basket->id);
            $this->updateMenuBasket();
            //$this->flashMessage('Položka byla přidána do košíku.', 'success');
            //$this->redrawControl('content');

            //$this->redrawControl('flash');

        }

    }

    public function handleSort($sort_type, $sort_order){
        $this->sort_order = $sort_order;
        $this->sort_type = $sort_type;


        //, $order, $searchTxt, $pricelistGroupId
        //$this->redirect('this', array('searchTxt' => $searchTxt, 'pricelistGroupId' => $pricelistGroupId, 'sort_type' => $type, 'sort_order' => $order ));
       $this->redrawControl('content');
    }

    public function handleShowCard($cl_pricelist_id){
        $this->card_id = $cl_pricelist_id;
        $this->showCard = TRUE;
        $this->redrawControl('itemCard');
    }

}
