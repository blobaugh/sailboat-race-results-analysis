<?php

class BatchProcessResults {

	private $path;

	public function __construct( $path ) {
		$this->path = $path;
	}

	public function process() {
		$dir = new DirectoryIterator( $this->path );
		
		foreach( $dir as $f ) {
			if ( $f->isDot() ) {
				continue; // Ignore this
			}
			$file = $f->getPath() . '/' . $f->getFilename();
			echo "<br>Processing: $file";

			$html = file_get_contents( $file );
				
			$results = new RaceResultsProcessor();
			$results->loadHtml( $html );

			$r = $results->processResults();  

			$writer = new RaceDbWriter();
			$writer->setResults( $r );
			$writer->write(); 
		}
	}
} // end class
