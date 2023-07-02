<?php



class Web extends \Workerman\WebServer
{
    function onMessage( $connection)
    {
        parent::onMessage( $connection);
    }
}