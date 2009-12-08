<?php

/**
 * PHP Growl
 *
 * An interface for sending growl notifications to multiple servers
 *
 * PHP version 5.0.0+
 * 
 * @package PHP Growl
 * @version 1.0
 * @author  Ben Morris <ben@bnmrrs.com>
 * @author  Tyler Hall <tylerhall@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */
class Growl
{
  /**
   *  GROWL PRIORITY CONSTANTS
   */
  const PRIORITY_LOW = -2;
  const PRIORITY_MODERATE = -1;
  const PRIORITY_NORMAL = 0;
  const PRIORITY_HIGH = 1;
  const PRIORITY_EMERGENCY = 2;

  /**
   * Array of growl server details
   * 
   * @var array
   * @see self::$_connectionDefaults for server connection defaults
   */
  protected $_serverDetails = array();

  /**
   * Array of Growl_Connection objects used
   * 
   * @var array
   */
  protected $_connections = array();

  /**
   * Default server connection values
   * 
   * @var array
   */
  protected $_connectionDefaults = array(
    'address'       => 'localhost',
    'password'      => '',
    'app_name'      => 'PHP_Growl',
    'port'          => 9887,
    'notifications' => array(),
  );

  /**
   * __construct
   *
   * Takes an array of servers and builds Growl_Connection objects for each
   *
   * @param  array $servers Servers to connect to
   * @return void
   */
  public function __construct(array $servers)
  {
    $this->_serverDetails = $servers;

    $this->_createConnections($servers);
  }

  /**
   * notify
   *
   * Sends out a notification to all growl servers by default
   * If you'd like to only send the notification to certain servers you can pass 
   * an array of server names as the final argument
   *
   * @param string  $name     Name of the notification
   * @param string  $title    Title of the notification
   * @param string  $message  Message to send
   * @param integer $priority Priority of notification.  Defaults to normal
   * @param boolean $sticky   Sticky notification or not.  Defaults to false
   * @param array   $servers  Array of server names to notify
   * @return void
   */
  public function notify($name, $title, $message, $priority = self::PRIORITY_NORMAL, $sticky = false, $servers = false)
  {
    // If we didn't get any servers default to all
    if (!$servers) {
      $servers = array_keys($this->_servers);
    }

    // Iterate over the server names and send each notification
    foreach ($servers as $serverName) {

      // Throw an exception if that is an unknown server name
      if (!$this->_connections[$serverName]) {
        throw new Growl_Exception(sprintf('Unknown server: %s', $serverName));
      }

      $this->connections[$serverName]->notify($name, $title, $message, $priority, $sticky);
    }
  }

  /**
   * _createConnections
   *
   *  Creates Growl_Connections from an array of server configs
   *
   * @param  array $servers Array of server configs
   * @return void
   */
  protected function _createConnections(array $servers)
  {
    // we need to create a new connection for every server
    foreach ($servers as $serverName => $server) {

      // Merge the defaults with the current server config.
      // Current server will over write any defaults
      $server = array_merge($this->_connectionDefaults, $server);

      // Create our new connection and set the details
      $conn = new Growl_Connection()
        ->setAddress($server['address'])
        ->setPassword($server['password'])
        ->setAppName($server['app_name'])
        ->setPort($server['port']);

      // Set all notifications
      foreach ($server['notifications'] as $notification) {
        $conn->setNotification($notification);
      }

      // Send the registration packet
      $conn->register();

      // Store the connection by it's server name
      $this->_connections[$serverName] = $conn;
    }
  }
}
