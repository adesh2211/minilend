<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Model\Customer;
use App\Model\CustomerApplication;
use App\Model\CustomerApplicationInfo;
use Illuminate\Support\Facades\Auth;
use Validator;
use Hash;
use Mail;
use DateTime,DateTimeZone;
use Redirect,Response,File;
use App\Jobs\ApplicationMail;
use Carbon\Carbon;
class CustomerController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/customer_application",
     *     description="Customer Application",
     * tags={"Customer"},
     *  @SWG\Parameter(
     *         name="ssn",
     *         in="query",
     *         type="string",
     *         description="valid ssn number",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         type="string",
     *         description="valid email",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         type="string",
     *         description="title for e.g mr. mrs.",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="gender",
     *         in="query",
     *         type="string",
     *         description="gender",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="first_name",
     *         in="query",
     *         type="string",
     *         description="first_name",
     *         required=true,
     *     ),     
     *  @SWG\Parameter(
     *         name="middle_name",
     *         in="query",
     *         type="string",
     *         description="middle_name",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="last_name",
     *         in="query",
     *         type="string",
     *         description="last_name",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="phone_number",
     *         in="query",
     *         type="string",
     *         description="phone_number",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="dob",
     *         in="query",
     *         type="string",
     *         description="Y-m-d format 1991-06-16",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="home_address",
     *         in="query",
     *         type="string",
     *         description="home_address",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="city",
     *         in="query",
     *         type="string",
     *         description="city",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="state",
     *         in="query",
     *         type="string",
     *         description="state",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="zip",
     *         in="query",
     *         type="string",
     *         description="zip",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="country",
     *         in="query",
     *         type="string",
     *         description="country",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="phone_number",
     *         in="query",
     *         type="string",
     *         description="phone_number",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="customer_info",
     *         in="query",
     *         type="object",
     *         description="extra user info",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="balance_income_data",
     *         in="query",
     *         type="object",
     *         description="valid json for balance and income data",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function createCustomerApplication(Request $request)
    {
        $rules =[
                'email' => 'email|required',
                'first_name' => 'required',
                'last_name' => 'required',
                'gender' => 'required',
                'dob' => 'required|date_format:Y-m-d',
                'ssn' => 'required',
                'home_address' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required',
                'country' => 'required',
                'phone_number' => 'required',
                'customer_info' => 'required',
                'balance_income_data' => 'required',
            ];
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' => $validator->getMessageBag()->first()), 400);
        }
        $input = $request->all();
        $input['customer_info'] = json_encode($input['customer_info']);
        // $input['balance_income_data'] = json_encode($input['balance_income_data']);
        // print_r($input['balance_income_data']);die;
        $customer = Customer::where('ssn',$input['ssn'])->first();
        if(!$customer){
            $customer = new Customer();
            $customer->ssn = $input['ssn'];
            $customer->email = $input['email'];
            $customer->title = isset($input['title'])?$input['title']:null;
            $customer->first_name = $input['first_name'];
            $customer->middle_name = isset($input['middle_name'])?$input['middle_name']:null;
            $customer->last_name = $input['last_name'];
            $customer->gender = $input['gender'];
            $customer->dob = $input['dob'];
            $customer->home_address = $input['home_address'];
            $customer->city = $input['city'];
            $customer->state = $input['state'];
            $customer->zip_code = $input['zip'];
            $customer->country = $input['country'];
            $customer->phone_number = $input['phone_number'];
        }else{
            // $exist = CustomerApplication::where('customer_id',$customer->id)->whereIn('status',['pending','in_review'])->first();
            // if($exist){
            //     return response(array('status' => "error", 'statuscode' => 400, 'message' => $validator->getMessageBag()->first()), 400);
            // }
        }
        $customer->save();
        $customer_application = new CustomerApplication();
        $customer_application->customer_id = $customer->id;
        $customer_application->customer_info = $input['customer_info'];
        $customer_application->application_number = time().$customer->id;
        $customer_application->email = $input['email'];
        $customer_application->title = isset($input['title'])?$input['title']:null;;
        $customer_application->first_name = $input['first_name'];
        $customer_application->middle_name = isset($input['middle_name'])?$input['middle_name']:null;
        $customer_application->last_name = $input['last_name'];
        $customer_application->gender = $input['gender'];
        $customer_application->dob = $input['dob'];
        $customer_application->home_address = $input['home_address'];
        $customer_application->city = $input['city'];
        $customer_application->state = $input['state'];
        $customer_application->zip_code = $input['zip'];
        $customer_application->country = $input['country'];
        $customer_application->phone_number = $input['phone_number'];
        $customer_application->status = 'in_review';
        $customer_application->save();
        $customer_application->application_number = time().$customer->id.$customer_application->id;
        $customer_application->save();
        foreach ($input['balance_income_data'] as $key=>$value) {
            if($key){
                $CustomerApplicationInfo = new CustomerApplicationInfo();
                $CustomerApplicationInfo->year = $key;
                // print_r($value);die;
                if(isset($value['current_assets'])){
                    $CustomerApplicationInfo->current_assets = json_encode($value['current_assets']);
                }
                if(isset($value['fixed_assets'])){
                    $CustomerApplicationInfo->fixed_assets = json_encode($value['fixed_assets']);
                }
                if(isset($value['other_assets'])){
                    $CustomerApplicationInfo->other_assets = json_encode($value['other_assets']);
                }
                if(isset($value['total_assets'])){
                    $CustomerApplicationInfo->total_assets = $value['total_assets'];
                }
                if(isset($value['current_liabities'])){
                    $CustomerApplicationInfo->current_liabities = json_encode($value['current_liabities']);
                }
                if(isset($value['long_term_liabities'])){
                    $CustomerApplicationInfo->long_term_liabities = json_encode($value['long_term_liabities']);
                }
                if(isset($value['owner_equity'])){
                    $CustomerApplicationInfo->owner_equity = json_encode($value['owner_equity']);
                }
                if(isset($value['total_liabities'])){
                    $CustomerApplicationInfo->total_liabities =$value['total_liabities'];
                }
                if(isset($value['common_financial_ratios'])){
                    $CustomerApplicationInfo->common_financial_ratios = json_encode($value['common_financial_ratios']);
                }
                if(isset($value['revenue'])){
                    $CustomerApplicationInfo->revenue = json_encode($value['revenue']);
                }
                if(isset($value['goods_sold'])){
                    $CustomerApplicationInfo->goods_sold = json_encode($value['goods_sold']);
                }
                if(isset($value['expenses'])){
                    $CustomerApplicationInfo->expenses = json_encode($value['expenses']);
                }
                if(isset($value['income_from_con_ope'])){
                    $CustomerApplicationInfo->income_from_con_ope = $value['income_from_con_ope'];
                }
                if(isset($value['below_line_items'])){
                    $CustomerApplicationInfo->below_line_items = json_encode($value['below_line_items']);
                }
                if(isset($value['net_income'])){
                    $CustomerApplicationInfo->net_income = $value['net_income'];
                }
                $CustomerApplicationInfo->customer_application_id = $customer_application->id;
                $CustomerApplicationInfo->save();
            }
        }
        $job = (new ApplicationMail($customer_application->id));
        dispatch($job);
        return response(['status' => 'success','data'=>["application"=>$customer_application], 'statuscode' => 200, 'message' => __('Your Application has been submited')], 200);
    }
}
