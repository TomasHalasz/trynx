<?php

namespace App\LoginModule\Presenters;

use Nette\Application\UI\Form;
use Nette\Mail\Message,
    Nette\Utils\Strings,
    Nette\Utils\Html;
use Nette\Mail\SendmailMailer;

class NewRegistrationPresenter extends \App\Presenters\BasePresenter {

    
    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;       

    /**
    * @inject
    * @var \App\Model\RegCompaniesManager
    */
    public $RegCompaniesManager;

    /**
     * @inject
     * @var \App\Model\RegCountriesManager
     */
    public $RegCountriesManager;
    
    /**
     * Registration form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentRegistrationForm() {
        $form = new Form;
        //$form->setTranslator($this->translator);
        $form->addText('email')
		->setHtmlAttribute('class','form-control')		
		->setHtmlAttribute('placeholder','Email')
		->setHtmlAttribute('autocomplete','off')		
		->setHtmlAttribute('data-url-string',$this->link('SearchEmail!'))
                ->setRequired('Zadejte svůj email.')		
                ->addRule($form::EMAIL, 'Prosím zadejte svůj platný email.');

        $form->addPassword('password')
			->setHtmlAttribute('class','form-control')
			->setHtmlAttribute('placeholder','Heslo')
			->setRequired('Prosím zadejte své heslo.')
			->addRule($form::MIN_LENGTH, 'Heslo je příliš krátké. Musí mít alespoň %d znaků.', 5)		
			->addRule($form::PATTERN, 'Heslo je příliš jednoduché. Musí obsahovat číslici.', '.*[0-9].*')
			->addRule($form::PATTERN, 'Heslo je příliš jednoduché. Musí obsahovat malé písmeno.', '.*[a-z].*');
        $form->addPassword('password2')
			->setHtmlAttribute('class','form-control')
			->setHtmlAttribute('placeholder','Heslo znovu')
			->addRule($form::EQUAL, 'Hesla se neshodují.',$form['password'])
			->setRequired('Prosím zadejte znovu své heslo.');	

        $form->addSubmit('submit', 'Registrovat')
            ->setHtmlAttribute('data-sitekey',$this->parameters['sitekeyReCaptcha'])
            ->setHtmlAttribute('data-callback', 'onSubmit')
            ->setHtmlAttribute('data-action','submit')
            ->setHtmlAttribute('class','form-control btn-primary g-recaptcha');

        $form->onSuccess[] = array($this, "registrationFormSubmitted");
        return $form;
    }

    /**
     * Sign in form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function registrationFormSubmitted($form) {
	    $regValues = $form->getValues();
	    $signValues = array('email' => $regValues['email'], 'password' => $regValues['password']);
	    bdump($signValues);
        try {
            //$this->getUser()->login($values->email, $values->password);
            //$regValues['password'] =  \Nette\Utils\Strings::random(8,'A-Za-z0-9');
            unset($regValues['submit']);
            unset($regValues['password2']);
            $regValues['role'] = 'user';
            //create new user and login
            $user = $this->UserManager->addRegistration($regValues);
            //bdump($signValues);
            //$this->getUser()->login($signValues['email'], $signValues['password']);


            //create company for user
            $dataCompany = new \Nette\Utils\ArrayHash;
            $dataCompany['email'] = $regValues->email;
            $dataCompany['platce_dph'] = 1;
            $tmpCountry = $this->RegCountriesManager->findAllTotal()->where('acronym = ?', 'CZ')->fetch();
            $dataCompany['cl_countries_id'] = $tmpCountry->id;
            $dataCompany['cl_users_id'] = $user->id;
            $company = $this->RegCompaniesManager->insert($dataCompany);
            //force update of user identity
            //$this->user->identity->cl_company_id = $company->id;

            //assign company to user as default
            unset($regValues['password']);
            $regValues['id'] = $user->id;
            $regValues['cl_company_id'] = $company->id;
            $regValues['main_company_id'] = $company->id;
            $regValues['eml_confirm_key'] = \Nette\Utils\Random::generate(24,'A-Za-z0-9');
            //uložíme urlKey a datetime expirace
            $expDate = new \Nette\Utils\DateTime;
            $regValues['eml_confirm_exp'] = $expDate->modify('+3 hour');
            $this->UserManager->updateUser($regValues);



            //create default values in some tables
            $this->RegCompaniesManager->createDefaultData($company->id);

            //send confirmation email
            $activateLink = $this->link(':Login:Homepage:EmailConfirmation',$regValues['eml_confirm_key'],$regValues->email );

            $this->emailService->sendRegistrationEmail($regValues->email, $activateLink);

            $this->flashMessage($this->translator->translate('Vítejte_registrace_proběhla_úspěšně'), 'success');
            $this->flashMessage($this->translator->translate('Poslali_jsme_vám_email_s_dalšími_instrukcemi'), 'success');
            //$this->getUser()->logout();
            $this->redirect(':Login:Homepage:default');
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($this->translator->translate($e->getMessage()));
        }
    }

    public function renderDefault() {
        $this->template->anyVariable = 'any value';
    }

    public function handleSearchEmail($email)
    {
		$result = $this->validatorService->isExistUserEmail($email);
		echo(json_encode($result));
		$this->terminate();	
    }
}
