<?php
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_reference.php';
require_once 'function/f_user.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_reference = new Class_reference();
$fn_user = new Class_user();
$api_name = 'api_local_data';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

try {   
    Class_db::getInstance()->db_connect();
    //$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    
    if ('GET' === $request_method) { 
        if (!isset($headers['Name']) || empty($headers['Name'])) {
            throw new Exception('[' . __LINE__ . '] - Parameter Name empty');
        }
        $name = $headers['Name'];    
            
        $result = array();
        switch ($name) {
            case 'cae_status':
                $result = $fn_reference->get_status();
                break;
            case 'cae_state':
                $result = $fn_reference->get_state();
                break;
            case 'cae_city':
                $result = $fn_reference->get_city();
                break;
            case 'cae_role':
                $result = $fn_reference->get_role();
                break;
            case 'cae_user':
                $result = $fn_user->get_users();
                break;
            case 'cae_designation':
                $result = $fn_reference->get_designation();
                break;
            case 'cae_group':
                $result = $fn_reference->get_group_list();
                break;
                break;
            default:
                throw new Exception('[' . __LINE__ . '] - Parameter name invalid ('.$name.')');
        }
                
        $form_data['result'] = $result;
        $form_data['success'] = true; 
        //$fn_general->log_debug('API', $api_name, __LINE__, 'Result = '.print_r($result, true));
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
    }
    Class_db::getInstance()->db_close();
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    } else {
        $form_data['errmsg'] = $constant::ERR_DEFAULT;
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}

echo json_encode($form_data);
