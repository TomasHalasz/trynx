<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Message presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminArchivePresenter extends SecuredPresenter
{

	public $id;
	private $archives;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
	* @inject
	* @var \App\Model\UserManager
	*/
	public $UserManager;

    /**
     * @inject
     * @var \App\Model\ArchiveManager
     */
    public $ArchiveManager;

    public function actionDefault()
	{
		$this->archives = $this->ArraysManager->getArchives();
				
	}
	
	public function renderDefault()
	{
	    $archives = array();
	    foreach($this->archives as $key => $one){
	        $archives[$key]['name'] = $one;
	        $archives[$key]['schema_name'] = $this->ArchiveManager->getSchemaName($one);
            $archives[$key]['tables'] = $this->ArchiveManager->getTablesCount($one);
            $archives[$key]['size'] = $this->ArchiveManager->getTablesSize($one);
        }
		$this->template->archives = $archives;

        $dir = APP_DIR.'/../data';
        if (is_dir($dir)) {
            $objects = scandir($dir);
            //dump($objects);
            $myFiles = array();
            foreach ($objects as $one) {
                $fName = APP_DIR . '/../data/' . $one;
                if (file_exists($fName) && $one != '.' && $one != '..' && !is_dir($fName)) {
                    $myFiles[$one]['datetime'] = filemtime($fName);
                    $myFiles[$one]['size'] = round(filesize($fName) / 1024 / 1024, 2);
                }
            }
            arsort($myFiles);
        }
	    $this->template->dumps = $myFiles;

	}	
	

	public function renderEditMessage($id)
	{
		$this->id = $id;

		if ($this->id > 0)
	    {
            $this->template->editItem =  $this->MessagesMainManager->find($this->id);
            $def_data =  $this->MessagesMainManager->find($this->id);

		}else{
			$this->template->editItem = NULL;
			$def_data = array();
			$def_data['created'] = new Nette\Utils\DateTime();

		}
		$this['editMessage']->setDefaults($def_data);
		$this['editMessage']['created']->setDefaultValue($def_data['created']->format('d.m.Y H:i'));
	}		

	protected function createComponentEditMessage($name)
	{	
		$form = new Form($this, $name);
		$form->addTextArea('message', 'Zpráva:', 50, 10)
			->setHtmlAttribute('placeholder','Zpráva')
			->setHtmlAttribute('class', 'form-control');
		$form->addText('created', 'Datum zprávy:')
			->setHtmlAttribute('placeholder','Datum zprávy')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('class', 'form-control datetimepicker');

		$form->addHidden('id');
		$form->addSubmit('create', 'Uložit')->setHtmlAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setHtmlAttribute('class','btn btn-default');
		$form->onSuccess[] = array($this,'editMessageSubmitted');
        return $form;
	}
	
	public function editMessageSubmitted(Form $form)
	{
            if ($form['create']->isSubmittedBy())
            {
				$data=$form->values;
				$data['created'] = date('Y-m-d H:i',strtotime($data['created']));
				if ($form->values['id']==NULL)
				{
					unset($data['id']);				
					$tmpRow = $this->MessagesMainManager->insert($data);
					$row = $tmpRow['id'];

				}else{				
					//$data=$form->values;
					$this->MessagesMainManager->update($data);
					$row = $form->values['id'];
				}
		    	$this->flashMessage('Zpráva uložena', 'success');
				$this->redirect('AdminMessages:EditMessage', $row);
            }
			$this->redirect('AdminMessages:');
	}	
	
	
	public function handleCreateStructures($db)
	{
	    try{
			$this->ArchiveManager->createStructure($db, $this->ArraysManager->getArchive('current'));
	    }
	    catch (Exception $e) {
            $errorMess = 'Není možné vytvořit struktury.';
            $this->flashMessage($errorMess,'danger');
            $errorMess = $e->getMessage();
            $this->flashMessage($errorMess,'danger');

	    }
	    $this->redirect('AdminArchive:');
	}

    public function handleDropStructures($db)
    {
        try{
            $this->ArchiveManager->dropStructure($db);
        }
        catch (Exception $e) {
            $errorMess = 'Není možné odstranit datové struktury.';
            $this->flashMessage($errorMess,'danger');
            $errorMess = $e->getMessage();
            $this->flashMessage($errorMess,'danger');
        }
        $this->redirect('AdminArchive:');
    }

    public function handleDownload($db){

        $bkpName = $this->ArchiveManager->dump($db, TRUE);
        $dataFolder = APP_DIR . '/../data';
        //$dataFolder = $this->CompaniesManager->getDataFolder($cl_company_id);
        //$subFolder = $this->ArraysManager->getSubFolder($file);
        //$fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
        $fileSend = $dataFolder . '/' . $bkpName;
        if (file_exists($fileSend)) {
         $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend,$bkpName, 'application/gzip'));
        } else {

            $this->flashMessage('Dump nebyl proveden','warning');
            $this->redirect('AdminArchive:');
        }
    }


    public function handleEraseFile($file)
    {
        $dir = APP_DIR.'/../data';
        $file = $dir.'/'.$file;
        if (is_file($file))
        {
            if (unlink($file))
                $this->flashMessage ('Soubor byl vymazán','success');
            else
                $this->flashMessage ('Soubor nebyl vymazán','warning');
        }
        $this->redirect('this');
    }

    public function actionGetFile($file)
    {
        $dir = APP_DIR.'/../data';
        $fileSend = $dir.'/'.$file;
        if (is_file($fileSend))
        {
            if (file_exists($fileSend)) {
                $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend, $file, 'application/gzip'));
            }
        }else{
            $this->redirect('Default');
        }

    }



}
