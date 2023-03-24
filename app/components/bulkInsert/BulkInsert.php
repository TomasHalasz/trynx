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

class BulkInsertControl extends Control
{

    public $parent_id;

    public $insertData = [];

    private $settings, $inputValPh, $enableInputVal, $notInsertNew, $focusTo, $anotherCols, $colours;


    /** @var \App\Model\Base*/
    private $dataManager;

    /** @var Nette\Http\Session */
    private $session;

    /** @var Nette\Http\SessionSection */
    private $sessionSection;

    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $PriceListManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;


    public function __construct(  Nette\Localization\Translator $translator, Nette\Http\Session $session,  $dataManager,$parent_id, $PriceListManager, $enableInputVal= FALSE, $inputValPh = "", $notInsertNew = FALSE,
                                $anotherCols = array(), $colours = array())
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->dataManager      = $dataManager;
        $this->parent_id        = $parent_id;
        $this->session          = $session;
        $this->PriceListManager = $PriceListManager;
        $this->inputValPh       = $inputValPh;
        $this->enableInputVal   = $enableInputVal;
        $this->notInsertNew     = $notInsertNew;
        $this->anotherCols      = $anotherCols;
        $this->colours          = $colours;
        $this->translator       = $translator;
    }

    public function action()
    {
	
    }
    
    public function render()
    {
        ////profiler::start();
        $this->template->setFile(__DIR__ . '/BulkInsert.latte');
        //$this->template->data       = $this->dataManager->findBy(array('id' => $this->parent_id))->fetch();
        $this->template->settings   = $this->settings;
        $this->template->cmpName    = $this->name;
        $this->template->myReadOnly = $this->parent->myReadOnly;
        $mySection = $this->session->getSection('bulkInsert-'.$this->dataManager->getTableName());
        if (!isset($mySection['data']))
        {
            $mySection['data'] = array();
        }
        $this->template->data = $mySection['data'];
        $this->template->lastId = $mySection['lastId'];
        $this->template->inputValPh = $this->inputValPh;
        $this->template->enableInputVal = $this->enableInputVal;
        $this->template->focusTo = $this->focusTo;
        $this->template->anotherCols = $this->anotherCols;
        $this->template->colours = $this->colours;
        $this->template->render();
        ////profiler::finish('component '.$this->getName());
    }

    public function handleSrch($q, $focusTo = '')
    {
        if ($q != '') {
            if (strlen($q) < 13 && strlen($q) > 3) {
                $tmpWhere = 'identification LIKE ?';
                $tmpParam = $q;
            } elseif(strlen($q) >=13) {
                $tmpWhere = 'ean_code LIKE ?';
                $tmpParam = '%' . $q . '%';
            } else {
                $tmpWhere = '';
                $tmpParam = '';
            }
            $mySection = $this->session->getSection('bulkInsert-'.$this->dataManager->getTableName());

            if (empty($tmpWhere) )
            {
                //in $q is quantity, replace quantity at last record
                //bdump(ord($q), $q);
                if (floatval($q) > 0 || (floatval($q) == 0 && ord($q) >= 48 && ord($q) <= 57 ))
                {
                    $tmpArr = $mySection['data'];
                    if (array_key_exists($mySection['lastId'], $tmpArr)) {
                        $tmpArr[$mySection['lastId']]['quantity'] = floatval($q);
                        $mySection['data'] = $tmpArr;
                    }
                }

            }else {
                //in $q is EAN or identification
                $pricelist = $this->PriceListManager->findAll()->where('(' . $tmpWhere . ') ', $tmpParam)->fetch();
                if ($pricelist) {
                    if (!isset($mySection['data'])) {
                        $mySection['data'] = array();
                    }

                    $tmpArr = $mySection['data'];

                    //bdump(array_key_exists($pricelist->id, $tmpArr));

                    if (array_key_exists($pricelist->id, $tmpArr)) {
                        $quantity = $tmpArr[$pricelist->id]['quantity'] + 1;
                        $order = $tmpArr[$pricelist->id]['item_order'];
                    } else {
                        $quantity = 1;
                        $order = count($tmpArr) + 1;
                    }
                    if ($this->notInsertNew && (!array_key_exists($pricelist->id, $tmpArr)))
                    {
                        $this->flashMessage($this->translator->translate('Nové_položky_není_možné_vkládat.'), 'warning');
                    }else{
                        if (array_key_exists($pricelist->id, $tmpArr)){
                            //item exists, leave every value as it is and update only quantity
                            $tmpArrOne = $tmpArr[$pricelist->id];
                            $tmpArrOne['quantity'] = $quantity;
                        }else{
                            //item does not exists, prepare new value
                            $tmpArrOne = array('item_order' => $order,
                                'id' => $pricelist->id,
                                'identification' => $pricelist->identification,
                                'item_label' => $pricelist->item_label,
                                'quantity' => $quantity);
                        }
                        $tmpArr[$pricelist->id] = $tmpArrOne;

                        //if ($this->enableInputVal)
                        //{
                            //$tmpArr[$pricelist->id]['input_value'] = 0;
                        //}

                        $mySection['data'] = $tmpArr;
                        $mySection['lastId'] = $pricelist->id;
                    }

                    //bdump($mySection['data'], 'search mysection data');
                }
            }
        }
        if ($this->enableInputVal) {
            $this->focusTo = $focusTo;
        }
        $this->redrawControl('bulkInsertMain');
        $this->presenter->redrawControl('flash');
    }

    public function handleErase()
    {
        $mySection = $this->session->getSection('bulkInsert-'.$this->dataManager->getTableName());
        $mySection['data'] = array();
        $mySection['lastId'] = 0;
        $this->redrawControl('bulkInsertMain');
    }

    public function handleActive($lastId, $focusTo = '')
    {
        $mySection = $this->session->getSection('bulkInsert-'.$this->dataManager->getTableName());
        $mySection['lastId'] = $lastId;
        $this->focusTo = $focusTo;
        $this->redrawControl('bulkInsertMain');
    }

    public function handleInsert()
    {
        $mySection = $this->session->getSection('bulkInsert-'.$this->dataManager->getTableName());
        $this->presenter->insertBulkData($mySection['data'] );
    }

    public function handleValueUpdate($value)
    {
        $mySection = $this->session->getSection('bulkInsert-'.$this->dataManager->getTableName());
        $lastId = $mySection['lastId'];
        $tmpArr = $mySection['data'];
        //bdump($lastId, 'lastID');
        //bdump($tmpArr, 'data');
        if (floatval($value) || (floatval($value) == 0 && ord($value) >= 48 && ord($value) <= 57 )) {
            $value = str_replace(',','.', $value);
            $tmpArr[$lastId]['input_value'] = floatval($value);
        }
        $mySection['data'] = $tmpArr;
        $nextId = $lastId;
        $found = FALSE;
        foreach($tmpArr as $key => $one)
        {
            if ($found){
                $nextId = $one['id'];
                break;
            }
            if ($one['id'] == $lastId) {
                $found =  TRUE;
            }

        }
        $mySection['lastId'] = $nextId;

        $this->redrawControl('bulkInsertMain');
    }

}

