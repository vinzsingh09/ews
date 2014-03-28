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

            // connect to your mysqli database
            $dbconn = mysqli_connect($hostname, $username, $password, $database);
            // if could not connect return false
            if(!$dbconn)
                return false;
				
            // return database connection handle.
            return $dbconn;
        }

        // method to close the database connection
        function disconnect_db($dbconn)
        {
            mysqli_close($dbconn);
        }

        // method to execute  queries 
        function execute_query($qry)
        {
            // open database connection
            $dbconn = $this->connect_db();

            // execute the query
            $result = mysqli_query($dbconn, $qry);

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
            $line = mysqli_fetch_array($result, MYSQL_ASSOC);
            return $line;
        }
    }
?>