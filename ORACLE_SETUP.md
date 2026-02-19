ORACLE DB SETUP MAYBE TUTORIAL
1. Download Required Software
•	Download these:
Oracle 11g XE (Express Edition)
https://www.oicbasics.com/2020/01/download-oracle-database-11g-xe-express.html
Follow installation instructions and create a password, make sure to remember the password as this is your administrator password for Oracle database.
SQL Developer 24.3.1
https://www.oracle.com/asean/database/sqldeveloper/technologies/download/
Download Windows 32-bit/64-bit, and then extract the folder to desktop.
Java SE 17 (JDK 17)
https://www.oracle.com/java/technologies/javase/jdk17-archive-downloads.html
If you don’t have JDK 17 or above installed, you need this as SQL Developer requires this. Select Windows x64 Installer to download. To confirm installation, open cmd and run
javac --version
Oracle Instant Client
https://www.oracle.com/asean/database/technologies/instant-client/winx64-64-downloads.html
Important for PHP and Oracle Database communication. To install, select Version 11.2.0.4.0 then download the one that says Instant Client Package – ODBC. After installing extract it to C:\oraclexe (folder created when you install Oracle)
2. SQL Developer
•	Open SQL Developer
•	You will be prompted to enter the JDK path 
•	Find where JDK is installed in your system (usually at C:\Program Files\Java\jdk-17) and enter that.
•	Create New Database Connection name it SYSTEM for username = system, and for password = (the one you set during Oracle Installation). Click ‘Test’, if success then ‘Connect’.
•	This is your administrative connection this is where you can do admin commands for your database. Oracle does not have multiple databases, it only has one database, and each connection is different ways to log in to the database.
•	Next open the worksheet on SYSTEM (right click > Open SQL Worksheet)
•	Create a new user through SQL queries, each user is its own schema, and each schema is its own workspace, essentially means each user is its own database.
•	Enter and run script this query one by one on the workspace:
o	CREATE USER my_user IDENTIFIED BY my_password;
o	GRANT CONNECT, RESOURCE TO my_user;
o	ALTER USER my_user QUOTA UNLIMITED ON USERS;
•	Note: Replace my_user and my_password with your chosen username and password.
•	Now create another connection for the new user, name it ONE_QCU, enter the username and password you just created. Test connection, then connect. You now have a new database workspace for standard user.
•	Now to test your workspace, open workspace on ONE_QCU, and enter this query:
o	BEGIN
         DBMS_OUTPUT.PUT_LINE('Hello Oracle!');
END;
•	If you see an output then it’s working.
•	Useful queries:
o	SELECT USER FROM dual; 
// To see the current user selected on a connection
o	SELECT username FROM dba_users ORDER BY username; 
// To see all users on the database
3. Oracle 11g XE
•	This is a checklist of configurations you need to setup in order for oracle to work.
•	Go to services.msc (Win + R type it and run), among the list make sure that OracleServiceXE and OracleXETNSListener have their status to ‘started’, if not start it.
•	Go to Win + R again and enter sysdm.cpl go to Advanced > Environment Variables then under System Variables find Path, select then click edit. DO NOT delete Variable Value, instead go to the end of the text and type semicolon (;) and the path to your installed Instant Client which is usually at C:\oraclexe\instantclient_11_2 Click ‘OK’ to all to save.
•	Now to make sure that ODBC is enabled. Open XAMPP click Config and select PHP (php.ini) Inside this file make sure of the following:
o	extension=oci8_11g
// Not used but add it anyway, search for OCI8 and add it with the other extensions.
o	extension=odbc
// In order to enable make sure this is not commented out, remove the semicolon (;)
o	extension=pdo_odbc
// Enable it, remove the semicolon
•	Then save.
•	Restart your pc or just the apache.
4. Testing
•	To test this, in this project folder there is a file name test_odbc.php run this in the browser. If it says PDO ODBC Connection successful! It means PHP and Oracle Database are able to communicate. 
5. Create the USERS table
•	Open worksheet on ONE_QCU.
•	Find the “Create ‘USERS’ table” file in this folder.
•	Open it, copy the query, enter and run that query on the workspace.
•	Now you have ‘users’ table on Oracle Database.
•	To view go to ONE_QCU, select table and see if ‘users’ table is created (refresh if not).
•	To test this, register an account to the signup.php page. Fill in the details and verify the email, if success, go to SQL Developer, select the ‘users’ table, around the top under tabs find ‘Data’ section and see if the account was added (refresh if not).
Note: You don’t have to move the project out of htdocs, we are still using xampp but only the apache and not MySQL or phpmyadmin.

