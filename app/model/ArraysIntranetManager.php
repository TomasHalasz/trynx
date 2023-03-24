<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Arrays Intranet management.
 */
class ArraysIntranetManager
{
	
	
	public function getEstateGroup()
	{
		$arrStatus = array( 0 => 'majetek',
			1 => 'pracovní a ochranné pomůcky',
			2 => 'stroje a zařízení');
		return $arrStatus;
	}
	
	public function getEstateGroupName($id)
	{
		return $this->getEstateGroup()[$id];
	}
	

    public function getParamTypes()
    {
        $arrStatus = array(
            0 => 'Text',
            1 => 'Celé číslo',
            2 => 'Desetinné číslo',
            3 => 'Datum a čas',
            4 => 'Datum',
            5 => 'Dlouhý text',
        );
        return $arrStatus;
    }

    public function getParamTypesName($id)
    {
        return $this->getParamTypes()[$id];
    }


    public function getMoveTypes()
    {
        $arrStatus = array(
            0 => 'Přijato',
            1 => 'Odebráno'
        );
        return $arrStatus;
    }

    public function getMoveTypeName($id)
    {
        return $this->getMoveTypes()[$id];
    }





    public function getEventTypes()
    {
        $arrStatus = array(
            4 => 'Změna umístění',
            0 => 'Provozní',
            1 => 'Porucha',
            2 => 'Oprava',
            3 => 'Revize'
        );
        return $arrStatus;
    }

    public function getEventTypeName($id)
    {
        return $this->getEventTypes()[$id];
    }



    public function getProfessionCategories()
    {
        $arrStatus = array(
                            1 => 'Kategorie 1',
                            2 => 'Kategorie 2',
                            3 => 'Kategorie 3',
                            4 => 'Kategorie 4'
                            );
        return $arrStatus;
    }    
    
    public function getProfessionCategoryName($id)
    {
	    return $this->getProfessionCategories()[$id];
    }


    public function getRisksCategories()
    {
        $arrStatus = array(
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4'
        );
        return $arrStatus;
    }

    public function getRisksCategoryName($id)
    {
        return $this->getRisksCategories()[$id];
    }

    public function getGenders()
    {
        $arrStatus = array(0 => 'Muž',
            1 => 'Žena',
            2 => 'Třetí'
        );
        return $arrStatus;
    }

    public function getGenderName($id)
    {
        return $this->getGenders()[$id];
    }


    public function getTitles()
    {
        $arrStatus = array(0 => 'Pan(í)',
            1 => 'Bc.',
            2 => 'Mgr.',
            3 => 'Ing.',
            4 => 'Dr.',
            5 => 'Doc.',
            6 => 'Prof.',
            7 => 'MUDr.'
        );
        return $arrStatus;
    }

    public function getTitleName($id)
    {
        if (!is_null($id) && $id != '')
            return $this->getTitles()[$id];
        else
            return "";
    }

}

