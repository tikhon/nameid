/*
    NameID, a namecoin based OpenID identity provider.
    Copyright (C) 2014 by Daniel Kraft <d@domob.eu>

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

/* JavaScript support routines for NameID login forms.  In particular,
   to implement the proper communication with the NameID login browser
   add-on (if it is installed).  */

/**
 * The main object encapsulating the NameID status.
 * @param url Site url to use for login.
 * @param nonce Login nonce.
 */
function NameId (url, nonce)
{
  this.url = url;
  this.nonce = nonce;
  this.apiEl = null;

  /* Set names used also in the add-on.  These must be the same
     as in the add-on.  */
  this.apiEvent = "nameid-login-event";
  this.apiRequestEvent = "nameid-login-requestAPI";
  this.apiElement = "nameid-login-eventTarget";
}

NameId.prototype =
  {

    /**
     * Try to activate the login add-on on the current page.  This
     * must be called before the "signChallenge" method can be attempted.
     * It may fail if the add-on is not available or the user denies
     * trust for the current page.
     * @return True iff the add-on was successfully activated.
     */
    requestApi: function ()
    {
      if (this.apiEl !== null)
        throw "NameID login API is already active.";

      var evt = document.createEvent ("Events");
      evt.initEvent (this.apiRequestEvent, true, false);
      document.dispatchEvent (evt);

      this.apiEl = document.getElementById (this.apiElement);
      return this.hasApi ();
    },

    /**
     * Check if the add-on is active.
     * @return True iff the add-on was already activated.
     */
    hasApi: function ()
    {
      return (this.apiEl !== null);
    },

    /**
     * Request to sign the challenge message.
     * @param id Identity to log in.
     * @return The signed challenge message or null if the sign failed.
     * @throws Error in case the API is not yet activated.
     */
    signChallenge: function (id)
    {
      var data =
        {
          "version": 1,
          "method": "signChallenge",
          "url": this.url,
          "nonce": this.nonce,
          "identity": id
        };
      var res = this.callApi (data);

      if (res.success)
        return res.signature;
      return null;
    },

    /**
     * Call the API with the given JSON data.
     * @param data Data to pass to the add-on as JSON object.
     * @return The resulting data object.
     * @throws Error in case the API is not yet activated.
     */
    callApi: function (data)
    {
      if (this.apiEl === null)
        throw "NameID login API is not active.";

      var str = JSON.stringify (data);
      this.apiEl.setAttribute ("data", str);

      var evt = document.createEvent ("Events");
      evt.initEvent (this.apiEvent, true, false);
      this.apiEl.dispatchEvent (evt);

      var resStr = this.apiEl.getAttribute ("result");
      return JSON.parse (resStr);
    },

    /**
     * Construct the challenge message for a given ID.
     * @param id The user entered ID.
     * @return The full challenge message.
     */
    getChallenge: function (id)
    {
      /* This must of course be in sync with the PHP code as well as
         the "ordinary" page JavaScript!  */

      var fullId = this.url + "?name=" + encodeURIComponent (id);
      var msg = "login " + fullId + " " + this.nonce;

      return msg;
    }

  };
