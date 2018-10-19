<?php

/*
 * RaceDbWriter
 * - construct( $processedResults )
 * 		this->processedResults = processedResults
 * 		this->race_id = this->insertRace()
 * 		this->insertClassResults()
 * - int insertRace()
 * 		check for existing race
 * 		insert committee_boat, data, title
 * - insertClassResults
 * 		foreach class
 * 			class_id = this->insertClass
 * 			this->insertResults( class_id, class['results']
 * - insertClass
 * 		class_id = insert into classes (name, start, course, distance  
 * 		return class_id
 * - insertResults( class_id, results )
 * 		foreach results
 * 			insert into results race_id, class_id, boat details..  
 */

class RaceDbWriter {

	/**
 	 * Hold a copy of the results
 	 * @var array
 	 */
	private $results;

	/**
 	 * The id of the race that was created
 	 * @var int
 	 */
	private $race_id;

	/**
 	 * Constructor. Brings in the results that will be written to the database.
 	 *
 	 */
	public function __construct() {
	
	}

	/**
 	 * Write the processed race results to the database
 	 */
	public function write() {
		$race_id = $this->insertRace();
		$this->insertClassResults( $race_id );
	}

	/**
 	 * Insert the race details into the database
 	 */
	private function insertRace() {
		global $db;
		/*
 		 * First we need to check and see if the race exists already.
 		 * If it does pass back the id, if not, insert and pass back the id.
 		 *
 		 * A race is checked by date and name.
 		 */
		$race = $this->results;
		unset( $race['classes'] );

		// Convert date into mysql expected format
		$race['date'] = date( 'Y-m-d', strtotime( $race['date'] ) );	

		$where = "title='" . $race['title'] . "' AND date='" . $race['date'] . "'";
		$result = $db->select( 'races', $where );

		// If there are no results insert a new race
		if ( 0 >= $result->num_rows ) {
			$race_id = $db->insertArray( 'races', $race );
			//$race_id = $db->insert_id();
		} else {
			// Already existed. Insert instead
			$race = $result->fetch_all( MYSQLI_ASSOC );
			$race_id = $race[0]['id'];
		}

		$this->race_id = $race_id;
		return $race_id;
	}

	/**
 	 * Insert the results of each class
 	 */
	private function insertClassResults() {
		/*
 		 * Loop through each of the classes.
 		 * Add the class 
 		 * Add the boat results
 		 */
		foreach( $this->results['classes'] as $class ) {
			$class_id = $this->maybeInsertClass( $class );
			$this->insertResults( $class_id, $class['results']);
		}	
	}

	/**
 	 * Finds the class id by attempting to insert it
 	 *
 	 * @return int
 	 */
	private function maybeInsertClass( $class ) {
		global $db;

		$where = "name='" . $class['name'] . "' AND race_id='" . $this->race_id . "'";
		$result = $db->select( 'classes', $where );
		
		if ( 0 >= $result->num_rows ) {
			// Class not yet created. Insert it
			unset( $class['results'] );
			$class['distance'] = floatval( $class['distance'] );
			$class['race_id'] = $this->race_id;
			$class_id = $db->insertArray( 'classes', $class );
		} else {
			$class = $result->fetch_all( MYSQLI_ASSOC );
			$class_id = $class[0]['id'];
		}

		return $class_id;
	}

	/**
 	 * Attempts to insert each boat result
 	 *
 	 * @param int $class_id
 	 * @param array $results
 	 */
	private function insertResults( $class_id, $results ) {
		foreach( $results as $r ) {
			$r['class_id'] = $class_id;
			$r['race_id'] = $this->race_id;

			$this->maybeInsertResult( $r );
		}
	}

	/**
 	 * Attempt to insert boat rusult
 	 *
 	 * @param array $result
 	 */
	private function maybeInsertResult( $result ) {
		global $db;

		$where = "race_id='". $result['race_id'] . "' AND class_id='" . $result['class_id'] . "' AND name='" . $result['name'] . "'";
		$res = $db->select( 'results', $where );
//echo "<br>Maybe insert";
		if ( 0 >= $res->num_rows ) {
			// Insert
			$db->insertArray( 'results', $result );
			//echo " Inserted";
		} else {
			// Uh? Nothing to do I guess
		}
	}


	/**
 	 * Set the processed results to be saved to the database
 	 *
 	 * @param array $processedResults
 	 */
	public function setResults( $processedResults ) {
		$this->results = $processedResults;
	}


} // end class
