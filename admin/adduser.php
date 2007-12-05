<?php
#CMS - CMS Made Simple
#(c)2004-2006 by Ted Kulp (ted@cmsmadesimple.org)
#This project's homepage is: http://cmsmadesimple.org
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT withOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

$CMS_ADMIN_PAGE=1;

require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "include.php");

check_login();

$error = "";

$user= "";
if (isset($_POST["user"])) $user = CmsRequest::clean_value($_POST["user"]);

$firstname = "";
if (isset($_POST["firstname"])) $firstname = CmsRequest::clean_value($_POST["firstname"]);

$lastname = "";
if (isset($_POST["lastname"])) $lastname = CmsRequest::clean_value($_POST["lastname"]);

$password= "";
if (isset($_POST["password"])) $password = $_POST["password"];

$passwordagain= "";
if (isset($_POST["passwordagain"])) $passwordagain = $_POST["passwordagain"];

$email = "";
if (isset($_POST["email"])) $email = CmsRequest::clean_value($_POST["email"]);

$active = 1;
if (!isset($_POST["active"]) && isset($_POST["adduser"])) $active = 0;

$adminaccess = 1;
if (!isset($_POST["adminaccess"]) && isset($_POST["adduser"])) $adminaccess = 0;

if (isset($_POST["cancel"]))
{
	redirect("listusers.php");
	return;
}

if (isset($_POST["adduser"]))
{
	$validinfo = true;

	if ($user == "")
	{
		$validinfo = false;
		$error .= "<li>".lang('nofieldgiven', array(lang('username')))."</li>";
	}

	if ( !preg_match("/^[a-zA-Z0-9]+$/", $user) ) {
		$validinfo = false;
		$error .= "<li>".lang('illegalcharacters', array(lang('username')))."</li>";
	} 

	if ($password == "")
	{
		$validinfo = false;
		$error .= "<li>".lang('nofieldgiven', array(lang('password')))."</li>";
	}
	else if ($password != $passwordagain)
	{
		#We don't want to see this if no password was given
		$validinfo = false;
		$error .= "<li>".lang('nopasswordmatch')."</li>";
	}

	if ($validinfo)
	{
		#$new_user_id = $db->GenID(cms_db_prefix()."users_seq");
		#$query = "INSERT INTO ".cms_db_prefix()."users (user_id, username, password, active, create_date, modified_date) VALUES ($new_user_id, ".$db->qstr($user).", ".$db->qstr(md5($password)).", $active, '".$db->DBTimeStamp(time())."', '".$db->DBTimeStamp(time())."')";
		#$result = $db->Execute($query);

		$newuser = new CmsUser();
		$newuser->username = $user;
		$newuser->active = $active;
		$newuser->first_name = $firstname;
		$newuser->last_name = $lastname;
		$newuser->email = $email;
		$newuser->admin_access = $adminaccess;
		$newuser->password = $password;

		#Perform the adduser_pre callback
		/*
		foreach($gCms->modules as $key=>$value)
		{
			if ($gCms->modules[$key]['installed'] == true &&
				$gCms->modules[$key]['active'] == true)
			{
				$gCms->modules[$key]['object']->AddUserPre($newuser);
			}
		}
		
		Events::SendEvent('Core', 'AddUserPre', array('user' => &$newuser));
		*/

		$result = $newuser->save();

		if ($result)
		{
			#Perform the adduser_post callback
			/*
			foreach($gCms->modules as $key=>$value)
			{
				if ($gCms->modules[$key]['installed'] == true &&
					$gCms->modules[$key]['active'] == true)
				{
					$gCms->modules[$key]['object']->AddUserPost($newuser);
				}
			}
			
			Events::SendEvent('Core', 'AddUserPost', array('user' => &$newuser));
			*/

			# set some default preferences, based on the user creating this user
			$adminid = get_userid();
			$userid = $newuser->id;
			set_preference($userid, 'wysiwyg', get_preference($adminid, 'wysiwyg'));
			set_preference($userid, 'default_cms_language', get_preference($adminid, 'default_cms_language'));
			set_preference($userid, 'admintheme', get_site_preference('logintheme','default'));
			set_preference($userid, 'bookmarks', get_preference($adminid, 'bookmarks'));
			set_preference($userid, 'recent', get_preference($adminid, 'recent'));

			audit($newuser->id, $newuser->username, 'Added User');
			redirect("listusers.php");
		}
		else
		{
			$error .= "<li>".lang('errorinsertinguser')."</li>";
		}
	}
}

$userid = get_userid();
$access = check_permission($userid, 'Add Users');

include_once("header.php");

if ($error != "")
{
	echo "<div class=\"pageerrorcontainer\"><ul class=\"error\">".$error."</ul></div>";
}

if (!$access) {
	echo "<div class=\"pageerrorcontainer\"><p class=\"pageerror\">".lang('noaccessto', array(lang('adduser')))."</p></div>";	
}
else {
?>

<div class="pagecontainer">
	<?php echo $themeObject->ShowHeader('adduser'); ?>
	<form method="post" action="adduser.php">		
		<div class="row">
			<label>*<?php echo lang('name')?>:</label>
			<input type="text" name="user" maxlength="255" value="<?php echo $user?>" />
		</div>
		<div class="row">
			<label>*<?php echo lang('password')?>:</label>
			<input type="password" name="password" maxlength="25" value="" />
		</div>
		<div class="row">
			<label>*<?php echo lang('passwordagain')?>:</label>
			<input type="password" name="passwordagain" maxlength="25" value="" />
		</div>
		<div class="row">
			<label><?php echo lang('firstname')?>:</label>
			<input type="text" name="firstname" maxlength="50" value="<?php echo $firstname ?>" />
		</div>
		<div class="row">
			<label><?php echo lang('lastname')?>:</label>
			<input type="text" name="lastname" maxlength="50" value="<?php echo $lastname ?>" />
		</div>
		<div class="row">
			<label><?php echo lang('email')?>:</label>
			<input type="text" name="email" maxlength="255" value="<?php echo $email ?>" />
		</div>
		<div class="row">
			<label><?php echo lang('active')?>:</label>
			<input class="checkbox" type="checkbox" name="active" <?php echo ($active == 1?"checked=\"checked\"":"")?> />
		</div>
		<input type="hidden" name="adduser" value="true" />
		<div class="submitrow">
			<button class="positive disabled" name="submitbutton" type="submit" disabled=""><?php echo lang('submit')?></button>
			<button class="negative" name="cancel" type="submit"><?php echo lang('cancel')?></button>
		</div>		
	</form>
</div>

<?php
}

echo '<p class="pageback"><a class="pageback" href="'.$themeObject->BackUrl().'">&#171; '.lang('back').'</a></p>';

include_once("footer.php");

# vim:ts=4 sw=4 noet
?>
