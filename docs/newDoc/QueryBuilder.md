# QueryBuilder

## Overview

`QueryBuilder` is a lightweight SQL query builder written in PHP. Its purpose is to programmatically construct SQL queries (SELECT, INSERT, UPDATE, DELETE) using a fluent, chainable API while keeping SQL generation consistent and parameterized.

The class focuses on:

- Building SQL strings dynamically
- Supporting prepared-statement style parameters (`?` placeholders)
- Converting PHP-style property names (camelCase) into SQL field names (snake_case)
- Providing a simple abstraction over common SQL operations

This component **does not execute queries**. It only generates:

- The SQL string (`toSql()`)
- The associated parameters (`getParams()`)

Execution is expected to be handled by a separate database layer (PDO, etc.).

---

## Key Concepts

### Table Binding

A `QueryBuilder` instance is always bound to a single main table, defined at construction time:

```php
$qb = new QueryBuilder('myTable');
```

This table is used as the base for all generated queries.

---

### Property Name Conversion

Column names provided in camelCase are automatically converted to snake_case when generating SQL.

Example:

```php
$qb->where('createdAt', '=', '2024-01-01');
```

Generates:

```sql
created_at = ?
```

---

## Supported SQL Operations

### SELECT

#### Simple SELECT

```php
$qb = new QueryBuilder('myTable');
$sql = $qb->toSql();
```

```sql
SELECT * FROM myTable
```

---

#### SELECT With Columns

```php
$qb->select(['id', 'userName']);
```

```sql
SELECT id, user_name FROM myTable
```

---

#### SELECT With Aliases

```php
$qb->select([
    'userName' => 'name',
    'createdAt' => 'created'
]);
```

```sql
SELECT user_name AS name, created_at AS created FROM myTable
```

---

#### WHERE Clauses

```php
$qb->where('status', '=', 'active')
   ->where('age', '>', 18);
```

```sql
WHERE status = ? AND age > ?
```

Parameters:

```php
['active', 18]
```

---

#### WHERE IN

```php
$qb->whereIn('id', [1, 2, 3]);
```

```sql
WHERE id IN (?, ?, ?)
```

---

#### ORDER BY and LIMIT

```php
$qb->orderBy('createdAt', 'DESC')
   ->limit(10);
```

```sql
ORDER BY created_at DESC LIMIT 10
```

---

## JOIN Queries

```php
$qb->selectJoin(['id', 'name'])
   ->join(
       'profile',
       ['bio'],
       ['user' => 'id', 'profile' => 'user_id']
   );
```

```sql
SELECT user.id, user.name, profile.bio
FROM user INNER JOIN profile ON user.id = profile.user_id
```

Supported join types:
- INNER JOIN
- LEFT JOIN
- RIGHT JOIN

---

## INSERT

```php
$qb->insert(['userName', 'email'])
   ->values(['john', 'john@example.com']);
```

```sql
INSERT INTO myTable (user_name, email) VALUES (?, ?)
```

---

## UPDATE

```php
$qb->update([
    'userName' => 'john',
    'email' => 'john@example.com'
])->where('id', '=', 1);
```

```sql
UPDATE myTable SET user_name = ?, email = ? WHERE id = ?
```

---

## DELETE

```php
$qb->delete()
   ->where('id', '=', 1);
```

```sql
DELETE FROM myTable WHERE id = ?
```

---

## Retrieving SQL and Parameters

```php
$sql = $qb->toSql();
$params = $qb->getParams();
```

---

## Design Limitations

- No query execution
- No GROUP BY / HAVING
- No subqueries
- AND-only WHERE conditions

---

## Summary

`QueryBuilder` provides a concise and predictable way to generate parameterized SQL queries in PHP without the overhead of a full ORM.
