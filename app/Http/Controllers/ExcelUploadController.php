<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ExcelUploadController extends Controller
{
    public function index(){
        $images = $this->getImages();

        return view('image_list',compact('images'));
    }

    public function write(Request $request)
    {
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls|max:2048',
        ]);

        $uploadedFile = $request->file('excelFile');
        $filePath = $uploadedFile->storeAs('uploads', 'uploaded-file.xlsx', 'public');

        $fullFilePath = storage_path('app/public/' . $filePath);

        // Pastikan folder storage/app/public/image ada
        $imageFolder = storage_path('app/public/image');
        if (!file_exists($imageFolder)) {
            mkdir($imageFolder, 0777, true); // Membuat folder jika belum ada
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $file = $reader->load($fullFilePath);
        $sheet = $file->getActiveSheet();
        $drawings = $sheet->getDrawingCollection();

        foreach ($drawings as $drawing) {
            if (!$drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                $coordinate = $drawing->getCoordinates();
                $itemPath = $drawing->getPath();
                $extension = pathinfo($itemPath, PATHINFO_EXTENSION);
                $name_file = Str::random();
                $img_url = "/storage/image/{$name_file}.{$extension}";
                $img_path = storage_path("app/public/image/{$name_file}.{$extension}");

                $originalImage = imagecreatefromstring(file_get_contents($itemPath));

                $width = $drawing->getWidth();
                $height = $drawing->getHeight();

                $croppedImage = imagecreatetruecolor($width, $height);
                imagesavealpha($croppedImage, true);
                $transparent = imagecolorallocatealpha($croppedImage, 0, 0, 0, 127);
                imagefill($croppedImage, 0, 0, $transparent);

                $rotation = $drawing->getRotation();
                $description = $drawing->getDescription();
                $name = $drawing->getName();

                $shape = $this->detectShapeFromProperties($name, $description);

                $mask = $this->createShapeMask($shape, $width, $height, $rotation);

                imagecopyresampled(
                    $croppedImage,
                    $originalImage,
                    0, 0,
                    $drawing->getOffsetX(),
                    $drawing->getOffsetY(),
                    $width, $height,
                    $width, $height
                );

                // Menyimpan gambar ke storage
                imagepng($croppedImage, $img_path);

                imagedestroy($originalImage);
                imagedestroy($croppedImage);
                imagedestroy($mask);
            }
        }

        return redirect()->back()->with('success', 'Images have been processed and saved.');
    }

    public function downloadImage($filename)
    {
        $filePath = 'public/image/' . $filename;

        if (!Storage::exists($filePath)) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return Storage::download($filePath, $filename);
    }

    private function getImages()
    {
        // Path folder image di storage
        $folderPath = storage_path('app/public/image');

        // Periksa apakah folder ada
        if (!file_exists($folderPath)) {
            return []; // Jika folder tidak ada, kembalikan array kosong
        }

        // Ambil semua file di folder
        $files = scandir($folderPath);

        $images = [];
        foreach ($files as $file) {
            if (is_file($folderPath . '/' . $file)) {
                $images[] = [
                    'src' => "/storage/image/{$file}",
                    'name' => $file,
                ];
            }
        }

        return $images;
    }

    private function detectShapeFromProperties($name, $description)
    {
        // Lowercase semua teks untuk memudahkan pengecekan
        $name = strtolower($name);
        $description = strtolower($description);
        
        // Array kata kunci untuk setiap bentuk
        $shapeKeywords = [
            'hexagon' => ['hexagon', 'segi6', 'segi enam'],
            'circle' => ['circle', 'bulat', 'lingkaran', 'oval'],
            'diamond' => ['diamond', 'belah ketupat', 'wajik'],
            'triangle' => ['triangle', 'segitiga'],
            'star' => ['star', 'bintang']
        ];
        
        // Cek nama dan deskripsi untuk kata kunci
        foreach ($shapeKeywords as $shape => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($name, $keyword) !== false || strpos($description, $keyword) !== false) {
                    return $shape;
                }
            }
        }
        
        return 'rectangle'; // Default shape
    }

    private function createShapeMask($shape, $width, $height, $rotation)
    {
        $mask = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($mask, 255, 255, 255);
        $black = imagecolorallocate($mask, 0, 0, 0);
        imagefilledrectangle($mask, 0, 0, $width, $height, $black);

        $centerX = $width / 2;
        $centerY = $height / 2;
        $size = min($width, $height);
        $radius = $size / 2;

        switch ($shape) {
            case 'hexagon':
                $points = [];
                for ($i = 0; $i < 6; $i++) {
                    $angle = ($i * 60 + $rotation) * M_PI / 180;
                    $points[] = $centerX + $radius * cos($angle);
                    $points[] = $centerY + $radius * sin($angle);
                }
                imagefilledpolygon($mask, $points, 6, $white);
                break;

            case 'circle':
                imagefilledellipse($mask, $centerX, $centerY, $size, $size, $white);
                break;

            case 'diamond':
                $points = [
                    $centerX, $centerY - $radius,             // top
                    $centerX + $radius, $centerY,             // right
                    $centerX, $centerY + $radius,             // bottom
                    $centerX - $radius, $centerY              // left
                ];
                imagefilledpolygon($mask, $points, 4, $white);
                break;

            case 'triangle':
                $points = [
                    $centerX, $centerY - $radius,             // top
                    $centerX + $radius, $centerY + $radius,   // bottom right
                    $centerX - $radius, $centerY + $radius    // bottom left
                ];
                imagefilledpolygon($mask, $points, 3, $white);
                break;

            case 'star':
                $points = [];
                $innerRadius = $radius * 0.4;
                for ($i = 0; $i < 10; $i++) {
                    $angle = ($i * 36 + $rotation) * M_PI / 180;
                    $r = ($i % 2 == 0) ? $radius : $innerRadius;
                    $points[] = $centerX + $r * cos($angle);
                    $points[] = $centerY + $r * sin($angle);
                }
                imagefilledpolygon($mask, $points, 10, $white);
                break;

            default:
                imagefilledrectangle($mask, 0, 0, $width, $height, $white);
        }

        return $mask;
    }

    private function applyMask($image, $mask)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $maskColor = imagecolorsforindex($mask, imagecolorat($mask, $x, $y));
                if ($maskColor['red'] == 0) { // Jika pixel mask hitam
                    $color = imagecolorallocatealpha($image, 0, 0, 0, 127);
                    imagesetpixel($image, $x, $y, $color);
                }
            }
        }
    }
}
