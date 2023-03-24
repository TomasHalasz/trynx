<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * RegCountries management.
 */
class RegCountriesManager
{
    public $database;
    public $userManager;
    public $user;
	const COLUMN_ID = 'id';
	public $tableName = 'cl_countries';

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user,  \DatabaseAccessor $accessor, ArraysManager $ArraysManager)
    {
        //$this->database = $db;
        $this->userManager = $userManager;
        $this->user = $user;

        $this->database = $accessor->get('dbCurrent');

        //$database = $accessor->get("db2");
        if($this->tableName === NULL) {
            $class = get_class($this);
            throw new \Nette\InvalidStateException("Název tabulky musí být definován v $class::\$tableName.");
        }
    }

    public function getDatabase(){
        return $this->database;
    }

    /**
     * Vrací všechny záznamy z databáze bez ohledu na vlastnickou cl_company_id
     * @return \Nette\Database\Table\Selection
     */
    public function findAllTotal() {
        return $this->database->table($this->tableName);
    }




}

