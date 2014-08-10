<?php
/*
    NameID, a namecoin based OpenID identity provider.
    Copyright (C) 2013-2014 by Daniel Kraft <d@domob.eu>

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

/* Page layout to show the identity page.  */

if (!isset ($fromIndex) || $fromIndex !== "yes")
  die ("Invalid page load.\n");

/* Sanitise a link.  This makes sure that it starts with 'http://'
   or 'https://', in order to prevent in particular javascript: links
   to be inserted by an attacker.  */
function sanitiseLink ($href)
{
  $allowed = array ("http://", "https://");
  foreach ($allowed as $prefix)
    if (substr ($href, 0, strlen ($prefix)) === $prefix)
      return $href;

  return "http://$href";
}

/* ************************************************************************** */
/* Field handlers.  */

/**
 * Basic class for a field handler.
 */
abstract class FieldHandler
{

  /** Label to display.  */
  private $label;

  /**
   * Construct it.  It only sets the label.  Information about the
   * actual data to display is handled by subclasses.
   * @param label The label to display.
   */
  public function __construct ($label)
  {
    $this->label = $label;
  }

  /**
   * Print out HTML for this field in the <dl>.
   * @param html HtmlOutput object.
   * @param data JSON data of the id value.
   */
  public function output (HtmlOutput $html, $data)
  {
    $content = $this->processContent ($html, $data);
    if ($content !== NULL)
      {
        echo "<dt>" . $html->escape ($this->label) . "</dt>\n";
        echo "<dd>$content</dd>\n";
      }
  }

  /**
   * Subclasses must implement extraction of the "content" (as HTML text)
   * to display.  NULL should be returned if this field is not served
   * by the given data.
   * @param html HtmlOutput object to use.
   * @param data JSON data of the id value.
   * @return Processed content.
   */
  abstract protected function processContent (HtmlOutput $html, $data);

}

/**
 * Basic field that just prints the literal value.
 */
class BasicField extends FieldHandler
{

  /** Key in the JSON data to extract.  */
  protected $key;

  /**
   * Construct the field.
   * @param label The label to display.
   * @param key The key for data extraction.
   */
  public function __construct ($label, $key)
  {
    parent::__construct ($label);
    $this->key = $key;
  }

  /**
   * Get the content to show.
   * @param html HtmlOutput object to use.
   * @param data JSON data of the id value.
   * @return Processed content.
   */
  protected function processContent (HtmlOutput $html, $data)
  {
    if (!isset ($data->{$this->key}))
      return NULL;

    return $html->escape ($data->{$this->key});
  }

}

/**
 * Field for website.
 */
class WebsiteField extends BasicField
{

  /**
   * Get the content to show.
   * @param html HtmlOutput object to use.
   * @param data JSON data of the id value.
   * @return Processed content.
   */
  protected function processContent (HtmlOutput $html, $data)
  {
    if (!isset ($data->{$this->key}))
      return NULL;

    $val = $data->{$this->key};
    $text = $html->escape ($val);
    $link = $html->escape (sanitiseLink ($val));

    return "<a href='$link'>$text</a>";
  }

}

/**
 * Field for email or crypto-addresses.  We create a link and prepend
 * a given "protocol" to it.
 */
class ProtocolledField extends BasicField
{

  /** The protocol to prepend.  */
  private $protocol;

  /**
   * Construct the field.
   * @param label The label to display.
   * @param key The key for data extraction.
   * @param protocol The protocol to prepent.
   */
  public function __construct ($label, $key, $protocol)
  {
    parent::__construct ($label, $key);
    $this->protocol = $protocol;
  }

  /**
   * Get the content to show.
   * @param html HtmlOutput object to use.
   * @param data JSON data of the id value.
   * @return Processed content.
   */
  protected function processContent (HtmlOutput $html, $data)
  {
    if (!isset ($data->{$this->key}))
      return NULL;

    $proto = $html->escape ($this->protocol);
    $val = $html->escape ($data->{$this->key});
    return "<a href='$proto:$val'>$val</a>";
  }

}

/**
 * Field for GPG fingerprint.
 */
class GPG_Field extends BasicField
{

  /**
   * Get the content to show.
   * @param html HtmlOutput object to use.
   * @param data JSON data of the id value.
   * @return Processed content.
   */
  protected function processContent (HtmlOutput $html, $data)
  {
    if (!isset ($data->{$this->key}))
      return NULL;

    $val = $data->{$this->key};
    if (!is_object ($val))
      return NULL;
    if (!isset ($val->v) || $val->v !== "pka1")
      return NULL;
    if (!isset ($val->fpr))
      return NULL;

    $formatted = $this->formatGPG ($val->fpr);
    if ($formatted === NULL)
      return NULL;

    $href = NULL;
    $content = "";
    if (isset ($val->uri))
      {
        $href = $val->uri;
        $href = $html->escape (sanitiseLink ($href));
        $content .= "<a href='$href'>";
      }
    $content .= $formatted;
    if ($href !== NULL)
      $content .= "</a>";

    return $content;
  }

  /**
   * Helper routine to check a GPG fingerprint and format it nicely.  This
   * makes all hex characters upper case, removes spaces and colons, and
   * puts back spaces to group the characters in a uniform way.  Finally,
   * it formats the final digits (as in the usual GPG fingerprint key ID)
   * in <strong> tags.
   * @param fpr The fingerprint to format.
   * @return The processed fingerprint.
   */
  private function formatGPG ($fpr)
  {
    $fpr = strtoupper ($fpr);
    $fpr = preg_replace ("/[ :]/", "", $fpr);

    if (!preg_match ("/^[0-9A-F]{40}$/", $fpr))
      return NULL;

    $fpr = preg_replace ("/(.{4})/", "$1 ", $fpr);
    $fpr = preg_replace ("/(.{4} .{4}) $/", "<strong>$1</strong>", $fpr);

    return $fpr;
  }

}


/* ************************************************************************** */

?>

<h1><?php echo $html->escape ("$namePrefix/$identityName"); ?></h1>

<?php

$fields = array (new BasicField ("Real Name", "name"),
                 new BasicField ("Nickname", "nick"),
                 new WebsiteField ("Website", "website"),
                 new ProtocolledField ("Email", "email", "mailto"),
                 new GPG_Field ("OpenPGP", "gpg"),
                 new BasicField ("Bitmessage", "bitmessage"),
                 new BasicField ("XMPP", "xmpp"),
                 new ProtocolledField ("Bitcoin", "bitcoin", "bitcoin"),
                 new ProtocolledField ("Namecoin", "namecoin", "namecoin"),
                 new ProtocolledField ("Huntercoin", "huntercoin",
                                       "huntercoin"),
                 new ProtocolledField ("Litecoin", "litecoin", "litecoin"),
                 new BasicField ("Peercoin", "ppcoin"));

if ($identityPage)
  {
?>

<p>The Namecoin identity
<code><?php echo $html->escape ("$namePrefix/$identityName"); ?></code> has some
public profile information registered:</p>

<?php
    echo "<dl class='dl-horizontal'>\n";
    foreach ($fields as $f)
      $f->output ($html, $identityPage);
    echo "</dl>\n";
  }
else
  {
?>
<p>There's no public information for
<code><?php echo $html->escape ("$namePrefix/$identityName"); ?></code>.</p>
<?php
  }
?>
