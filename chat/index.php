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

/* Driver file with page layout and basic action handling.  */

require_once ("lib/config.inc.php");

require_once ("lib/html.inc.php");
require_once ("lib/json.inc.php");
require_once ("lib/messages.inc.php");
require_once ("lib/request.inc.php");
require_once ("lib/session.inc.php");

require_once ("libauth/authenticator.inc.php");
require_once ("libauth/namecoin_rpc.inc.php");
require_once ("libauth/namecoin_interface.inc.php");

/* Construct basic worker classes.  */
$session = new Session ($sessionName);
$rpc = new HttpNamecoin ($rpcHost, $rpcPort, $rpcUser, $rpcPassword);
$nc = new NamecoinInterface ($rpc, $namePrefix);
$req = new RequestHandler ();
$html = new HtmlOutput ();
$msg = new MessageList ($html);

/* Set status which may be changed later to show different pages.  */
$status = "unknown";

/**
 * Try to perform a user login request.
 */
function tryLogin ()
{
  global $req, $session, $msg, $nc;
  global $status;
  global $serverUri;

  if ($status === "unknown" && $req->check ("action"))
    {
      $action = $req->getString ("action");
      switch ($action)
        {
        case "login":
          if ($req->getSubmitButton ("cancel"))
            {
              $status = "unknown";
              return;
            }
          assert ($req->getSubmitButton ("login"));

          $identity = $req->getString ("identity");
          $signature = $req->getString ("signature");

          $version = $req->getInteger ("version");
          if ($version !== 1)
            throw new RuntimeException ("Unsupported signature"
                                        ." version: $version");

          /* Redirect to loginForm in case an exception is thrown
             below (i. e., authentication fails).  */
          $status = "loginForm";

          $auth = new Authenticator ($nc, $serverUri);
          try
            {
              $res = $auth->login ($identity, $signature,
                                   $session->getNonce ());
              assert ($res === TRUE);
              $session->setUser ($identity);
            }
          catch (LoginFailure $err)
            {
              throw new UIError ($err->getMessage ());
            }

          /* No exception thrown means success.  */
          $msg->addMessage ("You have logged in successfully.");
          $status = "unknown";
          break;

        case "logout":
          $session->setUser (NULL);
          $msg->addMessage ("You have been logged out successfully.");
          $status = "unknown";
          break;

        default:
          // Ignore unknown action request.
          break;
        }
    }
}

/**
 * Check view parameter to see whether we should load a different page.
 */
function tryPages ()
{
  global $req, $status;

  if ($status === "unknown" && $req->check ("view"))
    {
      $view = $req->getString ("view");
      switch ($view)
        {
        case "login":
          $status = "loginForm";
          break;

        default:
          // Just leave status as unknown.
          break;
        }
    }
}

/**
 * Perform all page actions and choose a page to display.
 * @throws UIError in case of failed logins and such.
 */
function performActions ()
{
  global $session, $status;

  tryLogin ();
  tryPages ();

  /* If nothing matched, show default page.  */
  if ($status === "unknown")
    $status = "default";
}

/* Perform actions and catch errors.  */
$msg->runWithErrors ("performActions");

/* Set some global variables for the pages.  */
if ($status === "loginForm")
  $loginNonce = $session->generateNonce ($nonceBytes);
$loggedInUser = $session->getUser ();

/* Shut down worker classes.  msg and html have to be kept for later.  */
$req->close ();
$nc->close ();
$session->close ();

/* ************************************************************************** */

/* Set encoding to UTF-8.  */
header ("Content-Type: text/html; charset=utf-8");

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<title>NameID Chat Room</title>

<meta charset="utf-8" />

<link rel="stylesheet" type="text/css" href="layout/main.css" />
<link rel="stylesheet" type="text/css" href="layout/bootstrap.min.css" />

<script type="text/javascript" src="js/ajax.js"></script>
<script type="text/javascript" src="js/chat.js"></script>
<script type="text/javascript" src="js/core.js"></script>

<!-- Send stuff to JS.  -->
<script type="text/javascript">
<?php
$json = new JsonSender (false);
?>
var refreshIntv = <?php $json->sendInteger ($refreshTime); ?>;
var loggedInUser = null;
<?php
if ($loggedInUser !== NULL)
  {
    echo "loggedIn = ";
    $json->sendString ($loggedInUser);
  }
?>
<?php
$json->close ();
?>
</script>

</head>
<body>

  <div class="navbar navbar-fixed-top">
    <div class="navbar-inverse">
      <div class="navbar-inner">
        <a class="brand" href="?">NameID Chat</a>
        <ul class="nav">
<?php
$classHome = "";
$classLogin = "";
switch ($status)
  {
  case "default":
    $classHome = "active";
    break;

  case "loginForm":
    $classLogin = "active";
    break;

  default:
    // Ignore.
    break;
  }
?>
          <li class="<?php echo $classHome; ?>"><a href="?">Home</a></li>
<?php
if ($loggedInUser === NULL)
  echo "<li><a href='?view=login' class='$classLogin'>Login</a></li>\n";
else /* loggedInUser !== NULL */
  echo "<li><a href='?action=logout'>Logout</a></li>\n";
?>
          <li><a href="https://nameid.org/?view=faq">FAQs</a></li>
          <li><a href="https://nameid.org/?view=contact">Contact</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container">
<?php
$fromIndex = "yes";
include ("pages/$status.php");
?>
  </div>

  <hr />

  <p class="text-center">Copyright &copy; 2014
by <a href="http://www.domob.eu/">Daniel Kraft</a>.
<b>NameID</b> and this chat service are free software under the terms of the
<a href="https://www.gnu.org/licenses/agpl-3.0.html">AGPL v3</a>,
check out the <a href="https://gitorious.org/nameid/">source code</a>!</p>

  <p class="text-center">BTC: 1<b>Nameid</b>3brhZrbTN1M7t6afMAfVBiGioJT
| NMC: ND6yWYKZS9NnMPnfN6C6yZHG8gRe3r69GR</p>

</body>
</html>
<?php
$html->close ();
?>
