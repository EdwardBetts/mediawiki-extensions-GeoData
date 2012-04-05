<?php


$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../../..';
}
require_once( "$IP/maintenance/Maintenance.php" );

class UpdateIndexGranularity extends Maintenance {
	const BATCH_SIZE = 500;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Updates GeoData database after $wgGeoDataIndexGranularity has been changed';
	}

	public function execute() {
		global $wgGeoDataIndexGranularity;
		if ( !isset( $wgGeoDataIndexGranularity ) ) {
			$this->error( 'Please install GeoData properly', true );
		}
		$id = 0;
		$dbw = wfGetDB( DB_MASTER );
		do {
			$ids = array();
			$dbw->begin( __METHOD__ );
			$res = $dbw->select( 'geo_tags', 'gt_id',
				array( "gt_id > $id" ),
				__METHOD__,
				array( 'LIMIT' => self::BATCH_SIZE )
			);
			foreach ( $res as $row ) {
				$id = $row->gt_id;
				$ids[] = $id;
			}
			$dbw->update( 'geo_tags',
				array(
					"gt_lat_int = ROUND(gt_lat * $wgGeoDataIndexGranularity)",
					"gt_lon_int = ROUND(gt_lon * $wgGeoDataIndexGranularity)"
				),
				array( 'gt_id' => $ids ),
				__METHOD__
			);
			$dbw->commit( __METHOD__ );
			$this->output( "$id\n" );
			wfWaitForSlaves();
		} while ( count( $ids ) === self::BATCH_SIZE );
	}
}

$maintClass = 'UpdateIndexGranularity';
require_once( DO_MAINTENANCE );

