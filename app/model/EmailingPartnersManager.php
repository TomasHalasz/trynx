<?php
 
namespace App\Model;

use Nette,
	Nette\Utils\Strings;

 
class EmailingPartnersManager
{
	/** @var Nette\Database\Context */
	private $db;
	
	const
		TABLE_NAME = 'cl_emailing_partners',
		COLUMN_ID = 'id';	


	public function __construct(Nette\Database\Context $database)
	{
		$this->db = $database;
	}

 
        public function getById($id)
        {
              return $this->db->table(self::TABLE_NAME)->where(self::COLUMN_ID.'=?',$id)->limit(1)->fetch();
        }

        public function getAll()
        {
              return $this->db->table(self::TABLE_NAME);
        }        

        public function create($data)
        {
             return $this->db->table(self::TABLE_NAME)->insert($data);
        }
        
        public function save($id,$data)
        {
             return $this->db->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))->update($data);
        }        

        public function erase($id)
        {
             return $this->db->table(self::TABLE_NAME)->where(array(self::COLUMN_ID => $id))->delete();
        }

}