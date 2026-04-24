<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Requests\SettingFormRequest;

class SettingController extends Controller
{

    public function index(Request $request) : View
    {
        $filterByMembershipFee = $request->input('membership_fee');

        $query = Setting::query();

        if ($filterByMembershipFee !== null && $filterByMembershipFee !== '') {
            $query->where('membership_fee', $filterByMembershipFee);
        }

        $settings = $query->paginate(20);

        return view('settings.index', [
            'settings' => $settings,
            'filterByMembershipFee' => $filterByMembershipFee,
        ]);
    }

    public function create()
    {

        return view('Settings.create');
    }

    public function store(SettingFormRequest $request)
    {

        $newSetting = Setting::create($request->validated());

        $url = route('settings.show', ['setting' => $newSetting]);
        $htmlMessage = "Setting <a href='$url'><strong>{$newSetting->id}</strong>
                    - </a> New Setting has been created successfully!";
        return redirect()->route('settings.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', $htmlMessage);
    }

    public function show(Setting $setting)
    {

        return view('Settings.show', compact('setting'));
    }

    public function edit(Setting $setting)
    {

        return view('Settings.edit', compact('setting'));
    }

    public function update(SettingFormRequest $request, Setting $setting)
    {
        $setting->update($request->validated());

        return redirect()->route('settings.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Setting updated successfully!');
    }

    public function destroy(Setting $setting)
    {
        $setting->delete();

        return redirect()->route('settings.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Setting deleted successfully!');
    }

}
