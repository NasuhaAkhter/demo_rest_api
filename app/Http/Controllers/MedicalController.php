<?php

namespace App\Http\Controllers;

use App\Allergy;
use App\ProductGeneral;
use App\ProductMember;
use App\Doctor;
use App\Immunization;
use App\Insurance;
use App\InsuranceDocument;
use App\LogHistory;
use App\Medication;
use App\MedicinTakingMethod;
use App\PhysicalCondition;
use Illuminate\Support\Collection; 
use Illuminate\Http\Request;
use JWTAuth;
// use Bugsnag\BugsnagLaravel\Facades\Bugsnag;

class MedicalController extends Controller
{
    public function post_product_medical(Request $request){
        $data = $request->all();
        $newData = new Collection([]);
        $product = ProductGeneral::where('id', $data['product_general_id'])->first();
        $member_id = productMember::where('product_general_id', $data['product_general_id'])->pluck('member_id');
        $doctors = null;
        $medicins = null;
        $allergies = null;
        $insurance = null;
        $immunizations = null;
        $conditions = null;
        $conditions = $this->updateMedicalCondition($data, $member_id, $product);
        $doctors = $this->updateMedicalDoctor($data, $member_id, $product);
        $medicins = $this->updateMedicalMedication($data, $member_id, $product);
        $allergies = $this->updateMedicalAllergy($data, $member_id, $product);
        $immunizations = $this->updateMedicalImmunization($data, $member_id, $product);
        $insurance = $this->updateMedicalInsurance($data, $member_id, $product);
        if ($doctors != null && $conditions != null && $insurance != null  && $allergies != null  && $immunizations != null) {
            $doctors = Doctor::where('product_general_id', $data['product_general_id'])->where('doctor_type', "Medical")->get();
            $conditions = PhysicalCondition::where('product_general_id', $data['product_general_id'])->where('type', "Medical")->get();
            $medicins = Medication::where('product_general_id',  $data['product_general_id'])->with('medicin_taking_time')->get();
            $insurance = Insurance::where('product_general_id', $data['product_general_id'])->get();
            if(sizeof($insurance) > 0) {
                foreach ($insurance as $value) {
                    $value['front_photo'] = InsuranceDocument::where('insurance_id', $value['id'])->where('pic_type', "Front_Photo")->first();
                    $value['back_photo'] = InsuranceDocument::where('insurance_id', $value['id'])->where('pic_type', "Back_Photo")->first();
                }
            }
            $allergies = Allergy::where('product_general_id', $data['product_general_id'])->get();
            $immunizations = Immunization::where('product_general_id', $data['product_general_id'])->get();
            $Log_history = LogHistory::where('product_general_id',  $data['product_general_id'])
                                ->where('to_user',  $data['user_id'])
                                ->where('tag',"Medical")
                                ->with('user_info','log_documents')
                                ->limit(3)
                                ->orderBy('id', 'desc')
                                ->get();
                if(sizeof($Log_history)>0){
                    foreach($Log_history as $value){
                        $value['LogType'] = "Update";
                        // if($value['user_id'] == $data['user_id'] )
                        // unset($value['user_info']);
                        $newData->push($value);
                    }
                }
             return response()->json([
                'insurance' => $insurance,
                'doctor' => $doctors,
                'Medication' => $medicins,
                'Allergy' => $allergies,
                'Immunization' => $immunizations,
                'PhysicalCondition' => $conditions,
                'Log' => $newData,
                'success' => true
            ], 200);
        }else {
            return response()->json([
                'doctor' => $doctors,
                'Medication' => $medicins,
                'Allergy' => $allergies,
                'Immunization' => $immunizations,
                'insurance' => $insurance,
                'conditions' => $conditions,
                'msg' => "Something went wrong .",
                'success' => false
            ], 400);
        }
    }
    public function updateMedicalCondition($data, $member_id, $product){
        $conditions = null;
        $AuthUserId =  JWTAuth::parseToken()->authenticate()->id;
        if (sizeof($data['conditions']) > 0) {
            $newData4 = new Collection([]);
            $find = PhysicalCondition::where('product_general_id', $data['product_general_id'])->where('type', "Medical")->pluck('id');
            $find_array = $find->toArray();
            foreach ($data['conditions'] as $value) {
                if ($value['id'] == 0) {
                    $conditions =  PhysicalCondition::create([
                        'product_general_id' => $data['product_general_id'],
                        'title' => $value['title'],
                        'date' => $value['date'],
                        'type' => "Medical",
                        'description' => $value['description'],
                    ]);
                    $condition_fields = $value;
                    $condition_fields['id'] = $conditions->id;
                    $value['id'] = $conditions->id;
                    $condition_fields['title'] = null;
                    $condition_fields['type'] = null;
                    $condition_fields['description'] = null ;
                    $condition_fields['date'] = null;
                    // \Log::info($condition_fields);
                    // \Log::info($value);
                    $log = $this->make_logs_for_conditions( $condition_fields, $value, $member_id, $data['product_general_id'] );
                    
                }
                else if (in_array($value['id'], $find_array)) {
                    $newData4->push($value['id']);
                    $get_the_item = PhysicalCondition::where('id', $value['id'])->first();
                    // \Log::info( $get_the_item->toArray());
                    // \Log::info($value);
                    $log = $this->make_logs_for_conditions( $get_the_item->toArray(), $value, $member_id, $data['product_general_id'] );
                    

                    $conditions = PhysicalCondition::where('id', $value['id'])->update([
                        'product_general_id' => $data['product_general_id'],
                        'title' => $value['title'],
                        'date' => $value['date'],
                        'type' => "Medical",
                        'description' => $value['description'],
                    ]);
                }
            }
            $text_array = [];
            $log_array = [];
            foreach ($find as $value3) {
                $newData5 = $newData4->toArray();
                if (!in_array($value3, $newData5)) {
                    $get_condition = PhysicalCondition::where('id',$value3 )->first();
                    $text = " removed ".$get_condition->title." as a medical condition. ";
                    $conditions = PhysicalCondition::where('id', $value3)->delete();
                    array_push($text_array, $text);
                }
            }
            foreach($text_array as $value){
                $singleMemberNotiData = [
                    'product_general_id' =>$data['product_general_id'],
                    'user_id' =>$AuthUserId,
                    'tag' => "Medical",
                    "message" =>  $value 
                ]; 
                foreach ($member_id as $value){
                    if( $value == $AuthUserId){
                        $seen = 1;
                    }else{
                        $seen = 0;
                    }
                    $singleMemberNotiData['seen'] =  $seen;
                    $singleMemberNotiData['to_user'] = $value;
                    array_push($log_array, $singleMemberNotiData);
                }
            }
            LogHistory::insert($log_array);
        }
        if (sizeof($data['conditions']) <= 0) {
                $find = PhysicalCondition::where('product_general_id', $data['product_general_id'])->where('type', "Medical")->pluck('title');
                if (sizeof($find) > 0) {
                    $text_array = [];
                    $log_array = [];
                    foreach ($find as $value3) {
                            $text = " removed ".$value3." as a medical condition. ";
                            array_push($text_array, $text);
                    }
                    $conditions = PhysicalCondition::where('product_general_id', $data['product_general_id'])->where('type', "Medical")->delete();
                    foreach($text_array as $value){
                        $singleMemberNotiData = [
                            'product_general_id' =>$data['product_general_id'],
                            'user_id' =>$AuthUserId,
                            'tag' => "Medical",
                            "message" =>  $value 
                        ]; 
                        foreach ($member_id as $value){
                            if( $value == $AuthUserId){
                                $seen = 1;
                            }else{
                                $seen = 0;
                            }
                            $singleMemberNotiData['seen'] =  $seen;
                            $singleMemberNotiData['to_user'] = $value;
                            array_push($log_array, $singleMemberNotiData);
                        }
                    }
                    LogHistory::insert($log_array);
                }else{
                    $conditions  = 1;
                }
        }
        return $conditions;
    }
    public function updateMedicalDoctor($data,$member_id,$product){
        $doctors = null;
        $AuthUserId =  JWTAuth::parseToken()->authenticate()->id;
        if (sizeof($data['doctors']) > 0) {
            $newData4 = new Collection([]);
            $find = Doctor::where('product_general_id', $data['product_general_id'])->where('doctor_type', "Medical")->pluck('id');
            $find_array = $find->toArray();
            foreach ($data['doctors'] as $value){
                if ($value['id'] == 0){
                    $doctors = Doctor::create([
                        'product_general_id' => $data['product_general_id'],
                        'doctor_type' => "Medical",
                        'department' => $value['department'],
                        'name' => $value['name'],
                        'profile_picture' => $value['profile_picture'],
                        'address' => $value['address'],
                        'phone_number' => $value['phone_number'],
                    ]);
                    $null_fields = $value;
                    $null_fields['id'] = $doctors->id;
                    $value['id'] = $doctors->id;
                    $null_fields['name'] = null ;
                    $null_fields['doctor_type'] = null;
                    $null_fields['department'] = null;
                    $null_fields['address'] = null;
                    $null_fields['phone_number'] = null;
                    // \Log::info($null_fields);
                    // \Log::info($value);
                    $log = $this->make_logs_for_doctors( $null_fields, $value, $member_id, $data['product_general_id'] );
                    

                }else if (in_array($value['id'], $find_array)){
                    $newData4->push($value['id']);
                    $get_the_item = Doctor::where('id', $value['id'])->first();
                    // \Log::info($get_the_item->toArray());
                    // \Log::info($value);
                    $log = $this->make_logs_for_doctors( $get_the_item->toArray(), $value, $member_id, $data['product_general_id'] );
                    

                    $doctors = Doctor::where('id', $value['id'])->update([
                        'product_general_id' => $data['product_general_id'],
                        'doctor_type' => "Medical",
                        'department' => $value['department'],
                        'name' => $value['name'],
                        'profile_picture' => $value['profile_picture'],
                        'address' => $value['address'],
                        'phone_number' => $value['phone_number'],
                    ]);
                }else {
                    $doctors   = 1;
                }
            }
            $text_array = [];
            $log_array = [];
            foreach ($find as $value3) {
                $newData5 = $newData4->toArray();
                if (!in_array($value3, $newData5)) {
                    $get_dostor = Doctor::where('id',$value3 )->first();
                    $text = " removed ".$get_dostor->name." as a doctor. ";
                    $doctors = Doctor::where('id', $value3)->delete();
                    array_push($text_array, $text);
                }
            }
            foreach($text_array as $value){
                $singleMemberNotiData = [
                    'product_general_id' =>$data['product_general_id'],
                    'user_id' =>$AuthUserId,
                    'tag' => "Medical",
                    "message" =>  $value 
                ]; 
                foreach ($member_id as $value){
                    if( $value == $AuthUserId){
                        $seen = 1;
                    }else{
                        $seen = 0;
                    }
                    $singleMemberNotiData['seen'] =  $seen;
                    $singleMemberNotiData['to_user'] = $value;
                    array_push($log_array, $singleMemberNotiData);
                }
            }
            LogHistory::insert($log_array);
        }
        if (sizeof($data['doctors']) <= 0) {
            $find = Doctor::where('product_general_id', $data['product_general_id'])->where('doctor_type', "Medical")->pluck('id');
            if (sizeof($find) > 0) {
                $text_array = [];
                $log_array = [];
                foreach ($find as $value3) {
                    $get_doctor = Doctor::where('id',$value3 )->first();
                    $text = " removed ".$get_doctor->name." as a doctor. ";
                    array_push($text_array, $text);
                }
                $doctors = Doctor::where('product_general_id', $data['product_general_id'])->where('doctor_type', "Medical")->delete();
                foreach($text_array as $value){
                    $singleMemberNotiData = [
                        'product_general_id' =>$data['product_general_id'],
                        'user_id' =>$AuthUserId,
                        'tag' => "Medical",
                        "message" =>  $value 
                    ]; 
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($log_array, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($log_array);
            } else {
                $doctors  = 1;
            }
        }
        return $doctors;
    }
    public function updateMedicalMedication($data,$member_id,$product){
        $medicins = null;
        $AuthUserId =  JWTAuth::parseToken()->authenticate()->id;
        if (sizeof($data['medicins']) > 0) {
            $newData4 = new Collection([]);
            $find = Medication::where('product_general_id', $data['product_general_id'])->pluck('id');
            $find_array = $find->toArray();
            foreach ($data['medicins'] as $value) {
                if ($value['id'] == 0) {
                    $medicins = Medication::create([
                        'product_general_id' => $data['product_general_id'],
                        'medicin_name' => $value['medicin_name'],
                        'power' => $value['power'],
                        'taking_method' => $value['taking_method'],
                        'cause' => $value['cause'],
                        'time' => $value['time'],
                        'note' => $value['note'],
                        'doctor_id' => $value['doctor_id'],
                    ]);
                    $null_fields = $value;
                    $null_fields['id'] = $medicins->id;
                    $value['id'] = $medicins->id;
                    $null_fields['medicin_name'] = null ;
                    $null_fields['power'] = null;
                    $null_fields['taking_method'] = null;
                    $null_fields['cause'] = null;
                    $null_fields['time'] = null;
                    $null_fields['note'] = null;
                    // \Log::info($null_fields);
                    // \Log::info($value);
                    $log = $this->make_logs_for_medicins($null_fields, $value, $member_id, $data['product_general_id'] );                   
                    

                }else if (in_array($value['id'], $find_array)){
                    $newData4->push($value['id']);
                    $get_the_item = Medication::where('id', $value['id'])->first();
                    // \Log::info($get_the_item->toArray());
                    // \Log::info($value);
                    $log = $this->make_logs_for_medicins($get_the_item->toArray(), $value, $member_id, $data['product_general_id'] );
                    

                    $medicins = Medication::where('id', $value['id'])->update([
                        'product_general_id' => $data['product_general_id'],
                        'medicin_name' => $value['medicin_name'],
                        'power' => $value['power'],
                        'taking_method' => $value['taking_method'],
                        'cause' => $value['cause'],
                        'time' => $value['time'],
                        'note' => $value['note'],
                        'doctor_id' => $value['doctor_id'],
                    ]);
                }else{
                    $medicins = 1;  
                }
                
            }
            $text_array = [];
            $log_array = [];
            foreach ($find as $value3) {
                $newData5 = $newData4->toArray();
                if (!in_array($value3, $newData5)) {
                    $get_medicin = Medication::where('id',$value3 )->first();
                    $text = " removed ".$get_medicin->name." as medication. ";
                    array_push($text_array, $text);
                    $medicins = Medication::where('id', $value3)->delete();
                }
            }
            foreach($text_array as $value){
                $singleMemberNotiData = [
                    'product_general_id' =>$data['product_general_id'],
                    'user_id' =>$AuthUserId,
                    'tag' => "Medical",
                    "message" =>  $value 
                ]; 
                foreach ($member_id as $value){
                    if( $value == $AuthUserId){
                        $seen = 1;
                    }else{
                        $seen = 0;
                    }
                    $singleMemberNotiData['seen'] =  $seen;
                    $singleMemberNotiData['to_user'] = $value;
                    array_push($log_array, $singleMemberNotiData);
                }
            }
            LogHistory::insert($log_array);
        }
        if (sizeof($data['medicins']) <= 0) {
            $find = Medication::where('product_general_id', $data['product_general_id'])->pluck('id');
            if (sizeof($find) > 0) {
                $text_array = [];
                $log_array = [];
                foreach ($find as $value3) {
                    $get_doctor = Medication::where('id',$value3 )->first();
                    $text = " removed ".$get_doctor->name." as medication . ";
                    array_push($text_array, $text);
                }
                foreach($text_array as $value){
                    $singleMemberNotiData = [
                        'product_general_id' =>$data['product_general_id'],
                        'user_id' =>$AuthUserId,
                        'tag' => "Medical",
                        "message" =>  $value 
                    ]; 
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($log_array, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($log_array);
                $medicins  = Medication::where('product_general_id', $data['product_general_id'])->delete();
            } else {
                $medicins   = 1;
            }
        }
        return $medicins;
    }
    public function updateMedicalAllergy($data,$member_id,$product){
        $allergies = null;
        $AuthUserId =  JWTAuth::parseToken()->authenticate()->id;
        if (sizeof($data['allergies']) > 0) {
            $newData4 = new Collection([]);
            $find = Allergy::where('product_general_id', $data['product_general_id'])->pluck('id');
            $find_array = $find->toArray();
            foreach ($data['allergies'] as $value) {
                if($value['id'] == 0) {
                    $allergies = Allergy::create([
                        'product_general_id' => $data['product_general_id'],
                        'allergy_type' => $value['allergy_type'],
                        'symptoms' => $value['symptoms'],
                        'severity' => $value['severity']
                    ]);
                    $null_fields = $value;
                    $null_fields['id'] = $allergies->id;
                    $value['id'] = $allergies->id;
                    $null_fields['allergy_type'] = null ;
                    $null_fields['symptoms'] = null;
                    $null_fields['severity'] = null;
                    // \Log::info($null_fields);
                    // \Log::info($value);
                    $log = $this->make_logs_for_allergies($null_fields, $value, $member_id, $data['product_general_id'] );                   
                    

                }else if (in_array($value['id'], $find_array)) {
                    $newData4->push($value['id']);
                    $get_the_item = Allergy::where('id', $value['id'])->first();
                    // \Log::info($get_the_item->toArray());
                    // \Log::info($value);
                    $log = $this->make_logs_for_allergies($get_the_item->toArray(), $value, $member_id, $data['product_general_id'] );
                    

                    $allergies = Allergy::where('id', $value['id'])->update([
                        'product_general_id' => $data['product_general_id'],
                        'allergy_type' => $value['allergy_type'],
                        'symptoms' => $value['symptoms'],
                        'severity' => $value['severity']
                    ]);
                }
            }
            $text_array = [];
            $log_array = [];
            foreach ($find as $value3) {
                $newData5 = $newData4->toArray();
                if (!in_array($value3, $newData5)){
                    $get_allergy = Allergy::where('id',$value3 )->first();
                    $text = " removed ".$get_allergy->allergy_type." as an allergy. ";
                    array_push($text_array, $text);
                    $allergies = Allergy::where('id', $value3)->delete();
                }
            }
            foreach($text_array as $value){
                $singleMemberNotiData = [
                    'product_general_id' =>$data['product_general_id'],
                    'user_id' =>$AuthUserId,
                    'tag' => "Medical",
                    "message" =>  $value 
                ]; 
                foreach ($member_id as $value){
                    if( $value == $AuthUserId){
                        $seen = 1;
                    }else{
                        $seen = 0;
                    }
                    $singleMemberNotiData['seen'] =  $seen;
                    $singleMemberNotiData['to_user'] = $value;
                    array_push($log_array, $singleMemberNotiData);
                }
            }
            LogHistory::insert($log_array);
        }
        if (sizeof($data['allergies']) <= 0) {
            $find = Allergy::where('product_general_id', $data['product_general_id'])->pluck('id');
            if (sizeof($find) > 0) {
                $text_array = [];
                $log_array = [];
                foreach ($find as $value3) {
                    $get_allergy = Allergy::where('id',$value3 )->first();
                    $text = " removed ".$get_allergy->allergy_type." as an allergy . ";
                    array_push($text_array, $text);
                }
                foreach($text_array as $value){
                    $singleMemberNotiData = [
                        'product_general_id' =>$data['product_general_id'],
                        'user_id' =>$AuthUserId,
                        'tag' => "Medical",
                        "message" =>  $value 
                    ]; 
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($log_array, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($log_array);
                $allergies = Allergy::where('product_general_id', $data['product_general_id'])->delete();
            } else {
                $allergies   = 1;
            }
        }
        return $allergies;
    }
    public function updateMedicalInsurance($data,$member_id,$product){
        $insurance = null;
        $AuthUserId =  JWTAuth::parseToken()->authenticate()->id;
        if ($data['insurance']) {
            $newData4 = new Collection([]);
            $find_array = Insurance::where('product_general_id', $data['product_general_id'])->pluck('id');
            $find = $find_array->toArray();
            foreach ($data['insurance'] as $key => $value){
                $front_photo = $value['Front_Photo'];
                $back_photo = $value['Back_Photo'];
                 
                if ($value['id'] == 0) {
                    $insurance = Insurance::create(
                        [
                            'product_general_id' => $data['product_general_id'],
                            'plan_name' => $value['plan_name'],
                            'id_number' => $value['id_number'],
                            'rx_bin' => $value['rx_bin'],
                            'rx_group' => $value['rx_group'],
                            'rx_pcn' => $value['rx_pcn'],
                            'phone_number' => $value['phone_number'],
                        ]
                    );
                    if($insurance) {
                        if ($front_photo != null) {
                            if ($front_photo['id'] == 0) {
                                $insurance_front = InsuranceDocument::create([
                                    'insurance_id' => $insurance->id,
                                    'pic_type' => $front_photo['pic_type'],
                                    'url_type' => $front_photo['url_type'],
                                    'url' => $front_photo['url'],
                                    'extension' => $front_photo['extension'],
                                ]);
                                 
                            }else {
                                return response()->json([
                                    'msg' => "Something went wrong .",
                                    'success' => false
                                ], 400);
                            }
                        }
                        if ($back_photo != null) {
                            if ($back_photo['id'] == 0) {
                                $insurance_back = InsuranceDocument::create([
                                    'insurance_id' => $insurance->id,
                                    'pic_type' => $back_photo['pic_type'],
                                    'url_type' => $back_photo['url_type'],
                                    'url' => $back_photo['url'],
                                    'extension' => $back_photo['extension'],
                                ]);
                                 
                            }else {
                                return response()->json([
                                    'msg' => "Something went wrong .",
                                    'success' => false
                                ], 400);
                            }
                        }
                    }
                    $null_fields = $value;
                    $null_fields['id'] = $insurance->id;
                    $value['id'] = $insurance->id;
                    $null_fields['plan_name'] = null ;
                    $null_fields['id_number'] = null;
                    $null_fields['rx_bin'] = null;
                    $null_fields['rx_group'] = null;
                    $null_fields['rx_pcn'] = null;
                    $null_fields['phone_number'] = null;
                    $null_fields['Front_Photo'] = null;
                    $null_fields['Back_Photo'] = null;
                    // $value = $value->toArray();
                    if($value['Front_Photo'] == null){
                         $value['Front_Photo'] = null;
                    }else{
                        unset($value['Front_Photo']);
                        $value['Front_Photo'] = "uploaded";
                    }
                    if($value['Back_Photo'] == null){
                          $value['Back_Photo'] = null;
                    }else if( $value['Back_Photo'] != null){
                        unset($value['Back_Photo']);
                        $value['Back_Photo'] = "uploaded";
                    }
                    $log = $this->make_logs_for_insurances($null_fields, $value, $member_id, $data['product_general_id'] );
                }else if (in_array($value['id'], $find)){
                    $newData4->push($value['id']);
                    if($insurance != 0) {
                        if ($front_photo != null) {
                            if ($front_photo['id'] == 0) {
                                $insurance_front = InsuranceDocument::create([
                                    'insurance_id' => $value['id'],
                                    'pic_type' => $front_photo['pic_type'],
                                    'url_type' => $front_photo['url_type'],
                                    'url' => $front_photo['url'],
                                    'extension' => $front_photo['extension'],
                                ]);
                                
                            }else{
                                $insurance_front = InsuranceDocument::where('id', $front_photo['id'])->update([
                                    'insurance_id' => $value['id'],
                                    'pic_type' => $front_photo['pic_type'],
                                    'url_type' => $front_photo['url_type'],
                                    'url' => $front_photo['url'],
                                    'extension' => $front_photo['extension'],
                                ]);
                                 
                            }
                        }
                        if ($back_photo != null) {
                            if ($back_photo['id'] == 0) {
                                $insurance_back = InsuranceDocument::create([
                                    'insurance_id' => $value['id'],
                                    'pic_type' => $back_photo['pic_type'],
                                    'url_type' => $back_photo['url_type'],
                                    'url' => $back_photo['url'],
                                    'extension' => $back_photo['extension'],
                                ]);
                                 
                            }else{
                                $insurance_back = InsuranceDocument::where('id', $back_photo['id'])->update([
                                    'insurance_id' => $value['id'],
                                    'pic_type' => $back_photo['pic_type'],
                                    'url_type' => $back_photo['url_type'],
                                    'url' => $back_photo['url'],
                                    'extension' => $back_photo['extension'],
                                ]);
                                 
                            }
                        }
                    }
                    $get_the_item = Insurance::where('id', $value['id'])->with('Front_Photo','Back_Photo')->first()->toArray();
                    $insurance = Insurance::where('id', $value['id'])->update(
                        [
                            'product_general_id' => $data['product_general_id'],
                            'plan_name' => $value['plan_name'],
                            'id_number' => $value['id_number'],
                            'rx_bin' => $value['rx_bin'],
                            'rx_group' => $value['rx_group'],
                            'rx_pcn' => $value['rx_pcn'],
                            'phone_number' => $value['phone_number'],
                        ]
                    );
                    if($get_the_item['front__photo'] == null){
                        unset($get_the_item['front__photo']);
                        $get_the_item['Front_Photo'] = null;
                    }else{
                        unset($get_the_item['front__photo']);
                        $get_the_item['Front_Photo'] = "uploaded";
                    }
                    if($get_the_item['back__photo'] == null){
                        unset($get_the_item['back__photo']);
                        $get_the_item['Back_Photo'] = null;
                    }else{
                        unset($get_the_item['back__photo']);
                        $get_the_item['Back_Photo'] = "uploaded";
                    }
                    if($value['Front_Photo'] == null){
                        $value['Front_Photo'] = null;
                   }else{
                       unset($value['Front_Photo']);
                       $value['Front_Photo'] = "uploaded";
                   }
                   if($value['Back_Photo'] == null){
                         $value['Back_Photo'] = null;
                   }else if( $value['Back_Photo'] != null){
                       unset($value['Back_Photo']);
                       $value['Back_Photo'] = "uploaded";
                   }
                     
                    $log = $this->make_logs_for_insurances($get_the_item, $value, $member_id, $data['product_general_id'] );
                    
                }
            }
            $text_array = [];
            $log_array = [];
            foreach ($find_array as $value3) {
                $newData5 = $newData4->toArray();
                if(!in_array($value3, $newData5)) {
                    $get_insuances = Insurance::where('id',$value3 )->first();
                    $text = " removed ".$get_insuances->plan_name." as an insurance plan. ";
                    array_push($text_array, $text);
                    $insurance = Insurance::where('id', $value3)->delete();
                 }
            }
            foreach($text_array as $value){
                $singleMemberNotiData = [
                    'product_general_id' =>$data['product_general_id'],
                    'user_id' =>$AuthUserId,
                    'tag' => "Medical",
                    "message" =>  $value 
                ]; 
                foreach ($member_id as $value){
                    if( $value == $AuthUserId){
                        $seen = 1;
                    }else{
                        $seen = 0;
                    }
                    $singleMemberNotiData['seen'] =  $seen;
                    $singleMemberNotiData['to_user'] = $value;
                    array_push($log_array, $singleMemberNotiData);
                }
            }
            LogHistory::insert($log_array);
        }
        if (sizeof($data['insurance']) <= 0) {
            $find = Insurance::where('product_general_id', $data['product_general_id'])->pluck('id');
            if (sizeof($find) > 0) {
                $text_array = [];
                $log_array = [];
                foreach ($find as $value3) {
                    $get_insurance = Insurance::where('id',$value3 )->first();
                    $text = " removed ".$get_insurance->plan_name." as an insurance plan . ";
                    array_push($text_array, $text);
                }
                foreach($text_array as $value){
                    $singleMemberNotiData = [
                        'product_general_id' =>$data['product_general_id'],
                        'user_id' =>$AuthUserId,
                        'tag' => "Medical",
                        "message" =>  $value 
                    ]; 
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($log_array, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($log_array);
                $insurance = Insurance::where('product_general_id', $data['product_general_id'])->delete();
                // $img_delete = InsuranceDocument::whereIn('insurance_id ', $find)->delete();
            }else {
                $insurance  = 1;
            }
        }
        return $insurance;
    }
    public function updateMedicalImmunization($data,$member_id,$product){
        $immunizations = null;
        $AuthUserId =  JWTAuth::parseToken()->authenticate()->id;
        if (sizeof($data['immunizations']) > 0) {
            $newData4 = new Collection([]);
            $find = Immunization::where('product_general_id', $data['product_general_id'])->pluck('id');
            $find_array = $find->toArray();
            foreach ($data['immunizations'] as $value) {
                if ($value['id'] == 0) {
                    $immunizations = Immunization::create([
                        'product_general_id' => $data['product_general_id'],
                        'title' => $value['title'],
                        'description' => $value['description'],
                        'date' => $value['date']
                    ]);
                    $null_fields = $value;
                    $null_fields['id'] = $immunizations->id;
                    $value['id'] = $immunizations->id;
                    $null_fields['title'] = null ;
                    $null_fields['description'] = null;
                    $null_fields['date'] = null;
                    $log = $this->make_logs_for_immunization($null_fields, $value, $member_id, $data['product_general_id'] );                   
                    

                } else if (in_array($value['id'], $find_array)) {
                    $newData4->push($value['id']);
                    $get_the_item = Immunization::where('id', $value['id'])->first();
                    $log = $this->make_logs_for_immunization($get_the_item->toArray(), $value, $member_id, $data['product_general_id'] );
                    

                    $immunizations = Immunization::where('id', $value['id'])->update([
                        'product_general_id' => $data['product_general_id'],
                        'title' => $value['title'],
                        'description' => $value['description'],
                        'date' => $value['date']
                    ]);
                }
            }
            $text_array = [];
            $log_array = [];
            foreach ($find as $value3) {
                $newData5 = $newData4->toArray();
                if(!in_array($value3, $newData5)) {
                    $get_immunization = Immunization::where('id',$value3 )->first();
                    $text = " removed ".$get_immunization->title." in the immunization record. ";
                    array_push($text_array, $text);
                    $immunizations = Immunization::where('id', $value3)->delete();
                }
            }
            foreach($text_array as $value){
                $singleMemberNotiData = [
                    'product_general_id' =>$data['product_general_id'],
                    'user_id' =>$AuthUserId,
                    'tag' => "Medical",
                    "message" =>  $value 
                ]; 
                foreach ($member_id as $value){
                    if( $value == $AuthUserId){
                        $seen = 1;
                    }else{
                        $seen = 0;
                    }
                    $singleMemberNotiData['seen'] =  $seen;
                    $singleMemberNotiData['to_user'] = $value;
                    array_push($log_array, $singleMemberNotiData);
                }
            }
            LogHistory::insert($log_array);
        }
        if(sizeof($data['immunizations']) <= 0) {
            $find = Immunization::where('product_general_id', $data['product_general_id'])->pluck('id');
            if (sizeof($find) > 0) {
                $text_array = [];
                $log_array = [];
                foreach ($find as $value3) {
                    $get_immunization = Immunization::where('id',$value3 )->first();
                    $text = " removed ".$get_immunization->title." in the immunization record . ";
                    array_push($text_array, $text);
                }
                foreach($text_array as $value){
                    $singleMemberNotiData = [
                        'product_general_id' =>$data['product_general_id'],
                        'user_id' =>$AuthUserId,
                        'tag' => "Medical",
                        "message" =>  $value 
                    ]; 
                    foreach ($member_id as $value){
                        if( $value == $AuthUserId){
                            $seen = 1;
                        }else{
                            $seen = 0;
                        }
                        $singleMemberNotiData['seen'] =  $seen;
                        $singleMemberNotiData['to_user'] = $value;
                        array_push($log_array, $singleMemberNotiData);
                    }
                }
                LogHistory::insert($log_array);
                $immunizations = Immunization::where('product_general_id', $data['product_general_id'])->delete();
                 
            } else {
                $immunizations   = 1;
            }
        }
        return $immunizations;
    }
    public function make_logs_for_conditions($prior, $new, $member_id, $product_id){
        $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
        $new= (array)$new;
        $prior= (array)$prior;
        $diffrence = array_diff($new,$prior);
        $text = "";
        $type = "";
        $text_array = new Collection([]);
        foreach($diffrence as $key => $value){
            if($prior[$key] == null){
                $type = "Added";
            }else if($prior[$key] != null &&  $new[$key] != null){
                $type = "Updated";
            }else{
                $type = "Removed";
            }
            $text = $this->return_text_for_medical_conditions($key, $value, $type, $prior, $new);
            $text_array->push($text);
        }
        foreach ($member_id as $value){
            foreach( $text_array as $value2 ){
                if( $value == $AuthUserId){
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 1,
                        "message" =>  $value2 
                    ]);
                }else{
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 0,
                        "message" =>  $value2 
                    ]);
                }
            }
        }
       return $text_array;
    }
    public function make_logs_for_doctors($prior, $new, $member_id, $product_id){
        $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
        $new= (array)$new;
        $prior= (array)$prior;
        $diffrence = array_diff($new,$prior);
        $text = "";
        $type = "";
        $text_array = new Collection([]);
        foreach($diffrence as $key => $value){
            if($prior[$key] == null){
                $type = "Added";
            }else if($prior[$key] != null &&  $new[$key] != null){
                $type = "Updated";
            }else{
                $type = "Removed";
            }
            $text = $this->return_text_for_medical_doctors($key, $value, $type, $prior, $new);
            $text_array->push($text);
        }
        foreach ($member_id as $value){
            foreach( $text_array as $value2 ){
                if( $value == $AuthUserId){
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 1,
                        "message" =>  $value2 
                    ]);
                }else{
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 0,
                        "message" =>  $value2 
                    ]);
                }
            }
        }
       return $text_array;
    }
    public function make_logs_for_medicins($prior, $new, $member_id, $product_id){
        $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
        $new= (array)$new;
        $prior= (array)$prior;
        $diffrence = array_diff($new,$prior);
        $text = "";
        $type = "";
        $text_array = new Collection([]);
        foreach($diffrence as $key => $value){
            if($prior[$key] == null){
                $type = "Added";
            }else if($prior[$key] != null &&  $new[$key] != null){
                $type = "Updated";
            }else{
                $type = "Removed";
            }
            $text = $this->return_text_for_medicin($key, $value, $type, $prior, $new);
            $text_array->push($text);
        }
        foreach ($member_id as $value){
            foreach( $text_array as $value2 ){
                if( $value == $AuthUserId){
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 1,
                        "message" =>  $value2 
                    ]);
                }else{
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 0,
                        "message" =>  $value2 
                    ]);
                }
            }
        }
       return $text_array;
    }
    public function make_logs_for_insurances($prior, $new, $member_id, $product_id){
        $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
        $new= (array)$new;
        $prior= (array)$prior;
        $diffrence = array_diff($new,$prior);
        $text = "";
        $type = "";
        $text_array = new Collection([]);
        foreach($diffrence as $key => $value){
            if($prior[$key] == null){
                $type = "Added";
            }else if($prior[$key] != null &&  $new[$key] != null){
                $type = "Updated";
            }else{
                $type = "Removed";
            }
            $text = $this->return_text_for_insurance($key, $value, $type, $prior, $new);
            $text_array->push($text);
        }
        foreach ($member_id as $value){
            foreach( $text_array as $value2 ){
                if( $value == $AuthUserId){
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 1,
                        "message" =>  $value2 
                    ]);
                }else{
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 0,
                        "message" =>  $value2 
                    ]);
                }
            }
        }
       return $text_array;
    }
    public function make_logs_for_immunization($prior, $new, $member_id, $product_id){
        $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
        $new= (array)$new;
        $prior= (array)$prior;
        $diffrence = array_diff($new,$prior);
        $text = "";
        $type = "";
        $text_array = new Collection([]);
        foreach($diffrence as $key => $value){
            if($prior[$key] == null){
                $type = "Added";
            }else if($prior[$key] != null &&  $new[$key] != null){
                $type = "Updated";
            }else{
                $type = "Removed";
            }
            $text = $this->return_text_for_immunization($key, $value, $type, $prior, $new);
            $text_array->push($text);
        }
        foreach ($member_id as $value){
            foreach( $text_array as $value2 ){
                if( $value == $AuthUserId){
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 1,
                        "message" =>  $value2 
                    ]);
                }else{
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 0,
                        "message" =>  $value2 
                    ]);
                }
            }
        }
       return $text_array;
    }
    public function make_logs_for_allergies($prior, $new, $member_id, $product_id){
        $AuthUserId = JWTAuth::parseToken()->authenticate()->id; 
        $new= (array)$new;
        $prior= (array)$prior;
        $diffrence = array_diff($new,$prior);
        $text = "";
        $type = "";
        $text_array = new Collection([]);
        foreach($diffrence as $key => $value){
            if($prior[$key] == null){
                $type = "Added";
            }else if($prior[$key] != null &&  $new[$key] != null){
                $type = "Updated";
            }else{
                $type = "Removed";
            }
            $text = $this->return_text_for_allergies($key, $value, $type, $prior, $new);
            $text_array->push($text);
        }
        foreach ($member_id as $value){
            foreach( $text_array as $value2 ){
                if( $value == $AuthUserId){
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 1,
                        "message" =>  $value2 
                    ]);
                }else{
                    LogHistory::create([
                        'product_general_id' =>$product_id,
                        'user_id' =>$AuthUserId,
                        'to_user' =>$value,
                        'tag' => "Medical",
                        'seen' => 0,
                        "message" =>  $value2 
                    ]);
                }
            }
        }
       return $text_array;
    }
    public function return_text_for_medicin($key, $value, $type, $prior, $new){
        $text = ""; 
        if($key == "medicin_name"  ){
            if($type == "Updated") $text = " changed doctor medication from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added ".$new[$key]." as a medication.";
            if($type == "Removed") $text = " removed ".$prior[$key]." as a medication.";
        }
        if($key == "cause"  ){
            if($type == "Updated") $text = " changed Medication Use linked to ".$new['medicin_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Medication Use linked to ".$new['medicin_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Medication Use linked to ".$new['medicin_name'].": ".$prior[$key].".";
        }
        if($key == "taking_method"){
            if($type == "Updated") $text = " changed Medication Dosage linked to ".$new['medicin_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Medication Dosage linked to ".$new['medicin_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Medication Dosage linked to ".$new['medicin_name'].": ".$prior[$key].".";
        }
        if($key == "time"){
            if($type == "Updated") $text = " changed Medication Time linked to ".$new['medicin_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Medication Time linked to ".$new['medicin_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Medication Time linked to ".$new['medicin_name'].": ".$prior[$key].".";
        }
        if($key == "power"){
            if($type == "Updated") $text = " changed Medication Frequency linked to ".$new['medicin_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Medication Frequency linked to ".$new['medicin_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Medication Frequency linked to ".$new['medicin_name'].": ".$prior[$key].".";
        }
        if($key == "note"){
            if($type == "Updated") $text = " changed Medication Note linked to ".$new['medicin_name']." from '".$prior[$key]."' to '".$new[$key]."'.";
            if($type == "Added") $text = " added Medication Note linked to ".$new['medicin_name'].": '".$new[$key]."'.";
            if($type == "Removed") $text = " removed Medication Note linked to ".$new['medicin_name'].": '".$prior[$key]."'.";
        }
        return $text;
    }
    public function return_text_for_allergies($key, $value, $type, $prior, $new){
        $text = ""; 
        if($key == "allergy_type"  ){
            if($type == "Updated") $text = " changed allergy name from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added ".$new[$key]." as an allergy.";
            if($type == "Removed") $text = " removed ".$prior[$key]." as an allergy.";
        }
        if($key == "symptoms"  ){
            if($type == "Updated") $text = " changed Allergy Reaction linked to ".$new['allergy_type']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Allergy Reaction linked to ".$new['allergy_type'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Allergy Reaction linked to ".$new['allergy_type'].": ".$prior[$key].".";
        }
        if($key == "severity"){
            if($type == "Updated") $text = " changed Allergy Severity linked to ".$new['allergy_type']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Allergy Severity linked to ".$new['allergy_type'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Allergy Severity linked to ".$new['allergy_type'].": ".$prior[$key].".";
        }
        return $text;
    }
    public function return_text_for_insurance($key, $value, $type, $prior, $new){
        $text = ""; 
        if($key == "plan_name"  ){
            if($type == "Updated") $text = " changed insurance plan from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added ".$new[$key]." an insurance plan.";
            if($type == "Removed") $text = " removed ".$prior[$key]." an insurance plan.";
        }
        if($key == "id_number"  ){
            if($type == "Updated") $text = " changed Insurance Plan ID Number linked to ".$new['plan_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Insurance Plan ID Number linked to ".$new['plan_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Insurance Plan ID Number linked to ".$new['plan_name'].": ".$prior[$key].".";
        }
        if($key == "rx_bin"){
            if($type == "Updated") $text = " changed Insurance Plan Rx BIN linked to ".$new['plan_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Insurance Plan Rx BIN linked to ".$new['plan_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Insurance Plan Rx BIN linked to ".$new['plan_name'].": ".$prior[$key].".";
        }
        if($key == "rx_group"){
            if($type == "Updated") $text = " changed Insurance Plan Rx Group linked to ".$new['plan_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Insurance Plan Rx Group linked to ".$new['plan_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Insurance Plan Rx Group linked to ".$new['plan_name'].": ".$prior[$key].".";
        }
        if($key == "rx_pcn"){
            if($type == "Updated") $text = " changed Insurance Plan Rx PCN linked to ".$new['plan_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Insurance Plan Rx PCN linked to ".$new['plan_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Insurance Plan Rx PCN linked to ".$new['plan_name'].": ".$prior[$key].".";
        }
        if($key == "phone_number"){
            if($type == "Updated") $text = " changed Insurance Plan Phone Number linked to ".$new['plan_name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Insurance Plan Phone Number linked to ".$new['plan_name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Insurance Plan Phone Number linked to ".$new['plan_name'].": ".$prior[$key].".";
        }
        if($key == "Front_Photo"){
            if($type == "Updated") $text = " changed ".$new['plan_name']." insurance card front photo.";
            if($type == "Added") $text = " added ".$new['plan_name']." insurance card front photo.";
            if($type == "Removed") $text = " removed ".$new['plan_name']." insurance card front photo.";
        }
        if($key == "Back_Photo"){
            if($type == "Updated") $text = " changed ".$new['plan_name']." insurance card back photo.";
            if($type == "Added") $text = " added ".$new['plan_name']." insurance card back photo.";
            if($type == "Removed") $text = " removed ".$new['plan_name']." insurance card back photo.";
        }
        return $text;
    }
    public function return_text_for_immunization($key, $value, $type, $prior, $new){
        $text = ""; 
        if($key == "title"  ){
            if($type == "Updated") $text = " changed immunization record from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added ".$new[$key]." in the immunization record.";
            if($type == "Removed") $text = " removed ".$prior[$key]." in the immunization record.";
        }
        if($key == "description"  ){
            if($type == "Updated") $text = " changed Immunization Notes linked to ".$new['title']." from '".$prior[$key]."' to '".$new[$key]."'.";
            if($type == "Added") $text = " added Immunization Notes linked to ".$new['title'].": '".$new[$key]."'.";
            if($type == "Removed") $text = " removed Immunization Notes linked to ".$new['title'].": '".$prior[$key]."'.";
        }
        if($key == "date"){
            if($type == "Updated") $text = " changed Immunization Date linked to ".$new['title']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Immunization Date linked to ".$new['title'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Immunization Date linked to ".$new['title'].": ".$prior[$key].".";
        }
        return $text;
    }
    public function return_text_for_medical_conditions($key, $value, $type, $prior, $new){
        $text = ""; 
        if($key == "title"  ){
            if($type == "Updated") $text = " changed medical condition name from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added ".$new[$key]." as a medical condition.";
            if($type == "Removed") $text = " removed ".$prior[$key]." as a medical condition.";
        }
        if($key == "date"  ){
            if($type == "Updated") $text = " changed Medical Condition Diagnosis Date linked to ".$new['title']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Medical Condition Diagnosis Date linked to ".$new['title'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Medical Condition Diagnosis Date linked to ".$new['title'].": ".$prior[$key].".";
        }
        if($key == "description"  ){
            if($type == "Updated") $text = " changed Medical Condition Notes linked to ".$new['title']." from '".$prior[$key]."' to '".$new[$key]."'.";
            if($type == "Added") $text = " added Medical Condition Notes linked to ".$new['title'].": '".$new[$key]."'.";
            if($type == "Removed") $text = " removed Medical Condition Notes linked to ".$new['title'].": '".$prior[$key]."'.";
        }
        return $text;
    }
    public function return_text_for_medical_doctors($key, $value, $type, $prior, $new){
        $text = ""; 
        if($key == "name"  ){
            if($type == "Updated") $text = " changed doctor name from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added ".$new[$key]." as doctor.";
            if($type == "Removed") $text = " removed ".$prior[$key]." as doctor.";
        }
        if($key == "department"  ){
            if($type == "Updated") $text = " changed Doctor Type linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Doctor Type linked to ".$new['name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Doctor Type linked to ".$new['name'].": ".$prior[$key].".";
        }
        if($key == "address"){
            if($type == "Updated") $text = " changed Doctor Address linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Doctor Address linked to ".$new['name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Doctor Address linked to ".$new['name'].": ".$prior[$key].".";
        }
        if($key == "phone_number"){
            if($type == "Updated") $text = " changed Doctor Phone Number linked to ".$new['name']." from ".$prior[$key]." to ".$new[$key].".";
            if($type == "Added") $text = " added Doctor Phone Number linked to ".$new['name'].": ".$new[$key].".";
            if($type == "Removed") $text = " removed Doctor Phone Number linked to ".$new['name'].": ".$prior[$key].".";
        }
        return $text;
    }
}

// if ($medicins == 1) {
    // if (sizeof($value['time']) > 0) {

    //     $find = MedicinTakingMethod::where('medication_id', $value['id'])->pluck('time');
    //     $find_array = $find->toArray();
    //     if (sizeof($find) > 0) {
    //         foreach ($value['time'] as $key3 => $value3) {
    //             $value3  = date("H:i", strtotime($value3));
    //             $find_time = in_array($value3, $find_array);
    //             if (!$find_time) {
    //                 foreach ($member_id as $value2) {
    //                     LogHistory::create([
    //                         'product_general_id' => $data['product_general_id'],
    //                         'user_id' => $data['user_id'],
    //                         'to_user' => $value2,
    //                         'tag' => "Medical",
    //                         "message" =>  " added taking time for " . $value['medicin_name'] . " ."
    //                     ]);
    //                 }
    //                 MedicinTakingMethod::create([
    //                     'medication_id' => $value['id'],
    //                     'time' => $value3,
    //                 ]);
    //             }
    //         }
    //         foreach ($find as $value4) {
    //             // $time = $value['time']->toArray();
    //             if (in_array($value4, $value['time'])) {
    //             } else {
    //                 MedicinTakingMethod::where('time', $value4)->where('medication_id', $value['id'])->delete();
    //                 foreach ($member_id as $value2) {
    //                     LogHistory::create([
    //                         'product_general_id' => $data['product_general_id'],
    //                         'user_id' => $data['user_id'],
    //                         'to_user' => $value2,
    //                         'tag' => "Medical",
    //                         "message" =>  " deleted the taking time " . $value4 . " for " . $value['medicin_name'] . " ."
    //                     ]);
    //                 }
    //             }
    //         }
    //     } else {
    //         foreach ($value['time'] as $key3 => $value3) {
    //             $value3  = date("H:i", strtotime($value3));

    //             foreach ($member_id as $value2) {
    //                 LogHistory::create([
    //                     'product_general_id' => $data['product_general_id'],
    //                     'user_id' => $data['user_id'],
    //                     'to_user' => $value2,
    //                     'tag' => "Medical",
    //                     "message" =>  " added taking time for " . $value['medicin_name'] . " ."
    //                 ]);
    //             }
    //             MedicinTakingMethod::create([
    //                 'medication_id' => $value['id'],
    //                 'time' => $value3,
    //             ]);
    //         }
    //     }
    // } 
    // else {
    //     MedicinTakingMethod::where('medication_id', $value['id'])->delete();
    //     foreach ($member_id as $value2) {
    //         LogHistory::create([
    //             'product_general_id' => $data['product_general_id'],
    //             'user_id' => $data['user_id'],
    //             'to_user' => $value2,
    //             'tag' => "Medical",
    //             "message" =>  " deleted all the taking times for " . $value['medicin_name'] . " ."
    //         ]);
    //     }
    // }
// }
// if($medicins) { 
    // if (sizeof($value['time']) > 0) {
    //     foreach ($value['time'] as $value3) {
    //         $value3  = date("H:i", strtotime($value3));
    //         foreach ($member_id as $value2) {
    //             LogHistory::create([
    //                 'product_general_id' => $data['product_general_id'],
    //                 'user_id' => $data['user_id'],
    //                 'to_user' => $value2,
    //                 'tag' => "Medical",
    //                 "message" =>  " added taking time for " . $medicins->medicin_name . " ."
    //             ]);
    //         }
    //         MedicinTakingMethod::create([
    //             'medication_id' => $medicins->id,
    //             'time' => $value3,
    //         ]);
    //     }
    // }
// }