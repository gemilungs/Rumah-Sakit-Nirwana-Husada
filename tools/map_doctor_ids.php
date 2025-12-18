<?php
require_once __DIR__ . "/../php/config.php";
$names = [
    'dr. Alya Prameswari','dr. Amanda Putri','dr. Bayu Wirawan','dr. Rizky Mahendra','dr. Maya Shafira','dr. Naufal Saputro',
    'dr. Salsabila Putriandi','dr. Satria Wijaya','dr. Susilo Hartono','dr. Nadira Maheswari','dr. Yudi Setiawan',
    'dr. Samuel Widodo','dr. Hafizh Ramdhan','dr. Gilang Setiadharma','dr. Tiara Nurcahyani','dr. Yudha Permadi',
    'dr. Kevin Wiratmaja','dr. Kayla Ramadhanti','dr. Raka Pratama','dr. Reza Novriansyah','dr. Intan Dewandari'
];
$db = getConnection();
foreach ($names as $n) {
    $stmt = $db->prepare('SELECT id FROM dokter WHERE nama = ? LIMIT 1');
    $stmt->execute([$n]);
    $r = $stmt->fetch();
    echo $n . ' => ' . ($r ? $r['id'] : 'NOT FOUND') . PHP_EOL;
}
