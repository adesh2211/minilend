<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;

use Illuminate\Support\Facades\Auth;
use Validator;
use Hash;
use Mail;
use DateTime,DateTimeZone;
use Redirect,Response,File;
use App\Model\Customer;
use App\Model\CustomerApplication;
use App\Model\CustomerApplicationInfo;
class UserController extends Controller
{
	    /**
     * @SWG\Post(
     *     path="/admin/login",
     *     description="Login with Email",
     * tags={"User Register & Login Section"},
     *  @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         type="string",
     *         description="valid email",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         type="string",
     *         description="admin password",
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
    public function adminLogin(Request $request)
    {
    	try{
	        $rules =[
	            'email' => 'email|required',
	            'password' => 'required'
	        ];
	        $validator = \Validator::make($request->all(), $rules);
	        if ($validator->fails()) {
	            return response(array('status' => "error", 'statuscode' => 400, 'message' => $validator->getMessageBag()->first()), 400);
	        }

	         $user = User::where(function ($query) {
                                $query->where('email',request('email'));
                            })->first();
	        if (!$user){
                return Response(array('status' => "error", 'statuscode' => 400, 'message' => __('We are sorry, this user is not registered with us.')), 400);
            }elseif (!Hash::check($request->password, $user->password)){
                return Response(array('status' => "error", 'statuscode' => 400, 'message' => __('Sorry, this password is incorrect!')), 400);
            }
	        if(!$user->hasRole('admin')){
	        	return response(array('status' => "error", 'statuscode' => 400, 'message' =>"You are not registered admin role, Please try with other account."), 400);
	        }
	        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
	        	 return Response(array('status' => "error", 'statuscode' => 400, 'message' => __('Invalid Credentials')), 400);
	        }
	        $accessToken = auth()->user()->createToken('authToken')->accessToken;
	        return response(['user' => auth()->user(), 'access_token' => $accessToken]);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

     /**
     * @SWG\Post(
     *     path="/forgot_password",
     *     description="Forgot Password Api",
     * tags={"User Register & Login Section"},
     *
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         type="string",
     *         description="Email",
     *         required=true,
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function forgot_password(Request $request) {
        $validation = Validator::make($request->all(), [
                    'email' => 'bail|required',
                        ]
        );
        if ($validation->fails()) {
            return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                $validation->getMessageBag()->first()), 400);
        }
        $user = User::where('email', $request->email)->first();
        if (is_object($user)) {
            $password = rand('10000000', '99999999');
            $data['user_id'] = $user->id;
            $data['new_password'] = bcrypt($password);
            $data['name'] = $user->name;
            $data['email'] = $user->email;
            $mail = \Mail::send('emailtemplate.forgotpassword', array('data' => $data, 'password' => $password),
                            function($message) use ($data) {
                        $message->to($data['email'], $data['name'])->subject('MiniLend - Forgot Password!');
                        $message->from('info.minilend@gmail.com', 'noreply');
                    });
            if (Mail::failures()) {
                return response(['status' => 'error', 'statuscode' => 400, 'message' => __("We can't send password on your mail. Look email not valid")], 400);
            }else{
                User::whereId($data['user_id'])
                        ->limit(1)
                        ->update([
                            'password' => $data['new_password'],
                            'updated_at' => new \DateTime
                ]);
                return response(['status' => 'success', 'statuscode' => 200, 'message' => __('We have sent a temporary password in your email. Please check your email.')], 200);
            }
        } else {
            return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                __('This email is not registered with us!')), 400);
        }
    }

     /**
     * @SWG\Get(
     *     path="/admin/customers",
     *     description="Customers",
     * tags={"Admin"},
     *   security={
     *     {"Bearer": {}},
     *   },
     *     @SWG\Response(
     *         response=200,
     *         description="status:success,data:{customers:[],per_page:10,total:24},statuscode:200,message:Customer List",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function getCustomers(Request $request,Customer $customer){
        $input = $request->all();
        $per_page = (isset($request->per_page)?$request->per_page:10);
        $customers = $customer->newQuery();
        if(isset($input['ssn_search'])){
            $customers = $customers->where('ssn','like', '%'.$input['ssn_search'].'%');
        }
        if(isset($input['name_search'])){
            $name = $input['name_search'];
            $customers->where(function($q) use($name) {
                  $q->where('first_name', 'like', '%'.$name.'%')
                    ->orWhere('last_name', 'like', '%'.$name.'%')
                    ->orWhere('middle_name', 'like', '%'.$name.'%');
            });
        }
        if(isset($input['email_search'])){
            $customers = $customers->where('email','like', '%'.$input['email_search'].'%');
        }
        if(isset($input['gender_search'])){
            $customers = $customers->where('gender','like', '%'.$input['gender_search'].'%');
        }
        if(isset($input['state_search'])){
            $customers = $customers->where('state','like', '%'.$input['state_search'].'%');
        }
        if(isset($input['ssn_sortby'])){
            $customers = $customers->orderBy('ssn',$input['ssn_sortby']);
        }
        if(isset($input['email_sortby'])){
            $customers = $customers->orderBy('email',$input['email_sortby']);
        }
        if(isset($input['name_sortby'])){
            $customers = $customers->orderBy('first_name',$input['name_sortby']);
        }
        if(isset($input['gender_sortby'])){
            $customers = $customers->orderBy('gender',$input['gender_sortby']);
        }
        if(isset($input['state_sortby'])){
            $customers = $customers->orderBy('state',$input['state_sortby']);
        }
        $customers = $customers->paginate($per_page);
        // if($input['email_sortby'])
        // print_r($customers);die;
        foreach ($customers as $key => $customer) {
            $customer->applications;
            $customer->total_application = $customer->applications->count();
            unset($customer->applications);
        }
        $per_page = $customers->perPage();
        return response(['status' => 'success','data' =>
            ['customers'=>$customers->items(),
            'nextPageUrl'=>$customers->nextPageUrl(),
            'previousPageUrl'=>$customers->previousPageUrl(),
            'per_page'=>$per_page,'total'=>$customers->total()
        ], 'statuscode' => 200, 'message' => __('Customer Listing')
        ], 200);
    }

    /**
     * @SWG\Get(
     *     path="/admin/customer/applications",
     *     description="Customer Applications",
     * tags={"Admin"},
     *   security={
     *     {"Bearer": {}},
     *   },
     *     @SWG\Parameter(
     *         name="customer_id",
     *         in="query",
     *         type="string",
     *         description="Customer Id",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="status:success,data:{customer_applications:[],per_page:10,total:24},statuscode:200,message:Customer Applications",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function getCustomerApplications(Request $request){
        $validation = Validator::make($request->all(), [
                    'customer_id' => 'required|exists:customers,id',
                        ]
        );
        if ($validation->fails()) {
            return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                $validation->getMessageBag()->first()), 400);
        }
        $per_page = (isset($request->per_page)?$request->per_page:10);
        $customers = CustomerApplication::where('customer_id',$request->customer_id)->paginate($per_page);
        foreach ($customers as $key => $customer) {
           if(isset($customer->customer_info)){
                $customer->customer_info = json_decode($customer->customer_info);
            }
        }
        $per_page = $customers->perPage();
        return response(['status' => 'success','data' =>
            ['customer_applications'=>$customers->items(),
            'nextPageUrl'=>$customers->nextPageUrl(),
            'previousPageUrl'=>$customers->previousPageUrl(),
            'per_page'=>$per_page,'total'=>$customers->total()
        ], 'statuscode' => 200, 'message' => __('Customer Applications')
        ], 200);
    }


    /**
     * @SWG\Get(
     *     path="/customer/track_application",
     *     description="Customer Applications",
     * tags={"Customer"},
     *     @SWG\Parameter(
     *         name="request_type",
     *         in="query",
     *         type="string",
     *         description="ssn,application",
     *         required=true,
     *     ), 
     *     @SWG\Parameter(
     *         name="request_number",
     *         in="query",
     *         type="string",
     *         description="ssn_number,application_number",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="status:success,data:{customer_applications:[] or application:{}},statuscode:200,message:Customer Application",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function trackCustomerApplications(Request $request){
        $customMessages = [];
        $rules = ['request_type' => 'required|in:ssn,application','request_number'=>'required'];
        if(isset($request->request_type) && $request->request_type=='ssn'){
            $rules['request_number'] = 'required|exists:customers,ssn';
            $customMessages['request_number.exists'] = 'SSN Number Invalid';
            $customMessages['request_number.required'] = 'SSN number required(request_number)';
        }else if(isset($request->request_type) && $request->request_type=='application'){
            $rules['request_number'] = 'required|exists:customer_applications,application_number';
            $customMessages['request_number.exists'] = 'Application Number Invalid';
            $customMessages['request_number.required'] = 'Application number required(request_number)';
        }
        $validation = Validator::make($request->all(),$rules,$customMessages);
        if ($validation->fails()) {
            return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                $validation->getMessageBag()->first()), 400);
        }
        $input = $request->all();
        if($input['request_type']=='ssn'){
            $customer_applications = [];
            $customer = Customer::where('ssn',$input['request_number'])->first();
            if($customer){
               $customer_applications = CustomerApplication::select('id','application_number','status')->get();
            }
            return response(['status' =>'success',
                'data' =>['customer_applications'=>$customer_applications],
                'statuscode' => 200,
                'message' => __('Customer Applications')
            ], 200);
        }
        if($input['request_type']=='application'){
            $customer_application = CustomerApplication::where('application_number',$input['request_number'])->first();
            if($customer_application){
                unset($customer_application->customer_info);
            }
            // $application = $this->createJsonApplications($customer_application);
            return response(['status' =>'success',
                'data' =>['application'=>$customer_application],
                'statuscode' => 200,
                'message' => __('Customer Application Info')
            ], 200);
        }
    }

    /**
     * @SWG\Get(
     *     path="/admin/customer_application",
     *     description="Customers",
     * tags={"Admin"},
     *   security={
     *     {"Bearer": {}},
     *   },
     *     @SWG\Parameter(
     *         name="application_id",
     *         in="query",
     *         type="string",
     *         description="Application Id",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function getCustomerApplication(Request $request){
        $validation = Validator::make($request->all(), [
                    'application_id' => 'required|exists:customer_applications,id',
                        ]
        );
        if ($validation->fails()) {
            return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                $validation->getMessageBag()->first()), 400);
        }
        $application = CustomerApplication::where('id',$request->application_id)->first();
        if($application){
            $application->customer_info = json_decode($application->customer_info);
            $infos = CustomerApplicationInfo::where('customer_application_id',$application->id)->get();
            $data = [];
            foreach ($infos as $key => $value) {
                if(isset($value->current_assets)){
                    $value->current_assets = json_decode($value->current_assets);
                }
                if(isset($value->fixed_assets)){
                    $value->fixed_assets = json_decode($value->fixed_assets);
                }
                if(isset($value->other_assets)){
                    $value->other_assets = json_decode($value->other_assets);
                }
                if(isset($value->total_assets)){
                    $value->total_assets = $value->total_assets;
                }
                if(isset($value->current_liabities)){
                    $value->current_liabities = json_decode($value->current_liabities);
                }
                if(isset($value->long_term_liabities)){
                    $value->long_term_liabities = json_decode($value->long_term_liabities);
                }
                if(isset($value->owner_equity)){
                    $value->owner_equity = json_decode($value->owner_equity);
                }
                if(isset($value->total_liabities)){
                    $value->total_liabities = $value->total_liabities;
                }
                if(isset($value->common_financial_ratios)){
                    $value->common_financial_ratios = json_decode($value->common_financial_ratios);
                }
                if(isset($value->revenue)){
                    $value->revenue = json_decode($value->revenue);
                }
                if(isset($value->goods_sold)){
                    $value->goods_sold = json_decode($value->goods_sold);
                }
                if(isset($value->expenses)){
                    $value->expenses = json_decode($value->expenses);
                }
                if(isset($value->income_from_con_ope)){
                    $value->income_from_con_ope = $value->income_from_con_ope;
                }
                if(isset($value->below_line_items)){
                    $value->below_line_items = json_decode($value->below_line_items);
                }
                if(isset($value->net_income)){
                    $value->net_income = $value->net_income;
                }
                $data[$value->year] = $value; 
            }
            $application->data = $data;
        }
        return response(['status' => 'success','data' =>['application'=>$application], 'statuscode' => 200, 'message' => __('Customer Listing')
        ], 200);
    } 

    /**
     * @SWG\Post(
     *     path="/admin/update_status",
     *     description="Update Application Status",
     * tags={"Admin"},
     *   security={
     *     {"Bearer": {}},
     *   },
     *     @SWG\Parameter(
     *         name="application_id",
     *         in="query",
     *         type="string",
     *         description="Application Id",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         type="string",
     *         description="status",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function postStatusChange(Request $request){
        $validation = Validator::make($request->all(), [
                    'application_id' => 'required|exists:customer_applications,id',
                    'status' => 'required|in:in_review,underwriting,grant,reject',
                        ]
        );
        if ($validation->fails()) {
            return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                $validation->getMessageBag()->first()), 400);
        }
        $application = CustomerApplication::where('id',$request->application_id)->first();
        if($application->status=='in_review' && $request->status=='underwriting'){
                $application->status = $request->status;
        }elseif ($application->status=='underwriting' && ($request->status=='grant' || $request->status=='reject')) {
                $application->status = $request->status;
        }elseif (($application->status=='grant' || $application->status=='reject') && $request->status=='underwriting') {
                $application->status = $request->status;
        }elseif (($application->status=='grant') && $request->status=='reject') {
                $application->status = $request->status;
        }elseif (($application->status=='reject') && $request->status=='grant') {
                $application->status = $request->status;
        }elseif (($application->status=='underwriting') && $request->status=='in_review') {
                $application->status = $request->status;
        }else{
            return response(['status' => 'error', 'statuscode' => 400, 'message' => __("Can't Status Change Because Current Status is $application->status")
            ], 400);
        }
        $application->save();
        $application = $this->createJsonApplications($application);
        return response(['status' => 'success','data' =>['application'=>$application], 'statuscode' => 200, 'message' => __('Customer Listing')
        ], 200);
    }

    public function createJsonApplications($application){
            $application->customer_info = json_decode($application->customer_info);
            $infos = CustomerApplicationInfo::where('customer_application_id',$application->id)->get();
            $data = [];
            foreach ($infos as $key => $value) {
                if(isset($value->current_assets)){
                    $value->current_assets = json_decode($value->current_assets);
                }
                if(isset($value->fixed_assets)){
                    $value->fixed_assets = json_decode($value->fixed_assets);
                }
                if(isset($value->other_assets)){
                    $value->other_assets = json_decode($value->other_assets);
                }
                if(isset($value->total_assets)){
                    $value->total_assets = $value->total_assets;
                }
                if(isset($value->current_liabities)){
                    $value->current_liabities = json_decode($value->current_liabities);
                }
                if(isset($value->long_term_liabities)){
                    $value->long_term_liabities = json_decode($value->long_term_liabities);
                }
                if(isset($value->owner_equity)){
                    $value->owner_equity = json_decode($value->owner_equity);
                }
                if(isset($value->total_liabities)){
                    $value->total_liabities = $value->total_liabities;
                }
                if(isset($value->common_financial_ratios)){
                    $value->common_financial_ratios = json_decode($value->common_financial_ratios);
                }
                if(isset($value->revenue)){
                    $value->revenue = json_decode($value->revenue);
                }
                if(isset($value->goods_sold)){
                    $value->goods_sold = json_decode($value->goods_sold);
                }
                if(isset($value->expenses)){
                    $value->expenses = json_decode($value->expenses);
                }
                if(isset($value->income_from_con_ope)){
                    $value->income_from_con_ope = $value->income_from_con_ope;
                }
                if(isset($value->below_line_items)){
                    $value->below_line_items = json_decode($value->below_line_items);
                }
                if(isset($value->net_income)){
                    $value->net_income = $value->net_income;
                }
                $data[$value->year] = $value; 
            }
            $application->data = $data;
            return $application;
    }

    /**
     * @SWG\Post(
     *     path="/change_password",
     *     description="Change Password Api",
     * tags={"User Register & Login Section"},
     *   security={
     *     {"Bearer": {}},
     *   },
     *     @SWG\Parameter(
     *         name="current_password",
     *         in="query",
     *         type="string",
     *         description="Old Password",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="new_password",
     *         in="query",
     *         type="string",
     *         description="New Password",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function change_password(Request $request) {
        try {
            $user = Auth::user();
            $validation = Validator::make($request->all(), [
                        'current_password' => 'required',
                        'new_password' => 'required|min:8',
                        ]
            );
            if ($validation->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validation->getMessageBag()->first()), 400);
            }
            if (Hash::check($request->current_password, $user->password)) { 
               $user->fill([
                    'password' => Hash::make($request->new_password)
                ])->save();
                return response(["status" => "success", 'statuscode' => 200, 'message' => __('Password changed successfully !'), 'data' =>(Object)[]], 200);

            } else {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Current Password does not match'), 400);
            }
        } catch (Exception $e) {
            return response(['status' => "error", 'statuscode' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     *
     *   @SWG\Post(
     *     path="/app_logout",
     *     tags={"User Register & Login Section"},
     *     description="User Logout",
     *     security={
     *     {"Bearer": {}},
     *   },
     *     @SWG\Response(response=200, description="Success"),
     *     @SWG\Response(response=500, description="Api Error"),
     *     @SWG\Response(response=400, description="Unauthorized")
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function app_logout(Request $request) {
        try {
            $user = Auth::user();
            Auth()->user()->token()->revoke();
            User::whereId($user->id)
                    ->limit(1)
                    ->update([
                        'updated_at' => new \DateTime
            ]);
            return response(["status" => "success", 'statuscode' => 200, 'message' => __('Logout successfully !')], 200);
        } catch (\Exception $e) {
            return response(["status" => "error", 'statuscode' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
