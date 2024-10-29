<?php

require BACKUPBANK_PLUGIN_PATH.'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;
use phpseclib3\Net\SFTP;
use Ifsnop\Mysqldump as IMysqldump;

/**
 * BackupBank Backup
 */
class BackupBank_Backup {

    public $enabled = 0;
    public $backup_type = 'sftp_scp';
    public $sftp_scp_host = '';
    public $sftp_scp_user = '';
    public $sftp_scp_pass = '';
    public $sftp_scp_folder = '';
    public $backup_path = '';
    public $website_name = '';
    public $retention = '';
    public $backupbank_api = 'https://www.backupbank.io/api/';

    public function __construct() {
        global $wpdb;

        $table_name = $wpdb->prefix.'backupbank_settings';
        $sql = 'SELECT * FROM '.$table_name;
        $results = $wpdb->get_results($sql);
        foreach ($results as $result) {
            $this->enabled          = $result->enabled;
            $this->sftp_scp_host    = $result->sftp_scp_host;
            $this->sftp_scp_user    = $result->sftp_scp_user;
            $this->sftp_scp_pass    = openssl_decrypt($result->sftp_scp_pass, 'aes-256-cbc', '!B@ckupB@nk2^&', 0);
            $this->sftp_scp_folder  = $result->sftp_scp_folder;
            $this->backup_path      = $result->backup_path;
            $this->website_name     = $result->website_name;
            $this->retention        = $result->retention;
        }
    }

    public function testCredentials(): Array {
        $success = true;
        $error = null;

        if ($this->backup_type == 'sftp_scp' && !empty($this->sftp_scp_host) && !empty($this->sftp_scp_user) && !empty($this->sftp_scp_pass)) {
            try {
                $sftp = new SFTP($this->sftp_scp_host);
                if ($sftp->login($this->sftp_scp_user, $this->sftp_scp_pass)) {

                } else {
                    $success = false;
                    $error = 'SFTP / SCP login failed';
                }
            } catch (\Exception $e) {
                $success = false;
                $error = 'SFTP / SCP error connecting to host';
            }
        } else {
            $success = false;
            $error = 'No backup methods specified';
        }

        return [
            'status' => $success,
            'error' => $error,
        ];
    }

    public function runBackup(): Array
    {
        date_default_timezone_set(wp_timezone_string());

        if ($this->enabled == 1 && !empty($this->backup_type)) {
            set_time_limit(900);

            $test = $this->testCredentials();
            if ($test['status'] == false) {
                return $test;
            }

            // dump database
            try {
                $dump = new IMysqldump\Mysqldump('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
                $dump->start($this->backup_path.'/bb_backup_database.sql');
            } catch (\Exception $e) {
                $success = false;
                $error = 'Unable to backup database '.$e->getMessage();
            }

            // zip files up
            ini_set('memory_limit', '-1');
            $file_name = $this->website_name.'_bb_backup_'.date('Y-m-d-H-i').'_'.substr(md5(mt_rand()), 0, 7).'.zip';
            $zipFile = new \PhpZip\ZipFile();

            try {
                $zipFile->addDirRecursive($this->backup_path)
                    ->saveAsFile($this->backup_path.'/'.$file_name)
                    ->close();
            }
            catch (\PhpZip\Exception\ZipException $e) {
                $success = false;
                $error = 'Unable to zip up website';
            }
            finally {
                $zipFile->close();
            }

            // remove db dump file
            unlink($this->backup_path.'/bb_backup_database.sql');

            // Transfer file
            $success = true;
            $error = null;
            if ($this->backup_type == 'sftp_scp' && !empty($this->sftp_scp_host) && !empty($this->sftp_scp_user) && !empty($this->sftp_scp_pass)) {
                try {
                    $sftp = new SFTP($this->sftp_scp_host);
                    if ($sftp->login($this->sftp_scp_user, $this->sftp_scp_pass)) {
                        if (!empty($this->sftp_scp_folder) && $sftp->is_dir($this->sftp_scp_folder)) {
                            $sftp->put($this->sftp_scp_folder.'/'.$file_name, $this->backup_path.'/'.$file_name, SFTP::SOURCE_LOCAL_FILE);
                        } else {
                            $sftp->put($file_name, $this->backup_path.'/'.$file_name, SFTP::SOURCE_LOCAL_FILE);
                        }

                        // Remove old versions
                        $sftp->setListOrder('filename', SORT_DESC);
                        if (!empty($this->sftp_scp_folder)) {
                            $sftp->chdir($this->sftp_scp_folder);
                        }
                        if ($files = $sftp->nlist()) {
                            $old_backup_cnt = 0;
                            foreach ($files as $file) {
                                if ($sftp->is_file($file) && strpos($file, $this->website_name) !== FALSE) {
                                    $old_backup_cnt++;
                                    if ($old_backup_cnt > $this->retention) {
                                        $sftp->delete($file, false);
                                    }
                                }
                            }
                        }
                    } else {
                        $success = false;
                        $error = 'SFTP / SCP login failed';
                    }
                } catch (\Exception $e) {
                    $success = false;
                    $error = 'SFTP / SCP error connecting to host';
                }
            } else {
                $success = false;
                $error = 'No backup methods specified';
            }

            // Remove file
            $file_to_remove = $this->backup_path.$file_name;
            if (is_file($file_to_remove)) {
                if (!unlink($file_to_remove)) {
                    $success = false;
                    $error = 'Could not delete temporary backup file in root directory. Please check your permissions.';
                }
            }

            return [
                'status' => $success,
                'error' => $error,
            ];
        }

        return [
            'status' => 0,
            'error' => 'Backup is not enabled',
        ];
    }
}