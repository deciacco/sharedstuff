<?php
//phpdoc -o HTML:frames:earthli -d "c:\Documents and Settings\ecilento\desktop\iatftpbackup\Iat" -t "\\Ws01\www\iatmgu.net\dev\ftpbackup\"

/**
 * @author      Eugenio Cilento <eugenio@iatmgu.com>
 * @version     0.1 Alpha 2007.10.03
 * @package     Iat_Ftp_Backup
 * @copyright   Copyright (c) 2007, Eugenio Cilento, International Assurance of Tennessee
 */

/**
 * 
 */
require_once(dirname(__FILE__) . '/../File/Virtual.php');

/**
 * Tool to backup a local directory to a remote ftp server over SSL.
 * Currently only supports UNIX style directory listings
 * @package     Iat_Ftp_Backup
 */
class Iat_Ftp_Backup
{
    /**
     * Full path to log file including file name and extension.
     *
     * @var string
     */
    public $logFileFullName;

    /**
     * Determines if logging will be enabled.
     *
     * @var boolean
     */
    public $logEnabled;

    /**
     * Determines if errors should be logged to file.
     *
     * @var boolean
     */
    public $logErrors;

    /**
     * The name, url, or ip address of the FTP server
     *
     * @var string
     */
    public $ftpServer;

    /**
     * The port of the FTP server
     *
     * @var integer
     */
    public $ftpPort;

    /**
     * The time to wait for a response from the FTP server
     *
     * This defaults to 10 seconds. If you set it very low you increase the change of
     * file transfer errors.
     *
     * @var integer
     */
    public $ftpTimeout;

    /**
     * The User Name to use when authenticating to the FTP server
     *
     * @var string
     */
    public $ftpUserName;

    /**
     * The User Password to use when authenticating to the FTP server
     *
     * @var string
     */
    public $ftpUserPass;

    /**
     * Determines if PASV mode will be used when communicating with the FTP server
     *
     * Defaults to FALSE
     *
     * @var boolean
     */
    public $ftpUsePasv;

    /**
     * The connection handle to the FTP server
     *
     * @access private
     */
    private $_ftpConnHndl;

    /**
     * Stores the directories already available and possibly created
     * at file upload time.
     *
     * @var array
     * @access private
     */
    private $_ftpCreatedDirs;
    
    /**
     * Determins whether or not to use secure ftp
     *
     * Requires that PHP be compiled --with-openssl in the configuration line. 
     * This requires a patch in the PHP code. /ext/ftp.c & /ext/ftp.h files.
     * See http://www.deciacco.com/blog/archives/124 for more info.
     *
     * @var boolean
     */
    public $ftpUseSSL;

    /**
     * Files with these extensions will be uploaded in ASCII mode. All other in Binary.
     *
     * *.txt *.htm *.html *.pas *.c *.cpp *.h *.bas *.tex *.as *.ascx *.asmx *.asp 
     * *.aspx *.cfm *.cfml *.cgi *.cs *.css *.dwt *.inc *.lbi *.php *.shtm *.shtml 
     * *.text *.vb *.xhtm *.xhtml *.js
     *
     * @var array
     */
    public $asciiFiles;

    /**
     * The remote backup directory
     *
     * This is the directory to change to onced logged in. The backup will be dumped
     * here. It must have a begining and ending slash. Ex. /remote/dir/
     *
     * @var string
     */
    public $remoteDirFullName;

    /**
     * The local directory to be backed up
     *
     * This is the directory that will be backed up to the remote FTP server. It has
     * to be the full path to the directory including the last backslash.
     *
     * Ex. "c:\\Documents and Settings\\Administrator\\My Documents\\
     *
     * @var string
     */
    public $localDirFullName;

    /**
     * Last error message
     *
     * Stores the last error message that occured in the object.
     *
     * @var string
     */
    public $errorMessage;

    /**
     * Stores the extension of files to be ignored by the local analysis.
     *
     * All files which have an extension in this array will be omited from uploading to the
     * FTP server. Can be replaced with a new array. By default it ignores files with an
     * .lnk extension.
     *
     * The example below would ignore all link files as well as text files.
     *
     * Ex. $object->ignoredLocalExts = array('lnk','txt');
     *
     * @var array
     */
    public $ignoredLocalExts;

    /**
     * All directories listing here will be skipped and not backedup to the remote site. 
     * Matching directories on the remote site will also be skipped.
     *
     * @var array
     */
    public $ignoredLocalDirs;

    /**
     * Array of files found in the local directory.
     *
     * This array gets populated after _analyzeLocalDirectory is called. 
     * It holds Iat_File_Virtual objects.
     *
     * @var array
     */
    private $_localDirFileInfo;

    /**
     * Array of files found in the remote directory.
     *
     * This array is populated after the _analyzeRemoteDirectory is called. It holds
     * Iat_File_Virtual Objects.
     *
     * @var array
     */
    private $_remoteDirFileInfo;

    /**
     * Array of files to be uploaded to the remote directory.
     *
     * This array is populated after the _getDifference function is called. It holds
     * Iat_File_Virtual objects.
     *
     * @var array
     */
    private $_toUploadFileInfo;

    /**
     * Array of files to be zipped before uploading to the remote directory.
     *
     * @var array
     */
    private $_toZipFileInfo;

    /**
     * Flag that shows whether files were compressed, so the cleanup function
     * knows there are files to delete.
     *
     * @var boolean
     */
    private $_compressedToDel;

    /**
     * If set to TRUE it prevents uploadking of files.
     *
     * @var boolean
     */
    public $dataPreviewOnly;

    /**
     * The directory to place the html report files.
     *
     * !! *** NOT YET IMPLEMENTED *** !!
     *
     * @var string
     */
    public $reportsDir;

    /**
     * The count of files analyzed.
     *
     * @var integer
     */
    public $localFileCount;
    
    /**
     * The total bytes of files analyzed.
     *
     * @var float
     */
    public $localDirSize;
    
    /**
     * The count of files found on the remote site.
     *
     * @var integer
     */
    public $remoteFileCount;
    
    /**
     * Total bytes of the files anaylized on the remote directory.
     *
     * @var float
     */
    public $remoteDirSize;
    
