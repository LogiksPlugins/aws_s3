<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/s3.php";

if(!defined('awsAccessKey')) define('awsAccessKey', AWS_ACCESS_KEY);
if(!defined('awsSecretKey')) define('awsSecretKey', AWS_SECRET_KEY);
?>
