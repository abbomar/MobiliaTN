<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\API\ResponseTrait;
use function PHPUnit\Framework\throwException;


/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    use ResponseTrait;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $db = \Config\Database::connect();


        $db->query("SET lc_time_names = 'fr_FR';");
        // E.g.: $this->session = \Config\Services::session();
    }

    public function readParamsAndValidate($params) {

        if ( ! $this->validate($params) ) {
            return null;
        };

        $data = array();
        foreach ($params as $key => $value)
        {
            $data[$key] = $this->request->getVar($key);
        }

        return $data;
    }

    public function responseError($messages) {
        $body = [
            "error" => "1",
            "messages" => $messages,
            "data" => null,
        ];
        return $this->respond($body);
    }

    public function responseSuccess($data, $message = "Success") {

        $body = [
            "error" => "0",
            "messages" => [ $message ],
            "data" => $data,
        ];

        return $this->respond($body);
    }
}
