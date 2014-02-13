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

/* Routine for performing an Ajax query with JSON content and response.  */

/**
 * Perform an Ajax query POST'ing the given data as form data to the server
 * and calling the handler when a response JSON is available.
 * @param page Page to query.
 * @param content Content to send, each object member is posted as form value.
 * @param handler Handler routine to call with response JSON.
 */
function queryAjax (page, content, handler)
{
  var req = new XMLHttpRequest ();

  var postStr = "";
  var first = true;
  for (var key in content)
    {
      key = encodeURIComponent (key);
      val = encodeURIComponent (content[key]);

      if (first)
        first = false;
      else
        postStr += "&";

      postStr += key + "=" + val;
    }

  function stateChanged ()
    {
      if (req.readyState === req.DONE)
        handler (req.response);
    }

  req.open ("POST", page, true);
  req.setRequestHeader ("Content-Type", "application/x-www-form-urlencoded");
  req.setRequestHeader ("Content-Length", postStr.length);
  req.setRequestHeader ("Connection", "close");
  req.onreadystatechange = stateChanged;
  req.responseType = "json";
  req.send (postStr);
}
