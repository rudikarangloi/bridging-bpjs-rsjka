<?php

namespace App\Models;

use CodeIgniter\Model;

class Api_model extends Model{

    protected $table               = "data_antrian";
    protected $tableDetail         = "data_antrian_detail";
    protected $tableClient         = "client_antrian";
    protected $tableBiodata        = "tb_biodata";
    protected $tablekunjungan      = "tb_tr_kunjungan";
    protected $tablekunjunganrinci = "tb_tr_kunjungan_rinci";
	protected $tableFarmasi 	   = "data_antrian_apotik";

    
    protected $tableRefDokter      = "ref_dokter";

 
    public function register($data,$tableName) {
        $query = $this->db->table($tableName)->insert($data);
        return $query ? true : false;
    }

    
    public function updateTable($field,$dataField,$fieldFiltered,$id,$waktu,$tableName){
        
        $data = array(
                    $field => $dataField,
                    'checkIn' => $waktu
                );              
                    
        $query = $this->db->table($tableName)
                    ->where($fieldFiltered, $id)
                    ->update($data);        
    
        return $query ? true : false;
    }

    public function hapus($fieldFiltered,$id,$tableName){               
                    
        $query = $this->db->table($tableName)
                    ->where($fieldFiltered, $id)
                    ->delete();     
    
        return $query ? true : false;
    }
    
    //get all column on one row
    public function get_alldata($kode,$keyField,$tableName) {                 
       
        $conditions = " $keyField = '$kode' "; 
        
        $hasil = $this->db->table($tableName)    
                ->select(' * ')              
                ->where($conditions)               
                ->get()
                ->getRowArray(); 

        return $hasil;
    }

    //get One column on one row
    public function get_OneColumn($var,$fieldName,$fieldNameSelect,$operator,$tableName) {                 
       
        $conditions = " $fieldName $operator '$var' "; 
            
        //$hasil = $this->table($this->tableBiodata)   => Nama table tidak flexible, harus di deklarasikan
        $hasil = $this->db->table($tableName)     
                ->select($fieldNameSelect)              
                ->where($conditions)               
                ->get()
                ->getRowArray(); 

        return $hasil;
    }
   
    public function get_biodata($nomorkartubpjs,$nik,$tableName) {                 
       
        $conditions = " No_KTP = '$nik' OR no_kartu = '$nomorkartubpjs' "; 
        //$conditions = array('No_KTP' => $nik, 'no_kartu' => $nomorkartubpjs); 
        
        $hasil = $this->db->table($tableName) 
                ->select(' * ')              
                ->where($conditions)               
                ->get()
                ->getRowArray(); 

        return $hasil;
    }

    public function getLastNoRekMed() {     
        
        $computeQuery = " max(norekmed) as count ";
        
        $hasil = $this->db->table($this->tableBiodata)  
                ->select($computeQuery)                  
                ->get()
                ->getRowArray();         

       
        return $hasil;
    }

    public function get_data($conditions,$fieldNameSelect,$tableName) {                 
              
        //SELECT * FROM ref_dokter WHERE kodedokter ='22222' AND HOUR('08:00-16:00') >= HOUR(jampraktek)  AND HOUR('08:00-16:00') < HOUR(jamprakteksampai) ;

        $hasil = $this->db->table($tableName) 
                ->select($fieldNameSelect)              
                ->where($conditions)               
                ->get()
                ->getRowArray(); 

        return $hasil;
    }

    
       
    public function getAnyData_Antrians($nomorkartubpjs,$nik,$nomortelp,$nomorreferensi,$jenisreferensi,$jenisantrian,$jenispoli,$kdpoli,$waktu,$tableName) {                 
        $conditions = array(
                            'nomorkartubpjs' => $nomorkartubpjs, 'nik' => $nik, 'nomortelp' => $nomortelp,
                            'nomorreferensi' => $nomorreferensi, 'jenisreferensi' => $jenisreferensi, 'jenisantrian' => $jenisantrian, 
                            'jenispoli' => $jenispoli, 'kdpoli' => $kdpoli
                            );
       
        
            //$hasil = $this->table('data_antrian')
            $hasil = $this->db->table($tableName)     
                ->select('nomor AS nomorantrean,kodebooking,jenisantrian,
                                CAST(TIMEDIFF(CONVERT(waktu, TIME),CONVERT(waktudilayani, TIME)) AS UNSIGNED)  AS estimasidilayani,
                                (SELECT description FROM client_antrian WHERE client=data_antrian.counter) AS nampoli,
                                , namadokter'
                                )
                ->join('client_antrian', 'data_antrian.counter = client_antrian.client')
                ->where($conditions)           
                ->like('waktu', $waktu, 'after')
                ->get()
                ->getRowArray();         

       
        return $hasil;
    }
    
