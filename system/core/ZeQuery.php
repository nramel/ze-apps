<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ZeQuery
{
    private $_db = null ;
    private $_dbPDO = null ;

    // for QueryBuilder
    private $_select = "SELECT *" ;
    private $_table = "" ;
    private $_join = "" ;
    private $_where = "" ;
    private $_group_by = "" ;
    private $_order_by = "" ;
    private $_query = "" ;
    private $_valueQuery = array() ;
    private $_insertFieldName = "" ;
    private $_insertValueField = "" ;
    private $_updateValueField = "" ;

    public function __construct()
    {
    }


    public function setDb($dbConfig = "default") {
        $this->_db = ZeDatabase::getInstance() ;
        $this->_dbPDO = $this->_db->open($dbConfig) ;
    }

    public function clearSql() {
        $this->_select = "SELECT *" ;
        $this->_table = "" ;
        $this->_join = "" ;
        $this->_where = "" ;
        $this->_group_by = "" ;
        $this->_order_by = "" ;
        $this->_query = "" ;
        $this->_insertFieldName = "" ;
        $this->_insertValueField = "" ;
        $this->_updateValueField = "" ;
        $this->_valueQuery = array() ;
    }

    public function insertNewField($key, $value) {
        if ($this->_insertFieldName != "") {
            $this->_insertFieldName .= ", " ;
            $this->_insertValueField .= ", " ;
        }

        $keyName = ":" . $key . count($this->_valueQuery) ;
        $this->_valueQuery[$keyName] = $value ;

        $this->_insertFieldName .= $key ;
        $this->_insertValueField .= $keyName ;
    }

    public function updateNewField($key, $value) {
        if ($this->_updateValueField != "") {
            $this->_updateValueField .= ", " ;
        }

        $keyName = ":" . $key . count($this->_valueQuery) ;
        $this->_valueQuery[$keyName] = $value ;

        $this->_updateValueField .= $key . " = " . $keyName ;
    }



    public function getColumnName() {
        $q = $this->_dbPDO->prepare("DESCRIBE " . $this->_table);
        $q->execute();
        return $q->fetchAll(PDO::FETCH_COLUMN);
    }


    public function getPrimaryKey() {
        $q = $this->_dbPDO->prepare("show columns from " . $this->_table . " WHERE `Key` = \"PRI\"");
        $q->execute();

        $rs = $q->fetchAll(PDO::FETCH_CLASS) ;

        if (isset($rs[0]->Field)) {
            return $rs[0]->Field ;
        } else {
            return null ;
        }
    }




    public function select($argString) {
        $this->_select = $argString ;

        return $this ;
    }

    public function table($argString) {
        $this->_table = $argString ;

        return $this ;
    }

    public function join($argString, $typeJoin = 'INNER') {
        $this->_join = $typeJoin. " " . $argString . " " ;

        return $this ;
    }

    public function where($arrData) {
        foreach ($arrData as $key => $value) {
            if ($this->_where != '') {
                $this->_where .= " AND " ;
            }
            $keyName = ":" . $key . count($this->_valueQuery) ;
            $keyName = str_replace(" ", "_", $keyName) ;
            $keyName = str_replace(">", "_", $keyName) ;
            $keyName = str_replace("<", "_", $keyName) ;


            if (!is_array($value)) {
                $this->_valueQuery[$keyName] = $value ;
            }


            if (is_array($value)) {
                $stringValue = "" ;
                foreach ($value as $value_content) {
                    if ($stringValue != '') {
                        $stringValue .= ", " ;
                    }
                    $stringValue .= "'" . $value_content . "'" ;
                }
                $this->_where .= $key . " IN (" . $stringValue . ") " ;
            } elseif (strpos($key, "<") || strpos($key, ">")) {
                $this->_where .= $key . " " . $keyName ;
            } else {
                $this->_where .= $key . " = " . $keyName ;
            }


        }

        return $this ;
    }

    public function group_by($argString) {
        $this->_group_by = $argString ;

        return $this ;
    }

    public function order_by($argString) {
        $this->_order_by = $argString ;

        return $this ;
    }


    public function query($argString) {
        $this->_query = $argString ;

        return $this ;
    }

    public function result() {
        if ($this->_query == '') {
            $this->_createQuery() ;
        }

        $sth = $this->_dbPDO->prepare($this->_query);
        $sth->execute($this->_valueQuery);

        // clean SQL Query
        $this->clearSql();

        // return fetched objects
        return $sth->fetchAll(PDO::FETCH_CLASS) ;
    }




    public function create() {
        $this->_createInsertQuery() ;
        $sth = $this->_dbPDO->prepare($this->_query);

        $sth->execute($this->_valueQuery);
    }

    public function update() {
        $this->_createUpdateQuery() ;
        $sth = $this->_dbPDO->prepare($this->_query);
        $sth->execute($this->_valueQuery);
    }

    public function delete($arrData) {
        $this->where($arrData) ;
        $this->_deleteQuery() ;
        $sth = $this->_dbPDO->prepare($this->_query);
        $sth->execute($this->_valueQuery);
    }






    private function _createQuery() {
        $this->_query = $this->_select . " FROM " . $this->_table . " "  ;

        if ($this->_join != '') {
            $this->_query .= $this->_join . " " ;
        }

        if ($this->_where != '') {
            $this->_query .= "WHERE " . $this->_where . " " ;
        }

        if ($this->_group_by != '') {
            $this->_query .= $this->_group_by . " " ;
        }

        if ($this->_order_by != '') {
            $this->_query .= $this->_order_by . " " ;
        }
    }



    private function _createInsertQuery() {
        $this->_query = "INSERT INTO " . $this->_table . " "  ;

        $this->_query .= " (" . $this->_insertFieldName . ") " ;
        $this->_query .= " VALUES (" . $this->_insertValueField . ") " ;
    }

    private function _createUpdateQuery() {
        $this->_query = "UPDATE " . $this->_table . " "  ;

        $this->_query .= "SET " . $this->_updateValueField . " " ;

        if ($this->_where != '') {
            $this->_query .= "WHERE " . $this->_where . " " ;
        }
    }

    private function _deleteQuery() {
        $this->_query = "DELETE FROM " . $this->_table . " "  ;

        if ($this->_where != '') {
            $this->_query .= "WHERE " . $this->_where . " " ;
        }
    }
}

