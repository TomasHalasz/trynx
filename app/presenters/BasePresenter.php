<?php
declare(strict_types=1);


namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Utils\DateTime;
use Nette\Utils\Strings,
    Nette\Caching;
use Tracy\Debugger;


/**
 * Base presenter for all application presenters.
 * @property-read \SystemContainer $context
 */

//Nette\Application\UI\Presenter

abstract class BasePresenter extends \Nittro\Bridges\NittroUI\Presenter
{

    /** @persistent */
    public $locale;

    /** @var Nette\Localization\ITranslator @inject */
    public $translatorMain;

    public $translator;

    /** @var \App\Model\CompaniesManager */
    public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\VersionsManager
     */
    public $versionManager;

    /** @var \Contributte\Translation\LocalesResolvers\Session @inject */
    public $translatorSessionResolver;
    public $mainTableName = '';
    public $eventsModalShow, $emailModalShow = FALSE;
    public $docTemplate, $docAuthor, $docTitle, $docEmail, $csv_h, $csv_i;
    public $pdfPreviewData = NULL;
    public $pdfPreviewId = NULL;
    protected $pdfFileName, $csvFileName, $csvFileName2, $csvFileName3;
    protected $emlPreview = TRUE;
    public $emailData = [];
    public $DataManager;
    public $settings;
    public $startTime;
    public $docLang = 'CZ';

    /**
     * @inject
     * @var Caching\IStorage
     */
    public $cache;

    public $parameters;

    public function __construct(\Nette\DI\Container $container)
    {

        parent::__construct();

        $this->parameters = $container->getParameters();
        $this->startTime = $this->microtime_float();

    }

    public function beforeRender()
    {
        $this->template->version = $this->parameters['app_version'];
    }

    public function getTime()
    {
        return round(($this->microtime_float() - $this->startTime) * 1000, 0);
    }

