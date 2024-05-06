<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class JubileeController extends Controller
{
    public static function apiRoutes()
    {
      Route::get('pixel/rise', [JubileeController::class, 'index']);
      Route::get('pixel/rise/postback', [JubileeController::class, 'postback']);
    }
    
    public function index(Request $request)
    {
      $amount = $request->input('amount', 30);

      return view('rise/index')->with('amount', $amount);
    }

    public function postback(Request $request)
    {
      /*$campaignID = htmlentities($_GET['x1']); 
      $adGroupID =  htmlentities($_GET['x2']);
      $creative =  htmlentities($_GET['x3']); 
      $siteID =  htmlentities($_GET['x4']); 
      $log =  htmlentities($_GET['x5']); 
      $ob_click_id =  htmlentities($_GET['ob_click_id']); */

      $campaignID = $request->input('campaign_id', null);
      $adGroupID = $request->input('adgroup', null);
      $creative = $request->input('creative', null);
      $test = $request->input('test', null);
      $sub_id = $request->input('sub_id', null);
      
      DB::table('postbacks')->insert([
        'campaign_id' => $campaignID,
        'adgroup_id' => $adGroupID,
        'creative' => $creative,
        'site_id' => $test,
        'ob_click_id' => $sub_id
    ]);

      return view('rise/postback');
    }
}
