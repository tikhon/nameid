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

/* Default page with chat.  */

if (!isset ($fromIndex) || $fromIndex !== "yes")
  die ("Invalid page load.\n");

?>

<script type="text/javascript">

registerOnLoad (function ()
  {
    var chat = new Chat (document.getElementById ("chatHistory"), "ajax/");
    chat.setupRefresh (refreshIntv);

    function interceptSubmit (evt)
      {
        evt.preventDefault ();

        var field = document.getElementById ("message");
        var val = field.value;
        field.value = "";
        field.focus ();
        
        chat.submitMessage (val);
      }
    var form = document.getElementById ("submitForm");
    form.addEventListener ("submit", interceptSubmit, false);
  });

</script>

<h1>NameID Chat Room</h1>

<?php
$msg->finish ();
?>

<p>This is a simple
<a href="https://en.wikipedia.org/wiki/Ajax_%28programming%29">Ajax</a>-based
chat service which uses <a href="https://nameid.org/">NameID</a> as login
method so that anyone with a
<a href="https://github.com/namecoin/wiki/wiki/Identity">Namecoin identity</a>
can log in and chat, and as long as you trust this server, you can be assured
that messages are indeed from the rightful owners of the corresponding
<code>id/</code> names.  You can also look at the
<a href="https://gitorious.org/nameid/">source code</a> and consider
this an example for how to use <b>NameID</b> logins in your
own project.</p>

<dl class="well well-small" id="chatHistory"></dl>

<?php
if ($loggedInUser !== NULL)
  {
?>
    <form id="submitForm">
      <div class="input-prepend input-append">
<?php
$name = "$namePrefix/$loggedInUser:";
$name = $html->escape ($name);
echo "<label for='message' class='add-on'>$name</label>\n";
?>
        <input type="text" id="message" name="message" />
        <button class="btn btn-primary" type="submit">Send</button>
      </div>
    </form>
<?php
  }
else /* loggedInUser === NULL */
  {
?>
    <p>Please <a href="?view=login">log in</a> in order to chat.</p>
<?php
  } /* loggedInUser? */
?>
