<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class ChangeStoragePlaceControl extends Control
{

    public $item_id, $storage_id = NULL;

    /** @var \App\Model\StoragePlacesManager*/
    private $StoragePlacesManager;

    /** @var \App\Model\StoreMoveManager*/
    private $StoreMoveManager;

    public $onChange = [];

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;
    
    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct($storeMoveManager, $storagePlacesManager, $itemId, Nette\Localization\Translator $translator )
    {
       //// parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->StoreMoveManager = $storeMoveManager;
        $this->StoragePlacesManager = $storagePlacesManager;
        $this->item_id = $itemId;
        $this->translator = $translator;
    }

    public function render()
    {
        //$this->item_id = $itemId;
        $this->template->setFile(__DIR__ . '/ChangeStoragePlace.latte');
		$tmpPlaces = array();
		//bdump($this->item_id);
		if (!is_null($this->item_id)) {
		
			$tmpData = $this->StoreMoveManager->findAll()->where('id = ?', $this->item_id)->fetch();
			//bdump($tmpData);

			if ($tmpData) {
				$this->storage_id = $tmpData->cl_storage_id;
				if (!empty($tmpData->cl_storage_places))
				{
					$tmpPlaces = json_decode($tmpData->cl_storage_places, true);
				}
			}

			if (is_null($this->storage_id)) {
				throw new \Exception($this->translator->translate('Není_definován_cl_storage_id'));
			}
			$arrStoragePlaces = $this->StoragePlacesManager->findAll()->
											where('cl_storage_id = ?', $this->storage_id)->
											select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');
			//$arrOne = array($control->value => $control->items[$defData1[$control->name]]);
			$this['changePlaceForm']['cl_storage_places_id']->items = $arrStoragePlaces;
			$this['changePlaceForm']->setValues($tmpData);
		}
		$this->template->item_id = $this->item_id;
		$this->template->places = $tmpPlaces;
        $this->template->render();
    }

    public function setItemId($itemId)
    {
        $this->item_id = $itemId;
  
        $this->redrawControl();
    }

    protected function createComponentChangePlaceForm($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id',NULL);
        $arrStoragePlaces = $this->StoragePlacesManager->findAll()->
                                select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->order('item_order')->fetchPairs('id', 'rsp');

        $form->addSelect('cl_storage_places_id', $this->translator->translate("Umístění"), $arrStoragePlaces)
            ->setHtmlAttribute('data-placeholder','Zvolte umístění')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_umístění'));

        $form->addSubmit('save', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackChangePlaceForm');
        $form->onSuccess[] = array($this, 'SubmitChangePlaceForm');
        return $form;
    }

    public function stepBackChangePlaceForm()
    {
        $this->presenter->hideModal('changePlace');
    }

    public function SubmitChangePlaceForm(Form $form)
    {
        $data = $form->values;
       // bdump($data);
        if ($form['save']->isSubmittedBy() ) {
            if ($tmpData = $this->StoreMoveManager->find($data['id'])) {
            	if (!empty($tmpData->cl_storage_places))
				{
					$tmpPlaces = json_decode($tmpData->cl_storage_places, true);
				}else{
            		$tmpPlaces = array();
				}
            	$tmpPlaceName = $this->StoragePlacesManager->findAll()->select('id, CONCAT(rack,"/",shelf,"/", place) AS rsp')->where('id = ?',$data['cl_storage_places_id'])->fetch();
            	$tmpPlaces[$data['cl_storage_places_id']] =  $tmpPlaceName->rsp;
                $this->StoreMoveManager->update(array('id' => $data['id'], 'cl_storage_places' => json_encode($tmpPlaces)));
                $this->flashMessage($this->translator->translate('Umístění_bylo_změněno'), 'success');
                $this->onChange($data['id']);
            }
        }
        $this->presenter->hideModal('changePlace');
    }

	public function handleDeletePlace($place_id, $item_id){
		$tmpData = $this->StoreMoveManager->findAll()->where('id = ?', $item_id)->fetch();
		if (!empty($tmpData->cl_storage_places))
		{
			$tmpPlaces = json_decode($tmpData->cl_storage_places, true);
			unset($tmpPlaces[$place_id]);
			$this->StoreMoveManager->update(array('id' =>  $item_id, 'cl_storage_places' => json_encode($tmpPlaces)));
		}else{
			$tmpPlaces = array();
		}
		$this->item_id = $item_id;
		$this->redrawControl('placestable');
	}

        

}

