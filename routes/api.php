<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
    Route::post('register', 'UserController@register');
    Route::post('verifyCode', 'UserController@verifyCode');
    Route::post('upload_register_image', 'DataController@upload_register_image');
    Route::post('forgotPassword', 'UserController@forgotPassword');
    Route::post('verifyPasswordResetCode', 'UserController@verifyPasswordResetCode');
    Route::post('resetPassword', 'UserController@resetPassword');
    Route::post('login', 'UserController@authenticate');
    Route::get('getUser', 'UserController@getUser');
    Route::get('logout', 'UserController@logout');

    Route::group(['middleware' => ['jwt.verify']], function() {
        Route::get('user', 'UserController@getAuthenticatedUser');
        Route::post('post_case_general', 'DataController@post_case_general');
        Route::post('post_biographical_details', 'DataController@post_biographical_details');
        Route::post('get_case_general_by_id', 'DataController@get_case_general_by_id');
        Route::post('delete_case_general/{id}', 'DataController@delete_case_general');
        Route::post('update_case_general/{id}', 'DataController@update_case_general');

        Route::post('get_more_cases', 'DataController@get_more_cases');
        Route::get('get_all_case_general_by_id/{id}', 'DataController@get_all_case_general_by_id');
        Route::post('check_case_general', 'DataController@check_case_general');
        Route::get('member_of_case/{id}', 'DataController@member_of_case');
        Route::get('member_list_for_placement_people/{id}', 'DataController@member_list_for_placement_people');
        Route::get('search_by_case_name', 'DataController@search_by_case_name');

        Route::post('post_ActivitiesAndInterest', 'DataController@post_ActivitiesAndInterest');
        Route::post('post_case_placement', 'DataController@post_case_placement');
        Route::post('post_Allergy', 'DataController@post_Allergy');
        Route::post('post_Medication_from_medical', 'DataController@post_Medication_from_medical');
        Route::post('post_Doctor_from_medical', 'DataController@post_Doctor_from_medical');
        Route::post('post_dental', 'DataController@post_dental');
        Route::post('post_Therapy', 'DataController@post_Therapy');
        Route::post('post_CaseMember', 'DataController@post_CaseMember');
        Route::post('post_Education', 'DataController@post_Education');
        Route::post('post_Immunization', 'DataController@post_Immunization');
        // Route::post('post_Insurance', 'DataController@post_Insurance');
        // Route::post('post_Kinship', 'DataController@post_Kinship');
        // Route::post('post_Sibling', 'DataController@post_Sibling');  
        Route::post('post_LegalInformation', 'DataController@post_LegalInformation');
        // Route::post('post_case_medical', 'DataController@post_case_medical');
        Route::post('post_case_medical', 'MedicalController@post_case_medical');

        Route::post('post_PhysicalCondition_from_Medical', 'DataController@post_PhysicalCondition_from_Medical');
        Route::post('post_PhysicalCondition_from_Dental', 'DataController@post_PhysicalCondition_from_Dental');

        Route::post('post_Log', 'DataController@post_Log');
        Route::post('post_appointment', 'DataController@post_appointment');
        Route::post('edit_appointment', 'DataController@edit_appointment');
        Route::post('delete_appointment', 'DataController@delete_appointment');
        Route::post('post_medicin_taking_method', 'DataController@post_medicin_taking_method');
        Route::post('post_placement_people', 'DataController@post_placement_people');
        Route::post('post_scheduled_people', 'DataController@post_scheduled_people');
        Route::post('upload_file', 'DataController@upload_file');
        Route::post('case_profile_picture_upload', 'DataController@case_profile_picture_upload');
        Route::post('post_case_documents', 'DataController@post_case_documents');
        Route::post('post_insurance_documents', 'DataController@post_insurance_documents');
        Route::post('post_log_documents', 'DataController@post_log_documents');

        Route::post('notification_settings', 'DataController@notification_settings');
        Route::get('get_notification_settings', 'DataController@get_notification_settings');
        Route::get('notification_check', 'DataController@notification_check');
        Route::post('seen_all_log', 'DataController@seen_all_log');
        // Route::post('log_search', 'DataController@log_search');
        Route::post('filter_log', 'DataController@filter_log');  


        
        Route::post('cancel_invite/{id}', 'DataController@cancel_invite');
        Route::post('send_invitation', 'DataController@send_invitation');
        Route::post('get_biographical_details', 'DataController@get_biographical_details');
        Route::post('get_medical_details', 'DataController@get_medical_details');
        Route::get('get_dental_details/{id}', 'DataController@get_dental_details');
        Route::get('get_therapy_details/{id}', 'DataController@get_therapy_details');
        Route::get('get_education_details/{id}', 'DataController@get_education_details');
        Route::post('get_legal_details', 'DataController@get_legal_details');
        Route::post('get_all_Logs', 'DataController@get_all_Logs');
        Route::get('get_all_members/{id}', 'DataController@get_all_members');
        Route::get('get_all_appointments/{id}', 'DataController@get_all_appointments');
        Route::get('get_appointment_details/{id}', 'DataController@get_appointment_details');



        Route::get('get_all_user', 'DataController@get_all_user');
        Route::get('get_case_general', 'DataController@get_case_general');
        Route::get('get_all_medicins', 'DataController@get_all_medicins');
        Route::get('get_all_doctors', 'DataController@get_all_doctors');
// filter, profile, profile update, sending request to user for join a case , 
// 
// show apointmentList, 
// notification,notification settings, (post_case_member, successfull_removals),
// chat, appointment documents, leave case, remove case
// edit, delete
// emails, 
// New Case Invite” 
        Route::post('accepting_case_invite', 'DataController@accepting_case_invite');
        Route::post('rejecting_case_invite', 'DataController@rejecting_case_invite');
        Route::post('joining_a_case', 'DataController@joining_a_case');
        Route::post('accept_pending_add', 'DataController@accept_pending_add');
        Route::post('reject_pending_add', 'DataController@reject_pending_add');
        Route::post('cancel_removal', 'DataController@cancel_removal');
        Route::post('accept_close', 'DataController@accept_close');
        Route::post('remove_from_case', 'DataController@remove_from_case');
        Route::post('cancel_removal', 'DataController@cancel_removal');
        Route::post('cancel_close', 'DataController@cancel_close');
        Route::post('accept_removal', 'DataController@accept_removal');
        Route::post('reject_close', 'DataController@reject_close');
        Route::post('reject_removal', 'DataController@reject_removal');
        Route::post('get_by_filter', 'DataController@get_by_filter');
        Route::post('get_requested_member_list', 'DataController@get_requested_member_list');
        Route::post('withdrawRequest', 'DataController@withdrawRequest');
        Route::get('get_user_profile/{id}', 'DataController@get_user_profile');
        Route::post('profile_update', 'DataController@profile_update');
        Route::post('profile_picture_update', 'DataController@profile_picture_update');
        Route::post('changePassword', 'UserController@changePassword');
        Route::post('sending_email_for_join', 'DataController@sending_email_for_join');
        Route::post('close_case', 'DataController@close_case');
        Route::post('leave_case', 'DataController@leave_case');

        Route::post('notification_settings_update', 'DataController@notification_settings_update');
        Route::post('chat_search', 'DataController@chat_search');
        Route::post('search_result', 'DataController@search_result');
        Route::post('conversation_msg', 'DataController@conversation_msg');
        Route::post('msg_insert', 'DataController@msg_insert');
        Route::post('more_chat', 'DataController@more_chat');
        Route::post('more_log', 'DataController@more_log');

        Route::get('closed', 'DataController@closed');
        
        Route::post('find_the_diffrence', 'DataController@find_the_diffrence');

    });
// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