    public function getCountAntrians($kdpoli,$waktu,$tableName) {  
    
        $conditions = array('counter' => $kdpoli); 
        
        //Pada RSJ Kalawaatei , kode poli JIW semua, maka filter counter di tiadakan
        //$conditions = array('counter'); 
        
        //$hasil = $this->table('data_antrian')
        $hasil = $this->db->table($tableName)
                ->select('count(*) as count')                
                ->where($conditions)           
                ->like('waktu', $waktu, 'after')
                ->get()
                ->getRowArray();         

       
        return $hasil;
    }
    
    public function getUsedAntrians($kdpoli,$waktu,$form,$tableName) {  
    
        $conditions = array('counter' => $kdpoli, 'input_from' => $form); 
        
        //Pada RSJ Kalawaatei , kode poli JIW semua, maka filter counter di tiadakan        
        $conditions = " counter AND input_from = '" . $form ."'"; 
        if($form == 'nonjkn'){
            $conditions = " counter AND ISNULL(input_from) ";
        }elseif($form == 'sisaantrean'){
            $conditions = " counter AND STATUS != 2 ";
        }
        
        $computeQuery = 'count(*) as count';
        if($form == 'maxAntrean'){
            $computeQuery = " max(nomor) as count ";
            $conditions   = " counter AND STATUS = 2 ";
        }
        
        //$hasil = $this->table('data_antrian')
        $hasil = $this->db->table($tableName)
                ->select($computeQuery)                
                ->where($conditions)           
                ->like('waktu', $waktu, 'after')
                ->get()
                ->getRowArray();         

       
        return $hasil;
    }
    
    public function getNomorAntrians($kdbooking,$tableName) {  
    
        
        $conditions = " kode_booking = '" . $kdbooking ."'"; 
                
        $computeQuery = 'count(*) as count';
        
        $hasil = $this->db->table($tableName)
                ->select($computeQuery)                
                ->where($conditions)           
                ->like('waktu', $waktu, 'after')
                ->get()
                ->getRowArray();         

       
        return $hasil;
    }

