<?php

namespace App\Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class AuthorizeUserControl extends Control
{

    public $parent_id;
    
    private $title, $showModal = FALSE;

    /** @var \App\Model\Base*/
    private $userManager;
	
	/** @var Nette\Http\Session */
	private $session;
	
	/** @var Nette\Http\SessionSection */
	private $sessionSection;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    private $settings;
    /**
     * 
     *
     */
    public function __construct(Nette\Localization\Translator $translator, $userManager, $title, Nette\Http\Session $session)
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
		$this->userManager      = $userManager;
		$this->title            = $title;
		$this->session          = $session;
        $this->translator       = $translator;
    }        

    public function action()
    {
	
    }
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/AuthorizeUser.latte');
        $this->template->title = $this->title;
		//$this->template->data = $this->dataManager->findBy(array('id' => $this->parent_id))->fetch();
		$this->template->showModal = $this->showModal;
        $this->template->render();
    }
    
    public function handleAuthorizeUser()
	{
		$mySection = $this->session->getSection('authorized');
		if (!isset($mySection['authorizedPIN']))
		{
			$mySection['authorizedPIN'] = FALSE;
		}
		$this->showModal = TRUE;
		$this->redrawControl('showRequest');
	}
	
	
	public function handleAuthorizePin($pin)
	{
		$mySection = $this->session->getSection('authorized');
		if ($user = $this->userManager->getAll()->where('store_manager = 1 AND authorize_pin = ? AND authorize_pin != ""', $pin)->fetch())
		{
			$mySection['authorizedPIN'] = TRUE;
			$now = new Nette\Utils\DateTime();
			$mySection['authorizedDateTime'] = $now->modify('+1 minute');
		}else{
			$mySection['authorizedPIN'] = FALSE;
			$now = new Nette\Utils\DateTime();
			$mySection['authorizedDateTime'] = $now;
			$this->presenter->flashMessage($this->translator->translate('Chybný_PIN'), 'danger');
		}
		
		$this->showModal = FALSE;
		$this->redrawControl('hideRequest');
		$this->presenter->redrawControl('itemsContainer');
	}

}

