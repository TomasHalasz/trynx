<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;
use Nette\Utils\FileSystem;
use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
use DOMDocument;
use Nette\Utils\Arrays;
/**
 * Administrace Versions presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminVersionsPresenter extends SecuredPresenter
{

    private $parameters;

    /** @persistent */
    public $page;

    /** @persistent */
    public $filter;

    /** @persistent */
    public $id;

    /**
    * @inject
    * @var \App\Model\VersionsManager
    */
    public $VersionsManager;
	
    /**
    * @inject
    * @var \App\Model\UsersManager
    */
    public $UsersManager;

    public function __construct(\Nette\DI\Container $container)
    {

        parent::__construct();

        $this->parameters = $container->getParameters();

    }

	public function renderDefault($page = 1)
	{

		//$this->template->article_list_cz = $this->article_list_cz;
		//paginator start
		$paginator = new Nette\Utils\Paginator;
		$ItemsOnPage = 15;
		if (!is_null($this->filter))
		{		
		    $totalItems = $this->VersionsManager->findAll()->where('version LIKE ? OR allowed_ic LIKE ?', '%'.$this->filter.'%','%'.$this->filter.'%')->count();
		    $this['searchVersion']->setDefaults(['search' => $this->filter]);
		}else{
		    $totalItems = $this->VersionsManager->findAll()->count();
		}
		   
		$paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
		$paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
		$paginator->setPage($page); // číslo aktuální stránky, číslováno od 1
		$pages = ceil($totalItems/$ItemsOnPage);
		$this->template->paginator = $paginator;
		$steps = [];
		for ($i = 1; $i <= $pages; $i++) {
		    $steps[]=$i;
		}
		$this->template->steps = $steps;
		
		if (!is_null($this->filter))
		{		
		    $this->template->versions = $this->VersionsManager->findAll()->
			    where('version LIKE ? OR allowed_ic LIKE ?', '%'.$this->filter.'%','%'.$this->filter.'%')->
			    order('version_date DESC, id DESC')->limit($paginator->getLength(), $paginator->getOffset());
		}else{
            $this->template->versions = $this->VersionsManager->findAll()->order('version_date DESC, id DESC')->limit($paginator->getLength(), $paginator->getOffset());
		}

		$this->template->counter_total = $totalItems;
	       //paginator end

        $this->template->cmzName = CMZ_NAME;
        dump(CMZ_NAME);

	}	
	
	public function renderEditVersion($id)
	{
		$this->id = $id;
		if ($this->id > 0)
		{
			$tmpDef_data = $this->VersionsManager->find($this->id);
			$def_data = $tmpDef_data->toArray();
			
		}else{
			$def_data= [];
            $def_data['id'] = 0;
		    $def_data['version_date'] = new \Nette\Utils\DateTime;
            $def_data['version'] = $this->parameters['app_version'];
        }
        $def_data['version_date'] = date('d.m.Y',strtotime($def_data['version_date']));
		
		if (!isset($def_data['cl_users_id']))
		{
			$def_data['cl_users_id'] = $this->user->getId();
		}

		$this['editVersion']->setDefaults($def_data);
		$this->template->id = $def_data['id'] ;
    }	

	protected function createComponentEditVersion($name)
	{	
		$form = new Form($this, $name);

        $form->addHidden('id');
        $form->addText('version', 'Číslo verze', 200, 200)
			->setHtmlAttribute('placeholder','verze')
			->setHtmlAttribute('class', 'form-control')
			->addRule(Form::FILLED, 'Číslo verze musí být vyplněno.');

		$form->addText('version_date', 'Datum verze')
			->setHtmlAttribute('placeholder','datum verze')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('class', 'form-control datepicker');

	    $form->addTextArea('allowed_ic', 'Povolená IČ',10,10)
			->setHtmlAttribute('placeholder','Povolená IČ')
			->setHtmlAttribute('class', 'form-control');

        $form->addTextArea('sql_script', 'SQL script',10,10)
            ->setHtmlAttribute('placeholder','SQL')
            ->setHtmlAttribute('class', 'form-control');


		$form->addSubmit('create', 'Uložit')->setHtmlAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setHtmlAttribute('class','btn btn-default');
        $form->addSubmit('generate', 'Publikovat verzi')->setHtmlAttribute('class','btn btn-danger');
        $form->addSubmit('generateBig', 'Publikovat verzi s Nette')->setHtmlAttribute('class','btn btn-danger');
		$form->onSuccess[] = array($this,'editFormSubmitted');
		return $form;
	}
	
	public function editFormSubmitted(Form $form)
	{
		if ($form['create']->isSubmittedBy() || $form['generate']->isSubmittedBy())
		{
		    $data = $form->values;
		    $data['version_date'] = date('Y-m-d',strtotime($data['version_date']));

		    if ($form->values['id'] == 0)
			{
				$newData = $this->VersionsManager->insert($data);
				$id = $newData['id'];
                $this->flashMessage('Verze vytvořena', 'success');
			}else{
				$this->VersionsManager->update($data);
                $id = $data['id'];
                $this->flashMessage('Verze uložena', 'success');
			}
		    $this->makeSQLFile($data['sql_script'], $data['version']);

            if ($form['create']->isSubmittedBy()) {
                $this->redirect('AdminVersions:editVersion', ['id' => $id]);
            }elseif ($form['generate']->isSubmittedBy()) {
                $this->generateZIP();
            }elseif ($form['generateBig']->isSubmittedBy()) {
                $this->generateZIP(TRUE);
            }


		 }elseif ($form['storno']->isSubmittedBy()) {
            $this->redirect('AdminVersions:');
        }
	}


	public function handleEraseVersion($id)
	{
	    try{
	        //TODO: upgrades/[version_number].zip erase
            $this->VersionsManager->delete($id);
            $this->flashMessage('Verze byla vymazána', 'success');
		}
	    catch (Exception $e) {
		    if ($e->errorinfo[1]=1451)
		    {
			$errorMess = 'Není možné vymazat článek, je aktuálně použitý.'; 
		    } else
			$errorMess = $e->getMessage(); 
		    $this->flashMessage($errorMess,'danger');

	    }	    
	    $this->redirect('AdminVersions:');
		
	}	
	
	
	protected function createComponentSearchVersion() {
	     $form = new Nette\Application\UI\Form;
	     $form->addText('search', 'Hledat:')
				->setHtmlAttribute('class', 'form-control')
				->setHtmlAttribute('placeholder','Hledaná verze nebo IČ');
	     $form->addSubmit('send', 'Hledat')->setHtmlAttribute('class','btn btn-primary');
	     $form->addSubmit('storno', 'Zrušit')->setHtmlAttribute('class','btn btn-primary');
	     $form->onSuccess[] = array($this, 'SearchVersionSubmitted');
	     return $form;
	 }

	public function SearchVersionSubmitted($form) {
	    if ($form['send']->submittedBy)
	    {	    
			$values = $form->getValues();	    
			$this->filter = $values->search;
			$this->redirect('this');		
	    }
	    if ($form['storno']->submittedBy)
	    {
			$this->filter = '';
			$form->setValues(array('search'=>''));
			$this->redirect('this');
	    }	    			    
	}

    private function generateZIP($vendor = FALSE){
        //dump($this->id);
        //die;
        $tmpData = $this->VersionsManager->find($this->id);
        if ($tmpData){
            //prepare folder
            $tmpDir = __DIR__ . "/../../../upgrades/";
            if (!is_dir($tmpDir))
                mkdir($tmpDir);

            $versionDir = __DIR__ . "/../../../upgrades/" . $tmpData['version'] . '/';
            if (!is_dir($versionDir))
                mkdir($versionDir);

            //changelist.txt
            $srcChangelist = __DIR__ . "/../../../changelist.txt";
            if (is_file($srcChangelist)) {
                $tmpChangelist = $versionDir . 'changelist.txt';
                copy($srcChangelist, $tmpChangelist);
            }
            //update-.sql
            $srcUpdateSql = __DIR__ . "/../../../update-" . $tmpData['version'] . ".sql";
            if (is_file($srcUpdateSql)) {
                $tmpUpdateSql = $versionDir . 'update.sql';
                copy($srcUpdateSql, $tmpUpdateSql);
            }

            //app
            $appDir = $versionDir . 'app/';
            $srcApp = __DIR__ . "/../../../app";
            if (is_dir($srcApp)) {
            //if (!is_dir($srcApp)) {
                \Nette\Utils\FileSystem::copy($srcApp, $appDir);
                //delete config files, only common.neon is part of upgrade
                $tmpConfig = $versionDir . 'app/config';
                \Nette\Utils\FileSystem::delete($tmpConfig);
                \Tracy\Debugger::timer();
                $time = 0;
                while(is_dir($tmpConfig) && $time < 20){
                    //wait till is directory deleted or time is greater then 10 sec
                    $time = \Tracy\Debugger::timer();
                }
                if ($time >= 20){
                    $this->flashMessage('Došlo k chybě při mazání app/config . Zkontrolujte stav.', 'danger');
                }
                mkdir($tmpConfig);
                $srcConfig = __DIR__ . "/../../../app/config/common.neon";
                $tmpConfig = $versionDir . 'app/config/common.neon';
                \Nette\Utils\FileSystem::copy($srcConfig, $tmpConfig);


                //delete administration module, it's not a part of upgrade
                /*$tmpAdmin = $versionDir . 'app/AdministrationModule';
                \Nette\Utils\FileSystem::delete($tmpAdmin);
                \Tracy\Debugger::timer();
                $time = 0;
                while(is_dir($tmpAdmin) && $time < 20){
                    //wait till is directory deleted or time is greater then 10 sec
                    $time = \Tracy\Debugger::timer();
                }
                if ($time >= 20){
                    $this->flashMessage('Došlo k chybě při mazání app/config . Zkontrolujte stav.', 'danger');
                }*/



            }

            //bin
            $binDir = $versionDir . 'bin/';
            $srcBin = __DIR__ . "/../../../bin";
            if (is_dir($srcBin)) {
                \Nette\Utils\FileSystem::copy($srcBin, $binDir);
            }

            //vendor
            if ($vendor) {
                $vendorDir = $versionDir . 'vendor/';
                $srcVendor = __DIR__ . "/../../../vendor";
                if (is_dir($srcVendor)) {
                    \Nette\Utils\FileSystem::copy($srcVendor, $vendorDir);
                }
            }

            //www
            $wwwDir = $versionDir . 'www/';
            $srcWww = __DIR__ . "/../../../www";
            if (is_dir($srcWww)) {
                \Nette\Utils\FileSystem::copy($srcWww, $wwwDir);
            }

            //make zip
            $dir = __DIR__ . "/../../../upgrades/";
            $zipFile = $dir . 'upgrade-' . $tmpData['version'] . '.zip';
            if (file_exists($zipFile))
                unlink($zipFile);

            $this->makezip($zipFile, $versionDir);

            //md5 checksum
            $md5Checksum = md5_file($zipFile);
            $tmpData->update(['md5_checksum' => $md5Checksum]);
        }

    }

    private function makezip($zipFile, $sourceDir){
// Get real path for our folder
        $rootPath = realpath($sourceDir);

// Initialize archive object
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

// Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file)
        {
            // Skip directories (they would be added automatically)
            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

// Zip archive will be created only after closing object
        $zip->close();
    }

    /**make sql file from given string
     * @param $sql_script
     * @param $version
     */
    private function makeSQLFile($sql_script, $version)
    {
        $file = __DIR__ . "/../../../update-" . $version . ".sql";
        file_put_contents($file, $sql_script);
        return;
    }


}
