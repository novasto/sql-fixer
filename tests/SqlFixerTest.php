<?php
use PHPUnit\Framework\TestCase;
use Novasto\SqlFixer\SqlFixer;

class SqlFixerTest extends TestCase
{
    /**
     * @test
     */
    public function test_PHPFile()
    {
        [$formatted, $has_diff] = SqlFixer::format(__DIR__.'/data/php.php');
        $this->assertEquals(file_get_contents(__DIR__.'/data/php_expect.php'), $formatted);
        $this->assertTrue($has_diff);
    }

    /**
     * @test
     */
    public function test_SQLFile()
    {
        [$formatted, $has_diff] = SqlFixer::format(__DIR__.'/data/sql.sql');
        $this->assertEquals(file_get_contents(__DIR__.'/data/sql_expect.sql'), $formatted);
        $this->assertTrue($has_diff);
    }
}
