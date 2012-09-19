<?php

    class UserManagement
    {
        // DB Connection properties
        private $dbhost;
        private $dbname;
        private $dbuser;
        private $dbpass;

        // PDO properties
        private $dsn;
        private $dbcon;

        // Email Properties
        private $sender;
        private $headers; // 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // Constructor: Sets class properties and runs buildDSN function + connected to DB
        public function __construct($u_dbhost, $u_dbname, $u_dbuser, $u_dbpass, $emailsender)
        {
            $this->dbhost = $u_dbhost;
            $this->dbname = $u_dbname;
            $this->dbuser = $u_dbuser;
            $this->dbpass = $u_dbpass;
            $this->sender = $emailsender;
            $this->sender = $emailsender;
            $this->headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $this->headers .= 'From: admin@roketworks.com';

            $this->buildDSN($this->dbhost, $this->dbname);
            $this->connectToDB();
        }

        // PHP5 desctructor: calls disconnectDB() function, sets PDO connection property equal to null
        public function __destruct()
        {
            $this->disconnectDB();
        }

        // Sets $dsn property for mysql connections
        private function buildDSN($dbhost, $dbname)
        {
            $this->dsn = 'mysql:dbname=' . $dbname . ';host=' . $dbhost;
        }

        // Set $dbcon property to a new PDO connection (Catch any PDO exceptions)
        // Can be used publically for backwards compatability
        public function connectToDB()
        {
            try
            {
                $this->dbcon = new PDO($this->dsn, $this->dbuser, $this->dbpass);
                $this->dbcon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);         
            }

            catch (PDOException $e)
            {
                throw new Exception('Connection failed: ' . $e->getMessage());
            }
        }

        // Set $dbcon property to null
        // Can be used publically for backwards compatability
        public function disconnectDB()
        {
            $this->dbcon = null;
        }

        // Function to create user. Takes 5 parameters.
        public function createUser($email, $firstname, $surname, $username, $password)
        {	
            // Email validation
            if (!$this->validateEmail($email))
            {
                throw new Exception('Invalid Email');
            }

            // Username, first name and surename validation. Only Letters + numbers allowed
            $totest = array($firstname, $surname, $username);
            foreach ($totest as $str)
            {
                if (!ctype_alnum($str))
                {
                    $key = array_search($str, $totest);

                    switch ($key)
                    {
                        case 0:
                            throw new Exception('First name must only contain Alphanumeric charachters');
                            break;
                        case 1:
                            throw new Exception('Surname must only contain Alphanumeric charachters');
                            break;
                        case 2:
                            throw new Exception('Username must only contain Alphanumeric charachters');
                            break;
                    }

                    throw new Exception('Error occured validating data');
                }
            }

            // This is to add detailed error. Table schema will stop repeats. 
            // Next 4 lines can be removed if ajax calls for username validation are used.
            $uservalid = $this->checkUsername($username);
            if (!$uservalid)
            {
                throw new Exception('Username already exists');
            }
            
            // Password validation
            if (strlen($password) < 8)
            {
                throw new Exception('Password must be 8 charchters long');
            }
            
            // Hash password + generate salt
            $salt = $this->generateSalt(16);
            $password = $password . $salt;
            $pwordhash = hash('sha512', $password);

            // Generate email salt for account activation
            $email_hash = $this->generateSalt(20);        

            //Insert data into DB with PDO object
            try
            {
                $sql = $this->dbcon->prepare('INSERT INTO users (
                                                email, first_name, surname, username, password, salt, email_hash)
                                                VALUES (
                                                :email, :first_name, :surname, :username, :password, :salt, :email_hash)');

                $sql->bindParam(':email', $email, PDO::PARAM_STR, 100);
                $sql->bindParam(':first_name', $firstname, PDO::PARAM_STR, 40);
                $sql->bindParam(':surname', $surname, PDO::PARAM_STR, 50);
                $sql->bindParam(':username', $username, PDO::PARAM_STR, 20);
                $sql->bindParam(':password', $pwordhash, PDO::PARAM_STR);
                $sql->bindParam(':salt', $salt, PDO::PARAM_STR, 16);
                $sql->bindParam(':email_hash', $email_hash, PDO::PARAM_STR, 20);
                $sql->execute();
            }

            catch (PDOException $e)
            {
                // Throw Exception and log PDOExeption
                throw new Exception('Error occured creating user');

                $logfile = 'log.txt';
                $log = fopen($logfile, 'a');
                $logtxt = date('d-m-Y') . ': ' . $e->getMessage() . "\n";
                fwrite($log, $logtxt);
                fclose($log);
            }
            
            $message = "Please activate your account by clicking this link. http://firefly.iluvmeme.com/test/activate.php?act=" . $email_hash;
            mail($email, $message, $subject, $this->headers);
            
            return true;
        }

    // Email validation function
        private function validateEmail($email)
        {
            strtolower($email);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                return false;
            }

            else 
            {
                return true;
            }
        }

        // Function to check is username already exists in DB
        // Can be used publically for uses like ajax form calls
        public function checkUsername($username)
        {	
                $sql = $this->dbcon->prepare('SELECT username FROM users WHERE username LIKE :username');
                $sql->bindParam(':username', $username, PDO::PARAM_STR, 20);
                $sql->execute();
                $result = $sql->fetchColumn();

                if ($result != 0)
                {
                        return false;
                }

                else 
                {
                        return true;
                }
        }

        // Function to generate Salts for passwords
        private function generateSalt ($length = 8)
        {
            $password = '';

            // define possible characters - any character in this string can be
            // picked for use in the password, so if you want to put vowels back in
            // or add special characters such as exclamation marks, this is where
            // you should do it
            $possible = '2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ';

            $maxlength = strlen($possible);

            // check for length overflow and truncate if necessary
            if ($length > $maxlength) 
            {
                $length = $maxlength;
            }

            $i = 0; 

            // add random characters to $password until $length is reached
            while ($i < $length) 
            { 
                $char = substr($possible, mt_rand(0, $maxlength-1), 1);

                if (!strstr($password, $char)) 
                { 
                    $password .= $char;
                    $i++;
                }

            }
            return $password;
        }

    }

?>