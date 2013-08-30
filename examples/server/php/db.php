<?php
    
    Class Database {
        
        private $db;
        private $config;
        
        private $error_mode = PDO::ERRMODE_EXCEPTION;
        private $fetch_type = PDO::FETCH_ASSOC;
        
        public function __construct($config = null) {
            $this->config = isset($config) ? $config : new Config();
        }
        
        public function connect() {
            if (!isset($this->db)) {
                $cfg = $this->config;
                $this->db = new PDO('mysql:host='.$cfg->host.';dbname='.$cfg->dbname, $cfg->username, $cfg->password);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, $this->error_mode);
            }
            return $this->db;
        }
        
        public function select($table, $id = null, $id_col = null) {
            $sql = 'SELECT * FROM '.$table;
            $id_col = isset($id_col) ? $id_col : 'id';
            
            $this->connect();
            
            if (isset($id)) {
                $query = $this->db->prepare($sql.' WHERE '.$id_col.' = :id');
                $query->bindParam(':id', $id);
                $query->execute();
            }
            else {
                $query = $this->db->prepare($sql);
                $query->execute();
            }
            $result = $query->fetchAll($this->fetch_type);
            
            return $result;
        }
        
        public function query($table, array $values) {
            $sql = 'SELECT * FROM '.$table;
            $param_mappings = array();
            $col_mappings = array();
            $count = 1;
            
            foreach ($values as $col => $value) {
                // $param is auto-numbered rather than named to support querying
                // multiple times on the same column easily
                $param = ':'.$count++;
                
                if (is_array($value)) {
                    $col = isset($value['column']) ? $value['column'] : $col;
                    $param_mappings[$param] = $value['value'];
                    $comparator = isset($value['comparator']) ? $value['comparator'] : '=';
                    $conjunction = isset($value['conjunction']) ? $value['conjunction'] : 'AND';
                }
                else {
                    $param_mappings[$param] = $value;
                    $comparator = '=';
                    $conjunction = 'AND';
                }
                if (count($col_mappings) === 0) {
                    $conjunction = '';
                }
                if (strtoupper($comparator) === 'LIKE') {
                    // Enable partial matching:
                    $param_mappings[$param] = '%'.$param_mappings[$param].'%';
                }
                array_push($col_mappings, $conjunction.' '.$col.' '.$comparator.' '.$param);
            }
            
            if (count($col_mappings) > 0) {
                $param_list = implode(' ', $col_mappings);
                $sql = $sql.' WHERE '.$param_list;
            }
            
            return $this->querySql($sql, $param_mappings);
        }
        
        public function querySql($sql, array $params) {
            $this->connect();
            $query = $this->db->prepare($sql);
            $query->execute($params);
            $result = $query->fetchAll($this->fetch_type);
            
            return $result;
        }
        
        public function execSql($sql, array $params) {
            $this->connect();
            $query = $this->db->prepare($sql);
            return $query->execute($params);
        }
        
        private function save($action, $table, array $values) {
            /*
            $param_names = array();
            $param_mappings = array();
            $col_mappings = array();

            foreach ($values as $col => $value) {
                $param = ':'.$col;
                array_push($param_names, $param);
                $param_mappings[$param] = $value;
                array_push($col_mappings, $col.'='.$param);
            }

            if ($action === 'INSERT') {
                $cols = implode(',', array_keys($values));
                $param_list = implode(',', $param_names);
                $sql = 'INSERT INTO '.$table.' ('.$cols.') VALUES ('.$param_list.')';
            }
            else {
                $cols = implode(',', $col_mappings);
                $id = $values['id'];
                $sql = 'UPDATE '.$table.' SET '.$cols.' WHERE id = '.$id;
            }

            $this->connect();
            $query = $this->db->prepare($sql);
            $query->execute($param_mappings);
            $id = $action == 'INSERT' ? $this->db->lastInsertId() : $id;
            $result = $this->select($table, $id);

            return $result;
            */

            $this->connect();
            if ($action === 'INSERT') {
                $sql = 'INSERT INTO events (calendar_id,title,start,end,location,notes,url,all_day,reminder,app_id,rrule,duration) ' .
                    'VALUES (:calendar_id,:title,:start,:end,:location,:notes,:url,:all_day,:reminder,:app_id,:rrule,:duration)';
                $query = $this->db->prepare($sql);
                $query->bindParam(':calendar_id', $values['calendar_id'], PDO::PARAM_INT);
                $query->bindParam(':title', $values['title'], PDO::PARAM_STR);
                $startDt = gmdate("Y-m-d H:i:s", strtotime(substr($values['start'], 0, 19)));
                $query->bindParam(':start', $startDt, PDO::PARAM_STR);
                $endDt = gmdate("Y-m-d H:i:s", strtotime(substr($values['end'], 0, 19)));
                $query->bindParam(':end', $endDt, PDO::PARAM_STR);
                $query->bindParam(':location', $values['location'], PDO::PARAM_STR);
                $query->bindParam(':notes', $values['notes'], PDO::PARAM_STR);
                $query->bindParam(':url', $values['url'], PDO::PARAM_STR);
                $query->bindParam(':all_day', $values['all_day'], PDO::PARAM_INT);
                $query->bindParam(':reminder', $values['reminder'], PDO::PARAM_STR);
                $query->bindParam(':app_id', $values['app_id'], PDO::PARAM_STR);
                $query->bindParam(':rrule', $values['rrule'], PDO::PARAM_STR);
                $query->bindParam(':duration', $values['duration'], PDO::PARAM_INT);
            }
            else {
                $id = $values['id'];
                $sql = 'UPDATE events SET id=:id,calendar_id=:calendar_id,title=:title,start=:start,end=:end,location=:location,notes=:notes,url=:url,all_day=:all_day,reminder=:reminder,app_id=:app_id,rrule=:rrule,duration=:duration ' .
                    'WHERE id = :id';
                $query = $this->db->prepare($sql);
                $query->bindParam(':id', $values['id'], PDO::PARAM_STR);
                $query->bindParam(':calendar_id', $values['calendar_id'], PDO::PARAM_INT);
                $query->bindParam(':title', $values['title'], PDO::PARAM_STR);
                $startDt = gmdate("Y-m-d H:i:s", strtotime(substr($values['start'], 0, 19)));
                $query->bindParam(':start', $startDt, PDO::PARAM_STR);
                $endDt = gmdate("Y-m-d H:i:s", strtotime(substr($values['end'], 0, 19)));
                $query->bindParam(':end', $endDt, PDO::PARAM_STR);
                $query->bindParam(':location', $values['location'], PDO::PARAM_STR);
                $query->bindParam(':notes', $values['notes'], PDO::PARAM_STR);
                $query->bindParam(':url', $values['url'], PDO::PARAM_STR);
                $query->bindParam(':all_day', $values['all_day'], PDO::PARAM_INT);
                $query->bindParam(':reminder', $values['reminder'], PDO::PARAM_STR);
                $query->bindParam(':app_id', $values['app_id'], PDO::PARAM_STR);
                $query->bindParam(':rrule', $values['rrule'], PDO::PARAM_STR);
                $query->bindParam(':duration', $values['duration'], PDO::PARAM_INT);
            }
            
            $query->execute();
            $id = $action == 'INSERT' ? $this->db->lastInsertId() : $id;
            $result = $this->select($table, $id);
            
            return $result;
        }
        
        public function insert($table, array $values) {
            return $this->save('INSERT', $table, $values);
        }
        
        public function update($table, array $values) {
            return $this->save('UPDATE', $table, $values);
        }
        
        public function delete($table, $id, $id_col = null) {
            $id_col = isset($id_col) ? $id_col : 'id';
            $sql = 'DELETE FROM '.$table.' WHERE '.$id_col.' = :id';
            
            $this->connect();
            $query = $this->db->prepare($sql);
            $query->bindParam(':id', $id);
            $query->execute();
            $result = $query->rowCount();
            
            return $result;
        }
    }

?>