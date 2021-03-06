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

if (__FILE__==$_SERVER['SCRIPT_FILENAME']) {

	header("Location: index.php?act=logout");
	exit;

}


// well if we dont know in what language to talk, we cant show anything, so bye bye...
if ($lang!="pol" && $lang!="eng") { header("Location: index.php?act=logout"); exit; }

// control check - if global archivization is enabled...
if ($sess->get('enabled') == "f") { header ("Location: not_enabled.php"); }

// escape
$link_sw=mysql_escape_string($_GET['a']);

// number of my links saved...
$my_links_count=do_sel_quick("select count(id_link) from jorge_mylinks where owner_id='$user_id' and ext is NULL");

// number of items in trash
$tr_n=do_sel_quick("select count(*) from pending_del where owner_id='$user_id'");

// get preferences for saving
$pref_id=$_GET['set_pref'];
$pref_value=$_GET['v'];

// save preferences
if ($_GET['set_pref']) {

	//validate
	if (!ctype_digit($pref_id)) { unset($pref_id); }
	if (!ctype_digit($pref_value)) { unset($pref_value); }
	// what to set
	// view and language preferences are stored for now.
	if ($pref_id==="1" OR $pref_id==="2") 
		{ 
			if($pref_value==="1" OR $pref_value==="2") 
				{ 
					save_pref($user_id,$pref_id,$pref_value);
					if ($pref_id==="1") {
						$sess->set('view_type',$pref_value);
						}
					if ($pref_id==="2") {
						if ($pref_value=="1") { $s_lang="pol"; } else { $s_lang="eng"; }
						$sess->set('language',$s_lang);
						}
				} 
		}

}

// get preferences, if not set, fallback to standard view.
$view_type=$sess->get('view_type');
if ($view_type=="1") { $view_type="main.php"; } elseif($view_type=="2") { $view_type="calendar_view.php"; }

