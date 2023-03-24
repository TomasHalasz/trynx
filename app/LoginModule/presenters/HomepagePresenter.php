<?php

namespace App\LoginModule\Presenters;

use Nette\Application\UI\Form;
use Nette\Utils\DateTime;

class HomepagePresenter extends \App\Presenters\BasePresenter {

    /** @persistent */
    public $backlink = '';         
    
    public $parameters;
	
    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;        	

    	
    /**
    * @inject
    * @var \App\Model\RegCompaniesManager
    */
    public $RegCompaniesManager;
    
    public function __construct(\Nette\DI\Container $container) {
	//bdump(  Debugger::timer());
            $this->parameters = $container->getParameters();
    }

    protected function startup(){
        parent::startup();
        if ($this->user->isLoggedIn() && $this->getMntMode() == 'SET_OFF') {
            $strRedir = $this->ArraysManager->getAfterLoginPresenter($this->user->getIdentity()->after_login);
            //dump($strRedir);
            //die;
            if ($strRedir != '')
                $this->redirect($strRedir);
            else
                $this->redirect(':Application:Homepage:');
        }
    }

    
    
    /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm() {
        $form = new Form;
        $form->setTranslator($this->translator);
        $form->addText('email')
		->setHtmlAttribute('class','form-control')		
		->setHtmlAttribute('placeholder','email')->setRequired('emailReq');

//                ->setRequired('emailReq')
//                ->addRule(Form::EMAIL, 'emailRule')

        $form->addPassword('password')
		->setHtmlAttribute('class','form-control')
		->setHtmlAttribute('placeholder','password')
                ->setRequired('passwordReq');

        $form->addCheckbox('remember', 'storeLogin');

        $form->addSubmit('submit', 'loginButton')
            ->setHtmlAttribute('data-sitekey',$this->parameters['sitekeyReCaptcha'])
            ->setHtmlAttribute('data-callback', 'onSubmit')
            ->setHtmlAttribute('data-action','submit');
        if ($this->parameters['sitekeyReCaptcha'] != ''){
            $form['submit']->setHtmlAttribute('class','form-control btn-primary g-recaptcha');
        }else{
            $form['submit']->setHtmlAttribute('class','form-control btn-primary');
        }



        $form->onSuccess[] = array($this, "signInFormSubmitted");


		
        return $form;
    }

    /**
     * Sign in form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function signInFormSubmitted($form) {
		$values = $form->getValues();
		//dump($values);
		//die;
        try {
            $this->getUser()->login($values->email, $values->password);
            if ($values->remember) {
                $this->getUser()->setExpiration('7 days');
            }else{
                $this->getUser()->setExpiration('15 minutes');
            }

            //28.08.2017 - check needed lists if there are records which are necessery
            $this->RegCompaniesManager->checkNeededData($this->user->getIdentity()->cl_company_id);

            if ($this->user->isInRole('b2b')) {
                $this->user->logout(true);
                $this->redirect(':B2B:Homepage:');
            }else{
                    if (!empty($this->backlink)) {
                        $this->restoreRequest($this->backlink);
                        //die;
                        $this->redirect(':Application:Homepage:');
                    } else {
                        //$this->redirect(':Application:Homepage:default');
                        //11.04.2017 - redirect according to settings of user
                        //$this->redirect(':Application:Homepage:default');
                        $strRedir = $this->ArraysManager->getAfterLoginPresenter($this->user->getIdentity()->after_login);
                        $this->redirect($strRedir);
                    }
            }
        } catch (\Nette\Security\AuthenticationException $e) {
            
            $form->addError($e->getMessage());
        }
    }

    public function actionDefault(){
        //dump($this->parameters['debugMode'] );
        //die;
        //if ($this->parameters['maintenance_mode'] == 'SET_ON' && !$this->parameters['debugMode']) {
        if ($this->getMntMode() == 'SET_ON') {
            $this->redirect(':Login:Homepage:maintenance');
        }
    }
    
    public function renderDefault($email = '') {

	$this->template->welcomeMessage = $this->translator->translate('Vítejte!');
	$this['signInForm']->setValues(array('email' => $email));
	
	
    }
    
    public function renderMaintenance()
    {
        if ($tmpVersion = $this->versionManager->findAllTotal()->where('in_progress = 1')->limit(1)->fetch()){
            $reason = $tmpVersion['reason'];
            $back_date = $tmpVersion['start_dtm']->modify('+' . $tmpVersion['duration'] . ' minutes');
            $this->template->reason	    = $reason;
            $this->template->back_date  = $back_date->format('d.m.Y');
            $this->template->back_time  = $back_date->format('H:i');
        }else{
            $this->redirect(':Login:Homepage:');
        }
    }


    public function actionEmailConfirmation($key,$email)
    {

        if ($this->UserManager->confirmEmail($email, $key))
        {
            $this->flashMessage($this->translator->translate('Email_byl_úspěšně_potvrzen'), 'success');
            //$this->getUser()->getIdentity()->email_confirmed = 1;

        }
        else
            $this->flashMessage($this->translator->translate('Bohužel_email_nebyl_potvrzen'), 'danger');

        //$this->redirect(':Login:Homepage:default');
        $this->redirect(':Application:Homepage:default');
    }

}