    /**
     * Simple function to replicate PHP 5 behaviour
     */
    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }


    protected function startup()
    {
        parent::startup();
        $this->translator = $this->translatorMain->createPrefixedTranslator('messages');
        if ($this->user->isLoggedIn()) {
            $lang = $this->user->getIdentity()->lang;
        } else {
            $httpRequest = $this->getHttpRequest();
            $langs = ['en', 'cs']; // jazyky podporované aplikací
            $lang = $httpRequest->detectLanguage($langs); // en
        }
        if (is_null($lang) || $lang == '')
            $lang = 'cs';

        $this->translatorMain->setLocale($lang);

        $this->setDefaultSnippets(['content', 'header', 'headerMain', 'custom_js', 'titleHtml2', 'scriptTime','showCommentMain']);

        $mySection = $this->session->getSection('docLang');
        if (isset($mySection['selectedLang']))
            $this->docLang = $mySection['selectedLang'];
       // $this->session->close();

    }

    /**
     * @inject
     * @var \MainServices\ValidatorService
     */
    public $validatorService;

    /**
     * @inject
     * @var \MainServices\QrService
     */
    public $qrService;


    /**
     * @inject
     * @var \MainServices\EmailService
     */
    public $emailService;


    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;


    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\EmailingTextManager
     */
    public $EmailingTextManager;

    /**
     * @inject
     * @var \App\Model\UserManager
     */
    public $UserManager;

    /*protected function createTemplate($class = NULL): Nette\Application\UI\ITemplate
    {
		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter('loader' , $this->translator->createTemplateHelpers());
		return $template;
    } */

    public function invalidSubmit($form)
    {
        foreach ($form->errors as $k => $error) {
            $this->presenter->flashMessage($error, 'error');
        }
        $form->cleanErrors();
    }


    public function RGBtoHex($r, $g, $b)
    {
        $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
        return $hex;
    }

    public function handleProgressUpdate()
    {
        /*$retArr = array();
        if ($this->user->isLoggedIn()) {
            $user = $this->UserManager->getUserById($this->user->getIdentity()->id);
            $retArr = array('progressValue' => $user->progress_val, 'progressMax' => $user->progress_max, 'progressMessage' => $user->progress_message);
        }
        echo (json_encode($retArr));
        */
        //session_write_close();
        $retStr = $this->UserManager->getProgressBar($this->user->getIdentity()->id);
        echo($retStr);
        //$this->payload->data = $retStr;
        //$this->sendPayload();
        //session_write_close();
        $this->terminate();
    }




    /* 04.07.2018 - create document for later use as PDF or to attache in email
     *
     */
    public function createDocument($data, $latteIndex = NULL, $dataOther = array())
    {
        /*$this->cache->clean([
            Caching\Cache::TAGS => ['reports']
        ]);*/
        $arrDefaultReport = json_decode($this->getUser()->getIdentity()->default_report ?? '', true );
        $latteIndexNew = $arrDefaultReport[$this->presenter->name] ?? NULL;
        if ($latteIndexNew != NULL) {
            $tmpTemplate = $this->docTemplate[$latteIndexNew]['file'];
            //  bdump($tmpTemplate);
        }elseif (isset($this->docTemplate[0]['file']))  {
            $tmpTemplate = $this->docTemplate[0]['file'];
        }elseif ($latteIndex != NULL) {
            $tmpTemplate = $this->docTemplate[$latteIndex]['file'] ?? $this->docTemplate[$latteIndex];
        } else {
            $tmpTemplate = $this->docTemplate;
        }
        //$tmpTitle = "Zakázka ".$data->cm_number;
        $this->genFileName($latteIndex, $data);
        //die;
        $dataOther['identity'] = $this->user->getIdentity();
        $tmpAuthor = $this->docAuthor;
       // bdump($this->docLang);
        $template = $this->createMyTemplate($data, $tmpTemplate, $this->docTitle[3], $tmpAuthor, $dataOther);
        return ($template);
    }

    public function handleSetDocLang($lang){
        $mySection = $this->session->getSection('docLang');
        $mySection['selectedLang'] = $lang;
      //  $this->session->close();
        $this->docLang = $lang;
        $this->bscToolbar['langDoc']['group_settings']['show_selected'] = $lang;
        $this->flashMessage($this->translator->translate('Jazyk_dokumentu_nastaven'), 'success');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('bsc-toolbar');
    }

    public function handleSelectReport($index){
        $arrDefaultReport = json_decode($this->getUser()->getIdentity()->default_report ?? '', true);
        $arrDefaultReport[$this->presenter->name] = $index;
        $strDefaultReport =  json_encode($arrDefaultReport);
        $this->UserManager->updateUser(['id' => $this->user->getId(), 'default_report' => $strDefaultReport]);
        $this->getUser()->getIdentity()->default_report = $strDefaultReport;

        $this->terminate();
    }

    public function handleDownloadPDF($id, $latteIndex = NULL, $dataOther = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $this->handleSavePDF($id, $latteIndex, $dataOther, $noDownload, TRUE);
    }

    /*04.07.2018 - default handle to save document into PDF
     *
     */
    public function handleSavePDF($id, $latteIndex = NULL, $dataOther = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        //bdump($this->user->isLoggedIn());
        if ($this->isAllowed($this->name, 'report') || !$this->user->isLoggedIn() || $this->user->getIdentity()->b2b_enabled) {
            $fileName = "";
            $dataOther['stamp'] = $this->CompaniesManager->getStamp();
            $dataOther['logo'] = $this->CompaniesManager->getLogo();
            if ($tmpData = $this->DataManager->find($id)) {
                Debugger::log('SavePDF cl_invoice_id = ' .  $id, 'transportAPI');
                $tmpTemplate = $this->createDocument($tmpData, $latteIndex, $dataOther);
                //$this->pdfCreate($tmpTemplate, '', $noDownload);
                $this->pdfCreate($tmpTemplate, '', TRUE, $noPreview);
            }

            //bdump($this->pdfFileName, 'handleSavePDF');
            if ($noDownload) {
                return $this->pdfFileName;
            } else {
                //   return;
                /*$colName = $this->mainTableName . '_id';
                $type = 1; //1 for PDF
                if ($tmpFile = $this->FilesManager->findAll()->where(array($colName => $this->id, 'document_file' => $type))->fetch()) {
                    $dataFolder = $this->CompaniesManager->getDataFolder($this->getUser()->getIdentity()->cl_company_id);
                    $subFolder = $this->ArraysManager->getSubFolder($tmpFile);
                    $fileName = $dataFolder . '/' . $subFolder . '/' . $tmpFile->file_name;
                    if (is_file($fileName))
                        $this->pdfPreviewData = file_get_contents($fileName);
                    else
                        $this->pdfPreviewData = NULL;
                }*/
                if (is_file($this->pdfFileName))
                    $this->pdfPreviewData = file_get_contents($this->pdfFileName);
                else
                    $this->pdfPreviewData = NULL;

                //bdump($this->pdfPreviewData);
                $this->redrawControl('pdfPreview');
                $this->showModal('pdfModal');

            }
        } else {
            $this->flashMessage($this->translator->translate('Ke zvolené akci nemáte oprávnění!'), 'danger');
            $this->redrawControl('content');
        }
    }

    /*22.07.2018 - universal handle for sending doc via email
     *
     */
    public function handleSendDoc($id, $latteIndex = NULL, $dataOther = [], $recepients = [], $emailingTextIndex = NULL)
    {
        $this->emailModalShow = TRUE;
        $template = $this->createTemplate();
        $template->setFile($this->docEmail['template']);
        $data = $this->DataManager->find($id);

        $this->createDocument($data, $latteIndex, $dataOther);
        //bdump($data,'data');
        $data = $this->DataManager->find($id);

        $template->data = $data;
        $template->url = $this->link('//:Application:Documents:Show', $data->cl_company_id, $data->cl_documents->key_document);

        //$emailingText = $this->getEmailingText('commission', $template->url, $data);
        $emailingText = $this->getEmailingText($this->docEmail['emailing_text'], $template->url, $data, $emailingTextIndex);
        //dump($emailingText);
        $template->emailBody = $emailingText['body'];

        //12.1. emailToWorkers - available emails from cl_partners_book_workers
        if (isset($data['cl_partners_branch_id']) && !is_null($data['cl_partners_branch_id'])) {
            $tmpEmailToWorkers = $this->PartnersManager->findAll()->select('IF(:cl_partners_book_workers.worker_name = "", cl_partners_book.company, :cl_partners_book_workers.worker_name) AS worker_name, :cl_partners_book_workers.worker_email')->
            where('cl_partners_book.id = ? AND :cl_partners_book_workers.use_' . $this->mainTableName . ' = ? AND 
                            (cl_partners_branch_id = ? OR cl_partners_branch_id IS NULL)', $data->cl_partners_book_id, 1, $data['cl_partners_branch_id'])->
            fetchPairs('worker_name', 'worker_email');
        } else {
            $tmpEmailToWorkers = $this->PartnersManager->findAll()->select('IF(:cl_partners_book_workers.worker_name = "", cl_partners_book.company, :cl_partners_book_workers.worker_name) AS worker_name, :cl_partners_book_workers.worker_email')->
            where('cl_partners_book.id = ? AND :cl_partners_book_workers.use_' . $this->mainTableName . ' = ?', $data->cl_partners_book_id, 1)->
            fetchPairs('worker_name', 'worker_email');
        }

        //bdump($tmpEmailToWorkers);
        if (count($tmpEmailToWorkers) == 0) {
            if (!is_null($data->cl_partners_book) && !empty($data->cl_partners_book['email'])) {
                $tmpName = (!empty($data->cl_partners_book['person'])) ? $data->cl_partners_book['person'] : $data->cl_partners_book['company'];
                $tmpEml = $this->validateEmail($data->cl_partners_book['email']);
                if ($tmpEml == '')
                    $tmpEmailToWorkers = [$tmpName => $tmpEml];
            }
        }
        //single emailTo which is set on sending doc
        //            $tmpEmail = $this->validateEmail($one);
        if (isset($data['cl_partners_book_workers_id']) && !is_null($data['cl_partners_book_workers_id'])) {
            if ($data->cl_partners_book_workers['use_' . $this->mainTableName] == 1 && $this->validateEmail($data->cl_partners_book_workers->worker_email) != '') {
                $tmpEmailTo = ((!empty($data->cl_partners_book_workers->worker_name)) ? $data->cl_partners_book_workers->worker_name :  $data->cl_partners_book['company']) . ' <' . $data->cl_partners_book_workers->worker_email . '>';
                //04.09.2020 - remove duplicate emails
                $isWrk = array_search($data->cl_partners_book_workers->worker_email, $tmpEmailToWorkers);
                if ($isWrk)
                    unset($tmpEmailToWorkers[$isWrk]);

            } else {
                $tmpEmailTo = "";
            }


        } else {
            //24.05.2019 - test if is available record id cl_partners_book
            if (!is_null($data->cl_partners_book_id)) {
                $tmpEmail = str_replace(';', ',', $data->cl_partners_book->email);
                $tmpArrEmail = str_getcsv($tmpEmail, ',');
                $tmpEmailTo = "";
                foreach ($tmpArrEmail as $one) {
                    if ($one != '' && $this->validateEmail($one) != '') {
                        if ($tmpEmailTo != '') {
                            $tmpEmailTo .= ';';
                        }
                        $tmpEmailTo .= $data->cl_partners_book->company . ' <' . $one . '>';
                    }
                    //04.09.2020 - remove duplicate emails
                    $isWrk = array_search($one, $tmpEmailToWorkers);
                    if ($isWrk)
                        unset($tmpEmailToWorkers[$isWrk]);
                }
            } else {
                $tmpEmailTo = "";
            }
        }

        //07.04.2021 - move first worker into emailto if there is no emailTo
        if (empty($tmpEmailTo) && count($tmpEmailToWorkers) > 0) {
            foreach ($tmpEmailToWorkers as $keyEm => $oneEm) {
                $tmpEmailTo = $keyEm . ' <' . $oneEm . '>';
                unset($tmpEmailToWorkers[$keyEm]);
                break;
            }

        }

        //23.03.2022 - override recepients
        if (count($recepients) > 0 ){
            $tmpEmailTo = '';
            foreach ($recepients as $key => $one) {
                if ($one != '') {
                    if ($tmpEmailTo != '') {
                        $tmpEmailTo .= ';';
                    }
                    $tmpEmailTo .= $key . ' <' . $one . '>';
                }
            }
        }

        //14.07.2022 - TH
        $mySection = $this->session->getSection('company');
        if (is_null($mySection['cl_company_id'])) {
            $company_id = $this->UserManager->getCompany($this->user->getId());
        }else{
            $company_id = $mySection['cl_company_id'];
        }
      //  $this->session->close();

        //bdump($emailingText['activeRow']);
        if (!is_null($emailingText['activeRow'])) {
            if ($emailingText['activeRow']->attach_pdf) {
                $pdfFileName = FALSE;
                $pdfFileName = $this->handleSavePDF($id, $latteIndex, $dataOther, TRUE);
                // bdump($pdfFileName);
                $arrAttachment[] = $pdfFileName;
                //dump($arrAttachment);
            }

            if ($emailingText['activeRow']->attach_files) {
                $type = $this->DataManager->tableName . '_id';
                $sendFiles = $this->FilesManager->findAll()->where($type . ' = ?', $id);
                foreach($sendFiles as $key => $file){
                    $dataFolder = $this->CompaniesManager->getDataFolder($file->cl_company_id);
                    $subFolder = $this->ArraysManager->getSubFolder($file);
                    $fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
                    if (file_exists($fileSend) && $pdfFileName != $fileSend)
                        $arrAttachment[] = $fileSend;
                }

            }


            if ($emailingText['activeRow']->attach_csv_h) {
                $this->genFileName($latteIndex, $data);
                $csvFileName = $this->docTitle[3] . " doklad";

                $csv = new \CsvResponse\NCsvResponse($this->DataManager->findAll()->where($this->mainTableName . '.id = ?', $id)->select($this->csv_h['columns']), $csvFileName . "-" . date('Ymd-Hi') . ".csv", true);
               $this->csvFileName = APP_DIR . '/../data/' . $company_id . '/' . Strings::webalize($csvFileName) . '.csv'; //$this->getUser()->getIdentity()->cl_company_id
                file_put_contents($this->csvFileName, $csv->getCsv());
                $this->fileAfterComplete(2);
                $arrAttachment[] = $this->csvFileName;
            }

            if ($emailingText['activeRow']->attach_csv_i) {
                $this->genFileName($latteIndex, $data);

                $csvFileName = $this->docTitle[3] . " položky";
                $dataItems = $this->DataManager->find($id)->related($this->csv_i['datasource'])->select($this->csv_i['columns']);
                if ($dataItems->count() > 0) {
                    $csv = new \CsvResponse\NCsvResponse($dataItems, $csvFileName . "-" . date('Ymd-Hi') . ".csv", true);
                    $this->csvFileName = APP_DIR . '/../data/' . $company_id . '/' . Strings::webalize($csvFileName) . '.csv'; //$this->getUser()->getIdentity()->cl_company_id
                    file_put_contents($this->csvFileName, $csv->getCsv());
                    $this->fileAfterComplete(3);
                    $arrAttachment[] = $this->csvFileName;
                }
                if (isset($this->csv_i['datasource2'])) {
                    $csvFileName = $this->docTitle[3] . " položky zpět";
                    $dataItems = $this->DataManager->find($id)->related($this->csv_i['datasource2'])->select($this->csv_i['columns2']);
                    if ($dataItems->count() > 0) {
                        $csv = new \CsvResponse\NCsvResponse($dataItems, $csvFileName . "-" . date('Ymd-Hi') . ".csv", true);
                        $this->csvFileName = APP_DIR . '/../data/' . $company_id  . '/' . Strings::webalize($csvFileName) . '.csv'; //$this->getUser()->getIdentity()->cl_company_id
                        file_put_contents($this->csvFileName, $csv->getCsv());
                        $this->fileAfterComplete(3);
                        $arrAttachment[] = $this->csvFileName;
                    }
                }


            }
        } else {
            $arrAttachment = [];
            $this->flashMessage($this->translator->translate('Pro_tento_typ_dokumentu_není_definována_šablona_emailu', 'danger'));
        }

        $emailFrom = '';
        if (!is_null($data->cl_users_id) && !empty($data->cl_users['email'])) {
            $emailFrom = $this->validateEmail($data->cl_users['email']);
            if (empty($emailFrom)) {
                $emailFrom = $this->validateEmail($data->cl_users['email2']);
            }
            if (!empty($emailFrom)) {
                $emailFrom = $data->cl_users['name'] . ' <' . $emailFrom . '>';
            }
        }

        if (empty($emailFrom)) {
            if (!empty($this->validateEmail($this->settings->email))) {
                $emailFrom = (empty($this->settings->email_name) ? $this->settings->name : $this->settings->email_name) . ' <' . $this->settings->email . '>';
            }
        }

        //29.07.2021 - global email
        if ($this->settings->smtp_email_global == 1) {
            $emailFrom = $this->validateEmail($this->settings->smtp_username);
            if (empty($emailFrom)) {
                $emailFrom = $this->validateEmail($this->settings->email);
            }
            $emailFrom = (empty($this->settings->email_name) ? $this->settings->name : $this->settings->email_name) . ' <' . $emailFrom . '>';
        }

        if (empty($emailFrom)) {
            $this->flashMessage($this->translator->translate('Není_zadána_platná_emailová_adresa_odesílatele'), 'danger');
        }
        $this->emailData = ['singleEmailFrom' => $emailFrom,
            'singleEmailTo' => $tmpEmailTo,
            'workers' => $tmpEmailToWorkers,
            'subject' => $emailingText['subject'],
            'body' => (string)$template,
            'attachment' => json_encode($arrAttachment)];
        //Debugger::fireLog($this->emailData);

        if ($this->emlPreview) {
            //bdump($this->emailData,'emailDataXX');
            //bdump($this->emailData,'emailDataXX');
            //$this->presenter->emailModalShow = TRUE;
            $this['email']->setEmailData($this->emailData);
            //$this->redrawControl('content');
            $this->redrawControl('emailCtrl');
            $this->redrawControl('bscAreaEdit');
        } else {

        }


    }

    /** check validity of email, return empty string if it is not valid
     * @param $eml
     * @return mixed|string
     */
    public function validateEmail($eml)
    {
        if (!filter_var($eml, FILTER_VALIDATE_EMAIL)) {
            $eml = "";
        }
        return $eml;
    }

    private function genFileName($latteIndex, $data)
    {
        $arrDefaultReport = json_decode($this->getUser()->getIdentity()->default_report ?? '', true );
        $latteIndexNew     = $arrDefaultReport[$this->presenter->name] ?? null;

        if ($latteIndex == NULL && is_null($latteIndexNew)) {
            $this->docTitle[3] = $this->docTitle[0];
            if (isset($this->docTitle[1])) {
                $csvF = str_getcsv($this->docTitle[1], '.');
                $counterF = count($csvF);
                if ($counterF == 2) {
                    $this->docTitle[3] .= " " . $data[$csvF[0]][$csvF[1]];
                } else {
                    $this->docTitle[3] .= " " . $data[$this->docTitle[1]];
                }
            }
            if (isset($this->docTitle[2])) {
                $csvF = str_getcsv($this->docTitle[2], '.');
                $counterF = count($csvF);
                if ($counterF == 2) {
                    $this->docTitle[3] .= " " . $data[$csvF[0]][$csvF[1]];
                } else {
                    $this->docTitle[3] .= " " . $data[$this->docTitle[2]];
                }
            }
        }else {


            if (!is_null($latteIndexNew))
                $latteIndex = (int)$this->docTemplate[$latteIndexNew]['key'];
            elseif (isset($this->docTemplate[0]['file']))
                $latteIndex = 0;

            //$this->docTitle[3] = $this->docTitle[$latteIndex][0] . " " . $data[$this->docTitle[$latteIndex][1]] . " " . $data[$this->docTitle[$latteIndex][2]];
            $this->docTitle[3] = $this->docTitle[$latteIndex][0];
            bdump($latteIndex, 'latteIndex');
            bdump($this->docTitle);
            //bdump($latteIndex,'latteIndex');
            if (isset($this->docTitle[$latteIndex][1])) {
                $csvF = str_getcsv($this->docTitle[$latteIndex][1], '.');
                $counterF = count($csvF);
                if ($counterF == 2) {
                    $this->docTitle[3] .= " " . $data[$csvF[0]][$csvF[1]];
                } else {
                    $this->docTitle[3] .= " " . $data[$this->docTitle[$latteIndex][1]];
                }
            }
            if (isset($this->docTitle[$latteIndex][2])) {
                $csvF = str_getcsv($this->docTitle[$latteIndex][2], '.');
                $counterF = count($csvF);
                // bdump($this->docTitle[$latteIndex]);
                // bdump($this->docTitle[$latteIndex][2]);
                // bdump($csvF);
                if ($counterF == 2) {
                    $this->docTitle[3] .= " " . $data[$csvF[0]][$csvF[1]];
                } elseif ($counterF == 1) {
                    $this->docTitle[3] .= " " . $data[$csvF[0]];
                    //$this->docTitle[3] .= " " . $data[$this->docTitle[$latteIndex][2]];
                } else {
                    $this->docTitle[3] .= " " . $data[$this->docTitle[$latteIndex][2]];
                }
                //bdump($this->docTitle[3]);
            }
            bdump($this->docTitle[3]);
        }
    }

    public function getEmailingText($use, $url, $data, $id = NULL)
    {
        return ($this->EmailingTextManager->getEmailingText($use, $url, $data, $id));
    }

    public function getEmailingTexts($use)
    {
        return ($this->EmailingTextManager->getEmailingTexts($use));
    }


    public function isModuleAllowed($presenterName)
    {
        return ($this->UserManager->isModuleAllowed($presenterName, $this->getUser()->id));
    }

    public function isAllowed($presenterName, $actionName, $special = NULL)
    {
        return ($this->UserManager->isAllowed($presenterName, $actionName, $this->getUser()->id, $special));

    }

    public function fileAfterComplete($type = 1)
    {
        Debugger::log('fileAfterComplete type = ' .  $type, 'transportAPI');
        if (is_null($this->pdfFileName) && is_null($this->csvFileName)) {
            return;
        }

        //bdump($this->ArraysManager->getSubFolder(array($this->mainTableName.'_id' => $this->id), NULL));
        $colName = $this->mainTableName . '_id';
        Debugger::log('fileAfterComplete $colName = ' .  $colName . ' $this->id = ' . $this->id, 'transportAPI');
        if ($this->ArraysManager->getSubFolder([$colName => $this->id], NULL) != '') {
            //22.07.2022 - TH
            $mySection = $this->session->getSection('company');
            if (is_null($mySection['cl_company_id'])) {
                $company_id = $this->UserManager->getCompany($this->user->getId());
            }else{
                $company_id = $mySection['cl_company_id'];
            }
            //$this->session->close();
            //end 22.07.2022 - TH
            $dataFolder = $this->CompaniesManager->getDataFolder($company_id);
            $destFile = NULL;
            if ($type == 1)
                $fileName = basename($this->pdfFileName);
            elseif ($type == 2 || $type == 3)
                $fileName = basename($this->csvFileName);


            //find existed cl_files.document_file for this->id and erase it
            if ($tmpFile = $this->FilesManager->findAll()->where([$colName => $this->id, 'document_file' => $type, 'file_name' => $fileName])->fetch()) {
                $subFolder = $this->ArraysManager->getSubFolder($tmpFile);
                $fileDel = $dataFolder . '/' . $subFolder . '/' . $tmpFile->file_name;
                if (file_exists($fileDel))
                    unlink($fileDel);

                $tmpFile->delete();
                //$this->flashMessage('Soubor byl vymazán.', 'success');
            }


            //bdump($fileName);
            $i = 0;
            $arrFile = str_getcsv($fileName, '.');
            // $dataFolder = $this->CompaniesManager->getDataFolder($this->getUser()->getIdentity()->cl_company_id);
            $subFolder = $this->ArraysManager->getSubFolder([], $this->mainTableName);
            while (is_null($destFile) || file_exists($destFile)) {
                if (!is_null($destFile)) {
                    $fileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];
                }
                $destFile = $dataFolder . '/' . $subFolder . '/' . $fileName;
                $i++;
            }
            $data = new \Nette\Utils\ArrayHash;
            if ($type == 1) {
                Debugger::log('fileAfterComplete $this->pdfFileName = ' .  $this->pdfFileName . ' $destFile = '  . $destFile, 'transportAPI');
                rename($this->pdfFileName, $destFile);
                $this->pdfFileName = $destFile;  //pdfFileName is used in attachment array for email
                $data['document_file'] = $type;
            } elseif ($type == 2 || $type == 3) {
                //bdump(basename($this->csvFileName));
                //bdump($this->csvFileName);
                //bdump(basename($destFile));
                //bdump($destFile);

                rename($this->csvFileName, $destFile);

                //copy($this->csvFileName, $destFile);
                //bdump('ted');
                $this->csvFileName = $destFile;  //pdfFileName is used in attachment array for email
                $data['document_file'] = $type;
            }

            $data['file_name'] = $fileName;
            $data['label_name'] = $fileName;
            $data['mime_type'] = mime_content_type($destFile);
            $data['file_size'] = filesize($destFile);
            $data['create_by'] = 'fileWriter';
            $data['created'] = new \Nette\Utils\DateTime;
            if (!$this->getUser()->isInRole('b2b')) {
                $data['cl_users_id'] = $this->getUser()->id;
            }
            $data[$colName] = $this->id;
            $row = $this->FilesManager->insert($data);
            $retId = $row['id'];
            if ($type == 1 && ($tmpData = $this->DataManager->find($this->id))) {
                //TH 22.05.2022 - update file ID only in casa of PDF file
                if (!is_null($tmpData['cl_documents_id'])) {
                    $tmpData->cl_documents->update(array('cl_files_id' => $retId));
                }
            }
        } else {
            //   $retId = null;
        }
        // return $retId;
    }


    /*create template from given values without saving to cl_documents
         * 28.07.2018 - TH
         */
    public function createMyTemplateWS($data, $tmpTemplateFile, $arrDataOther = array(), $dataSettings = array(), $tmpTitle = "")
    {
        $this->cache->clean([
            Caching\Cache::TAGS => ['reports']
        ]);

        $arrDataOther['stamp'] = $this->CompaniesManager->getStamp();
        $arrDataOther['logo'] = $this->CompaniesManager->getLogo();
        $template = $this->createTemplate()->setFile($tmpTemplateFile);

        if (isset($data['cl_partners_book']['lang'])) {
            $lang = $data['cl_partners_book']['lang'];
        } else {
            $lang = 'cs';
            //$lang = $this->getUser()->getIdentity()->lang;
        }


        $this->translatorMain->setLocale($lang);
        //$this->translatorMain->setPrefix(['messages']);
        $template->setTranslator($this->translatorMain);

        /*    $lang = $this->getUser()->getIdentity()->lang;
            if ($lang == ''){
                $lang = 'cs';
            }

        $template = $this->createTemplate()->setFile($tmpTemplateFile);
        $this->translator->setLocale($lang);
        $this->translator->setPrefix(array('reportsModule' => 'reportsModule'));
        $template->setTranslator($this->translator);*/

        ////$this->translator->setPrefix(['reportsModule']);

        $template->data = $data;
        $template->settings = $this->settings;
        $template->dataOther = $arrDataOther;
        $template->author = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $template->title = $tmpTitle;
        $template->today = new \Nette\Utils\DateTime;
        //foreach($arrData as $key => $one)
        //{
        //  $template[$key] = $one;
        //}
        $template->dataSettings = $dataSettings;
        //$template->dataSettingsPartners = $dataSettingsPartners;

        $template->logo = $this->tmpLogo();
        $template->stamp = $this->tmpStamp();
        return $template;
    }


    /*Create template and save it to cl_documents and update data->cl_documents_id
     *
     */
    public function createMyTemplate($data, $tmpTemplateFile, $tmpTitle, $tmpAuthor, $arrData = [])
    {
        //save html template to cl_documents
        //$data = $this->DataManager->find($data['id']);
        $this->cache->clean([
            Caching\Cache::TAGS => ['reports']
        ]);

        if (isset($data['cl_partners_book']['lang'])) {
            $lang = $data['cl_partners_book']['lang'];
        } else {
            $lang = 'cs';
            //$lang = $this->getUser()->getIdentity()->lang;
        }

        $template = $this->createTemplate()->setFile($tmpTemplateFile);
        $this->translatorMain->setLocale($lang);
        //dump($lang);
        //die;
        //$this->translatorMain->setPrefix(['messages']);
        $template->setTranslator($this->translatorMain);

        $template->data = $data;
        $template->settings = $this->settings;
        $template->dataOther = $arrData;
        $template->author = $tmpAuthor;
        if ($this->user->getIdentity()) {
            $authorName = $this->user->getIdentity()->name;
        } else {
            $authorName = "";
        }
        $template->authorName = $authorName;
        $template->today = new \Nette\Utils\DateTime;

        //$template->logo = $this->tmpLogo();
        //$template->stamp = $this->tmpStamp();

        //save html to cl_documents for later use
        $tmpDocuments = new \Nette\Utils\ArrayHash;
        //$tmpDocuments['html_document'] = (string) $template;


        //$tmpDocuments['doc_author'] = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $tmpDocuments['doc_title'] = $tmpTitle;
        $tmpDocuments['doc_author'] = $tmpAuthor;
        //$tmpDocuments['doc_title'] = "Objednávka ".$data->od_number;
        $tmpDocuments['cl_company_id'] = $data->cl_company_id;
        $tmpDocuments['valid'] = 1;
        //dump(isset($data->cl_documents_id));
        //die;
        if (!isset($data->cl_documents_id)) {
            $tmpDocuments['key_document'] = \Nette\Utils\Random::generate(32, 'A-Za-z0-9');
            //check for duplicity
            while ($this->DocumentsManager->findAllTotal()->where(array('key_document' => $tmpDocuments['key_document']))->fetch()) {
                $tmpDocuments['key_document'] = \Nette\Utils\Random::generate(32, 'A-Za-z0-9');
            }
            $newDocuments = $this->DocumentsManager->insert($tmpDocuments);
        } else {
            $tmpDocuments['id'] = $data->cl_documents_id;
            //dump($tmpDocuments);
            //die;
            $newDocuments = $this->DocumentsManager->update($tmpDocuments);
            $newDocuments = new \Nette\Utils\ArrayHash;
            $newDocuments['id'] = $data->cl_documents_id;
        }
        $tmpUpdate = new \Nette\Utils\ArrayHash;
        $tmpUpdate['id'] = $data->id;
        $tmpUpdate['cl_documents_id'] = $newDocuments->id;
        $data->update($tmpUpdate);
        return $template;
    }

    public function pdfBeforeComplete()
    {
        /*if (file_exists($this->tmpStamp))
            unlink($this->tmpStamp);

        if (file_exists($this->tmpLogo))
            unlink($this->tmpLogo);
        */
    }


    public function pdfCreate($template, $docTitle = NULL, $noDownload = FALSE, $noPreview = FALSE)
    {
        //save pdf

        $pdf = new \PdfResponse\PdfResponse($template);
        //$pdf->mPDF->OpenPrintDialog();
        // Všechny tyto konfigurace jsou volitelné:
        // Orientace stránky
        $pdf->pageOrientation = \PdfResponse\PdfResponse::ORIENTATION_PORTRAIT;

        // Formát stránky
        $pdf->pageFormat = "A4";
        // Okraje stránky
        /* Margins in this order:
         * <ol>
         *   <li>top
        *   <li>right
        *   <li>bottom
        *   <li>left
        *   <li>header
        *   <li>footer
        * </ol>
         * */

        $pdf->pageMargins = "10,5,10,5,5,5";

        // Způsob zobrazení PDF
        //$pdf->displayLayout = "continuous";
        // Velikost zobrazení
        //$pdf->displayZoom = "fullwidth";
        // Název dokumentu
        if ($docTitle == NULL) {
            $pdf->documentTitle = $this->docTitle[3];
        } else {
            $pdf->documentTitle = $docTitle;
        }
        //$pdf->documentTitle="test.pdf";
        //bdump($pdf->documentTitle, "documentTitle");
        //$pdf->documentTitle = APP_DIR.'\..\..\\'.$pdf->documentTitle;
        // Dokument vytvořil:
        $pdf->documentAuthor = $this->docAuthor;
        //    bdump($noDownload, 'noDownload');

//        if ($noDownload)
//        {
        $pdf->outputDestination = \PdfResponse\PdfResponse::OUTPUT_FILE;

        if ($noPreview) {
            //$pdf->outputName = Strings::webalize($pdf->documentTitle).".pdf";
            //$pdf->outputName = "test.pdf";
            $pdf->outputDestination = \PdfResponse\PdfResponse::OUTPUT_DOWNLOAD;
        } else {
            //TH 26.07.2019 - when is set outputName, then pdfresponse send PDF to browser and save PDF to given outputName
            $companyId = $this->CompaniesManager->getTable()->fetch()->id;
            $this->pdfFileName = APP_DIR . '/../data/' . $companyId . '/' . Strings::webalize($pdf->documentTitle) . '.pdf';
            $pdf->outputName = $this->pdfFileName;
        }
        bdump($this->pdfFileName, 'pdf file name');
//        }else {
//            $pdf->outputDestination = \PdfResponse\PdfResponse::OUTPUT_DOWNLOAD;
//        }


        // Ignorovat styly v html (v tagu <style>?)
        //$pdf->ignoreStylesInHTMLDocument = true;

        // Další styly mimo HTML dokument
        //$pdf->styles .= "p {font-size: 80%;}";

        // Callback - těsně před odesláním výstupu do prohlížeče
        $pdf->onBeforeComplete[] = [$this, 'pdfBeforeComplete'];

        if ($noDownload) {
            // Callback - po odeslání výstupu do prohlížeče a uložení do souboru
            $pdf->onAfterComplete[] = [$this, 'fileAfterComplete'];
        } else {

        }

        //$pdf->mPDF->IncludeJS("app.alert('This is alert box created by JavaScript in this PDF file!',3);");
        //$pdf->mPDF->IncludeJS("app.alert('Now opening print dialog',1);");
        //$pdf->mPDF->OpenPrintDialog();
        //

        // Ukončíme presenter -> předáme řízení PDFresponse
        //$this->terminate($pdf);
        //$pdf->OpenPrintDialog();
        if ($noPreview) {
            $this->sendResponse($pdf);
        } else {
            $pdf->makePDF();
        }

        //}else {
        //$this->sendResponse($pdf);
        //$this->pdfFileName = APP_DIR . '/../data/' . $this->getUser()->getIdentity()->cl_company_id . '/' . Strings::webalize( $pdf->documentTitle ). '.pdf';
        if (!$noDownload && !$noPreview) {
            if (is_file($this->pdfFileName)) {
                $this->pdfPreviewData = file_get_contents($this->pdfFileName);
                unlink($this->pdfFileName);
            } else {
                $this->pdfPreviewData = NULL;
            }
            //bdump($this->pdfPreviewData);
            $this->redrawControl('pdfPreview');
            $this->showModal('pdfModal');
        }

        //}

    }


    public function fileCSV()
    {

    }

    /**return path to template file in current module dir
     *
     */
    public function getLattePath()
    {
        $lattePath = $this->presenter->getName();
        $nameParts = str_getcsv($lattePath, ":");
        $lattePath = $nameParts[0] . 'Module\\templates\\';
        return $lattePath;
    }


    public function handleShowFlashNow($data)
    {
        $this->redrawControl('flash');
    }


    public function handleMenuBadgeUpdate()
    {
        if ($this->presenter->name != 'Application:HelpdeskPublic') {
               $this->redrawControl('header');
               $this->redrawControl('menuEventsBadge');
        } else {
            die;
        }
    }

    public function handleScrollTop($page_b, $filterValue, $index)
    {

    }

    public function handleScrollBottom($page_b, $filterValue, $index)
    {

    }

    public function handlePaginatorUpdate($page_b, $filterValue, $index)
    {

    }

    /** removes wrong formats from form values e.g. date, numbers etc.
     * @param $data
     * @return mixed
     */
    public function removeFormat($data)
    {
        //bdump($data);
        foreach ($data as $key => $one) {
            if (isset($this->dataColumns[$key]['format']) && ($this->dataColumns[$key]['format'] == 'number' || $this->dataColumns[$key]['format'] == 'currency')) {
                $data[$key] = str_replace(' ', '', $one);
                $data[$key] = str_replace(',', '.', $data[$key]);
            }

            if (isset($this->dataColumns[$key]['format']) && $this->dataColumns[$key]['format'] == 'date') {
                if ($data[$key] == '')
                    $data[$key] = NULL;
                else
                    $data[$key] = date('Y-m-d', strtotime($data[$key]));
            }
            if (isset($this->dataColumns[$key]['format']) && $this->dataColumns[$key]['format'] == 'datetime') {
                if ($data[$key] == '')
                    $data[$key] = NULL;
                else
                    $data[$key] = date('Y-m-d H:i:s', strtotime($data[$key]));
            }
            if (isset($this->dataColumns[$key]['format']) && $this->dataColumns[$key]['format'] == 'datetime2') {
                if ($data[$key] == '')
                    $data[$key] = NULL;
                else
                    $data[$key] = date('Y-m-d H:i', strtotime($data[$key]));
            }

            //unset readonly
            //dump($key);
            if (strpos($key, '__'))
                unset($data[$key]);

        }
        //bdump($data);
        return ($data);
    }

    function br2nl($str)
    {
        $str = preg_replace("/(\r\n|\n|\r)/", "", $str);
        return preg_replace("=&lt;br */?&gt;=i", "\n", $str);
    }

    /**Check if there is differnce between two arrays
     * @param $oldData
     * @param $newData
     * @return array
     */
    public function isChange($oldData, $newData){
        $tmpChange = false;
        $arrIgnore =  ['created', 'create_by', 'changed', 'change_by'];
        foreach ($newData as $key => $one){
            if (!array_search($key, $arrIgnore))
            {
                if (!is_null($one) && gettype($one) == 'string' && strtotime($one))
                    $tmpChange1 = ($oldData[$key] != DateTime::from($one));
                else
                    $tmpChange1 = ($oldData[$key] != $one);

                //bdump($oldData[$key], 'oldData');
                //bdump($one, 'newOne');

                if ($tmpChange1) {
                    $arrKey = explode('_id', $key);
                    //get constraint key name
                    $arrFound = false;
                    foreach ($this->dataColumns as $key2 => $value2) {
                        if (strpos($key2, $arrKey[0]) === 0) {
                            $arrFound = $key2;
                            break;
                        }
                    }

                    $key3 = '';
                    if ($arrFound) {
                        $arrKeyFound = explode('.', $arrFound);
                        if (count($arrKeyFound) > 1) {
                            $key3 = $arrKeyFound[1];
                            $key2 = $arrKey[0] . '.' . $key3;
                        } else
                            $key2 = $arrFound;
                    } else
                        $key2 = 'nenalezeno';

                    if (isset($this->dataColumns) && isset($this->dataColumns[$key2])) {
                        $keyName = $this->dataColumns[$key2][0];
                        $oneData = $one;
                        if (count($arrKey) > 1) {
                            if (isset($this->dataColumns[$key2]['function'])) {
                                $lcEval = '$evalResult = $this->' . $this->dataColumns[$key2]['function'] . '(["' . $this->dataColumns[$key2]['function_param'][0] . '" => "' . $newData[$this->dataColumns[$key2]['function_param'][0]] . '"]);';
                                eval($lcEval);
                                $oneData = $evalResult;
                            } else
                                $oneData = $newData[$arrKey[0]][$key3];
                        }

                        if (!is_null($oneData) && gettype($oneData) == 'object')
                            $tmpChange[$keyName] = date_format($oneData, 'd.m.Y H:i');
                        else
                            $tmpChange[$keyName] = $oneData;
                    } else {
                        $tmpChange[$key] = $one;
                    }
                }
            }
        }



       // bdump($tmpChange, 'tmpChange');
        return $tmpChange;
    }

    /** Create email with change list and prepare for send
     * @param $tmpChange
     * @param $tmpNewData
     * @param $arrEmailTo
     * @return void
     */
    public function emlChangeNotify($tmpChange, $tmpNewData, $arrEmailTo = [], $docNumber = ''){
        $dataEml = [];
        $arrRet = [];
        //$dataEml['singleEmailTo'] = '';
        $dataEml['singleEmailTo'] = implode(';', $arrEmailTo);
        /*foreach($arrEmailTo as $key => $one){
           if ($dataEml['singleEmailTo'] != '')
               $dataEml['singleEmailTo'] .= ';';
            $dataEml['singleEmailTo'] .= $one;
        }*/

        if (!is_null($tmpNewData['cl_users_id'])) {
            $tmpEmail = $this->UserManager->getEmail($tmpNewData['cl_users_id']);
            if ($tmpEmail != '') {
                if ($dataEml['singleEmailTo'] != '') {
                    $dataEml['singleEmailTo'] .= ';';
                }
                $dataEml['singleEmailTo'] .= $tmpNewData->cl_users['name'] . ' <' . $tmpEmail . '>';
            }
        }
        if (isset($tmpNewData['cl_users2_id']) && !is_null($tmpNewData['cl_users2_id'])) {
            $tmpEmail = $this->UserManager->getEmail($tmpNewData['cl_users2_id'], TRUE);
            if (count($tmpEmail) > 0) {
                if ($dataEml['singleEmailTo'] != '') {
                    $dataEml['singleEmailTo'] .= ';';
                }
                $dataEml['singleEmailTo'] .= $tmpEmail['name'] . ' <' . $tmpEmail['email'] . '>';
            }
        }
        if ($dataEml['singleEmailTo'] != '') {
            $dataEml['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email . '>';
            if ($this->settings['smtp_email_global'] == 1) {
                $emailFrom = $this->validateEmail($this->settings['smtp_username']);
                if (!empty($emailFrom)) {
                    $dataEml['singleEmailFrom'] = $this->settings['name'] . ' <' . $emailFrom . '>';
                }
            }
            $dataEml['subject'] = $docNumber . ' - ' . $this->translator->translate('Uživatel') . ' ' . $this->user->getIdentity()->name . ' ' . $this->translator->translate('provedl_změny');
            $link = $this->link('//showBsc!', ['id' => $tmpNewData['id'], 'copy' => false]);
            //$tmpEmlText = $this->EmailingTextManager->getEmailingText('complaint', $link, $tmpNewData, NULL);
            $tmpEmlText['body'] = '<h3>Provedené změny</h3>';
            /*$tmpTable = '<table>';
            foreach($tmpChange as $key => $one){
                $tmpTable .= '<tr><td>' . $key . '</td><td>' . $one . '</td></tr>';
            }
            $tmpTable .= '</table>';*/
            $tmpTable = $this->createTemplate()->setFile(__DIR__ . '/../components/history/pureHistory.latte');
            $tmpTable->dataColumns = $this->dataColumns;
            $tmpTable->data = $tmpChange;
            $tmpEmlText['body'] .= $tmpTable;
            //bdump($tmpTable);
            $template = $this->createTemplate()->setFile(__DIR__ . '/../templates/Emailing/email.latte');
            $template->body = $tmpEmlText['body'];
            $template->link = '<a href="' . $link . '" title="Odkaz pro otevření dokladu" target="_new">Doklad: ' . $docNumber . '</a>';

            $dataEml['body'] = $template;
            $dataEml['to_send'] = 1;
            $this->EmailingManager->insert($dataEml);
            $arrRet['success'] = 'ok';
        }else{
            $arrRet['error'] = 'No email to';
        }

        return $arrRet;
    }

    public function getRealValue($key, $col){
        $arrDb = str_getcsv($col, ".");
        $retVal = "";
        if (count($arrDb) > 1){
            $db = $this->accessor->get($GLOBALS['DBNAME']);
            if (!is_null($key)) {
                $retRow = $db->table($arrDb[0])->where('id = ?', $key)->select('id,' . $arrDb[1])->fetch();
                if ($retRow) {
                    $retVal = $retRow[$arrDb[1]];
                } else {
                    $retVal = $key;
                }
            }
        }
        return $retVal;
    }


    /*05.03.2021 - return customized latte if exists */
    public function getCMZTemplate($templateFile)
    {
        if (!empty(CMZ_NAME)) {
            $fileName = str_replace(".latte", "", $templateFile);
            $fileName = $fileName . "_" . CMZ_NAME . ".latte";
            if (file_exists($fileName)) {
                $templateFile = $fileName;
            }
        }
        return $templateFile;
    }

    /**
     * Check if associative array contains an Error message
     *
     * @param array $data Usually array returned from some function
     * @return boolean
     * @example ['error' => 'This is an error message'] returns true
     * @example ['success' => ['data' => 'Some valid data to be processed']] return false
     */
    public static function hasError(array $data): bool
    {
        return (\array_key_exists('error', $data)) ? true : false;
    }


    /**
     * Helper function to do a partial search for string inside array.
     *
     * @param array  $array   Array of strings.
     * @param string $keyword Keyword to search.
     *
     * @return array
     */
    public static function array_partial_search( $array, $keyword ) {
        $found = [];

        // Loop through each item and check for a match.
        foreach ( $array as $string ) {
            // If found somewhere inside the string, add.
            if ( strpos( $string, $keyword ) !== false ) {
                $found[] = $string;
            }
        }
        return $found;
    }




    /**check if there is any version in progress mode which means new version installation has been started
     * @return string
     */
    public function getMntMode() : string{
        if ($tmpVersion = $this->versionManager->findAllTotal()->where('in_progress = 1')->limit(1)->fetch())
        {
            $ret = 'SET_ON';
        }else{
            $ret = 'SET_OFF';
        }
        return $ret;
    }

    public function centerNotify($data, $use){
        $dataEml = [];
        $docName = $this->ArraysManager->getStatusName($use);

        if (!is_null($data['cl_center_id'])) {
            $tmpData = $this->DataManager->find($data['id']);

            if (!is_null($tmpData['cl_center_id']) && !empty($this->validateEmail($tmpData->cl_center['email']))) {
                $dataEml['singleEmailTo'] = $tmpData->cl_center->name . ' <' . $tmpData->cl_center->email . '>';
                $dataEml['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email . '>';
                if ($this->settings['smtp_email_global'] == 1)
                {
                    $emailFrom = $this->validateEmail($this->settings['smtp_username']);
                    if (!empty($emailFrom)){
                        $dataEml['singleEmailFrom'] = $this->settings['name'] . ' <' . $emailFrom . '>';
                    }
                }
                $dataEml['subject'] = '[' . $tmpData['doc_number'] . '] ' . $docName . ' - '. $this->translator->translate('doklad_byl_uložen');
                $link = $this->link('//showBsc!', ['id' => $tmpData->id, 'copy' => false]);
                $tmpEmlText = $this->EmailingTextManager->getEmailingText($use, '', $tmpData, NULL);

                $template = $this->createTemplate()->setFile(__DIR__ . '/../templates/Emailing/email.latte');
                $template->body = $tmpEmlText['body'];
                $dataEml['body'] = $template;
                //$data['body'] = html_entity_decode($data['body']);


                $this->emailService->sendMail2($dataEml);
            }
        }


    }

}
