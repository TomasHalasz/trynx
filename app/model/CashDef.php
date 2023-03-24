<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Cash Definition management.
 */
class CashDefManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_cash_def';

    /** @var Nette\Database\Context */
    public $InvoiceTypesManager;

    /** @var Nette\Database\Context */
    public $NumberSeriesManager;

    /** @var Nette\Database\Context */
    public $PairedDocsManager;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
                                InvoiceTypesManager $InvoiceTypesManager, NumberSeriesManager $NumberSeriesManager,
                                PairedDocsManager $PairedDocsManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->InvoiceTypesManager = $InvoiceTypesManager;
        $this->NumberSeriesManager = $NumberSeriesManager;
        $this->PairedDocsManager   = $PairedDocsManager;
    }

}