    /**
     * Total number of files to upload.
     *
     * @var integer
     */
    public $filesToUpCount;

    /**
     * Total bytes to transfer.
     *
     * @var float
     */
    public $filesToUpSize;

    /**
     * Total bytes to transfer after compression.
     *
     * @var float
     */
    public $filesToUpSizeCompressed;

    /**
     * Shows a more complete log.
     *
     * Significantly slows down the process. Use only for testing.
     *
     * @var boolean
     */
    public $logVerbose;

    /**
     * Shows the log on the screen as well as in the text file.
     *
     * @var boolean
     */
    public $logToScreen;

    /**
     * Shows the file progress in the log file.
     *
     * Defaults to: FALSE
     *
     * @var boolean
     */
    public $logFileProgress;

    /**
     * The ip address of the smpt server.
     *
     * @var string
     */
    public $emailSmtpServer;

    /**
     * The port of the smtp server.
     *
     * @var integer
     */
    public $emailSmtpPort;
    
    /**
     * The from address.
     *
     * @var string
     */
    public $emailFrom;
    
    /**
     * The recipient email address.
     *
     *
     * @var string
     */
    public $emailTo;

    /**
     * Files listed in here will be zipped before uploaded.
     *
     * @var array
     */
    public $zipFileNames;

    /**
     * File extensions matching extensions contained herein will be zipped before uploading.
     *
     * @var array
     */
    public $zipFileExtensions;

    /**
     * Determines wheter existing files on the remote server will be overwritten by the 
     * newer local copy.
     * 
     * If set to TRUE, the newer local copy will overwrite its counterpart on the remote site.
     * If set to FALSE, the newer local copy will be renamed and the current time appended 
     * to the remote file. This allows an update history to be kept.
     *
     * @var boolean
     */
    public $remoteOverwrite;

    /**
     * Class Constructor
     */
    function __construct()
    {
        $this->logFileFullName = '';
        $this->logEnabled = true;
        $this->logErrors = true;
        $this->ftpServer = '';
        $this->ftpPort = 21;
        $this->ftpTimeout = 10;
        $this->ftpUserName = '';
        $this->ftpUserPass = '';
        $this->ftpUsePasv = false;
        $this->ftpUseSSL = false;
        $this->asciiFiles = array('txt','htm','html', 'pas', 'c', 'cpp', 'h',
                                    'bas', 'tex', 'as', 'ascx', 'asmx', 'asp', 'aspx', 
                                    'cfm', 'cfml', 'cgi', 'cs', 'css', 'dwt', 'inc', 
                                    'lbi', 'php', 'shtm', 'shtml', 'text', 'vb', 
                                    'xhtm', 'xhtml', 'js');
        $this->remoteDirFullName = '';
        $this->localDirFullName = '';
        $this->errorMessage = '';
        $this->ignoredLocalExts = array();
        $this->ignoredLocalDirs = array();
        $this->dataPreviewOnly = false;
        $this->reportsDir = '';
        $this->localFileCount = 0;
        $this->localDirSize = 0;
        $this->remoteFileCount = 0;
        $this->remoteDirSize = 0;
        $this->filesToUpCount = 0;
        $this->filesToUpSize = 0;
        $this->filesToUpSizeCompressed = 0;
        $this->logVerbose = false;
        $this->logToScreen = false;
        $this->logFileProgress = false;
        
        $this->_ftpConnHndl = 0;
        $this->_ftpCreatedDirs = array();
        $this->_localDirFileInfo = array();
        $this->_remoteDirFileInfo = array();
        $this->_toUploadFileInfo = array();
        $this->_toZipFileInfo = array();
        $this->_compressedToDel = false;
        
        $this->emailSmtpServer = '127.0.0.1';
        $this->emailSmtpPort = 25;
        $this->emailFrom = '';
        $this->emailTo = '';

        $this->zipFileNames = array();
        $this->zipFileExtensions = array();

        $this->remoteOverwrite = true;
        
    }

    /**
     * Class Destructor
     */
    function __destruct()
    {
    }

