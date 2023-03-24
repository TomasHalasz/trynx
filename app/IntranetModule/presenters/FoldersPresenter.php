<?php

namespace App\IntranetModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;

class FoldersPresenter extends \App\Presenters\BaseAppPresenter {

    //public $id;
    /** @persistent */
    public $id;

    public $createDocShow;

    /**
     * @inject
     * @var \App\Model\FoldersManager
     */
    public $DataManager;


    /**
     * @inject
     * @var \App\Model\UsersManager
     */
    public $UsersManager;



    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;


    /**
     * @inject
     * @var \App\Model\FilesAgreementsManager
     */
    public $FilesAgreementsManager;


    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;


    protected function startup()
    {
		parent::startup();

    }

    protected function createComponentFiles()
    {
        bdump($this->id,'created files');
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_folder_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager, $this->FilesAgreementsManager, $this->UsersManager);
    }


    public function renderDefault($id,$new = FALSE, $parent_id = NULL)
    {
        $this->id = $id;
        $this->template->folders = $this->DataManager->findAll()->where('in_folders_id IS NULL')->order('name');
        $this->template->new = $new;

        if (is_null($this->id)) {
            //creating new record
            $this['edit']->setDefaults(array('id' => NULL, 'name' => '', 'description' =>'', 'in_folders_id' => $parent_id));
            $this->template->data = FALSE;
//            if ($tmpId = $this->DataManager->findAll()->order('name')->limit(1)->fetch()){
//                $this->id = $tmpId->id;
//            }

        }else{
            $this->template->data = $this->DataManager->find($this->id);
            if (!is_null($this->id)) {
                $this['edit']->setDefaults($this->template->data);
            }
        }



    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id',NULL);
        $form->addHidden('in_folders_id',NULL);
        $form->addText('name', 'Název složky:', 20, 20)
            ->setHtmlAttribute('placeholder','Název složky');

        $form->addTextArea('description', 'Poznámka:', 30, 8)
            ->setHtmlAttribute('placeholder','Poznámka');

        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class','btn btn-success');
        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
        $form->onSuccess[] = array($this,'SubmitEditSubmitted');
        return $form;
    }

    public function stepBack()
    {
        //$this->redirect('default');
        $this->hideModal('folderEditModal');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
           // $data = $this->removeFormat($data);
            if ($data->in_folders_id == "")
                unset($data->in_folders_id);

            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else {
                $newId = $this->DataManager->insert($data);
                $this->id = $newId->id;
            }

            $this->flashMessage('Změny byly uloženy.', 'success');

        }
        $this->createDocShow = FALSE;
        $this->redrawControl('flash');
        $this->redrawControl('treeFolders');
        //$this->redrawControl('content');
        $this->hideModal('folderEditModal');

        //$this->redirect('default', array('id' => $this->id));
        //$this->redrawControl('treeFolders');
        //$this->redrawControl('cardFolder');
    }

    public function handleEdit($id)
    {
        $this->id = $id;
        $this->redrawControl('treeFolders');
        $this->redrawControl('cardFolder');
    }


    public function handleDelete($id)
    {
        //$this->id = $id;
        $files = $this->FilesManager->findAll()->where('in_folder_id = ?', $id);
        foreach($files as $key => $one)
        {
            if ($one->new_place == 0) {
                $fileDel = __DIR__ . "/../../../data/files/" . $one->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
                $subFolder = $this->ArraysManager->getSubFolder($one);
                $fileDel =  $dataFolder . '/' . $subFolder . '/' . $one->file_name;
            }

            if (file_exists($fileDel))
                unlink ($fileDel);

            $one->delete();
        }
        $this->flashMessage('Soubory byly vymazány.', 'success');
        $this->DataManager->delete($id);
        $this->flashMessage('Složka byla vymazána.', 'success');
        $this->redrawControl('treeFolders');
        $this->redrawControl('cardFolder');
    }

    public function handleEditFolder($id, $new = FALSE, $parent_id = NULL){

        if (is_null($id)) {
            //creating new record
            $this['edit']->setDefaults(array('id' => NULL, 'name' => '', 'description' =>'', 'in_folders_id' => $parent_id));
            $this->template->data = FALSE;
        }else{
            $this->template->data = $this->DataManager->find($this->id);
            if (!is_null($this->id)) {
                $this['edit']->setDefaults($this->template->data);
            }
        }

        $this->createDocShow = TRUE;
        $this->showModal('folderEditModal');
        //$this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

}
