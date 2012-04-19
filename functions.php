<?php

/**
 * @author Tareq Hasan
 * @copyright 2009
 */

if (!defined('FORUM'))
    die();

function miss_get_users()
{
	global $forum_db, $db_prefix, $forum_config;

	$duration = miss_get_duration(); //get duration
	$now = time();

	$query = array(
		'SELECT'	=> 'u.id, u.username, u.email',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id!=1 AND (u.last_visit <= '.$duration.') AND ('.$now.' - u.last_miss_send>'.$duration.')',
		'ORDER BY'	=> 'u.last_visit DESC '. miss_get_limit()
	);

	$have_post = $forum_config['o_miss_have_post']; //who have posts

	if($have_post == 'yes')
		$query['WHERE'] .= ' AND u.num_posts!=0';
	else if($have_post == 'no')
		$query['WHERE'] .= ' AND u.num_posts=0';
	else if($have_post == 'both')
		$query['WHERE'] .= '';


	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	//$user_number = $forum_db->num_rows($result);

	while($row = $forum_db->fetch_assoc($result))
	{
		miss_send_mail($row['id'], $row['username'], $row['email']);
	}

}

function miss_get_limit()
{
	global $forum_config;
	$limit = (int) $forum_config['o_miss_mail'];
	return "LIMIT $limit";
}

function miss_send_mail($id, $username, $email)
{
	global $forum_config, $lang_common;

	$mail	= $forum_config['o_miss_mail_message'];
	$sub	= $forum_config['o_miss_mail_sub'];

	$mail = str_replace('<username>', $username, $mail);
	$mail = str_replace('<board_title>', $forum_config['o_board_title'], $mail);
	$mail = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail);
	
	forum_mail($email, $sub, $mail);
	miss_update_last_sent($id);
}

function miss_update_last_sent($id)
{
	global $forum_db, $db_prefix;

	$query = array(
		'UPDATE'	=> 'users as u',
		'SET'		=> 'u.last_miss_send=\''.time().'\'',
		'WHERE'		=> 'u.id=\''.$id.'\''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function miss_update_last_script_exec()
{
	global $forum_db, $db_prefix;

	$query = array(
		'UPDATE'	=> 'config',
		'SET'		=> 'conf_value=\''.time().'\'',
		'WHERE'		=>	'conf_name=\'o_miss_last_sent\''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function miss_get_last_script_exec()
{
	global $forum_db, $db_prefix;

	$query = array(
		'SELECT'	=> 'conf_value',
		'FROM'		=> 'config',
		'WHERE'		=>	'conf_name=\'o_miss_last_sent\''
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$result = $forum_db->fetch_assoc($result);

	$last_exec = (int) $result['conf_value'];
	
	return $last_exec;
}

function miss_get_duration()
{
	global $forum_config;
	//get config duration
	$now = time();
	$thirty = (int) $forum_config['o_miss_duration'];
	//$thirty = 30*24*60*60;
	$duration = $now - ($thirty*24*60*60);

	return $duration;
}
?>
