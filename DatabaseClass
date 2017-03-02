<?php

namespace erp_mvc\libs {

    class Database extends \PDO {

        /**
         * Asegura que solo se mantenga una conexion por sesion a las bases de datos.
         * @var Array 
         */
        private static $selfInstance;

        /**
         *
         * @var int Fetch assoc by default
         */
        public $fetch_mode;

        /**
         *
         * @var String class to fetch;
         */
        public $fetch_cls;

        /**
         *
         * @var String current Qeury;
         */
        static $currentQuery = "";

        /**
         * Verify if Database has errors trying to connect
         * @var Boolean 
         */
        public $databaseError = false;

        function __construct($DB_TYPE = null, $DB_HOST = null, $DB_NAME = null, $DB_USER = null, $DB_PASS = null) {
            try {
                if ($DB_HOST) {
                    if (DB_TYPE_SUCURSAL == $DB_TYPE) {
                        parent::__construct($DB_TYPE . ':host=' . $DB_HOST . ';dbname=' . $DB_NAME, $DB_USER, $DB_PASS);
                    } else {
                        parent::__construct($DB_TYPE . ':host=' . $DB_HOST . ';dbname=' . $DB_NAME, $DB_USER, $DB_PASS, array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
                    }
                } else {
                    parent::__construct(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
                }
                $this->fetch_mode = \PDO::FETCH_ASSOC;
            } catch (\Exception $ex) {
                $this->databaseError = true;
            }
        }

        /**         *
         * insert
         * @param string $table name of table to insert into
         * @param array $data An associative array 
         */
        public function insert($table, $data) {
            ksort($data);
            $fieldNames = implode('`, `', array_keys($data));
            $fieldValues = ':' . implode(', :', array_keys($data));
            $sth = $this->prepare("INSERT INTO $table(`$fieldNames`) VALUES($fieldValues)");
            foreach ($data as $key => $value) {
                if ($value != 0 && (empty($value) || is_null($value))) {
                    $sth->bindValue(":$key", null, \PDO::PARAM_NULL);
                } else {
                    $sth->bindValue(":$key", $value);
                }
            }
            $sth->execute();
            return $this->lastInsertId();
        }

        /*         * *
         * update
         * @param string $table name of table to insert into
         * @param array $data An associative array 
         * @param array $where the Where Query part
         */

        public function update($table, $data, $where) {
            ksort($data);
            $fieldDetails = null;
            foreach ($data as $key => $value) {
                $fieldDetails .= "$key=:$key,";
            }
            $fieldDetails = rtrim($fieldDetails, ',');
            $sth = $this->prepare("UPDATE $table SET $fieldDetails WHERE $where");
            foreach ($data as $key => $value) {
                if (empty($value) || is_null($value)) {
                    $sth->bindValue(":$key", null, \PDO::PARAM_NULL);
                } else {
                    $sth->bindValue(":$key", $value);
                }
            }
            $sth->execute();
        }

        /**
         * update
         * @param string $table name of table to delete from
         * @param array $where the Where Query part
         */
        public function delete($table, $where) {
            $sth = $this->prepare("delete from $table WHERE $where");
            $sth->execute();
        }

        public function select($table, $colums, $where = null, $order = null) {
            if (is_array($colums)) {
                $cols = '`' . implode("`,`", $colums) . '`';
            } else {
                $cols = $colums;
            }
            $sth = $this->prepare("SELECT $cols FROM $table $where $order");
            if ($this->fetch_mode == \PDO::FETCH_CLASS) {
                $sth->setFetchMode($this->fetch_mode, $this->fetch_cls);
            } else {
                $sth->setFetchMode($this->fetch_mode);
            }
            $sth->execute();
            return $sth->fetchAll();
        }

        public function ExcecuteStoredProcedure($name, $params = null) {
            if (is_array($params)) {
                $params = implode("','", $params);
            }
            $sth = ($params != null) ? $this->prepare("CALL $name('$params')") : $this->prepare("CALL $name()");
            if ($this->fetch_mode == \PDO::FETCH_CLASS) {
                $sth->setFetchMode($this->fetch_mode, $this->fetch_cls);
            } else {
                $sth->setFetchMode($this->fetch_mode);
            }
            $sth->execute();
            return $sth->fetchAll();
        }

        public function validateSPosIds2($params, $is_special = 0) {
            $_params = array($params, $is_special);
            $result = $this->ExcecuteStoredProcedure('get_ids_spos', $_params);
            $newRes = array();
            foreach ($result as $value) {
                $newRes[$value['id']] = $value['item_id'];
            }
            return $newRes;
        }

        public function SaveLogs($tblName, $action, $message, $user, $custom1 = NULL, $custom2 = NULL, $custom3 = NULL) {
            $params = array(
                'table_name' => $tblName,
                'action' => $action,
                'message' => $message,
                'user' => $user,
                'custom1' => $custom1,
                'custom2' => $custom2,
                'custom3' => $custom3
            );
            return $this->ExcecuteStoredProcedure('sp_save_log', $params);
        }

        /*         * *Override prepare-function to save all user interactions in a mysql log table** */

        public function prepare($statement, $driver_options = array()) {
            $lstatement = strtolower($statement);
            if ((strpos($lstatement, 'update ') !== false || strpos($lstatement, 'delete ') !== false || strpos($lstatement, 'insert ') !== false) && (strpos($lstatement, 'call ') === false)) {
                $aStatement = explode(' ', $lstatement);
                $user = $_SESSION['user_id'];
                $table = '';
                switch ($aStatement[0]) {
                    case 'insert':
                    case 'delete':
                        $table = $aStatement[2];
                        break;
                    case 'update':
                        $table = $aStatement[1];
                        break;
                }
                if (self::$currentQuery != $lstatement) {
                    self::$currentQuery = $lstatement;
                    $this->SaveLogs($table, $aStatement[0], 'erp_logs', $user, $statement);
                }
            }
            return parent::prepare($statement, $driver_options);
        }

        public function GetMinMaxOrderID($store, $date1, $date2 = null) {
            if ($date2 == null) {
                $date1 = (strpos($date1, 'now()') !== false) ? $date1 : "'$date1'";
                $sql = "select min_order_id, max_order_id, ifnull(void_ids, 0) into @min, @max, @voids from (select min(order_id) min_order_id, max(order_id) max_order_id from psorder 
                            where storeid = $store and bizdatesale = date($date1)) m, 
                        (select group_concat(distinct order_id) void_ids from psorder where storeid = $store and bizdatesale = date($date1) and bizdatevoid is not null) v;";
            } else {
                $date1 = (strpos($date1, 'now()') !== false ) ? $date1 : "'$date1'";
                $date2 = (strpos($date2, 'now()') !== false) ? $date2 : "'$date2'";
                $sql = "select min_order_id, max_order_id, ifnull(void_ids, 0) into @min, @max, @voids from (select min(order_id) min_order_id, max(order_id) max_order_id from psorder 
                            where storeid = $store and bizdatesale >= date($date1) and bizdatesale <= date($date2)) m, 
                        (select group_concat(distinct order_id) void_ids from psorder where storeid = $store and bizdatesale >= date($date1) and bizdatesale <= date($date2) and bizdatevoid is not null) v";
            }
            //echo $sql;
            $stm = $this->prepare($sql);
            $stm->execute();
            $stm->closeCursor();
            $stm = $this->prepare('select @min, @voids');
            $stm->execute();
            $result = $stm->fetch(\PDO::FETCH_ASSOC);
            return array_pop($result);
        }

    }

}
