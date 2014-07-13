roundcube-redirect-for-approval
===============================

A Roundcube plugin that causes emails sent by selected users to be redirected 
to an approver before being delivered.

Usage:

First, redirect_for_approval must be installed and configured, as described
below.  The plugin configuration file identifies users who requrie approval,
and the email address of the approver.

Users who require approval to send email compose their messages as usual;
however, when they click "Sent", their message is delivered to the approval
email address rather than the addressed recipients.  The original To, Cc and
Bcc headers are recorded in the message sent to the approver.

When the approver receives a message, it can be approved and sent to the
intended recipients in two easy steps:

 1. Click "Forward"
 2. Click "Send"

The redirect_for_approval plugin also modifies messages being forwarded,
restoring the original message recipients, subject, body and "From" address
so that it is ready for sending.  The final recipients will receive a message
that looks and behaves as if it were sent to them directly, without an
approval step.  Replies will be sent to the original sender, not the approver.


Installation
============
Copy the folder redirect_for_approval to your plugins directory (often located
at /var/lib/roundcube/plugins or /usr/share/roundcube/plugins).

Edit the 'plugins' config value in config/main.inc.php and add contain this entry:

$rcmail_config['plugins'] = array('redirect_for_approval');


Configuration
=============
Copy config.inc.php.dist to config.inc.php, and set values for the following
configuration variables:

redirect_for_approval_users - An array of login names that should require
approval prior to the delivery of their email.

redirect_approver_email - The email address where outgoing email should be
sent to for approval.

The approver should create Roundcube identities for every email address to
be approved; if this step is not done, then the "From" address of approved
(forwarded) emails will not be changed to the original sender, and replies
will go to the approver instead of the author.


License
=======
GPL v2; see the provided LICENSE file for more information.


Support
=======
https://github.com/greg-1-anderson/roundcube-redirect-for-approval
