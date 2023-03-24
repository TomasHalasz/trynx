<?php

namespace Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
    App\Model;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class PreviewContent extends Control
{

    /** @persistent */
    public $id;

    /** @var \App\Model\Base */
    private $dataManager;

    /** @var \App\Model\Base */
    private $items1Manager;

    /** @var \App\Model\Base */
    private $items2Manager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    private $previewLatteFile;

    /**
     *
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct($latteFile, $dataManager, $items1Manager = NULL, $items2Manager = NULL)
    {
        // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        /*$this->dataManager = $dataManager;
        $this->id = $parent_id;
        $this->PairedDocsManager = $pairedDocsManager;
        $this->translator = $translator;*/
        $this->previewLatteFile = $latteFile;
        $this->dataManager = $dataManager;
        $this->items1Manager = $items1Manager;
        $this->items2Manager = $items2Manager;

    }

    public function action()
    {

    }

    public function render($id)
    {
        $this->id = $id;
        $this->template->setFile(__DIR__ . '/PreviewContent.latte');
        $this->template->previewLatteFile = $this->previewLatteFile;
        $this->template->data = $this->dataManager->find($this->id);
        if (!is_null($this->items1Manager)) {
            $this->template->items1 = $this->items1Manager->findAll()->where($this->dataManager->tableName . '_id = ?', $id)->order('item_order, id');
        }else{
            $this->template->items1 = FALSE;
        }
        if (!is_null($this->items2Manager)) {
            $this->template->items2 = $this->items2Manager->findAll()->where($this->dataManager->tableName . '_id = ?', $id)->order('item_order, id');
        }else{
            $this->template->items2 = FALSE;
        }
        $this->template->settings = $this->template->data->cl_company;
        $this->template->render();
    }

    public function handleEdit($id){
        $this->presenter->redirect('edit', [$id]);
    }

}

