<?php
/*
    NameID, a namecoin based OpenID identity provider.
    Copyright (C) 2014 by Daniel Kraft <d@domob.eu>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/* Sending of JSON data.  */

/* ************************************************************************** */
/* JsonSender.  */

/**
 * Manage sending of JSON data and objects.
 */
class JsonSender
{

  /**
   * Construct.  This sends the corresponding header() commands, too.
   */
  public function __construct ()
  {
    header ("Cache-Control: no-cache");
    header ("Pragma: no-cache");
    header ("Content-Type: application/json; charset=utf-8");
  }

  /**
   * Close at the end.
   */
  public function close ()
  {
    // Nothing to be done.
  }

  /**
   * Send escaped string.
   * @param str String to escape and send.
   */
  public function sendString ($str)
  {
    $escaped = addcslashes ($str, "\r\n\"\'\\");
    $this->sendRaw ("\"$escaped\"");
  }

  /**
   * Send escaped integer.
   * @param val Value to send.
   */
  public function sendInteger ($val)
  {
    $val = (int) $val;
    $this->sendRaw ("$val");
  }

  /**
   * Send a date-time value.  It is a string in ISO format.
   * @param val Value to send.
   */
  public function sendDateTime ($val)
  {
    $this->sendString ($val);
  }

  /**
   * Send an array.
   * @return Array sender to use.
   */
  public function sendArray ()
  {
    return new JsonArraySender ($this);
  }

  /**
   * Send an object.
   * @return Object sender to use.
   */
  public function sendObject ()
  {
    return new JsonObjectSender ($this);
  }

  /**
   * Send raw data to the output stream.
   * @param str String to send.
   */
  public function sendRaw ($str)
  {
    echo $str;
  }

}

/* ************************************************************************** */
/* JsonArraySender.  */

/**
 * A utility class to handle the sending of arrays, especially to ensure
 * correct comma handling.
 */
class JsonArraySender
{

  /** Sender object to use.  */
  private $sender;

  /** Whether the next element is the first one.  */
  private $first;

  /** Whether the sending is finished already.  */
  private $closed;

  /**
   * Construct it.
   * @param sender JsonSender object to use as parent.
   */
  public function __construct ($sender)
  {
    $this->sender = $sender;
    $this->closed = false;
    $this->first = true;

    $this->sender->sendRaw ("[");
  }

  /**
   * Close the array.
   */
  public function close ()
  {
    assert (!$this->closed);
    $this->sender->sendRaw ("]");
  }

  /**
   * Switch to next "thing" to send, without actually sending
   * a particular next value.
   */
  private function next ()
  {
    assert (!$this->closed);
    if (!$this->first)
      $this->sender->sendRaw (", ");
    else
      $this->first = false;
  }

  /**
   * Switch to a next object and return the object sender.
   */
  public function addObject ()
  {
    $this->next ();
    return new JsonObjectSender ($this->sender);
  }

}

/* ************************************************************************** */
/* ObjectSender.  */

/**
 * A class to send JS compound object literals, including correct comma
 * handling for sending the individual fields.
 */
class JsonObjectSender
{

  /** Sender object to use.  */
  private $sender;

  /** Whether the next element is the first one.  */
  private $first;

  /** Whether the sending is finished already.  */
  private $closed;

  /**
   * Construct it.
   * @param sender JsonSender object to use as parent.
   */
  public function __construct ($sender)
  {
    $this->sender = $sender;
    $this->closed = false;
    $this->first = true;

    $this->sender->sendRaw ("{");
  }

  /**
   * Finish the sending.
   */
  public function close ()
  {
    assert (!$this->closed);
    $this->sender->sendRaw ("}\n");
  }

  /**
   * Switch to next field.
   * @param key Next key used.
   */
  private function next ($key)
  {
    assert (!$this->closed);
    if (!$this->first)
      $this->sender->sendRaw (",\n");
    else
      $this->first = false;

    $this->sender->sendString ($key);
    $this->sender->sendRaw (": ");
  }

  /**
   * Send a string.
   * @param key Key to send with.
   * @param str String to send.
   */
  public function sendString ($key, $str)
  {
    $this->next ($key);
    $this->sender->sendString ($str);
  }

  /**
   * Send an integer.
   * @param key Key to send with.
   * @param val Integer to send.
   */
  public function sendInteger ($key, $val)
  {
    $this->next ($key);
    $this->sender->sendInteger ($val);
  }

  /**
   * Send a date-time value.
   * @param key Key to send with.
   * @param val Value to send.
   */
  public function sendDateTime ($key, $val)
  {
    $this->next ($key);
    $this->sender->sendDateTime ($val);
  }

  /**
   * Start sending an array.
   * @param key Key to send with.
   * @return Array sender.
   */
  public function sendArray ($key)
  {
    $this->next ($key);
    return $this->sender->sendArray ();
  }

}

?>