    /**
     * Runs the backup
     *
     * 
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    public function runBackup()
    {
        $success = true;
        
        switch(true)
        {
            case !$this->_writeToLog('----- Beginning Backup ------', true, false, true):
                $success = false;
                break;
            case !$this->_analyzeLocalDirectory():
                $success = false;
                break;
            case !$this->_analyzeRemoteDirectory():
                $success = false;
                break;
            case !$this->_getDifference():
                $success = false;
                break;
            case !$this->_compressFiles():
                $success = false;
                break;
            case !$this->_updateRemoteDirectory():
                $success = false;
                break;
            case !$this->_writeToLog('------ Backup Finished ------', false, false):
        }

        return $success;
    }

    /**
     * Used internally to write activity to a log file and/or screen.
     *
     * @param string $pText The string of text to write to the log file.
     * @param boolean $pAddNewLine Set to True to clear the log before writing.
     * @param boolean $pUseTimeStamp Set to True to mark each log line with a timestamp.
     * @param boolean $pClearLog Set to True to send log to screen.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _writeToLog($pText, $pAddNewLine = false, $pUseTimeStamp = true, 
        $pClearLog = false)
    {
        $success = true;

        if($this->logEnabled)
        {
            if(!empty($this->logFileFullName))
            {
                if(($fh = @fopen($this->logFileFullName,
                                    ($pClearLog ? 'w' : 'a'))) !== false)
                {
                    if(!empty($pText))
                    {
                        if($pUseTimeStamp)
                        {
                            fwrite($fh, date("h:i:s A")." -- $pText" . 
                                ($pAddNewLine ? "\r\n\r\n" : "\r\n" ));
                            if($this->logToScreen)
                                echo date("h:i:s A") . " -- $pText" .
                                ($pAddNewLine ? "\n\n" : "\n");
                        }
                        else
                        {
                            fwrite($fh, "$pText" . 
                                ($pAddNewLine ? "\r\n\r\n" : "\r\n" ));
                            if($this->logToScreen)
                                echo "$pText" . 
                                ($pAddNewLine ? "\n\n" : "\n");
                        }
                    }

                    fclose($fh);
                }
                else
                {
                    $success = false;
                    $this->logEnabled = false; // disable logging
                    $this->_setErrorMessage(
                        __FUNCTION__,
                        "Can't open $this->logFileFullName. Logging disabled...");
                }
            }
            else
            {
                $success = false;
                $this->_setErrorMessage(
                    __FUNCTION__,
                    "Variable 'logFileFullName' looks to be empty.");
            }
        }

        return $success;
    }

    /**
     * Sends the log file to an email recipient.
     */
    public function sendEmailWithLog($pInBody = false)
    {
        ini_set('SMTP', $this->emailSmtpServer);
        ini_set('smtp_port', $this->emailSmtpPort);

        $to             = $this->emailTo; 
        $from           = $this->emailFrom;
        $subject        = "FTP Backup Process Log"; 
        
        if($pInBody)
            $message    = "Backup Log\n---------------------------------------------\n";
        else
            $message    = '';

        $fileatt        = $this->logFileFullName;
        $fileatttype    = "text/plain"; 
        $fileattname    = pathinfo($this->logFileFullName, PATHINFO_FILENAME);

        $headers        = "From: $from";

        $file = fopen($fileatt, 'rb'); 
        $data = fread($file, filesize( $fileatt )); 
        fclose( $file );

        if($pInBody)
            $message .= $data;

        $semi_rand = md5(time()); 
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

        $headers .= "\nMIME-Version: 1.0\n" . 
                    "Content-Type: multipart/mixed;\n" . 
                    " boundary=\"{$mime_boundary}\"";

        $message = "This is a multi-part message in MIME format.\n\n" . 
                "--{$mime_boundary}\n" . 
                "Content-Type: text/plain; charset=\"iso-8859-1\"\n" . 
                "Content-Transfer-Encoding: 7bit\n\n" . 
                $message . "\n\n";

        $data = chunk_split( base64_encode($data) );
                 
        $message .= "--{$mime_boundary}\n" . 
                 "Content-Type: {$fileatttype};\n" . 
                 " name=\"{$fileattname}\"\n" . 
                 "Content-Disposition: attachment;\n" . 
                 " filename=\"{$fileattname}\"\n" . 
                 "Content-Transfer-Encoding: base64\n\n" . 
                 $data . "\n\n" . 
                 "--{$mime_boundary}--\n"; 

        mail( $to, $subject, $message, $headers );
    }

    /**
     * 
     */
    private function _sendEmailMessage($pMessage = '')
    {
        $success = true;



        return $success;
    }

    /**
     * Connects to the FTP server.
     *
     * Goes throught the process of connecting and authenticating to the FTP server.
     * It also enables PASV mode if specified by the properties.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _ftpLogOn()
    {
        $success = true;
        
        if($this->ftpUseSSL)
        {
            $this->_writeToLog(
                "Connecting with SSL to $this->ftpServer on port $this->ftpPort.");
            $ftpNotConnected = ($this->_ftpConnHndl =
                                @ftp_ssl_connect($this->ftpServer,
                                            $this->ftpPort,
                                            $this->ftpTimeout)) === false;
        }
        else
        {
            $this->_writeToLog(
                "Connecting to $this->ftpServer on port $this->ftpPort.");
            $ftpNotConnected = ($this->_ftpConnHndl =
                                @ftp_connect($this->ftpServer,
                                            $this->ftpPort,
                                            $this->ftpTimeout)) === false;
        }

        if($ftpNotConnected == true)
        {
            $success = false;
            $this->_setErrorMessage(
                __FUNCTION__,
                "Could not connect to ftp server.");
        }
        else
        {
            $this->_writeToLog("Authenticating...");
            if(($loginResult =
                    @ftp_login($this->_ftpConnHndl,
                                $this->ftpUserName,
                                $this->ftpUserPass)) === false)
            {
                $success = false;
                $this->_setErrorMessage(
                    __FUNCTION__,
                    "Attempt to authenticate to $this->ftpServer " .
                    "for user $this->ftpUserName failed.");
            }
            else
            {
                $this->_writeToLog("Authentication successfull!");

                if($this->ftpUsePasv)
                {
                    $this->_writeToLog("Setting PASV mode.");
                    ftp_pasv($this->_ftpConnHndl, true);
                }
            }
        }
        return $success;
    }

    /**
     * Compresses files to zip format if needed.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _compressFiles()
    {
        $success = true;
        
        if(!$this->dataPreviewOnly)
        {
            $this->_compressedToDel = true;
            $this->filesToUpSizeCompressed = 0;

            $filesToCompressSize = 0;

            while((list($key, $toZipFile) = each($this->_toZipFileInfo)) && $success)
            {
                $remotePath = substr_replace($toZipFile->oldPath, '', 0, 1);
                $localPath = $this->localDirFullName . str_replace('/','\\', $remotePath);

                $nbBytes = $this->_zipFile($localPath, true);
                
                if($nbBytes > 0)
                    $this->filesToUpSizeCompressed += $nbBytes;
                else
                    $success = false;

                $filesToCompressSize += $toZipFile->size;
            }

            if($success)
            {
                $this->_writeToLog('File compression complete.');
                $this->_writeToLog(
                        "Total size of compressed files: $this->filesToUpSizeCompressed" 
                        . 'b = ~' . 
                        round($this->filesToUpSizeCompressed/pow(1024,2),2) . 'Mb');
                
                $newTotal = ($this->filesToUpSize - $filesToCompressSize) + $this->filesToUpSizeCompressed;

                $this->_writeToLog("*** Total size before: $this->filesToUpSize" 
                    . 'b = ~' . 
                    round($this->filesToUpSize/pow(1024,2),2) . 'Mb ***');

                $this->_writeToLog("*** Total size after: $newTotal"
                    . 'b = ~' .
                    round($newTotal/pow(1024,2),2) . 'Mb ***');
            }
        }
        else
        {
            $this->_writeToLog("Data Preview mode detected. Compression skiped.");
        }

        return $success;
    }

    /**
     * Delete zip version of files that were zipped up before remote upload
     *
     * 
     */
    private function _cleanCompressed()
    {
        if($this->_compressedToDel)
        {
            $this->_writeToLog('Deleting temp compressed files.');

            foreach($this->_toZipFileInfo as $toZipFile)
            {
                $remotePath = substr_replace($toZipFile->path, '', 0, 1);
                $localPath = $this->localDirFullName . str_replace('/','\\', $remotePath);

                $fileDeleted = unlink($localPath);
                
                $this->_compressedToDel = false;

                if($this->logVerbose)
                {
                    if($fileDeleted)
                        $this->_writeToLog("File $toZipFile->name deleted.");
                    else
                        $this->_writeToLog("File delete error: $toZipFile->name");
                }   
            }
        }
        else
        {
            $this->_writeToLog('No temp compressed files to delete.');
        }
    }

