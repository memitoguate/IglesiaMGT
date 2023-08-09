<?php
/*******************************************************************************
 *
 *  filename    : Include/Header-Minimal.php
 *  last change : 2003-05-29
 *  description : page header (Bare minimum, not for use with Footer.php)
 *
 *  http://www.ecclesiacrm.com/
 *  Copyright 2003 Chris Gebhardt
  *
 ******************************************************************************/
require_once 'Header-Security.php';

use EcclesiaCRM\dto\SystemURLs;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<head>
  <meta http-equiv="pragma" content="no-cache">
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8">

  <?php require 'Header-HTML-Scripts.php'; ?>

    <script nonce="<?= SystemURLs::getCSPNonce() ?>">
        window.CRM = {
            root: "<?= SystemURLs::getRootPath() ?>",
        };
    </script>
</head>

<body>
