<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette,
	App\Model;
use Tracy\Debugger;

class FileListControl extends Control
{

    /** @var \App\Model\Base*/
    private $dataManager;

    /** @var \App\Model\Base*/
    private $FilesManager;

    /** @var \App\Model\Base*/
    private $CompaniesManager;

    /** @var \App\Model\Base*/
    private $ArraysManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    public $data, $data_import, $page = 1, $parentName, $docName, $cl_company_id, $parentApp;
    
    /**
     * 
     * @param type $cl_pricelist_id - id of pricelist item
     */
    public function __construct(Nette\Localization\Translator $translator, \App\Model\Base $dataManager, \App\Model\Base $filesManager, \App\Model\Base $companiesManager, $arraysManager,
                                        $data, $data_import, $parentName, $docName, $cl_company_id, $parentApp)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
	    $this->dataManager          = $dataManager;
        $this->FilesManager         = $filesManager;
	    $this->data                 = $data;
        $this->data_import          = $data_import;
        $this->parentName           = $parentName;
        $this->docName              = $docName;
        $this->CompaniesManager     = $companiesManager;
        $this->ArraysManager        = $arraysManager;
        $this->cl_company_id        = $cl_company_id;
        $this->parentApp            = $parentApp;
        $this->translator           = $translator;


    }        

    public function action()
    {
	
    }

    public function render( $page = 1)
    {
           $this->template->setFile(__DIR__ . '/FileList.latte');

           //$mainTableName			        = $this->dataManager->getTableName() . '_id';
           //$this->template->mainTableName	= $mainTableName;
            $data = $this->data;
            //paginator start
            $paginator = new \Nette\Utils\Paginator;
            $ItemsOnPage = 20;
            $paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
            $totalItems = $data->count();
            $paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
            //$paginator->setPage($page); // číslo aktuální stránky, číslováno od 1
            $pages = ceil($totalItems/$ItemsOnPage);
            $paginator->setPage($this->page);

            $this->template->paginator = $paginator;
            $steps=array();
            for ($i = 1; $i <= $pages; $i++) {
                $steps[]=$i;
            }
            $this->template->steps = $steps;
            $finalData = $data->limit($paginator->getLength(), $paginator->getOffset());
            $this->template->dataSource = $finalData;

            $this->template->data           = $this->data;
            $this->template->data_import    = $this->data_import;
            $this->template->parentTableName = str_replace('_id','',$this->parentName);

            $this->template->docName        = $this->docName;
            $this->template->parentApp      = $this->parentApp;
            $this->template->userCanErase   = $this->presenter->userCanErase;

            //$this->template->data_mission    = $this->getFileList('cl_commission_id', $this->CommissionManager, 'cm_number');


            $this->template->render();
    }

    public function handleGetFile($id)
    {
        if ($file = $this->FilesManager->findAllTotal()->where('cl_company_id =? AND id = ?', $this->cl_company_id, $id)->fetch())
        {
            if ($file->new_place == 0) {
                $fileSend = __DIR__ . "/../../../../data/files/" . $file->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                $subFolder = $this->ArraysManager->getSubFolder($file);
                $fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
            }

            if (file_exists($fileSend))
                $this->presenter->sendResponse(new Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
            else{
                $snippetName = $this->ArraysManager->getSubFolder($fileSend);
                $this->flashMessage($this->translator->translate('Soubor_neexistuje.'), 'warning');
                $this->redrawControl( $snippetName);
                $this->redrawControl('flash');
            }


        }
    }


    public function handleFileDelete($id){
        if ($file = $this->FilesManager->find($id))
        {
            if ($file->new_place == 0) {
                $fileDel = __DIR__ . "/../../../../data/files/" . $file->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                $subFolder = $this->ArraysManager->getSubFolder($file);
                $fileDel =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
            }

            if (file_exists($fileDel))
                unlink ($fileDel);

            $snippetName = $this->ArraysManager->getSubFolder($file);
            $file->delete();
            $this->flashMessage($this->translator->translate('Soubor_byl_vymazán.'), 'success');
            $this->redrawControl( $snippetName);
            $this->redrawControl('flash');
        }
    }


    public function handleImport(){
            foreach($this->data_import as $key => $one)
            {
                if (!is_null($one['id'])){
                    $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                    $subFolder  = $this->ArraysManager->getSubFolder(array(), $this->parentName);

                    $name_parts = pathinfo($one['name']);

                    //echo $path_parts['dirname'], "\n";
                    //echo $path_parts['basename'], "\n";
                    //echo $path_parts['extension'], "\n";
                    //echo $path_parts['filename'], "\n"; // since PHP 5.2.0
                    //bdump($name_parts);
                    $safeFileName   = trim(Nette\Utils\Strings::webalize($name_parts['filename'], null , false), '.-') . '.' . $name_parts['extension'];
                    //bdump($safeFileName);
                    $fileOld        = $dataFolder . '/' . $subFolder . '/import/' . $one['name'];
                    $fileNew        = NULL;
                    $i              = 0;
                    $arrFile        = str_getcsv( $safeFileName, '.');
                    while(file_exists($fileNew) || is_null($fileNew))
                    {
                        if (!is_null($fileNew)) {
                            $safeFileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];
                        }
                        $fileNew   =  $dataFolder . '/' . $subFolder . '/' .  $safeFileName;
                        $i++;
                    }
                    //$fileNew        = $dataFolder . '/' . $subFolder . '/' . $safeFileName;

                    $mimeType =  mime_content_type($fileOld) ;
                    $this->FilesManager->insert(array( $this->parentName => $one['id'],
                                                        'file_name' => $safeFileName,
                                                        'mime_type' => $mimeType,
                                                        'label_name' => $safeFileName,
                                                        'file_size' => $one['size'],
                                                        'create_by' => 'imported',
                                                        'created' => new \Nette\Utils\DateTime,
                                                        'cl_users_id' =>  $this->presenter->getUser()->id
                                                        ));
                    copy($fileOld, $fileNew);
                    if (file_exists($fileOld)) {
                        unlink($fileOld);
                        unset($this->data_import[$key]);
                    }

                }



            }
        $this->redrawControl();
/*
        'name'     => $file->getFileName(),
                'exte'     => $file->getExtension(),
                'size'     => $file->getSize(),
                'type'     => $file->getType(),
                'date_cre' => $file->getCTime(),
                'date_mod' => $file->getMTime(),
                'id'       => $idPaired,
                'number'   => $pairedNum);*/

    }

    public function handleNewPage($page)
    {
        $this->page = $page;
        $this->redrawControl('files');
    }





	        

        

}

