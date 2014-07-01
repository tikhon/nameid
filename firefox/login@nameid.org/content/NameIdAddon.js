/*
    NameID, a namecoin based OpenID identity provider.
    Copyright (C) 2013-2014 by Daniel Kraft <d@domob.eu>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/* Main driver object for the addon.  */

Components.utils.import ("chrome://nameid-login/content/Namecoind.js");
Components.utils.import ("chrome://nameid-login/content/TrustManager.js");
Components.utils.import ("chrome://nameid-login/content/Utils.js");
Components.utils.import ("resource://gre/modules/Services.jsm");

var EXPORTED_SYMBOLS = ["NameIdAddon"];

/**
 * The main object encapsulating the addon's state.
 * @param pref Preferences handler to use.
 */
function NameIdAddon (pref)
{
  this.pref = pref;
  this.trust = new TrustManager (pref);

  /* Set constants for API event and element ID.  We have two events:
     the "apiEvent" is fired on the "apiElement" when the extension is
     already enabled on the page, and does the real work.  The "apiRequestEvent"
     is fired on the document itself to request that the extension is enabled
     on the page in the first place (possibly prompting a "Trust?" dialog).  */
  this.apiEvent = "nameid-login-event";
  this.apiRequestEvent = "nameid-login-requestAPI";
  this.apiElement = "nameid-login-eventTarget";
}

NameIdAddon.prototype =
  {

    /**
     * Initialise the observer to "start" this addon.
     */
    register: function ()
    {
      Services.obs.addObserver (this, "document-element-inserted", false);
    },

    /**
     * Stop the observer on shutdown.
     */
    unregister: function ()
    {
      Services.obs.removeObserver (this, "document-element-inserted");
      this.trust.close ();
      this.trust = null;
    },

    /**
     * Observe events, in particular loads of new documents for which we
     * want to enable the apiRequestEvent.
     * @param subject Subject of the event.
     * @param topic Topic of the event.
     * @param data Further data.
     */
    observe: function (subject, topic, data)
    {
      if (topic !== "document-element-inserted")
        return;

      log ("Observing page load: " + subject.URL);
      var me = this;
      function handler (evt)
        {
          me.requestApi (evt.target);
        }
      subject.addEventListener (this.apiRequestEvent, handler, false, true);
      log ("Registered API-request event listener on document.");
    },

    /**
     * Handle a request made by the page to enable the API.  This shows
     * a trust dialog (if the page is not whitelisted) and, if positive,
     * inserts the actual event target element.
     * @param doc The page's document.
     */
    requestApi: function (doc)
    {
      /* If there is already an API element, just return.  */
      var apiEl;
      apiEl = doc.getElementById (this.apiElement);
      if (apiEl !== null)
        return;

      /* Check trust.  */
      var ok = this.trust.decide (doc.URL);
      if (!ok)
        return;

      /* Insert api element.  */
      var body = doc.getElementsByTagName ("body");
      if (body.length !== 1)
        {
          log ("Could not find the page's <body>.");
          return;
        }
      else
        body = body[0];
      apiEl = doc.createElement ("div");
      body.appendChild (apiEl);
      apiEl.id = this.apiElement;

      /* Register custom event handler.  */
      var me = this;
      function handler ()
        {
          var data = apiEl.getAttribute ("data");
          var res;
          try
            {
              res = me.handleCall (doc.URL, JSON.parse (data));
              res.success = true;
            }
          catch (err)
            {
              Services.prompt.alert (null, "NameID Login Error", err);
              res = {"error": err, "success": false};
            }
          apiEl.setAttribute ("result", JSON.stringify (res));
        }
      apiEl.addEventListener (this.apiEvent, handler, false, true);
      log ("Registered API event listener.");
    },

    /**
     * Handle an API call with the given data.
     * @param url Current document's URL.
     * @param data JSON-parsed call data.
     * @return Resulting data to be returned to the caller.
     */
    handleCall: function (url, data)
    {
      log ("API-Call: " + JSON.stringify (data));
      if (data.version !== 1)
        throw "Unsupported API version: " + data.version;

      switch (data.method)
        {
        case "signChallenge":
          var signature = this.signChallenge (url, data.nonce, data.identity);
          return {"signature": signature};

        default:
          throw "Unsupported method: " + data.method;
        }

      /* Should not happen.  */
      throw "No method matched.";
    },

    /**
     * Sign a challenge message.
     * @param url The document login URL.
     * @param nonce The login nonce.
     * @param id The ID as which to log in.
     * @return The signed challenge message.
     */
    signChallenge: function (url, nonce, id)
    {
      var msg = this.getChallenge (url, nonce, id);
      log ("Attempting to sign challenge: " + msg);

      /* Custom error handler that understands some error codes.  */
      function errHandler (err)
        {
          switch (err.code)
            {
            case -4:
              throw "The specified name 'id/" + id + "' is not registered.";

            case -14:
              throw "The provided passphrase is incorrect.";

            default:
              break;
            }

          return false;
        }

      var nc = new Namecoind (this.pref);

      var data = nc.executeRPC ("name_show", ["id/" + id], errHandler);
      var addr = data.address;
      log ("Found address for name 'id/" + id + "': " + addr);

      /* Try to find an address that is allowed to sign and also
         contained in the user's wallet.  */

      var myAddr = null;

      res = nc.executeRPC ("validateaddress", [addr]);
      if (res.ismine)
        myAddr = addr;
      else
        {
          log ("Looking for signer in value:\n" + data.value);

          var value;
          try
            {
              value = JSON.parse (data.value);
            }
          catch (exc)
            {
              /* JSON parse error, assume no signers.  */
              value = {};
            }

          var arr;
          if (typeof value.signer === "string")
            arr = [value.signer];
          else if (Array.isArray (value.signer))
            arr = value.signer;
          else
            arr = [];
            
          for (var i = 0; i < arr.length; ++i)
            {
              res = nc.executeRPC ("validateaddress", [arr[i]]);
              if (res.ismine)
                {
                  myAddr = arr[i];
                  log ("Found available signer: " + myAddr);
                  break;
                }
            }
        }

      if (myAddr === null)
        throw "You are not allowed to sign for 'id/" + id + "'.";

      /* Try to sign the challenge with the address found.  */

      res = nc.executeRPC ("getinfo", []);
      var didUnlock = false;
      if (res.unlocked_until !== undefined && res.unlocked_until === 0)
        {
          var title = "Unlock Namecoin Wallet";
          var text = "Please provide the password to temporarily unlock"
                     + " your namecoin wallet:";

          var pwd = {};
          var btn = Services.prompt.promptPassword (null, title, text, pwd, 
                                                    null, {});
          /* Abort if cancel was clicked.  */
          if (!btn)
            {
              log ("Wallet unlock cancelled by user.");
              nc.close ();
              return null;
            }

          nc.executeRPC ("walletpassphrase", [pwd.value, 10], errHandler);
          didUnlock = true;
        }

      var signature = nc.executeRPC ("signmessage", [myAddr, msg]);
      log ("Successfully provided signature.");

      if (didUnlock)
        nc.executeRPC ("walletlock", []);

      nc.close ();
      return signature;
    },

    /**
     * Construct the challenge message for a given ID.
     * @param url Login page url.
     * @param nonce Login nonce.
     * @param id The user entered ID.
     * @return The full challenge message.
     */
    getChallenge: function (url, nonce, id)
    {
      /* This must of course be in sync with the PHP code as well as
         the "ordinary" page JavaScript!  */

      var fullId = url + "?name=" + encodeURIComponent (id);
      var msg = "login " + fullId + " " + nonce;

      return msg;
    }

  };
