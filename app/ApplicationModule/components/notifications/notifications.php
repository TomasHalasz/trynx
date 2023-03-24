<?php

namespace App\ApplicationModule\Presenters;
use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class NotificationsControl extends Control
{

    private $showData,$displayName,$templateFile, $lang, $notificationId;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    /** @var \App\Model\NotificationsManager*/
    private $notificationsManager;

    public function __construct(Nette\Localization\Translator $translator, $showData, $displayName, $templateFile, \App\Model\NotificationsManager $notificationsManager, Nette\Security\User $user)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData             = $showData;
	    $this->displayName          = $displayName;
	    $this->templateFile         = $templateFile;
        $this->translator           = $translator;
        $this->notificationsManager = $notificationsManager;
        $tmpIdentity = $user->getIdentity();
        $this->lang = $tmpIdentity->lang;
	
    }        
    
    public function render()
    {
        $this->template->setTranslator($this->translator);
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
        $this->template->lang = $this->lang;
        $this->template->dataN = $this->showData;
        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->presenter->settings;
        $this->template->notifData = NULL;
        if (!is_null($this->notificationId)){
            $ntfData = $this->notificationsManager->find($this->notificationId);
            $this->template->notifData = $ntfData;
        }

        $this->template->render();
    }


    public function handleShowNotification($notificationId){
        $this->notificationId = $notificationId;
        $this->presenter->showModal('notification_window');
        //$this->redrawControl('bscAreaEdit');
        $this->redrawControl('notificationWindow');
    }







}