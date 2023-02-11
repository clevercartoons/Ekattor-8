<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\User;
use App\Models\School;
use App\Models\Package;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Models\GlobalSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;

class InstallController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public  function index()
    {
        if(DB::connection()->getDatabaseName() != 'db_name')
        {
            // echo get_settings('frontend_view');
            if(get_settings('frontend_view') == '1') {
                $packages = Package::all();
                $faqs = Faq::all();
                $users = User::all();
                $schools = School::all();
                return view('frontend.landing_page', ['packages' => $packages, 'faqs' => $faqs, 'users' => $users,'schools' => $schools]);
            } else {
                return redirect(route('login'));
            }
        } else {
            return to_route('step0');
        }
    }

    public function step0() {
        return view('install.step0');
    }

    public function step1() {
        return view('install.step1');
    }

    public function step3(Request $request) {
        $db_connection = "";
        $data = $request->all();

        $this->check_purchase_code_verification();

        if ($data) {

            $hostname = $data['hostname'];
            $username = $data['username'];
            $password = $data['password'];
            $dbname   = $data['dbname'];
            // check db connection using the above credentials
            $db_connection = $this->check_database_connection($hostname, $username, $password, $dbname);
            if ($db_connection == 'success') {
                // proceed to step 4
                // session_start();
                $_SESSION['hostname'] = $hostname;
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $password;
                $_SESSION['dbname']   = $dbname;
                return to_route('step4');
            } else {

                return view('install.step3', ['db_connection' => $db_connection]);
            }
        }

        return view('install.step3', ['db_connection' => $db_connection]);
    }

    public function check_purchase_code_verification() {
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
            //return 'running_locally';
        } else {
            session_start();

        }
    }

    public function check_database_connection($hostname, $username, $password, $dbname) {

        $newName = uniqid('db'); //example of unique name

        Config::set("database.connections.".$newName, [
            "host"      => $hostname,
            "port"      => env('DB_PORT', '3306'),
            "database"  => $dbname,
            "username"  => $username,
            "password"  => $password,
            'driver'    => env('DB_CONNECTION', 'mysql'),
            'charset'   => env('DB_CHARSET', 'utf8mb4'),
        ]);
        try {
            DB::connection($newName)->getPdo();
            return 'success';
        } catch (\Exception $e) {
            return 'Could not connect to the database.  Please check your configuration.';
        }
    }

    public function step4(Request $request) {

        return view('install.step4');
    }


    public function confirmImport($param1='')
    {
        if ($param1 = 'confirm_import') {
            // write database.php
            $this->configure_database();

            // redirect to admin creation page
            return view('install.install');
        }
    }

    public function confirmInstall()
    {
        // run sql
        $this->run_blank_sql();

        // redirect to admin creation page
        return to_route('finalizing_setup');
    }

    public function configure_database() {
        // write database.php
        $data_db = file_get_contents('./config/database.php');
        session_start();
        $data_db = str_replace('db_name',    $_SESSION['dbname'],    $data_db);
        $data_db = str_replace('db_user',    $_SESSION['username'],    $data_db);
        $data_db = str_replace('db_pass',    $_SESSION['password'],    $data_db);
        $data_db = str_replace('db_host',    $_SESSION['hostname'],    $data_db);
        file_put_contents('./config/database.php', $data_db);

        // $route_path = file_get_contents('./routes/web.php');
        // $install_ended = str_replace("Route::get('/', 'index');",    "Route::get('/install_ended', 'index');",    $route_path);
        // file_put_contents('./routes/web.php', $install_ended);

    }

    public function run_blank_sql() {

        // Set line to collect lines that wrap
        $templine = '';
        // Read in entire file
        $lines = file('./public/assets/install.sql');
        // Loop through each line
        foreach ($lines as $line) {
        // Skip it if it's a comment
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;
                // Add this line to the current templine we are creating
                $templine .= $line;
            // If it has a semicolon at the end, it's the end of the query so can process this templine
            if (substr(trim($line), -1, 1) == ';') {
                // Perform the query
                DB::statement($templine);

                // Reset temp variable to empty
                $templine = '';
            }
        }
    }

    public function finalizingSetup(Request $request) {

        $data = $request->all();
        if ($data) {

            /*session data*/
            $session_data['session_title']        = $data['current_session'];
            $session_data['status']      = 1;

            $session = Session::create([
                'session_title' => $session_data['session_title'],
                'status' => $session_data['status']
            ]);

            /*system data*/
            $system_data['system_name']  = $data['system_name'];
            $system_data['timezone']  = $data['timezone'];
            session_start();
            if (isset($_SESSION['purchase_code'])) {
                $system_data['purchase_code']  = $_SESSION['purchase_code'];
            }
            session_destroy();
            $system_data['running_session'] = $session->id;

            foreach($system_data as $key => $global_data){
                GlobalSettings::where('key', $key)->update([
                    'value' => $global_data,
                ]);
            }

            /*superadmin data*/
            $superadmin_data['name']      = $data['superadmin_name'];
            $superadmin_data['email']     = $data['superadmin_email'];
            $superadmin_data['password']  = $data['superadmin_password'];
            $superadmin_data['role_id']      = '1';

            $info = array(
                'gender' => "Male",
                'blood_group' => "a+",
                'birthday' => time(),
                'phone' => $data['superadmin_phone'],
                'address' => $data['superadmin_address'],
                'photo' => "user.png"
            );

            $superadmin_data['user_information'] = json_encode($info);

            User::create([
                'name' => $superadmin_data['name'],
                'email' => $superadmin_data['email'],
                'password' => Hash::make($superadmin_data['password']),
                'role_id' => $superadmin_data['role_id'],
                'user_information' => $superadmin_data['user_information'],
            ]);

            return to_route('success');

            return view('install.success', ['admin_email' => $superadmin_data['email']]);
        }

        return view('install.finalizing_setup');
    }

    public function success($param1 = '') {
        if ($param1 == 'login') {
            return view('auth.login');
        }

        $superadmin_email = User::find('1')->email;

        $page_data['admin_email'] = $superadmin_email;
        $page_data['page_name'] = 'success';
        return view('install.success', ['admin_email' => $superadmin_email]);
    }

    public function configure_routes() {
        // write routes.php
        $data_routes = file_get_contents('./config/routes.php');
        $data_routes = str_replace('install',    'home',    $data_routes);
        file_put_contents('./application/config/routes.php', $data_routes);
    }
}
