<?php

/**
 * Redirect for Approval
 *
 * Plugin to redirect emails sent by a certain user to an approval queue.
 *
 * Usage:
 *
 * Define the users who require approval and the email address of the
 * approver in the configuration file.
 *
 * Whenever a selected user sends an email, it will be delivered to
 * the approver rather than the intended recipients.  All that the
 * approver needs to do is hit the "Forward" button and then "Send".
 * All of the message headers that were adjusted in the forward-for-approval
 * step will be restored to their original state, ready for sending.
 *
 * IMPORTANT NOTE
 *
 * In order for this to work correctly, the approver must have set
 * up an identity matching the selected user's "From" address in
 * their RoundCube identity preferences.
 * Otherwise, RoundCube will ignore this plugin's attempt to set
 * the From address of the outgoing email, and the message will be
 * delivered from the approver's address rather than the sender's.
 *
 *
 * @version 1.0
 * @author Greg Anderson
 */
class redirect_for_approval extends rcube_plugin
{
  // we've got no ajax handlers
  public $noajax = true;
  // skip frames
  public $noframe = true;

  private $msg_uid;

	// Register our hooks
	function init()
	{
    $rcmail = rcmail::get_instance();
    $this->load_config();

    $this->add_hook('template_object_loginform', array($this, 'add_login_info'));

		$this->add_hook('message_outgoing_headers', array($this, 'fix_headers'));
		$this->add_hook('message_compose', array($this, 'prepare_approved_forward'));
		$this->add_hook('message_compose_body', array($this, 'prepare_approved_forward_body'));
	}

	// If this is a message to be redirected for approval,
	// then adjust the headers so that the 'To:' address goes
	// to the approver rather than the intended recipients.
	// We also save all of the original header values in
	// 'X-...' headers so that we can restore them once the
	// message is approved for delivery.
  public function fix_headers($args)
	{
    $rcmail = rcmail::get_instance();
		$target_users = $rcmail->config->get('redirect_for_approval_users');
		$approver = $rcmail->config->get('redirect_approver_email');

		if (!empty($approver) && in_array($_SESSION['username'], $target_users))
		{
			$args['headers']['X-Approval-For'] = $_SESSION['username'];
			$args['headers']['X-Original-From'] = $args['headers']['From'];

			foreach (array('To', 'Cc', 'Bcc') as $h)
			{
				if (isset($args['headers'][$h]))
				{
					$args['headers']['X-' . $h] = $args['headers'][$h];
				}
			}
			$args['headers']['To'] = $approver;
			unset($args['headers']['Cc']);
			unset($args['headers']['Bcc']);
		}
		return $args;
	}

	// Prepare a message for forwarding.
	public function prepare_approved_forward($args)
	{
		$rcmail = rcmail::get_instance();
		$imap = $rcmail->imap;

		if (isset($args['param']['forward_uid']))
		{
			// Recover the message id and parse the message
			// headers.  The headers that RoundCube provides
			// us are missing important entries that we need
			// (such as those added by the function above).
      $this->msg_uid = $args['param']['forward_uid'];
			$original_headers = array();
			$raw_header_lines = $imap->get_raw_headers($this->msg_uid);
			foreach (explode("\n", $raw_header_lines) as $line)
			{
				list($key, $value) = explode(':', $line, 2);
				$original_headers[$key] = trim($value);
			}

			// Copy some of the original headers over to
			// the message being composed.  This will insure
			// that the 'To:' address, etc. is restored
			// to the state it was in when the message was
			// originally sent.
      foreach (array('X-To' => 'to', 'X-Cc' => 'cc', 'X-Bcc' => 'bcc', 'X-Original-From' => 'from', 'Subject' => 'override-subject') as $h => $k)
      {
        if (isset($original_headers[$h]))
        {
					$args['param'][$k] = $original_headers[ $h];
				}
			}
		}
		return $args;
	}

	// Fix up the forwarded message body, so that it
	// is ready to send without any editing.
	public function prepare_approved_forward_body($args)
	{
		$rcmail = rcmail::get_instance();
		$imap = $rcmail->imap;

		$approver = $rcmail->config->get('redirect_approver_email');

		// This would be preferred, but msg_uid not set here. :?
		// $body = $imap->get_body($this->msg_uid);

		// Use a regular expression to get rid of the
		// "Original Message" stuff at the top of
		// the email.  We could just use:
		//   $args['body'] = $body;
		// For some reason, though, our msg_uid is
		// NULL here.
		$args['body'] = preg_replace("/.*-- Original Message --.*To: ${approver}.*\n\n/msU", '', $args['body']);
		$this->msg_uid = NULL;
		return $args;
	}

	// We put an indicator on the login screen to show
	// that our approval system has been installed.
  public function add_login_info($arg)
	{
    $rcmail = rcmail::get_instance();
    $this->load_config();

		$target_users = $rcmail->config->get('redirect_for_approval_users');
		$approver = $rcmail->config->get('redirect_approver_email');
		if (!empty($target_users) && !empty($approver))
		{
			$userlist = implode(', ', $target_users);
			$rcmail->output->add_footer( "<div style='margin-top:20px; text-align: center'>Redirect for approval is active for $userlist.</div>" );
		}
    return $arg;

	}

}

?>
