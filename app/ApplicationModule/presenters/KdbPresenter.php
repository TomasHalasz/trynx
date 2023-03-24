<?php
namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class KdbPresenter extends \App\Presenters\BaseAppPresenter {

	public $myReadOnly,$txtSearch = NULL;

    /** @persistent */
    public $id;       
	
	
    const
	    DEFAULT_STATE = 'Czech Republic';
	    

    /**
    * @inject
    * @var \App\Model\FilesManager
    */
    public $FilesManager;    	

    /**
    * @inject
    * @var \App\Model\KdbManager
    */
    public $KdbManager;         
    
    /**
    * @inject
    * @var \App\Model\KdbManager
    */
    public $DataManager;        
	
    /**
    * @inject
    * @var \App\Model\NumberSeriesManager
    */
    public $NumberSeriesManager;         	

    /**
    * @inject
    * @var \App\Model\KdbCategoryManager
    */
    public $KdbCategoryManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Kdb']);
    }

    protected function createComponentFiles()
     {
	    if ($this->getUser()->isLoggedIn()){
		$user_id = $this->user->getId();
		$cl_company_id = $this->settings->id;
	    }
        // $translator = clone $this->translator->setPrefix([]);
		return new Controls\FilesControl($this->translator,$this->FilesManager,$this->UserManager,$this->id,'cl_kdb_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
     }       	
	
	
    public function actionDefault()
    {

    }
    
    public function renderDefault() {
				
		//dump($this->txtSearch);
		$this->template->modal = FALSE;
		$this->template->kdbCategory = $this->KdbCategoryManager->findAll()->where('cl_kdb_category_id IS NULL')->order('name');		
		$this->template->txtSearch = $this->txtSearch;
		if ($this->txtSearch != NULL)
		{
			$this->template->kdbResults = $this->KdbManager->findAll()->where('kdb_number LIKE ? OR title LIKE ? OR description_txt LIKE ?', '%'.$this->txtSearch.'%', '%'.$this->txtSearch.'%', '%'.$this->txtSearch.'%');
		}else
		{
			$this->template->kdbResults = NULL;
		}
		$userData = $this->UserManager->getUserById($this->user->getId());
		$this->template->expanded = json_decode($userData['kdb_expand'], TRUE);
    }
	
    public function renderEdit($id = NULL) {
				
		$this->id = $id;
		$this->template->modal = FALSE;
		$this->template->data = $this->KdbManager->find($id);
		$this['edit']->setValues($this->template->data);
		if (!$this->isAllowed($this->name,'edit'))
		{		
			foreach ($this['edit']->getControls() as $control) {
				if ($control->name != 'back')
				{
					$control->controlPrototype->readonly = 'readonly';
					if ($control->controlPrototype->attrs['type'] == 'submit' || $control->controlPrototype->attrs['type'] == NULL || $control->controlPrototype->attrs['type'] == 'checkbox' )
						$control->setDisabled(TRUE);

					if (isset($control->setValidationScope))
						$control->setValidationScope([]);
				}
			}	    
			
			
			$this->myReadOnly = TRUE;
			$this->template->myReadOnly = TRUE;		
		}else
		{
			$this->myReadOnly = FALSE;			
			$this->template->myReadOnly = FALSE;
		}
				

    }	
	
	
	
	protected function createComponentEdit($name)
    {	
		$form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
		$form->addText('kdb_number', $this->translator->translate('Číslo_záznamu:'))->
				setHtmlAttribute('readonly','readonly');
		$form->addText('title', $this->translator->translate('Problém:'));
		$form->addTextArea('description', $this->translator->translate('Řešení:'), 20,10);
		$form->addHidden('description_txt');
		$arrCategory = array();
		$tmpCategory = $this->KdbCategoryManager->findAll()->where('cl_kdb_category_id IS NULL');
		foreach($tmpCategory as $key=>$one)
		{
			$arr2 = array();
			foreach($one->related('cl_kdb_category') as $key2=>$one2)
			{
				$arr2[$key2] = $one2->name;
			}
			//if (count($arr2) > 0)
			//{
				$arrCategory[$one->name] = $arr2;
			//}
		}
		$form->addSelect('cl_kdb_category_id','Kategorie:', $arrCategory)
				->setRequired($this->translator->translate('Kategorie_musí_být_vybrána'))
				->setHtmlAttribute('placeholder',$this->translator->translate('Vyberte_kategorii'));

		$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		
		$form->onSuccess[] = array($this, 'editSubmitted');
		return $form;
    }

    public function stepBack()
    {	    
		$this->redirect('Kdb:default');
    }		

    public function editSubmitted(Form $form)
    {
		$data=$form->values;	
		//dump($data);
		//die;
		if ($form['send']->isSubmittedBy())
		{    
			$this->KdbManager->update($data);
		}
		$this->redirect('Kdb:default');
	}	
	

	protected function createComponentSearch($name)
    {	
		$form = new Form($this, $name);
	    //$form->setMethod('POST');
		$form->addText('searchTxt', '',40)
				->setHtmlAttribute('placeholder',$this->translator->translate('Hledaný_text'));
		
		$form->addSubmit('send', $this->translator->translate('Hledat'))->setHtmlAttribute('class','btn btn-sm btn-primary');
		
	    $form->addSubmit('back', $this->translator->translate('Zrušit'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'searchBack');	    	    
		
		$form->onSuccess[] = array($this, 'searchSubmitted');
		return $form;
    }

    public function searchBack()
    {	    
		$this->txtSearch = "";
		$this->redrawControl('kdbList');
    }		

    public function searchSubmitted(Form $form)
    {
		$data=$form->values;	
		if ($form['send']->isSubmittedBy())
		{    
			$this->txtSearch = $data['searchTxt'];
		}
		//$this->redirect(this');
		$this->redrawControl('kdbList');		
	}	
		
	

	public function handleKdbSearch($txt)	
	{

	}
    
	public function handleKdbNew()	
	{
		$data = array();
		
		$nSeries = $this->NumberSeriesManager->getNewNumber('kdb');
		//dump($nSeries);

		$data['cl_number_series_id'] = $nSeries['id'];
		$data['kdb_number'] = $nSeries['number'];
		
		$row = $this->KdbManager->insert($data);
		$this->id = $row->id;
		$this->redirect('Kdb:edit',$this->id);
	}	
	
	public function handleEdit($id)	
	{
		$this->id = $id;
		$this->redirect('Kdb:edit',$id);
	}		
	
	public function handleDelete($id)
	{
		try{
			$this->KdbManager->delete($id);

		}catch (Exception $e) {
			if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451)
				$errorMess = $this->translator->translate('Záznam_nebylo_možné_vymazat,_protože_k_němu_existují_podřízené_záznamy.');
			else
				$errorMess = $e->getMessage(); 
			$this->flashMessage($errorMess,'danger');
		}	
		$this->redrawControl('kdbList');	
	}
	
	public function handleShowDescr($id)
	{
		if ($tmpData = $this->KdbManager->find($id))
		{
			/*$payload = array('description' => substr($tmpData->description,0,200)
						);*/
                        $payload = array('description' => substr($tmpData->description,0)
						);                    
		}else{
			$payload = array();
		}
		$response = new \Nette\Application\Responses\JsonResponse($payload);
		$this->sendResponse($response);		
		
	}
	

	public function handleKdbExpand($id)
	{
		$userData = $this->UserManager->getUserById($this->user->getId());
		$tmpData = json_decode($userData['kdb_expand'], TRUE);		
		if (isset($tmpData[$id])){
			unset($tmpData[$id]);
		}else{
			$tmpData[$id] = TRUE;
		}
		
		$arrUserData = array('id' => $this->user->getId(), 'kdb_expand' => json_encode($tmpData) );
		$this->UserManager->updateUser($arrUserData);
		$this->redrawControl('kdbList');
	}

	public function renderPublicShow($id, $access_key)
    {
        $this->template->data = $this->DataManager->findAllTotal()->where('cl_kdb.id = ? AND cl_kdb_category.public = 1 AND cl_kdb_category.access_key = ?', $id, $access_key)->fetch();
    }
}
