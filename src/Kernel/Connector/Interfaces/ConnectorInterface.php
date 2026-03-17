<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Interfaces;


interface ConnectorInterface 
{
    /**
     * return direct connection to database. Generally \PDO
     *
     * @return mixed
     */
    public function getConnection(): mixed;

    /**
     * Get the singleton instance. If created, pass $env array as arguments
     *
     * @return ConnectorInterface
     */
    public static function getInstance(): ConnectorInterface;

    /**
     * Get a special DB Connection, not mapped to database. Not a singleton
     * As getInstance, if not initialized, needs $env array as argument
     *
     * @return ConnectorInterface
     */
    public static function getDetachedConnector(): ConnectorInterface;

    /**
     * Execute a Query that returns no datas
     *
     * @param string $sql
     * @param array $params
     * @return bool|int : true or last insertId if ok, false otherwise
     */
    public function executeQuery(string $sql, array $params=[]): bool | int;

    /**
     * Fetch datas in database
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchQuery(string $sql, array $params=[]): array;

    /**
     * Fetch one row in database
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function FetchQueryOnce(string $sql, array $params = []): ?array;

    public function startTransac(): void;

    public function commitTransac(): void;
    
    public function rollBack(): void;

    /**
     * Used in migration. If database support transaction on CREATE, DELETE OR ALTER
     *
     * @return boolean
     */
    public function supportsTransactionalDDL(): bool;

    /**
     * Return CREATE DATABASE query
     *
     * @param string $name : name of database
     * @return string
     */
    public function getCreateDatabaseQuery(string $name): string;
}
