<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Logs presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminLogsPresenter extends SecuredPresenter
{
	
  
    public function renderDefault()
    {
        $dir = APP_DIR.'/../log';
          if (is_dir($dir)) { 
            $objects = scandir($dir); 
            $myFiles = [];
            foreach($objects as $one)
            {
                $fName = APP_DIR.'/../log/'.$one;
                if (file_exists($fName) && $one != '.' && $one != '..')
                {
                    $myFiles[$one] = filemtime($fName);
                }
            }
            arsort($myFiles);
            //dump($myFiles);
            //die;
            $this->template->files = $myFiles ;
            //dump($objects);
            //die;
          }
    }	
	

    public function handleEraseLog()
    {
        $dir = APP_DIR.'/../log';
        if (is_dir($dir)) 
        { 
            $objects = scandir($dir);         
            foreach ($objects as $object) 
            { 
                if ($object != "." && $object != "..")
                {
                  if (is_file($dir."/".$object))
                  {
                      unlink($dir."/".$object); 
                  }
                } 
            }        
        }
        $this->redirect('this');        
    }
    
    public function handleEraseFile($file)
    {
        $dir = APP_DIR.'/../log';
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
        $dir = APP_DIR.'/../log';
        
        $file = $dir.'/'.$file;
        if (is_file($file))
        {
            $cont = file_get_contents($file);
            if (substr($file,-3) == 'log')
                header("Content-Type: text/plain");
            else
                header("Content-Type: text/html");
            
            echo($cont);
            die;
        }else{
            $this->redirect('Default');
        }
        
    }


}
