<?php
    //DONE
    class DBConn
    {
        //constructor
        function DBConn()
        { }
        
        // method to connect to the database
        function connect_db()
        {
            //include the file containing the password, etc
            require("config.php");

            // connect to your mysql database
            $dbconn = mysql_connect($hostname, $username, $password);
            // if could not connect return false
            if(!$dbconn)
                return false;

            // if could not switch to the database then return false
            if(!$this->select_db($database, $dbconn))
                return false;

            // return database connection handle.
            return $dbconn;
        }

        // method to select one of the databases
        function select_db($database, $dbconn)
        {
            return mysql_select_db($database, $dbconn);
        }

        // method to close the database connection
        function disconnect_db($dbconn)
        {
            mysql_close($dbconn);
        }

        // method to execute  queries 
        function execute_query($qry)
        {
            // open database connection
            $dbconn = $this->connect_db();

            // execute the query
            $result = mysql_query($qry, $dbconn);

            // if there was an error in executing the query
            if(!$result)
            {
                // close the db connection
                $this->disconnect_db($dbconn);
                // return false indicating query was unsuccessful.
                return false;
            }
            // otherwise the query executed successfully

            // close connection
            $this->disconnect_db($dbconn);
            // return the results
            return $result;
        }
        
        // method to get a row from the resultset
        function get_row($result)
        {
            $line = mysql_fetch_array($result, MYSQL_ASSOC);
            return $line;
        }
    }
?>