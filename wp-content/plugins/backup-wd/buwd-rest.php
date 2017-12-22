<?php

/**
 * Created by PhpStorm.
 * User: Araqel
 * Date: 08/04/2017
 * Time: 1:38 PM
 */


//require_once IOWD_DIR_INCLUDES . "/iowd-optimize.php";

class BUWD_Rest extends WP_REST_Controller
{
    private $version = '1';
    private $route = 'buwd';

    public function register_routes()
    {
        $namespace = $this->route . '/v' . $this->version;

        register_rest_route($namespace, '/job/(?P<id>\d+)/run', array(
            array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'run_job'),
                'args'     => array(
                    'type'      => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                    'buwd_hash'     => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                ),
            )
        ));

    }

    public function run_job(WP_REST_Request $request)
    {
        //clearstatcache();
        /*@ini_set('max_execution_time',1300);
        $bucket = $request->get_param('bucket');
        $images_data = $request->get_param('images_data');
        $post_id = $request->get_param('post_id');
        $iteration = $request->get_param('iteration');
        $credentials = get_option("iowd_crd_" . $post_id);*/

        $job_id=$request->get_param('id');
        $type=$request->get_param('type');
        $nonce=$request->get_param('buwd_hash');
        $hash = Buwd_Options::getSetting('job_start_key');
        if ($nonce != md5($hash)) {
            delete_site_option('buwd_job_running');
            return new WP_REST_Response(array('status' => 'error', 'message' => 'Not Allowed'), 401);
        }

        Buwd_Job::setup($type, $job_id);

        return new WP_REST_Response(array('status' => 'ok', 'message' => 'success'), 200);
    }



}
