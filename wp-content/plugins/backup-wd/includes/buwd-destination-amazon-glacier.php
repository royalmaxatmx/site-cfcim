<?php

/**
 *
 */
use Aws\Glacier\Exception\GlacierException;
use Aws\Glacier\GlacierClient;

require_once BUWD_DIR . '/vendor/autoload.php';

class Buwd_Destination_Amazon_Glacier
{
    protected static $instance = null;

    private $key = '';
    private $secret = '';
    private $region = '';
    private $vault = '';
    private $errors = array();

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to Amazon Glacier', 'buwd');
        $this->info['name'] = __('Amazon Glacier', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = false;
    }

    public function defaults()
    {
        $defaults = array(
            'glacier_region'     => 'us-east-1',
            'glacier_accesskey'  => '',
            'glacier_privatekey' => '',
            'glacier_newvault'   => '',
            'glacier_filedelete' => 15,
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        $this->set_keys($job_id);

        $services = $this->get_region();
        $vault_list = $this->get_vault_list($job_id);

        $vault_selection_class = $vault_list['type'] == 'paragraph' ? 'buwd-hide' : '';
        $options = array(
            'key'    => 'destination-amazon-glacier',
            'title'  => '',
            'fields' => array(
                array(
                    'label'   => __('Select a region', 'buwd'),
                    'header'  => __('Amazon Glacier', 'buwd'),
                    'id'      => 'glacier_region',
                    'name'    => 'glacier_region',
                    'type'    => 'select',
                    'class'   => array(
                        'buwd-large-text',
                    ),
                    'choices' => $services,
                    'value'   => Buwd_Options::get($job_id, 'glacier_region'),
                    'hint'    => array(
                        'html' => '<p class="description">Choose the region of Amazon Glacier for your backup files.</p>',
                    ),
                ),
                array(
                    'label'  => __('Access Key', 'buwd'),
                    'header' => __('Amazon Access Keys', 'buwd'),
                    'id'     => 'glacier_accesskey',
                    'name'   => 'glacier_accesskey',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'glacier_accesskey')),
                    'hint'   => array(
                        'html' => '<p class="description">Provide the access key for your Amazon Glacier account.</p>',
                    ),
                ),
                array(
                    'label' => __('Private Key', 'buwd'),
                    'id'    => 'glacier_privatekey',
                    'name'  => 'glacier_privatekey',
                    'type'  => 'password',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 'glacier_privatekey'))),
                    'hint'  => array(
                        'html' => '<p class="description">Provide the private key for your Amazon Glacier account.</p>',
                    ),
                ),
                array(
                    'label'   => __('Select Vault', 'buwd'),
                    'header'  => __('Vault', 'buwd'),
                    'id'      => 'glacier_vault',
                    'name'    => 'glacier_vault',
                    'type'    => $vault_list['type'],
                    'choices' => $vault_list['vaults'],
                    'class'   => array(
                        'buwd-large-text buwd-error',
                    ),
                    'value'   => $vault_list['value'],
                    'hint'    => array(
                        'html' => '<p class="description  ' . $vault_selection_class . '">Select a vault from your Amazon Glacier region.</p>',
                    ),
                ),
                array(
                    'label' => __('Create a new vault', 'buwd'),
                    'id'    => 'glacier_newvault',
                    'name'  => 'glacier_newvault',
                    'type'  => 'text',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => '',
                    'hint'  => array(
                        'html' => '<p class="description">Alternatively, you can create a new vault by setting a title for it. The vault will be added when you save tab options.</p>',
                    ),
                ),
                array(
                    'label'  => __('File deletion', 'buwd'),
                    'header' => 'Glacier Backup settings',
                    'id'     => 'glacier_filedelete',
                    'name'   => 'glacier_filedelete',
                    'type'   => 'number', // to number
                    'class'  => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'   => array(
                        'min' => "0"
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'glacier_filedelete')),
                    'hint'   => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in your Amazon Glacier vault for backup. When the limit is reached, the oldest backup file will be deleted.</p>',
                    ),
                ),


            ),
        );

        return $options;
    }

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to Amazon Glacier.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $this->key = $job_object->job['glacier_accesskey'];
        $this->secret = Buwd_Encrypt::decrypt($job_object->job['glacier_privatekey']);
        $this->region = $job_object->job['glacier_region'];
        $this->vault = $job_object->job['glacier_vault'];

        $this->upload_file($job_object->backup_folder . $job_object->backup_file, $job_object);

        $backup_files = $this->get_objects_vault($job_object->job_id);

        $deleted = 0;
        $files_to_delete = array();
        if (!empty($job_object->job['glacier_filedelete']) && $job_object->job['glacier_filedelete'] > 0 && count($backup_files) > $job_object->job['glacier_filedelete']) {
            while (count($backup_files) > $job_object->job['glacier_filedelete']) {
                $file = array_shift($backup_files);
                $this->delete_object($file);
                $deleted++;
                $files_to_delete[] = basename($file);
            }
        }

        if (!empty($files_to_delete)) {
            Buwd_Options::backup_bulk_delete('buwd-dest-amazon-glacier-' . $job_object->job_id, $files_to_delete);
        }

        if ($deleted > 0) {
            $job_object->buwd_logger->set_log(sprintf(__('%d files deleted from Amazon Glacier', 'buwd'), $deleted));
        }

        return true;
    }

    public function set_keys($job_id)
    {
        $this->region = Buwd_Options::get($job_id, 'glacier_region', '');
        $this->key = esc_html(Buwd_Options::get($job_id, 'glacier_accesskey'));
        $this->secret = Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 'glacier_privatekey')));
    }

    public function get_objects_vault($job_id)
    {
        $files_option = get_site_option('buwd-dest-amazon-glacier-' . $job_id);
        $files_vault = Buwd_Helper::search_in_array($files_option, 'vault', $this->vault);
        $objects_by_date = array();
        foreach ($files_vault as $file) {
            $objects_by_date[$file['date_uploaded']] = $file['archive_id'];
        }

        ksort($objects_by_date);

        return $objects_by_date;
    }

    public function get_config()
    {
        $region = $this->region;
        $key = $this->key;
        $secret = $this->secret;

        if (isset($_POST['buwd_ajax'])) {
            $region = sanitize_text_field($_POST['region']);
            $key = sanitize_text_field($_POST['key']);
            $secret = sanitize_text_field($_POST['secret']);
        }

        return array(
            'version'     => 'latest',
            'region'      => $region,
            'credentials' => array(
                'key'    => $key,
                'secret' => $secret,
            ),
            'http'        => array(
                'verify' => Buwd::get_plugin_data('cacert'),
            ),
        );
    }

    public function get_output($archive_id)
    {
        try {
            $glacier = GlacierClient::factory($this->get_config());

            $glacier_job_id = get_site_option('glacier_' . $archive_id);

            $status = 'none';
            if ($glacier_job_id == '') {


                $result = $glacier->initiateJob(array(
                    'vaultName'     => $this->vault,
                    'jobParameters' => [
                        'ArchiveId' => $archive_id,
                        'Type'      => 'archive-retrieval',
                    ],
                ));

                $jobId = $result->get('jobId');
                update_site_option('glacier_' . $archive_id, $jobId);
            } else {

                $result = $glacier->describeJob(array(
                    'vaultName' => $this->vault,
                    'jobId'     => $glacier_job_id,
                ));

                $status = $result->get('StatusCode');
            }

            if ($status == 'InProgress') {
                set_site_transient('buwd_backups_updated', 'Download request sent. Backup archive download will be available after 4-5 hours.');

                return false;
            }
            if ($status == 'Succeeded') {
                delete_site_option('glacier_' . $archive_id);
                $data = $glacier->getJobOutput(array(
                    'vaultName' => $this->vault,
                    'jobId'     => $glacier_job_id
                ));

                return $data;
            }

        } catch (GlacierException $e) {
            //echo $e->getMessage();
            return false;
        }
    }

    private function create_vault($vaultName)
    {
        try {
            $glacier = GlacierClient::factory($this->get_config());
            $glacier->createVault(array('vaultName' => $vaultName));
        } catch (GlacierException $e) {
            set_site_transient('buwd_glacier_error', $e->getMessage());

            return false;
        }

        return true;
    }

    public function get_region($key = '')
    {
        $services = array(
            "us-east-1"      => "US Standard",
            "us-west-1"      => "US West (Northern California)",
            "us-west-2"      => "US West (Oregon)",
            "eu-west-1"      => "EU (Ireland)",
            "eu-central-1"   => "EU (Germany)",
            "ap-south-1"     => "Asia Pacific (Mumbai)",
            "ap-northeast-1" => "Asia Pacific (Tokyo)",
            "ap-northeast-2" => "Asia Pacific (Seoul)",
            "ap-southeast-1" => "Asia Pacific (Singapore)",
            "ap-southeast-2" => "Asia Pacific (Sydney)",
            "sa-east-1"      => "South America (Sao Paulo)",
            "cn-north-1"     => "China (Beijing)"
        );

        if (empty($key)) {
            return $services;
        }

        if (isset($services[$key])) {
            return $services[$key];
        } else {
            return false;
        }
    }

    public function get_vault_list($job_id)
    {
        $vaults_array = array();
        $type = 'select';

        try {
            $glacier = GlacierClient::factory($this->get_config());
            $vaults = $glacier->listVaults();

            $region = isset($_POST['region']) ? sanitize_text_field($_POST['region']) : $this->region;
            if (empty($vaults['VaultList'])) {
                $type = 'paragraph';
                $value = 'Vault not found for ' . $this->get_region($region) . ' region';

                return array('vaults' => $vaults_array, 'type' => $type, 'value' => $value);
            }

            foreach ($vaults['VaultList'] as $vault) {
                $vaults_array[esc_attr($vault['VaultName'])] = esc_attr($vault['VaultName']);
            }

            $value = esc_html(Buwd_Options::get($job_id, 'glacier_vault'));
        } catch (GlacierException $e) {
            $type = 'paragraph';
            $value = __('Access or Private keys are incorrect.', 'buwd');
            //			echo $e->getCode();
            //			echo "<pre>" . $e->getMessage() . "</pre>" . "\n";
        }

        return array('vaults' => $vaults_array, 'type' => $type, 'value' => $value);
    }

    public function run_ajax()
    {
        $html = $this->render_tab();
        echo $html;
        die;
    }

    public function is_valid($job_options)
    {
        if (empty($job_options['glacier_accesskey']) || empty($job_options['glacier_privatekey'])) {
            return false;
        }

        $this->key = $job_options['glacier_accesskey'];
        $this->secret = Buwd_Encrypt::decrypt($job_options['glacier_privatekey']);
        $this->region = $job_options['glacier_region'];
        $this->vault = $job_options['glacier_vault'];

        try {
            $glacier = GlacierClient::factory($this->get_config());
            $vaults = $glacier->listVaults();
            if (empty($vaults['VaultList'])) {
                $this->errors[] = sprintf(__('Amazon Glacier vault "%s" does not exist in "%s" region.', 'buwd'), $this->get_region($this->region));

                return false;
            }

            $vault_exist = false;
            foreach ($vaults['VaultList'] as $vault) {
                if ($vault['VaultName'] == $this->vault) {
                    $vault_exist = true;
                    break;
                }
            }

            if (!$vault_exist) {
                $this->errors[] = sprintf(__('Amazon Glacier vault "%s" does not exist! in "%s" region', 'buwd'), $this->vault, $this->region);

                return false;
            }

        } catch (GlacierException $e) {
            return false;
        }

        return true;
    }

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';
            if (in_array($field_name, array('glacier_region', 'glacier_accesskey', 'glacier_privatekey', 'glacier_vault', 'glacier_newvault'))) {
                $field_value = sanitize_text_field($field_value);
            }

            if ($field_name == 'glacier_privatekey') {
                $field_value = Buwd_Encrypt::encrypt($field_value);
            }

            if ($field_name == 'glacier_newvault' && $field_value != '') {
                $this->set_keys($job_id);
                if ($this->create_vault($field_value)) {
                    Buwd_Options::update_job_option($job_id, 'glacier_vault', $field_value);
                }
            }

            if ($field_name == 'glacier_filedelete') {
                $field_value = (int)$field_value;
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }
    }

    public function delete_file($backup)
    {
        $this->vault = $backup['folder'];
        $this->region = $backup['region'];
        $this->key = $backup['key'];
        $this->secret = Buwd_Encrypt::decrypt($backup['secret']);

        //delete from folder
        try {
            if (!$this->delete_object($backup['archive_id'])) {

                return false;
            }
        } catch (GlacierException $e) {
            return false;
        }

        return true;
    }

    public function download_file($backup)
    {
        $file = $backup['file'];
        $size = $backup['size'];
        $this->vault = $backup['folder'];
        $this->region = $backup['region'];
        $this->key = $backup['key'];
        $this->secret = Buwd_Encrypt::decrypt($backup['secret']);

        if (false === $result = $this->get_output($backup['archive_id'])) {
            set_site_transient('buwd_backups_error', __('File not found.', 'buwd'));
            Buwd_Helper::redirect(array('page' => 'buwd_backups'));
        }

        @set_time_limit(3000);
        nocache_headers();
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: ' . Buwd_File::mime_content_type($file));
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . $size);

        echo $result->get('body');
        die();
    }

    public function get_errors()
    {
        return $this->errors;
    }

    private function upload_file($filePath, Buwd_Job $job_object)
    {
        try {
            $glacier = GlacierClient::factory($this->get_config());

            $vaults = $glacier->listVaults();
            if (empty($vaults['VaultList'])) {
                $this->errors[] = sprintf(__('Vault not found for "%s" region', 'buwd'), $this->get_region($this->region));

                return false;
            }

            $vault_exist = false;
            foreach ($vaults['VaultList'] as $vault) {
                if ($vault['VaultName'] == $this->vault) {
                    $vault_exist = true;
                    break;
                }
            }

            if ($vault_exist) {
                $job_object->buwd_logger->set_log(sprintf(__('Connected to Amazon Glacier vault "%1$s" in %2$s region', 'buwd'), $this->vault, $this->region));
                $job_object->update_progress();
            } else {
                $job_object->buwd_logger->set_log(sprintf(__('Amazon Glacier vault "%s" does not exist in "%s" region.', 'buwd'), $this->vault, $this->region), E_USER_ERROR);
                $job_object->update_progress();

                return true;
            }

            $job_object->buwd_logger->set_log(__('Upload to Amazon Glacier has started.', 'buwd'));
            $job_object->update_progress();

            $partSize = 2 * 1024 * 1024;
            if ($file = fopen($filePath, 'r')) {
                if (!isset($job_object->steps_data[$job_object->current_step]['uploadId'])) {
                    $multiupload = $glacier->initiateMultipartUpload(
                        array(
                            'vaultName' => $this->vault,
                            'partSize'  => $partSize,

                        )
                    );
                    $job_object->steps_data[$job_object->current_step]['uploadId'] = $multiupload->get('uploadId');
                }


                ///thanks stackoverflow
                $th = new \Aws\Glacier\TreeHash();
                $th->update(fread($file, filesize($filePath)));
                $hash = $th->complete();
                $hash = bin2hex($hash);

                if (!$job_object->steps_data[$job_object->current_step]['sub_step']) {
                    $job_object->steps_data[$job_object->current_step]['sub_step'] = 0;
                }

                fseek($file, $job_object->steps_data[$job_object->current_step]['sub_step']);
                $fileSize = filesize($filePath);

                while (!feof($file)) {
                    $job_object->restart_if_needed();
                    $job_object->check_if_stopped();

                    $chunk_data = fread($file, $partSize);

                    $start_byte = $job_object->steps_data[$job_object->current_step]['sub_step'];
                    $end_byte = ($job_object->steps_data[$job_object->current_step]['sub_step'] + $partSize - 1);

                    $lastByte = min($end_byte, $fileSize - 1);

                    if (strlen($chunk_data) == 0) {
                        break;
                    }

                    $glacier->uploadMultipartPart(
                        array(
                            'vaultName' => $this->vault,
                            'uploadId'  => $job_object->steps_data[$job_object->current_step]['uploadId'],
                            'body'      => $chunk_data,
                            'range'     => 'bytes ' . $start_byte . '-' . $lastByte . '/*',
                        ));

                    $job_object->steps_data[$job_object->current_step]['sub_step'] = $job_object->steps_data[$job_object->current_step]['sub_step'] + (strlen($chunk_data));
                }

                $result = $glacier->completeMultipartUpload(array(
                    'vaultName'   => $this->vault,
                    'uploadId'    => $job_object->steps_data[$job_object->current_step]['uploadId'],
                    'archiveSize' => filesize($filePath),
                    'checksum'    => $hash,
                ));

                //getCompleteParams()
                fclose($file);

                if ($archiveId = $result->get('archiveId')) {
                    $job_object->buwd_logger->set_log(sprintf(__('Backup was transferred to %s vault.', 'buwd'), $this->vault));
                    $job_object->update_progress();

                    $last_file = array();
                    $last_file['file'] = $job_object->backup_file;
                    $last_file['folder'] = $this->vault;
                    $last_file['time'] = current_time('timestamp', true);
                    $last_file['size'] = filesize($job_object->backup_folder . $job_object->backup_file);
                    $last_file['archive_id'] = $archiveId;
                    $last_file['region'] = $this->region;
                    $last_file['key'] = $this->key;
                    $last_file['secret'] = $job_object->job['glacier_privatekey'];
                    $last_file['jid'] = $job_object->job_id;
                    $last_file['dest'] = 'amazon-glacier';
                    $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                    $dest_files = get_site_option('buwd-dest-amazon-glacier-' . $job_object->job_id, array());
                    $dest_files[] = $last_file;

                    update_site_option('buwd-dest-amazon-glacier-' . $job_object->job_id, $dest_files);
                } else {
                    $job_object->buwd_logger->set_log(__('Error transfering backup to Amazon Glacier.', 'buwd'), E_USER_ERROR);
                }
            } else {
                $job_object->buwd_logger->set_log(__('Could not open source file for transfer.', 'buwd'), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }

        } catch (GlacierException $e) {
            $job_object->buwd_logger->set_log(sprintf(__('Glacier Service API: %s', 'buwd'), $e->getMessage()), E_USER_ERROR, $e->getFile(), $e->getLine());
            $job_object->update_progress();

            return false;
        }
    }

    private function render_tab()
    {
        $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        $options = $this->get_options($job_id);
        $group_class = new Buwd_Form_Group(array($options));
        $groups = $group_class->get_groups();
        $group_html = array();
        foreach ($groups as $g_name => $group) {
            $group_html['title'] = $group->title;
            $group_html['content'] = $group_class->render_group($g_name);
        }

        return '<table>' . $group_html['content'] . '</table>';
    }

    private function delete_object($archive_id)
    {
        try {
            $glacier = GlacierClient::factory($this->get_config());
            $args = array(
                'vaultName' => $this->vault,
                'archiveId' => $archive_id
            );
            if ($glacier->deleteArchive($args)) {
                /*$backup_files = get_site_option( 'buwd-glacier-' . $job_id );
                $vault_files  = $backup_files[ $this->vault ];

                foreach ( $vault_files as $key => $value ) {
                    if ( $archive_id == $value['archive_id'] ) {
                        unset( $vault_files[ $key ] );
                    }
                }

                $backup_files[ $this->vault ] = $vault_files;
                update_site_option( 'buwd-glacier-' . $job_id, $backup_files );*/
            }

        } catch (GlacierException $e) {
            echo $e->getMessage();
        }
    }


    /**
     *
     */
    public static function display_messages()
    {
        if ($error = get_site_transient('buwd_glacier_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_glacier_error');
        } else if ($updated = get_site_transient('buwd_glacier_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_glacier_updated');
        }
    }

    /**
     * Return an instance of this class.
     */
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}