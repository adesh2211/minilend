<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\User;
use App\Model\Customer;
use App\Model\CustomerApplication;
use App\Model\CustomerApplicationInfo;
class ApplicationMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $application_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($application_id)
    {
        $this->application_id = $application_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $customer_application = CustomerApplication::where('id',$this->application_id)->first();
        $mail = \Mail::send('emailtemplate.welcomeapplication', array('customer_application' =>$customer_application),
                function($message) use ($customer_application) {
            $message->to($customer_application->email, $customer_application->first_name.' '. $customer_application->last_name)->subject('MiniLend - Application Submitted');
            $message->from('info.minilend@gmail.com', 'noreply');
        });
    }
}