    /**
     * Uploads updated and new files to the remote directory.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _updateRemoteDirectory()
    {
        $success = true;

        if(!$this->dataPreviewOnly)
        {
            if($this->filesToUpCount > 0)
            {
                if($this->_ftpLogOn())
                {
                    if(@ftp_chdir($this->_ftpConnHndl, $this->remoteDirFullName) != false)
                    {
                        while((list($key, $fileInfo) = each($this->_toUploadFileInfo))
                                                                            && $success)
                        {
                            if(($success =
                                $this->_ftpCreateDirectories($fileInfo->path)) == true)
                            {
                                $remotePath = substr_replace($fileInfo->path, '', 0, 1);
                                $localPath = $this->localDirFullName .
                                    str_replace('/','\\', $remotePath);

                                // if the file is on the remote site and remote overwrite is
                                // is turned off, then we need to append date/time to file.
                                if($fileInfo->inRemote && !$this->remoteOverwrite)
                                    $remotePath = $this->_getNewRemotePath($remotePath);

                                $startTime = microtime(true);
                                
                                //Can use non-blocking here to show upload status

                                if($this->_upLoadFile($remotePath, 
                                    $localPath, $fileInfo->name, $fileInfo->size) == false)
                                {
                                    $success = false;
                                    $this->_setErrorMessage(
                                        __FUNCTION__,
                                        "There was an error uploading $fileInfo->name.");
                                }
                                else //change date/time with mdtm command
                                {
                                    $endTime = microtime(true);
                                    $this->_writeToLog('File uploaded. Time: ' . 
                                        (string)round(($endTime - $startTime), 4));
                                    $this->_ftpMdtmFile($fileInfo, $remotePath);

                                    // if the file is on the remote site and remote overwrite
                                    // is turned off, then we need to update the original file
                                    // time to flag that we do endeed have an updated copy of
                                    // the local file, but it has a different name
                                    if($fileInfo->inRemote && !$this->remoteOverwrite)
                                        $this->_ftpMdtmFile($fileInfo, 
                                            substr_replace($fileInfo->path, '', 0, 1));
                                }
                            }
                        }
                    }
                    else
                    {
                        $success = false;
                        $this->_setErrorMessage(
                            __FUNCTION__,
                            'Could not change remote directory.');
                    }

                    ftp_close($this->_ftpConnHndl);
                }
            }
            else
            {
                $this->_writeToLog('No files to upload. Grrr...');
            }
        }
        else
        {
            $this->_writeToLog("Data Preview mode detected. Remote update skiped.");
        }

        // remove any compressed files.
        if($success)
            $this->_cleanCompressed();

        return $success;
    }

    /**
     * Appends the current date a time to a file in a path.
     *
     * @return string
     */
    private function _getNewRemotePath($pRemotePath)
    {
        $path_parts = pathinfo($pRemotePath);
                                    
        $newRemotePath =    $path_parts['dirname'] . '/' .
                            $path_parts['filename'] . '_' . 
                            date("Ymd_His", time()) . '.' .
                            $path_parts['extension'];

        $newRemotePath = str_replace('\\', '', $newRemotePath);
        
        return $newRemotePath;
    }

    /**
     * Uploads a file to the server and optonally displays a progress indicator.
     *
     * Only displays the progress indicator if logToScreen is true.
     *
     */
    private function _upLoadFile($pRemotePath, $pLocalPath, $pFileName, $pFileSize)
    {
        $success = true;
    
        $fh = fopen ($pLocalPath, "r");
    
        $upModeTranslation = array(1=>'ASCII',2=>'BINARY');
        
        $upMode = (in_array(pathinfo($pFileName,PATHINFO_EXTENSION), 
                    $this->asciiFiles) ?
                    FTP_ASCII : 
                    FTP_BINARY);

        $this->_writeToLog("Uploading $pFileName in " . 
            "$upModeTranslation[$upMode] mode. s:$pFileSize");

        $ret = ftp_nb_fput ($this->_ftpConnHndl, $pRemotePath, $fh, $upMode);
        
        if($this->logFileProgress)
        {
            while ($ret == FTP_MOREDATA)
            {
               $this->_writeToLog(ftell($fh),false, false);
               $ret = ftp_nb_continue($this->_ftpConnHndl);
            }
        }
        else
        {
            while ($ret == FTP_MOREDATA)
            {
               $ret = ftp_nb_continue($this->_ftpConnHndl);
            }
        }
        
        if ($ret != FTP_FINISHED) {
            $success = false;
            $this->_setErrorMessage(
                    __FUNCTION__,
                    'Error uploading file '.$pFileName);
        }
        
        fclose($fh);
        
        return $success;
    }