    public function getInsert_Antrians($loket,$nomor_rm,$noBPJS,$noKTP,$fAlamat,$fNama,$fNik,
                                       $nomortelp,$nomorreferensi,$jenisreferensi,$jenisantrian,$jenispoli,$waktu,$poliName,$tableName) {           
        
        date_default_timezone_set('Asia/Jakarta');
        
        $counter                 = "";
        $filter_jenis_antrian    = " AND jenis_antrian_poliklinik <> '0' ";    
        $filter_waktu            = " AND DATE(waktu) = CURDATE()  ";
        $filter_waktu            = " AND DATE(waktu) = '$waktu'  ";

        //Cek pada tabel dengan status = 3 pada hari yang di pilih
              
        $conditions = " counter='' AND status=3  " . $filter_waktu . $filter_jenis_antrian . " LIMIT 1"; 
        $query = $this->db->table($tableName)
                ->where($conditions)       
                ->countAll();

        if($query >  0){
          
            $conditions2 = " counter='".$loket."' " . $filter_waktu . $filter_jenis_antrian; 

            //Pada RSJ Kalawaatei antar ruang pemeriksaan angkanya berlanjut
            $conditions2 = " counter " . $filter_waktu . $filter_jenis_antrian ;           
            $query2 = $this->db->table($tableName)
                    ->select(' count(*) as count ')  
                    ->where($conditions2)                          
                    ->get()
                    ->getRowArray();

            if($query2 >  0){
                $jmlCountId =  (int)$query2['count']+1 ;
            }else{
                $jmlCountId = 1;
            }

            $KodeBookings = $this->KodeBooking();

            //Tujuan ruang pemeriksaan di buat 2/POLIKLINIK PENYAKIT JIWA. Sebab kode poliklinik hanya 1 : JIW. Tidak bisa memmilih
            $loket = '2';
            $poliName = 'POLIKLINIK PENYAKIT JIWA';
            
            $data_antrian = [
                'waktu' => $waktu,
                'counter' => $loket,
                'status' => '3',
                'nomor' => $jmlCountId,
                'kodebooking' => $KodeBookings,
                'nomorkartubpjs' => $noBPJS,
                'nik' => $noKTP,
                'input_from' => 'jkn',
                'jenis_antrian_poliklinik' =>'2',              
                'nomortelp' =>$nomortelp,
                'nomorreferensi' =>$nomorreferensi,
                'jenisreferensi' =>$jenisreferensi,
                'jenisantrian' =>$jenisantrian,
                'jenispoli' =>$jenispoli,
            ];
     
            $insert_data_antrian = $this->register($data_antrian,$this->table);


            //-------------INSERT DATA_ANTRIAN_DETAIL

            $qr = "Antrian pada ".$poliName."\n Nomor ".$jmlCountId."\n Tanggal ".$waktu."=>".$fNik;
            $data_antrian_detail = [
                'nik' => $noKTP,
                'no_rm' => $nomor_rm,
                'nama' => $fNama,
                'alamat' => $fAlamat,
                'alamatKtp' => $fAlamat,
                'antrianDate' => $waktu,
                'antrianNo' => $jmlCountId,
                'antrianNoBooking' => $jmlCountId,
                'PoliklinikName' =>$poliName,              
                'PoliklinikNo' => $loket,
                'kodeBooking' =>$KodeBookings,
                'jenisantrianpoliklinik' =>'2',
                'qr' =>$qr,
                'namaPesertaBPJS' =>$fNama,
                'telpPesertaBPJS' =>$nomortelp,
                'noKartuPesertaBPJS' => $noBPJS,
                'nikPesertaBPJS' =>$fNik,
                'status_kedatangan' =>1
            ];

            $insert_data_antrian_detail = $this->register($data_antrian_detail,$this->tableDetail);


            //-------------INSERT KUNJUNGAN
          
                                   

            //------------------------------------------
     
            if($insert_data_antrian == true){                
            } else {                
            }       
            
            //2021-04-09 -> Y-m-d
            $estimasidilayani = strtotime(date('Y-m-d H:i:s', strtotime('+3 hours')));
            $hasil = [
                'nomorantrean' => $jmlCountId,
                'kodebooking' => $KodeBookings,
                'jenisantrian' =>$jenisantrian,
                'estimasidilayani' => $estimasidilayani,
                'nampoli' => $poliName,
                'namadokter' => ''
            ];
            
            

        } else {
            $hasil = [
                'nomorantrean' => '000'               
            ];
        }

       
        return $hasil;
    }
    
