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

/* Class to handle the JS front-end stuff for the chat.  */

/**
 * Class handling the chat front-end.
 * @param history Where to show the chat history.
 * @param ajaxRoot Root of Ajax query URIs.
 */
function Chat (history, ajaxRoot)
{
  this.history = history;
  this.ajaxRoot = ajaxRoot;

  this.lastId = null;
}

/**
 * Process a server response array with messages.
 * @param msg Messages array.
 */
Chat.prototype.handleMessages = function (msg)
{
  if (msg.length === 0)
    return;

  for (var i = 0; i < msg.length; ++i)
    {
      assert (this.lastId === null || msg[i].id > this.lastId);
      var dt = createWithText ("dt", msg[i].name + ":");
      var dd = createWithText ("dd", msg[i].message);
      if (msg[i].name.length > 12)
        {
          dt.className = "long";
          dd.className = "long";
        }
      if (msg[i].message.length === 0)
        {
          dt.className = "empty";
          dd.className = "empty";
        }
        
      this.history.appendChild (dt);
      this.history.appendChild (dd);
    }

  this.history.lastChild.scrollIntoView ();
  this.lastId = msg[msg.length - 1].id;
}

/**
 * Set up automatic refreshing.
 * @param intv Refresh interval.
 */
Chat.prototype.setupRefresh = function (intv)
{
  var me = this;
  function updateIt ()
    {
      function handler (data)
        {
          me.handleMessages (data.messages);
        }

      var content = {};
      if (me.lastId !== null)
        content.since = me.lastId;

      queryAjax (me.ajaxRoot + "messages.php", content, handler);
    }

  registerInterval (intv, updateIt);
  updateIt ();
}

/**
 * Submit a chat message.
 * @param msg Message to submit.
 */
Chat.prototype.submitMessage = function (msg)
{
  var content = {};
  if (this.lastId !== null)
    content.since = this.lastId;
  content.message = msg;

  var me = this;
  function handler (data)
    {
      me.handleMessages (data.messages);
    }

  queryAjax (me.ajaxRoot + "submit.php", content, handler);
}
