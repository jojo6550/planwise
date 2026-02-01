<?php
/**
 * Database Integration Tests
 * Tests database connectivity and operations
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/Database.php';

class DatabaseTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection()
    {
        $this->assertInstanceOf(Database::class, $this->db);
        $pdo = $this->db->getConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    /**
     * Test simple query execution
     */
    public function testQueryExecution()
    {
        $sql = "SELECT 1 as test";
        $result = $this->db->fetch($sql);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['test']);
    }

    /**
     * Test prepared statement with parameters
     */
    public function testPreparedStatement()
    {
        $sql = "SELECT :value as test";
        $result = $this->db->fetch($sql, [':value' => 'hello']);
        
        $this->assertIsArray($result);
        $this->assertEquals('hello', $result['test']);
    }

    /**
     * Test fetchAll returns array
     */
    public function testFetchAll()
    {
        $sql = "SELECT role_id, role_name FROM roles LIMIT 2";
        $results = $this->db->fetchAll($sql);
        
        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
    }

    /**
     * Test singleton pattern
     */
    public function testSingletonPattern()
    {
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();
        
        $this->assertSame($db1, $db2);
    }

    /**
     * Test transaction support
     */
    public function testTransaction()
    {
        $this->assertTrue($this->db->beginTransaction());
        $this->assertTrue($this->db->rollback());
    }
}
