<?php
namespace DB;
final class MySQL_Cached {
    
    
    private $link;
    private $cache;
    private $cachedquery;

    public function __construct($hostname, $username, $password, $database, $port = '3306') {
        
        $this->cache = new Cache(DB_CACHED_EXPIRE);
        if (!$this->link = mysql_pconnect($hostname, $username, $password)) {
              exit('Error: Could not make a database link using ' . $username . '@' . $hostname);
        }
        if (!mysql_select_db($database, $this->link)) {
              exit('Error: Could not connect to database ' . $database);
        }
        mysql_query("SET NAMES 'utf8'", $this->link);
        mysql_query("SET CHARACTER SET utf8", $this->link);
        mysql_query("SET CHARACTER_SET_link=utf8", $this->link);
        mysql_query("SET SQL_MODE = ''", $this->link);
      }









      public function query($sql) {
            // Кэшируем только SELECT запросы (ключ = md5hash всего запроса, в переменной сапоминаем точный текст запроса для точного сравнения)
            // При кэшировании результат последнего запроса запоминаем в $this->cachedquery (для функции countAffected)
            // При кэшировании запроса в запросе указываем время кэширования
            // В специальной глобальной переменной держим дату последнего сброса кэша. При этом если у извлеченного значения время записи меньше указанного времени зброса - кэшь считается неактуальным
            $isselect = 0;
            $md5query = '';
            $pos = stripos($sql, 'select ');
            if ($pos == 0)
            {
            $isselect = 1;
            // Это select
            $md5query = md5($sql);
            if ($query = $this->cache->get('sql_' . $md5query))
            {
                if ($query->sql == $sql)
                {
                // Проверяем флаг сброса
                if ($resetflag = $this->cache->get('sql_globalresetcache'))
                {
                    // Если время сброса раньше чем время текущего запроса - все нормально
                    if ($resetflag <= $query->time)
                    {
                    $this->cachedquery = $query;
                    return($query);
                    };
                }
                else
                {
                    $this->cachedquery = $query;
                    return($query);
                };
                };
            };
            };
            $resource = mysql_query($sql, $this->link);
            if ($resource) {
                if (is_resource($resource)) {
                    $i = 0;




                    $data = array();
                    while ($result = mysql_fetch_assoc($resource)) {
                        $data[$i] = $result;
                        $i++;
                    }
                    mysql_free_result($resource);





                    $query = new stdClass();

                    $query->row = isset($data[0]) ? $data[0] : array();
                    $query->rows = $data;
                    $query->num_rows = $i;


                    unset($data);
                    if ($isselect == 1)
                    {
                    $query->sql = $sql;
                    $query->time = time();
                    $this->cache->set('sql_' . $md5query, $query);
                    };
                    unset($this->cachedquery);
                    return $query;
                } else {
                    return TRUE;
                }
            } else {








            exit('Error: ' . mysql_error($this->link) . '<br />Error No: ' . mysql_errno($this->link) . '<br />' . $sql);
            }
      }




    public function escape($value) {
        return mysql_real_escape_string($value, $this->link);
    }


      public function countAffected() {
        if (isset($this->cachedquery) && $this->cachedquery)
        {
        return $this->cachedquery->num_rows;
        }
        else
        {
        return mysql_affected_rows($this->link);
        }
      }


      public function getLastId() {
        return mysql_insert_id($this->link);
      }



    public function __destruct() {
        mysql_close($this->link);
    }


}
