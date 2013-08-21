<?php

namespace Weavora\Doctrine\DBAL;

use Weavora\TestCase;

class ConnectionTest extends TestCase
{
    public function testLocksSafeUpdate()
    {
        $connection = \Mockery::mock('Weavora\Doctrine\DBAL\Connection')->shouldDeferMissing();
        $connection->shouldReceive('executeUpdate')->andReturn(100);

        $this->assertEquals(100, $connection->locksSafeUpdate('UPDATE users SET is_old = 1'));

        $connection = \Mockery::mock('Weavora\Doctrine\DBAL\Connection')->shouldDeferMissing();
        $connection->shouldReceive('executeUpdate')->andReturnUsing(
            // during first attempt we got lock
            function($query) {
                throw new \Exception('Error: 1205 SQLSTATE: HY000 (ER_LOCK_WAIT_TIMEOUT) Message: Lock wait timeout exceeded; try restarting transaction');
            },
            // second attempt successfully finished
            function($query) {
                return 123;
            }
        );
        $this->assertEquals(123, $connection->locksSafeUpdate('UPDATE users SET is_old = 1'));
    }
}