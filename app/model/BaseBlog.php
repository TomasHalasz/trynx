<?php
namespace App\Model;

use Nette\Utils\Arrays;

class BaseBlog
{
    /** @var Nette\Database\Context */
    public $database;
    public $userManager;
    public $user;
    
    /** @var string */
    public $tableName;


    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user)
    {
	    $this->database = $db;
	    $this->userManager = $userManager;
	    $this->user = $user;
	    
	    if($this->tableName === NULL) {
		    $class = get_class($this);
		    throw new \Nette\InvalidStateException("Název tabulky musí být definován v $class::\$tableName.");
	    }
    }    

    /**
     * Vrací table
     * @return type
     */
    protected function getTable() {
		return $this->database->table($this->tableName);
    }
    
    public function getTableName() {
		return ($this->tableName);
    }
        

    /**
     * Vrací všechny záznamy z databáze
     * @return \Nette\Database\Table\Selection
     */
    public function findAll() {
	    return $this->getTable();
    }
    
    
    /**
     * Vrací vyfiltrované záznamy na základě vstupního pole
     * (pole array('name' => 'David') se převede na část SQL dotazu WHERE name = 'David')
     * @param array $by
     * @return \Nette\Database\Table\Selection
     */
    public function findBy(array $by) {
	    return $this->getTable()->where($by);
    }

    /**
     * To samé jako findBy akorát vrací vždy jen jeden záznam
     * @param array $by
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function findOneBy(array $by) {
	    return $this->findBy($by)->limit(1)->fetch();
    }

    /**
     * Vrací záznam s daným primárním klíčem
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function find($id) {
	    return $this->getTable()->get($id);
    }

    /**
     * Upraví záznam
     * @param array $data
     */
    public function update($data) {
		if ($this->user->isLoggedIn())
		{
			$data['change_by'] = $this->user->getIdentity()->name;
			$data['changed'] = new \Nette\Utils\DateTime;				
		}

		$this->findBy(array('id'=>$data['id']))->update($data);

			
    }

    /**
     * Vloží nový záznam a vrátí jeho ID
     * @param array $data
     * @return \Nette\Database\Table\ActiveRow
     */
    public function insert($data) {
		if ($this->user->isLoggedIn())
		{
			$data['create_by'] = $this->user->getIdentity()->name;
		} else {
			$data['create_by'] = 'anonym';
		}
		
		if ($data['create_by'] === NULL)
		{
			$data['create_by'] = '';
		}
		
	    $data['created'] = new \Nette\Utils\DateTime;

	    return $this->getTable()->insert($data);
    }
    

    public function delete($id) {
		$this->getTable()->where('id',$id)->delete();
    }    
    
    
}