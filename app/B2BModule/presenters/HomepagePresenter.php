<?php

namespace App\B2BModule\Presenters;

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
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;        	
    
    public function __construct(\Nette\DI\Container $container) {
	//bdump(  Debugger::timer());
            $this->parameters = $container->getParameters();
    }

    public function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['B2BModule.Homepage']);
        $this->setLayout(__DIR__ . '/../templates/@layout.latte');
    }

    public function actionDefault()
    {

        //if ($this->parameters['maintenance_mode'] == 'SET_ON' && !$this->parameters['debugMode']) {
        if ($this->getMntMode() == 'SET_ON') {
            $this->redirect(':B2B:Homepage:maintenance');
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
		->setHtmlAttribute('class','form-control btn-primary');

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
        try {
            $this->getUser()->login($values->email, $values->password);
            if ($values->remember) {
                $this->getUser()->setExpiration('+ 7 days');
            }else{
                $this->getUser()->setExpiration('+ 15 minutes');
            }

            //28.08.2017 - check needed lists if there are records which are necessery
            //$this->CompaniesManager->checkNeededData($this->user->getIdentity()->cl_company_id);

            $mySection = $this->session->getSection('company');
            $mySection['cl_company_id'] = $this->user->getIdentity()->cl_company_id;

            if ($this->user->isInRole('b2b')) {
                //b2b user = worker from cl_partners_book_workers is going to B2B
                if (!empty($this->backlink)) {
                    $this->restoreRequest($this->backlink);
                    //die;
                    $this->redirect(':B2B:Mainpage:');
                } else {
                    $this->redirect(':B2B:Mainpage:default');
                }
            }else{
                //common user = from cl_users is going to selection from his cl_partners_book
                $this->redirect(':B2B:PartnersBook:');

            }
        } catch (\Nette\Security\AuthenticationException $e) {
            
            $form->addError($e->getMessage());
        }
    }


    
    public function renderDefault($email = '') {

        $this->template->welcomeMessage = $this->translator->translate('Vítejte');
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
            $this->redirect(':B2B:Homepage:default');
        }

    }

}