    /**
     * Updates the file date/time.
     *
     * It uses the ftp MDTM command after the file is uploaded to change the remote file
     * date and time to match the local date and time.
     *
     * MDTM YYYYMMDDHHMMSS[+-TZ] filename
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _ftpMdtmFile(&$pFileInfo, $pFileToChange)
    {
        $success = true;

        $newTime = $pFileInfo->fdate['year'].
            str_pad($pFileInfo->fdate['month'], 2, '0', STR_PAD_LEFT).
            str_pad($pFileInfo->fdate['day'], 2, '0', STR_PAD_LEFT).
            str_pad($pFileInfo->ftime['hours'], 2, '0', STR_PAD_LEFT).
            str_pad($pFileInfo->ftime['min'], 2, '0', STR_PAD_LEFT).
            date('s'). // seconds irrelavent but needed in mdtm command
            (date('O')/100)*60; // tell the server what timezone client is in

        $serverResponse = ftp_raw($this->_ftpConnHndl, 'MDTM '.$newTime." $pFileToChange");

        if((int)substr($serverResponse[0], 0, 3) === 253){
            $this->_writeToLog('MDTM '.$newTime." $pFileToChange - ok");
        }else{
            // outputs a lot of data to help in troubleshooting
            $this->_writeToLog($pFileInfo->fdate['year']);
            $this->_writeToLog($pFileInfo->fdate['month'].'|'.
                str_pad($pFileInfo->fdate['month'], 2, '0', STR_PAD_LEFT));
            $this->_writeToLog($pFileInfo->fdate['day'].'|'.
                str_pad($pFileInfo->fdate['day'], 2, '0', STR_PAD_LEFT));
            $this->_writeToLog($pFileInfo->ftime['hours'].'|'.$newHour);
            $this->_writeToLog($pFileInfo->ftime['min'].'|'.
                str_pad($pFileInfo->ftime['min'], 2, '0', STR_PAD_LEFT));

            $this->_writeToLog('MDTM command error! - MDTM '.$newTime." $pFileToChange");
            $this->_writeToLog("Response: $serverResponse[0]");
        }

        return $success;
    }

    /**
     * Creates any new folders needed on the ftp server.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _ftpCreateDirectories($pPath)
    {
        $success = true;

        if(strpos($pPath, '/', 1) !== false)
        {
            $justDirs = pathinfo($pPath, PATHINFO_DIRNAME);

            if(!in_array(($justDirs . '/'), $this->_ftpCreatedDirs))
            {
                $mySepDirs = split('/', substr_replace($justDirs, '', 0, 1));

                $dirDepth = count($mySepDirs);
                $cnt = 1;

                while($cnt <= $dirDepth && $success)
                {
                    $x = '';
                    for($cnt_1 = 0; $cnt_1 < $cnt; $cnt_1++)
                    {
                        $x .= '/'.$mySepDirs[$cnt_1];
                    }

                    $x .= '/';

                    if(!in_array($x, $this->_ftpCreatedDirs))
                    {
                        $this->_writeToLog("Creating directory $x");
                        $success = @ftp_mkdir($this->_ftpConnHndl,
                            substr_replace($x, '', 0, 1));
                        $this->_ftpCreatedDirs[] = $x;
                    }

                    $cnt++;
                }

                if(!$success)
                    $this->_setErrorMessage(
                            __FUNCTION__,
                            "There was an error creating dir $x. Cannot continue.");
            }
        }
        return $success;
    }

    /**
     * Determines which files need to be uploaded to the remote site.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _getDifference()
    {
        $success = true;

        $this->_writeToLog("Determening files to upload.", true);

        if($this->remoteFileCount > 0)
            $remoteFileIndices = array_keys($this->_remoteDirFileInfo);

        if($this->logVerbose)
        {
            ob_start();
            echo "*** REMOTE FILE INDICES ***\n";
            $remoteFileIndices = array_keys($this->_remoteDirFileInfo);
            print_r($remoteFileIndices);
            $outPut = ob_get_contents();
            ob_end_clean();
            $outPut = str_replace("\n", "\r\n", $outPut);
            $this->_writeToLog($outPut, false, false);
            
            ob_start();
            echo "*** LOCAL FILE INDICES ***\n";
            $localFileIndices = array_keys($this->_localDirFileInfo);
            print_r($localFileIndices);
            $outPut = ob_get_contents();
            ob_end_clean();
            $outPut = str_replace("\n", "\r\n", $outPut);
            $this->_writeToLog($outPut, false, false);
        }
        
        // reset counters
        $this->filesToUpCount = 0; 
        $this->filesToUpSize = 0;

        if($this->logVerbose)
        {
            ob_start();
            echo "*** FILES TO UPLOAD ***\n";
        }

        foreach($this->_localDirFileInfo as $localFileIndex => $localFile)
        {
            if($localFile->size > 0)
            {        
                // no remote files or not in remote site, must upload
                // (short-circuit or at work here)
                if(($this->remoteFileCount == 0) || 
                    !in_array($localFileIndex, $remoteFileIndices))
                {
                    if(in_array($localFile->oldName, $this->zipFileNames) || 
                       in_array($localFile->oldExt, $this->zipFileExtensions))
                    {
                        $this->_toZipFileInfo[] = $localFile;
                        if($this->logVerbose)
                            echo "$localFile->oldName queued for compression.\n";
                    }

                    if($this->logVerbose)
                        echo "New - $localFile->path - s: $localFile->size\n";

                    $this->_toUploadFileInfo[] = $localFile;
                    $this->filesToUpSize += $localFile->size;
                    $this->filesToUpCount += 1;
                }
                else // in remote site, must check date/time to determine upload
                {
                    $remoteFile = $this->_remoteDirFileInfo[$localFileIndex];

                    if(($localFile->uxtimestamp) > ($remoteFile->uxtimestamp))
                    {
                        if($this->logVerbose)
                        {
                            $output = 'l:' . date("m/d/Y G:i", $localFile->uxtimestamp);
                            $output .= ' r:' . date("m/d/Y G:i", $remoteFile->uxtimestamp);
                            $output .= ' s:' . $localFile->size;
                            
                            if(!$this->remoteOverwrite)//Append current time to file
                                $localPath = $this->_getNewRemotePath($localFile->path);
                            else
                                $localPath = $localFile->path;

                            echo "Existing - $localPath - $output\n";
                        }

                        if(in_array($localFile->oldName, $this->zipFileNames) || 
                           in_array($localFile->oldExt, $this->zipFileExtensions))
                        {
                            $this->_toZipFileInfo[] = $localFile;
                            if($this->logVerbose)
                                echo "$localFile->oldName queued for compression.\n";
                        }
    
                        $localFile->inRemote = true;

                        $this->_toUploadFileInfo[] = $localFile;
                        $this->filesToUpSize += $localFile->size;
                        $this->filesToUpCount += 1;
                    }
                }
            } // size > 0
            else
            {
                if($this->logVerbose)
                    $this->_writeToLog("Skipping $localFile->name. size = 0");
            }
        }

        if($this->logVerbose)
        {
            $outPut = ob_get_contents();
            ob_end_clean();
            $outPut = str_replace("\n", "\r\n", $outPut);
            $this->_writeToLog($outPut, false, false);
        }

        $this->_writeToLog('Directory analysis complete.');
        $this->_writeToLog("Files to upload: $this->filesToUpCount");
        $this->_writeToLog("Total size: $this->filesToUpSize" . 'b = ~' . 
                    round($this->filesToUpSize/pow(1024,2),2) . 'Mb');

        return $success;
    }

    /**
     * Gather remote ftp directory file information.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _analyzeRemoteDirectory()
    {
        $success = true;

        // attempt to connect to ftp server
        if($this->_ftpLogOn())
        {
            $this->_writeToLog("Analyzing remote directory $this->remoteDirFullName");

            if(($success =
                $this->_getRemoteDirData($this->remoteDirFullName, '/')) === true)
            {
                $this->remoteFileCount = count($this->_remoteDirFileInfo);
                $this->remoteDirSize = $this->remoteDirSize;

                $this->_writeToLog('Remote analysis finished. '.
                    $this->remoteFileCount . ' files. '.
                    $this->remoteDirSize  . ' b = ~' . 
                    round($this->remoteDirSize/pow(1024,2),2) . 'Mb');

                if($this->remoteFileCount > 0)
                {
                    $this->_writeToLog('Sorting remote file data...');
                    arsort($this->_remoteDirFileInfo);
                }
            }

            ftp_close($this->_ftpConnHndl);
        }
        else
        {
            $success = false;
        }

        return $success;
    }

    /**
     * Gathers local file information.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _analyzeLocalDirectory()
    {
        // get the root directory from the full path
        // used to create the file id
        $rootDir = substr_replace($this->localDirFullName, '', -1);
        $rootDir = substr($rootDir, strrpos($rootDir, '\\'));
        $rootDir = str_replace('\\','/',$rootDir).'/';

        $this->_writeToLog("Analyzing $rootDir.");
        $this->localDirSize = 0;

        // start the recursion
        if(($success = $this->_getLocalDirData($this->localDirFullName, '/')) === true)
        {
            $this->localFileCount = count($this->_localDirFileInfo);
            $this->localDirSize = $this->localDirSize;

            $this->_writeToLog('Local analysis finished. '.
                $this->localFileCount . ' files. '.
                $this->localDirSize . 'b = ~' . 
                round($this->localDirSize/pow(1024,2),2) . 'Mb');

            if($this->localFileCount > 0)
            {
                $this->_writeToLog('Sorting local file data...');
                arsort($this->_localDirFileInfo);
            }
            else
            {
                $success = false;
                $this->_setErrorMessage(
                    __FUNCTION__,
                    'No files to backup! What\'s the point!');
            }
        }
        return $success;
    }

    /**
     * Used recursevly to get remote directory information.
     *
     * @param string $pDir The remote directory to analyze.
     * @param string $pRootDir The parent directory path.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _getRemoteDirData($pDir, $pRootDir)
    {
        $success = true;
        
        // get system type and check if there is still a connection to the ftp server
        if(($sysType = ftp_systype($this->_ftpConnHndl)) !== false)
        {
            if(!in_array(str_replace($this->remoteDirFullName, '/' , $pDir), 
                $this->ignoredLocalDirs))
            {
                if($this->logVerbose)
                    $this->_writeToLog("Geting raw listing for $pDir.");
                
                $rawList = ftp_rawlist($this->_ftpConnHndl, $pDir, false);
                
                if($this->logVerbose)
                {
                    ob_start();
                    print_r($rawList);
                    $outPut = ob_get_contents();
                    ob_end_clean();
                    $outPut = str_replace("\n", "\r\n", $outPut);
                    $this->_writeToLog($outPut, false, false);
                }

                $listParsedOk = true;
                
                switch($sysType)
                {
                    case 'UNIX':
                        if($this->logVerbose)
                            $this->_writeToLog("Parsing $sysType listing.");
                        $parsedList = $this->_parseFtpListUnix($rawList, $pDir);
                        break;
                    default:
                        // can't parse list...abort!
                        $listParsedOk = false;
                }
            
                if($listParsedOk)
                {
                    if($this->logVerbose)
                        $this->_writeToLog("List parsed.");

                    if(is_array($parsedList)) // skips empty remote folders
                    {
                        while((list($key, $listItem) = each($parsedList)) && $success)
                        {
                            if($listItem['type'] == 'd') //directory
                            {
                                $success = $this->_getRemoteDirData(
                                    $pDir.$listItem['name'].'/',
                                    $pRootDir.$listItem['name'].'/');

                                // don't create folders that already exist
                                $this->_ftpCreatedDirs[] = $pRootDir.$listItem['name'].'/';
                            }
                            else
                            {
                                $fileInfo = new Iat_File_Virtual();

                                $fileInfo->path = $pRootDir.$listItem['name'];
                                $fileInfo->name = $listItem['name'];
                                $fileInfo->type = $listItem['type'];
                                $fileInfo->size = $listItem['size'];

                                $fileInfo->ftime['hours'] = $listItem['time']['hours'];
                                $fileInfo->ftime['min'] = $listItem['time']['min'];

                                $fileInfo->fdate['day'] = $listItem['date']['day'];
                                $fileInfo->fdate['month'] =  $listItem['date']['month'];
                                $fileInfo->fdate['year'] =  $listItem['date']['year'];
                                $fileInfo->uxtimestamp = $listItem['uxtimestamp'];

                                $this->_remoteDirFileInfo[$pRootDir.$listItem['name']] =
                                    $fileInfo;

                                $this->remoteDirSize += $fileInfo->size;
                            }
                        }
                    }
                }
                else
                {
                    $success = false;
                    $this->_setErrorMessage(
                        __FUNCTION__,
                        "List not parsed. Can't continue.");
                }
            }
            else
                $this->_writeToLog("Skipping directory $pDir.");
        }
        else
        {
            $success = false;
            $this->_setErrorMessage(
                __FUNCTION__,
                "No FTP connection available.");
        }

        return $success;
    }

    /**
     * Used recursevly to get local directory information.
     */
    private function _getLocalDirData($pDir, $pRootDir)
    {
        $continueRecur = true;

        if(($hndl = @opendir($pDir)) !== false)
        {
            while(($file = readdir($hndl)) !== false && $continueRecur)
            {
                if($file != '.' && $file != '..')
                {
                    if(is_dir($pDir.$file))
                    {
                        if($this->logVerbose)
                            $this->_writeToLog($pRootDir.$file.'/');

                        if(!in_array(strtolower($pRootDir.$file.'/'), 
                            $this->ignoredLocalDirs))
                            $continueRecur =
                                $this->_getLocalDirData($pDir.$file.'\\', 
                            $pRootDir.$file.'/');
                        else
                            if($this->logVerbose)
                                $this->_writeToLog("Skipping $pDir$file");
                    }
                    else
                    {
                        $fileExt = strtolower(pathinfo($file,PATHINFO_EXTENSION));
                        
                        if(!in_array(
                            $fileExt,
                            $this->ignoredLocalExts)
                            )// if not an ignored extension add to list
                        {
                            // create new file info. object
                            $newFileInfo = new Iat_File_Virtual();

                            $file = strtolower($file);
                            $pRootDir = strtolower($pRootDir);

                            // populate file info. object and add it to the list
                            if(($continueRecur =
                                    $this->_getFileInfo($pRootDir, $pDir,
                                                    $file, $newFileInfo)) === true)
                            {
                                // if this file is to be zipped before uploading, the virtual
                                // file's extension is changed to zip. this will then
                                // cause _getDifference() to look at the zipped version of the
                                // file. if the zip file needs to be uploaded then it will
                                // be placed in the toZip queue. the _compressFiles function 
                                // will ensure the zipped version of the file will exist 
                                // before uploading with the _updateRemoteDirectory function.
                                
                                if(in_array($newFileInfo->name, $this->zipFileNames) || 
                                    in_array($newFileInfo->ext, $this->zipFileExtensions))
                                {
                                    $newFileInfo->changeVirtualFileExtensionTo('zip', true);
                                    // change the virtual file's extension to zip.
                                    // the _getDifference() function will determine if the
                                    // file actually needs to be zipped because the remote zip
                                    // may not need to be updated if the local file
                                    // hasn't been updated.
                                    
                                    // the file index also needs to be changed.
                                    $this->_localDirFileInfo[$newFileInfo->path] = 
                                        $newFileInfo;
                                }
                                else
                                    $this->_localDirFileInfo[$pRootDir.$file] =
                                        $newFileInfo;

                                $this->localDirSize += $newFileInfo->size;
                            }
                        }
                        else
                        {
                            if($this->logVerbose)
                                $this->_writeToLog(
                                "Ignoring $file. Ivalid file extension detected: $fileExt");
                        }
                    }
                }
            }
            closedir($hndl);
        }
        else
        {
            $continueRecur = false;
            $this->_setErrorMessage(
                __FUNCTION__,
                "Could not open directory $pDir.");
        }

        return $continueRecur;
    }

