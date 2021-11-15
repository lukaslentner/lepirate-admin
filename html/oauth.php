<?php

header('Cache-Control: no-cache');

require_once(dirname(__FILE__) . '/php/include.php');

OAuth::handleCallback();

?>