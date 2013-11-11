<?php
/*##############################################################################
# Project:    Labels - Extremadura High Schools
# Purpose:    Module to get groups of students
# Date:       24-Sep-2010.
# Ver.:       29-Sep-2010.
# Copyright:  2010 - Manu Mora Gordillo       <manuito @nospam@ gmail.com>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
# 
############################################################################## */

	$user="cn=".$_GET["userLDAP"].",ou=People,dc=instituto,dc=extremadura,dc=es";

	$conex = ldap_connect("ldaps://".$_GET["hostLDAP"], 636);
	if (!$conex){
		$responce->msg = "noConnect";
		die(json_encode($responce)); 
	}

	if (!@ldap_set_option($conex, LDAP_OPT_PROTOCOL_VERSION, 3)){
		$responce->msg = "noProtocol";
		die(json_encode($responce)); 
	}

   if (!@ldap_bind($conex, $user, $_GET["password"])){
		$responce->msg = "noPassword";
		die(json_encode($responce)); 
	}

	$dn = "ou=Group,dc=instituto,dc=extremadura,dc=es";
	$fields = array("cn","grouptype","memberuid");
	$sr=ldap_search($conex, $dn, "cn=*", $fields);
	$result = ldap_get_entries($conex, $sr);
	$groups = array();

	for($i=0;$i<count($result);$i++){
		if($result[$i]["grouptype"][0]=="school_class"){
			$groups[] = $result[$i]["cn"][0];
		}
	}

	if(count($groups)==0) die("noGroups");

	sort($groups);

	for($j=0;$j<count($groups);$j++)
		$responce->groups[]=$groups[$j];

	echo json_encode($responce); 
?>