    public function getInsert_Antrians2($loket,$nomor_rm,$noBPJS,$noKTP,$fAlamat,$fNama,$fNik,
                                       $nomortelp,$nomorreferensi,$jenisreferensi,$jenisantrian,$jenispoli,$waktu,$poliName,
                                       $kodedokter,$namaDokter,$restJkn,$kuotaJkn,$kuotaNonJkn,$restNonJkn,$tableName
                                       ) {           
        
        date_default_timezone_set('Asia/Jakarta');
        
        $counter                 = "";
        $filter_jenis_antrian    = " AND jenis_antrian_poliklinik <> '0' ";    
        $filter_waktu            = " AND DATE(waktu) = CURDATE()  ";
        $filter_waktu            = " AND DATE(waktu) = '$waktu'  ";

        //Cek pada tabel dengan status = 3 pada hari yang di pilih
              
        $conditions = " counter='' AND status=3  " . $filter_waktu . $filter_jenis_antrian . " LIMIT 1"; 
        $query = $this->db->table($tableName)
                ->where($conditions)       
                ->countAll();

        if($query >  0){
          
            $conditions2 = " counter='".$loket."' " . $filter_waktu . $filter_jenis_antrian; 

            //Pada RSJ Kalawaatei antar ruang pemeriksaan angkanya berlanjut
            $conditions2 = " counter " . $filter_waktu . $filter_jenis_antrian ;           
            $query2 = $this->db->table($tableName)
                    ->select(' count(*) as count ')  
                    ->where($conditions2)                          
                    ->get()
                    ->getRowArray();

            if($query2 >  0){
                $jmlCountId =  (int)$query2['count']+1 ;
            }else{
                $jmlCountId = 1;
            }

            $KodeBookings = $this->KodeBooking();

            //Tujuan ruang pemeriksaan di buat 2/POLIKLINIK PENYAKIT JIWA. Sebab kode poliklinik hanya 1 : JIW. Tidak bisa memmilih
            $loket = '2';
            $poliName = 'POLIKLINIK PENYAKIT JIWA';
            
            $data_antrian = [
                'waktu' => $waktu,
                'counter' => $loket,
                'status' => '3',
                'nomor' => $jmlCountId,
                'kodebooking' => $KodeBookings,
                'nomorkartubpjs' => $noBPJS,
                'nik' => $noKTP,
                'input_from' => 'jkn',
                'jenis_antrian_poliklinik' =>'2',              
                'nomortelp' =>$nomortelp,
                'nomorreferensi' =>$nomorreferensi,
                'jenisreferensi' =>$jenisreferensi,
                'jenisantrian' =>$jenisantrian,
                'jenispoli' =>$jenispoli,
                'kodedokter' => $kodedokter
            ];
     
            $insert_data_antrian = $this->register($data_antrian,$this->table);


            //-------------INSERT DATA_ANTRIAN_DETAIL

            $qr = "Antrian pada ".$poliName."\n Nomor ".$jmlCountId."\n Tanggal ".$waktu."=>".$fNik;
            $data_antrian_detail = [
                'nik' => $noKTP,
                'no_rm' => $nomor_rm,
                'nama' => $fNama,
                'alamat' => $fAlamat,
                'alamatKtp' => $fAlamat,
                'antrianDate' => $waktu,
                'antrianNo' => $jmlCountId,
                'antrianNoBooking' => $jmlCountId,
                'PoliklinikName' =>$poliName,              
                'PoliklinikNo' => $loket,
                'kodeBooking' =>$KodeBookings,
                'jenisantrianpoliklinik' =>'2',
                'qr' =>$qr,
                'namaPesertaBPJS' =>$fNama,
                'telpPesertaBPJS' =>$nomortelp,
                'noKartuPesertaBPJS' => $noBPJS,
                'nikPesertaBPJS' =>$fNik,
                'status_kedatangan' =>0
            ];

            $insert_data_antrian_detail = $this->register($data_antrian_detail,$this->tableDetail);

              
            if($insert_data_antrian == true){                
            } else {                
            }       
            
            //2021-04-09 -> Y-m-d
            //$estimasidilayani = strtotime(date('Y-m-d H:i:s', strtotime('+3 hours')));
			$estimasidilayani = (strtotime(date($waktu))+28800) * 1000;
            $pasienbaru = "0";          
            
            $namadokter = $namaDokter;
            $sisakoutajkn = $restJkn;
            $koutajkn = $kuotaJkn;
            $sisakoutanonjkn = $restNonJkn;
            $koutanonjkn = $kuotaNonJkn;
            $nmrantre = 'A-'. $jmlCountId;
            $hasil = [
                'nomorantrean'      => $nmrantre,
                'angkaantrean'      => $jmlCountId,
                'kodebooking'       => $KodeBookings,
                'norm '             => $nomor_rm,
                'namapoli'           => $poliName,
                'namadokter'        => $namadokter,
                'estimasidilayani'  => $estimasidilayani,               
                'sisakuotajkn'      => $sisakoutajkn,
                'kuotajkn'          => $koutajkn,
                'sisakuotanonjkn'   => $sisakoutanonjkn,
                'kuotanonjkn'       => $koutanonjkn,                
                'keterangan'        => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi'
            ];
            
            

        } else {
            $hasil = [
                'nomorantrean' => '000'               
            ];
        }

       
        return $hasil;
    }

