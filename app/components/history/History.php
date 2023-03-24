<?php

namespace Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Netpromotion\Profiler\Profiler;
use Nette,
	App\Model;

class HistoryControl extends Control
{
    private $messages, $parent_id, $dataColumns = array(), $table_name = "";

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    /**
     * @var \App\Model\HistoryManager*/
    private $HistoryManager;

    /**
     * @var \App\Model\DataManager*/
    private $dataManager;

    /** @var \DatabaseAccessor */
    private $accessor;


    public function __construct( Model\HistoryManager $historyManager, $parent_id, $dataColumns, $dataManager,  \DatabaseAccessor $accessor)
    {
        //arent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->HistoryManager   = $historyManager;
        $this->parent_id        = $parent_id;
        $this->dataColumns      = $dataColumns;
        $this->dataManager      = $dataManager;
        $this->accessor         = $accessor;
    }

    public function setId($id)
    {
        $this->parent_id = $id;
    }

    public function setDataColumns($dataColumns)
    {
        $this->dataColumns = $dataColumns;
    }

    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
    }


    protected function startup()
    {
        parent::startup();
    }
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/History.latte');
        $this->template->data = $this->HistoryManager->findAll()->where('parent_id = ? AND table_name = ?', $this->parent_id, $this->table_name)->order('created DESC');
        $this->template->dataColumns = $this->dataColumns;
        $this->template->render();
    }




}