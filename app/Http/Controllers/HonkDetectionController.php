<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class HonkDetectionController extends Controller
{
    protected function getSpeedViolationsImagesPath(): string
    {
        $path = config('services.speed_violations_path');
        if ($path && is_dir($path)) {
            return $path;
        }
        $default = base_path('yolov8-multiple-vehicle-detection/speed_violations');
        return is_dir($default) ? $default : storage_path('app/public/violation_images');
    }

    /**
     * Parse YOLOv8 speed violation filename: 20260129_103334_xxx_frame138_car_30_left_104p4kmh_live.jpg
     * Returns date (Y-m-d), time (H:i:s), speed (e.g. "104.4 km/h"), and direction/vehicle if parseable.
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
        $events = [];

        $dir = storage_path('app/public/violation_images');
        if (is_dir($dir)) {
            $files = glob($dir . DIRECTORY_SEPARATOR . 'horn_event_*.json');
            rsort($files);
            $files = array_slice($files, 0, 20);

            foreach ($files as $file) {
                $json = @file_get_contents($file);
                if ($json === false) {
                    continue;
                }
                $data = json_decode($json, true);
                if (!is_array($data)) {
                    continue;
                }

                // Only keep events with decibel_level >= 30 (best 30+)
                if (isset($data['decibel_level']) && is_numeric($data['decibel_level'])) {
                    if ((float) $data['decibel_level'] < 30.0) {
                        continue;
                    }
                } else {
                    // if no decibel_level, skip the event
                    continue;
                }

                $data['_filename'] = basename($file);
                $events[] = $data;
            }
        }

        // Speed violation images from YOLOv8 (cars who violated)
        $speedViolationImages = [];
        $speedDir = $this->getSpeedViolationsImagesPath();
        if (is_dir($speedDir)) {
            $imageFiles = File::files($speedDir);
            foreach ($imageFiles as $file) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $file->getFilename();
                    $details = $this->parseSpeedViolationFilename($filename);
                    $speedViolationImages[] = [
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
            usort($speedViolationImages, fn ($a, $b) => strcmp($b['filename'], $a['filename']));
        }

        return view('honk-detection', [
            'events' => $events,
            'speedViolationImages' => $speedViolationImages,
        ]);
    }
}

