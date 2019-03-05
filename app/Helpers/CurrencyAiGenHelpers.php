<?php

namespace App\Helpers;

class CurrencyAigenHelpers
{
    public static function index($data){
        $pop_size = (int)env('POP_SIZE');
        $gen = (int)env('JUMLAH_GEN');
        $listDataExcel = self::olahData($data);
        // print_r($listDataExcel);
        $dataPopulasi = self::generateIndividu($pop_size, $gen);
        $evaluasi = self::evaluasi($dataPopulasi, $listDataExcel, $pop_size, 0);
    }

    public static function olahData($data){
        $listData = array();
        if (isset($data[0])){
            for ($i=0; $i<=6; $i++){
                array_push($listData, $data[0][$i][5]);
            }
        }
        return $listData;
    }

    public static function evaluasi($dataPopulasi, $dataExcel, $jumlahIndividu, $generasi){
        // echo "Generasi ke - ".$generasi." <br>";
        
        $threshold = (float)env('THRESHOLD');
        $listFitness = array();
        $terpenuhi = false;
        $index = 0;

        for ($i=0; $i<$jumlahIndividu; $i++){
            $predict_6 = $dataPopulasi[$i][0] + $dataPopulasi[$i][1]*$dataExcel[0] + $dataPopulasi[$i][2]*$dataExcel[1] + $dataPopulasi[$i][3]*$dataExcel[2] + $dataPopulasi[$i][4]*$dataExcel[3] + $dataPopulasi[$i][5]*$dataExcel[4];
            $predict_7 = $dataPopulasi[$i][0] + $dataPopulasi[$i][1]*$dataExcel[0] + $dataPopulasi[$i][2]*$dataExcel[2] + $dataPopulasi[$i][3]*$dataExcel[3] + $dataPopulasi[$i][4]*$dataExcel[4] + $dataPopulasi[$i][5]*$dataExcel[5];

            $fx = 1/2*(abs($dataExcel[5] - $predict_6) + abs($dataExcel[6] - $predict_7));
            $Fx = 1/($fx+1);
            array_push($listFitness, $Fx);
        }

        // if ($generasi == 6){
        //     echo "<br> Nilai Fitness : ";
        //     print_r($listFitness);
        //     echo "<br>";
        //     echo "<br> Nilai Individu : ";
        //     print_r($dataPopulasi);
        //     echo "<br>";
        //     dd("exit");
        // }

        foreach ($listFitness as $list){
            if ($list > $threshold){
                $terpenuhi = true;
                echo "Generasi ke - ".$generasi." <br>";
                echo "<br> Fitness Mencukupi : ".$list."<br>";
                echo "<br> Data Populasi : <br>" ;
                print_r($dataPopulasi[$index]);
                echo "<br> Data Excel : <br>" ;
                print_r($dataExcel);
                dd("sukses");
            }
            $index++;
        }

        if ($terpenuhi == false){
            // print_r($dataPopulasi);
            self::seleksi($listFitness, $dataPopulasi, $generasi);
        }
        
        return $listFitness;
    }

    public static function seleksi($listFitness, $dataPopulasi, $generasi){
        $jumlahSeleksi = (int)env('JUMLAH_SELEKSI'); // 80% dari jumlah populasi
        $jumlahFitness = array_sum($listFitness);
        $probabilityFitnessList = array();
        $mergeFitnessAndIndividu = array();
        $individuHasilSeleksi = array();
        
        foreach ($listFitness as $list){
            $probability = $list/$jumlahFitness;
            array_push($probabilityFitnessList, $probability);
        }
        
        /** Mapping FitnessList dan Index Individu */
        for ($i=0; $i<count($dataPopulasi); $i++){
            $mergeFitnessAndIndividu[] = [
                'fitness' => $probabilityFitnessList[$i],
                'index_individu' => $i, 
                'nilai_gen' => $dataPopulasi[$i]
            ];
        }

        /** Mengurutkan sesuai nilai fitness terkecil ke terbesar */
        usort($mergeFitnessAndIndividu, function ($a, $b) {
            return $a['fitness'] <=> $b['fitness'];
        });

        /** Menyeleksi 80% dari 7 individu */
        while (count($individuHasilSeleksi) < (int)env('POP_SIZE')) {
            $randomValue = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
            foreach ($mergeFitnessAndIndividu as $listData){
                if ($listData['fitness'] > $randomValue){
                    array_push($individuHasilSeleksi, $listData);
                }
                if (count($individuHasilSeleksi) >= (int)env('POP_SIZE')){
                    break;
                }
            }
        }
        self::crossover($individuHasilSeleksi, $generasi);
    }

    
    public static function crossover($data, $generasi){
        $alfa = (float)env("NILAI_ALFA");
        $randomIndex = rand(0,5);
        $jumlahIndividuPopulasi = count($data);
        $parent = $data;
        $anak = $data;
        $a = 0;
        $b = 1;

        for ($i=0; $i<$jumlahIndividuPopulasi/2; $i++){
            $anak[$a]['nilai_gen'][$randomIndex] = ($alfa*$anak[$b]['nilai_gen'][$randomIndex])+(1-$alfa)*$anak[$a]['nilai_gen'][$randomIndex];
            $anak[$b]['nilai_gen'][$randomIndex] = ($alfa*$anak[$a]['nilai_gen'][$randomIndex])+(1-$alfa)*$anak[$b]['nilai_gen'][$randomIndex];
            
            $a = $a + 2;
            $b = $b + 2;
        }

        self::mutasi($parent, $anak, $generasi);

    }
    
    public static function mutasi($parent, $anak, $generasi){
        $individuPopulasiAnak = $anak;
        $jumlahIndividuPopulasiAnak = count($individuPopulasiAnak);
        $jumlahGen = (int)env('JUMLAH_GEN');
        $seleksi = array();
        $dataPopulasi = array();

        // foreach ($data as $list){
        for ($i=0; $i<$jumlahIndividuPopulasiAnak; $i++){
            $randomIndex = rand(0,5);
            $randomValue = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
            $individuPopulasiAnak[$i]['nilai_gen'][$randomIndex] = $randomValue;
        }
        
        $parentDanAnak = array_merge($parent, $individuPopulasiAnak);
        $randomIndex = array_rand($parentDanAnak, (int)env("POP_SIZE"));
        for ($i=0; $i < (int)env("POP_SIZE"); $i++){
            array_push($seleksi, $parentDanAnak[$randomIndex[$i]]);
        }

        for ($i=0; $i < (int)env("POP_SIZE"); $i++){
            array_push($dataPopulasi, $seleksi[$i]['nilai_gen']);
        }
        self::evaluasi($dataPopulasi, session()->get('listData'), (int)env('POP_SIZE'), $generasi+1);
        return $seleksi;
    }

    public static function generateIndividu($jumlahIndividu, $jumlahGen){
        for ($i=1; $i<=$jumlahIndividu; $i++){
            for ($j=1; $j<=$jumlahGen; $j++){
                $randomValue = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
                $individu[$i-1][$j-1] = $randomValue;
            }
        }
        return $individu;
    }
}
