<?php

namespace App\B2BModule\Presenters;

use Nette\Application\UI\Form;

class OrdersPresenter extends \App\B2BModule\Presenters\BasePresenter {

    /** @persistent */
    public $backlink = '';
    public $page = 1;
    public $parameters;
    public $settings;

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
        //$this->translator->setPrefix(['B2BModule.Orders']);
    }

    public function actionDefault()
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage($this->translator->translate('Zadali_jste_neplatný_odkaz'), 'danger');
            $this->redirect(':B2B:Mainpage:default');
        }
        parent::actionDefault();
        $cl_company_id = $this->getUser()->getIdentity()->cl_company_id;
        // bdump($cl_company_id);
        $this->settings = $this->CompaniesManager->findAllTotal()->where('id = ?', $cl_company_id)->fetch();
    }

    public function actionEdit($id)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage($this->translator->translate('Zadali_jste_neplatný_odkaz'), 'danger');
            $this->redirect(':B2B:Mainpage:default');
        }
    }


    public function renderDefault()
    {
        $this->template->locale = $this->translatorMain->getLocale();
        //$this->template->locale = 'cs';
        $this->template->logged = $this->getUser()->isLoggedIn();
        $this->template->modal = FALSE;
        $this->template->lattePath = $this->getLattePath();
        $this->template->settings = $this->settings;

        $cl_partners_book_id =  $this->getUser()->getIdentity()->cl_partners_book_id;
        $cl_partners_branch_id = $this->getUser()->getIdentity()->cl_partners_branch_id;
        if (is_null($cl_partners_branch_id)){
            $tmpData = $this->B2BOrderManager->findAll()->where('cl_partners_book_id = ? AND cl_status.s_new = 0', $cl_partners_book_id);
        }else{
            $tmpData = $this->B2BOrderManager->findAll()->where('cl_partners_book_id = ? AND cl_status.s_new = 0 AND cl_partners_branch_id = ?', $cl_partners_book_id, $cl_partners_branch_id);
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
        $this->template->data = $tmpData->limit($paginator->getLength(), $paginator->getOffset())->order('date DESC');
    }

    public function handleNewPage($page)
    {
        $this->page = $page;
        $this->redrawControl('content');
    }

    public function renderEdit($id = NULL){
        $this->setLayout(__DIR__ . '/../templates/@layoutmain.latte');
        $cl_company_id = $this->getUser()->getIdentity()->cl_company_id;
        $this->settings = $this->CompaniesManager->findAllTotal()->where('id = ?', $cl_company_id)->fetch();
        $this->template->settings = $this->settings;
        if (!is_null($id)) {
            $this->template->items = $this->B2BOrderItemsManager->findAll()->where('cl_b2b_order_id = ?', $id)->order('item_label');
            $this->template->data = $this->B2BOrderManager->find($id);
        }else{
            $this->template->items = [];
            $this->template->data = [];
        }
        $this->redrawControl('content');

    }

}