    public function getInsert_Kunjungans($loket,$nomor_rm,$noBPJS,$noKTP,$fAlamat,$fNama,$fNik,
                                       $nomortelp,$nomorreferensi,$jenisreferensi,$jenisantrian,
									   $jenispoli,$waktu,$poliName,
                                       $Jenis_Kelamin,$RT_RW,$Kecamatan,$Kota_Kab,$Provinsi,$Kode_Pos,$Kd_Stakaw,
									   $Kd_Pendidikan,$Kd_Pekerjaan) {           
                 
            //-------------INSERT KUNJUNGAN        
            $getRegister = $this->get_OneColumn("RWJ.".date('Y').".%","Register","Max(Register)"," LIKE ",'tb_tr_kunjungan');   
            $gKD    =  $getRegister['Max(Register)'];

            if ($gKD!="") {
                $gKD = (int)substr($gKD,-7,7)+1;
            }else {
                $gKD = 1;
            }
            $regi = "RWJ.".date('Y').".".substr("0000000".$gKD,-7,7);

                       
           
            $gNow = date("Y-m-d H:i:s");
            $gUser = "Antrian-jkn";

            $tanggal  = $waktu;   //date("Y-m-d");
            $jam = date("H:i:s");
            
            //Kelompok BPJS
            $Kd_Jns_Klmpok = "02";
			
            $tb_tr_kunjungan = [
                'Register'=>$regi,              
                'Tanggal'=>$tanggal,
                'Jam'=>$jam,
                'NoRekMed'=>$nomor_rm,
                'Nama'=>$fNama,
                'Jenis_Kelamin'=>$Jenis_Kelamin,
                'Alamat'=>$fAlamat,
                'RT_RW'=>$RT_RW,
                'Kecamatan'=>$Kecamatan,
                'Kota_Kab'=>$Kota_Kab,
                'Provinsi'=>$Provinsi,
                'Kode_Pos'=>$Kode_Pos,
                'Kd_Stakaw'=>$Kd_Stakaw,
                'Kd_Pendidikan'=>$Kd_Pendidikan,
                'Kd_Pekerjaan'=>$Kd_Pekerjaan,      
                'Nma_PnggJawab'=>'',
                'Hub_PnggJawab'=>'',
                'Alm_PnggJawab'=>'',
                'Tlp_PnggJawab'=>'',
                'Ket_Polisi'=>'',
                'Ket_Polisi_Tgl'=>'0000-00-00',                
                'Kd_Jns_Klmpok'=>$Kd_Jns_Klmpok,
                'Kd_Perusahaan'=>'',
                'NoRegKartu'=>'',
                'KartuBerobat'=>'BARU',              
                'NoBPJS'=>$noBPJS,
                'No_SEP'=>'',
                'Status'=>'CheckIn',
                'KePnjgLgsg'=>'N',                
                'Recorded'=>$gNow,
                'Pencatat'=>$gUser
            ];

            $insert_tb_tr_kunjungan = $this->register($tb_tr_kunjungan,$this->tablekunjungan);

                                   

            //------------------------------------------
            $regix = $regi.'.01';
            $fLaYK = 'RJ001';
            switch ($fLaYK) {
                case "RJ001":
                  $fLaYK = "010101";
                  break;
                case "RJ002":
                  $fLaYK = "010102";
                  break;
                case "RJ003":
                  $fLaYK = "010103";
                  break;
                case "RJ004":
                  $fLaYK = "010104";
                  break;
                case "RJ005":
                  $fLaYK = "010105";
                  break;
                case "RJ006":
                  $fLaYK = "010106"; 
                  break;
                case "RJ007":
                  $fLaYK = "010107";
                  break;
                default:
                  $fLaYK = "010101";
              }

            $g08 = $fLaYK ;

            $tb_tr_kunjungan_rinci = [
                'Register'=>$regix,              
                'Tanggal'=>$tanggal,
                'Jam'=>$jam,
                'NoRekMed'=>$nomor_rm,
                'Kd_Rujukan'=>'00',
                'Kd_Ruang_Poli'=>$g08,          
                'Jns_Layanan'=>$g08,            
                'Status'=>'ON',
                'Recorded'=>$gNow,            
                'Pencatat'=>$gUser
            ];

            $insert_tb_tr_kunjungan = $this->register($tb_tr_kunjungan_rinci,$this->tablekunjunganrinci);

                  

       
        return 1;
    }

