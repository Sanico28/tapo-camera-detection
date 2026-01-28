<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CameraController extends Controller
{
    /**
     * Show the Tapo C310 camera page.
     */
    public function index()
    {
        $cameraIp = config('services.tapo.ip');

        return view('camera', [
            'cameraIp' => $cameraIp,
        ]);
    }
}

