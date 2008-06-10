<?
/*
Jorge - frontend for mod_logdb - ejabberd server-side message archive module.

Copyright (C) 2007 Zbigniew Zolkiewski

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

header("content-type: text/html; charset=utf-8");
// includes
require_once("func.php");
require_once("class.sessions.php");
require_once("class.ejabberd_xmlrpc.php");
require_once("class.db.php");
require_once("config.php");
require_once("lang.php");
// sessions and db connections
$sess = new session;

// RPC server redundancy
$rpc_host = check_rpc_server($rpc_arr,$rpc_port);

// rcp object
$ejabberd_rpc = new rpc_connector("$rpc_host","$rpc_port","$xmpp_host_dotted");

// db connect
db_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASS,MYSQL_NAME);

define(TOKEN,$sess->get('uid_l'));

// check user session
if (check_registered_user($sess,$ejabberd_rpc,$xmpp_host) !== true) { header("Location: index.php?act=logout"); exit; }

define(USER_ID,get_user_id(TOKEN,$xmpp_host));

if (!ctype_digit(USER_ID)) { print 'Service unavailable'; exit; }

// language
$lang=$sess->get('language');

// get POST data
$request=$_POST['a'];
$process_id=$_POST['req'];

// processing ...
if ($process_id=="1") {

	// processing favorites request

	// decompose data
	$variables = decode_url2($request,TOKEN,$url_key);
	$tslice = $variables[tslice];
	$talker = $variables[talker];
	$server = $variables[server];
	// validate
	if (validate_date($tslice) == "f" OR !ctype_digit($talker) OR !ctype_digit($server)) { 
			print '<div class="message">'.$ajax_error[$lang].'<br><a href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a></div>'; exit; 
		}

	$check=ch_favorite(USER_ID,$tslice,$talker,$server);
	if ($check=="f") {
		print '<div class="message">';
		print $ajax_error[$lang].'<br><a style="font-weight: normal;" href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a>';
		print '</div>'; 
		exit;
		}

	elseif($check=="1") {
		print '<div class="message">';
		print $fav_exist[$lang].'<br><a style="font-weight: normal;" href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a>';
		print '</div>'; exit;
		}
		
	elseif($check=="0") {
		$query="insert into jorge_favorites(owner_id,peer_name_id,peer_server_id,tslice) values(
			'".USER_ID."',
			'$talker',
			'$server',
			'$tslice')";

		if (mysql_query($query)==TRUE) {

				print '<div class="message">';
				print $fav_success[$lang].'<br><a style="font-weight: normal;" href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a>';
				print '</div>'; 
			}

			else

			{
				print '<div class="message">';
				print $ajax_error[$lang].'<br><a style="font-weight: normal;" href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a>';
				print '</div>';

			}

	}
	// terminate script
	exit;
}

if ($process_id=="2") {

	// remove first seq as this is always request_id...
	array_shift($_POST);
	// control
	$num=count($_POST);
	$i=0;
	while(array_keys($_POST)) {
		
		$i++;
		$enc_data=array_shift($_POST);
		// decompose data
		$variables = decode_url2($enc_data,TOKEN,$url_key);
		$tslice = $variables[tslice];
		$talker = $variables[talker];
		$server = $variables[server];
		// validate
		if (validate_date($tslice) == "f" OR !ctype_digit($talker) OR !ctype_digit($server)) { 
			print '<div class="message" style="width: 400px;">';
			print $ajax_error[$lang].'<br><a href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a></div>'; 
			exit; 
		}

		$query="delete from jorge_favorites where owner_id='".USER_ID."' and peer_name_id='$talker' and peer_server_id='$server' and tslice='$tslice'";
		mysql_query($query);
		
		// stop on any error
		if (mysql_errno()>0) {

			print '<div class="message" style="width: 400px;">';
			print $ajax_error[$lang].'<br><a style="font-weight: normal;" href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a>';
			print '</div><br>';
			exit;
		}

	}
	
	if (($i==$num)AND($num!=0)) {
		print '<div class="message" style="width: 400px;">';
		print $fav_removed[$lang].'<br><a style="font-weight: normal;" href="#" onClick="$(\'#fav_result\').fadeOut(\'slow\');" ><u>'.$fav_discard[$lang].'</u></a>';
		print '</div><br>';
		exit;
	}

}



?>