    /**
     * Used to get a file's information
     */
    private function _getFileInfo($pRootDir, $pDir, $pFile, 
                                &$pFileInfo, $pDoFileCheck = false)
    {
        $fileOk = true;

        if($pDoFileCheck)
            $fileOk = file_exists($pDir.$pFile);

        if($fileOk){
            $fileInfo = stat($pDir.$pFile);

            //** Need to get actual file data here
            $pFileInfo->path = $pRootDir.$pFile;
            $pFileInfo->name = $pFile;
            $pFileInfo->ext  = pathinfo($pFile,PATHINFO_EXTENSION);
            $pFileInfo->type = '-';
            
            if($fileInfo['size'] < 0) // file size larger than 2GB
                $pFileInfo->size = $this->_getLargeFileSize($pDir.$pFile);
            else
                $pFileInfo->size = $fileInfo['size']; 

            // windows stat does some weird things with file time and summer time 
            $fileInfo['mtime'] = $this->_dosUxTime($pDir.$pFile);

            // if the file is older than 6 months than zero out time like unix
            if($fileInfo['mtime'] < strtotime('-6 months'))
            {
                $pFileInfo->ftime['hours'] = 0;
                $pFileInfo->ftime['min'] = 0;
            }
            else
            { 
                $pFileInfo->ftime['hours'] = (int)date('G', $fileInfo['mtime']);
                $pFileInfo->ftime['min'] = (int)date('i', $fileInfo['mtime']);   
            }

            $pFileInfo->fdate['day'] = (int)date('d', $fileInfo['mtime']);
            $pFileInfo->fdate['month'] = (int)date('m', $fileInfo['mtime']);
            $pFileInfo->fdate['year'] = (int)date('Y', $fileInfo['mtime']);

            $pFileInfo->uxtimestamp = mktime($pFileInfo->ftime['hours'],
                                            $pFileInfo->ftime['min'], 0,
                                            $pFileInfo->fdate['month'],
                                            $pFileInfo->fdate['day'],
                                            $pFileInfo->fdate['year']);
        }
        else
        {
            $this->_setErrorMessage(
                __FUNCTION__,
                "File $fileFullName does not exists.");
        }

        return $fileOk;
    }

