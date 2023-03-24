<?php
namespace App\AdministrationModule\Presenters;

abstract class SecuredPresenter extends BasePresenter
{
    protected function startup()
    {
        parent::startup();

	    if ($this->getUser()->isLoggedIn() ) {
            $userData = $this->getUser()->getIdentity();
            if (!($userData->email == 't.halasz@2hcs.cz' || $userData->email == 'info@faktury.cz')) {
                $this->getUser()->logout();
                $this->redirect('Sign:in');
            }
        }elseif ($this->getUser()->isLoggedIn()) {
            if (!$this->getUser()->isInRole('admin'))
                $this->redirect('Homepage:');
        }else{
            $this->redirect('Sign:in');
        }

    }

    protected $section;
    public $opener = false;

    /**
     * @inject
     * @var \App\Model\UserManager
     */
    public $userManager;


    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->opener = false;
        $this->template->cmzName = CMZ_NAME;
    }





}