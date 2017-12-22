<?php

/**
 *
 */
use OpenCloud\Rackspace;

require_once BUWD_DIR . '/vendor/autoload.php';

class Buwd_Destination_Rsc
{
    protected static $instance = null;
    private $username = '';
    private $api_key = '';
    private $region = '';
    private $container = '';
    private $file_key = '';
    private $contfolder = '';
    public $errors = array();

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to Rackspace Cloud', 'buwd');
        $this->info['sync_title'] = __('Syncing files to Rackspace Cloud', 'buwd');
        $this->info['name'] = __('RackSpace Cloud', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = true;
    }

    public function defaults()
    {
        $defaults = array(
            'rscuser'           => '',
            'rsckey'            => '',
            'rscregion'         => 'DFW',
            'rsccontainer'      => '',
            'rscnewcont'        => '',
            'rscfolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            'rscfiledelete'     => 15,
            'rscfiledeletesync' => '1'
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        $this->set_keys($job_id);
        $containers = $this->get_container_list($job_id);

        $container_selection_class = $containers['type'] == 'paragraph' ? 'buwd-hide' : '';
        $regions = array(
            "DFW" => "Dallas (DFW)",
            "ORD" => "Chicago (ORD)",
            "SYD" => "Sydney (SYD)",
            "LON" => "London (LON)",
            "IAD" => "Northern Virginia (IAD)",
            "HKG" => "Hong Kong (HKG)",
        );

        $options = array(
            'key'    => 'destination-azure',
            'title'  => '',
            'fields' => array(
                array(
                    'label'  => __('Username', 'buwd'),
                    'header' => __('RackSpace Cloud Keys', 'buwd'),
                    'id'     => 'rscuser',
                    'name'   => 'rscuser',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'rscuser')),
                    'hint'   => array(
                        'html' => '<p class="description">Provide the username of your RackSpace Cloud account.</p>',
                    ),
                ),
                array(
                    'label' => __('API key', 'buwd'),
                    'id'    => 'rsckey',
                    'name'  => 'rsckey',
                    'type'  => 'password',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 'rsckey'))),
                    'hint'  => array(
                        'html' => '<p class="description">Provide the API key associated with your RackSpace Cloud account.</p>',
                    ),
                ),
                array(
                    'label'   => __('Rackspace Cloud Files Region', 'buwd'),
                    'header'  => __('Select region', 'buwd'),
                    'id'      => 'rscregion',
                    'name'    => 'rscregion',
                    'type'    => 'select',
                    'class'   => array(
                        'buwd-large-text',
                    ),
                    'choices' => $regions,
                    'value'   => esc_html(Buwd_Options::get($job_id, 'rscregion')),
                    'hint'    => array(
                        'html' => '<p class="description">Select the region of your RackSpace Cloud account for the backup files.</p>',
                    ),
                ),
                array(
                    'label'   => __('Select Container', 'buwd'),
                    'id'      => 'rsccontainer',
                    'name'    => 'rsccontainer',
                    'type'    => $containers['type'],
                    'choices' => $containers['containers'],
                    'class'   => array(
                        'buwd-large-text buwd-error',
                    ),
                    'value'   => $containers['value'],
                    'hint'    => array(
                        'html' => '<p class="description ' . $container_selection_class . '">Choose the container where the backup files will be uploaded.</p>',
                    ),
                ),
                array(
                    'label' => __('Create a new container', 'buwd'),
                    'id'    => 'rscnewcont',
                    'name'  => 'rscnewcont',
                    'type'  => 'text',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => '',
                    'hint'  => array(
                        'html' => '<p class="description">Alternatively, you can create a new container by providing its title in this input box. The container will be added when you save tab options.</p>',
                    ),
                ),
                array(
                    'label'  => __('Folder in container', 'buwd'),
                    'header' => __('Backup settings', 'buwd'),
                    'id'     => 'rscfolder',
                    'name'   => 'rscfolder',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'rscfolder')),
                    'hint'   => array(
                        'html' => '<p class="description">Specify the folder in selected container where the backup files will be uploaded.</p>',
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'rscfiledelete',
                    'name'       => 'rscfiledelete',
                    'type'       => 'number', // to number
                    'class'      => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'       => array(
                        'min' => "0"
                    ),
                    'value'      => esc_html(Buwd_Options::get($job_id, 'rscfiledelete')),
                    'hint'       => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in RackSpace Cloud folder for backup. When the limit is reached, the oldest backup file will be deleted.</p>',
                    ),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') == 'archive' ? true : false,
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'rscfiledeletesync',
                    'name'       => 'rscfiledeletesync',
                    'type'       => 'checkbox', // to number
                    'class'      => array(),
                    'choices'    => array(
                        '1' => 'Keep deleted files from previous backup sync.'
                    ),
                    'value'      => Buwd_Options::get($job_id, 'rscfiledeletesync'),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') != 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<p class="description">Uncheck the option to remove the deleted files.</p><span class="buwd-error">Please note that if you uncheck this option, you will loose all previous files in backup storage folder</span>'
                    ),
                ),
            ),
        );

        return $options;
    }

    public function set_keys($job_id)
    {
        $this->username = Buwd_Options::get($job_id, 'rscuser');
        $this->api_key = Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 'rsckey'), ''));

        if (isset($_POST['buwd_ajax'])) {
            $this->region = sanitize_text_field($_POST['rsc_region']);
        } else {
            $this->region = Buwd_Options::get($job_id, 'rscregion', 'DFW');
        }

    }

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to Rackspace cloud.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $this->username = $job_object->job['rscuser'];
        $this->api_key = Buwd_Encrypt::decrypt($job_object->job['rsckey']);
        $this->region = $job_object->job['rscregion'];
        $this->container = $job_object->job['rsccontainer'];
        $this->file_key = $job_object->job['rscfolder'] . $job_object->backup_file;
        $this->contfolder = $job_object->job['rscfolder'];

        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
            $objectStoreService = $client->objectStoreService(null, $this->region);
            $container = $objectStoreService->getContainer($this->container);

            $job_object->buwd_logger->set_log(sprintf(__('Connected to Rackspace Cloud %s container.', 'buwd'), $this->container));
            $job_object->buwd_logger->set_log(__('Upload to Rackspace Cloud has started.', 'buwd'));
            $job_object->update_progress();

            if ($content = fopen($job_object->backup_folder . $job_object->backup_file, 'r')) {
                $job_object->check_if_stopped();
                $job_object->restart_if_needed();
                $uploded_obj = $container->uploadObject($this->file_key, $content);
                if (isset($content) && is_resource($content)) {
                    fclose($content);
                }

                if ($uploded_obj) {
                    $job_object->buwd_logger->set_log(sprintf(__('Backup file was transferred to %s', 'buwd'), $this->container . '/' . $this->contfolder . $job_object->backup_file));
                    $job_object->update_progress();

                    $lastUploded = $container->getObject($this->file_key);

                    // download url
                    $last_file = array();
                    $UTC = new DateTimeZone("UTC");
                    $lm = new DateTime($lastUploded->getLastModified());
                    $last_file['file'] = $job_object->backup_file;
                    $last_file['folder'] = $this->contfolder;
                    $last_file['time'] = $lm->setTimezone($UTC)->getTimestamp();
                    $last_file['size'] = $lastUploded->getContentLength();
                    $last_file['region'] = $this->region;
                    $last_file['container'] = 'RSC://' . $this->container;
                    $last_file['username'] = $this->username;
                    $last_file['api_key'] = $job_object->job['rsckey'];
                    $last_file['jid'] = $job_object->job_id;
                    $last_file['dest'] = 'rsc';
                    $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                    $dest_files = get_site_option('buwd-dest-rsc-' . $job_object->job_id, array());
                    $dest_files[] = $last_file;

                    update_site_option('buwd-dest-rsc-' . $job_object->job_id, $dest_files);

                } else {
                    $job_object->buwd_logger->set_log(__('Could not transfer backup to Rackspace Cloud.', 'buwd'), E_USER_ERROR);
                    $job_object->update_progress();

                    return false;
                }

            } else {
                $job_object->buwd_logger->set_log(__('Could not open source file for transfer.', 'buwd'), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }

        } catch (Guzzle\Http\Exception\ClientErrorResponseException  $e) {
            $job_object->buwd_logger->set_log(E_USER_ERROR, sprintf(__('Rackspace Cloud API: %s', 'buwd'), $e->getMessage()), $e->getFile(), $e->getLine());
            $job_object->update_progress();

            return false;
        }

        $backup_files = $this->get_objects_container();

        $deleted = 0;
        $files_to_delete = array();
        if (!empty($job_object->job['rscfiledelete']) && $job_object->job['rscfiledelete'] > 0 && count($backup_files) > $job_object->job['rscfiledelete']) {

            while (count($backup_files) > $job_object->job['rscfiledelete']) {
                $file = array_shift($backup_files);
                if ($this->delete_object($file)) {
                    $deleted++;
                    $files_to_delete[] = basename($file);
                }
            }
        }

        if (!empty($files_to_delete)) {
            Buwd_Options::backup_bulk_delete('buwd-dest-rsc-' . $job_object->job_id, $files_to_delete);
        }

        if ($deleted > 0) {
            $job_object->buwd_logger->set_log(sprintf(__('%d files were deleted from Rackspace Cloud.', 'buwd'), $deleted));
            $job_object->update_progress();
        }

        return true;
    }

    public function run_sync(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to sync files to Rackspace Cloud.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $this->username = $job_object->job['rscuser'];
        $this->api_key = Buwd_Encrypt::decrypt($job_object->job['rsckey']);
        $this->region = $job_object->job['rscregion'];
        $this->container = $job_object->job['rsccontainer'];
        $this->file_key = $job_object->job['rscfolder'] . $job_object->backup_file;
        $this->contfolder = $job_object->job['rscfolder'];

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
            $job_object->steps_data[$job_object->current_step]['sub_step'] = 'rsc_files';
            $job_object->steps_data[$job_object->current_step]['files_count'] = 0;
            $job_object->steps_data[$job_object->current_step]['folder_size'] = 0;
        }

        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
            $objectStoreService = $client->objectStoreService(null, $this->region);
            $container = $objectStoreService->getContainer($this->container);


            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'rsc_files') {
                $job_object->check_if_stopped();
                $job_object->restart_if_needed();

                $job_object->buwd_logger->set_log(sprintf(__('Connected to Rackspace Cloud %s container.', 'buwd'), $this->container));
                $job_object->update_progress();

                $total = (int)$container->getObjectCount() - 1;
                $job_object->buwd_logger->set_log($container->getObjectCount());
                $marker = '';
                $count = 0;
                while ($marker !== null) {
                    $params = array(
                        'marker' => $marker,
                        'prefix' => $this->contfolder,
                    );

                    $objects = $container->objectList($params);

                    if ($total <= 10000) {
                        $total = count($objects);
                    }

                    if ($total == 0) {
                        break;
                    }
                    foreach ($objects as $object) {
                        $job_object->steps_data[$job_object->current_step]['files'][$object->getName()] = $object->getETag();
                        $count++;

                        $marker = ($count != $total) ? $object->getName() : null;
                    }
                }

                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_files';
            }

            //sync files
            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_files') {
                if (isset($files_folders['folders'])) {
                    foreach ($files_folders['folders'] as $folder_key => $folder) {
                        $folder_files = isset($files_folders['files'][($folder)]) ? $files_folders['files'][($folder)] : array();
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
                                $file_dirname = $job_object->job['rscfolder'] . substr($f_file, strlen($job_object->job['abs_path']));
                            } else {
                                $file_dirname = $job_object->job['rscfolder'] . substr($f_file, strlen(dirname($job_object->job['abs_path']) . '/'));
                            }

                            if (!isset($job_object->steps_data[$job_object->current_step]['files'][($file_dirname)]) || (isset($job_object->steps_data[$job_object->current_step]['files'][($file_dirname)]) && $job_object->steps_data[$job_object->current_step]['files'][($file_dirname)] != md5_file($f_file))) {
                                $content = fopen($f_file, 'r');
                                $container->uploadObject(urlencode($file_dirname), $content);
                                $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($f_file);

                                if (isset($content) && is_resource($content)) {
                                    fclose($content);
                                }
                                $job_object->buwd_logger->set_log(sprintf(__('%s file was uploaded to Rackspace Cloud.', 'buwd'), $file_dirname));
                                $job_object->update_progress();
                            }

                            if (isset($job_object->steps_data[$job_object->current_step]['files'][($file_dirname)])) {
                                unset($job_object->steps_data[$job_object->current_step]['files'][($file_dirname)]);
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
                        $extra_file_dirname = $job_object->job['rscfolder'] . basename($extra_file);
                        if (!isset($job_object->steps_data[$job_object->current_step]['files'][($extra_file_dirname)]) || (isset($job_object->steps_data[$job_object->current_step]['files'][($extra_file_dirname)]) && $job_object->steps_data[$job_object->current_step]['files'][($extra_file_dirname)] != md5_file($extra_file))) {
                            $content = fopen($extra_file, 'r');
                            $container->uploadObject(urlencode($extra_file_dirname), $content);
                            $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($extra_file);

                            if (isset($content) && is_resource($content)) {
                                fclose($content);
                            }
                            $job_object->buwd_logger->set_log(sprintf(__('%s extra file was uploaded to Rackspace Cloud.', 'buwd'), $extra_file_dirname));
                            $job_object->update_progress();
                        }

                        if (isset($job_object->steps_data[$job_object->current_step]['files'][($extra_file_dirname)])) {
                            unset($job_object->steps_data[$job_object->current_step]['files'][($extra_file_dirname)]);
                        }
                    }
                }
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'delete_files';
            }

            $sync_file = get_site_option('buwd-dest-rsc-sync-' . $job_object->job_id, array());
            $sync_file['file'] = 'Synchronized';
            $sync_file['folder'] = $this->contfolder;
            $sync_file['time'] = current_time('timestamp', true);
            $sync_file['size'] = $job_object->steps_data[$job_object->current_step]['folder_size'];
            $sync_file['jid'] = $job_object->job_id;
            $sync_file['dest'] = 'rsc';
            $sync_file['logfile'] = basename($job_object->buwd_logger->logfile);
            $sync_file['sync'] = 1;

            update_site_option('buwd-dest-rsc-sync-' . $job_object->job_id, $sync_file);

            //delete files
            if (!$job_object->job['rscfiledeletesync'] && $job_object->steps_data[$job_object->current_step]['sub_step'] == 'delete_files') {
                if (!empty($job_object->steps_data[$job_object->current_step]['files'])) {
                    $job_object->buwd_logger->set_log(__('Non-existent files were deleted from Rackspace Cloud.', 'buwd'));
                    $job_object->update_progress();
                    foreach (array_keys($job_object->steps_data[$job_object->current_step]['files']) as $rsc_file) {
                        $job_object->check_if_stopped();
                        $job_object->restart_if_needed();

                        $this->delete_object(urlencode($rsc_file));
                        $job_object->buwd_logger->set_log(sprintf(__('%s file was deleted from Rackspace Cloud.', 'buwd'), ($rsc_file)));
                        $job_object->update_progress();
                        unset($job_object->steps_data[$job_object->current_step]['files'][$rsc_file]);
                    }
                }
            }
        } catch (Guzzle\Http\Exception\ClientErrorResponseException  $e) {
            $job_object->buwd_logger->set_log(sprintf(__('Rackspace Cloud API: %s', 'buwd'), $e->getMessage()), E_USER_ERROR, $e->getFile(), $e->getLine());
            $job_object->update_progress();

            return false;
        }

        return true;
    }

    public function get_objects_container()
    {
        $container = $this->container;
        $prefix = $this->contfolder;
        $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
        $objectStoreService = $client->objectStoreService(null, $this->region);
        $container_object = $objectStoreService->getContainer($container);
        $objects = $container_object->objectList(array('prefix' => $prefix));

        $objects_by_date = array();
        $UTC = new DateTimeZone("UTC");
        foreach ($objects as $object) {
            $date_modified = new DateTime($object->getLastModified());
            $date_modified = $date_modified->setTimezone($UTC)->getTimestamp();
            if ($prefix != '') {
                if ($object->getContentType() != 'application/directory') {
                    $objects_by_date[$date_modified] = $object->getName();
                }
            } else {
                if (strpos($object->getName(), '/') === false && $object->getContentType() != 'application/directory') {
                    $objects_by_date[$date_modified] = $object->getName();
                }
            }
        }

        ksort($objects_by_date);

        return $objects_by_date;
    }

    public function run_ajax()
    {
        $html = $this->render_tab();
        echo $html;
        die;
    }

    public function get_errors()
    {
        return $this->errors;
    }

    public function get_container_list($job_id)
    {
        $containers_array = array();
        $type = 'select';
        $not_available_cont = false;
        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
            $objectStoreService = $client->objectStoreService(null, $this->region);
            $containers = $objectStoreService->listContainers();
            foreach ($containers as $container) {
                $containers_array[$container->getName()] = esc_html($container->getName());
            }
            $value = esc_html(Buwd_Options::get($job_id, 'rsccontainer'));
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $type = 'paragraph';
            $value = __('Access or Private keys are incorrect.', 'buwd');
            $not_available_cont = true;

        } catch (OpenCloud\Common\Exceptions\CredentialError $e) {
            $type = 'paragraph';
            $value = __('Access or Private keys are incorrect.', 'buwd');
            $not_available_cont = true;

        } catch (OpenCloud\Common\Exceptions\EndpointError $e) {
            $type = 'paragraph';
            $value = sprintf(__('Files are currently unavailable %s region.', 'buwd'), $this->region);

            $not_available_cont = true;
        }

        if (empty($containers_array) && !$not_available_cont) {
            $type = 'paragraph';
            $value = sprintf(__('You do not have any containers %s region', 'buwd'), $this->region);

        }

        return array('containers' => $containers_array, 'type' => $type, 'value' => $value);
    }

    public function get_config()
    {
        $username = $this->username;
        $api_key = $this->api_key;

        if (isset($_POST['buwd_ajax'])) {
            $username = sanitize_text_field($_POST['username']);
            $api_key = sanitize_text_field($_POST['api_key']);
        }

        return array(
            'username' => $username,
            'apiKey'   => $api_key,
        );
    }

    public function is_valid($job_options)
    {
        if (empty($job_options['rscuser']) || empty($job_options['rsckey'])) {
            return false;
        }

        $this->username = $job_options['rscuser'];
        $this->api_key = Buwd_Encrypt::decrypt($job_options['rsckey']);
        $this->region = $job_options['rscregion'];
        $this->container = $job_options['rsccontainer'];

        if (empty($this->container)) {
            $regions = array(
                "DFW" => "Dallas (DFW)",
                "ORD" => "Chicago (ORD)",
                "SYD" => "Sydney (SYD)",
                "LON" => "London (LON)",
                "IAD" => "Northern Virginia (IAD)",
                "HKG" => "Hong Kong (HKG)",
            );

            $this->errors[] = sprintf(__('You do not have any containers %s region', 'buwd'), $regions[$this->region]);

            return false;
        }

        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
            $objectStoreService = $client->objectStoreService(null, $this->region);
            $containers = $objectStoreService->listContainers();

            $container_exist = false;
            foreach ($containers as $container) {
                if ($container->getName() == $this->container) {
                    $container_exist = true;
                    break;
                }
            }

            if (!$container_exist) {
                $this->errors[] = sprintf(__('RackSpace Cloud container "%s" does not exist!', 'buwd'), $this->container);

                return false;
            }

        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {

            return false;
        } catch (OpenCloud\Common\Exceptions\CredentialError $e) {

            return false;
        } catch (OpenCloud\Common\Exceptions\EndpointError $e) {

            return false;
        }

        return true;
    }

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';
            if (in_array($field_name, array('rscuser', 'rsckey', 'rscregion', 'rsccontainer', 'rscnewcont', 'rscfolder'))) {
                $field_value = sanitize_text_field($field_value);
            }

            if ($field_name == 'rsckey') {
                $field_value = Buwd_Encrypt::encrypt($field_value);
            }

            if ($field_name == 'rscfolder') {
                $field_value = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim($field_value))));
                if ($field_value === '/') {
                    $field_value = '';
                } else {
                    if (substr($field_value, 0, 1) == '/') {
                        $field_value = substr($field_value, 1);
                    }
                }
            }

            if ($field_name == 'rscnewcont' && $field_value != '') {
                $this->set_keys($job_id);
                if ($this->create_container($field_value)) {
                    Buwd_Options::update_job_option($job_id, 'rsccontainer', $field_value);
                }
            }

            if ($field_name == 'rscfiledelete') {
                $field_value = (int)$field_value;
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }
    }

    public function delete_file($backup)
    {
        $this->region = $backup['region'];
        $this->container = str_replace('RSC://', '', $backup['container']);
        $this->username = $backup['username'];
        $this->api_key = Buwd_Encrypt::decrypt($backup['api_key']);

        try {
            if (!$this->delete_object($backup['folder'] . $backup['file'])) {

                return false;
            }
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
            return false;
        }

        return true;
    }

    public function download_file($backup)
    {
        $this->region = $backup['region'];
        $this->container = str_replace('RSC://', '', $backup['container']);
        $this->username = $backup['username'];
        $this->api_key = Buwd_Encrypt::decrypt($backup['api_key']);

        try {
            if (false === $result = $this->get_object($backup['folder'] . $backup['file'])) {
                set_site_transient('buwd_backups_error', __('File not found.', 'buwd'));
                Buwd_Helper::redirect(array('page' => 'buwd_backups'));
            }
            set_time_limit(3000);
            nocache_headers();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: ' . Buwd_File::mime_content_type($backup['file']));
            header('Content-Disposition: attachment; filename="' . basename($backup['file']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . $result->getContentLength());
            echo $result->getContent();
            die();

        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
            set_site_transient('buwd_backups_error', $e->getMessage());
            Buwd_Helper::redirect(array('page' => 'buwd_backups'));
        }

    }

    private function delete_object($key)
    {
        $container = $this->container;
        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
            $objectStoreService = $client->objectStoreService(null, $this->region);
            $container_object = $objectStoreService->getContainer($container);
            $container_object->deleteObject($key);
        } catch (ServiceException $e) {
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

    private function create_container($containerName)
    {
        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
            $objectStoreService = $client->objectStoreService(null, $this->region);
            $objectStoreService->createContainer($containerName);
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
            return false;
        }

        return true;
    }

    private function get_object($key)
    {
        $container = $this->container;
        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $this->get_config());
            $objectStoreService = $client->objectStoreService(null, $this->region);
            $container_object = $objectStoreService->getContainer($container);
            if (!$container_object->objectExists($key)) {
                return false;
            }
            $result = $container_object->getObject($key);

        } catch (ServiceException $e) {
            return false;
        }

        return $result;
    }

    public static function display_messages()
    {
        if ($error = get_site_transient('buwd_rsc_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_rsc_error');
        } else if ($updated = get_site_transient('buwd_rsc_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_rsc_updated');
        }
    }

    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}
