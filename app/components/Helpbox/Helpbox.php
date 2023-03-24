<?php

namespace App\Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Netpromotion\Profiler\Profiler;
use Nette,
	App\Model;

class HelpboxControl extends Control
{

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    public $showHelp = false;

    private $url;

    public $unMoHandler = array(); // was NULL universalModalHandler will content id_modal = ID of modal window, status = TRUE/FALSE for visible/hidden

    public function __construct( Nette\Localization\Translator $translator, $url)
    {
        $this->url              = $url;
        $this->translator       = $translator;
        //parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/Helpbox.latte');
        $this->template->showHelp = $this->showHelp;
        $this->template->unMoHandler = $this->unMoHandler;
        $this->template->url = $this->url;

        $this->template->render();
    }
    
    public function handleShowHelpBox()
    {
        $this->showHelp = true;
        $this->showModal('myHelpModal');
        $this->redrawControl('helpCnt');
    }

    public function showModal($id_modal)
    {
        $this->unMoHandler['status'] = TRUE;
        $this->unMoHandler['id_modal'] = $id_modal;
        $this->redrawControl('unMoHandler');
    }

    public function hideModal($id_modal)
    {
        $this->unMoHandler['status'] = FALSE;
        $this->unMoHandler['id_modal'] = $id_modal;
        $this->redrawControl('unMoHandler');
    }

}