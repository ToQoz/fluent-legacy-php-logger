<?php
    require "./src/FluentLogger.php";

    $logger = FluentLogger::open('0.0.0.0', 24224);
    $logger->post("debug.toqoz", array("id" => 111, "name" => "ToQoz"));