    /**
     * Returns the file last write date/time as a unix timestamp
     */
    private function _dosUxTime($pFileFullName)
    {
        $uxtime = 0;

        if(exec('dir /-C /T:W /4 "'. $pFileFullName .'"', $rawReturn) !== '')
        {
            $reg = '%(?P<date>(?P<month>0[1-9]|1[012])[- /.]';
            $reg .= '(?P<day>0[1-9]|[12][0-9]|3[01])[- /.](?P<year>[12][0-9][0-9]{2}))  ';
            $reg .= '(?P<time>(?P<hour>0[1-9]|1[012]):(?P<min>0[1-9]|[0-5][0-9]) ';
            $reg .= '(?P<ampm>AM|PM))%';

            if (preg_match($reg, $rawReturn[5], $rawFileInfo)) 
                $uxtime = strtotime($rawFileInfo['date'].' '.$rawFileInfo['time']);
        }
        return $uxtime;
    }

    /**
     * Gets the file size in bytes for files larger than 2GB.
     *
     * @return float Returns the size of the given file.
     */
    private function _getLargeFileSize($pFullName)
    {
        if(exec('dir /-C "' . $pFullName . '"', $rawReturn ) !== '')
        {
            if (preg_match('/(?<=file\\(s\\)).*(?=bytes)/i', $rawReturn[6], $regs))
            {
                $result = (float)$regs[0];
            }
        }
        else
            $result = 0;
        
        return $result;
    }

