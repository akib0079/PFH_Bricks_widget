<?php
/**
 * Install the built archive exactly as WordPress would.
 *
 * "Incompatible Archive." comes out of unzip_file(), so the honest check is
 * to run that function against the real file rather than trust that `unzip`
 * on a Mac can read it.
 */
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
header( 'Content-Type: text/plain' );

$zip = __DIR__ . '/test-upload.zip';

echo "file        : " . basename( $zip ) . "\n";
echo "size        : " . number_format( filesize( $zip ) ) . " bytes (" . round( filesize( $zip ) / 1048576, 2 ) . " MB)\n";
echo "mime        : " . ( function_exists( 'mime_content_type' ) ? mime_content_type( $zip ) : 'n/a' ) . "\n\n";

echo "── the server's own limits ──\n";
echo "upload_max_filesize : " . ini_get( 'upload_max_filesize' ) . "\n";
echo "post_max_size       : " . ini_get( 'post_max_size' ) . "\n";
echo "memory_limit        : " . ini_get( 'memory_limit' ) . "\n";
echo "ZipArchive present  : " . var_export( class_exists( 'ZipArchive' ), true ) . "\n\n";

echo "── WordPress's own unzip_file() ──\n";
WP_Filesystem();
$target = trailingslashit( sys_get_temp_dir() ) . 'pfh-unzip-' . wp_rand( 1000, 9999 );
$result = unzip_file( $zip, $target );

if ( is_wp_error( $result ) ) {
	echo "FAILED: " . $result->get_error_code() . " — " . $result->get_error_message() . "\n";
} else {
	$files = 0;
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $target, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) { if ( $f->isFile() ) { $files++; } }
	echo "OK — unpacked $files files\n";
	echo "top level   : " . implode( ', ', array_diff( scandir( $target ), [ '.', '..' ] ) ) . "\n";
	echo "plugin head : " . ( file_exists( $target . '/pfh-bricks-widgets/pfh-bricks-widgets.php' ) ? 'present' : 'MISSING' ) . "\n";

	// And does WordPress recognise it as a plugin?
	$data = get_plugin_data( $target . '/pfh-bricks-widgets/pfh-bricks-widgets.php', false, false );
	echo "reads as    : {$data['Name']} {$data['Version']}\n";
}

// Also the PclZip fallback, which is what runs when ZipArchive is absent.
echo "\n── the PclZip fallback ──\n";
require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
$pcl = new PclZip( $zip );
$list = $pcl->listContent();
echo $list ? 'OK — ' . count( $list ) . " entries\n" : "FAILED: " . $pcl->errorInfo( true ) . "\n";
