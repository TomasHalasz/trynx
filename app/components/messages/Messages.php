<?php

namespace App\Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Netpromotion\Profiler\Profiler;
use Nette,
	App\Model;

class MessagesControl extends Control
{


    private $messages;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    public function __construct( Nette\Localization\Translator $translator, $messages)
    {
        //parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->messages         =$messages;
        $this->translator       = $translator;
    }

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['components.mymessages']);
    }
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/Messages.latte');
    	$this->template->messages = $this->messages;
    	$this->template->messCount = $this->messages->count();
		//$this->MessagesManager->findAll()->where('closed = ?',0)->count();	
    	//$this->template->images = $this->presenter->ImagesManager->getAll()->where('gallery_id = ?',$gallery_id)->limit(6);
        $this->template->render();
    }
    
    public function handleCloseMessage($id)
    {
        $this->presenter->MessagesManager->setClose($id, $this->presenter->user->getId());
        $this->redrawControl('snpMessCount');
        $this->redrawControl('snpMessages');
    }
}