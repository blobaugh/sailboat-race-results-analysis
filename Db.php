<?php

class Db {

	/**
 	 * Holds the database connection
 	 * @var mysqli
 	 */
	private $conn;

	/**
 	 * Constructor. Creates the connection.
 	 *
 	 * @param string $dbuser
 	 * @param string $dbpass
 	 * @param string $dbname
 	 * @param string $dbserver
 	 */
	public function __construct( $dbuser, $dbpass, $dbname, $dbserver ) {
		$this->conn = new mysqli( $dbserver, $dbuser, $dbpass, $dbname );
	}

	/**
 	 * Insert an associative array into a table
 	 *
 	 * @param string $table
 	 * @param array $data
 	 * @return int
 	 */
	public function insertArray( $table, $data ) {
		$sql = "INSERT INTO `$table` SET ";

		$d = [];
		foreach( $data as $k => $v ) {
			$d[] = "`$k`='$v'";
		}

		$sql = $sql . implode( ',', $d );

		$result = $this->conn->query( $sql );
		/*echo '<hr>';
		echo '<pre>';
		var_dump( $data );
		echo $sql . "<br>";
		var_dump( $this->conn->error );A*/
		return $this->conn->insert_id;
	}

	/**
 	 * Run select query
 	 *
 	 * @param string $table
 	 * @param string $where
 	 * @return
 	 */
	public function select( $table, $where ) {
		$sql = "SELECT * FROM `$table` WHERE $where";
		
		$result = $this->conn->query( $sql );
		return $result;
	}

	/** 
 	 * Get the last insert id
 	 *
 	 * @return int
 	 */
	public function insert_id() {
		$this->conn->insert_id; 
	}	
} // end class
