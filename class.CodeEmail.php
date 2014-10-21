<?php 
/******************************************************************************
 * CodeEmail.php
 *
 * This class provides a mean to encode and decode email addresses to be used
 * in URL.  Using this coding scheme can be useful to prevent web robots to
 * harvest your php web forms.
 * 
 * File index.php provides detailed examples.
 *
 * See file CHANGES for version history.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA,
 * or go to http://www.gnu.org/copyleft/gpl.html
 *
 * See file LICENSE for full license text.
 *
 * Copyright © 2002 Antoine Delvaux
 *****************************************************************************/

/******************************************************************************
 * Class:	CodeEmail
 * Function Listing:
 *	Public methods :
 *		CodeEmail($address)
 *		DecodeEmail($address)
 *	Private methods :
 *****************************************************************************/
class CodeEmail
{
  //---Static
  var $version		= "1.0";
  var $ver_str          = "v1.0 by <antoine(at)delvaux(dot)net>";

  //---Public Properties

  var $decodedAddress	= "";	// string of the clear address
  var $encodedAddress	= "";	// string of the hidden address

  //---Private Properties

  //---Methods

  /****************************************************************************
   * CodeEmail($addr)
   *
   * Desc:	constructor
   * Type:	public
   * Args:	$addr : the address (encoded or unencoded)
   * Returns:	the object created
   ***************************************************************************/
  function CodeEmail($addr) {
    if(strpos($addr,'|')>0 && strpos($addr,'#')>0) {
      //--We have an encoded address
      $this->encodedAddress = $addr;
      //--first replace '|' by '@' and '#' by '.'
      $addr = str_replace('|','@',str_replace('#','.',$addr));
      //--then reverse string
      $this->decodedAddress = strrev($addr);
    } else {
      //--We have an unencoded address
      $this->decodedAddress = $addr;
      //--first reverse string
      $addr = strrev($addr);
      //--then replace '@' by '|' and '.' by '#'
      $this->encodedAddress = str_replace('@','|',str_replace('.','#',$addr));
    }
  }
  
}
?>
