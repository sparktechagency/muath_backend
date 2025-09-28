<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function bannerUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480', // Image validation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $data = Banner::find(1);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        if ($request->hasFile('banner')) {
            if ($data->banner && Storage::disk('public')->exists(str_replace('/storage/', '', $data->banner))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $data->banner));
            }

            $path = $request->banner->store('banners', 'public');
            $data->banner = '/storage/' . $path;
        }

        $data->save();

        return response()->json([
            'message' => 'Banner ' . ($data->wasRecentlyCreated ? 'created' : 'updated') . ' successfully',
            'banner' => $data
        ], 200);
    }
    public function getBanner()
    {
        $data = Banner::where('id', 1)->first();
        $data->banner = asset($data->banner);
        return $this->sendResponse($data, 'Get banner.');
    }
}
