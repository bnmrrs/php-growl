<?php

/**
 * Growl_Connection 
 *
 * Interfaces with Growler over UDP.
 * Is responsible for registering the avaliable notifications and then actually 
 * sending them out
 *
 * PHP version 5.0.0+
 * 
 * @package PHP Growl
 * @version 1.0
 * @author Ben Morris <ben@bnmrrs.com>
 * @author Tyler Hall <tylerhall@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */
class Growl_Connection
{

  /**
   * Address for growl server
   *
   * @var string
   */
  protected $_address = 'localhost';

  /**
   * Password, if any, for growl server
   * 
   * @var string
   */
  protected $_password = '';

  /**
   * Port for growl server.  Defaults to 9887
   * 
   * @var int
   */
  protected $_port = 9887;

  /**
   * Application name
   * 
   * @var string
   */
  protected $_appName = 'PHP Growl';

  /**
   * Array of notifications to register
   * 
   * @var array
   */
  protected $_notifications = array();

  /**
   * Boolean flag for if the notifications have been registered
   * 
   * @var boolean
   */
  protected $_registered = false;

  /**
   * notify 
   *
   * Sends out a growl notification
   * 
   * @param string  $name     Notification name
   * @param string  $title    Title of the growl
   * @param string  $message  Message to display
   * @param integer $priority Growl Priority
   * @param boolean $sticky   Stick notification or not
   * @return void
   */
  public function notify($name, $title, $message, $priority, $sticky)
  {
    // Notifications must be registered before they are sent
    if (!$this->_registered) {
      throw new Exception ('All notifications have not been registered');
    }

    // Set proper encoding
    $name     = utf8_encode($name);
    $title    = utf8_encode($title);
    $message  = utf8_encode($message);
    $priority = intval($priority);

    // Combine the priority and sticky into one flag
    $flags = ($priority & 7) * 2;
    if($priority < 0) $flags |= 8;
    if($sticky) $flags |= 1;

    // pack(Protocol version, type, priority/sticky flags
    $data = pack('c2n5', 1, 1, $flags,
      strlen($name), // Notification name length
      strlen($title), // Title length
      strlen($message), // Message length
      strlen($this->_appName) // Application name length
    );

    $data .= $name . $title . $message . $this->_appName;

    // pack(Hash of the current data and password)
    $data .= pack('H32', md5($data . $this->_password));

    return $this->_send($data);
  }

  /**
   * register
   *
   * Bundles all of the avaliable notifications and sends a registration packet
   * to the growl server
   * 
   * @return void
   */
  public function register()
  {
    $defaults = '';
    $data = '';

    // Iterate over the notifications and add them to the registration packet
    // Registration is not aditive, all notifications need to be sent at once
    foreach ($this->_notifications as $key => $notification) {
      $data .= pack('n', strlen($notification));
      $data .= $notification;

      // Set the notification to enabled
      $defaults .= pack('c', $key);
      $numDefaults++;
    }

    // pack(Protocol version, type, app name, number of notifications
    $data = pack('c2nc2', 1, 0, strlen($this->_appName), count($this->notifications), $numDefaults);
    $data .= $this->_appName . $data . $defaults;
    $data .= pack('H32', md5($data . $this->_password));

    // Send the registration packet
    return $this->_send($data);
  }

  /**
   * addNotification 
   *
   * Setter for notifications
   * 
   * @param  string $notification 
   * @return Growl_Connection Allows for method chaining
   */
  public function addNotification($notification)
  {
    $this->_notifications[] = $notification;

    // As a notification as added the object needs to reregister with the server
    $this->_registered = false;

    return $this;
  }

  /**
   * setAddress 
   *
   * Setter for the address
   * 
   * @param string $address 
   * @return Growl_Connection Allows for method chaining
   */
  public function setAddress($address)
  {
    $this->_address = $address;

    return $this;
  }

  /**
   * setPassword 
   *
   * Setter for the server password
   * 
   * @param string $password 
   * @return Growl_Connection Allows for method chaining
   */
  public function setPassword($password)
  {
    $this->_password = $password;

    return $this;
  }

  /**
   * setAppName 
   *
   * Setter for the application name
   * 
   * @param string $appName 
   * @return Growl_Connection Allows for method chaining
   */
  public function setAppName($appName)
  {
    $this->_appName = $appName;

    return $this;
  }

  /**
   * setPort 
   *
   * Setter for the port to use
   * 
   * @param integer $port 
   * @return Growl_Connection Allows for method chaining
   */
  public function setPort($port)
  {
    $this->_port = $port;

    return $this;
  }

  /**
   * _send 
   *
   * Actually sends data out to the growl server
   * 
   * @param string $data Data that will be sent
   * @return boolean
   */
  protected function _send($data)
  {
    // Create the UDP socket
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    return (socket_sendto($sock, $data, strlen($data), 0x100, $this->_address, $this->_port));
  }
}
