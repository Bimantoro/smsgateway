<?php 
	require_once("lib/nusoap.php");
	include "koneksi_db.php";

	//membuat server nusoap (server sms)
	$server = new nusoap_server();

	//registrasi interface
	$server->register('psn_masuk_baru');
	$server->register('psn_masuk');
	$server->register('baca_psn_masuk');
	$server->register('baca_psn_masuk_responder');
	$server->register('psn_keluar');
	$server->register('baca_psn_keluar');
	$server->register('psn_terkirim');
	$server->register('baca_psn_terkirim');
	$server->register('kirim_psn');
	$server->register('hapus_psn_masuk');
	$server->register('hapus_psn_keluar');
	$server->register('hapus_psn_terkirim');
	$server->register('about');
	$server->register('count_sms');
	$server->register('grafik');

	//membuat fungsi interface yang sudah diregistrasi	
	function psn_masuk(){
		$sql		= mysql_query("SELECT *
						 FROM v_inbox ORDER BY ReceivingDateTime DESC;");
		while($data=mysql_fetch_array($sql)){
		$hasil[]	= array(	'id'		=>$data['ID'],
								'tgl_masuk'	=>$data['ReceivingDateTime'],
								'pengirim'	=>$data['SenderNumber'],
								'pesan'		=>$data['TextDecoded'],
								'status'	=>$data['Processed']);
			}
		return $hasil;
	}

	function baca_psn_masuk($id){
		$sql		= mysql_query("SELECT *
						 FROM v_inbox WHERE ID='".$id."';");
		$data		= mysql_fetch_array($sql);
		$hasil[]	= array(	'id'		=>$data['ID'],
								'tgl_masuk'	=>$data['ReceivingDateTime'],
								'pengirim'	=>$data['SenderNumber'],
								'pesan'		=>$data['TextDecoded'],
								'status'	=>$data['Processed']);
		$sql 		= mysql_query("UPDATE inbox SET Processed='true' WHERE ID='".$id."';");

		return $hasil;
	}

	function baca_psn_masuk_responder($id){
		$sql		= mysql_query("SELECT *
						 FROM v_inbox WHERE ID='".$id."';");
		$data		= mysql_fetch_array($sql);
		$hasil[]	= array(	'id'		=>$data['ID'],
								'tgl_masuk'	=>$data['ReceivingDateTime'],
								'pengirim'	=>$data['SenderNumber'],
								'pesan'		=>$data['TextDecoded'],
								'status'	=>$data['Processed']);
		//$sql 		= mysql_query("UPDATE inbox SET Processed='true' WHERE ID='".$id."';");

		return $hasil;
	}

	function psn_masuk_baru(){
		$sql		= mysql_query("SELECT *
						 FROM v_inbox WHERE Processed='false';");
		while($data=mysql_fetch_array($sql)){
		$hasil[]	= array(	'id'		=>$data['ID'],
								'tgl_masuk'	=>$data['ReceivingDateTime'],
								'pengirim'	=>$data['SenderNumber'],
								'pesan'		=>$data['TextDecoded']);
			}
		return $hasil;
	}

	function psn_keluar(){
		$sql		= mysql_query("SELECT *
						FROM v_outbox ORDER BY InsertIntoDB DESC;");
		while($data=mysql_fetch_array($sql)){
		$hasil[]	= array(	'id'			=>$data['ID'],
								'tgl_dikirim'	=>$data['InsertIntoDB'],
								'tujuan'		=>$data['DestinationNumber'],
								'pesan'			=>$data['TextDecoded']);
			}
		return $hasil;
	}

	function baca_psn_keluar($id){
		$sql		= mysql_query("SELECT *
						FROM v_outbox WHERE ID='".$id."';");
		$data 		= mysql_fetch_array($sql);
		$hasil[]	= array(	'id'			=>$data['ID'],
								'tgl_dikirim'	=>$data['InsertIntoDB'],
								'tujuan'		=>$data['DestinationNumber'],
								'pesan'			=>$data['TextDecoded']);
		return $hasil;
	}


	function psn_terkirim(){
		$sql		= mysql_query("SELECT *
						FROM v_sentitems ORDER BY SendingDateTime DESC;");
		while($data=mysql_fetch_array($sql)){
		$hasil[]	= array(	'id'			=>$data['ID'],
								'tgl_terkirim'	=>$data['SendingDateTime'],
								'tujuan'		=>$data['DestinationNumber'],
								'status'		=>$data['Status'],
								'pesan'			=>$data['TextDecoded']);
			}
		return $hasil;
	}

	function baca_psn_terkirim($id){
		$sql		= mysql_query("SELECT *
						FROM v_sentitems WHERE ID='".$id."';");
		$data 		= mysql_fetch_array($sql);
		$hasil[]	= array(	'id'			=>$data['ID'],
								'tgl_terkirim'	=>$data['SendingDateTime'],
								'tujuan'		=>$data['DestinationNumber'],
								'pesan'			=>$data['TextDecoded']);
		return $hasil;
	}

	function kirim_psn($no, $pesan){

		$jumlah_karakter = strlen($pesan);
		if($jumlah_karakter<160){
			$sql		= mysql_query("INSERT INTO v_outbox (TextDecoded, DestinationNumber) value('".$pesan."', '".$no."');");
			if(!$sql){
			$hasil	= "pesan gagal dikirim !";
			}else{
				$hasil	= "pesan berhasil dikirim !";
			}

		}else{

			//salah satu solusi untuk mengirim pesan dengan modem biasa agar pesan tetap terkirim semua dan tidak hilang di saat transmisi

			$jmlSMS = ceil(strlen($pesan)/153);
 
			// memecah pesan asli
			$pecah  = str_split($pesan, 153);

			for($i=0; $i<$jmlSMS; $i++){
				$msg 		= $pecah[$i]; 
				$sql		= mysql_query("INSERT INTO v_outbox (TextDecoded, DestinationNumber) value('".$msg."', '".$no."');");
				sleep(2);
			}
			 
			if(!$sql){
			$hasil	= "pesan gagal dikirim !";
			}else{
				$hasil	= "pesan berhasil dikirim !";
			}

			//algoritma untuk mengiri pesan dengan modem wavecome fastrack

			// proses untuk mendapatkan ID record yang akan disisipkan ke tabel OUTBOX 		 
			// $query = "SHOW TABLE STATUS LIKE 'outbox'";
			// $hasil = mysql_query($query);
			// $data  = mysql_fetch_array($hasil);
			// $newID = $data['Auto_increment'];
			 
			// // proses penyimpanan ke tabel mysql untuk setiap pecahan		 
			// for ($i=1; $i<=$jmlSMS; $i++)
			// {
			//    // membuat UDH untuk setiap pecahan, sesuai urutannya
			//    $udh = "050003A7".sprintf("%02s", $jmlSMS).sprintf("%02s", $i);
			 
			//    // membaca text setiap pecahan
			//    $msg = $pecah[$i-1];
			 
			//    if ($i == 1) 
			//    {
			//       // jika merupakan pecahan pertama, maka masukkan ke tabel OUTBOX
			//       $query = "INSERT INTO outbox (DestinationNumber, UDH, TextDecoded, ID, MultiPart)
			//                 VALUES ('".$no."', '".$udh."', '".$msg."', '$newID', 'true')";
			//    }
			//    else 
			//    {
			//       // jika bukan merupakan pecahan pertama, simpan ke tabel OUTBOX_MULTIPART
			//       $query = "INSERT INTO outbox_multipart(UDH, TextDecoded, ID, SequencePosition)
			//                 VALUES ('".$udh."', '".$msg."', '".$newID."', '".$i."')";			
			//    }
			 
			//    // jalankan query							  
			//    mysql_query($query);
			//    if($query){
			//    		$hasil="sukses";
			//    }else{
			//    		$hasil="failed";
			//    }

			// }

			}		

		return $hasil;
	}

	function hapus_psn_masuk($id){
		$sql		= mysql_query("DELETE FROM v_inbox WHERE ID='".$id."';");

		if(!$sql){
			$hasil	= "pesan gagal dihapus !";
		}else{
			$hasil	= "pesan berhasil dihapus !";
		}

		return $hasil;
	}

	function hapus_psn_keluar($id){
		$sql		= mysql_query("DELETE FROM v_outbox WHERE ID='".$id."';");

		if(!$sql){
			$hasil	= "pesan gagal dihapus !";
		}else{
			$hasil	= "pesan berhasil dihapus !";
		}

		return $hasil;
	}


	function hapus_psn_terkirim($id){
		$sql		= mysql_query("DELETE FROM v_sentitems WHERE ID='".$id."';");

		if(!$sql){
			$hasil	= "pesan gagal dihapus !";
		}else{
			$hasil	= "pesan berhasil dihapus !";
		}

		return $hasil;
	}

	function about(){
		$sql		= mysql_query("SELECT * FROM phones ORDER BY UpdatedInDB DESC LIMIT 1");

		$data 		= mysql_fetch_array($sql);
		$hasil[]	= array(	'ID'		=>$data['ID'],
								'IMEI'		=>$data['IMEI'],
								'server'	=>$data['Client'],
								'Signal'	=>$data['Signal'],
								'Sent'		=>$data['Sent'],
								'Received'	=>$data['Received'],
								'Update'	=>$data['UpdatedInDB'],
								'Battery'	=>$data['Battery']);
		return $hasil;


	}

	function count_sms(){
		$inbox_baru_sql	= mysql_query("SELECT ID FROM inbox WHERE Processed='false';");
		$inbox_sql 		= mysql_query("SELECT ID FROM inbox;");
		$outbox_sql		= mysql_query("SELECT ID FROM outbox;");
		$sentitems_sql	= mysql_query("SELECT ID FROM sentitems;");

		$jmlh_inbox_baru	= mysql_num_rows($inbox_baru_sql);
		$jmlh_inbox 		= mysql_num_rows($inbox_sql);
		$jmlh_outbox		= mysql_num_rows($outbox_sql);
		$jmlh_sent			= mysql_num_rows($sentitems_sql);

		$hasil[]	= array('baru'		=> $jmlh_inbox_baru,
							'inbox'		=> $jmlh_inbox,
							'outbox'	=> $jmlh_outbox,
							'sent'		=> $jmlh_sent);

		return $hasil;
	}

	function grafik(){
		$tahun = date("Y");
		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='01') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_jan	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='02') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_feb	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='03') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_mar	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='04') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_apr	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='05') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_mei	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='06') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_jun	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='07') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_jul	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='08') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_aug	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='09') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_sep	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='10') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_okt	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='11') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_nov	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM inbox WHERE (MONTH(ReceivingDateTime)='12') AND (YEAR(ReceivingDateTime)='".$tahun."');");
		$masuk_des	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='01') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_jan	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='02') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_feb	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='03') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_mar	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='04') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_apr	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='05') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_mei	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='06') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_jun	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='07') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_jul	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='08') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_aug	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='09') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_sep	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='10') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_okt	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='11') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_nov	= mysql_num_rows($query);

		$query = mysql_query("SELECT ID FROM sentitems WHERE (MONTH(SendingDateTime)='12') AND (YEAR(SendingDateTime)='".$tahun."');");
		$keluar_des	= mysql_num_rows($query);

		$hasil[]	= array('msk_jan'	=> $masuk_jan,
							'msk_feb'	=> $masuk_feb,
							'msk_mar'	=> $masuk_mar,
							'msk_apr'	=> $masuk_apr,
							'msk_mei'	=> $masuk_mei,
							'msk_jun'	=> $masuk_jun,
							'msk_jul'	=> $masuk_jul,
							'msk_aug'	=> $masuk_aug,
							'msk_sep'	=> $masuk_sep,
							'msk_okt'	=> $masuk_okt,
							'msk_nov'	=> $masuk_nov,
							'msk_des'	=> $masuk_des,

							'klr_jan'	=> $keluar_jan,
							'klr_feb'	=> $keluar_feb,
							'klr_mar'	=> $keluar_mar,
							'klr_apr'	=> $keluar_apr,
							'klr_mei'	=> $keluar_mei,
							'klr_jun'	=> $keluar_jun,
							'klr_jul'	=> $keluar_jul,
							'klr_aug'	=> $keluar_aug,
							'klr_sep'	=> $keluar_sep,
							'klr_okt'	=> $keluar_okt,
							'klr_nov'	=> $keluar_nov,
							'klr_des'	=> $keluar_des);

		return $hasil;


	}

	$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
	$server->service($HTTP_RAW_POST_DATA);
 ?>