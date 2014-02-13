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

/* Some core routines for JS.  */

/**
 * Register a routine to fire with the "onload" routine.
 * @param handler Handler routine called.
 */
function registerOnLoad (handler)
{
  var oldHandler = window.onload;
  
  function doAll ()
    {
      if (oldHandler !== null)
        oldHandler ();
      handler ();
    }

  window.onload = doAll;
}

/**
 * Create an element with the given text as child node.
 * @param tag Element's tag.
 * @param text Text content to put into the tag.
 * @return The created element.
 */
function createWithText (tag, text)
{
  var textNode = document.createTextNode (text);
  var node = document.createElement (tag);
  node.appendChild (textNode);

  return node;
}

/**
 * Assert routine.
 * @param cond Condition that should be true.
 */
function assert (cond)
{
  if (!cond)
    {
      if (console && console.trace)
        console.trace ();

      alert ("Assertion failure.");
      throw "Assertion failure.";
    }
}
