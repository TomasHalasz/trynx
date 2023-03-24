<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Reprort management.
 */
class ReportManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_reports';
	
	/** @var App\Model\ArraysManager */
	public $ArraysManager;
	/** @var App\Model\CompaniesManager */
	public $CompaniesManager;
	
	/**
	 * @param Nette\Database\Connection $db
	 * @throws Nette\InvalidStateException
	 */
	public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor,
                                CompaniesManager $CompaniesManager, ArraysManager $ArraysManager, \Nette\Http\Session $session)
	{
		parent::__construct($db, $userManager, $user, $session, $accessor);
		$this->ArraysManager = $ArraysManager;
		$this->CompaniesManager = $CompaniesManager;
	}

	public function getReport($latteFile)
	{
        $this->settings = $this->CompaniesManager->getTable()->fetch();
		$fileName = basename($latteFile);
		$tmpReport = $this->findAll()->where('report_name = ? AND active = 1', $fileName)->order('id DESC')->limit(1)->fetch();
		if ($tmpReport)
		{
			$dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
			$subFolder = $this->ArraysManager->getSubFolder(FALSE, 'report');
			$dir = $dataFolder . '/' . $subFolder;
			$retName = $dir . '/' . $tmpReport['report_file'];
		}else{
			$retName = $latteFile;
		}
		return $retName;
	}

    public function getReport2($arrTemplates)
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $arrRet = [];
        $i = 0;
        foreach($arrTemplates as $keyTemp => $oneTemp){
            //bdump($i);
            $fileName = basename($oneTemp['latte']);
            $tmpReport = $this->findAll()->where('report_name = ? AND active = 1', $fileName)->order('id DESC');
            $arrRet[$i] = ['key' => $keyTemp, 'name' => $oneTemp['name'], 'file' => $oneTemp['latte']];
            //bdump($arrRet);
            $i++;
            foreach($tmpReport as $key => $one)
            {
                if ($one['replace_origin'] == 1){
                    $i--;
                }
                $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
                $subFolder = $this->ArraysManager->getSubFolder(FALSE, 'report');
                $dir = $dataFolder . '/' . $subFolder;
                $retName = $dir . '/' . $one['report_file'];
                if (file_exists($retName))
                    $arrRet[$i] = ['key' => $keyTemp, 'name' => $one['report_description'], 'file' => $retName];
                $i++;
                //bdump($arrRet);
            }
        }

        return $arrRet;
    }

	
}

