=== Backup WD - WordPress Backup and Restore Plugin ===
Contributors: webdorado
Donate link:  https://web-dorado.com/products/wordpress-backup-wd.html
Tags: : backup, cloud backup, database backup, wordpress backup
Requires at least: 3.9
Tested up to: 4.9
Stable tag: 1.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Backup WD is an easy-to-use, fully functional backup plugin that allows to backup your website.  

== Description ==

[Special Offer for all Premium Plugins](https://web-dorado.com/wordpress-plugins-bundle.html)   
[Backup WD](https://web-dorado.com/products/wordpress-backup-wd.html)     
[User Manual](https://web-dorado.com/wordpress-backupwd-guide/introduction.html)     

Are you concerned that your website will be hacked, server will crash or data will accidentally be deleted? Having a backup plugin will give you  peace of mind that your website is backed up and you’re protected from losing all your essential information.

Backup WD is an easy-to-use, fully functional free backup plugin for WordPress. It allows to backup your entire site, including database tables and files quickly and easily with just a few clicks.

The backup plugin for WordPress gives you an ability to securely backup your site and automatically store it to the cloud storage of your choice without any technical know-how. Directly backup into storage locations such as Amazon S3, Dropbox, RackSpace, GDrive, Amazon Glacier, SugarSync and others. Archive your site backups in 4 different formats, including Zip, Tar, Tar GZip and Tar BZip2.

The  backup plugin for WordPress also supports automation and scheduling. Choose how often your site should be backed up based on the frequency of website changes. Backup your site automatically on a repeating schedule (daily, weekly, or monthly). 

The plugin offers flexibility, so you can exclude the files that are not important or are unnecessary from the backup. 

Easily manage all your backups and logs, view and delete jobs, run new backups with just a few clicks. Try the easiest and most efficient backup plugin for WordPress completely free.


### Features of BackUp WD plugin:

*   Create an unlimited number of backups  
*   Backup your entire site, including your database and files 
*   Automatically send your files to Google Drive, FTP and more
*   Wide variety of off-site storage locations
*   Choose what to backup (database backup, file backup, backup everything)
*   Exclude specific files/folders from backup
*   Choose backup archive format - Zip, Tar, Tar GZip and Tar BZip2
*   View all your backup files in a list and restore the one you want
*   Schedule your backups to run daily, weekly or monthly
*   Control all your backups from one dashboard
 

== Changelog ==

= 1.0.10 =
*  Changed: Deactivation popup   
 
= 1.0.9 =
*  Fixed: Minor bug   

= 1.0.8 =
*  Changed: Run job with rest api instead of wp-cron    
*  Fixed: Do not load backup wd styles and scripts on other pages and add prefixes  

= 1.0.7 =
*  Fixed: File including error     

= 1.0.6 =
*  Fixed: Bug on dropbox    

= 1.0.5 =
*  Fixed: Conflict with Google Analytics WD   

= 1.0.4 =
*  Added: Dropbox default API Keys   
*  Fixed: File including error    

= 1.0.3 =
*  Added: Review link in foother

= 1.0.2 =
*  Added: Notices for disabled WP Cron and PHP Zip extension

= 1.0.1 =
*  Fixed: Bug on backup logs delete and download   

= 1.0.0 =
*  Initial version   
   


== Screenshots ==
1.  Backup WD - Jobs List          
2.  Backup WD - Edit job           
3.  Backup WD - Backups list           
4.  Backup WD - Settings     
5.  Backup WD - Restore   

== Installation ==

#### Thank you for your interest in BackUp WD.  

### Minimum requirements.  
*   Wordpress 3.9+  
*   PHP 5.4+  
*   MySQL 5.x  

### Perform a new installation  

After downloading the ZIP file of the BackUp WD plugin,  

1. Log in to the administrator panel.   
2. Go to Plugins Add > New > Upload.     
3. Click "Choose file" ("Browse") and select the downloaded zip file of the BackUp WD plugin.     
*For Mac Users*     
*Go to your Downloads folder and locate the folder with the BackUp WD. Right-click on the folder and select Compress. Now you have a newly created .zip file which can be installed as described here.*     
4. Click "Install Now" button.     
5. Click "Activate Plugin" button for activating the BackUp WD.    
6. If the installation does not succeed, please contact us at [support@web-dorado.com](mailto:support@web-dorado.com).

==  Backup WD User Manual  == 

Backup WD is a free, easy-to-use, and intuitive plugin created for WordPress. It is designed to backup website files, folders and MySQL database tables. Backup WD is the ultimate free backup plugin for securely creating backups and restoring database files.

It’s powerful functionality lets you create and schedule automatic backup jobs, store the backup files on a local or external folder, as well as upload them to a cloud hosting service, such as Google Drive, Dropbox, Amazon S3 and more.

Let’s start configuring your first backup job. Click on Backup WD menu item, then navigate to Jobs page.

Creating a new job

Jobs page of Backup WD plugin contains the summary of all created backup jobs. You can run them anytime, edit, copy or delete them. Press Add New Job button to start configuring the first backup job on your website.

The backup job editor dashboard consists of several options, which are divided into tabs. Let’s go through each of them, and discuss all available settings. Don’t forget to hit Save after the set up.

If you have many jobs on Jobs page, you can change the number of displayed items using the input next to Items from top right side.
General

Start with writing a name for this backup job using Job Name option. Then select Job Type for it, which can be website database, files, or both.

Backup file type. Choose the type of the backup file. Archive creates a compressed file with the backup, whereas Sync synchronizes the updated files on the previous backup.

Note, that Sync option does not add new backup folder with each run. It simply checks if files were modified, and replaces them with their new versions.

Backup to. Use this setting to configure the destination where backup files will be sent or synced to. It can be saved on a cloud hosting service account, or to your website directory.

The following options are available, we will discuss each of their options in Backup Destinations section of this guide.

Folder  
GDrive  
Amazon S3  
Dropbox  
Microsoft Azure  
RackSpace Cloud  
FTP  
SugarSync  
Amazon Glacier  


Backup archive name. Provide a name for your backup archive file. You can edit it as necessary, or click on the input field to use available suggestions. 

We do not recommend removing {hash_key} component. It is a unique identifier, which will be used to add hashes to backup folder and file names.

Preview option will show the final output of the backup archive name. For example, if the format is set to backupwd_{hash_key}_%Y-%m-%d_%H-%i-%s and the hash key is set to 3e7812, the archive name will look like this: backupwd_3e7812_2017-08-01_13-56-59.

Backup archive format. Use this setting to select the format of the backup archive file. It can be one of the following:

Zip
Tar
Tar GZip
Tar BZip2

Send Email. Select this option to send email containing the log of this backup job. You can the recipient email address, and other email options from Backup WD > Settings > Email Options tab. The complete description of these options is available in Settings section of this guide.

Schedule

Backup WD plugin lets you run each backup job manually. Additionally, you can configure a schedule to backup the files or database automatically. Use Start job with option under Schedule tab to set this up.

Manually

To run your backup job manually, save the created job first, then navigate to Backup WD > Jobs page. Hover on the title of backup job you need to run, then click Run Now.

WPCron

WPCron option lets you schedule automatic backups. There are two Schedule types, Basic or Advanced. They let you schedule specific date when the backup will be done automatically.

Advanced. Use this setting to select the month, weekday, the day of the month and time for the automatic backup. Select Any to run the backup on any month or a weekday.

Note, that the day of the month can be set up to 28th of each month. Select L option for backup on the last day of the month.

Link

This type of backup run executes backup functions only in case you open the given URL with a web browser. Simply copy the link, paste it to your browser window and press Enter. The backup will start immediately.

DB Backup

Backup WD plugin is the perfect tool to keep database backups also. DB Backup tab of new job editor dashboard lets you configure the backup of necessary database tables. Let’s go through each option individually.

Database connection. Select this option to use WordPress connection to the website database. Alternatively, you can connect manually after unchecking the option.

In case you choose to connect to website database manually, you need to provide the Host, Username, Password, then select the Database to connect to.

Tables to backup. Use this setting to select the database tables, which you wish to include in database backup. All the tables of your website MySQL are listed underneath this setting.

Backup file name. Provide a filename for database backup file. Note, that .sql file extension is added to the filename automatically. Therefore, make sure to set the name without writing .sql at the end.

Backup file compression. Select the compression type for database backup file, it can be set to None or GZip.

Encrypt DB file. Mark this option as checked, in case you would like to encrypt the database backup file for data protection.

Files Backup

Backup WD plugin allows you to quickly create backup archives containing your website files. This feature has a dedicated section of options, which you can use to configure the backup process. Click on Files Backup tab to open them.

Use the following settings to choose which folders to backup:
Backup installation folder (wp-admin, wp-includes or wp-snapshots),
Backup content folder (wp-content),
Backup plugins (wp-content/plugins),
Backup themes (wp-content/themes),
Backup uploads (wp-content/uploads).

Exclude option of these 5 settings allows you to exclude selected folders of these directories from being included in the backup.

Extra folders to backup. In case there are extra folders on your website, which you wish to add to the backup, simply write their absolute path in this input. You can add multiple folders, however, make sure to separate them with a line break. For example:

https://yourdomain.com/website_folder/subfolder/folder1
https://yourdomain.com/website_folder/subfolder/folder2

Thumbnails in uploads. Select this option to exclude the backup of thumbnails from /wp-content/uploads folder.

Exclude files/folders from backup. Use this option to write the files or paths to exclude from backup. Make sure to separate the entries with a line break or comma. For example:

.tmp,.svn,.git,desktop.ini

Or

.tmp
.svn
.git
desktop.ini

Include special files. Mark this option as checked, in case you wish to backup wp-config.php, robots.txt, nginx.conf, .htaccess, .htpasswd and favicon.ico files from root, if they are not included in backup.

Use one folder above as WordPress install directory. This option is recommended, in case you wish to backup files and folders, which are not in WordPress installation directory. Also, this can be helpful in case your WordPress installation is located under a separate folder. 

Note: If you enable Use one folder above as WP install folder option, make sure to configure excludes again.

Backup Destinations

Backup WD plugin is incorporated with various cloud hosting services, where it can send or sync the backup files it creates. Moreover, it lets you keep the files among your website files, in a separate folder, as well as on an external FTP server.

Each storage option will be precisely described within the next few sections of this guide.

Folder

Setup the local folder using Folder to store option. The backup files will be stored here. You can write any name for the folder, but make sure the path starts with uploads/ directory.

Also, we recommend keeping {hash_key} component, which is a unique identifier that is used to add hashes to backup folder and file names.

File deletion option sets the maximum number of files in the dedicated folder. When the limit is reached, the oldest backup file will be deleted automatically.

Google Drive

Before configuring Google Drive as the destination of this backup job, make sure to add Google API keys to the plugin. This can be done from Backup WD > API Keys > Google Drive page. The detailed description of API keys setup is under API Keys section of this guide.

Press Authenticate (Reauthenticate) button to connect Backup WD to your Google Drive. In case you are working with multiple Google account, make sure to use the one where you wish to keep the backup files.

Folder in Google Drive. Set the title of your Google Drive folder, where the backup files will be uploaded.

File deletion. Specify the maximum number of files in Google Drive folder. When the limit is reached, the oldest backup file will be deleted automatically.

Amazon S3


Select Bucket. Choose a bucket from your Amazon S3 service. Make sure the bucket belongs to S3 region you have selected.

Create a new bucket. This option lets you to create a new bucket on your Amazon S3 cloud account. The bucket will be added when you save tab options.

Folder name in bucket. Provide the folder name on your Amazon S3 bucket, where the backup files will be stored.

File Amazon S3 service is separated to regions. Fistly, you will need to select the region where your Amazon S3 buckets are stored. Use Select S3 service option to set the region. You will afterwards need to provide Access Key and Private Key of your Amazon S3 cloud account.
deletion. Specify the maximum number of files in Amazon S3 bucket folder. When the limit is reached, the oldest backup file will be deleted automatically.

File deletion (Sync). Mark this option, in case you wish to keep deleted files from previous backup sync. Uncheck the option to remove the deleted files.

Multipart Upload. Select this setting to enable multipart upload for backup files. Since the files are generally large, this option splits them into multiple chunks while uploading. Therefore, it is recommended to use this setting.

Amazon: Storage Class. Amazon S3 service has several storage classes, and you can select yours using this option.

Server side encryption. Enable this option to encrypt the backup files for data protection.

Dropbox

To configure Dropbox as the destination for your backup files, you will need to add its API keys to Backup WD > API Keys > Dropbox page. The detailed description of API keys setup is under API Keys section of this guide.

This service offers Full Dropbox App and Sandbox App for integration. While configuring the API Keys, make sure to add yours to the correct options.

In case you don’t have a Dropbox account, please firstly create one here. Now let’s go through all options associated with Dropbox integration for storing backup files.

App Access to Dropbox and Full Access to Dropbox options will appear under Dropbox tab of your backup job configuration, based on the setup on the plugin’s API Keys > Dropbox page.

Press Get Dropbox App auth code to let the plugin create a dedicated folder titled BackupWD in Apps folder of your Dropbox account. The plugin will have permissions to read and write files into this folder only. You can also specify a subfolder as backup destination, using Folder in Dropbox setting.

Folder in Dropbox. Set the title of your Dropbox folder, where the backup files will be uploaded. 

File deletion. Specify the maximum number of files in Dropbox folder for backup. When the limit is reached, the oldest backup file will be deleted automatically.

Microsoft Azure

Start configuring Microsoft Azure as the destination of this backup job by providing Account name and Access key of your Microsoft Azure account.

Select container. Choose the Blob container where the backup files will be uploaded. 

Create a new container. You can also create a new Blob container as the destination directory by providing its title in this input box. The container will be added when you save tab options.

Folder in container. Specify the folder in selected container where the backup files will be uploaded.

File deletion. Specify the maximum number of files in Microsoft Azure folder for backup. When the limit is reached, the oldest backup file will be deleted automatically.

RackSpace Cloud

To save the backup file on RackSpace Cloud, configure RackSpace Cloud Keys by providing Username and API Key of your RackSpace Cloud account. Afterwards, use RackSpace Cloud Files Region option to select the region of your account for storing the backup files.

Select Container. Choose the container where the backup files will be uploaded.

Create a new container. Alternatively, you can create a new container as the destination directory by providing its title in this input box. The container will be added when you save tab options.

Folder in container. Specify the folder in selected container where the backup files will be uploaded.

File deletion. Specify the maximum number of files in RackSpace Cloud folder for backup. When the limit is reached, the oldest backup file will be deleted automatically.

FTP

In addition to Folder option of storing backup files, Backup WD plugin allows you to keep them externally on a different FTP server. Login by providing FTP server host, Port, Username and Password of your FTP account.

Folder to store. Specify the folder where the backup files will be uploaded.

File deletion. Specify the maximum number of files in your FTP folder for backup. When the limit is reached, the oldest backup file will be deleted automatically.

Timeout for FTP connection. Specify the timeout period for the FTP connection.

SSL-FTP connection. Select this option in case your server uses explicit SSL-FTP connection.

FTP Passive Mode. Check this option to use FTP Passive mode. It can be helpful in case the FTP server fails to establish data channel connection. One of the reasons this happens is network firewalls.

SugarSync

To use SugarSync as the destination of this backup job, firstly make sure to configure its API keys from Backup WD > API Keys > SugarSync page. The detailed description of API keys setup is under API Keys section of this guide.

Authentication. Authenticate your SugarSync account by providing its user email address and password. If you don’t have an account yet, please create an account.

SugarSync Root. Select the root folder of your SugarSync account where the backup sync will be uploaded.

Folder in SugarSync. Specify the SugarSync folder where the backup sync will be uploaded.

File deletion. Specify the maximum number of files in your SugarSync folder for backup. When the limit is reached, the oldest backup file will be deleted automatically.

Amazon Glacier

Amazon Glacier is another service from Amazon Web Services, which is integrated with Backup WD plugin. Start with selecting a region of Amazon Glacier for your backup files. Afterwards provide Access Key and Private Key from your Amazon Glacier account.

Select Vault. Select a vault from your Amazon Glacier region, where the backup files will be stored.

Create a new vault. You can also create a new vault for storing the backup files by setting a title for it. The vault will be added when you save tab options.

File deletion. Specify the maximum number of files in your Amazon Glacier vault for backup. When the limit is reached, the oldest backup file will be deleted automatically.

API Keys

To use Google Drive, Dropbox and SugarSync as the destination of your backup files, you will need to configure API keys for each of them. Navigate to Backup WD > API Keys page to start the setup.

The step-by-step description for this configuration will be described in the following three sections of this guide.

Google Drive

Go to Google APIs Manager page to create a new OAuth Client ID. Login with your Google account first. Note, that in case you have multiple Google accounts, you will need to log in with the one where you wish to keep the backup files.

Firstly, go to Dashboard tab after logging in and press Enable APIs and Services button. Click on Drive API link, then hit Enable button at the top.

Navigate to Credentials tab, then to OAuth Consent Screen page. Use Product name shown to users option to set a product name, then press Save.

Afterwards, go back to Credentials tab and press Create Credentials button. Choose OAuth Client ID option. Select Web application option for Application type.

Provide your website URL as the value of Authorized JavaScript origins input. As for Authorized redirect URIs option, you will need to provide the link in Redirect URIs option below exactly as it is.

Client ID. As soon as you are done creating the OAuth client, provide the Client ID of it on Google Drive tab of the plugin’s API Keys page. Here’s an example of how it should look like:
1077302661009-rd9osq5fnq4hj8044ovsp2v889qpl5jv.apps.googleusercontent.com

Client secret. Also copy Client Secret from your Google OAuth Client and paste it in this input.

Redirect URIs. Use the URI provided by this option while creating your Google OAuth Client ID.

Dropbox

Visit Dropbox Apps page and log in to create API keys and add them to corresponding settings of this page. If you don’t have any apps, press Create Apps button from Dropbox App Console. 
Select Dropbox API option, then choose the access type, Sandbox App or Full Dropbox App. Name your app, then hit Create App button. You will be redirected to a dashboard page, where the App key and App secret will be provided.

Note, that Full Dropbox App and Sandbox App have different API keys. Make sure to add yours to the correct options of Backup WD plugin.

SugarSync

Open SugarSync Developer Console to create an app for your account. If will be required to log in with your account email address and password first. Press Create App button and configure your app by providing the required information.

Afterwards, go back to API Keys page of Backup WD plugin, and provide the values of Access Key ID, Private Access Key and App ID from your SugarSync app.

Settings

Backup WD plugin provides a set of options which lets you personalize the process of the backup globally. It is divided into the following sections:

General
Jobs
Logs
Network
Email Options
Export/Import

Let’s discuss all available options one by one. Also, don’t forget to hit Save after modifying any of the settings.
General

Hash key. Set the hash key for your backup files. It is a unique identifier, which will be used to add hashes to backup folder and file names. It needs to be at least 6 characters.

Admin bar. If this option is enabled, the plugin adds Backup WD quick links to the top admin bar of WordPress dashboard.

Folder sizes. Enable this option to display folder sizes on Files Backup tab when adding or editing a job.

Protect folders. Check this option to protect Backup WD folders, such as Temp, Log and Backups with .htaccess and index.php files.

Jobs

Maximum number of retries for job steps. Use this option to set up a limit of retries for backup job steps.

Maximum script execution time. Set a maximum time limit for script executions during backup jobs.

Key to start jobs externally with a URL. Some servers might require keys to run the backup jobs externally with Link option. This key is provided by your web hosting. Add it to this input and it will be automatically added to the start link of your backup jobs.

Logs

Log file folder. Specify the folder where the log file of the backups will be kept. Make sure to start it with uploads/ directory. For example:

uploads/backup-wd-logs-{hash_key}

Maximum log files. Set the maximum limit of log files which are allowed to be kept in log file folder.

Logging Level. Set the logging level, Normal or Debug. Debug mode will help you troubleshoot problems during the execution of backup jobs.

Network

Authentication method. Select the method of Authentication, None, Basic auth or Query argument. Correspondingly, you will need to provide Basic Auth Username and Password, or Query argument key=value.

Email Options

Send to. Backup WD plugin lets you send emails containing the log of backup processes. Specify the email address to which the log will be sent. The plugin takes the email address of Administrator user by default.

From name. Specify the name of the sender of the backup log email.

Email from. Specify the email address from which the backup log will be sent. The plugin takes the email address of Administrator user by default.

Subject. Set the subject of the backup log email.

Export/Import

Backup WD plugin lets you export the settings from one site, and import to another.

Press Export button to download the .json file, or use Choose File to browse the downloaded file and import by clicking Save.

Logs

Logs page of Backup WD plugin lets you view all logs from all backup jobs. You can filter the logs by selecting a job, and/or the backup type, database or files backup. This page also allows you to Delete selected backup logs using Bulk Actions option.

To view each log individually, hover over its title and click View link. You can also Download the log which is an .html file, and Delete the log using corresponding links.

If you have many logs, you can change the number of displayed logs using the input next to Items from top right side.

Backups

Check all backups and syncs created with Backup WD plugin from Backup WD > Backups page. It lets you filter backups and syncs by jobs and destinations, as well as delete the records using Bulk Actions.

You can Delete or Download each backup file individually by hovering over its title. Note, that the backup files are generally quite large. The sizes of each backup is mentioned under Size column of Backups page.

Restore

After downloading the database backup file, you are able to easily restore it by uploading the package through Backup WD plugin. Simply press Choose File and browse the backup file you wish to restore, then click Restore.

The feature to restore website files will also be available in Backup WD plugin soon.

