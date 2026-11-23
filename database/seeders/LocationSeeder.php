<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Malaysian states and their administrative districts (daerah).
 * Perlis and the Federal Territories have no districts, so each is
 * listed once under its own name.
 */
class LocationSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    public const DISTRICTS = [
        'Johor' => [
            'Batu Pahat', 'Johor Bahru', 'Kluang', 'Kota Tinggi', 'Kulai',
            'Mersing', 'Muar', 'Pontian', 'Segamat', 'Tangkak',
        ],
        'Kedah' => [
            'Baling', 'Bandar Baharu', 'Kota Setar', 'Kuala Muda', 'Kubang Pasu',
            'Kulim', 'Langkawi', 'Padang Terap', 'Pendang', 'Pokok Sena', 'Sik', 'Yan',
        ],
        'Kelantan' => [
            'Bachok', 'Gua Musang', 'Jeli', 'Kota Bharu', 'Kuala Krai',
            'Machang', 'Pasir Mas', 'Pasir Puteh', 'Tanah Merah', 'Tumpat',
        ],
        'Melaka' => [
            'Alor Gajah', 'Jasin', 'Melaka Tengah',
        ],
        'Negeri Sembilan' => [
            'Jelebu', 'Jempol', 'Kuala Pilah', 'Port Dickson', 'Rembau', 'Seremban', 'Tampin',
        ],
        'Pahang' => [
            'Bentong', 'Bera', 'Cameron Highlands', 'Jerantut', 'Kuantan', 'Lipis',
            'Maran', 'Pekan', 'Raub', 'Rompin', 'Temerloh',
        ],
        'Perak' => [
            'Bagan Datuk', 'Batang Padang', 'Hilir Perak', 'Hulu Perak', 'Kampar', 'Kerian',
            'Kinta', 'Kuala Kangsar', 'Larut, Matang dan Selama', 'Manjung', 'Muallim', 'Perak Tengah',
        ],
        'Perlis' => [
            'Perlis',
        ],
        'Pulau Pinang' => [
            'Barat Daya', 'Seberang Perai Selatan', 'Seberang Perai Tengah',
            'Seberang Perai Utara', 'Timur Laut',
        ],
        'Sabah' => [
            'Beaufort', 'Beluran', 'Kalabakan', 'Keningau', 'Kinabatangan', 'Kota Belud',
            'Kota Kinabalu', 'Kota Marudu', 'Kuala Penyu', 'Kudat', 'Kunak', 'Lahad Datu',
            'Nabawan', 'Papar', 'Penampang', 'Pitas', 'Putatan', 'Ranau', 'Sandakan',
            'Semporna', 'Sipitang', 'Tambunan', 'Tawau', 'Telupid', 'Tenom', 'Tongod', 'Tuaran',
        ],
        'Sarawak' => [
            'Asajaya', 'Bau', 'Belaga', 'Beluru', 'Betong', 'Bintulu', 'Bukit Mabong',
            'Dalat', 'Daro', 'Julau', 'Kabong', 'Kanowit', 'Kapit', 'Kuching', 'Lawas',
            'Limbang', 'Lubok Antu', 'Lundu', 'Marudi', 'Matu', 'Meradong', 'Miri', 'Mukah',
            'Pakan', 'Pusa', 'Samarahan', 'Saratok', 'Sarikei', 'Sebauh', 'Sebuyau', 'Selangau',
            'Serian', 'Sibu', 'Simunjan', 'Song', 'Sri Aman', 'Subis', 'Tanjung Manis',
            'Tatau', 'Tebedu', 'Telang Usan',
        ],
        'Selangor' => [
            'Gombak', 'Hulu Langat', 'Hulu Selangor', 'Klang', 'Kuala Langat',
            'Kuala Selangor', 'Petaling', 'Sabak Bernam', 'Sepang',
        ],
        'Terengganu' => [
            'Besut', 'Dungun', 'Hulu Terengganu', 'Kemaman', 'Kuala Nerus',
            'Kuala Terengganu', 'Marang', 'Setiu',
        ],
        'W.P. Kuala Lumpur' => [
            'W.P. Kuala Lumpur',
        ],
        'W.P. Labuan' => [
            'W.P. Labuan',
        ],
        'W.P. Putrajaya' => [
            'W.P. Putrajaya',
        ],
    ];

    public function run(): void
    {
        foreach (self::DISTRICTS as $state => $districts) {
            foreach ($districts as $district) {
                Location::firstOrCreate(['state' => $state, 'district' => $district]);
            }
        }
    }
}
