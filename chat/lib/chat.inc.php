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

/* Utility routines for chat handling.  */

require_once ("database.inc.php");
require_once ("json.inc.php");

/**
 * Class to handle the chat.
 */
class Chat
{

  /** Database handle to use.  */
  private $db;

  /**
   * Construct it given the DB handle.
   * @param db Database handle.
   */
  public function __construct (Database $db)
  {
    $this->db = $db;
  }

  /**
   * Close at the end.
   */
  public function close ()
  {
    // Nothing to be done.
  }

  /**
   * Insert a new chat message.
   * @param name Name of sending user.
   * @param msg Chat message.
   */
  public function submitMessage ($name, $msg)
  {
    $name = $this->db->escapeString ($name);
    $msg = $this->db->escapeString ($msg);

    $this->db->query ("INSERT INTO `chat_history`"
                      ."  (`User`, `Time`, `Message`) VALUES"
                      ."  ($name, NOW(), $msg)");
  }

  /**
   * Send chat messages since a given one to the JSON sender.
   * @param sender JSON array sender object.
   * @param since ID of last object *not* to send any more.
   */
  public function queryNew (JsonArraySender $sender, $since = 0)
  {
    global $maximalMessages;

    $since = (int) $since;
    $res = $this->db->query ("SELECT `ID`, `User`, `Time`, `Message`"
                             ." FROM `chat_history`"
                             ." WHERE `ID` > $since"
                             ." ORDER BY `ID` DESC"
                             ." LIMIT $maximalMessages");
    $arr = array ();
    while ($row = $this->db->fetchRow ($res))
      array_push ($arr, $row);
    $this->db->free ($res);

    /* Send them in reverse order.  We have to query descending above so that
       the LIMIT clause gives us the latest messages, but we want to send them
       to the client in increasing order.  */
    for ($i = count ($arr) - 1; $i >= 0; --$i)
      {
        $obj = $sender->addObject ();
        $obj->sendInteger ("id", $arr[$i]["ID"]);
        $obj->sendDateTime ("time", $arr[$i]["Time"]);
        $obj->sendString ("name", $arr[$i]["User"]);
        $obj->sendString ("message", $arr[$i]["Message"]);
        $obj->close ();
      }
  }

}

?>
