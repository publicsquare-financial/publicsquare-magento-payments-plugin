<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use PDO;

// all public methods declared in helper class will be available in $I

class DbHelper extends \Codeception\Module
{
    public function getDbh(): PDO
    {
        return $this->getModule('Db')->_getDbh();
    }

    public function startDatabaseTransaction()
    {
        $this->getModule('Db')->_getDbh()->beginTransaction();
    }

    public function rollbackDatabaseTransaction()
    {
        $this->getModule('Db')->_getDbh()->rollBack();
    }

    public function executeOnDatabase($sql)
    {
        $dbh = $this->getModule('Db')->_getDbh();
        $sth = $dbh->prepare($sql);
        return $sth->execute();
    }
}
