<?php

namespace App\B2BModule\Presenters;

use Nette\Application\UI\Form;
use Halasz;
use Nette\Utils\Image;

class BasePresenter extends \App\Presenters\BasePresenter {

    /** @persistent */
    public $backlink = '';
    public $currencyName = '';
    public $itemsSum = 0;
    public $itemsCount = 0;
    public $parameters;
    public $supportForm;
    public $pdfPreviewData = NULL;
    public $unMoHandler = array(); //was null // universalModalHandler will content id_modal = ID of modal window, status = TRUE/FALSE for visible/hidden
    public $pricelist_partner_only = FALSE;


    /** @persistent */
    public $pricelistAll = FALSE;

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
     * @var \App\Model\PartnersManager
     */
    public $PartnersManager;


    /**
     * @inject
     * @var \App\Model\PartnersBranchManager
     */
    public $PartnersBranchManager;


    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;

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


    public function __construct(\Nette\DI\Container $container, Halasz\Support\Support\ISupportFormFactory $SupportFormFactory) {
        parent::__construct($container);

       // $this->parameters = $container->getParameters();
        $this->supportForm = $SupportFormFactory;
    }

   public function actionDefault()
    {
        $httpRequest = $this->getHttpRequest();
        $query = $httpRequest->getQuery();
        $b2b_key = (isset($query['b2b_key'])) ? $query['b2b_key'] : $b2b_key = NULL;

        if (!$this->getUser()->isLoggedIn())
        {
            $mySection = $this->session->getSection('company');
            if (!is_null($b2b_key) || isset($mySection['cl_partners_book_id'])){

                if (isset($mySection['cl_partners_book_id'])){
                    $tmpPartnerBook = $this->PartnersManager->findAllTotal()->where('id = ?', $mySection['cl_partners_book_id'])->fetch();
                }else {
                    $tmpPartnerBook = $this->PartnersManager->findAllTotal()->where('b2b_key = ? AND b2b_public = 1', $b2b_key)->fetch();
                }
                if ($tmpPartnerBook) {
                    $mySection['cl_company_id'] = $tmpPartnerBook->cl_company_id;
                    $mySection['cl_partners_book_id'] =  $tmpPartnerBook->id;
                    $cl_partners_book_id = $tmpPartnerBook->id;
                    $mySection['cl_partners_branch_id'] = NULL;
                    $cl_partners_branch_id = NULL;
                }else{
                    $this->flashMessage($this->translator->translate('Zadali_jste_neplatný_odkaz'), 'warning');
                    $this->redirect(':B2B:Homepage:default');
                    return;
                }
            }else {
                $this->redirect(':B2B:Homepage:default');
                return;
            }
        }else{
            $cl_partners_book_id = $this->user->getIdentity()->cl_partners_book_id;
            $cl_partners_branch_id = $this->user->getIdentity()->cl_partners_branch_id;
        }
        $this->setLayout(__DIR__ . '/../templates/@layoutmain.latte');

        if (!is_null($cl_partners_book_id)) {
            $tmpPartner = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->fetch();
            $this->pricelist_partner_only = $tmpPartner->pricelist_partner_only;
            //24.06.2020 - set
            if (is_null($cl_partners_branch_id)) {
                $arrBranch = $this->PartnersBranchManager->findAll()->
                    where('cl_partners_book_id = ?', $tmpPartner->id)->
                    order('item_order')->limit(1)->fetch();
                $arrBranchId = NULL;
                if ($arrBranch) {
                    $cl_partners_branch_id = $arrBranch['id'];
                }
                if ($this->getUser()->isLoggedIn()) {
                    $this->getUser()->getIdentity()->cl_partners_branch_id = $cl_partners_branch_id;
                }
                //$this->getUser()->getIdentity()->lang = $values['lang'];
            }
        }
    }


/*    protected function createComponentSupportForm()
    {
        return $this->supportForm->create();
    }
*/
    protected function createComponentSupportForm()
    {
        $translator = $this->translatorMain;
        $translator->setPrefix(array('supportForm' => 'supportForm'));
        $supportForm = $this->supportForm->create($translator);
        return $supportForm;
    }


