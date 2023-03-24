<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class HomepagePresenter extends \App\Presenters\BaseAppPresenter {

    public $notification_id;

    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;


    /**
     * @inject
     * @var \App\Model\FilesAgreementsManager
     */
    public $FilesAgreementsManager;

    /**
     * @inject
     * @var \App\Model\NotificationsManager
     */
    public $NotificationsManager;

   	
    protected function startup()
    {
		parent::startup();
    }	


    public function renderDefault()
    {
        $dataFiles = $this->FilesAgreementsManager->findAll()->where('cl_users_id = ? AND dtm_agreement IS NULL', $this->user->getId());
        $this->template->files = $dataFiles;
        $this->template->showAgreement = TRUE;

        $notifications = $this->NotificationsManager->findValid();
        $this->template->notifications = $notifications;
        $this->template->lang = $this->user->getIdentity()->lang;
        if (!is_null($this->notification_id)){
            $notifData = $this->NotificationsManager->find($this->notification_id);
            $this->template->notifData = $notifData;
        }else{
            $this->template->notifData = NULL;
        }

    }



    public function handleGetPDF($id)
    {
        $this->getPDF($id);
    }


    public function handleGetFile($id)
    {
        if ($file = $this->FilesManager->findAllTotal()->where('cl_company_id =? AND id = ?', $this->cl_company_id, $id)->fetch())
        {
            if ($file->new_place == 0) {
                $fileSend = __DIR__ . "/../../../data/files/" . $file->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                $subFolder = $this->ArraysManager->getSubFolder($file);
                $fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
            }
            if (file_exists($fileSend)) {

                $this->sendResponse(new Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
            }

        }
    }


    public function handleShowNotification($notification_id){
        $this->notification_id = $notification_id;
        $this->showModal('notification_window');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }






}
