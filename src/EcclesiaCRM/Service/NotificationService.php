<?php

namespace EcclesiaCRM\Service;

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\SessionUser;

class NotificationService
{
  public static function updateNotifications()
  {
    /* Get the latest notifications from the source.  Store in session variable
     *
     */
    if (!empty(SystemConfig::getValue("sCloudURL"))) {
      try {
        $cloudURL = SystemConfig::getValue("sCloudURL");
        if (!isset($_SESSION['SystemNotifications']) and !empty ($cloudURL) ) {
          $TempNotificaions = json_decode(file_get_contents(SystemConfig::getValue("sCloudURL")."notifications.json" ));
          if (!is_null($TempNotificaions) and isset($TempNotificaions->TTL)) {
            $_SESSION['SystemNotifications'] = $TempNotificaions;
            $_SESSION['SystemNotifications']->expires = new \DateTime();
            $_SESSION['SystemNotifications']->expires->add(new \DateInterval("PT".$_SESSION['SystemNotifications']->TTL."S"));
          } else {
            $_SESSION['SystemNotifications'] = NULL;
          }
        }
      } catch (\Exception $ex) {
        //a failure here should never prevent the page from loading.
        //Possibly log an exception when a unified logger is implemented.
        //for now, do nothing.
      }
    }
  }

  public static function getNotifications()
  {
    /* retreive active notifications from the session variable for display
     *
     */
    if (isset($_SESSION['SystemNotifications']))
    {
      $notifications = array();
      if ( !is_null($_SESSION['SystemNotifications']) and isset($_SESSION['SystemNotifications']->messages) ) {
        foreach ($_SESSION['SystemNotifications']->messages as $message)
        {
          if($message->targetVersion == $_SESSION['sSoftwareInstalledVersion'])
          {
            if (! $message->adminOnly ||  SessionUser::getUser()->isAdmin())
            {
              array_push($notifications, $message);
            }
          }
        }
      }
      return $notifications;
    }

    return NULL;
  }

  public static function hasActiveNotifications()
  {
      if (!is_null(NotificationService::getNotifications()))
        return count(NotificationService::getNotifications()) > 0;
      return 0;
  }

  public static function isUpdateRequired()
  {
    /*
     * If session does not contain notifications, or if the notification TTL has expired, return true
     * otherwise return false.
     */
    if ( !isset($_SESSION['SystemNotifications']) 
        || (isset($_SESSION['SystemNotifications']->expires) && $_SESSION['SystemNotifications']->expires < new \DateTime()) )
    {
      return true;
    }
    return false;
  }

}
