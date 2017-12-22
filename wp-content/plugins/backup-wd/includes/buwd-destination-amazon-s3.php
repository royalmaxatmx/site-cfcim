<?php

/**
 *
 */
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

require_once BUWD_DIR . '/vendor/autoload.php';

class Buwd_Destination_Amazon_S3
{
    protected static $instance = null;

    private $key = '';
    private $secret = '';
    private $region = '';
    private $bucket = '';
    private $folderName = '';
    private $fileKey = '';
    public $errors = array();

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to Amazon S3', 'buwd');
        $this->info['sync_title'] = __('Syncing files to Amazon S3', 'buwd');
        $this->info['name'] = __('Amazon S3', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = true;
    }

    public function defaults()
    {
        $defaults = array(
            's3service'          => 'us-east-1',
            's3accesskey'        => '',
            's3privatekey'       => '',
            's3bucket'           => '',
            's3newbucket'        => '',
            's3bucketfolder'     => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            's3filedelete'       => 15,
            's3filedeletesync'   => '1',
            's3multiupload'      => '1',
            's3storageclass'     => 'standard',
            's3serverencryption' => '1',
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        $this->set_keys($job_id);
        $services = array(
            "us-east-1"      => "Amazon S3: US East (N. Virginia)",
            "us-west-1"      => "Amazon S3: US West (Northern California)",
            "us-west-2"      => "Amazon S3: US West (Oregon)",
            "eu-west-1"      => "Amazon S3: EU (Ireland)",
            "eu-central-1"   => "Amazon S3: EU (Germany)",
            "ap-south-1"     => "Amazon S3: Asia Pacific (Mumbai)",
            "ap-northeast-1" => "Amazon S3: Asia Pacific (Tokyo)",
            "ap-northeast-2" => "Amazon S3: Asia Pacific (Seoul)",
            "ap-southeast-1" => "Amazon S3: Asia Pacific (Singapore)",
            "ap-southeast-2" => "Amazon S3: Asia Pacific (Sydney)",
            "sa-east-1"      => "Amazon S3: South America (Sao Paulo)",
            "cn-north-1"     => "Amazon S3: China (Beijing)"
        );

        $bucket_list = $this->get_bucket_list($job_id);
        $bucket_selection_class = $bucket_list['type'] == 'paragraph' ? 'buwd-hide' : '';

        $options = array(
            'key'    => 'destination-amazon-s3',
            'title'  => '',
            'fields' => array(
                array(
                    'label'   => __('Select S3 service', 'buwd'),
                    'header'  => __('S3 Service', 'buwd'),
                    'id'      => 's3service',
                    'name'    => 's3service',
                    'type'    => 'select',
                    'class'   => array(
                        'buwd-large-text',
                    ),
                    'choices' => $services,
                    'value'   => esc_attr(Buwd_Options::get($job_id, 's3service')),
                    'hint'    => array(
                        'html' => '<p class="description">Choose the region of Amazon S3 service for your backup files.</p>'
                    ),

                ),
                array(
                    'label'  => __('Access Key', 'buwd'),
                    'header' => __('S3 Access Keys', 'buwd'),
                    'id'     => 's3accesskey',
                    'name'   => 's3accesskey',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 's3accesskey')),
                    'hint'   => array(
                        'html' => '<p class="description">Provide Access Key for your Amazon S3 cloud account.</p>'
                    ),
                ),
                array(
                    'label' => __('Private Key', 'buwd'),
                    'id'    => 's3privatekey',
                    'name'  => 's3privatekey',
                    'type'  => 'password',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 's3privatekey'))),
                    'hint'  => array(
                        'html' => '<p class="description">Provide Private Key for your Amazon S3 cloud account.</p>'
                    ),
                ),
                array(
                    'label'   => __('Select Bucket', 'buwd'),
                    'header'  => __('S3 Bucket', 'buwd'),
                    'id'      => 's3bucket',
                    'name'    => 's3bucket',
                    'type'    => $bucket_list['type'],
                    'choices' => $bucket_list['buckets'],
                    'class'   => array(
                        'buwd-large-text buwd-error',
                    ),
                    'value'   => $bucket_list['value'],
                    'hint'    => array(
                        'html' => '<p class="description ' . $bucket_selection_class . '">Select a bucket from your Amazon S3 service. Make sure the bucket belongs to S3 region you have selected.</p>'
                    ),
                ),
                array(
                    'label' => __('Create a new bucket', 'buwd'),
                    'id'    => 's3newbucket',
                    'name'  => 's3newbucket',
                    'type'  => 'text',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => '',
                    'hint'  => array(
                        'html' => '<p class="description">This option lets you to create a new bucket on your Amazon S3 service. The bucket will be added when you save tab options.</p>'
                    ),
                ),
                array(
                    'label'  => __('Folder name in bucket', 'buwd'),
                    'header' => __('S3 Backup settings', 'buwd'),
                    'id'     => 's3bucketfolder',
                    'name'   => 's3bucketfolder',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 's3bucketfolder')),
                    'hint'   => array(
                        'html' => '<p class="description">Provide the folder name on your Amazon S3 bucket, where the backup files will be stored.</p>'
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 's3filedelete',
                    'name'       => 's3filedelete',
                    'type'       => 'number', // to number
                    'class'      => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'       => array(
                        'min' => "0"
                    ),
                    'value'      => esc_html(Buwd_Options::get($job_id, 's3filedelete')),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') == 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in Amazon S3 bucket folder. When the limit is reached, the oldest backup file will be deleted.</p>'
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 's3filedeletesync',
                    'name'       => 's3filedeletesync',
                    'type'       => 'checkbox', // to number
                    'class'      => array(),
                    'choices'    => [
                        '1' => 'Keep deleted files from previous backup sync.'
                    ],
                    'value'      => Buwd_Options::get($job_id, 's3filedeletesync'),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') != 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<p class="description">Uncheck the option to remove the deleted files.</p><span class="buwd-error">Please note that if you uncheck this option, you will loose all previous files in backup storage folder</span>'
                    ),
                ),
                array(
                    'label'      => __('Multipart Upload', 'buwd'),
                    'id'         => 's3multiupload',
                    'name'       => 's3multiupload',
                    'type'       => 'checkbox',
                    'choices'    => array(
                        '1' => 'Use multipart upload for uploading a file'
                    ),
                    'hint'       => array(
                        'html' => '<p class="description">Select this setting to enable multipart upload for backup files. Since the files are generally large, this option splits them into multiple chunks while uploading. Therefore, it is recommended to use this setting.
</p>',
                    ),
                    'value'      => Buwd_Options::get($job_id, 's3multiupload'),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') == 'archive' ? true : false,
                ),
                array(
                    'label'   => __('Amazon: Storage Class ', 'buwd'),
                    'header'  => __('Amazon specific settings ', 'buwd'),
                    'id'      => 's3storageclass',
                    'name'    => 's3storageclass',
                    'type'    => 'select',
                    'class'   => array(
                        'buwd-medium-text',
                    ),
                    'choices' => array(
                        'standard'            => 'Standard',
                        'standard_infrequent' => 'Standard - Infrequent Access',
                        'reduced_redundancy'  => 'Reduced Redundancy'
                    ),
                    'value'   => esc_attr(Buwd_Options::get($job_id, 's3storageclass')),
                    'hint'    => array(
                        'html' => '<p class="description">Select the <a href="https://aws.amazon.com/s3/storage-classes/" target="_blank">storage class</a> of your Amazon S3 service.</p>'
                    ),
                ),
                array(
                    'label'   => __('Server side encryption', 'buwd'),
                    'id'      => 's3serverencryption',
                    'name'    => 's3serverencryption',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => 'Save files encrypted (AES256) on server.'
                    ),
                    'value'   => Buwd_Options::get($job_id, 's3serverencryption'),
                    'hint'    => array(
                        'html' => '<p class="description">Enable this option to encrypt the backup files for data protection.</p>'
                    ),
                ),
            ),
        );

        return $options;
    }

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to Amazon S3.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $this->key = $job_object->job['s3accesskey'];
        $this->secret = Buwd_Encrypt::decrypt($job_object->job['s3privatekey']);
        $this->region = $job_object->job['s3service'];
        $this->bucket = $job_object->job['s3bucket'];
        $this->folderName = $job_object->job['s3bucketfolder'];
        $this->fileKey = $this->folderName . $job_object->backup_file;

        try {
            //check if bucket exist
            if ($job_object->steps_data[$job_object->current_step]['step_done'] < $job_object->backup_file_size) {
                $s3 = S3Client::factory($this->get_config());

                if ($s3->doesBucketExist($this->bucket)) {
                    $bucket_region = $s3->getBucketLocation(array('Bucket' => $this->bucket));

                    $job_object->buwd_logger->set_log(sprintf(__('Connected to Amazon S3 Bucket "%s" in %s region.', 'buwd'), $this->bucket, $bucket_region['LocationConstraint']));
                } else {
                    $job_object->buwd_logger->set_log(sprintf(__('Amazon S3 Bucket "%s" does not exist in "%s" region.', 'buwd'), $this->bucket, $this->region), E_USER_ERROR);
                    $job_object->update_progress();

                    return true;
                }
                //upload file
                $job_object->buwd_logger->set_log(__('Upload to Amazon S3 has started.', 'buwd'));
                $job_object->update_progress();
            }

            $arg = array(
                'Bucket' => $this->bucket,
                'Key'    => $this->fileKey,
            );
            if (!empty($job_object->job['s3storageclass'])) {
                $arg['StorageClass'] = strtoupper($job_object->job['s3storageclass']);
            }
            if (!empty($job_object->job['s3serverencryption'])) {
                $arg['ServerSideEncryption'] = 'AES256';
            }

            if (!$job_object->job['s3multiupload']) {
                $arg['SourceFile'] = $job_object->backup_folder . $job_object->backup_file;

                $s3->putObject($arg);
            } else {
                try {
                    if ($content = fopen($job_object->backup_folder . $job_object->backup_file, 'r')) {
                        if (empty ($job_object->steps_data[$job_object->current_step]['UploadId'])) {
                            $res = $s3->createMultipartUpload($arg);
                            $job_object->steps_data[$job_object->current_step]['UploadId'] = $res['UploadId'];
                        }

                        if (!isset($job_object->steps_data[$job_object->current_step]['parts'])) {
                            $job_object->steps_data[$job_object->current_step]['parts'] = array();
                        }
                        if (!isset($job_object->steps_data[$job_object->current_step]['partNumber'])) {
                            $job_object->steps_data[$job_object->current_step]['partNumber'] = 1;
                        }

                        if (!isset($job_object->steps_data[$job_object->current_step]['sub_step'])) {
                            $job_object->steps_data[$job_object->current_step]['sub_step'] = 0;
                        }

                        fseek($content, $job_object->steps_data[$job_object->current_step]['sub_step']);
                        while (!feof($content)) {
                            $job_object->restart_if_needed();
                            $job_object->check_if_stopped();

                            $part_upload = $s3->uploadPart(array(
                                'Bucket'     => $this->bucket,
                                'Key'        => $this->fileKey,
                                'UploadId'   => $job_object->steps_data[$job_object->current_step]['UploadId'],
                                'PartNumber' => $job_object->steps_data[$job_object->current_step]['partNumber'],
                                'Body'       => fread($content, 5 * 1024 * 1024),
                            ));

                            $job_object->steps_data[$job_object->current_step]['parts'][] = array(
                                'PartNumber' => $job_object->steps_data[$job_object->current_step]['partNumber']++,
                                'ETag'       => $part_upload['ETag'],
                            );

                            $job_object->steps_data[$job_object->current_step]['sub_step'] += (5 * 1024 * 1024);
                        }

                        $job_object->restart_if_needed();

                        $s3->completeMultipartUpload(array(
                            'Bucket'          => $this->bucket,
                            'UploadId'        => $job_object->steps_data[$job_object->current_step]['UploadId'],
                            'Key'             => $this->fileKey,
                            'MultipartUpload' => array(
                                'Parts' => $job_object->steps_data[$job_object->current_step]['parts'],
                            ),
                        ));

                        $head_obj = $s3->headObject(array(
                            'Bucket' => $this->bucket,
                            'Key'    => $this->fileKey
                        ));

                        if ($head_obj['ContentLength'] == filesize($job_object->backup_folder . $job_object->backup_file)) {
                            $job_object->steps_data[$job_object->current_step]['step_done'] = 1 + $job_object->backup_file_size;

                            $job_object->buwd_logger->set_log(sprintf(__('Backup was transferred to %s.', 'buwd'), $this->get_region_url($this->region) . '/' . $job_object->job['s3bucket'] . '/' . $job_object->job['s3bucketfolder'] . $job_object->backup_file));
                            $job_object->update_progress();

                            $last_file = array();
                            $utc = new DateTimeZone("UTC");
                            $last_file['file'] = $job_object->backup_file;
                            $last_file['folder'] = $job_object->job['s3bucketfolder'];
                            $last_file['time'] = $head_obj['LastModified']->setTimezone($utc)->getTimestamp();
                            $last_file['size'] = $head_obj['ContentLength'];
                            $last_file['bucket'] = $this->bucket;
                            $last_file['region'] = $this->region;
                            $last_file['key'] = $this->key;
                            $last_file['secret'] = $job_object->job['s3privatekey'];
                            $last_file['jid'] = $job_object->job_id;
                            $last_file['dest'] = 'amazon-s3';
                            $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                            $dest_files = get_site_option('buwd-dest-amazon-s3-' . $job_object->job_id, array());
                            $dest_files[] = $last_file;

                            update_site_option('buwd-dest-amazon-s3-' . $job_object->job_id, $dest_files);
                        } else {
                            $job_object->buwd_logger->set_log(sprintf(__('Could not transfer backup to Amazon S3 %1$d: %2$s', 'buwd'), $head_obj['status'], $head_obj['Message']), E_USER_ERROR);
                            $job_object->update_progress();

                            return false;
                        }

                        if (isset($content) && is_resource($content)) {
                            fclose($content);
                        }
                    } else {
                        $job_object->buwd_logger->set_log(__('Could not open source file for transfer.', 'buwd'), E_USER_ERROR);
                        $job_object->update_progress();

                        return false;
                    }
                } catch (S3Exception $e) {
                    if (!empty ($job_object->steps_data[$job_object->current_step]['UploadId'])) {
                        $s3->abortMultipartUpload(array(
                            'Bucket'   => $this->bucket,
                            'Key'      => $this->fileKey,
                            'UploadId' => $job_object->steps_data[$job_object->current_step]['UploadId']
                        ));
                    }
                    if (isset($content) && is_resource($content)) {
                        fclose($content);
                    }

                    $job_object->buwd_logger->set_log(sprintf(__('S3 Service API: %s', 'buwd'), $e->getMessage()), E_USER_ERROR, $e->getFile(), $e->getLine());
                    $job_object->update_progress();

                    return false;
                }
            }
        } catch (S3Exception $e) {
            $job_object->buwd_logger->set_log(sprintf(__('S3 Service API: %s', 'buwd'), $e->getMessage()), E_USER_ERROR, $e->getFile(), $e->getLine());
            $job_object->update_progress();

            return false;
        }

        $backup_files = $this->get_objects_container();
        $deleted = 0;
        $files_to_delete = array();
        if (!empty($job_object->job['s3filedelete']) && $job_object->job['s3filedelete'] > 0 && count($backup_files) > $job_object->job['s3filedelete']) {
            while (count($backup_files) > $job_object->job['s3filedelete']) {
                $file = array_shift($backup_files);
                if ($this->delete_object($file)) {
                    $deleted++;
                    $files_to_delete[] = basename($file);
                }
            }
        }


        if (!empty($files_to_delete)) {
            Buwd_Options::backup_bulk_delete('buwd-dest-amazon-s3-' . $job_object->job_id, $files_to_delete);
        }

        if ($deleted > 0) {
            $job_object->buwd_logger->set_log(sprintf(__('%d files were successfully deleted from Amazon S3.', 'buwd'), $deleted));
            $job_object->update_progress();
        }

        return true;
    }

    public function run_sync(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to sync files to Amazon S3.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $this->key = $job_object->job['s3accesskey'];
        $this->secret = Buwd_Encrypt::decrypt($job_object->job['s3privatekey']);
        $this->region = $job_object->job['s3service'];
        $this->bucket = $job_object->job['s3bucket'];
        $this->folderName = $job_object->job['s3bucketfolder'];
        $this->fileKey = $this->folderName . $job_object->backup_file;

        $files_folders = array();
        $files_folders_dir = Buwd::get_plugin_data('temp_folder_dir') . 'job_files_folders.php';
        if (file_exists($files_folders_dir)) {
            $files_folders = unserialize(file_get_contents($files_folders_dir));
        }
        if (!isset($files_folders['files'])) {
            $files_folders['files'] = array();
        }

        if (!isset($job_object->steps_data[$job_object->current_step]['files'])) {
            $job_object->steps_data[$job_object->current_step]['files'] = array();
            $job_object->steps_data[$job_object->current_step]['sub_step'] = 's3_files';
            $job_object->steps_data[$job_object->current_step]['files_count'] = 0;
            $job_object->steps_data[$job_object->current_step]['folder_size'] = 0;
        }

        try {
            $s3 = new S3Client($this->get_config());

            if ($s3->doesBucketExist($this->bucket)) {
                $bucket_region = $s3->getBucketLocation(array('Bucket' => $this->bucket));
                $job_object->buwd_logger->set_log(sprintf(__('Connected to Amazon S3 Bucket "%1$s" in %2$s region.', 'buwd'), $this->bucket, $bucket_region['LocationConstraint']));
                $job_object->update_progress();
            } else {
                $job_object->buwd_logger->set_log(sprintf(__('Amazon S3 Bucket "%s" does not exist in "%s" region.', 'buwd'), $this->bucket, $this->region), E_USER_ERROR);
                $job_object->update_progress();

                return true;
            }

            $job_object->buwd_logger->set_log(__('Upload to Amazon S3 has started.', 'buwd'));
            $job_object->update_progress();

            //get files list
            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 's3_files') {
                $job_object->check_if_stopped();
                $job_object->restart_if_needed();

                $args = array(
                    'Bucket' => $this->bucket,
                    'Prefix' => $this->folderName,
                );
                if (isset($job_object->steps_data[$job_object->current_step]['marker'])) {
                    $args['Marker'] = $job_object->steps_data[$job_object->current_step]['marker'];
                }

                $objects = $s3->getIterator('ListObjects', $args);
                if (is_object($objects) && !empty($objects)) {
                    foreach ($objects as $object) {
                        $job_object->steps_data[$job_object->current_step]['files'][$object['Key']] = trim($object['ETag'], '"');
                        $job_object->steps_data[$job_object->current_step]['files_count']++;
                        $job_object->steps_data[$job_object->current_step]['marker'] = $object['Key'];
                    }
                }
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_files';
            }

            //sync files
            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_files') {
                if (isset($files_folders['folders'])) {
                    foreach ($files_folders['folders'] as $folder_key => $folder) {
                        $folder_files = isset($files_folders['files'][$folder]) ? $files_folders['files'][$folder] : array();

                        if (!isset($job_object->steps_data[$job_object->current_step][$folder_key])) {
                            $job_object->steps_data[$job_object->current_step][$folder_key] = array();
                            $job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] = -1;
                        }

                        foreach ($folder_files as $file_key => $f_file) {
                            if ($job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] >= $file_key) {
                                continue;
                            }

                            $job_object->check_if_stopped();
                            $job_object->restart_if_needed();

                            if (strpos($f_file, $job_object->job['abs_path']) !== false) {
                                $file_dirname = $job_object->job['s3bucketfolder'] . substr($f_file, strlen($job_object->job['abs_path']));
                            } else {
                                $file_dirname = $job_object->job['s3bucketfolder'] . substr($f_file, strlen(dirname($job_object->job['abs_path']) . '/'));
                            }

                            if (!isset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname]) || (isset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname]) && $job_object->steps_data[$job_object->current_step]['files'][$file_dirname] != md5_file($f_file))) {
                                $file_args = array(
                                    'Bucket'      => $this->bucket,
                                    'Key'         => $file_dirname,
                                    'SourceFile'  => $f_file,
                                    'ContentType' => Buwd_File::mime_content_type($f_file)
                                );
                                if (!empty($job_object->job['s3storageclass'])) {
                                    $file_args['StorageClass'] = strtoupper($job_object->job['s3storageclass']);
                                }
                                if (!empty($job_object->job['s3serverencryption'])) {
                                    $file_args['ServerSideEncryption'] = 'AES256';
                                }

                                $s3->putObject($file_args);
                                $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($f_file);

                                $job_object->buwd_logger->set_log(sprintf(__('%s file was uploaded to Amazon S3.', 'buwd'), $file_dirname));
                                $job_object->update_progress();
                            }

                            if (isset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname])) {
                                unset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname]);
                            }

                            $job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] = $file_key;
                        }
                    }
                }
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_extra_files';
            }

            //sync extra files
            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_extra_files') {
                if ($job_object->extra_files) {
                    foreach ($job_object->extra_files as $extra_file) {
                        $job_object->check_if_stopped();
                        $job_object->restart_if_needed();
                        $extra_file_dirname = $job_object->job['s3bucketfolder'] . basename($extra_file);
                        if (!isset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname]) || (isset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname]) && $job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname] != md5_file($extra_file))) {
                            $extra_file_args = array(
                                'Bucket'      => $this->bucket,
                                'Key'         => $extra_file_dirname,
                                'SourceFile'  => $extra_file,
                                'ContentType' => Buwd_File::mime_content_type($extra_file)
                            );

                            if (!empty($job_object->job['s3storageclass'])) {
                                $extra_file_args['StorageClass'] = strtoupper($job_object->job['s3storageclass']);
                            }
                            if (!empty($job_object->job['s3serverencryption'])) {
                                $extra_file_args['ServerSideEncryption'] = 'AES256';
                            }
                            $s3->putObject($extra_file_args);
                            $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($extra_file);
                            $job_object->buwd_logger->set_log(sprintf(__('%s extra file was uploaded to Amazon S3.', 'buwd'), $extra_file_dirname));
                            $job_object->update_progress();
                        }

                        if (isset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname])) {
                            unset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname]);
                        }
                    }
                }
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'delete_files';
            }

            $sync_file = get_site_option('buwd-dest-amazon-s3-sync-' . $job_object->job_id, array());
            $sync_file['file'] = 'Synchronized';
            $sync_file['folder'] = $job_object->job['s3bucketfolder'];
            $sync_file['time'] = current_time('timestamp', true);
            $sync_file['size'] = $job_object->steps_data[$job_object->current_step]['folder_size'];
            $sync_file['jid'] = $job_object->job_id;
            $sync_file['dest'] = 'amazon-s3';
            $sync_file['logfile'] = basename($job_object->buwd_logger->logfile);
            $sync_file['sync'] = 1;

            update_site_option('buwd-dest-amazon-s3-sync-' . $job_object->job_id, $sync_file);

            //delete files
            if (!$job_object->job['s3filedeletesync'] && $job_object->steps_data[$job_object->current_step]['sub_step'] == 'delete_files') {
                if (!empty($job_object->steps_data[$job_object->current_step]['files'])) {
                    $job_object->buwd_logger->set_log(__('Non-existent files will be deleted from Amazon S3.', 'buwd'));
                    $job_object->update_progress();
                    foreach (array_keys($job_object->steps_data[$job_object->current_step]['files']) as $s3_file) {
                        $job_object->restart_if_needed();

                        $args = array(
                            'Bucket' => $job_object->job['s3bucket'],
                            'Key'    => $s3_file
                        );
                        $s3->deleteObject($args);
                        $job_object->buwd_logger->set_log(sprintf(__('%s file was deleted from Amazon S3.', 'buwd'), $s3_file));
                        $job_object->update_progress();
                        unset($job_object->steps_data[$job_object->current_step]['files'][$s3_file]);
                    }
                }
            }

        } catch (S3Exception $e) {
            $job_object->buwd_logger->set_log(E_USER_ERROR, sprintf(__('S3 Service API: %s', 'buwd'), $e->getMessage()), $e->getFile(), $e->getLine());
            $job_object->update_progress();

            return false;
        }

        return true;
    }

    public function get_errors()
    {
        return $this->errors;
    }

    public function set_keys($job_id)
    {
        $this->region = esc_html(Buwd_Options::get($job_id, 's3service'));
        $this->key = esc_html(Buwd_Options::get($job_id, 's3accesskey'));
        $this->secret = Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 's3privatekey')));
    }

    public function get_objects_container()
    {
        $bucket = $this->bucket;
        $prefix = (string)$this->folderName;
        $objects_by_date = array();

        try {
            $s3 = S3Client::factory($this->get_config());
            $objects = $s3->listObjects(array('Bucket' => $bucket, 'Prefix' => $prefix));
            if (is_object($objects)) {
                $UTC = new DateTimeZone("UTC");
                foreach ($objects['Contents'] as $object) {
                    $date_modified = strtotime($object['LastModified']->setTimezone($UTC)->format(\DateTime::ISO8601));
                    if ($prefix != '') {
                        if (substr($object['Key'], -1) != '/') {
                            $objects_by_date[$date_modified] = $object['Key'];
                        }
                    } else {
                        if (strpos($object['Key'], '/') === false) {
                            $objects_by_date[$date_modified] = $object['Key'];
                        }
                    }
                }
            }
            ksort($objects_by_date);
        } catch (S3Exception $e) {

            return $objects_by_date;
        }

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
            'region'      => (string)$region,
            'credentials' => array(
                'key'    => $key,
                'secret' => $secret,
            ),
            'http'        => array(
                'verify' => Buwd::get_plugin_data('cacert'),
            ),
        );
    }

    public function get_bucket_list($job_id)
    {
        $buckets_array = array();
        $type = 'select';

        try {
            $s3 = new S3Client($this->get_config());
            $s3->getRegion();
            $buckets = $s3->listBuckets();

            foreach ($buckets['Buckets'] as $bucket) {
                $buckets_array[$bucket['Name']] = esc_html($bucket['Name']);
            }
            $value = esc_html(Buwd_Options::get($job_id, 's3bucket'));
        } catch (S3Exception  $e) {
            $type = 'paragraph';
            $value = __('Access or Private keys are incorrect.', 'buwd');
        }


        return array('buckets' => $buckets_array, 'type' => $type, 'value' => $value);
    }

    public function get_region_url($region)
    {
        switch ($region) {
            case 'us-east-1':
                return 'https://s3.amazonaws.com';
            case 'us-west-1':
                return 'https://s3-us-west-1.amazonaws.com';
            case 'us-west-2':
                return 'https://s3-us-west-2.amazonaws.com';
            case 'eu-west-1':
                return 'https://s3-eu-west-1.amazonaws.com';
            case 'eu-central-1':
                return 'https://s3-eu-central-1.amazonaws.com';
            case 'ap-south-1':
                return 'https://s3-ap-south-1.amazonaws.com';
            case 'ap-northeast-1':
                return 'https://s3-ap-northeast-1.amazonaws.com';
            case 'ap-northeast-2':
                return 'https://s3-ap-northeast-2.amazonaws.com';
            case 'ap-southeast-1':
                return 'https://s3-ap-southeast-1.amazonaws.com';
            case 'ap-southeast-2':
                return 'https://s3-ap-southeast-2.amazonaws.com';
            case 'sa-east-1':
                return 'https://s3-sa-east-1.amazonaws.com';
            case 'cn-north-1':
                return 'https://cn-north-1.amazonaws.com';

            default:
                return '';
        }
    }

    public function run_ajax()
    {
        $html = $this->render_tab();
        echo $html;
        die;
    }

    public function is_valid($job_options)
    {
        if (empty($job_options['s3accesskey']) || empty($job_options['s3privatekey'])) {
            return false;
        }

        $this->key = $job_options['s3accesskey'];
        $this->secret = Buwd_Encrypt::decrypt($job_options['s3privatekey']);
        $this->region = $job_options['s3service'];
        $this->bucket = $job_options['s3bucket'];
        try {
            $s3 = S3Client::factory($this->get_config());

            if (!$s3->doesBucketExist($this->bucket)) {
                $this->errors[] = sprintf(__('Amazon S3 Bucket "%s" does not exist in "%s" region.', 'buwd'), $this->bucket, $this->region);

                return false;
            }

        } catch (S3Exception  $e) {
            return false;
        }

        return true;
    }

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';

            if (in_array($field_name, array('s3service', 's3accesskey', 's3privatekey', 's3bucket', 's3newbucket', 's3bucketfolder', 's3storageclass'))) {
                $field_value = sanitize_text_field($field_value);
            }

            if ($field_name == 's3privatekey') {
                $field_value = Buwd_Encrypt::encrypt($field_value);
            }

            if ($field_name == 's3bucketfolder') {
                $field_value = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim($field_value))));
                if ($field_value === '/') {
                    $field_value = '';
                } else {
                    if (substr($field_value, 0, 1) == '/') {
                        $field_value = substr($field_value, 1);
                    }
                }
            }

            if ($field_name == 's3newbucket' && $field_value != '') {
                $this->set_keys($job_id);
                if ($this->create_bucket($field_value)) {
                    Buwd_Options::update_job_option($job_id, 's3bucket', $field_value);
                }
            }

            if ($field_name == 's3filedelete') {
                $field_value = (int)$field_value;
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }

    }

    public function delete_file($backup)
    {
        $this->bucket = $backup['bucket'];
        $this->region = $backup['region'];
        $this->key = $backup['key'];
        $this->secret = Buwd_Encrypt::decrypt($backup['secret']);

        //delete from folder
        try {
            if (!$this->delete_object($backup['folder'] . $backup['file'])) {

                return false;
            }
        } catch (S3Exception $e) {
            return false;
        }

        return true;
    }

    public function download_file($backup)
    {
        $file = $backup['file'];
        $folder = $backup['folder'];
        $size = $backup['size'];
        $this->bucket = $backup['bucket'];
        $this->region = $backup['region'];
        $this->key = $backup['key'];
        $this->secret = Buwd_Encrypt::decrypt($backup['secret']);

        if (false === $result = $this->get_object($folder . $file)) {
            set_site_transient('buwd_backups_error', __('File not found.', 'buwd'));
            Buwd_Helper::redirect(array('page' => 'buwd_backups'));
        }

        @set_time_limit(3000);
        nocache_headers();
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Description: File Transfer');
        header("Content-Type: {$result['ContentType']}");
        header('Content-Disposition: attachment; filename="' . basename($backup['file']) . '"');
        header('Content-Length: ' . $size);

        echo $result['Body'];
        die();
    }

    public function get_object($key)
    {
        $bucket = $this->bucket;
        try {
            $s3 = S3Client::factory($this->get_config());
            $result = $s3->getObject(array('Bucket' => $bucket, 'Key' => $key));

        } catch (S3Exception $e) {
            return false;
        }

        return $result;
    }


    private function delete_object($key)
    {
        $bucket = $this->bucket;
        try {
            $s3 = S3Client::factory($this->get_config());

            $s3->deleteObject(array('Bucket' => $bucket, 'Key' => $key));
        } catch (S3Exception $e) {

            return false;
        }

        return true;
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

    private function create_bucket($bucketName)
    {
        try {
            $s3 = S3Client::factory($this->get_config());
            if (!$s3->doesBucketExist($bucketName)) {
                $s3->createBucket(array('Bucket' => $bucketName, 'CreateBucketConfiguration' => ['LocationConstraint' => $this->region]));
            }

        } catch (S3Exception $e) {

            return false;
        }

        return true;
    }

    /**
     *
     */
    public static function display_messages()
    {
        if ($error = get_site_transient('buwd_s3_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_s3_error');
        } else if ($updated = get_site_transient('buwd_s3_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_s3_updated');
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