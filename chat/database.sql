--  NameID, a namecoin based OpenID identity provider.
--  Copyright (C) 2014 by Daniel Kraft <d@domob.eu>
--
--  This program is free software: you can redistribute it and/or modify
--  it under the terms of the GNU Affero General Public License as published by
--  the Free Software Foundation, either version 3 of the License, or
--  (at your option) any later version.
--
--  This program is distributed in the hope that it will be useful,
--  but WITHOUT ANY WARRANTY; without even the implied warranty of
--  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--  GNU Affero General Public License for more details.
--
--  You should have received a copy of the GNU Affero General Public License
--  along with this program.  If not, see <http://www.gnu.org/licenses/>.

-- Database setup script for the chat.

-- Set some options.
SET GLOBAL sql_mode = 'STRICT_ALL_TABLES,NO_AUTO_VALUE_ON_ZERO';
SET CHARSET utf8;

-- Create table with chat history.
CREATE TABLE `chat_history`
  (`ID`       SERIAL,
   `User`     VARCHAR(255) NOT NULL,
   `Time`     DATETIME NOT NULL,
   `Message`  TEXT NOT NULL,
   PRIMARY KEY (`ID`));