    public function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['B2BModule.Base']);
    }

    public function beforeRender()
    {
        parent::beforeRender(); // TODO: Change the autogenerated stub

        $this->template->pricelistAll = $this->pricelistAll;
        $this->template->unMoHandler = $this->unMoHandler;
        $this->template->pdfPreviewData = $this->pdfPreviewData;
        $this->template->locale = $this->translatorMain->getLocale();
        //$this->template->locale = 'cs';
        $this->template->logged = $this->getUser()->isLoggedIn();
        $this->template->modal = FALSE;
        $this->template->lattePath = $this->getLattePath();
        if ($this->getUser()->isLoggedIn()) {
            $cl_company_id = $this->getUser()->getIdentity()->cl_company_id;
            $cl_partners_book_id = $this->user->getIdentity()->cl_partners_book_id;
            $cl_partners_branch_id = $this->user->getIdentity()->cl_partners_branch_id;
            $this->template->role = $this->user->isInRole('b2b') ? 'b2b' : 'user';
        }else {
            $mySection = $this->session->getSection('company');
            $cl_company_id = $mySection['cl_company_id'];
            $cl_partners_book_id = $mySection['cl_partners_book_id'];
            $cl_partners_branch_id = $mySection['cl_partners_branch_id'];
            $this->template->role = 'b2b';
        }
        $tmpPartnersBranchId = is_null($cl_partners_branch_id) ? 0 : $cl_partners_branch_id;
        $this->template->userCompany = $this->PartnersManager->findAllTotal()->where('id = ?', $cl_partners_book_id)->fetch();
        $this->template->userBranch = $this->PartnersBranchManager->findAllTotal()->where('id = ?', $tmpPartnersBranchId)->fetch();
        $this->template->cl_partners_book_id = $cl_partners_book_id;
        $this->template->version = $this->parameters['app_version'];
        $this->template->debugMode = $this->parameters['debugMode'];
        $this->updateMenuBasket();
        $this->template->currencyName = $this->currencyName;
        $this->template->itemsSum = $this->itemsSum;
        $this->template->itemsCount = $this->itemsCount;

        $this->template->pricelist_partner_only = $this->pricelist_partner_only;

       /// $this->redrawControl('content');

    }

    public function handleLogout()
    {
        if ($this->user->isLoggedIn()){
            $mySection = $this->session->getSection('company');
            $mySection['cl_company_id'] = NULL;
            $mySection['cl_partners_book_id'] = NULL;
            $mySection['cl_partners_branch_id'] = NULL;
            $this->user->logout(TRUE);
        }

        $this->redirect(':B2B:Homepage:default');
    }

    public function updateMenuBasket(){
        if ($this->getUser()->isLoggedIn()) {
            $basket = $this->B2BOrderManager->getBasket($this->user->getIdentity()->cl_partners_book_id,
                $this->user->getIdentity()->cl_partners_branch_id);
            if ($basket) {
                $this->itemsCount = $basket['item_count'];
                $this->itemsSum = $basket['price_e2_vat'];
            }
            $this->currencyName = $basket['cl_currencies']['currency_name'];
        }
        $this->redrawControl('header');
        $this->redrawControl('menuBasketBadge');
    }

    /** returns picture
     * @param type $type
     */
    public function handleGetImage($id, $sizex = 0, $sizey = 0)
    {
        $mySection = $this->session->getSection('company');

        //$row = $this->UserManager->getUserById($id);
        if ($file = $this->FilesManager->find($id))
        {
            if ($file->new_place == 0) {
                $file = __DIR__ . "/../../../data/files/" . $file->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($mySection['cl_company_id']);
                $subFolder = $this->ArraysManager->getSubFolder($file);
                $file =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
            }

            if (file_exists($file))
            {
                //$image = Image::fromFile($file);
                //if ($sizex > 0 || $sizey > 0){
                //    $image->resize($sizex, $sizey);
                //}
                //$image->send(Image::JPEG, 60);

                $httpResponse = $this->getHttpResponse();
                $httpResponse->setHeader('Pragma', "public");
                $httpResponse->setHeader('Expires', 0);
                $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
                $httpResponse->setHeader('Content-Transfer-Encoding', "binary");
                $httpResponse->setHeader('Content-Description', "File Transfer");
                $httpResponse->setHeader('Content-Type', filetype($file));
                $httpResponse->setHeader('Content-Length', filesize($file));
                $httpResponse->setHeader('Content-Disposition', "inline; filename=\"{$file}\"\n");
                //$this->sendResponse(new DownloadResponse($file, basename($file) , array('application/octet-stream', 'application/force-download', 'application/download')));
                $this->sendResponse(new \Nette\Application\Responses\FileResponse($file, basename($file), 'application/download'));

            }
        }
    }

    public function handleBack()
    {
        if (isset($mySection['url']) && !is_null($mySection['url']))
            $this->redirectUrl($mySection['url']);
        else
            $this->redirect('this');


    }



}