    /**
     * Creates a zipped version of the file.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    private function _zipFile($pFileFullName, $pKeepOldExt = false)
    {
        $compressedSize = 0;
    
        $this->_writeToLog("Zipping file: $pFileFullName");

        $pathInfo = pathinfo($pFileFullName);
        
        if($pKeepOldExt)
            $zipFileFullName = $pathInfo['dirname'] . '\\' . $pathInfo['basename'] . '.zip';
        else
            $zipFileFullName = $pathInfo['dirname'] . '\\' . $pathInfo['filename'] . '.zip';

        $fileZippedName = $pathInfo['basename'];

        $zip = new ZipArchive();
        $res = $zip->open($zipFileFullName, ZipArchive::OVERWRITE);

        if ($res === TRUE)
        {
            $zip->addFile($pFileFullName, $fileZippedName);

            $startTime = microtime(true);
            $zip->close();
            $endTime = microtime(true);

            $this->_writeToLog("Zipped successfully.");

            $compressedSize = $this->_getLargeFileSize($zipFileFullName);

            if($this->logVerbose)
            {
                $secondsToCompress = ($endTime - $startTime);
                
                $fileToZipSize = $this->_getLargeFileSize($pFileFullName);
                $bytePerSecond = $fileToZipSize/$secondsToCompress;

                ob_start();

                echo 'Time to compress: ~' . (string)round($secondsToCompress, 4) . " sec\n";
                echo 'Original size: ' . (string)round($fileToZipSize) . " bytes\n";
                echo 'Compressed size: ' . (string)round($compressedSize, 2) . " bytes\n";
                echo 'Size diff.: ~1/' . (string)round(100/
                    ((round($compressedSize/$fileToZipSize, 2))*100)) . " \n";
                echo 'bytes/sec: ~' . (string)round($bytePerSecond/pow(1024,2), 2) . 'Mb';

                $outPut = ob_get_contents();
                ob_end_clean();
                $outPut = str_replace("\n", "\r\n", $outPut);
                $this->_writeToLog($outPut, false, false);
            }
        }
        else 
        {
            $this->_setErrorMessage(
                __FUNCTION__,
                "File $fileFullName does not exists.");
        }

        return $compressedSize;
    }

    /**
     * Parses the raw ftp list in a unix format and returns an array of items
     * that is much easier to work with.
     */
    private function _parseFtpListUnix($pRawFtpList, $pParentDir)
    {
        $dirList = '';

        $acceptedFileTypes = array('-'=>'file', 'd'=>'directory');

        $monthToNum = array("Jan" => 1,"Feb" => 2,"Mar" => 3, "Apr" => 4,
                    "May" => 5, "Jun" => 6, "Jul" => 7, "Aug" => 8,
                    "Sep" => 9, "Oct" => 10, "Nov" => 11, "Dec" => 12);

        if(strrpos($pParentDir, '/') != strlen($pParentDir)-1){
           $pParentDir .= '/';
        }

        foreach ($pRawFtpList as $line) {
            if (substr(strtolower($line), 0, 5) != 'total'){
                preg_match('/'. str_repeat('([^\s]+)\s+', 7) .
                    '([^\s]+) (.*)/', $line, $matches);

                list($permissions, $children,
                     $owner, $group, $size,
                     $month, $day, $time, $name) = array_slice($matches, 1);

                // only dir and files, no links
                if (in_array($permissions[0], array_keys($acceptedFileTypes)) &&
                    $name != '.' && $name != '..' // don't want these either
                    ){
                    $name = strtolower($name);
                    $type = $permissions[0];

                    // seperate date elements
                    if(strpos($time, ':') === false){// year only
                        $date = array('day'=>(int)$day,
                                        'month'=>(int)$monthToNum[$month],
                                        'year'=>(int)$time
                            );
                    }else{
                        $date = array('day'=>(int)$day,
                                        'month'=>(int)$monthToNum[$month],
                                        'year'=>(int)date('Y')
                            );
                    }

                    // seperate time elements
                    if(strpos($time, ':') === false){//Year only
                        $time = array('hours'=>0,
                                        'min'=>0
                            );
                    }else{
                        $time = array('hours'=>(int)substr($time, 0, 2),
                                        'min'=>(int)substr($time, -2)
                            );
                    }

                    // seperate permission elements
                    $permissions = array('owner'=>substr($permissions, 1, 3),
                                            'group'=>substr($permissions, 4, 3),
                                            'other'=>substr($permissions, 7, 3)
                        );

                    $uxtimestamp = mktime($time['hours'], $time['min'], 0,
                                            $date['month'], $date['day'], $date['year']);

                    $dirList[$pParentDir.$name] = array(
                                'name'=>$name,
                                'type'=>$type,
                                'permissions'=>$permissions,
                                'children'=>$children,
                                'owner'=>$owner,
                                'group'=>$group,
                                'size'=>$size,
                                'time'=>$time,
                                'date'=>$date,
                                'uxtimestamp'=>$uxtimestamp
                            );
                }
            }
        }

        return $dirList;
    }

    /**
     * Used internally to set the message of the
     * last occured error.
     */
    private function _setErrorMessage($pFunction, $pMessage)
    {
        $this->errorMessage =
            get_class($this)."::{$pFunction}(): $pMessage";

        if($this->logErrors)
            $this->_writeToLog($this->errorMessage);
    }
}
?>