    public function postInsert_Biodata2($nomorkartubpjs,$nik,$nomorkk,$nama,$jeniskelamin,$tanggallahir,$nohp,$alamat,
                                        $kodeprop,$namaprop,$kodedati2,$namadati2,$kodekec,$namakec,$kodekel,$namakel,$rw,$rt) {           

            //$getNorekmed = $this->get_global_biodata("1","NoRekMed","Max(NoRekMed)"," = ");   
            $getNorekmed = $this->getLastNoRekMed();    
            $gKD         =  $getNorekmed['count'];

            if ($gKD!="") {
                $gKD = (int)substr($gKD,-6,6)+1;
            }else {
                $gKD = 1;
            }
            $gREG = substr("000000".$gKD,-6,6);       

            $gNow = date("Y-m-d H:i:s");

            $staCall = '';
            $tempatLahir = '';
            $rtRw = $rt .'/'.$rw;
            $kdAgama = '';
            $kdGolDarah = '';

            $kdGolDarah = '';
            $kdStakaw = '';
            $kdNegara = '';
            $kdPendidikan = '';
            $kdPekerjaan = '';
            $kodePos = '';
            $namaAyah = '';
            $namaIbu = '';
            $nmIstriSuami = '';     
            $namaPnggJawab = '';
            $namaHubPnggJawab = '';
            $almPnggJawab = '';
            $telpPnggJawab = '';
            $caraBayar = '';
            $kdBahasa = '';
            $memo = '';
                    
            $tb_biodata = [
                'NoRekMed'=>$gREG,              
                'Nama'=>$nama,
                'StaCall'=>$staCall,
                
                'Tempat_Lahir'=>$tempatLahir,
                'Tanggal_Lahir'=>$tanggallahir,
                'Alamat'=>$alamat,
                'RT_RW'=>$rtRw,
                
                'Kd_Agama'=>$kdAgama,
                'Kd_Gol_Darah'=>$kdGolDarah,
                'Kd_Stakaw'=>$kdStakaw,
                'Kd_Negara'=>$kdNegara,
                'Kd_Pendidikan'=>$kdPendidikan,
                'Kd_Pekerjaan'=>$kdPekerjaan,
                'No_KTP'=>$nik,
                'No_Telpon'=>$nohp,
                'Kode_Pos'=>$kodePos,
                'Nma_Ayah'=>$namaAyah,
                'Nma_Ibu'=>$namaIbu,
                'Nma_IstriSuami'=>$nmIstriSuami,
                'Nma_PnggJawab'=>$namaPnggJawab,
                'Hub_PnggJawab'=>$namaHubPnggJawab,
                'Alm_PnggJawab'=>$almPnggJawab,
                'Tlp_PnggJawab'=>$telpPnggJawab,                    
                'Cara_Bayar'=>$caraBayar,
                'KdBahasa'=>$kdBahasa,
                'Memo'=>$memo,                    
                'Recorded'=>$gNow,
                'No_BPJS'=>$nomorkartubpjs,
                'no_kartu'=>$nomorkartubpjs,
            ];

            $insert_tb_biodata = $this->register($tb_biodata,$this->tableBiodata);



            $hasil = [
                    'norm' => $gREG               
                ];

            return $hasil;
    }

    public function getRekapHarian_Antrians($kdpoli,$jenispoli,$waktu,$tableName) {     
                    
        $conditions = array('kdpoli' => $kdpoli, 'jenispoli' => $jenispoli );

        $hasil = $this->db->table($tableName)
           ->select('(SELECT description FROM client_antrian WHERE client=data_antrian.counter) AS nampoli,  COUNT(*) AS totalantrean,  SUM(terlayani) AS terlayani,    waktu AS lastupdate')
           ->join('client_antrian', 'data_antrian.counter = client_antrian.client')
            ->where($conditions)           
            ->like('waktu', $waktu, 'after')
            ->get()
            ->getRowArray();

        return $hasil;
                            
        
    }
    
