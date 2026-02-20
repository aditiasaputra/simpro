<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Equipment
            ['name' => 'Laptop Dell XPS 15', 'type' => 'equipment', 'description' => 'Laptop kerja performa tinggi dengan layar 15 inch'],
            ['name' => 'Monitor LG 27 inch', 'type' => 'equipment', 'description' => 'Monitor IPS Full HD untuk workstation'],
            ['name' => 'Keyboard Mechanical Logitech', 'type' => 'equipment', 'description' => 'Keyboard mekanikal dengan switch blue'],
            ['name' => 'Mouse Wireless Logitech MX', 'type' => 'equipment', 'description' => 'Mouse nirkabel ergonomis untuk produktivitas'],
            ['name' => 'Headset Sony WH-1000XM5', 'type' => 'equipment', 'description' => 'Headset noise-cancelling premium'],
            ['name' => 'Webcam Logitech C920', 'type' => 'equipment', 'description' => 'Webcam HD 1080p untuk video conference'],
            ['name' => 'Printer Canon PIXMA', 'type' => 'equipment', 'description' => 'Printer inkjet multifungsi warna'],
            ['name' => 'UPS APC 650VA', 'type' => 'equipment', 'description' => 'Uninterruptible power supply untuk komputer'],
            ['name' => 'Hard Disk Eksternal 1TB', 'type' => 'equipment', 'description' => 'Penyimpanan eksternal portabel 1 Terabyte'],
            ['name' => 'Switch Hub 8 Port', 'type' => 'equipment', 'description' => 'Network switch untuk koneksi LAN kantor'],

            // Consumable
            ['name' => 'Kertas HVS A4 80gsm', 'type' => 'consumable', 'description' => 'Kertas cetak ukuran A4 satu rim (500 lembar)'],
            ['name' => 'Tinta Printer Canon Black', 'type' => 'consumable', 'description' => 'Kartrid tinta hitam original untuk Canon PIXMA'],
            ['name' => 'Tinta Printer Canon Color', 'type' => 'consumable', 'description' => 'Kartrid tinta warna original untuk Canon PIXMA'],
            ['name' => 'Spidol Whiteboard Hitam', 'type' => 'consumable', 'description' => 'Spidol untuk papan tulis whiteboard'],
            ['name' => 'Ballpoint Pilot G2', 'type' => 'consumable', 'description' => 'Pulpen gel hitam isi ulang'],
            ['name' => 'Sticky Note 3x3 inch', 'type' => 'consumable', 'description' => 'Kertas tempel warna-warni isi 100 lembar'],
            ['name' => 'Staples No. 10', 'type' => 'consumable', 'description' => 'Isi staples ukuran kecil untuk stepler standar'],
            ['name' => 'Map Plastik Transparan', 'type' => 'consumable', 'description' => 'Plastik pelindung dokumen ukuran A4'],
            ['name' => 'Amplop Coklat A4', 'type' => 'consumable', 'description' => 'Amplop coklat ukuran folio isi 50 pcs'],
            ['name' => 'Baterai AA Alkaline', 'type' => 'consumable', 'description' => 'Baterai AA untuk perangkat wireless, isi 4 pcs'],
        ];

        Item::insert($items);
    }
}
