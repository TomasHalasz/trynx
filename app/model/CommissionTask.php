<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Commission Task management.
 */
class CommissionTaskManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_commission_task';




    /** @var App\Model\PricelistManager */
    public $PricelistManager;


    /**
     * @inject
     * @var \App\Model\CommissionWorkManager
     */
    public $CommissionWorkManager;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session,
                                \App\Model\PriceListManager $priceListManager, ArraysManager $ArraysManager, \DatabaseAccessor $accessor, CommissionWorkManager $commissionWorkManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);

        $this->PricelistManager = $priceListManager;
        $this->CommissionWorkManager = $commissionWorkManager;
    }

    public function  pricelistTaskInsert($cl_pricelist_id, $cl_commission_id){
        $tmpPricelist = $this->PricelistManager->findAll()->where('id = ?', $cl_pricelist_id)->fetch();
        $itemOrder = $this->findAll()->where('cl_commission_id = ?', $cl_commission_id)->max('item_order');
        foreach($tmpPricelist->related('cl_pricelist_task')->order('item_order') as $key => $one)
        {
            //$tmpTask = $this->findAll()->where('cl_commission_id = ? AND cl_pricelist_task_id = ?', $cl_commission_id, $key)->fetch();
            //$nmbRep = ($one['nmb_repeats'] == 0) ? 1 : $one['nmb_repeats'];

            $itemOrder++;
            //create task
            $newTask = $this->insert([
                'cl_commission_id'      => $cl_commission_id,
                'cl_pricelist_task_id'  => $key,
                'item_order'            => $itemOrder,
                'number'                => $one['number'],
                'name'                  => $one['name'],
                'description'           => $one['description'],
                'work_time'             => $one['work_time'],
                'units'                 => $one['units'],
                'cl_workplaces_id'      => $one['cl_workplaces_id'],
                'cl_users_id'           => $one['cl_users_id'],
                'work_rate'             => $one['work_rate'],
                'qty_norm'                => $one['qty_norm']
            ]);
//                'work_date_s'           => $one['work_date_s'],
//                'work_date_e'           => $one['work_date_e'],

            $nmbRep     = ($one['is_work'] == 1) ? $one['nmb_repeats'] : 1;
            $itemOrder2 = $this->CommissionWorkManager->findAll()->where('cl_commission_id = ?', $cl_commission_id)->max('item_order');
            $cntTask    = $this->CommissionWorkManager->findAll()->where('cl_commission_id = ? AND cl_commission_task_id = ?', $cl_commission_id, $newTask['id'])->count();
            for($i = $cntTask; $i < $nmbRep; $i++)
            {
                $itemOrder2++;
                $this->CommissionWorkManager->insert([
                    'cl_commission_id'      => $cl_commission_id,
                    'item_order'            => $itemOrder2,
                    'work_label'            => $one['name'],
                    'work_rate'             => $one['work_rate'],
                    'cl_commission_task_id' => $newTask['id'],
                    'cl_users_id'           => $one['cl_users_id'],
                    'cl_workplaces_id'      => $one['cl_workplaces_id']
                ]);

            }

        }
    }
	
}

