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
            ->where('firstName', '=', 'John');
        $query = 'SELECT * FROM myTable WHERE status = ? AND first_name = ?';
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
        $qb->whereIn('roleUser', ['admin', 'moderator'])
            ->Where('name', '=', 'John');
        $query = 'SELECT * FROM myTable WHERE role_user IN (?,?) AND name = ?';
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
            ->orderBy('statusObject', 'DESC');
        $query = 'SELECT * FROM myTable WHERE status = ? ORDER BY status_object DESC';
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
        $qb->select(['name', 'firstName']);
        $query = 'SELECT name, first_name FROM myTable';
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
        $qb->insert(['name', 'firstName'])
            ->values(['John', 'Doe']);
        $query = 'INSERT INTO myTable (name, first_name) VALUES (?, ?)';
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
            'firstName' => 'John'
        ];
        $qb->update($updateValues)
            ->where('id', '=', 1);
        $query = 'UPDATE myTable SET name = ?, first_name = ? WHERE id = ?';
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
            ->where('id', '=', 1);
        $query = 'DELETE FROM myTable WHERE id = ?';
        $this->assertEquals($query, $qb->toSQL());
        $params =  $qb->getParams();
        $this->assertIsArray($params);
        $this->assertEquals(1, $params[0]);
    }

    public function testSelectAs(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->select(['name' => 'nom']);
        $query = 'SELECT name AS nom FROM myTable';
        $this->assertEquals($query, $qb->toSQL());
    }

    public function testSelectAsMultiColumns(): void
    {
        $qb = new QueryBuilder('myTable');
        $qb->select(['FirstName' => 'nameFirst', 'lastName' => 'nameLast']);
        $query = 'SELECT first_name AS name_first, last_name AS name_last FROM myTable';
        $this->assertEquals($query, $qb->toSQL());
    }
    public function testBadSelectJoin(): void
    {
        $qb = new QueryBuilder('table1');
        $qb->selectJoin(['firstname', 'lastname']);
        $this->expectException(DatabaseException::class);
        $qb->join('table2', ['role'], ['table2', 'table2' => 'userid']);
    }

    public function testBadSelectJoin2(): void
    {
        $qb = new QueryBuilder('table1');
        $qb->selectJoin(['table1' => 'id', 'table2' => 'userid']);
        $this->expectException(DatabaseException::class);
        $qb->join(
            'table2',
            ['role'],
            [
                'table1' => 'userizezed',
                'table2' => 'userid',
                'table3' => 'toto',
            ]
        );
    }

    public function testSelectJoin(): void
    {
        $query = "SELECT table1.firstname, table1.lastname, table2.role FROM table1 INNER JOIN table2 ON table1.id = table2.userid";
        $qb = new QueryBuilder('table1');
        $qb->selectJoin(['firstname', 'lastname'])
            ->join('table2', ['role'], ['table1' => 'id', 'table2' => 'userid']);
        $this->assertEquals($query, $qb->toSQL());
    }

    public function testSelectJoinDouble(): void
    {
        $query = "SELECT table1.firstname, table1.lastname, table2.role, table3.champ1 FROM table1 INNER JOIN table2 ON table1.id = table2.userid INNER JOIN table3 ON table1.id = table3.user";
        $qb = new QueryBuilder('table1');
        $qb->selectJoin(['firstname', 'lastname'])
            ->join('table2', ['role'], ['table1' => 'id', 'table2' => 'userid'])
            ->join('table3',['champ1'],['table1' => 'id', 'table3' => 'user']);
        $this->assertEquals($query, $qb->toSQL());
    }

    // public function testLeftJoinOne(): void
    // {
       
    // }

}
