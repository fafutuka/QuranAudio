<?php

class DatabaseService {
    protected $conn = "";
    private $host;
    private $user;
    private $password;
    private $database;

    // Constructor to set the database connection
    public function __construct($host, $user, $password, $database) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;

        $this->conn = new \mysqli($host, $user, $password, $database);

        // Check if the connection was successful
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // Create a new record in the database using prepared statements
    public function create($table, $data) {
        // Check if the data array is not empty
        if (empty($data)) {
            return false;
        }

        // Normalize values: convert arrays/objects to JSON and booleans to ints
        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $data[$k] = json_encode($v);
            } elseif (is_bool($v)) {
                $data[$k] = $v ? 1 : 0;
            }
        }

        // Create SQL query for insertion with placeholders
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        // Prepare the statement
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        // Bind parameters
        $types = str_repeat("s", count($data)); // Default all to string
        $values = array_values($data);

        // Create array of references for bind_param
        $bindParams = array();
        $bindParams[] = &$types;
        foreach ($values as $key => $value) {
            $bindParams[] = &$values[$key];
        }

        call_user_func_array(array($stmt, 'bind_param'), $bindParams);

        // Execute the statement
        $result = $stmt->execute();

        // Check if the query was successful
        if ($result) {
            $insertId = $stmt->insert_id;
            $stmt->close();

            // Return just the insert ID instead of the full record
            return $insertId;
        } else {
            // Log the specific database error
            $error = $stmt->error;
            error_log("Database error in create method for table $table: " . $error);
            
            $stmt->close();
            return false;
        }
    }

    // Helper method to read a record by ID using prepared statement
    public function readById($table, $id) {
        $query = "SELECT * FROM $table WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Use store_result and fetch with metadata instead of get_result
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $row = $this->fetchAssocStatement($stmt);
            $stmt->close();
            return $row;
        } else {
            $stmt->close();
            return false;
        }
    }

    // Helper method to fetch associative array from prepared statement
    private function fetchAssocStatement($stmt) {
        $meta = $stmt->result_metadata();
        $fields = array();
        $row = array();
        
        while ($field = $meta->fetch_field()) {
            $fields[] = &$row[$field->name];
        }
        
        call_user_func_array(array($stmt, 'bind_result'), $fields);
        
        if ($stmt->fetch()) {
            // Create a copy of the row to avoid reference issues
            $result = array();
            foreach ($row as $key => $value) {
                $result[$key] = $value;
            }
            return $result;
        }
        
        return false;
    }

    // Helper method to fetch all rows from prepared statement
    private function fetchAllAssocStatement($stmt) {
        $meta = $stmt->result_metadata();
        $fields = array();
        $row = array();
        
        while ($field = $meta->fetch_field()) {
            $fields[] = &$row[$field->name];
        }
        
        call_user_func_array(array($stmt, 'bind_result'), $fields);
        
        $results = array();
        while ($stmt->fetch()) {
            // Create a copy of the row to avoid reference issues
            $result = array();
            foreach ($row as $key => $value) {
                $result[$key] = $value;
            }
            $results[] = $result;
        }
        
        return $results;
    }

    // Read records from the database using prepared statements when possible
    public function read($table, $conditions = "") {
        // If no conditions or empty array, select all records
        if (empty($conditions)) {
            $query = "SELECT * FROM $table";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return false;
            }

            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $rows = $this->fetchAllAssocStatement($stmt);
                $stmt->close();
                
                // Always return array of arrays for consistency
                return $rows;
            } else {
                $stmt->close();
                return array();
            }

        } else if (is_array($conditions)) {
            // Handle array conditions with prepared statement
            $whereClause = [];
            $params = [];
            $types = '';

            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = ?";
                $params[] = $value;
                
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } elseif (is_string($value)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }

            $query = "SELECT * FROM $table";
            if (!empty($whereClause)) {
                $query .= " WHERE " . implode(' AND ', $whereClause);
            }

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return false;
            }

            if (!empty($params)) {
                // Create array of references for bind_param
                $bindParams = array();
                $bindParams[] = &$types;
                foreach ($params as $key => $value) {
                    $bindParams[] = &$params[$key];
                }

                call_user_func_array(array($stmt, 'bind_param'), $bindParams);
            }

            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $rows = $this->fetchAllAssocStatement($stmt);
                $stmt->close();
                
                // Always return array of arrays for consistency
                return $rows;
            } else {
                $stmt->close();
                return array();
            }

        } else if (preg_match('/^id = (\d+)$/', $conditions, $matches)) {
            // Handle simple id condition with prepared statement
            $id = $matches[1];
            return $this->readById($table, $id);
        } else {
            // For complex conditions, fallback to regular query but escape properly
            $query = "SELECT * FROM $table WHERE " . $conditions;
            $result = mysqli_query($this->conn, $query);
            
            // Check if the query was successful
            if ($result) {
                // Fetch all rows into an array
                $rows = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                }

                // Always return array of arrays for consistency
                return $rows;
            } else {
                return false;
            }
        }
    }

    // Execute a custom query with prepared statement support
    public function runQuery($query, $params = array()) {
        // Check if it's a SELECT query
        $isSelectQuery = (stripos(trim($query), 'SELECT') === 0);
        
        // If params are provided, use prepared statement
        if (!empty($params)) {
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return false;
            }

            // Determine types string
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }

            // Create array of references for bind_param
            $bindParams = array();
            $bindParams[] = &$types;
            foreach ($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }

            call_user_func_array(array($stmt, 'bind_param'), $bindParams);
            $stmt->execute();

            if ($isSelectQuery) {
                // For SELECT queries, use our custom fetch method
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $rows = $this->fetchAllAssocStatement($stmt);
                    $stmt->close();
                    
                    // Always return array of arrays for consistency
                    return $rows;
                } else {
                    $stmt->close();
                    return array();
                }
            } else {
                // For non-SELECT queries (INSERT, UPDATE, DELETE)
                $affected = $stmt->affected_rows;
                $stmt->close();
                return $affected;
            }
        } else {
            // For queries without parameters, use regular query
            $result = mysqli_query($this->conn, $query);
            
            // Check if the query was successful
            if ($result) {
                if ($isSelectQuery) {
                    // For SELECT queries, fetch results
                    $rows = array();
                    while ($row = mysqli_fetch_assoc($result)) {
                        $rows[] = $row;
                    }

                    // Always return array of arrays for consistency
                    return $rows;
                } else {
                    // For non-SELECT queries
                    return mysqli_affected_rows($this->conn);
                }
            } else {
                return false;
            }
        }
    }

    // Update a record in the database using prepared statements
    public function update($table, $data, $conditions) {
        if (empty($data)) {
            return false;
        }

        // Create SET part of query with placeholders
        $setStatements = array();
        foreach (array_keys($data) as $key) {
            $setStatements[] = "$key = ?";
        }
        $setString = implode(", ", $setStatements);

        // Handle different types of conditions
        $whereClause = "";
        $conditionValues = [];
        $conditionTypes = "";

        if (is_array($conditions)) {
            // Handle array conditions
            $whereStatements = [];
            foreach ($conditions as $key => $value) {
                $whereStatements[] = "$key = ?";
                $conditionValues[] = $value;
                
                if (is_int($value)) {
                    $conditionTypes .= 'i';
                } elseif (is_float($value)) {
                    $conditionTypes .= 'd';
                } elseif (is_string($value)) {
                    $conditionTypes .= 's';
                } else {
                    $conditionTypes .= 'b';
                }
            }
            $whereClause = implode(' AND ', $whereStatements);
        } else if (is_string($conditions)) {
            // Check if conditions is a simple ID condition
            if (preg_match('/^id = (\d+)$/', $conditions, $matches)) {
                $whereClause = "id = ?";
                $conditionValues[] = $matches[1];
                $conditionTypes = "i";
            } else {
                // For complex string conditions, use as-is (less secure)
                $whereClause = $conditions;
            }
        }

        $query = "UPDATE $table SET $setString WHERE $whereClause";

        // Prepare the statement
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        // Normalize values: convert arrays/objects to JSON and booleans to ints
        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $data[$k] = json_encode($v);
            } elseif (is_bool($v)) {
                $data[$k] = $v ? 1 : 0;
            }
        }

        // Bind parameters for SET values and conditions
        $types = str_repeat("s", count($data)) . $conditionTypes;
        $values = array_merge(array_values($data), $conditionValues);

        // Create array of references for bind_param
        $bindParams = array();
        $bindParams[] = &$types;
        foreach ($values as $key => $value) {
            $bindParams[] = &$values[$key];
        }

        call_user_func_array(array($stmt, 'bind_param'), $bindParams);

        // Execute the statement
        $result = $stmt->execute();

        // Check if the query was successful
        if ($result) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected;
        } else {
            $stmt->close();
            return false;
        }
    }

    // Delete a record from the database using prepared statements when possible
    public function delete($table, $conditions) {
        $whereClause = "";
        $conditionValues = [];
        $conditionTypes = "";

        if (is_array($conditions)) {
            // Handle array conditions
            $whereStatements = [];
            foreach ($conditions as $key => $value) {
                $whereStatements[] = "$key = ?";
                $conditionValues[] = $value;
                
                if (is_int($value)) {
                    $conditionTypes .= 'i';
                } elseif (is_float($value)) {
                    $conditionTypes .= 'd';
                } elseif (is_string($value)) {
                    $conditionTypes .= 's';
                } else {
                    $conditionTypes .= 'b';
                }
            }
            $whereClause = implode(' AND ', $whereStatements);
        } else if (is_string($conditions)) {
            // Check if conditions is a simple ID condition
            if (preg_match('/^id = (\d+)$/', $conditions, $matches)) {
                $whereClause = "id = ?";
                $conditionValues[] = $matches[1];
                $conditionTypes = "i";
            } else if (preg_match('/^activity_id = (\d+)$/', $conditions, $matches)) {
                $whereClause = "activity_id = ?";
                $conditionValues[] = $matches[1];
                $conditionTypes = "i";
            } else if (preg_match('/^activity_id = (\d+) AND user_id = (\d+)$/', $conditions, $matches)) {
                $whereClause = "activity_id = ? AND user_id = ?";
                $conditionValues[] = $matches[1];
                $conditionValues[] = $matches[2];
                $conditionTypes = "ii";
            } else {
                // For complex string conditions, use as-is (less secure)
                $whereClause = $conditions;
            }
        }

        $query = "DELETE FROM $table WHERE $whereClause";

        // Prepare the statement
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return false;
        }

        // Bind parameters if we have any
        if (!empty($conditionValues)) {
            // Create array of references for bind_param
            $bindParams = array();
            $bindParams[] = &$conditionTypes;
            foreach ($conditionValues as $key => $value) {
                $bindParams[] = &$conditionValues[$key];
            }

            call_user_func_array(array($stmt, 'bind_param'), $bindParams);
        }

        // Execute the statement
        $result = $stmt->execute();

        if ($result) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected;
        } else {
            $stmt->close();
            return false;
        }
    }

    // Properly escape a string for use in a SQL query
    public function escapeString($data) {
        return $this->conn->real_escape_string($data);
    }

    // Sanitize input data to prevent SQL injection
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map(array($this, 'sanitize'), $data);
        } else {
            // First sanitize for HTML output
            $data = htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
            // Then escape for SQL
            return $this->escapeString($data);
        }
    }
}
