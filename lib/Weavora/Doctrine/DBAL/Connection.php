<?php

namespace Weavora\Doctrine\DBAL;

class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * How long we should wait before restart locked transaction
     * @var int
     */
    protected $transactionRestartDelay = 1;

    /**
     * Execute update query
     * Return number of affected rows
     *
     * @param string $query SQL query
     * @param array $params
     * @param int $maxAttempts
     *
     * @throws \Exception
     *
     * @return int number of affected rows
     */
    public function locksSafeUpdate($query, $params = array(), $maxAttempts = 3)
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->executeUpdate($query, $params);
            } catch (\Exception $e) {
                // we need try execute query again in case of followign MySQL errors:
                // Error: 1205 SQLSTATE: HY000 (ER_LOCK_WAIT_TIMEOUT) Message: Lock wait timeout exceeded; try restarting transaction
                // Error: 1213 SQLSTATE: 40001 (ER_LOCK_DEADLOCK) Message: Deadlock found when trying to get lock; try restarting transaction
                if (stripos($e->getMessage(), 'try restarting transaction') === false || $attempt == $maxAttempts) {
                    throw $e;
                }

                sleep($this->transactionRestartDelay);
            }
        }
    }
}