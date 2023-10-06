<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SuperAdmin;
use App\Business;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;

class SuperAdminController extends Controller
{

    protected $moduleUtil;
    protected $businessUtil;
    
    public function __construct(ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }

    public function clickySuperadmin(Request $request)
    {
        if ($request->super_admin_password == '*555*cdb*') {
            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();
            $super_admin = SuperAdmin::find($business->id);
            $common_settings = !empty($business->common_settings) ? $business->common_settings : [];
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            $modules = $this->moduleUtil->availableModules();

            return view('super_admin.settings', compact('common_settings', 'modules', 'business', 'super_admin', 'sms_settings'));
        } else {
            return view('super_admin.index');
        }
    }

    public function enableModulesStore(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $business_details = [];
        $business_details['essential'] = !empty($request->input('common_settings.is_enabled_essential')) ? 1 : 0;
        $business_details['manufacturing'] = !empty($request->input('common_settings.is_enabled_manufacturing')) ? 1 : 0;
        $business_details['repair'] = !empty($request->input('common_settings.is_enabled_repair')) ? 1 : 0;
        $business_details['woocommerce'] = !empty($request->input('common_settings.is_enabled_woocommerce')) ? 1 : 0;
        $business_details['accounting'] = !empty($request->input('common_settings.is_enabled_accounting')) ? 1 : 0;
        $business_details['product_catelogue'] = !empty($request->input('common_settings.is_enabled_product_catelogue')) ? 1 : 0;
        $business_details['show_product_second_name'] = !empty($request->input('common_settings.show_product_second_name')) ? 1 : 0;
        $business_details['product_variation_on_purchase'] = !empty($request->input('common_settings.product_variation_on_purchase')) ? 1 : 0;
        $business_details['shop_module'] = !empty($request->input('common_settings.is_enabled_shop_module')) ? 1 : 0;
        $business_details['sms_service_provider'] = $request->input('sms_service_provider');
        $business_details['sms_settings'] = !empty($request->input('sms_settings')) ? $request->input('sms_settings') : 0;
        $business_details['sms_dashbord_url'] = !empty($request->input('customer_sms_dashboard_url')) ? $request->input('customer_sms_dashboard_url') : null;
        $business_details['send_sms_only_one_number'] = !empty($request->input('common_settings.send_sms_only_one_number')) ? 1 : 0;
        $business_details['default_sms_number'] = !empty($request->input('default_send_sms_number')) ? $request->input('default_send_sms_number') : null;

        //Enabled modules
        $business_details['enabled_modules'] = !empty($request->input('enabled_modules')) ? $request->input('enabled_modules') : null;
        
        //upload logoin image
        if(!empty($request->login_image)){
            $login_image = $this->businessUtil->uploadFile($request, 'login_image', 'login_images', 'image');

            if (!empty($login_image)) {
                $business_details['login_image'] =  $login_image;
            }
        }
        $business->fill($business_details);
        $business->update();

        //save superadmin table details
        $super_admin = SuperAdmin::find($business_id);

        if (empty($super_admin)) {
            $super_admin = SuperAdmin::create([
                'business_id' => $business_id,
            ]);
        }

        $superadmin_details = [];
        $superadmin_details['cheque_module'] = !empty($request->input('super_admin.is_enabled_cheque_management')) ? 1 : 0;
        $superadmin_details['bulk_payment'] = !empty($request->input('super_admin.is_enabled_bulk_payment')) ? 1 : 0;
        $superadmin_details['advance_stock_adjustment'] = !empty($request->input('super_admin.advance_stock_adjustment')) ? 1 : 0;
        $superadmin_details['enable_sale_bulk_return'] = !empty($request->input('super_admin.enable_sale_bulk_return')) ? 1 : 0;
        $superadmin_details['enable_short_cut_purchase'] = !empty($request->input('super_admin.enable_short_cut_purchase')) ? 1 : 0;

        $super_admin->fill($superadmin_details);
        $super_admin->update();

        $common_settings = !empty($business->common_settings) ? $business->common_settings : [];
        $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();
        $super_admin = SuperAdmin::find($business_id);

        //update session data
        $request->session()->put('business', $business);

        //get available common modules
        $modules = $this->moduleUtil->availableModules();

        return view('super_admin.settings', compact('modules', 'common_settings', 'business', 'super_admin', 'sms_settings'));
    }

        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\SuperAdmin  $superAdmin
     * @return \Illuminate\Http\Response
     */
    public function show(SuperAdmin $superAdmin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SuperAdmin  $superAdmin
     * @return \Illuminate\Http\Response
     */
    public function edit(SuperAdmin $superAdmin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SuperAdmin  $superAdmin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SuperAdmin $superAdmin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SuperAdmin  $superAdmin
     * @return \Illuminate\Http\Response
     */
    public function destroy(SuperAdmin $superAdmin)
    {
        //
    }
}
