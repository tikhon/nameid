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

/* Main page of the chat.  */

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

</head>
<body>

  <div class="navbar navbar-fixed-top">
    <div class="navbar-inverse">
      <div class="navbar-inner">
        <a class="brand" href="?">NameID Chat</a>
        <ul class="nav">
          <li class="active"><a href="?">Home</a></li>
          <li><a href="login.php">Login</a></li>
          <li><a href="https://nameid.org/?view=faq">FAQs</a></li>
          <li><a href="https://nameid.org/?view=contact">Contact</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container">
    <h1>NameID Chat Room</h1>

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

    <dl class="well well-small" id="chatHistory">
      <dt>domob:</dt>
      <dd>Dies ist eine Testnachricht.</dd>

      <dt>domob:</dt>
      <dd>Dies ist eine Testnachricht.</dd>

      <dt class="long">ganz, ganz, ganz langer Name:</dt>
      <dd class="long">Und hier noch eine ganz, ganz, ganz, ganz lange
Testnachricht, die auch hier noch weiter geht und erst jetzt langsam
zu Ende ist.</dd>

      <dt>domob:</dt>
      <dd>Dies ist eine Testnachricht.</dd>

      <dt class="long">ganz, ganz, ganz langer Name:</dt>
      <dd class="long">Und hier noch eine ganz, ganz, ganz, ganz lange
Testnachricht, die auch hier noch weiter geht und erst jetzt langsam
zu Ende ist.</dd>

      <dt class="long">ganz, ganz, ganz langer Name:</dt>
      <dd class="long">Und hier noch eine ganz, ganz, ganz, ganz lange
Testnachricht, die auch hier noch weiter geht und erst jetzt langsam
zu Ende ist.</dd>
    </dl>

    <form id="submitForm">
      <div class="input-append">
        <input type="text" id="message" name="message" />
        <button class="btn btn-primary" type="submit">Send</button>
      </div>
    </form>
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
