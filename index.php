<?php
/*
 * Brain dump:
 * - boats class place average (filter by all or last N years)
 * - boat place chart (show line chart of place over time)
 * - boat time per course (slowest, fastest, average)
 * - boat winningest course (calculated by most wins across courses)
 * - add new results by url
 * - batch add new results by filesystem, recursive iterator
 *
 * BatchProcessHtmlResults
 * - construct( path_to_folder_of_results )
 * - run
 * 		recursive folder iterator as file
 * 			results = new RaceResultsProcessor
 * 			results->loadHtml( file )
 * 			writer = new RaceDbWriter( results->processResults() )
 *
 * Search results
 * 	Filter by: race, year, course, class, boat name, skipper, boat type, place
 * 	Start with: boat name, course
 * 	Extra return data: elapsed low, elapsed high, elapsed average
 *
 * 	Search example
 * 		Show me the elapsed time stats for all 1st place, NFS boats on course NMWN
 * 		Show me the elapsed time stats for Breeze on course NRN
 */

require_once( 'config.php' );
require_once( 'Db.php' );
require_once( 'RaceResultsProcessor.php' );
require_once( 'RaceDbWriter.php' );
require_once( 'BatchProcessResults.php' );

/*
 * Set up the database connection
 */
$db = new Db( $dbuser, $dbpass, $dbname, $dbserver );

$path = __DIR__ . '/results';
$b = new BatchProcessResults( $path );
$b->process();
/*
 * Lets start out the testing of this project with a single race.
 *
 * - Load up the results
 * - Put the results in the db
 * - On subsequent loads update the db instead of adding new records
 *
 */
// Load the race results html
/*
$html = file_get_contents( 'results/s2r2.htm' );

$results = new RaceResultsProcessor();
$results->loadHtml( $html );

$r = $results->processResults();
$writer = new RaceDbWriter();
$writer->setResults( $r );
$writer->write();
echo '<pre>'; var_dump( $r );
 */

