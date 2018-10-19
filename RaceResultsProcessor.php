<?php

/*
 * Here is where things get tricky. The html structure looks like this
 *
 * table - race info
 * race links
 * loop {
 * 	H3 class info
 * 	table boats + results
 *
 * 	What we need to do is pull it apart and massage the data into an array
 * 	that looks something like the following:
 *
 * 	race [array] {
 * 		committee_boat
 * 		date
 * 		name
 *		class [array] {
 *			name (1 NFS)
 *			start (24 hr time)
 *			course (NRN)
 *			distance (decimal rep miles)
 *			results [array] {
 *				boat
 *				sail_number
 *				boat_type
 *				skipper
 *				phrf
 *				club
 *				start
 *				finish
 *				elapsed
 *				corrected
 *				bce
 *				place
 *				overall
 *			}
 *		}
 *	}
 */
class RaceResultsProcessor {

	/**
 	 * Holds the domDocument from the html string
 	 *
 	 * @var domDocument
 	 */
	private $dom;

	/**
 	 * Array that contains the processed results.
 	 * Should be the full race details
 	 *
 	 * @var array
 	 */
	private $processed_results = [];

	public function __construct() {
		$this->dom = new domDocument;

		// Discard redundant whitespace
		//$this->dom->preserveWhiteSpace = false;
	}

	/**
 	 * Load the html from the race results into dom
 	 *
 	 * @param string $html
 	 */
	public function loadHtml( $html ) {
		@$this->dom->loadHTML( $html ); // supress messages to avoid duplicate attributes notice
	}

	/**
 	 * Convert html results into usable array
 	 */
	public function processResults() {
		$this->processed_results = $this->getRaceInfo();

		$this->processed_results['classes'] = $this->get_classes();
		return $this->processed_results;
	}

	/**
 	 * Gets the classes with the class results from the domDocument
 	 *
 	 * @return array
 	 */
	private function get_classes() {
		$classes_dom = $this->dom->getElementsByTagName( 'h3' );

		$classes = []; // Holds class details, incl boat results

		$i = 0; // Which class are we looking at in the array? Should be same results table
		foreach( $classes_dom as $class_dom ) {
			$class = []; // Holds data for this one class

			// Break the data into usable chunks
			$data = explode( '-', $class_dom->nodeValue );

			$class['name'] = trim( str_replace( 'Class: ', '', $data[0] ) );
			$class['start'] = trim( str_replace( 'Start: ', '', $data[1] ) );
			$class['course'] = trim( str_replace( 'Course: ', '', $data[2] ) );
			$class['distance'] = trim( str_replace( 'Distance: ', '', $data[3] ) );

			$class['results'] = $this->getClassResults( $i );
			$classes[] = $class;
			$i++;

		}

		return $classes;
	}

	/**
 	 * Attempts to get the results from the associated class.
 	 * Numbered from the top down.
 	 *
 	 * @return array
 	 */
	private function getClassResults( $i ) {
		$i++; // To accound for race info table

		$table = $this->dom->getElementsByTagName( 'table' )->item( $i );

		// get each row (boat result)
		$rows = $table->getElementsByTagName('tr'); 

		$results = [];
		$first_row = true;
		$headers = [];

		foreach( $rows as $row ) {
			if( $first_row ) {
				$first_row = false;
					
				$cols = $row->getElementsByTagName('th'); 
				for( $i = 0; $i < $cols->length; $i++ ) {
					$headers[] = strtolower( str_replace( ' ', '_', $cols->item( $i )->nodeValue ) );
				}	
				$headers = $this->fixHeaders( $headers );
				continue;
			}

			$cols = $row->getElementsByTagName('td'); 
			$a = [];
			for( $i = 0; $i < $cols->length; $i++ ) {
				$a[ $headers[ $i ] ] = $cols->item( $i )->nodeValue;
			}
			$results[] = $a;
/*
			$results[] = [
				$headers[ $x ]			=> $cols->item( 0 )->nodeValue,
				'sail_number'	=> $cols->item( 1 )->nodeValue,
				'boat_type'		=> $cols->item( 2 )->nodeValue,
				'skipper'		=> $cols->item( 3 )->nodeValue,
				'phrf'			=> $cols->item( 4 )->nodeValue,
				'club'			=> $cols->item( 5 )->nodeValue,
				'start'			=> $cols->item( 6 )->nodeValue,
				'finish'		=> $cols->item( 7 )->nodeValue,
				'elapsed'		=> $cols->item( 8 )->nodeValue,
				'corrected'		=> $cols->item( 9 )->nodeValue,
				'bce'			=> $cols->item( 10 )->nodeValue,
				'place'			=> $cols->item( 11 )->nodeValue,
				'overall'		=> $cols->item( 12 )->nodeValue
			];
			$x++;
 */
		}

		return $results;
	}

	/**
 	 * Fix the headers so they match the database.
 	 *
 	 * @param array $bad_headers
 	 * @return array
 	 */
	private function fixHeaders( $bad_headers ) {
		$headers = [];
		foreach( $bad_headers as $k => $v ) {
			switch ( $v ) {
				case 'boat_name':
					$v = 'name';
					break;
				case 'sailno':
					$v = 'sail_number';
					break;
				case 'rating':
					$v = 'phrf';
					break;
				case 'oa':
					$v = 'overall';
					break;
			}

			$headers[ $k ] = $v;
		}
		return $headers;
	}

	/**
 	 * Gets the race info from the domDocument
 	 *
 	 * Due to some soppy formatting on the html we have to load a new domDocument
 	 * to parse out the race info :facepalm:
 	 *
 	 * @return array
 	 */
	private function getRaceInfo() {
		$html = $this->innerHTML( $this->dom->getElementsByTagName( 'table' )->item( 0 ) );

		$dom = new domDocument;
		$dom->loadHTML( $html );

		$race_info = [];
		
		// Race title
		// Here is the bad formatting
		$race_info['title'] = explode( '<', $this->innerHTML( $dom->getElementsByTagName( 'h1' )->item( 0 ) ) )[0];

		// Date
		$race_info['date'] = $dom->getElementsByTagName( 'font' )->item( 0 )->nodeValue;

		$race_info['committee_boat'] = trim( explode( '-', $dom->getElementsByTagName( 'p' )->item( 0 )->nodeValue )[1] );

		return $race_info;
	}

	private function innerHTML($node) {
		return implode( array_map(
			[$node->ownerDocument,"saveHTML"], 
			iterator_to_array($node->childNodes)
		) );
	} 
}

