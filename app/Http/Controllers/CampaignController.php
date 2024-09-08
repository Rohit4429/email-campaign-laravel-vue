<?php

namespace App\Http\Controllers;

use App\Mail\BulkMail;
use App\Mail\CampaignUserSendMail;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    public function index(Request $request){
        try{
            $campaign = Campaign::withCount('get_contact')->paginate(15);
            // dd($campaign);
            return Inertia::render('Campaign/Index',[
                'campaign' => $campaign,
            ]);

        }catch(Exception $e){
            return response()->json(['status'=>'error','message'=>'Something Wrong'.$e->getMessage()],403);
        }
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:campaigns',
            'upload_csv' => 'required|file|mimes:csv|max:2048', // 2MB Max Upload
        ]);

        try {
            DB::beginTransaction();

            $campaign = new Campaign();
            $campaign->name = $request->name;
            $campaign->added_by = Auth::user()->id;

            if ($request->hasFile('upload_csv')) {
                $file = $request->file('upload_csv');
                $filename = Carbon::now()->format('Ymdhis') . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('campaigns', $filename, 'public');
                $campaign->csv = $path;
            }

            $campaign->save();

            if ($campaign) {
                $path_storage_uploaded_csv = storage_path('app/public/' . $campaign->csv);

                if (file_exists($path_storage_uploaded_csv)) {
                    $file = fopen($path_storage_uploaded_csv, 'r');


                    $firstLine = fgets($file);
                    if (strpos($firstLine, "\u{FEFF}") === 0) {
                        $firstLine = substr($firstLine, 3);
                    }


                    $errorMessages = [];

                    while (($row = fgetcsv($file, null, ',')) !== false) {
                        $name = $row[0] ?? null;
                        $email = $row[1] ?? null;

                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            return back()->withErrors(['email' => 'Invalid email format.']);
                        }elseif(empty($email)){
                            return back()->withErrors(['email' => 'Email is required']);
                        }elseif(empty($name)){
                            return back()->withErrors(['name' => 'Name is required']);
                        }else{
                            Contact::insert([
                                'campaign_id' => $campaign->id,
                                'name' => $name,
                                'email' => $email,
                                'status' => 1
                            ]);
                        }

                    }

                    fclose($file);


                    DB::commit();
                    return back()->with('success', 'Campaign created successfully');
                } else {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'File not found: ' . $path_storage_uploaded_csv], 404);
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Something went wrong: ' . $e->getMessage()], 403);
        }
    }

    public function sendCampaign($id)
    {
        try {
            $campaign = Campaign::with('get_user')->where('id',$id)->first();

            if (!empty($campaign)) {
                Contact::where('campaign_id', $campaign->id)
                    ->chunk(100, function ($contacts) use ($campaign) {
                        foreach ($contacts as $contact) {
                            $data = [
                                'name' => $contact->name,
                                'email' => $contact->email,
                                'campaign_name' => $campaign->name,
                                'status' => 1,
                            ];

                            try {

                                Mail::to($contact->email)->queue(new BulkMail($data));
                                $contact->update(['status' => 2]);
                            } catch (Exception $e) {
                                $contact->update(['status' => 3]);
                                Log::error('Failed to send email to: ' . $contact->email . '. Error: ' . $e->getMessage());
                            }
                        }
                    });
                    $data = [
                        'name' => $campaign->get_user->email,
                    ];
                    Mail::to($campaign->get_user->email)->queue(new CampaignUserSendMail($data));
                return back()->with('success', 'Emails sent successfully!');
            } else {
                return response()->json(['status' => 'error', 'message' => 'Campaign not found'], 404);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong: ' . $e->getMessage()], 403);
        }
    }


}
