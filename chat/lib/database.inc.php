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

/* Database connection wrapper.  */

/**
 * Database connection class.
 */
class Database
{

  /** Internal handle.  */
  private $db;

  /** Selected database name.  */
  private $selectedName;

  /**
   * Construct it given the connection settings.
   * @param host Database host.
   * @param user Database user.
   * @param pass Database password.
   * @param name Database name to select.
   * @throws RuntimeException in case the connection fails.
   */
  public function __construct ($host, $user, $pass, $name)
  {
    $this->db = @mysql_connect ($host, $user, $pass);
    if (!$this->db)
      throw new RuntimeException ("Error connecting to database:\n"
                                  .mysql_error ($this->db));

    $this->select ($name);

    /* Send options.  */
    $this->query ("SET SQL_MODE = 'STRICT_ALL_TABLES,NO_AUTO_VALUE_ON_ZERO'");
    $this->query ("SET CHARSET utf8");
  }

  /**
   * Select a database name.
   * @param name DB name to select.
   * @throws RuntimeException in case this fails.
   */
  private function select ($name)
  {
    if (!mysql_select_db ($name, $this->db))
      throw new RuntimeException ("Couldn't select database '$name':\n"
                                  .mysql_error ($this->db));
    $this->selectedName = $name;
  }

  /**
   * Close the connection.
   */
  public function close ()
  {
    mysql_close ($this->db);
    $this->db = NULL;
  }

  /**
   * Perform a query.
   * @param sql Query to perform.
   * @throws RuntimeException in case of failure.
   */
  public function query ($sql)
  {
    $result = mysql_query ($sql, $this->db);
    if (!$result)
      throw new RuntimeException ("Error in database query:\n"
                                  .mysql_error ($this->db));

    return $result;
  }

  /**
   * Perform an INSERT query and return the generated AUTOID.
   * @param sql INSERT query to execute.
   * @return ID of inserted record.
   */
  public function insertQuery ($sql)
  {
    $res = $this->query ($sql);
    return mysql_insert_id ($this->db);
  }

  /**
   * Query and expect exactly one result row.
   * @param sql Query to do.
   */
  public function queryOne ($sql)
  {
    $res = $this->query ($sql);
    if ($this->isEmpty ($res))
      throw new RuntimeException ("No result found, expected one.");

    $row = $this->fetchRow ($res);
    $row2 = $this->fetchRow ($res);
    if ($row2)
      throw new RuntimeException ("Found more than one result.");

    $this->free ($res);

    return $row;
  }

  /**
   * Get number of result rows.
   * @param res Database result.
   * @return Number of rows in the result.
   */
  public function getNumRows ($res)
  {
    return mysql_num_rows ($res);
  }

  /**
   * Check whether a result is empty.
   * @param res Database result.
   * @return True iff there are no result rows.
   */
  public function isEmpty ($res)
  {
    return mysql_num_rows ($res) === 0;
  }

  /**
   * Fetch a result row.
   * @param res Database result.
   * @return Row as associative array.
   */
  public function fetchRow ($res)
  {
    return mysql_fetch_assoc ($res);
  }

  /**
   * Free a result set.
   * @param res Database result.
   */
  public function free ($res)
  {
    mysql_free_result ($res);
  }

  /**
   * Escape a string for the DB.
   * @param str String to escape.
   * @return Escaped string.
   */
  public function escapeString ($str)
  {
    $str = mysql_real_escape_string ($str);
    return "'$str'";
  }

}

?>
