<?php 
// pour le debug on se met au bon endroit : http://192.168.151.205/mysql/upgrade/5.4.0-upgrade.php
// et il faut décommenter
/*define("webdav", "1");
require '../../Include/Config.php';*/

  use Propel\Runtime\Propel;
  use EcclesiaCRM\Utils\LoggerUtils;
  use EcclesiaCRM\dto\SystemURLs;

  $logger = LoggerUtils::getAppLogger();
  
  $logger->info("Start to delete : all unusefull files");

  unlink(SystemURLs::getDocumentRoot()."/PropertyTypeEditor.php");
  unlink(SystemURLs::getDocumentRoot()."/PropertyTypeDelete.php");
  unlink(SystemURLs::getDocumentRoot()."/PropertyTypeList.php");
  unlink(SystemURLs::getDocumentRoot()."/PropertyEditor.php");
  unlink(SystemURLs::getDocumentRoot()."/PropertyDelete.php");
  unlink(SystemURLs::getDocumentRoot()."/PropertyList.php");
  
  unlink(SystemURLs::getDocumentRoot()."/MapUsingBing.php");
  unlink(SystemURLs::getDocumentRoot()."/MapUsingGoogle.php");
  unlink(SystemURLs::getDocumentRoot()."/MapUsingLeaflet.php");
  
  unlink(SystemURLs::getDocumentRoot()."/DocumentDelete.php");
  unlink(SystemURLs::getDocumentRoot()."/DocumentEditor.php");
  
  $logger->info("End of delete :  all unusefull files");
?>
