<?php

/*
    Database session handler
*/

class Session implements ArrayAccess {
    static protected $connection = null;
    
    static protected $__singelton__ = null;
    
    private $context = null;

    function offsetExists ($key)
    {
        if(empty($this->context))
        {
            return array_key_exists($key, $_SESSION);
            
        }
        else
        {
            return array_key_exists($key, $_SESSION[$this->context]);
        }
    }

    function offsetGet ($key)
    {
        if(empty($this->context))
        {
            if (array_key_exists($key, $_SESSION)) {
                return $_SESSION[$key];
            } else {
                return null;
            }
        }
        else
        {
            if (array_key_exists($key, $_SESSION[$this->context])) {
                return $_SESSION[$this->context][$key];
            } else {
                return null;
            }
        }
    }

    function offsetSet ($key, $value)
    {
        if(empty($this->context))
        {
            $_SESSION[$key] = $value;
        }
        else
        {
            $_SESSION[$this->context][$key] = $value;
        }
    }

    function offsetUnSet ($key)
    {
        if(empty($this->context))
        {
            $_SESSION[$key] = null;
            unset($_SESSION[$key]);
        }
        else
        {
            $_SESSION[$this->context][$key] = null;
            unset($_SESSION[$this->context][$key]);
        }
    }
    
    
    public function __get($name) 
    {
        if (self::$__singelton__ == null) {
            self::$__singelton__ = new Session();
        }
        
        self::$__singelton__->context = $name;
        
        return self::$__singelton__;

    }
    
    public function __set($name, $value) 
    {
        $_SESSION[$name] = $value;
    }
    
    public function __isset($name) 
    {
        return array_key_exists($name, $_SESSION);
    }
    
    public function __unset($name) 
    {
        $_SESSION[$name] = null;
        unset($_SESSION[$name]);
    }
    

    public static function remembered ()
    {
        return (isset($_COOKIE['remember']) && $_COOKIE['remember']);
    }

    public static function install ()
    {
        if (Session::remembered()) {
            self::remember(false);
        } else {
            session_set_cookie_params(0);
        }

        session_set_save_handler(
            array('Session', 'connect'),
            array('Session', 'close'),
            array('Session', 'read'),
            array('Session', 'write'),
            array('Session', 'destroy'), 
            array('Session', 'gc' )
        );
        session_start();
    }

    public static function connect ()
    {
        $host = Config::settings()->session_db['host'];
        $dbname = Config::settings()->session_db['dbname'];
        $username = Config::settings()->session_db['username'];
        $password = Config::settings()->session_db['password'];
        $dsn = sprintf("mysql:host=%s;dbname=%s", $host, $dbname);
        $driver_options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        );

        self::$connection = new PDO($dsn, $username, $password, $driver_options);

        return (self::$connection !== null);
    }

    final static public function connected ()
    {
        return (self::$connection != null);
    }
    
    final static public function connection ()
    {
        if (!self::connected()) {
            self::connect();
        }

        return self::$connection;
    }

    public static function close ()
    {
        self::$connection = null;
    }

    public static function read ($key)
    {
        $mebbe = 0;
        $table = Config::settings()->session_db['table'];
        if (strlen($key) > 0) {
            while ($mebbe < 3) {
                try {
                    $statement = self::connection()->prepare(sprintf("SELECT * FROM %s WHERE session_id = ?", $table));
                    $statement->bindValue(1, $key);
                    $response = $statement->execute();
                    if ($response !== false) {
                        $row = $statement->fetch();
                        return $row["session_data"];
                    }
                } catch (Exception $e) {
                    Log::fatal(array("mebbe" => $mebbe, "exception" => $e));
                }
                $mebbe++;
            }
        }
    }

    public static function write ($key, $value)
    {
        $table = Config::settings()->session_db['table'];
        $mebbe = 0;
        while ($mebbe < 3) {
            try {
                $statement = self::connection()->prepare(sprintf("REPLACE INTO %s (session_data, session_id, session_expire) VALUES (?, ?, ?)", $table));
                $statement->bindValue(1, ($value), PDO::PARAM_LOB);
                $statement->bindValue(2, $key);
                if (Session::remembered()) {
                    $statement->bindValue(3, time() + (365 * 24 * 60 * 60)); 
                } else {
                    $statement->bindValue(3, time() + (4 * 60 * 60)); 
                }
                $response = $statement->execute();
                return true;
            } catch (Exception $e) {
                Log::fatal(array("mebbe" => $mebbe, "exception" => $e));
            }
            $mebbe++;
        }
    }

    public static function id ()
    {
        return session_id();
    }

    public static function name ()
    {
        return session_name();
    }

    public static function obliterate ($keys_to_retain = array())
    {
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $keys_to_retain)) {
                unset($_SESSION[$key]);
            }
        }
        return session_regenerate_id(true);
    }

    public static function destroy ($key)
    {
        $table = Config::settings()->session_db['table'];
        $statement = self::connection()->prepare(sprintf("DELETE FROM %s WHERE session_id = ? LIMIT 1", $table));
        $statement->bindValue(1, $key);
        try {
            $response = $statement->execute();
            return true;
        } catch (Exception $e) {
            Log::fatal($e);
        }
    }

    public static function gc ($lifetime)
    {
        return true;
    }

    public static function garbage_collect ()
    {
        $table = Config::settings()->session_db['table'];
        $sql = sprintf("DELETE FROM %s WHERE session_expire < %d", $table, time());
        $response = self::connection()->query($sql);
    }

    public static function remember ($regenerate_id = true)
    {
        session_set_cookie_params((60 * 60 * 24 * 365));
        if ($regenerate_id) {
            session_regenerate_id();
        }
        setcookie('remember', 1, time() + 3600, '/');
    }

    public static function forget ()
    {
        setcookie('remember', 0, time() + 3600, '/');
    }

    public function __toString ()
    {
        return "Session Object\n" . print_r($_SESSION, true);
    }
}

?>
