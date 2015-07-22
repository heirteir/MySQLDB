<?php

    class DB
    {

        //init variables
        public $db_name = '';
        public $user = '';
        public $pass = '';
        public $host = 'localhost';
        public $port = NULL;
        public $encoding = 'latin1';
        public $connect = NULL;

        public function __construct($host = NULL, $user, $pass = NULL, $db_name, $port = NULL, $encoding = NULL)
        {
            if ($host === NULL) {
                $host = $this->host;
            }
            if ($pass === NULL) {
                $pass = '';
            }
            if ($encoding === NULL) {
                $encoding = $this->encoding;
            }

            $this->db_name = $db_name;
            $this->user = $user;
            $this->pass = $pass;
            $this->host = $host;
            $this->port = $port;
            $this->encoding = $encoding;

            $this->init_mysql_connect(TRUE);
        }

        private function init_mysql_connect($create_db = FALSE)
        {
            $this->connect = new mysqli($this->host, $this->user, $this->pass, NULL, $this->port);

            if ($this->connect->connect_errno > 0) {
                throw new DBMySQLException('Unable to connect to MySQL server reason: ' . $this->connect->connect_error, 1);
            }

            $this->connect->set_charset($this->encoding);

            if ($create_db) {
                $this->connect->query('CREATE DATABASE IF NOT EXISTS ' . $this->format_table_or_database_string($this->db_name));
            }

            $this->connect->select_db($this->db_name);
        }

        public function get_value($table_name, $column_id, $column_value, $needed_column)
        {
            $table_name = $this->format_table_or_database_string($table_name);
            $result = $this->connect->query($this->build_sanitize_query("SELECT $needed_column FROM $table_name WHERE $column_id = :$column_id:", array($column_id => $column_value)));
            return $result->fetch_assoc()[$needed_column];
        }

        public function get_row($table_name, $column_id, $column_value)
        {
            $table_name = $this->format_table_or_database_string($table_name);
            $result = $this->connect->query($this->build_sanitize_query("SELECT * FROM $table_name WHERE $column_id = :$column_id:", array($column_id => $column_value)));
            if ($result->num_rows < 1) {
                return NULL;
            } else {
                return $result->fetch_assoc();
            }
        }

        public function change_value($table_name, $column_id, $column_value, $new_column_value)
        {
            $table_name = $this->format_table_or_database_string($table_name);
            $new_column_value = $this->escape_string($new_column_value);
            $this->connect->query($this->build_sanitize_query("UPDATE $table_name SET $column_id = $new_column_value WHERE $column_id = :$column_id:", array($column_id => $column_value)));
        }

        public function delete_row($table_name, $column_id, $column_value, $limit = NULL)
        {
            $table_name = $this->format_table_or_database_string($table_name);
            $this->connect->query($this->build_sanitize_query("DELETE FROM $table_name WHERE $column_id = :$column_id:" . ($limit !== NULL ? " limit $limit" : NULL), array($column_id => $column_value)));
        }

        private function build_sanitize_query($query, $data)
        {
            if (!$this->is_assoc($data)) {
                throw new DBMySQLException('non-associative array passed for build_sanitize_query() : build_sanitize_query() only takes associative arrays', 2);
            }

            $data = $this->sanitize_array($data);

            foreach ($data as $k => $v) {
                $query = str_replace(":$k:", $v, $query);
            }

            return $query;
        }

        private function escape_string($string, $no_surround_quotes = FALSE)
        {
            if ($no_surround_quotes) {
                return $this->connect->real_escape_string(strval($string));
            } else {
                return '\'' . $this->connect->real_escape_string(strval($string)) . '\'';
            }
        }

        private function sanitize_data($data, $no_surround_quotes = FALSE)
        {
            if (is_object($data)) {
                return '';
            }

            if (is_null($data)) {
                return '';
            } else if (is_bool($data)) {
                return ($data ? 1 : 0);
            } else if (is_int($data)) {
                return $data;
            } else if (is_float($data)) {
                return $data;
            } else {
                return $this->escape_string($data, $no_surround_quotes);
            }
        }

        public function create_table($table_name, $data)
        {
            if (!is_array($data)) {
                throw new DBMySQLException('no array passed for create_table() : create_table() only takes arrays', 5);
            }
            if ($this->is_assoc($data)) {
                throw new DBMySQLException('associative array passed for create_table() : create_table() only takes sequential arrays', 3);
            }

            $data = $this->sanitize_array($data, TRUE);
            $qstring = 'CREATE TABLE IF NOT EXISTS ' . $this->format_table_or_database_string($table_name) . ' (';

            foreach ($data as $k) {
                if (end($data) === $k) {
                    $qstring .= "$k)";
                } else {
                    $qstring .= "$k, ";
                }
            }

            if (!$this->connect->query($qstring)) {
                throw new DBMySQLException("unable to create table '$table_name' reason: " . $this->connect->error, 4);
            }
        }

        public function custom_query($query, $data)
        {
            if (!is_array($data)) {
                return;
            } //Throw ERROR
            if (!$this->is_assoc($data)) {
                return;
            } //THROW ERROR


            return $this->connect->query($this->build_sanitize_query($query, $data));
        }

        public function known_custom_query($query)
        {
            return $this->connect->query($query);
        }

        public function value_exists($table_name, $column_id, $column_value)
        {
            $table_name = $this->format_table_or_database_string($table_name);
            if (!$result = $this->connect->query($this->build_sanitize_query("SELECT * FROM $table_name WHERE $column_id = :$column_id:", array($column_id => $column_value)))) {
                throw new DBMySQLException("failed to search for '$column_id' in table $table_name reason: " . $this->connect->error, 7);
            }
            return $result->num_rows > 0;
        }

        public function add_row($table_name, $data)
        {
            if (!$this->is_assoc($data)) {
                throw new DBMySQLException('non-associative array passed for add_row() : add_row() only takes associative arrays', 2);
            }

            $qstring = 'INSERT INTO ' . $this->format_table_or_database_string($table_name) . ' (';
            $vstring = ' VALUES (';

            foreach ($data as $k => $v) {
                if (end($data) === $v) {
                    $qstring .= "$k)";
                    $vstring .= ":$k:)";
                } else {
                    $qstring .= "$k, ";
                    $vstring .= ":$k:, ";
                }
            }

            if (!$this->connect->query($this->build_sanitize_query($qstring . $vstring, $data))) {
                throw new DBMySQLException("unable to add row to table '$table_name' reason: " . $this->connect->error, 8);
            }
        }

        private function sanitize_array($data, $no_surround_quotes = FALSE)
        {
            if (!$this->is_assoc($data)) {
                $return_array = array();
                foreach ($data as $v) {
                    if (is_array($v)) {
                        $return_array[] = $this->sanitize_array($v, $no_surround_quotes);
                        continue;
                    }

                    $return_array[] = $this->sanitize_data($v, $no_surround_quotes);
                }
                return $return_array;
            }

            $return_array = array();
            foreach ($data as $k => $v) {
                $return_array[strval($k)] = $this->sanitize_data($v, $no_surround_quotes);
            }

            return $return_array;
        }

        public function get_all_rows($table_name, $limit = NULL)
        {
            $table_name = $this->format_table_or_database_string($table_name);
            $result = $this->connect->query("SELECT * FROM $table_name" . ($limit !== NULL ? " limit $limit" : NULL));
            $assoc_array = array();
            while ($assoc = $result->fetch_assoc()) {
                $assoc_array[] = $assoc;
            }
            return $assoc_array;
        }

        private function format_table_or_database_string($data)
        {
            return '`' . str_replace('`', '``', trim($data, '`')) . '`';
        }

        function is_assoc(array $array)
        {
            return (bool)count(array_filter(array_keys($array), 'is_string')); //STACKOVERFLOW
        }

        public function close()
        {
            $this->connect->close();
        }
    }

    class DBMySQLException extends Exception
    {
        function __construct($message, $code)
        {
            parent::__construct($message, $code);
        }
    }
