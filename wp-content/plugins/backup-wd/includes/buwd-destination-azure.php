<?php

/**
 *
 */
use MicrosoftAzure\Storage\Blob\Models\Block;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Common\ServiceException;
use MicrosoftAzure\Storage\Common\ServicesBuilder;

require_once BUWD_DIR . '/vendor/autoload.php';

class Buwd_Destination_Azure
{
    protected static $instance = null;
    private $acc_name = '';
    private $access_key = '';
    private $container = '';
    private $blobKey = '';
    private $contfolder = '';
    private $errors = array();

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to Microsoft Azure', 'buwd');
        $this->info['sync_title'] = __('Syncing files to Microsoft Azure', 'buwd');
        $this->info['name'] = __('MS Azure', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = true;
    }

    public function defaults()
    {
        $defaults = array(
            'azurename'           => '',
            'azurekey'            => '',
            'azurenewcont'        => '',
            'azurefolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            'azurefiledelete'     => 15,
            'azurefiledeletesync' => '1'
        );

        return $defaults;
    }

    /**
     * @param $job_id
     *
     * @return array
     */
    public function get_options($job_id)
    {
        $this->set_keys($job_id);
        $container_list = $this->get_container_list($job_id);

        $container_selection_class = $container_list['type'] == 'paragraph' ? 'buwd-hide' : '';
        $options = array(
            'key'    => 'destination-azure',
            'title'  => '',
            'fields' => array(
                array(
                    'label'  => __('Account name', 'buwd'),
                    'header' => __('MS Azure access keys', 'buwd'),
                    'id'     => 'azurename',
                    'name'   => 'azurename',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'azurename')),
                    'hint'   => array(
                        'html' => '<p class="description">Provide your Microsoft Azure account name.</p>',
                    ),
                ),
                array(
                    'label' => __('Access key', 'buwd'),
                    'id'    => 'azurekey',
                    'name'  => 'azurekey',
                    'type'  => 'password',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 'azurekey'))),
                    'hint'  => array(
                        'html' => '<p class="description">Write the access key to your Microsoft Azure account.</p>',
                    ),
                ),
                array(
                    'label'   => __('Select container', 'buwd'),
                    'header'  => __('Blob container', 'buwd'),
                    'id'      => 'azurecontainer',
                    'name'    => 'azurecontainer',
                    'type'    => $container_list['type'],
                    'choices' => $container_list['containers'],
                    'class'   => array(
                        'buwd-large-text buwd-error',
                    ),
                    'value'   => $container_list['value'],
                    'hint'    => array(
                        'html' => '<p class="description ' . $container_selection_class . '">Choose the Blob container where the backup files will be uploaded.</p>',
                    ),
                ),
                array(
                    'label' => __('Create a new container', 'buwd'),
                    'id'    => 'azurenewcont',
                    'name'  => 'azurenewcont',
                    'type'  => 'text',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => '',
                    'hint'  => array(
                        'html' => '<p class="description">Alternatively, you can create a new Blob container by providing its title in this input box. The container will be added when you save tab options.</p>',
                    ),
                ),
                array(
                    'label'  => __('Folder in container', 'buwd'),
                    'header' => __('Backup settings', 'buwd'),
                    'id'     => 'azurefolder',
                    'name'   => 'azurefolder',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'azurefolder')),
                    'hint'   => array(
                        'html' => '<p class="description">Specify the folder in selected container where the backup files will be uploaded.</p>',
                    )
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'azurefiledelete',
                    'name'       => 'azurefiledelete',
                    'type'       => 'number', // to number
                    'class'      => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'       => array(
                        'min' => "0"
                    ),
                    'value'      => esc_html(Buwd_Options::get($job_id, 'azurefiledelete')),
                    'hint'       => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in Microsoft Azure folder for backup. When the limit is reached, the oldest backup file will be deleted.</p>',
                    ),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') == 'archive' ? true : false,
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'azurefiledeletesync',
                    'name'       => 'azurefiledeletesync',
                    'type'       => 'checkbox', // to number
                    'class'      => array(),
                    'choices'    => array(
                        '1' => 'Keep deleted files from previous backup sync.'
                    ),
                    'value'      => Buwd_Options::get($job_id, 'azurefiledeletesync'),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') != 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<p class="description">Uncheck the option to remove the deleted files.</p><span class="buwd-error">Please note that if you uncheck this option, you will loose all previous files in backup storage folder</span>'
                    ),
                ),
            ),
        );

        return $options;
    }

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to Microsoft Azure.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $this->acc_name = $job_object->job['azurename'];
        $this->access_key = Buwd_Encrypt::decrypt($job_object->job['azurekey']);
        $this->container = $job_object->job['azurecontainer'];
        $this->blobKey = $job_object->job['azurefolder'] . $job_object->backup_file;
        $this->contfolder = $job_object->job['azurefolder'];

        //chunk upload add in the feuture
        try {
            $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());
            $containers = $blobClient->listContainers()->getContainers();
            $container_exist = false;
            foreach ($containers as $container) {
                if ($container->getName() == $this->container) {
                    $container_exist = true;
                    break;
                }
            }

            if ($container_exist) {
                $job_object->buwd_logger->set_log(sprintf(__('Connected to Microsoft Azure container "%s".', 'buwd'), $this->container));
            } else {
                $job_object->buwd_logger->set_log(sprintf(__('Microsoft Azure container "%s" does not exist.', 'buwd'), $this->container), E_USER_ERROR);

                return true;
            }

            if ($content = fopen($job_object->backup_folder . $job_object->backup_file, "rb")) {
                $job_object->buwd_logger->set_log(__('Upload to  Microsoft Azure has started.', 'buwd'));
                $job_object->update_progress();

                $chunk_size = 4 * 1024 * 1024;

                ////////////////////////////////////Chunk Upload
                if (!isset($job_object->steps_data[$job_object->current_step]['blockIDs'])) {
                    $job_object->steps_data[$job_object->current_step]['blockIDs'] = array();
                }

                if (!isset($job_object->steps_data[$job_object->current_step]['sub_step'])) {
                    $job_object->steps_data[$job_object->current_step]['sub_step'] = 0;
                }

                fseek($content, $job_object->steps_data[$job_object->current_step]['sub_step']);
                while (!feof($content)) {
                    ////Do Restart
                    $job_object->restart_if_needed();
                    $job_object->check_if_stopped();
                    //////END restart
                    $data = fread($content, $chunk_size);
                    if (strlen($data) == 0) {
                        break;
                    }
                    $block_count = count($job_object->steps_data[$job_object->current_step]['blockIDs']) + 1;
                    $blockID = str_pad($block_count, 6, "0", STR_PAD_LEFT);
                    $blobClient->createBlobBlock($this->container, $this->blobKey, base64_encode($blockID), $data);

                    $job_object->steps_data[$job_object->current_step]['blockIDs'][] = $blockID;
                    $job_object->steps_data[$job_object->current_step]['sub_step'] += strlen($data);
                }

                if (isset($content) && is_resource($content)) {
                    fclose($content);
                }

                $blocks = array();
                foreach ($job_object->steps_data[$job_object->current_step]['blockIDs'] as $block_id) {
                    $block = new Block();
                    $block->setBlockId(base64_encode($block_id));
                    $block->setType('Uncommitted');
                    array_push($blocks, $block);
                }

                $blobClient->commitBlobBlocks($this->container, $this->blobKey, $blocks);

                $job_object->buwd_logger->set_log(sprintf(__('Backup transferred to %s', 'buwd'), $this->container . '/' . $this->contfolder . $job_object->backup_file));
                $job_object->update_progress();

                $lastBlob = $blobClient->getBlob($this->container, $this->blobKey);

                $last_file = array();
                $UTC = new DateTimeZone("UTC");
                $last_file['file'] = $job_object->backup_file;
                $last_file['folder'] = $this->contfolder;
                $last_file['time'] = $lastBlob->getProperties()->getLastModified()->setTimezone($UTC)->getTimestamp();
                $last_file['size'] = $lastBlob->getProperties()->getContentLength();
                $last_file['container'] = $this->container;
                $last_file['acc_name'] = $this->acc_name;
                $last_file['access_key'] = $job_object->job['azurekey'];
                $last_file['jid'] = $job_object->job_id;
                $last_file['dest'] = 'azure';
                $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                $dest_files = get_site_option('buwd-dest-azure-' . $job_object->job_id, array());
                $dest_files[] = $last_file;

                update_site_option('buwd-dest-azure-' . $job_object->job_id, $dest_files);

            } else {
                $job_object->buwd_logger->set_log(__('Could not open source file for transfer.', 'buwd'), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }
        } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
            if (isset($content) && is_resource($content)) {
                fclose($content);
            }

            $job_object->buwd_logger->set_log(sprintf(__('Microsoft Azure API: %s', 'buwd'), $e->getMessage()), $e->getFile(), $e->getLine(), E_USER_ERROR);
            $job_object->update_progress();

            return false;
        } catch (Exception $e) {
            $job_object->buwd_logger->set_log(sprintf(__('Exception: %s', 'buwd'), $e->getMessage()), $e->getFile(), $e->getLine(), E_USER_ERROR);
            $job_object->update_progress();
        }

        $backup_files = $this->get_objects_container();
        $deleted = 0;
        $files_to_delete = array();
        if (!empty($job_object->job['azurefiledelete']) && $job_object->job['azurefiledelete'] > 0 && count($backup_files) > $job_object->job['azurefiledelete']) {
            while (count($backup_files) > $job_object->job['azurefiledelete']) {
                $file = array_shift($backup_files);
                if ($this->delete_object($file)) {
                    $deleted++;
                    $files_to_delete[] = basename($file);
                }
            }
        }

        if (!empty($files_to_delete)) {
            Buwd_Options::backup_bulk_delete('buwd-dest-azure-' . $job_object->job_id, $files_to_delete);
        }

        if ($deleted > 0) {
            $job_object->buwd_logger->set_log(sprintf(__('%d files were deleted from Microsoft Azure.', 'buwd'), $deleted));
            $job_object->update_progress();
        }

        return true;
    }

    public function run_sync(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to sync files to Microsoft Azure.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $this->acc_name = $job_object->job['azurename'];
        $this->access_key = Buwd_Encrypt::decrypt($job_object->job['azurekey']);
        $this->container = $job_object->job['azurecontainer'];
        $this->blobKey = $job_object->job['azurefolder'] . $job_object->backup_file;
        $this->contfolder = $job_object->job['azurefolder'];

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
            $job_object->steps_data[$job_object->current_step]['sub_step'] = 'azure_files';
            $job_object->steps_data[$job_object->current_step]['files_count'] = 0;
            $job_object->steps_data[$job_object->current_step]['folder_size'] = 0;
            $job_object->steps_data[$job_object->current_step]['nextMarker'] = '';
        }

        //chunk upload add in feuture
        try {
            $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());
            $containers = $blobClient->listContainers()->getContainers();
            $container_exist = false;
            foreach ($containers as $container) {
                if ($container->getName() == $this->container) {
                    $container_exist = true;
                    break;
                }
            }

            if ($container_exist) {
                $job_object->buwd_logger->set_log(sprintf(__('Connected to Microsoft Azure container "%s".', 'buwd'), $this->container));
            } else {
                $job_object->buwd_logger->set_log(sprintf(__('Microsoft Azure container "%s" does not exist.', 'buwd'), $this->container), E_USER_ERROR);

                return true;
            }

            //get files list
            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'azure_files') {
                $count = 0;
                do {
                    $blobListOptions = new \MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions();
                    $blobListOptions->setPrefix($this->contfolder);
                    $blobListOptions->setMarker($job_object->steps_data[$job_object->current_step]['nextMarker']);
                    $container_object_list = $blobClient->listBlobs($this->container, $blobListOptions);
                    $container_object = $container_object_list->getBlobs();
                    $job_object->steps_data[$job_object->current_step]['nextMarker'] = $container_object_list->getNextMarker();

                    if (is_array($container_object)) {
                        foreach ($container_object as $object) {
                            $count++;
                            $job_object->steps_data[$job_object->current_step]['files'][$object->getName()] = bin2hex(
                                base64_decode($object->getProperties()->getContentMD5())
                            );
                        }

                    }
                } while ($job_object->steps_data[$job_object->current_step]['nextMarker'] != '');

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
                                $file_dirname = $job_object->job['azurefolder'] . substr($f_file, strlen($job_object->job['abs_path']));
                            } else {
                                $file_dirname = $job_object->job['azurefolder'] . substr($f_file, strlen(dirname($job_object->job['abs_path']) . '/'));
                            }

                            if (!isset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname]) || (isset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname]) && $job_object->steps_data[$job_object->current_step]['files'][$file_dirname] != md5_file($f_file))) {
                                $content = fopen($f_file, "r");
                                $blobClient->createBlockBlob($this->container, $file_dirname, $content);
                                $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($f_file);

                                if (is_resource($content)) {
                                    fclose($content);
                                }
                                $job_object->buwd_logger->set_log(sprintf(__('%s file was uploaded to Microsoft Azure.', 'buwd'), $file_dirname));
                                $job_object->update_progress();
                            }

                            if (isset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname])) {
                                unset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname]);
                            }
                            $job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] = $file_key;
                        }
                    }
                    $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_extra_files';
                }
            }

            //sync extra files
            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_extra_files') {
                if ($job_object->extra_files) {
                    foreach ($job_object->extra_files as $extra_file) {
                        $job_object->check_if_stopped();
                        $job_object->restart_if_needed();
                        $extra_file_dirname = $job_object->job['azurefolder'] . basename($extra_file);
                        if (!isset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname]) || (isset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname]) && $job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname] != md5_file($extra_file))) {
                            $content = fopen($extra_file, "r");
                            $blobClient->createBlockBlob($this->container, $extra_file_dirname, $content);
                            $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($extra_file);
                            if (is_resource($content)) {
                                fclose($content);
                            }
                            $job_object->buwd_logger->set_log(sprintf(__('%s extra file was uploaded to Microsoft Azure.', 'buwd'), $extra_file_dirname));
                            $job_object->update_progress();
                        }

                        if (isset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname])) {
                            unset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_dirname]);
                        }
                    }
                }
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'delete_files';
            }

            $sync_file = get_site_option('buwd-dest-azure-sync-' . $job_object->job_id, array());
            $sync_file['file'] = 'Synchronized';
            $sync_file['folder'] = $this->contfolder;
            $sync_file['time'] = current_time('timestamp', true);
            $sync_file['size'] = $job_object->steps_data[$job_object->current_step]['folder_size'];
            $sync_file['jid'] = $job_object->job_id;
            $sync_file['dest'] = 'azure';
            $sync_file['logfile'] = basename($job_object->buwd_logger->logfile);
            $sync_file['sync'] = 1;

            update_site_option('buwd-dest-azure-sync-' . $job_object->job_id, $sync_file);
            //delete files
            if (!$job_object->job['azurefiledeletesync'] && $job_object->steps_data[$job_object->current_step]['sub_step'] == 'delete_files') {
                if (!empty($job_object->steps_data[$job_object->current_step]['files'])) {
                    $job_object->buwd_logger->set_log(__('Non-existent files will be deleted from Microsoft Azure.', 'buwd'));
                    $job_object->update_progress();
                    foreach (array_keys($job_object->steps_data[$job_object->current_step]['files']) as $azure_file) {
                        $job_object->restart_if_needed();

                        if (!$this->delete_object($azure_file)) {
                            $job_object->buwd_logger->set_log(sprintf(__('%s file was not deleted from Microsoft Azure.', 'buwd'), $azure_file), E_WARNING);
                        } else {
                            $job_object->buwd_logger->set_log(sprintf(__('%s file was deleted from Microsoft Azure.', 'buwd'), $azure_file));
                        }
                        $job_object->update_progress();
                        unset($job_object->steps_data[$job_object->current_step]['files'][$azure_file]);
                    }
                }
            }
        } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
            if (isset($content) && is_resource($content)) {
                fclose($content);
            }

            $job_object->buwd_logger->set_log(sprintf(__('Microsoft Azure API: %s', 'buwd'), $e->getMessage()), $e->getFile(), $e->getLine(), E_USER_ERROR);
            $job_object->update_progress();

            return false;
        }

        return true;
    }

    public function set_keys($job_id)
    {
        $this->acc_name = Buwd_Options::get($job_id, 'azurename');
        $this->access_key = Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 'azurekey')));
    }

    public function get_objects_container()
    {
        $container = $this->container;
        $prefix = $this->contfolder;
        $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());

        $blobListOptions = new \MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions();
        $blobListOptions->setPrefix($prefix);

        $objects_by_date = array();
        $container_object = $blobClient->listBlobs($container, $blobListOptions)->getBlobs();
        if (is_array($container_object)) {
            $UTC = new DateTimeZone("UTC");
            foreach ($container_object as $object) {
                $date_modified = strtotime($object->getProperties()->getLastModified()->setTimezone($UTC)->format(\DateTime::ISO8601));
                if ($prefix != '') {
                    $objects_by_date[$date_modified] = $object->getName();
                } else {
                    if (strpos($object->getName(), '/') === false) {
                        $objects_by_date[$date_modified] = $object->getName();
                    }
                }
            }
        }

        ksort($objects_by_date);

        return $objects_by_date;
    }

    public function get_config()
    {
        $acc_name = $this->acc_name;
        $access_key = $this->access_key;

        if (isset($_POST['buwd_ajax'])) {
            $acc_name = sanitize_text_field($_POST['acc_name']);
            $access_key = sanitize_text_field($_POST['access_key']);
        }

        return 'DefaultEndpointsProtocol=http;AccountName=' . $acc_name . ';AccountKey=' . $access_key;
    }

    public function get_container_list($job_id)
    {
        $containers_array = array();
        $type = 'select';

        try {
            $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());
            $containers = $blobClient->listContainers()->getContainers();

            foreach ($containers as $container) {
                $containers_array[esc_attr($container->getName())] = esc_attr($container->getName());
            }

            $value = esc_html(Buwd_Options::get($job_id, 'azurecontainer'));
        } catch (Exception $e) {
            $type = 'paragraph';
            $value = __('Access or Private keys are incorrect.', 'buwd');
            //			echo $e->getCode();
            //			echo "<pre>" . $e->getMessage() . "</pre>" . "\n";
        }

        return array('containers' => $containers_array, 'type' => $type, 'value' => $value);
    }

    private function delete_object($key)
    {
        try {
            $container = $this->container;
            $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());

            $blobClient->deleteBlob($container, $key);
        } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
            return false;
        }

        return true;
    }

    private function create_container($containerName)
    {
        $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());
        $containers = $blobClient->listContainers()->getContainers();

        foreach ($containers as $container) {
            if ($container->getName() == $containerName) {
                return false;
            }
        }

        try {
            $containerOptions = new CreateContainerOptions();
            $blobClient->createContainer($containerName, $containerOptions);
        } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
            set_site_transient('buwd_azure_error', $e->getMessage());

            return false;
        }

        return true;
    }

    public function run_ajax()
    {
        $html = $this->render_tab();
        echo $html;
        die;
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

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';
            if (in_array($field_name, array('azurename', 'azurekey', 'azurecontainer', 'azurenewcont', 'azurefolder'))) {
                $field_value = sanitize_text_field($field_value);
            }

            if ($field_name == 'azurekey') {
                $field_value = Buwd_Encrypt::encrypt($field_value);
            }

            if ($field_name == 'azurefolder') {
                $field_value = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim($field_value))));
                if ($field_value === '/') {
                    $field_value = '';
                } else {
                    if (substr($field_value, 0, 1) == '/') {
                        $field_value = substr($field_value, 1);
                    }
                }
            }

            if ($field_name == 'azurenewcont' && $field_value != '') {
                $this->set_keys($job_id);
                if ($this->create_container($field_value)) {
                    Buwd_Options::update_job_option($job_id, 'azurecontainer', $field_value);
                }
            }

            if ($field_name == 'azurefiledelete') {
                $field_value = (int)$field_value;
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }

    }

    public function is_valid($job_options)
    {
        if (empty($job_options['azurename']) || empty($job_options['azurekey'])) {
            return false;
        }

        $this->acc_name = $job_options['azurename'];
        $this->access_key = Buwd_Encrypt::decrypt($job_options['azurekey']);
        $this->container = $job_options['azurecontainer'];
        try {
            $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());

            $containers = $blobClient->listContainers()->getContainers();

            $container_exist = false;
            foreach ($containers as $container) {
                if ($container->getName() == $this->container) {
                    $container_exist = true;
                    break;
                }
            }

            if (!$container_exist) {
                $this->errors[] = sprintf(__('MS Azure container "%s" does not exist!', 'buwd'), $this->container);

                return false;
            }

        } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
            return false;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return false;
        }


        return true;
    }

    public function delete_file($backup)
    {
        $this->container = $backup['container'];
        $this->acc_name = $backup['acc_name'];
        $this->access_key = Buwd_Encrypt::decrypt($backup['access_key']);

        //delete from folder
        try {
            if (!$this->delete_object($backup['folder'] . $backup['file'])) {
                return false;
            }
        } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
            //echo $e->getMessage();
            return false;
        }

        return true;
    }

    public function download_file($backup)
    {
        $this->container = $backup['container'];
        $this->acc_name = $backup['acc_name'];
        $this->access_key = Buwd_Encrypt::decrypt($backup['access_key']);

        //delete from folder
        try {
            $blobClient = ServicesBuilder::getInstance()->createBlobService($this->get_config());
            $blob = $blobClient->getBlob($this->container, $backup['folder'] . $backup['file']);


            @set_time_limit(3000);
            nocache_headers();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: ' . Buwd_File::mime_content_type($backup['file']));
            header('Content-Disposition: attachment; filename="' . basename($backup['file']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . $blob->getProperties()->getContentLength());
            fpassthru($blob->getContentStream());
            die();

        } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
            set_site_transient('buwd_backups_error', $e->getMessage());
            Buwd_Helper::redirect(array('page' => 'buwd_backups'));
        }

    }

    public function get_errors()
    {
        return $this->errors;
    }

    /**
     *
     */
    public static function display_messages()
    {
        if ($error = get_site_transient('buwd_azure_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_azure_error');
        } else if ($updated = get_site_transient('buwd_azure_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_azure_updated');
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
