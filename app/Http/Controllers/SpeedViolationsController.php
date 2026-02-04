<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpeedViolationsController extends Controller
{
    protected function getImagesPath(): string
    {
        $path = config('services.speed_violations_path');
        if ($path && is_dir($path)) {
            return realpath($path) ?: $path;
        }
        $default = base_path('yolov8-multiple-vehicle-detection/speed_violations');
        if (is_dir($default)) {
            return realpath($default) ?: $default;
        }
        return storage_path('app/public/violation_images');
    }

    /**
     * Parse YOLOv8 speed violation filename: 20260129_103334_xxx_frame138_car_30_left_104p4kmh_live.jpg
     */
    protected function parseSpeedViolationFilename(string $filename): array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $date = null;
        $time = null;
        $speed = null;
        $vehicle = null;
        $direction = null;

        if (preg_match('/^(\d{8})_(\d{6})/', $base, $m)) {
            $date = substr($m[1], 0, 4) . '-' . substr($m[1], 4, 2) . '-' . substr($m[1], 6, 2);
            $time = substr($m[2], 0, 2) . ':' . substr($m[2], 2, 2) . ':' . substr($m[2], 4, 2);
        }
        if (preg_match('/(\d+)p(\d+)kmh/', $base, $m)) {
            $speed = $m[1] . '.' . $m[2] . ' km/h';
        }
        if (preg_match('/_(car|truck|bus)_\d+_(left|right)_/i', $base, $m)) {
            $vehicle = $m[1];
            $direction = $m[2];
        }

        return [
            'date' => $date,
            'time' => $time,
            'speed' => $speed,
            'vehicle' => $vehicle,
            'direction' => $direction,
        ];
    }

    public function index()
    {
        $dir = $this->getImagesPath();
        $images = [];

        if (is_dir($dir)) {
            $files = File::files($dir);
            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $file->getFilename();
                    $details = $this->parseSpeedViolationFilename($filename);
                    $images[] = [
                        'filename' => $filename,
                        'url' => url(route('speed.image', ['filename' => $filename])),
                        'date' => $details['date'],
                        'time' => $details['time'],
                        'speed' => $details['speed'],
                        'vehicle' => $details['vehicle'],
                        'direction' => $details['direction'],
                    ];
                }
            }
            usort($images, fn ($a, $b) => strcmp($b['filename'], $a['filename']));
        }

        return view('speed-violations', [
            'images' => $images,
        ]);
    }

    public function image(string $filename)
    {
        $filename = basename($filename);
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            abort(404);
        }

        $dir = $this->getImagesPath();
        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (! is_file($path) || ! is_readable($path)) {
            abort(404);
        }

        $mime = match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return response()->file($path, [
            'Content-Type' => $mime,
        ]);
    }
}