    public function cekNoAntrianBpjs($nomorkartubpjs,$waktu,$tableName){         
        
        $currentDate = date("Y-m-d") ;
        $currentDate = $waktu;
        
        $conditions = array('nomorkartubpjs' => $nomorkartubpjs);
                            
        $hasil = $this->db->table($tableName)
                ->where($conditions)                
                ->like('waktu', $currentDate, 'after')
                ->get()
                ->getRowArray();   

        return $hasil;  
        
    }
        
    //$tableName = client_antrian
    public function cekKdPoli($kodepoli,$tableName){
                
        $conditions = array('kdpoli' => $kodepoli);
                            
        //$hasil = $this->table('client_antrian')->where($conditions)->get()->getRowArray();   
         $hasil = $this->db->table($tableName)  
                //->join('client_antrian', 'data_antrian.counter = client_antrian.client')
                ->where($conditions)   
                ->get()
                ->getRowArray(); 

        return $hasil;  
        
    }

    public function tanggalMerah($value) {
        date_default_timezone_set("Asia/Jakarta");
        $array = json_decode(file_get_contents("https://raw.githubusercontent.com/guangrei/Json-Indonesia-holidays/master/calendar.json"),true);

            //check tanggal merah berdasarkan libur nasional
            if(isset($array[$value]))
                :$hasil = "tanggal merah ".$array[$value]["deskripsi"];

            //check tanggal merah berdasarkan hari minggu
            elseif(
                date("D",strtotime($value))==="Sun")
                :$hasil = "tanggal merah hari minggu";

            //bukan tanggal merah
            else
                :$hasil = "bukan tanggal merah";
            endif;
            
        return $hasil;
    }
    
    public function cekJenisReferensiRequest($input){
                
        if($input == "1" ||  $input == "2" ||  $input == "3" ||  $input == "4"){
            $hasil = 1;
        }else{
            $hasil = 0;
        }

        return $hasil;  
        
    }

    public function getTotalRow($sql)   {
        
        $conditions =  $sql ;
        $query = $this->table($this->table)
                ->select(' count(*) as count ')  
                ->where($conditions)                          
                ->get()
                ->getRowArray();

        if($query >  0){
            $jmlCountId =  $query['count'] ;
        }else{
            $jmlCountId = 1;
        }

        return $jmlCountId;

    }

    public function KodeBooking()   {
        $length_abjad = "2";
        $length_angka = "4";
    
        $huruf = "ABCDEFGHJKMNPRSTUVWXYZ";

        $i = 1;
        $txt_abjad = "";
        while ($i <= $length_abjad) {
            $txt_abjad .= $huruf[mt_rand(0,strlen($huruf))];
            $i++;
        }

        $datejam = date("His");
        $time_md5 = rand(time(), $datejam);
        $cut = substr($time_md5, 0, $length_angka); 

        $acak = str_shuffle($txt_abjad.$cut);
    
        $cek  = $this->getTotalRow("kodebooking = '".$acak."'");
        if($cek > 0) { $cek = $this->KodeBooking(); }

        return $acak;
    }
    
