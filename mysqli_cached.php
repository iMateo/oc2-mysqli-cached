<?php
namespace DB;
final class MySQLi_Cached {
	private $link;
    private $cache;
    private $cachedquery;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
        $this->cache = new Cache(DB_CACHED_EXPIRE);
		$this->link = new \mysqli($hostname, $username, $password, $database, $port);
		if ($this->link->connect_error) {
			trigger_error('Error: Could not make a database link (' . $this->link->connect_errno . ') ' . $this->link->connect_error);
			exit();
		}

		$this->link->set_charset("utf8");
		$this->link->query("SET SQL_MODE = ''");
        $this->link->query("SET NAMES 'utf-8");
        $this->link->query("SET CHARACTER_SET_CONNECTION=utf8");

	}
    
	public function query($sql) {
        // Only SELECT query
        // COMMENTS HERE
        // COMMENTS HERE
        // COMMENTS HERE








        $isselect = 0;
        $md5query = '';
        $pos = stripos($sql, 'select ');
        if ($pos == 0)
        {
            $isselect = 1;
            $md5query = md5($sql);
            if ($query = $this->cache->get('sql_' . $md5query)) {
                if ($query->sql == $sql) {
                    if ($resetflag = $this->cache->get('sql_globalresetcache')) {
                        if ($resetflag <= $query->time) {
                            $this->cachedquery = $query;
                            return($query);
                        }
                       else {
                           $this->cachedquery = $query;
                           return($query);
                            
                        }                        
                    }
                }
            }
            $resource = $this->link->query($sql);
            if ($resource) {
                if (is_resource($resource)) {
                    $i = 0;
                    $data = array;
                    while ($result = $query->fetch_accoc($resource)) {
                        $data[$i] = $result;
                        $i++;
                    }
                    
                }
            }
        }














		$query = $this->link->query($sql);

		if (!$this->link->errno) {
			if ($query instanceof \mysqli_result) {
				$data = array();






				while ($row = $query->fetch_assoc()) {
					$data[] = $row;
				}

				$result = new \stdClass();
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();















				$result->rows = $data;

				$query->close();

				return $result;
			} else {
				return true;
			}
		} else {
			trigger_error('Error: ' . $this->link->error  . '<br />Error No: ' . $this->link->errno . '<br />' . $sql);


		}
	}

	public function escape($value) {
		return $this->link->real_escape_string($value);

	}

	public function countAffected() {
        if(isset($this->cachedquery) && $this->cachedquery)
        {
            return $this->cachedquery->num_rows;
        }
        else {
            return $this->link->affected_rows;    
        }


		






	}

	public function getLastId() {


		return $this->link->insert_id;
	}

	public function __destruct() {


		$this->link->close();
	}
}