// this is menu. not nice but works ;)
if (preg_match("/search_v2.php/i",$location)) 

	{ 
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<b>'.$menu_item_search[$lang].'</b>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		$search_loc=1;
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }
	}
	elseif(preg_match("/main.php/i",$location))
	{
		$menu_main='<b>'.$menu_item_browser[$lang].'</b>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }

	}
	elseif(preg_match("/my_links.php/i",$location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<b>'.$menu_item_links[$lang].' ('.$my_links_count.') </b>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }


	}
	elseif(preg_match("/help.php/i",$location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }

	}
	elseif(preg_match("/contacts.php/i", $location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<b>'.$menu_item_contacts[$lang].'</b>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php">Stats</a>'; }

	}
	elseif(preg_match("/stats.php/i", $location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <b>Stats</b></a>'; }

		
	}
	elseif(preg_match("/logger.php/i", $location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<b>'.$menu_item_logs[$lang].'</b>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }

		
	}
	elseif(preg_match("/trash.php/i", $location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<b>'.$menu_item_trash[$lang].'('.$tr_n.')</b>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }

		
	}
	elseif(preg_match("/calendar_view.php/i", $location))
	{
		$menu_main='<b>'.$menu_item_browser[$lang].'</b>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }

		
	}
	elseif(preg_match("/chat_map.php/i", $location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<b>'.$menu_item_map[$lang].'</b>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }

		
	}
	elseif(preg_match("/settings.php/i", $location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<a class="mmenu" href="favorites.php">'.$menu_item_fav[$lang].'</a>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }
	}
	elseif(preg_match("/favorites.php/i", $location))
	{
		$menu_main='<a class="mmenu" href="'.$view_type.'">'.$menu_item_browser[$lang].'</a>';
		$menu_map='<a class="mmenu" href="chat_map.php">'.$menu_item_map[$lang].'</a>';
		$menu_search='<a class="mmenu" href="search_v2.php">'.$menu_item_search[$lang].'</a>';
		$menu_mylinks='<a class="mmenu" href="my_links.php">'.$menu_item_links[$lang].' ('.$my_links_count.')</a>';
		$menu_favorites='<b>'.$menu_item_fav[$lang].'</b>';
		$menu_contacts='<a class="mmenu" href="contacts.php">'.$menu_item_contacts[$lang].'</a>';
		$menu_logger='<a class="mmenu" href="logger.php">'.$menu_item_logs[$lang].'</a>';
		$menu_trash='<a class="mmenu" href="trash.php">'.$menu_item_trash[$lang].'('.$tr_n.')</a>';
		if ($token==$admin_name) { $menu_stats=' | <a class="mmenu" href="stats.php"> Stats</a>'; }
	}
// check if archivization is currently enabled...
if ($sess->get('log_status') == "0") { 
		print '<center><div class="message">'.$status_msg1[$lang].'</div></center>';
	}
if ($start) { $cur_loc="&start=$start"; }

// check number of offline messages - this feature is pushed into later betas...
$spool = spool_count($bazaj,$token);

print '<a name="top"></a>'."\n";
print '<table border="0" cellspacing="0" class="ff" width="100%">'."\n";
print '<tr>'."\n";
print '<td colspan="2" height="29" style="text-align: right;">'."\n";
print '<b>'.$token.'@'.$xmpp_host_dotted.'</b>&nbsp; | &nbsp;';
print '<a href="settings.php">'.$menu_item_panel[$lang].'</a>&nbsp; | &nbsp;';
print '<a href="#" onClick="smackzk();">'.$sel_client[$lang].'</a>&nbsp; | &nbsp;';
print '<a href="help.php" target="_blank">'.$help_but[$lang].'</a>&nbsp; | &nbsp;<a href="index.php?act=logout">'.$log_out_b[$lang].'</a><hr size="1" noshade="" color="#c9d7f1"/></td>';
print '</tr>'."\n";
print '<tr><td height="57"><a href="'.$view_type.'"><img src="img/'.$brand_logo.'" alt="logo" border="0" /></a></td></tr>';
print '<tr><td valign="top" height="35"><form action="search_v2.php" method="post">'."\n";
print '<input id="t_search" type="text" name="query" class="cc" value="'.$search_phase.'">'."\n";

if ($search_loc==1) {

	if (isset($_GET[c])) {
		$trange_from_get = $_GET[c];
		$time2s = decode_trange($trange_from_get,$token,$url_key);
		$time2_start=$time2s[0];
		$time2_end=$time2s[1];
	}
	else
	{

	$time2_start=$_POST[time2_start];
	$time2_end=$_POST[time2_end];
	
	}
	if ($time2_start OR $time2_end) {
		if (validate_date($time2_start=="f")) { unset($time2_start); }
		if (validate_date($time2_start=="f")) { unset($time2_end); }
		if ($time2_start AND $time2_end) { if (strtotime("$time2_start") > strtotime("$time2_end")) { $alert = $time_range_w[$lang]; unset ($search_phase); } }
		}

	$result=db_q($user_id,$server,$tslice_table,$talker,$search_p,1,$offset_arch,$xmpp_host);
	while ($results=mysql_fetch_array($result)) {

		$r++;
		$to_tble[$r] = $results[at];

	}

	print '<select class="cc" name="time2_start" style="text-align: center;">'."\n";
	print '<option value="">'.$time_range_from[$lang].'</option>'."\n";
	for ($t=1;$t<=$r;$t++) {

		print '<option value="'.$to_tble[$t].'"';
			if ($time2_start==$to_tble[$t]) {
				print 'selected="selected"'; 
			}
		print '>'.$to_tble[$t].'</option>'."\n";
	
	}

	print '</select>'."\n";
	print '&nbsp;';
	$pass_t=$t;
	print '<select class="cc" name="time2_end" style="text-align: center;">'."\n";
	print '<option value="">'.$time_range_to[$lang].'</option>'."\n";

	for ($t=$r;$t>=1;$t--) {

		print '<option value="'.$to_tble[$t].'"';
			if ($time2_end==$to_tble[$t]) {
				print 'selected="selected"'; 
			}
		print '>'.$to_tble[$t].'</option>'."\n";
	
	}

	print '</select>'."\n";

	if ($time2_start AND !$time2_end) { $time2_end = $to_tble[$pass_t-1]; }
	if (!$time2_start AND $time2_end) { $time2_start = $to_tble[($t+1)-$t]; }

}

print '<input class="red" type="submit" value="'.$search_box[$lang].'">'."\n";
print '</form></td>'."\n";
print '</tr>'."\n";
print '<tr style="background-image: url(img/bell-bak.png); height: 24;">';
print '<td colspan="11" width="100%" style="text-align: left; padding-left: 30px; color: white;">'
	.$menu_main.' | '
	.$menu_map.' | '
	.$menu_favorites.' | '
	.$menu_mylinks.' | '
	.$menu_search.' | '
	.$menu_contacts.' | '
	.$menu_logger.$menu_stats.' | ' 
	.$menu_trash. 
	' | <a class="mmenu" href="" onClick="window.location.reload()">'.$refresh[$lang].'</td>'."\n";
print '</tr>'."\n";
print '</table>'."\n";
print '<p align="center"><b>'.$alert.'</b></p>';
?>
