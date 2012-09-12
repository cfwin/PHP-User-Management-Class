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

		// Constructor: Sets class properties and runs buildDSN function + connected to DB
		public function __construct($u_dbhost, $u_dbname, $u_dbuser, $u_dbpass)
		{
			$this->dbhost = $u_dbhost;
			$this->dbname = $u_dbname;
			$this->dbuser = $u_dbuser;
			$this->dbpass = $u_dbpass;

			$this->buildDSN($this->dbhost, $this->dbname);
			$this->connectToDB();
		}

		// PHP4 constructor support
		function UserManagement()
		{
			$this->__construct();
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
				echo 'Connection established';
			}

			catch (PDOException $e)
			{
				echo 'Connection failed: ' . $e->getMessage();
			}
		}

		// Set $dbcon property to null
		// Can be used publically for backwards compatability
		public function disconnectDB()
		{
			$this->dbcon = null;
		}
		
		// Test Function
		public function test()
		{
			try
			{
				$stmt = $this->dbcon->prepare('SELECT U_name from admin');
				$stmt->execute();
				
				while ($row = $stmt->fetch())
				{
					print_r($row['U_name']);
				}
			}
			
			catch (PDOException $e)
			{
				print_r('ERROR: ' . $e->getMessage());
			}
		}
		
		public function createUser($email, $firstname, $surname, $username, $password)
		{
			$stmt = $this->dbcon->prepare('INSERT INTO users (
											email, first_name, surname, username, password)
											VALUES (
											:email, :first_name, :surname, :username, :password)');
			
			$stmt->bindParam(':email', $email, PDO::PARAM_STR);
			
		}
		
		// Function to generate Salts for passwords
		private function generateSalt ($length = 8)
		{
			$password = "";

			// define possible characters - any character in this string can be
			// picked for use in the password, so if you want to put vowels back in
			// or add special characters such as exclamation marks, this is where
			// you should do it
			$possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

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