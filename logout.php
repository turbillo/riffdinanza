<?php
require_once 'misvars.php';
session_destroy();
header('Location: index.php');
exit; 