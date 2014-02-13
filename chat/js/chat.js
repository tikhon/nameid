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
 */
function Chat (history)
{
  this.history = history;
  this.lastId = 0;
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
      assert (msg[i].id > this.lastId);
      var dt = createWithText ("dt", msg[i].name);
      var dd = createWithText ("dd", msg[i].message);
      if (msg[i].name.length > 12)
        {
          dt.className = "long";
          dd.className = "long";
        }
        
      this.history.appendChild (dt);
      this.history.appendChild (dd);
    }
  this.lastId = msg[msg.length - 1].id;
}