	public function postAntrianFarmasi($kodebooking) {           
			
			date_default_timezone_set('Asia/Jakarta');
			
			$waktu 		= date("Y-m-d");	
			$waktu2		= date("Y-m-d H:i:s");				
			$counter  	= '1';
			$status   	= '3';
			
			$filter_jenis_antrian    = ""; 
			$jenisresep				 = "Racikan";

			$getKodeBooking  = $this->get_OneColumn($kodebooking,"kodebooking","kodebooking","=",'data_antrian_apotik');   
            $gKodeBooking    = $getKodeBooking['kodebooking'];
									
			$getNoRm  = $this->get_OneColumn($kodebooking,"kodeBooking","no_rm","=",'data_antrian_detail');   
            $gNoRm    = $getNoRm['no_rm'];
			$getNama  = $this->get_OneColumn($kodebooking,"kodeBooking","nama","=",'data_antrian_detail');   
            $gNama    = $getNama['nama'];
			$getWaktu = $this->get_OneColumn($kodebooking,"kodeBooking","waktu","=",'data_antrian');   
            $gWaktu   = $getWaktu['waktu'];
			
			//Waktu mengikuti tabel data_antrian_detail
			$filter_waktu = " AND DATE(waktu) = '$gWaktu'  ";
			//Waktu saat ini
			$filter_waktu = " AND DATE(waktu) = '$waktu'  ";

			
			//Get Antrian Number
            $conditions2 = " counter " . $filter_waktu . $filter_jenis_antrian ;           
            $query2 = $this->db->table($this->tableFarmasi)
                    ->select(' count(*) as count ')  
                    ->where($conditions2)                          
                    ->get()
                    ->getRowArray();

            if($query2 >  0){
                $jmlCountId =  (int)$query2['count']+1 ;
            }else{
                $jmlCountId = 1;
            }

            $data_antrian_farmasi = [
                'waktu' 		=> $waktu2,
                'counter' 		=> $counter,
                'status' 		=> $status,
                'nomor' 		=> $jmlCountId,
                'kodebooking' 	=> $kodebooking,
                'norm' 			=> $gNoRm,
                'nama' 			=> $gNama
            ];
			
			if($gNoRm!=""){
				if($gKodeBooking != ""){
					$hasil = [			
						'nomorantrean'  => "",
						'message'    	=> "Kode booking Sudah digunakan",
						'code'    		=> "201",
					];
				}else{
					$insert_data_antrian_farmasi = $this->register($data_antrian_farmasi,$this->tableFarmasi);
					$hasil = [
						'jenisresep'      => $jenisresep,	
						'nomorantrean'    => $jmlCountId,										
						'keterangan'      => ""					
					];
				}
			}else{
				$hasil = [			
					'nomorantrean'  => "",
					'message'    	=> "Kode Booking tidak ditemukan",
					'code'    		=> "201",
				];
				
			}
			

           
            return $hasil;
    }


	public function postStatusAntrianFarmasi($kodebooking) {           
			
			date_default_timezone_set('Asia/Jakarta');
			
			$waktu 		= date("Y-m-d");	
			$waktu2		= date("Y-m-d H:i:s");				
			$counter  	= '1';
			$status   	= '3';
			
			$jenisresep	= "Racikan";		

			$getKodeBooking  = $this->get_OneColumn($kodebooking,"kodebooking","kodebooking","=",'data_antrian_apotik');   
            $gKodeBooking    = $getKodeBooking['kodebooking'];									
						
			$gettotalantrean = $this->get_OneColumn($waktu,"DATE(waktu)","MAX(nomor)","=",'data_antrian_apotik');   
            $totalAntrean    =  $gettotalantrean['MAX(nomor)'];
			
			$conditions = " DATE(waktu) = '$waktu' AND status = '2' "; 			
			$getAntreanSudahPanggil = $this->db->table('data_antrian_apotik') 
					->select(' COUNT(*) AS count ')              
					->where($conditions)               
					->get()
					->getRowArray(); 
			$antreanSudahPanggil = $getAntreanSudahPanggil['count'];
			
			$sisaAntrean = $totalAntrean - $antreanSudahPanggil;
			
			$conditions2 = " DATE(waktu) = '$waktu' AND status = '2' "; 			
			$getAntreanSedangPanggil = $this->db->table('data_antrian_apotik') 
					->select(' MAX(nomor) AS count ')              
					->where($conditions2)               
					->get()
					->getRowArray(); 
			$antreanSedangPanggil = $getAntreanSedangPanggil['count'];
			
			/*				
			"jenisresep": "Racikan/Non Racikan",
			"totalantrean": 10,
			"sisaantrean": 8,
			"antreanpanggil": 2,
			"keterangan": ""
			*/
			
			//if($totalAntrean > 0){ 
			if($gKodeBooking != ""){
				$hasil = [
					'jenisresep'      => $jenisresep,	
					'totalantrean'    => $totalAntrean,	
					'sisaantrean'     => $sisaAntrean,
					'antreanpanggil'  => $antreanSedangPanggil,					
					'keterangan'      => ""					
				];
            } else {     
				$hasil = [		
					'totalantrean'  => "",
					'message'    	=> "Ada Kesalahan",
					'code'    		=> "201",
				];
            } 

           
            return $hasil;
    }

}