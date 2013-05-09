<?php
//error_reporting(E_ALL);

/*
Script for backing up a directory of files. Files are zipped and then sent to
a CDN. This script uses the Opencloud API developed by Rackspace, but portable
for use on any Openstack cloud hosting service. Requires filesystem level 
access including exec() and chdir(). 

ToDo:
 - Add args[] input for more open ended use.
 - Add timestamping and additonal directory breakdown
 - Seperate config file for Defines
 - Alerts for failed backup, especially 
 - invoke TAR append funcitonality and check for 'touch' date

@link http://www.rackspace.com/cloud/openstack/
@link http://www.openstack.org/software/openstack-storage/
@link http://php-opencloud.com/

@author Aaron Montana
*/



// Include the autoloader
require '/Users/aaron/GitRepos/php-opencloud/lib/php-opencloud.php';

//Define anything used in our API connection, could be moved to a config file later.
define('AUTHURL', "https://identity.api.rackspacecloud.com/v2.0/");
define('USERNAME', "USERNAMEGOESHERE");
define('APIKEY', "APIKEYGOESRIGHTHERE");
define('REGION', "DFW");
define('SERVICETYPE', "cloudFiles");
define('CONTAINERNAME', "database_backups");



//Wrap procedular code in functions to ease upgrades later.


function compressDirectory($backupsource = null, $backupname = "backup.tar.gz"){ 
	//Where are these files from?
	$backupsource = "/Users/aaron/Pictures/";
	//Where are we at?
	$backupdestination = getcwd();
	//Go to the source to avoid zipping the directory structure along with files
	chdir($backupsource);
	//Write file to the script origin to ease upload pathing
	echo "tar -czf ".$backupdestination."/".$backupname." *";
	exec("tar -czf ".$backupdestination."/".$backupname." *");
	//reset current directory to be safe.
	chdir($backupdestination);

	return true;
}



//Send a file to the CDN for storage. Note that the MIME type for a compressed tar should be: application/x-gzip
function uploadToCDN($cdn_file_name = "backup.tar.gz", $local_file_name = "backup.tar.gz", $mimetype = "application/x-gzip"){
	// establish our credentials... we should fix this later and register the namespace for cleaner invoke.
	$connection = new \OpenCloud\Rackspace( AUTHURL, array( 'username' => USERNAME, 'apiKey' => APIKEY ) );
	// now, connect to the ObjectStore service
	$objstore = $connection->ObjectStore(SERVICETYPE, REGION);
	//Grab out container for storing backups
	$container = $objstore->Container(CONTAINERNAME);
	//Container Data Objects can be used to upload and download files from the CDN
	$backuptarget = $container->DataObject();
	$readabletime =  date("D_M_j_Y_H:i:s_");
	echo $readabletime . "\n";
	echo getcwd();
	chmod($local_file_name, 0777);
	//Create is Upload, named weird IMO
	$backuptarget->Create(array('name'=>$readabletime.$cdn_file_name, 'content_type'=>$mimetype), $local_file_name);
	chmod($local_file_name, 0400);
	return true;

}

//Proceed.

if(compressDirectory()){
	echo "Compressed... \n";
	if(uploadToCDN()){
		echo "Sent to CDN. \n";
	}
}











?>