<?php

use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    public function testSimpleSelectRequest(): void
    {
        $qb = new QueryBuilder('myTable');
        $query = 'SELECT * FROM myTable';
        $this->assertEquals($query, $qb->toSQL());
    }

    public function testSelectWhereRequest(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->where('status', '=', 'active');
        $query = 'SELECT * FROM myTable WHERE status = ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('active', $params[0]);
    }

    public function testSelectMultipleWhereSelectRequest(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->where('status', '=', 'active')
            ->where('name', '=', 'John');
        $query = 'SELECT * FROM myTable WHERE status = ? AND name = ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('active', $params[0]);
        $this->assertEquals('John', $params[1]);
    }

    public function testSelectWhereInSelectRequest(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->whereIn('role', ['admin', 'moderator']);
        $query = 'SELECT * FROM myTable WHERE role IN (?,?)';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('admin', $params[0]);
        $this->assertEquals('moderator', $params[1]);
    }

    public function testSelectWhereWhereInSelectRequest(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->whereIn('role', ['admin', 'moderator'])
            ->Where('name', '=', 'John');
        $query = 'SELECT * FROM myTable WHERE role IN (?,?) AND name = ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('admin', $params[0]);
        $this->assertEquals('moderator', $params[1]);
        $this->assertEquals('John', $params[2]);
    }

    public function testSelectWhereLikeSelectRequest(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->where('status', 'LIKE', '%active%');
        $query = 'SELECT * FROM myTable WHERE status LIKE ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('%active%', $params[0]);
    }

    public function testSelectOrderBy(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->orderBy('status', 'DESC');
        $query = 'SELECT * FROM myTable ORDER BY status DESC';
        $this->assertEquals($query, $qb->toSQL());
    }

    public function testSelectWhereOrderBy(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->where('status', '=', 'active')
            ->orderBy('status', 'DESC');
        $query = 'SELECT * FROM myTable WHERE status = ? ORDER BY status DESC';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('active', $params[0]);
    }

    public function testSelectOrderByLimit(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->orderBy('status', 'DESC')
            ->limit(10);
        $query = 'SELECT * FROM myTable ORDER BY status DESC LIMIT 10';
        $this->assertEquals($query, $qb->toSQL());
    }

    public function testSelectOneColumn(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->select(['name']);
        $query = 'SELECT name FROM myTable';
        $this->assertEquals($query, $qb->toSQL());
    }

    public function testSelectMultiColumns(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->select(['name', 'firstname']);
        $query = 'SELECT name, firstname FROM myTable';
        $this->assertEquals($query, $qb->toSQL());
    }

    public function testInsertValue(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->insert(['name'])
            ->values(['John']);
        $query = 'INSERT INTO myTable (name) VALUES (?)';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('John', $params[0]);
    }


    public function testInsertValues(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->insert(['name', 'firstname'])
            ->values(['John', 'Doe']);
        $query = 'INSERT INTO myTable (name, firstname) VALUES (?, ?)';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('John', $params[0]);
        $this->assertEquals('Doe', $params[1]);
    }

    public function testUpdateBadValue(): void
    {
        $qb = new QueryBuilder('myTable');
        $this->expectException(DatabaseException::class);
        $qb->update(['name', 'firstname']);
    }

    public function testUpdateOneValue(): void
    {
        $qb = new QueryBuilder('myTable');
        $updateValues = [
            'name' => 'Doe',
        ];
        $qb->update($updateValues)
            ->where('id', '=', 1);
        $query = 'UPDATE myTable SET name = ? WHERE id = ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('Doe', $params[0]);
    }

    public function testUpdateMultipleValues(): void
    {
        $qb = new QueryBuilder('myTable');
        $updateValues = [
            'name' => 'Doe',
            'firstname' => 'John'
        ];
        $qb->update($updateValues)
            ->where('id', '=', 1);
        $query = 'UPDATE myTable SET name = ?, firstname = ? WHERE id = ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals('Doe', $params[0]);
        $this->assertEquals('John', $params[1]);
    }

    public function testDeleteValue(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->delete(1)
        ->where('id','=',1);
        $query = 'DELETE FROM myTable WHERE id = ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals(1, $params[0]);
    }
}
