<?php

/**
 * Database connection and query execution class using PDO.
 */
class Database
{
    private PDO $pdo;
    private PDOStatement|false $statement = false;
    /* ΟΙ ΣΤΑΘΕΡΕΣ ΕΔΩ ΠΡΕΠΕΙ ΝΑ ΓΙΝΟΥΝ ENV VARIABLES */
    private string $host = DB_HOST;
    private string $user = DB_USER;
    private string $pass = DB_PASS;
    private string $dbname = DB_NAME;
    private string $charset = DB_CHARSET;

    /**
     * Constructor to initialize the PDO connection.
     */
    public function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try
        {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        }
        catch (PDOException $e)
        {
            throw new Exception("Connection Error: " . $e->getMessage());
        }
    }

    /**
     * Executes a query with optional parameters.
     * @param string $sql The SQL query to execute.
     * @param array $params optional parameters for prepared statement.
     * @return $this The current Database instance.
     */
    public function query(string $sql, array $params = []): self
    {
        try
        {
            $this->statement = $this->pdo->prepare($sql);
            $this->statement->execute($params);
            return $this;
        }
        catch (PDOException $e)
        {
            throw new Exception("SQL Query Error: " . $e->getMessage() . " | Query: " . $sql);
        }
    }

    /**
     * Creates a database table with support for composite primary keys, unique keys, and foreign keys.
     * * @param string $tableName The name of the table.
     * @param array $fields Array of field definitions.
     * @param array $primary_keys Array of column names for composite primary key.
     * @param array $unique_keys Array of arrays for unique constraints.
     * @param array $fulltext_keys Array of column names for fulltext indexes.
     * @return bool
     * @throws Exception
     * 
     * $fields:
     * [
     *  'name' => 'field_name',
     *  'type' => 'DATA_TYPE',
     *  'constraints' => ['NOT NULL', 'AUTO_INCREMENT', ...],
     *  'is_primary' => true/false,
     *  'is_fk' => true/false,
     *  'references' => 'referenced_table(referenced_column)'
     * ]
     */
    public function createTable(string $tableName, array $fields, array $primary_keys = [], array $unique_keys = [], array $fulltext_keys = []): bool
    {
        $fieldDefinitions = [];
        $pk_cols = $primary_keys;
        $foreignKeys = [];
        $simpleIndexes = [];

        foreach ($fields as $field)
        {
            if (empty($field['name']) || empty($field['type']))
            {
                throw new Exception("Invalid field structure: missing name or type.");
            }

            $fieldName = $field['name'];
            $quotedName = "`$fieldName`";
            $def = "{$quotedName} {$field['type']}";

            // Handle Constraints (NOT NULL, AUTO_INCREMENT, etc.)
            if (!empty($field['constraints']) && is_array($field['constraints']))
            {
                // Filter out keywords that shouldn't be inline (PRIMARY KEY or FOREIGN KEY)
                $validConstraints = array_filter($field['constraints'], function ($c)
                {
                    $cUpper = strtoupper($c);
                    return stripos($cUpper, 'FOREIGN KEY') === false && stripos($cUpper, 'PRIMARY KEY') === false;
                });

                if (!empty($validConstraints))
                {
                    $def .= ' ' . implode(' ', $validConstraints);
                }
            }

            $fieldDefinitions[] = $def;

            // Collect Primary Keys
            if (!empty($field['is_primary']))
            {
                $pk_cols[] = $fieldName;
            }

            // Simple Indexes
            if (!empty($field['is_index']))
            {
                $simpleIndexes[] = "INDEX ({$quotedName})";
            }

            // Collect Foreign Keys
            if (!empty($field['is_fk']) && !empty($field['references']))
            {
                $foreignKeys[] = "FOREIGN KEY ({$quotedName}) REFERENCES {$field['references']} ON DELETE CASCADE ON UPDATE CASCADE";
            }
        }

        // Append Primary Key
        if (!empty($pk_cols))
        {
            $pk_string = implode(', ', array_map(fn($col) => "`$col`", $pk_cols));
            $fieldDefinitions[] = "PRIMARY KEY ($pk_string)";
        }

        // Append Unique Keys
        if (!empty($unique_keys))
        {
            foreach ($unique_keys as $uk_group)
            {
                $uk_string = implode(', ', array_map(fn($col) => "`$col`", $uk_group));
                $fieldDefinitions[] = "UNIQUE KEY ($uk_string)";
            }
        }

        // Append Simple Indexes
        if (!empty($simpleIndexes))
        {
            $fieldDefinitions = array_merge($fieldDefinitions, $simpleIndexes);
        }

        // Fulltext Keys Indexes
        if (!empty($fulltext_keys))
        {
            $ft_string = implode(', ', array_map(fn($col) => "`$col`", $fulltext_keys));
            $fieldDefinitions[] = "FULLTEXT KEY ($ft_string)";
        }

        // Append Foreign Keys
        if (!empty($foreignKeys))
        {
            $fieldDefinitions = array_merge($fieldDefinitions, $foreignKeys);
        }

        // Construct and execute the final SQL
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (" .
            implode(', ', $fieldDefinitions) .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->query($sql);
        return true;
    }

    /**
     * Bulk or single record insertion.
     * @param string $table The table.
     * @param array $data Array of records or a single record.
     * @return array Records ids inserted.
     */
    public function insert(string $table, array $data): array
    {
        if (empty($data)) return [];

        // Convert to 2D array if it's a single record
        $isSingleRecord = !is_array(reset($data));
        $records = $isSingleRecord ? [$data] : $data;

        $insertedIds = [];
        $this->pdo->beginTransaction();

        try
        {
            foreach ($records as $record)
            {
                if (empty($record)) continue;

                $columns = array_keys($record);
                $safeTable = "`" . str_replace("`", "``", $table) . "`";
                $safeColumns = array_map(fn($c) => "`" . str_replace("`", "``", $c) . "`", $columns);
                $placeholders = array_fill(0, count($columns), "?");

                $sql = "INSERT INTO $safeTable (" . implode(", ", $safeColumns) . ") VALUES (" . implode(", ", $placeholders) . ")";

                $this->query($sql, array_values($record));

                // Store the ID for each record separately
                $insertedIds[] = $this->pdo->lastInsertId();
            }

            $this->pdo->commit();
            // Returns an array (e.g., [45] or [45, 46, 47]) of inserted record IDs, even for single record insertion.
            return $insertedIds;
        }
        catch (Exception $e)
        {
            if ($this->pdo->inTransaction())
            {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Smart Update: Supports both positional (?) and named (:id) placeholders.
     * @param string $table The table name.
     * @param array $data Associative array [column => value] for SET.
     * @param string $where SQL WHERE clause.
     * @param array $params Values for the WHERE clause placeholders.
     * @return int Number of affected rows.
     */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        if (empty($data) || empty($where))
        {
            throw new Exception("Update failed: Data and Where clause cannot be empty.");
        }

        $setParts = [];
        $setValues = [];

        // Check if $params is associative (named placeholders) or indexed (positional placeholders)
        $isNamed = !empty($params) && (array_keys($params) !== range(0, count($params) - 1));

        // Build SET clause and values for the UPDATE statement
        foreach ($data as $column => $value)
        {
            if ($isNamed)
            {
                // Use set_ prefix to avoid collision with placeholders in the WHERE clause
                $placeholder = "set_" . str_replace([' ', '`', '.'], '_', $column);
                $setParts[] = "`" . str_replace("`", "``", $column) . "` = :$placeholder";
                $setValues[$placeholder] = $value;
            }
            else
            {
                // Positional placeholders
                $setParts[] = "`" . str_replace("`", "``", $column) . "` = ?";
                $setValues[] = $value;
            }
        }

        // Merge the SET values and WHERE parameters, 
        // set values first to ensure correct order for positional placeholders.
        $finalParams = array_merge($setValues, $params);

        $sql = "UPDATE `" . str_replace("`", "``", $table) . "` 
                SET " . implode(', ', $setParts) . " 
                WHERE " . $where;

        $this->query($sql, $finalParams);

        return $this->rowCount();
    }

    /**
     * Delete records based on a custom WHERE string.
     * @param string $table The table name.
     * @param string $where Custom WHERE clause string (e.g., "id = 5" or "id IN (1,2,3)").
     * @param array $params Optional parameters for prepared statement placeholders.
     * @return int Number of affected rows.
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        if (empty($where))
        {
            throw new Exception("Update failed: Where clause cannot be empty.");
        }

        $safeTable = "`" . str_replace("`", "``", $table) . "`";
        $sql = "DELETE FROM $safeTable WHERE $where";

        $this->query($sql, $params);

        return $this->rowCount();
    }

    /**
     * Helper to prepare an IN clause safely.
     * @param array $values The array of values for the IN clause.
     * @param string $prefix Optional prefix for named placeholders (e.g., 'id' to generate :id_0, :id_1, etc.).
     * @return array Contains 'placeholders' string and 'params' array.
     */
    public function prepareIn(array $values, string $prefix = ''): array
    {
        $params = [];
        $placeholderArray = [];
        $values = array_values($values);

        foreach ($values as $index => $value)
        {
            if (!empty($prefix))
            {
                // Named placeholders
                $name = ":" . $prefix . "_" . $index;
                $placeholderArray[] = $name;
                $params[$name] = $value;
            }
            else
            {
                // Standard positional placeholders
                $placeholderArray[] = '?';
                $params[] = $value;
            }
        }

        return [
            'placeholders' => implode(',', $placeholderArray),
            'params' => $params
        ];
    }

    /**
     * Returns the number of affected rows from the last executed statement.
     * @return int Number of affected rows.
     */
    public function rowCount(): int
    {
        return $this->statement ? $this->statement->rowCount() : 0;
    }

    /**
     * Returns the ID of the last inserted row.
     * @return string|false The last insert ID or false on failure.
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Fetches all results from the last executed statement.
     * @return array Array of results.
     */
    public function fetchAll(): array
    {
        return $this->statement ? $this->statement->fetchAll() : [];
    }

    /**
     * Fetches a single result from the last executed statement.
     * @return mixed Single result or null if no result.
     */
    public function fetch(): mixed
    {
        return $this->statement ? $this->statement->fetch() : null;
    }
}
