====
MEMO
====

Version 1.0

July 22, 2010

_____
INTRO

Ridiculously simple online memo tool.

Users log in with a username and password, and can edit their own text document that is saved on the server. Creating an account is as easy as loggin in with a username that does not yet exist.

There is currently no way to remove an account. You could just empty the contents of your memo, though.

Currently only available in Swedish. Why this readme is in English, remains a mystery.

____________
INSTALLATION

Just drop index.php in a folder accessible through your http server. User accounts will be stored in 'users.ini', memos will be stored in plaintext in the 'data/' subdirectory, which will be created if it doesn't exist. So make sure there are sufficient privileges for PHP to create files and directories. It might also be a good idea to make a .htaccess or something to deny access to the users file and the data directory.

____________
REQUIREMENTS

Memo users a session to store login state. Passwords are stored as SHA-1 hashes. Session support and SHA-1 support is needed, thus.
