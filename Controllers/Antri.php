<?php
 
namespace App\Controllers;
 
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Models\Api_model;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CodeIgniter\HTTP\IncomingRequest;
 
class Antri extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    public function index()
    {
        
         return   $this->respond($response,200);
        //return $this->respond($token);
    }
    public function auth()
    {
        
        $request = service('request');
        
        // Extract the token
        $user = $request->getHeader('x-username')->getValue();
        $pass = $request->getHeader('x-password')->getValue();
        

        //if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $model = new UserModel();
        $user = $model->where("username", $user)->first();
        if(!$user) return $this->failNotFound('username tidak ditemukan');
        // echo $this->request->getVar('password');
        // echo $user['password'];
        //echo strlen($user['password']);
        //$verify = password_verify($pass, $user['password']);
        if($pass != $user['password']){
                return $this->fail('password salah');
        }
        //if(!$verify) return $this->fail('password salah');
 
        $key = getenv('TOKEN_SECRET');
        $iat = time(); // current timestamp value
        $exp = $iat + 3600;
  
        $payload = array(
            "iss" => "RSJKA",
            "aud" => "MJKN",
            "sub" => "ws mjkn",
            "iat" => $iat, //Time the JWT issued at
            "exp" => $exp, // Expiration time of token
            "uid" => $user['id'],
            "username" => $user['username']
            
        );
       
        $token = JWT::encode($payload, $key, 'HS256');
        //$token = JWT::encode($payload, $key);
       
            $response = array(
                'response' => array(
                    'token' => $token
                ),
                'metadata' => array(
                    'message' => 'ok',
                    'code' => '200'
                )
            );
	$this->response->removeHeader('Cache-Control');
         return   $this->respond($response,200);
        //return $this->respond($token);
    }

    public function status()
    {
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();
        /* parameter dikirim post */
        $waktu = $this->request->getVar('tanggalperiksa');
        $kodepoli = $this->request->getVar('kodepoli');
        $kodedokter = $this->request->getVar('kodedokter');
        $jampraktek = $this->request->getVar('jampraktek');
        //echo $kodepoli;
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        
        //try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            //$decoded = JWT::decode($token, $key, ['HS256']);
            //print_r($decoded);
            $response = [
                'id' => $decoded->uid,
                'username' => $decoded->username
            ];
            
            //mulai sini
            $getPoliNo  = $this->api->get_OneColumn($kodepoli,'kdpoli','client','=','client_antrian');
                    $clientNo   = $getPoliNo['client'];
                    
                    $datas = $this->api->getCountAntrians($clientNo,$waktu,'data_antrian');
                    //print_r($datas);
                    $explodeWaktu = explode("-",$waktu);                    
                    $month = $explodeWaktu[1];  
                    $day = $explodeWaktu[2];  
                    $year = $explodeWaktu[0];                       
                                        $LocalDate = $day .'-'. $month .'-'. $year;
                    $chkDate = (checkdate($month, $day, $year));
                                        date_default_timezone_set("Asia/Jakarta");       
                    $hari_ini = date("Ymd");                    
                    $cekTanggalMerah   = $this->api->tanggalMerah($hari_ini);
                    
                    $cekKdPoli         = $this->api->cekKdPoli($kodepoli,'client_antrian');                                                     
                    
                    if(!$chkDate || (!isset($waktu) || trim($waktu)==='')){
                        $response = "";
                        $message  = "Format Tanggal Tidak Sesuai, format yang benar adalah yyyy-mm-dd";
                        $code     = 201;                                        
                                
                     }else if($cekTanggalMerah != "bukan tanggal merah"){                           
                        $response = "";
                        $message  = "Jadwal tidak tersedia atau kuota penuh. Silahkan pilih tanggal lain";
                        $code     = 401;                    
                                    
                    }else if($cekKdPoli == NULL){                           
                        $response = "";
                        $message  = "Poli tidak ditemukan";
                        $code     = 201;
                                    
                    }else if($datas['count'] == '0'){   
                        
                        $response = "";
                            $message  = "Belum ada antrian ditanggal ". $LocalDate;
                                            //$message  = "Tanggal Periksa Tidak Berlaku";
                        $code     = 201;
                    }else{                      
                       $response = $datas;
                        $message  = "Ok";
                        $code     = 200;
                        
                        //Nama Poli
                        $getDataPoli    = $this->api->get_alldata($kodepoli,'kdpoli','client_antrian');
                        $poliName       = $getDataPoli['description'];
                        $kuotaJkn       = intval($getDataPoli['kuota_jkn']);
                        $kuotaHp    = intval($getDataPoli['kuota_hp']);
                        $kuotaWeb   = intval($getDataPoli['kuota_web']);
                        $kuotaBox   = intval($getDataPoli['kuota_box']);

                        
                        $kuotaNonJkn  = $kuotaHp + $kuotaWeb + $kuotaBox ;
                        
                        //Get nama dokter       
                        $getNamaDokter = $this->api->get_OneColumn($kodedokter,'kodedokter','namadokter','=','ref_dokter');         
                        $namaDokter    = $getNamaDokter['namadokter'];
                        
                        //sisaantrean
                        $getSisaAntrean = $this->api->getUsedAntrians($clientNo,$waktu,'sisaantrean','data_antrian');
                        //$sisaAntrean    = $getSisaAntrean['count'];   
                                                $sisaAntrean    = intval($getSisaAntrean['count']);
    
                        //antreanPanggil / MAx antrean
                        $getmaxAntrean  = $this->api->getUsedAntrians($clientNo,$waktu,'maxAntrean','data_antrian');
                        $antreanPanggil = $getmaxAntrean['count'];  
                                                $antreanPanggil = intval($getmaxAntrean['count']);
                                                
                        //Penggunaan antrian JKN
                        $getUserJkn = $this->api->getUsedAntrians($clientNo,$waktu,'jkn','data_antrian');
                        $usedJkn    = $getUserJkn['count'];                     
                        $restJkn   = intval($kuotaJkn) - intval($usedJkn);
                        
                        //Penggunaan antrian Non JKN
                        $getUserNonJkn = $this->api->getUsedAntrians($clientNo,$waktu,'nonjkn','data_antrian');
                        $usedNonJkn    = $getUserNonJkn['count'];                       
                        $restNonJkn    = $kuotaNonJkn - intval($usedNonJkn);
                        $response = [
                            'namapoli'      => $poliName,
                            'namadokter'    => $namaDokter,
                            'totalantrean'  => intval($datas['count']),
                            'sisaantrean'   => $sisaAntrean,
                            'antreanpanggil' => $antreanPanggil,
                            'sisakoutajkn'  => $restJkn,
                            'koutajkn'      => $kuotaJkn,
                            'sisakoutanonjkn'   => $restNonJkn,
                            'koutanonjkn'   => $kuotaNonJkn,
                            'keterangan'    => 'Datanglah minimal 30 menit, jika nomor antrian lewat, silahkan hubungi bagian pendaftaran. Terimakasih'
                            
                            
                        ];
                       
                       

                    }
                    
                    if($response == ""){
                        $output = [ 'metadata' => [
                                            'message' => $message, 
                                            'code'=>$code
                                                ]
                                ];
                    }else{
                        $output = [ 'response' => $response,                                
                                    'metadata' => [
                                            'message' => $message, 
                                            'code'=>$code
                                                ]
                                ];
                    }
                    
			$this->response->removeHeader('Cache-Control');
                    return $this->respond($output, 200);
            //    }
            //return $this->respond($response);
        // } 
        // //print_r($decoded);
        // catch (\Throwable $th) {
        //     return $this->fail('Invalid Token');
        // }
    }

    /* get no antrean */
    public function antrean()
    {
        // Get all the headers
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();
        /* parameter dikirim post */
        $waktu = $this->request->getVar('tanggalperiksa');
        $kodepoli = $this->request->getVar('kodepoli');
        $kodedokter = $this->request->getVar('kodedokter');
        $jampraktek = $this->request->getVar('jampraktek');
        //echo $kodepoli;
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        
        //try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            //$decoded = JWT::decode($token, $key, ['HS256']);
            //print_r($decoded);
            $response = [
                'id' => $decoded->uid,
                'username' => $decoded->username
            ];
        // $headers = $this->input->request_headers();
        // // Extract the token
        // $header_token = $headers['x-token'];
        

        // Use try-catch
        // JWT library throws exception if the token is not valid
        //try {
            // Validate the token
            // Successfull validation will return the decoded user data else returns false
            // $token = AUTHORIZATION::validateToken($header_token);
            // if ($token === false) {
            //     $this->gagal();
            //     exit();
            // } else {
                /* kalau token valid lanjut disini */
                    $nomorkartubpjs = $this->request->getVar('nomorkartu');
                    $nik            = $this->request->getVar('nik');
                    $nomortelp      = $this->request->getVar('notelp');
                    $kodepoli       = $this->request->getVar('kodepoli');
                    $norm           = $this->request->getVar('norm');
                    $waktu          = $this->request->getVar('tanggalperiksa');
                    $kodedokter     = $this->request->getVar('kodedokter');
                    $jampraktek     = $this->request->getVar('jampraktek');
                    $jeniskunjungan = $this->request->getVar('jeniskunjungan');
                    $nomorreferensi = $this->request->getVar('nomorreferensi');
                    
                    $jenisreferensi = "";
                    $jenisantrian = "";
                    $jenispoli = "";
                                                    
                   
                    $datas  = $this->api->get_biodata($nomorkartubpjs,$nik,'tb_biodata');
                    //->$datas = $this->TbBiodata->get_biodata($nomorkartubpjs,$nik);       
                    //print_r($datas);
                    $explodeWaktu = explode("-",$waktu);                    
                    $month = $explodeWaktu[1];  
                    $day = $explodeWaktu[2];  
                    $year = $explodeWaktu[0];                       
                    $chkDate = (checkdate($month, $day, $year));

                    $cekNoAntrianBpjs  = $this->api->cekNoAntrianBpjs($nomorkartubpjs,$waktu,'data_antrian');
                    //echo $cekNoAntrianBpjs;
                    //->$cekNoAntrianBpjs  = $this->antrian->cekNoAntrianBpjs($nomorkartubpjs,$waktu);                  
                    date_default_timezone_set("Asia/Jakarta");
                    $hari_ini = date("Ymd");

                    $cekTanggalMerah   = $this->api->tanggalMerah($waktu);

                    $cekHariLibur   = $this->api->tanggalMerah($waktu);
                    
                    $cekKdPoli         = $this->api->cekKdPoli($kodepoli,'client_antrian'); 
                    
                    $cekJenisKunjungan = $this->api->cekJenisReferensiRequest($jeniskunjungan);
                    
                    $query = " kodedokter ='$kodedokter' AND HOUR('$jampraktek') >= HOUR(jampraktek)  AND HOUR('$jampraktek') < HOUR(jamprakteksampai)";
                    $cekJadwalDokter    = $this->api->get_data($query,' kodedokter ','ref_dokter');

                                    
                    //Get nama dokter   
                    $getNamaDokter = $this->api->get_OneColumn($kodedokter,'kodedokter','namadokter','=','ref_dokter');         
                    $namaDokter    = $getNamaDokter['namadokter'];                  
                    
                    //Kuota Non JKN (Hp,Web dan Box/antrian dari anjungan)
                    $getDataPoli    = $this->api->get_alldata($kodepoli,'kdpoli','client_antrian');
                    //print_r($getDataPoli);
                    $kuotaJkn       = intval($getDataPoli['kuota_jkn']);
                    $kuotaHp        = intval($getDataPoli['kuota_hp']);
                    $kuotaWeb       = intval($getDataPoli['kuota_web']);
                    $kuotaBox       = intval($getDataPoli['kuota_box']);
                    $poliDeskripsi  = $getDataPoli['description'];  
                    $poliTutup      = $getDataPoli['tutup'];
                    $poliTutupJam   = $getDataPoli['tutup_jam'];                    
                                        
                    $kuotaNonJkn  = $kuotaHp + $kuotaWeb + $kuotaBox ;
                    
                    $getPoliNo  = $this->api->get_OneColumn($kodepoli,'kdpoli','client','=','client_antrian');
                    $clientNo   = $getPoliNo['client'];
                    
                    //sisaantrean
                    $getSisaAntrean = $this->api->getUsedAntrians($clientNo,$waktu,'sisaantrean','data_antrian');
                    $sisaAntrean    = $getSisaAntrean['count']; 

                    //antreanPanggil / MAx antrean
                    $getmaxAntrean  = $this->api->getUsedAntrians($clientNo,$waktu,'maxAntrean','data_antrian');
                    $antreanPanggil = $getmaxAntrean['count'];  
                                                
                    
                    //Penggunaan antrian JKN
                    $getUserJkn = $this->api->getUsedAntrians($clientNo,$waktu,'jkn','data_antrian');
                    $usedJkn    = $getUserJkn['count'];                     
                    $restJkn   = intval($kuotaJkn) - intval($usedJkn);
                                
                    //Penggunaan antrian Non JKN
                    $getUserNonJkn = $this->api->getUsedAntrians($clientNo,$waktu,'nonjkn','data_antrian');
                    $usedNonJkn    = $getUserNonJkn['count'];                       
                    $restNonJkn    = $kuotaNonJkn - intval($usedNonJkn);                                                
                            
                    //1.Validasi Nomor Kartu Tidak Valid / Null 
                    if(strlen($nomorkartubpjs) != 13){                      
                        $response = "";
                        $message  = "Nomor kartu harus 13 digit";
                        $code     = 201;    
                        
                    //2.Validasi Format Tanggal Periksa Tidak Valid/Null
                    }else if(!$chkDate || (!isset($waktu) || trim($waktu)==='')){
                        $response = "";
                        $message  = "Format Tanggal Tidak Sesuai, format yang benar adalah yyyy-mm-dd";
                        $code     = 201;
                        
                    //3. Validasi Tanggal Periksa Tidak Boleh Melebihi 90 hari dari Rujukan FKTP ???
                    /*
                    }else if($datas == NULL){                           
                        $response = "";
                        $message  = "Tanggal periksa bisa dilakukan tanggal xxxx-xx-xx hingga tanggal xxxx-xx-xx";
                        $code     = 401;        
                    
                    */
                    
                    //4.  Validasi  Tanggal Periksa Membaca tanggal merah           
                                        //    Validasi pengambilan antrian tidak boleh hari ini, harus besok
                         }else if($waktu <= date("Y-m-d")){
                        $response = "";
                        $message  = "Pengambilan nomor antrian paling cepat H - 1";
                        $code     = 201;

                     }else if($cekTanggalMerah != "bukan tanggal merah"){                           
                        $response = "";
                        $message  = "Jadwal tidak tersedia atau kuota penuh. Silahkan pilih tanggal lain";
                        $code     = 201;    
                         
                    //4a.  Validasi Tanggal Periksa Membaca Hari Libur / Jadwal Praktek                 
                    }else if($cekHariLibur != "bukan tanggal merah"){                           
                        $response = "";
                        $message  = "Pendaftaran ke Poli Ini Sedang Tutup";
                        $code     = 201;    
                    
                    //5. Validasi No Antrean Hanya Bisa Diambil 1 Kali                  
                    }else if($cekNoAntrianBpjs != NULL){                            
                        $response = "";
                        $message  = "Nomor Antrean Hanya Dapat Diambil 1 Kali Pada Tanggal Yang Sama";
                        $code     = 201;    
                    
                    
                    //6. Validasi Poli Tidak Sesuai                 
                    }else if($cekKdPoli == NULL){                           
                        $response = "";
                        $message  = "Poli tidak ditemukan";
                        $code     = 201;
                                            
                                        
                    //8.Validasi Jenis kunjugan Tidak Sesuai
                    }else if($cekJenisKunjungan == 0){                          
                        $response = "";
                        $message  = "Jenis Kunjungan tidak ditemukan";
                        $code     = 201;                        

                    }else if($cekJadwalDokter == NULL){                         
                        $response = "";
                        $message  = "Jadwal  $namaDokter Tersebut Belum Tersedia, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya";
                        $code     = 201;

                    }else if($poliTutup == '1'){                            
                        $response = "";
                        $message  = "Pendaftaran Ke Poli $poliDeskripsi Sudah Tutup Jam  $poliTutupJam.00";
                        $code     = 201;
                    
                    }else if($datas == NULL){   
                        
                        //Jika kosong Buat Insert ke tb_biodata dan Insert ke tabel2x ini : tabel data_antrian, data_antrian_detail, tb_tr_kunjungan dan tb_tr_kunjungan_rinci
                        
                    
                        //$nama = ''; //Ambil dari hasil bridging ke BPJS
                        //$data_insert_biodata = $this->TbBiodata->postInsert_Biodata($nomorkartubpjs,$nik,$nomortelp,$nama);
                    
                        $response = "";
                        $message  = "Data pasien ini tidak ditemukan, silahkan Melakukan Registrasi Pasien Baru";
                        $code     = 202;
                    }else{                      
                        $response = $datas;
                        $message  = "Ok";
                        $code     = 200;

                        $getPoliNo  = $this->api->get_OneColumn($kodepoli,'kdpoli','client','=','client_antrian');
                        $getPoliName  = $this->api->get_OneColumn($kodepoli,'kdpoli','description','=','client_antrian');

                        $poliNo   =  $getPoliNo['client'];
                        $poliName =  $getPoliName['description'];

                        $nomor_rm = $response['NoRekMed'];
                        $noBPJS   = $nomorkartubpjs;
                        $noKTP    = $nik;
                        $fAlamat  = $response['Alamat'];
                        $fNama    = $response['Nama'];
                        
                        //Insert tabel data_antrian, data_antrian_detail, tb_tr_kunjungan dan tb_tr_kunjungan_rinci
                       
                        $data_insert = $this->api->getInsert_Antrians2($poliNo,$nomor_rm,$noBPJS,$noKTP,$fAlamat,$fNama,$noKTP,
                                                                          $nomortelp,$nomorreferensi,$jenisreferensi,$jenisantrian,$jenispoli,$waktu,$poliName,
                                                                          $kodedokter,$namaDokter,$restJkn,$kuotaJkn,$kuotaNonJkn,$restNonJkn,'data_antrian'
                                                                          );

                        $Jenis_Kelamin = $response['Jenis_Kelamin'];                                                                        
                        $RT_RW         = $response['RT_RW'];
                        $Kecamatan     = $response['Kecamatan'];
                        $Kota_Kab      = $response['Kota_Kab'];
                        $Provinsi      = $response['Provinsi'];
                        $Kode_Pos      = $response['Kode_Pos'];
                        $Kd_Stakaw     = $response['Kd_Stakaw'];
                        $Kd_Pendidikan = $response['Kd_Pendidikan'];
                        $Kd_Pekerjaan  = $response['Kd_Pekerjaan'];
                        
                        $data_insert_kunjungan = $this->api->getInsert_Kunjungans($poliNo,$nomor_rm,$noBPJS,$noKTP,$fAlamat,$fNama,$noKTP,
                                                                          $nomortelp,$nomorreferensi,$jenisreferensi,$jenisantrian,$jenispoli,$waktu,$poliName,
                                                                          $Jenis_Kelamin,$RT_RW,$Kecamatan,$Kota_Kab,$Provinsi,$Kode_Pos,$Kd_Stakaw,$Kd_Pendidikan,$Kd_Pekerjaan
                                                                        );
                        
                        //var_dump($data_insert);

                        $response = $data_insert;
                        

                    }
                    
                    
                
                    if($response == ""){
                        $output = ['metadata' => [
                                            'message' => $message, 
                                            'code'=>$code
                                                ]
                                ];
                    }else{
                        $output = [ 'response' => $response,                                
                                    'metadata' => [
                                            'message' => $message, 
                                            'code'=>$code
                                                ]
                                ];
                    }
                    
			$this->response->removeHeader('Cache-Control');
                    //return $this->response($output, 200);
			return $this->respond($output, 200);
              //  }
                
        
          //  }
                
            //}
        // } catch (Exception $e) {
        //     // Token is invalid
        //     // Send the unathorized access message
        //     $this->gagal();
        // }
    }

    public function sisa()
    {
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();

        // Get all the headers
        // $headers = $this->input->request_headers();
        // // Extract the token
        // $header_token = $headers['x-token'];
        // /* parameter dikirim post */
         $kodebooking= $this->request->getVar('kodebooking');
        

        // Use try-catch
        // JWT library throws exception if the token is not valid
        /* parameter dikirim post */
        // $waktu = $this->request->getVar('tanggalperiksa');
        // $kodepoli = $this->request->getVar('kodepoli');
        // $kodedokter = $this->request->getVar('kodedokter');
        // $jampraktek = $this->request->getVar('jampraktek');
        //echo $kodepoli;
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        
        //try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            //$decoded = JWT::decode($token, $key, ['HS256']);
            //print_r($decoded);
            $response = [
                'id' => $decoded->uid,
                'username' => $decoded->username
            ];

        try {
            // Validate the token
            // Successfull validation will return the decoded user data else returns false
            //$token = AUTHORIZATION::validateToken($header_token);
            if ($token === false) {
                $this->gagal();
                exit();
            } else {
                /* kalau token valid lanjut disini */

                //$requestBody = json_decode($this->request->getBody());
                    
                    // $kodebooking    = $requestBody->kodebooking;                
                                    
                    $datas = $this->api->get_OneColumn($kodebooking,'kodebooking','nomor','=','data_antrian');

                    if(!$datas || $datas['nomor'] == ''){                          
                        $response = "";
                        $message  = "Antrean Tidak Ditemukan";
                        $code     = 201;
                    }else{                      
                        $response = $datas;
                        $message  = "Ok";
                        $code     = 200;

                        $getDataClient  = $this->api->get_alldata($kodebooking,'kodebooking','data_antrian');
                        $clientNumber   = $getDataClient['counter'];
                        $tanggalPeriksa = $getDataClient['waktu'];
                        $kodeDokter     = $getDataClient['kodedokter'];
                        
                                                
                        //Nama Poli
                        $getPoliName = $this->api->get_OneColumn($clientNumber,'client','description','=','client_antrian');                    
                        $poliName    = $getPoliName['description'];
                        
                        //sisaantrean
                        $getSisaAntrean = $this->api->getUsedAntrians($clientNumber,$tanggalPeriksa,'sisaantrean','data_antrian');
                        $sisaAntrean    = intval($getSisaAntrean['count']); 

                        //antreanPanggil / MAx antrean
                        $getmaxAntrean  = $this->api->getUsedAntrians($clientNumber,$tanggalPeriksa,'maxAntrean','data_antrian');
                        $antreanPanggil = intval($getmaxAntrean['count']);  
                                                
                        //Get nama dokter
                        $getNamaDokter = $this->api->get_OneColumn($kodeDokter,'kodedokter','namadokter','=','ref_dokter');                         
                        $namaDokter    = $getNamaDokter['namadokter'];

			$nomorantrean = $datas['nomor'];
                        //$nomorantrean = 2;
                                                
                        //Waktu Tunggu= SPM (sisaAntrean - 1)
                        $rr = date($tanggalPeriksa);
                        $stamp = strtotime($rr); 
                        $time_in_ms1 = $stamp-14400;
			$time_in_ms = $time_in_ms1*1000;
                        //$sisaAntrean = 2;
                        $spm = $time_in_ms;
                        //$waktutunggu = $spm * (intval($sisaAntrean) ) ;                  
                        $waktutunggu = $spm + ($nomorantrean * 900000 ) ;
			

                         $response = [
                            'nomorantrean'   => $nomorantrean,                            
                            'namapoli'       => $poliName,
                            'namadokter'     => $namaDokter,
                            'sisaantrean'    => $sisaAntrean,   
                            'antreanpanggil' => $antreanPanggil,
                            'waktutunggu'    => $waktutunggu,
                            'keterangan'     => ''
                            
                            
                        ];
                        
                        //var_dump($getClientNumber);

                        //$response = $data_insert;
                        

                    }
                    
                    
                    if($response == ""){
                        $output = [ 'metadata' => [
                                    'message' => $message, 
                                    'code'=>$code
                                                ]
                                ];
                    }else{
                        $output = [ 'response' => $response,                                
                                    'metadata' => [
                                            'message' => $message, 
                                            'code'=>$code
                                                ]
                                ];
                    }

                                    
                    $this->response->removeHeader('Cache-Control');
                    return $this->respond($output, 200);
                }
        } catch (Exception $e) {
            // Token is invalid
            // Send the unathorized access message
            $this->gagal();
        }
    }

	public function batal()
    {
        // Get all the headers
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();

        $kodebooking= $this->request->getVar('kodebooking');
        
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        
        //try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            //$decoded = JWT::decode($token, $key, ['HS256']);
            //print_r($decoded);
            $response = [
                'id' => $decoded->uid,
                'username' => $decoded->username
            ];

        // $headers = $this->input->request_headers();
        // // Extract the token
        // $header_token = $headers['x-token'];
        //  parameter dikirim post 
        
        // $kodebooking    = $this->post('kodebooking');
        // $keterangan     = $this->post('keterangan');
        // Use try-catch
        // JWT library throws exception if the token is not valid
        try {
            // Validate the token
            // Successfull validation will return the decoded user data else returns false
            //$token = AUTHORIZATION::validateToken($header_token);
            if ($token === false) {
                $this->gagal();
                exit();
            } else {
                /* kalau token valid lanjut disini */

                // $requestBody = json_decode($this->request->getBody());
                    
                    // $kodebooking    = $requestBody->kodebooking;
                    // $keterangan     = $requestBody->keterangan;
                                        
                    //$datas = $this->api->get_OneColumn($kodebooking,'kodebooking','nomor','=','data_antrian');

                    //$terlayani = $this->api->get_OneColumn($kodebooking,'kodebooking','terlayani','=','data_antrian');

                    $datas      = $this->api->get_alldata($kodebooking,'kodebooking','data_antrian');
                    $nomor      = $datas['nomor'];
                    $terlayani  = $datas['terlayani'];                  
                    
                    $query      = " kodebooking ='$kodebooking' AND status_kedatangan = '1' ";
                    $checkIn     = $this->api->get_data($query,' status_kedatangan ','data_antrian_detail');
                    //print_r($checkIn);
                    if($nomor == ''){                           
                        $response = "";
                        $message  = "Antrean Tidak Ditemukan atau Sudah Dibatalkan";
                        $code     = 201;

                    }else if($terlayani == '1'){                            
                            $response = "";
                            $message  = "Pasien Sudah Dilayani, Antrean Tidak Dapat Dibatalkan";
                            $code     = 201;

                    }else if(isset($checkIn['status_kedatangan']) == '1'){                         
                            $response = "";
                            $message  = "Anda sudah Check In, Antrean Tidak Dapat Dibatalkan";
                            $code     = 201;

                    }else{                      
                        $response = $datas;
                        $message  = "Ok";
                        $code     = 200;
                        
                        $data_delete_data_antrian = $this->api->hapus('kodebooking',$kodebooking,'data_antrian');
                        $data_delete_data_antrian_detail = $this->api->hapus('kodebooking',$kodebooking,'data_antrian_detail');

                    }                   
                    
                    $output = [
                                                            
                                'metadata' => [
                                        'message' => $message, 
                                        'code'=>$code
                                    ]
                            ];
                    
                    $this->response->removeHeader('Cache-Control');
                    return $this->respond($output, 200);
            }
        } catch (Exception $e) {
            // Token is invalid
            // Send the unathorized access message
            $this->gagal();
        }
    }

    public function checkin()
    {
        // Get all the headers
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();

        $kodebooking= $this->request->getVar('kodebooking');
        
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        
        //try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            //$decoded = JWT::decode($token, $key, ['HS256']);
            //print_r($decoded);
            $response = [
                'id' => $decoded->uid,
                'username' => $decoded->username
            ];


        // $headers = $this->input->request_headers();
        // // Extract the token
        // $header_token = $headers['x-token'];
        /* parameter dikirim post */
        
        $kodebooking    = $this->request->getVar('kodebooking');
        $waktu          = $this->request->getVar('waktu');

        // Use try-catch
        // JWT library throws exception if the token is not valid
        try {
            // Validate the token
            // Successfull validation will return the decoded user data else returns false
            //$token = AUTHORIZATION::validateToken($header_token);
            if ($token === false) {
                $this->gagal();
                exit();
            } else {
                /* kalau token valid lanjut disini */
               // $requestBody = json_decode($this->request->getBody());
                    
                    // $kodebooking    = $requestBody->kodebooking;
                    // $waktu          = $requestBody->waktu;
        
                    $datas = $this->api->get_OneColumn($kodebooking,'kodebooking','nomor','=','data_antrian');
                                                                        
                    if(!$datas || $datas['nomor'] == ''){                      
                        $response = "Data Kosong";
                        $message  = "Gagal";
                        $code     = 201;
                    }else{                      
                        $response = $datas;
                        $message  = "Ok";
                        $code     = 200;                        
                                                     
                        $data_update = $this->api->updateTable('status_kedatangan','1','kodebooking',$kodebooking,$waktu,'data_antrian_detail');                    

                    }       
                                
                    $output = [
                                                            
                                'metadata' => [
                                        'message' => $message, 
                                        'code'=>$code
                                    ]
                            ];
                    
                    $this->response->removeHeader('Cache-Control');
                    return $this->respond($output, 200);
                
            }
        } catch (Exception $e) {
            // Token is invalid
            // Send the unathorized access message
            $this->gagal();
        }
    }


    public function pasienbaru()
    {
        // Get all the headers
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();

        //$kodebooking= $this->request->getVar('kodebooking');
        
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        // $headers = $this->input->request_headers();
        // // Extract the token
        // $header_token = $headers['x-token'];
        /* parameter dikirim post */
        
        //$tanggalperiksa = $this->request->getVar('tanggalperiksa');
        $nomorkartubpjs = $this->request->getVar('nomorkartu');
        $nik            = $this->request->getVar('nik');
        $nomorkk        = $this->request->getVar('nomorkk');
        $nama           = $this->request->getVar('nama');
        $jeniskelamin   = $this->request->getVar('jeniskelamin');
        $tanggallahir   = $this->request->getVar('tanggallahir');
        $nohp           = $this->request->getVar('nohp');
        $alamat         = $this->request->getVar('alamat');
        $kodeprop       = $this->request->getVar('kodeprop');
        $namaprop       = $this->request->getVar('namaprop');
        $kodedati2      = $this->request->getVar('kodedati2');
        $namadati2      = $this->request->getVar('namadati2');
        $kodekec        = $this->request->getVar('kodekec');
        $namakec        = $this->request->getVar('namakec');
        $kodekel        = $this->request->getVar('kodekel');
        $namakel        = $this->request->getVar('namakel');
        $rw             = $this->request->getVar('rw'); 
        $rt             = $this->request->getVar('rt');     

        // Use try-catch
        // JWT library throws exception if the token is not valid
        try {
            // Validate the token
            // Successfull validation will return the decoded user data else returns false
            //$token = AUTHORIZATION::validateToken($header_token);
            if ($token === false) {
                $this->gagal();
                exit();
            } else {
                /* kalau token valid lanjut disini */
                //$requestBody = json_decode($this->request->getBody());
                    

                    

                    $cekNomorBpjs   = $this->api->get_OneColumn($nomorkartubpjs,'No_BPJS','No_BPJS','=','tb_biodata');                  
                    $isAngkabpjs    = is_numeric($nomorkartubpjs);
                    $isAngkanik     = is_numeric($nik);

                                        if(!empty($tanggallahir)){
                        $explodeWaktu = explode("-",$tanggallahir);                 
                        $month = $explodeWaktu[1];  
                        $day = $explodeWaktu[2];  
                        $year = $explodeWaktu[0];                       
                        $chkDate = (checkdate($month, $day, $year));
                    }else{
                        $chkDate ='';
                    }

                                      //var_dump(checkdate($month,$day,$year));
//                                      var_dump(checkdate(2,29,2004));

                    if(empty($nomorkartubpjs)){                                     
                        $response = "";
                        $message  = "Nomor Kartu Belum Diisi ";
                        $code     = 201;

                    }else if(empty($nik)){                          
                        $response = "";
                        $message  = "Nomor NIK Belum Diisi";
                        $code     = 201;

                    }else if(empty($nomorkk)){                          
                        $response = "";
                        $message  = "Nomor KK Belum Diisi";
                        $code     = 201;

                    }else if(empty($nama)){                         
                        $response = "";
                        $message  = "Nama Belum Diisi";
                        $code     = 201;

                    }else if(empty($jeniskelamin)){                         
                        $response = "";
                        $message  = "Jenis Kelamin Belum Dipilih";
                        $code     = 201;

                    }else if(empty($tanggallahir)){                         
                        $response = "";
                        $message  = "Tanggal Lahir Belum Diisi";
                        $code     = 201;

                    }else if(empty($alamat)){                           
                        $response = "";
                        $message  = "Alamat Belum Diisi";
                        $code     = 201;

                    }else if(empty($kodeprop)){                         
                        $response = "";
                        $message  = "Kode Propinsi Belum Diisi";
                        $code     = 201;

                    }else if(empty($namaprop)){                         
                        $response = "";
                        $message  = "Nama Propinsi Belum Diisi";
                        $code     = 201;

                    }else if(empty($kodedati2)){                            
                        $response = "";
                        $message  = "Kode Dati 2 Belum Diisi";
                        $code     = 201;

                    }else if(empty($namadati2)){                            
                        $response = "";
                        $message  = "Dati 2 Belum Diisi";
                        $code     = 201;

                    }else if(empty($kodekec)){                          
                        $response = "";
                        $message  = "Kode Kecamatan Belum Diisi";
                        $code     = 201;

                    }else if(empty($namakec)){                          
                        $response = "";
                        $message  = "Kecamatan Belum Diisi";
                        $code     = 201;

                    }else if(empty($kodekel)){                          
                        $response = "";
                        $message  = "Kode Kelurahan Belum Diisi";
                        $code     = 201;

                    }else if(empty($namakel)){                          
                        $response = "";
                        $message  = "Kelurahan Belum Diisi";
                        $code     = 201;

                    }else if(empty($rw)){                           
                        $response = "";
                        $message  = "RW Belum Diisi";
                        $code     = 201;

                    }else if(empty($rt)){                           
                        $response = "";
                        $message  = "RT Belum Diisi";
                        $code     = 201;
                        
                    }else if(!$chkDate || (!isset($tanggallahir) || trim($tanggallahir)==='')){
                        $response = "";
                        $message  = "Format Tanggal Lahir Tidak Sesuai ";
                        $code     = 201;





                    }else if($cekNomorBpjs != NULL){                            
                        $response = "";
                        $message  = "Data Peserta Sudah Pernah Dientrikan";
                        $code     = 201;    
                    
                   }else if((strlen($nomorkartubpjs) != 13) OR $isAngkabpjs != 1){                                      
                        $response = "";
                        $message  = "Format Nomor Kartu Tidak Sesuai";
                        $code     = 201;
                        
                    }else if((strlen($nik) != 16) OR $isAngkanik != 1){                                     
                        $response = "";
                        $message  = "Format NIK Tidak Sesuai ";
                        $code     = 201;

                    
                    /*}else if($data_insert_biodata == NULL){                                       
                        $response = "";
                        $message  = "Gagal";
                        $code     = 201;
                    */
                    }else{      
                                    

                        $data_insert_biodata = $this->api->postInsert_Biodata2(
                                $nomorkartubpjs,$nik,$nomorkk,$nama,$jeniskelamin,$tanggallahir,$nohp,$alamat,
                                $kodeprop,$namaprop,$kodedati2,$namadati2,$kodekec,$namakec,$kodekel,$namakel,$rw,$rt
                                );
                                
                        $response = $data_insert_biodata;
                        
                        $message  = "Harap datang ke adminisi untuk melengkapi data rekam medis";
                        $code     = 200;                    

                    }
            
                    if($response == ""){
                        $output = [ 'metadata' => [
                                    'message' => $message, 
                                    'code'=>$code
                                                        ]
                                ];
                    }else{
                        $output = [ 'response' => $response,                                
                                    'metadata' => [
                                    'message' => $message, 
                                    'code'=>$code
                                    ]
                                ];
                    }
                    
                    $this->response->removeHeader('Cache-Control');
                    return $this->respond($output, 200);
                
            }
        } catch (Exception $e) {
            // Token is invalid
            // Send the unathorized access message
            $this->gagal();
        }
    }


    public function operasipasien()
    {
        // Get all the headers
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();

        $kodebooking= $this->request->getVar('kodebooking');
        
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;

        // $headers = $this->input->request_headers();
        // // Extract the token
        // $header_token = $headers['x-token'];
        /* parameter dikirim post */
       // $nopeserta = $this->post('nopeserta');

        // Use try-catch
        // JWT library throws exception if the token is not valid
        try {
            // Validate the token
            // Successfull validation will return the decoded user data else returns false
            //$token = AUTHORIZATION::validateToken($header_token);
            if ($token === false) {
                $this->gagal();
                exit();
            } else {
                /* kalau token valid lanjut disini */

                
                $response = array(
                    
                    'metadata' => array(
                        'message' => 'RS Belum ada layanan Operasi',
                        'code' => '201'
                    )
                );
                //$this->respond($response, '201');
                $this->response->removeHeader('Cache-Control');
                return $this->respond($response, 201);
            }
        } catch (Exception $e) {
            // Token is invalid
            // Send the unathorized access message
            $this->gagal();
        }
    }

    public function operasirs()
    {
        // Get all the headers
        $this->api = new Api_model();
        $request = service('request');
        $key = getenv('TOKEN_SECRET');
        $header = $request->getHeader('x-token')->getValue();

        $kodebooking= $this->request->getVar('kodebooking');
        
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;

        // $headers = $this->input->request_headers();
        // // Extract the token
        // $header_token = $headers['x-token'];
        // /* parameter dikirim post */
        // $nopeserta = $this->post('nopeserta');

        // Use try-catch
        // JWT library throws exception if the token is not valid
        try {
            // Validate the token
            // Successfull validation will return the decoded user data else returns false
            //$token = AUTHORIZATION::validateToken($header_token);
            if ($token === false) {
                $this->gagal();
                exit();
            } else {
                /* kalau token valid lanjut disini */

                
                $response = array(
                    
                    'metadata' => array(
                        'message' => 'RS Belum ada layanan Operasi',
                        'code' => '201'
                    )
                );
                //$this->respond($response, '201');
                $this->response->removeHeader('Cache-Control');
                return $this->respond($response, 201);
            }
        } catch (Exception $e) {
            // Token is invalid
            // Send the unathorized access message
            $this->gagal();
        }
    }

	public function antreanfarmasi()
    {
        // Get all the headers
        $this->api 	= new Api_model();
        $request 	= service('request');
        $key 		= getenv('TOKEN_SECRET');
        $header 	= $request->getHeader('x-token')->getValue();
        
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
           
        $response = [
            'id' => $decoded->uid,
            'username' => $decoded->username
        ];
        
		$kodebooking = $this->request->getVar('kodebooking');                             
                     
        $data_insert = $this->api->postAntrianFarmasi($kodebooking);
		
		$response = $data_insert;
						 
                
		//if($response == ""){
		if($response['nomorantrean'] == ""){			
            $output = ['metadata' => [
                        'message' => $response['message'], 
                         'code'=>$response['code']
                        ]
                       ];
        }else{
			$message  = "Ok";
			$code     = 200;	
            $output = [ 'response' => $response,                                
                        'metadata' => [
							'message' => $message, 
                            'code'=>$code
                        ]
                      ];
        }
                    
		$this->response->removeHeader('Cache-Control');
		return $this->respond($output, 200);
             
    }

	public function statusantreanfarmasi()
    {
        // Get all the headers
        $this->api 	= new Api_model();
        $request 	= service('request');
        $key 		= getenv('TOKEN_SECRET');
        $header 	= $request->getHeader('x-token')->getValue();
        
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = $header;
        
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
           
        $response = [
            'id' => $decoded->uid,
            'username' => $decoded->username
        ];
        
		$kodebooking = $this->request->getVar('kodebooking');                             
                     
        $data_status = $this->api->postStatusAntrianFarmasi($kodebooking);
		
		$response = $data_status;
						 
                
		if($response['totalantrean'] == ""){
			$message  = "Kodebooking tidak ditemukan";
			$code     = 201;
            $output = ['metadata' => [
                        'message' => $message, 
                         'code'=>$code
                        ]
                       ];
        }else{
			$message  = "Ok";
			$code     = 200;	
            $output = [ 'response' => $response,                                
                        'metadata' => [
							'message' => $message, 
                            'code'=>$code
                        ]
                      ];
        }
                    
			$this->response->removeHeader('Cache-Control');
			return $this->respond($output, 200);
             
    }



}