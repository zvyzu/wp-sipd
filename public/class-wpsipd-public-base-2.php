<?php

require_once WPSIPD_PLUGIN_PATH."/public/class-wpsipd-public-base-3.php";

class Wpsipd_Public_Base_2 extends Wpsipd_Public_Base_3
{
	public function monitoring_rup($atts)
	{
		// untuk disable render shortcode di halaman edit page/post
		if(!empty($_GET) && !empty($_GET['post'])){
			return '';
		}
		
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/wpsipd-public-monitoring-rup.php';
	}

	public function get_data_monitoring_rup(){
		global $wpdb;
		$return = array(
			'status' => 'success',
			'data'	=> array()
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran']) && !empty($_POST['id_lokasi'])){
					$params = $columns = $totalRecords = array();
					$params = $_REQUEST;
					$columns = array(
						0 => 'id_skpd',
						1 => 'nama_skpd'
					);

					$where_sirup = $where_sipd = $where_data_unit = $sqlRec ="";

					// check search value exist
					if( !empty($params['search']['value']) ) {
						$where_data_unit .=" AND ( nama_skpd LIKE ".$wpdb->prepare('%s', "%".$params['search']['value']."%").")";  
					}

					$sqlTotDataUnit = "SELECT 
											count(*) as jml 
										FROM 
											`data_unit`
										where 
											tahun_anggaran=".$_POST['tahun_anggaran']."
											and is_skpd=1  
											and active=1".$where_data_unit;
					$totalRecords = $wpdb->get_results($sqlTotDataUnit, ARRAY_A);
					$totalRecords = $totalRecords[0]['jml'];

					$sqlRec = 'SELECT 
									* 
								from 
									data_unit 
								where 
									tahun_anggaran='.$_POST['tahun_anggaran'].'
									and is_skpd=1  
									and active=1'.$where_data_unit;
					$sqlRec .=  " ORDER BY ". $columns[$params['order'][0]['column']]."   ".$params['order'][0]['dir']."  LIMIT ".$params['start']." ,".$params['length']." ";

					$skpd = $wpdb->get_results($sqlRec, ARRAY_A);

					foreach ($skpd as $k => $opd) {
						$id_skpd_sirup = get_option('_crb_unit_sirup_'.$opd['id_skpd']);

						$where_sipd = '
							tahun_anggaran='.$_POST['tahun_anggaran'].' 
								and active=1 
								and id_skpd='.$opd['id_skpd'].'
						';
						$data_total_pagu_sipd = $wpdb->get_row('
							select 
								sum(pagu) as total_sipd
							from 
								data_sub_keg_bl 
							where 
								'.$where_sipd
						, ARRAY_A);
						$skpd[$k]['total_pagu_sipd'] = (!empty($data_total_pagu_sipd['total_sipd'])) ? number_format($data_total_pagu_sipd['total_sipd'],0,",",".") : '-' ;

						$where_non_pengadaan = 'a.kode_akun LIKE "5.1.01.03%"
												and a.active=1
												and b.id_skpd='.$opd['id_skpd'].' 
												and b.tahun_anggaran='.$_POST['tahun_anggaran'].' 
												and b.active=1 
												';
						$data_total_pagu_non_pengadaan = $wpdb->get_row('
							select 
								sum(a.total_harga) as total_non_pengadaan
							from 
								data_rka as a 
							join 
								data_sub_keg_bl as b 
								on a.kode_sbl = b.kode_sbl
							where 
								'.$where_non_pengadaan
						, ARRAY_A);
						$skpd[$k]['total_pagu_non_pengadaan'] = (!empty($data_total_pagu_non_pengadaan['total_non_pengadaan'])) ? number_format($data_total_pagu_non_pengadaan['total_non_pengadaan'],0,",",".") : '-' ;

						$where_sirup = '
							tahun_anggaran='.$_POST['tahun_anggaran'].'
							and active=1
							and idlokasi='.$_POST['id_lokasi'].'
							and satuanKerja="'.$opd['nama_skpd'].'"
						';
						$data_total_pagu_sirup = $wpdb->get_row('
							select 
								sum(pagu) as total_sirup
							from 
								data_sirup_lokal
							where 
								'.$where_sirup
						, ARRAY_A);
						$skpd[$k]['total_pagu_sirup'] = (!empty($data_total_pagu_sirup['total_sirup'])) ? number_format($data_total_pagu_sirup['total_sirup'],0,",",".") : '-';

						$total_paket_penyedia_sirup = $wpdb->get_results($wpdb->prepare('
							SELECT
								COUNT(DISTINCT paket) as total_paket
							FROM 
								data_sirup_lokal
							WHERE
								active=1
								and idlokasi=%d
								and satuanKerja=%s
								and tahun_anggaran=%d',
								$_POST['id_lokasi'],
								$opd['nama_skpd'],
								$_POST['tahun_anggaran'])
								,ARRAY_A);
						$skpd[$k]['cek_query'] = $wpdb->last_query;
						$skpd[$k]['total_paket_penyedia_sirup'] = (!empty($total_paket_penyedia_sirup[0]['total_paket'])) ? $total_paket_penyedia_sirup[0]['total_paket'] : '-';

						$total_pagu_rekap_penyedia_sirup = $wpdb->get_results($wpdb->prepare('
							SELECT
								pagu_penyedia
							FROM
								data_skpd_sirup
							WHERE
								active=1
								and id_satuan_kerja=%d
								and tahun_anggaran=%d',
								$id_skpd_sirup,
								$_POST['tahun_anggaran'] )
							, ARRAY_A);

						$skpd[$k]['total_pagu_rekap_penyedia_sirup'] = (!empty($total_pagu_rekap_penyedia_sirup[0]['pagu_penyedia'])) ? number_format($total_pagu_rekap_penyedia_sirup[0]['pagu_penyedia'] * 1000000,0,",",".") : '-';

						$total_paket_rekap_penyedia_sirup = $wpdb->get_results($wpdb->prepare('
							SELECT
								paket_penyedia
							FROM
								data_skpd_sirup
							WHERE
								active=1
								and id_satuan_kerja=%d
								and tahun_anggaran=%d',
								$id_skpd_sirup,
								$_POST['tahun_anggaran']
						),ARRAY_A);
						$skpd[$k]['total_paket_rekap_penyedia_sirup'] = (!empty($total_paket_rekap_penyedia_sirup[0]['paket_penyedia'])) ? $total_paket_rekap_penyedia_sirup[0]['paket_penyedia'] : '-';

						$data_total_pagu_rekap_sirup = $wpdb->get_results($wpdb->prepare('
							SELECT 
								total_pagu
							FROM 
								data_skpd_sirup
							WHERE 
								active=1
								and id_satuan_kerja=%d
								and tahun_anggaran=%d',
								$id_skpd_sirup,
								$_POST['tahun_anggaran'] )
						, ARRAY_A);

						$skpd[$k]['total_pagu_rekap_sirup'] = (!empty($data_total_pagu_rekap_sirup[0]['total_pagu'])) ? number_format($data_total_pagu_rekap_sirup[0]['total_pagu'] * 1000000,0,",",".") : '-';

						$skpd[$k]['selisih_pagu'] = $data_total_pagu_sipd['total_sipd'] - $data_total_pagu_non_pengadaan['total_non_pengadaan'] - $data_total_pagu_sirup['total_sirup'];
						$skpd[$k]['selisih_pagu'] = number_format($skpd[$k]['selisih_pagu'],0,",",".");
						$skpd[$k]['keterangan'] = '-';
					}


					$json_data = array(
						"draw"            => intval( $params['draw'] ),  
						"recordsTotal"    => intval( $totalRecords ), 
						"recordsFiltered" => intval( $totalRecords ),
						"data"            => $skpd
					);

					die(json_encode($json_data));
				}else{
					$return = array(
						'status' => 'error',
						'message'	=> 'Harap diisi semua,API setting tidak boleh ada yang kosong!'
					);
				}
			}else{
				$return = array(
					'status' => 'error',
					'message'	=> 'Api Key tidak sesuai!'
				);
			}
		}else{
			$return = array(
				'status' => 'error',
				'message'	=> 'Format tidak sesuai!'
			);
		}
		die(json_encode($return));
	}

		/** Submit lock data jadwal RPJPD */
	public function submit_lock_schedule_rpjpd(){
		global $wpdb;
		$return = array(
			'status' => 'success',
			'data'	=> array()
		);

		$user_id = um_user( 'ID' );
		$user_meta = get_userdata($user_id);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['id_jadwal_lokal'])){
					if(in_array("administrator", $user_meta->roles)){
						$id_jadwal_lokal= trim(htmlspecialchars($_POST['id_jadwal_lokal']));

						$data_this_id 	= $wpdb->get_results($wpdb->prepare('SELECT * FROM data_jadwal_lokal WHERE id_jadwal_lokal = %d',$id_jadwal_lokal), ARRAY_A);

						$timezone = get_option('timezone_string');
						if(preg_match("/Asia/i", $timezone)){
							date_default_timezone_set($timezone);
						}else{
							$return = array(
								'status' => 'error',
								'message'	=> "Pengaturan timezone salah. Pilih salah satu kota di zona waktu yang sama dengan anda, antara lain:  \'Jakarta\',\'Makasar\',\'Jayapura\'",
							);
							die(json_encode($return));
						}

						$dateTime = new DateTime();
						$time_now = $dateTime->format('Y-m-d H:i:s');
						if(!empty($data_this_id[0])){
							if($time_now > $data_this_id[0]['waktu_awal']){
								$status_check = array(0,NULL,2);
								if(in_array($data_this_id[0]['status'],$status_check)){
									//lock data penjadwalan
									$wpdb->update('data_jadwal_lokal', array('waktu_akhir' => $time_now,'status' => 1), array(
										'id_jadwal_lokal'	=> $id_jadwal_lokal
									));

									$delete_lokal_history = $this->delete_data_lokal_history('data_rpjpd_visi', $data_this_id[0]['id_jadwal_lokal']);

									$columns_1 = array('visi_teks','update_at');
		
									$sql_backup_data_rpjpd_visi =  "INSERT INTO data_rpjpd_visi_history (".implode(', ', $columns_1).",id_jadwal,id_asli)
												SELECT ".implode(', ', $columns_1).", ".$data_this_id[0]['id_jadwal_lokal'].", id as id_asli
												FROM data_rpjpd_visi";

									$queryRecords1 = $wpdb->query($sql_backup_data_rpjpd_visi);

									$delete_lokal_history = $this->delete_data_lokal_history('data_rpjpd_misi', $data_this_id[0]['id_jadwal_lokal']);

									$columns_2 = array('id_visi','misi_teks','urut_misi','update_at');
		
									$sql_backup_data_rpjpd_misi =  "INSERT INTO data_rpjpd_misi_history (".implode(', ', $columns_2).",id_jadwal,id_asli)
												SELECT ".implode(', ', $columns_2).", ".$data_this_id[0]['id_jadwal_lokal'].", id as id_asli
												FROM data_rpjpd_misi";

									$queryRecords2 = $wpdb->query($sql_backup_data_rpjpd_misi);

									$delete_lokal_history = $this->delete_data_lokal_history('data_rpjpd_sasaran', $data_this_id[0]['id_jadwal_lokal']);

									$columns_3 = array('id_misi','saspok_teks','urut_saspok','update_at');
		
									$sql_backup_data_rpjpd_sasaran =  "INSERT INTO data_rpjpd_sasaran_history (".implode(', ', $columns_3).",id_jadwal,id_asli)
												SELECT ".implode(', ', $columns_3).", ".$data_this_id[0]['id_jadwal_lokal'].", id as id_asli
												FROM data_rpjpd_sasaran";

									$queryRecords3 = $wpdb->query($sql_backup_data_rpjpd_sasaran);

									$delete_lokal_history = $this->delete_data_lokal_history('data_rpjpd_kebijakan', $data_this_id[0]['id_jadwal_lokal']);

									$columns_4 = array('id_saspok','kebijakan_teks','urut_kebijakan','update_at');
		
									$sql_backup_data_rpjpd_kebijakan =  "INSERT INTO data_rpjpd_kebijakan_history (".implode(', ', $columns_4).",id_jadwal,id_asli)
												SELECT ".implode(', ', $columns_4).", ".$data_this_id[0]['id_jadwal_lokal'].", id as id_asli
												FROM data_rpjpd_kebijakan";

									$queryRecords4 = $wpdb->query($sql_backup_data_rpjpd_kebijakan);

									$delete_lokal_history = $this->delete_data_lokal_history('data_rpjpd_isu', $data_this_id[0]['id_jadwal_lokal']);

									$columns_5 = array('id_kebijakan','isu_teks','urut_isu','update_at',);
		
									$sql_backup_data_rpjpd_isu =  "INSERT INTO data_rpjpd_isu_history (".implode(', ', $columns_5).",id_jadwal,id_asli)
												SELECT ".implode(', ', $columns_5).", ".$data_this_id[0]['id_jadwal_lokal'].", id as id_asli
												FROM data_rpjpd_isu";

									$queryRecords5 = $wpdb->query($sql_backup_data_rpjpd_isu);

									$return = array(
										'status' => 'success',
										'message'	=> 'Berhasil!',
										'data_input' => $queryRecords1
									);
								}else{
									$return = array(
										'status' => 'error',
										'message'	=> "User tidak diijinkan!\nData sudah dikunci!",
									);
								}
							}else{
								$return = array(
									'status' => 'error',
									'message'	=> "Penjadwalan belum dimulai!",
								);
							}
						}else{
							$return = array(
								'status' => 'error',
								'message'	=> "Penjadwalan tidak ditemukan!",
							);
						}
					}else{
						$return = array(
							'status' => 'error',
							'message'	=> "User tidak diijinkan!",
						);
					}
				}else{
					$return = array(
						'status' => 'error',
						'message'	=> 'Harap diisi semua,tidak boleh ada yang kosong!'
					);
				}
			}else{
				$return = array(
					'status' => 'error',
					'message'	=> 'Api Key tidak sesuai!'
				);
			}
		}else{
			$return = array(
				'status' => 'error',
				'message'	=> 'Format tidak sesuai!'
			);
		}
		die(json_encode($return));
	}

	public function input_renja($atts)
	{
		// untuk disable render shortcode di halaman edit page/post
		if(!empty($_GET) && !empty($_GET['post'])){
			return '';
		}
		
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/wpsipd-public-input-renja.php';
	}

	public function monitor_rak($atts)
	{
		// untuk disable render shortcode di halaman edit page/post
		if(!empty($_GET) && !empty($_GET['post'])){
			return '';
		}
		if(!empty($atts['id_skpd'])){
			require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/wpsipd-public-monitor-rak.php';
		}
	}

	public function monitor_rkpd_renja($atts)
	{
		// untuk disable render shortcode di halaman edit page/post
		if(!empty($_GET) && !empty($_GET['post'])){
			return '';
		}

		$input = shortcode_atts( array(
			'id_skpd' => false,
			'tahun_anggaran' => '2022',
		), $atts );

		require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/wpsipd-public-monitor-rkpd-renja.php';
	}

	public function get_sub_unit_by_id(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'action'	=> $_POST['action'],
			'data'	=> array()
		);
		if (!empty($_POST)) {
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				$table_content = '<option value="">Pilih Perangkat Daerah</option>';
				if(!empty($_POST['id_skpd'])){
					$ret['data'] = $wpdb->get_results(
						$wpdb->prepare("
						SELECT 
							id_skpd,
							nama_skpd
						from data_unit
						where id_skpd=%d
							AND tahun_anggaran=%d
							AND active=1", $_POST['id_skpd'], $_POST['tahun_anggaran']),
						ARRAY_A
					);
					if(!empty($ret['data']) && $ret['data'][0]['isskpd'] == 0){
						$ret['data'] = $wpdb->get_results(
							$wpdb->prepare("
							SELECT 
								id_skpd,
								nama_skpd
							from data_unit
							where id_skpd=%d
								AND tahun_anggaran=%d
								AND active=1
							order by id_skpd ASC", $ret['data'][0]['id_skpd'], $_POST['tahun_anggaran']),
							ARRAY_A
						);
					}else if(!empty($ret['data'])){
						$ret['data'] = $wpdb->get_results(
							$wpdb->prepare("
							SELECT 
								id_skpd,
								nama_skpd
							from data_unit
							where idinduk=%d
								AND tahun_anggaran=%d
								AND active=1
							order by id_skpd ASC", $ret['data'][0]['id_skpd'], $_POST['tahun_anggaran']),
							ARRAY_A
						);
					}
			    	foreach ($ret['data'] as $key => $value) {
			    		$table_content .= '<option value="'.$value['id_skpd'].'">'.$value['nama_skpd'].'</option>';
			    	}
					$ret['table_content'] = $table_content;
					$ret['query'] = $wpdb->last_query;
					$ret['id_skpd'] = $_POST['id_skpd'];
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'ID SKPD tidak boleh kosong!';
				}
			} else {
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		} else {
			$ret['status'] = 'error';
			$ret['message'] = 'Format Salah!';
		}
		die(json_encode($ret));
	}

	public function run_sql_data_master(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'action'	=> $_POST['action'],
			'data'		=> array(),
			'status_insert' => array(),
			'cek_query' => array()
		);
		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['nama_tabel'] && !empty($_POST['tahun_anggaran']))){
					$nama_tabel = $_POST['nama_tabel'];
					if($nama_tabel === 'data_rumus_indikator'){
						$ret['data'] = get_option('data_master_rumus_indikator');
						if(!empty($ret['data'])){
							foreach($ret['data'] as $val_data){
								$data_rumus_indikator = $wpdb->get_results($wpdb->prepare('
											SELECT *
											FROM data_rumus_indikator
											WHERE id=%d
											AND rumus=%s
											AND tahun_anggaran=%d',
											$val_data['id'],$val_data['rumus'],$_POST['tahun_anggaran']), ARRAY_A);
								if(empty($data_rumus_indikator)){
									$ret['status_insert'] = $wpdb->insert($nama_tabel, array('id'=>$val_data['id'],'rumus'=>$val_data['rumus'],'keterangan'=>$val_data['keterangan'],'user'=>$val_data['user'],'active'=>$val_data['active'],'update_at'=>current_time('mysql'),'tahun_anggaran'=>$_POST['tahun_anggaran']));
								}
							}
						}
					}else if($nama_tabel === 'data_label_komponen'){
						$ret['data'] = get_option('data_master_label_komponen');
						if(!empty($ret['data'])){
							foreach($ret['data'] as $val_data){
								$data_rumus_indikator = $wpdb->get_results($wpdb->prepare('
											SELECT *
											FROM data_label_komponen
											WHERE nama=%s
											AND tahun_anggaran=%d',
											$val_data['nama'],$_POST['tahun_anggaran']), ARRAY_A);
								if(empty($data_rumus_indikator)){
									$ret['status_insert'] = $wpdb->insert($nama_tabel, array('nama'=>$val_data['nama'],'keterangan'=>$val_data['keterangan'],'id_skpd'=>$val_data['id_skpd'],'user'=>$val_data['user'],'active'=>$val_data['active'],'update_at'=>current_time('mysql'),'tahun_anggaran'=>$_POST['tahun_anggaran']));
								}
							}
						}
					}else if($nama_tabel === 'data_tipe_perencanaan'){
						$ret['data'] = get_option('data_master_tipe_perencanaan');
						if(!empty($ret['data'])){
							foreach($ret['data'] as $val_data){
								$data_tipe_perencanaan = $wpdb->get_results($wpdb->prepare('
															SELECT *
															FROM data_tipe_perencanaan
															WHERE id=%d',
															$val_data['id']), ARRAY_A);
								if(empty($data_tipe_perencanaan)){
									$ret['status_insert'] = $wpdb->insert($nama_tabel, array('nama_tipe'=>$val_data['nama_tipe'],'keterangan_tipe'=>$val_data['keterangan_tipe'],'lama_pelaksanaan'=>$val_data['lama_pelaksanaan']));
								}else{
									$ret['status_insert'] = $wpdb->update($nama_tabel, array('nama_tipe'=>$val_data['nama_tipe'],'keterangan_tipe'=>$val_data['keterangan_tipe'],'lama_pelaksanaan'=>$val_data['lama_pelaksanaan']), array('id'=>$val_data['id']));
								}
							}
						}
					}
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Data ada  yang kosong!';
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}
		die(json_encode($ret));
	}

	public function get_data_program_renstra(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'action'	=> $_POST['action'],
			'data'		=> array(),
		);
		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {

				$sasaran_parent = "data_rpd_sasaran_lokal_history";
				$program_parent = "data_rpd_program_lokal";

				if(!empty($_POST['type']) && $_POST['type']=='rpjm'){
					$sasaran_parent = "data_rpjmd_sasaran_lokal_history";
					$program_parent = "data_rpjmd_program_lokal";
				}

				$data_sasaran_rpd_history = $wpdb->get_results($wpdb->prepare(
					'SELECT DISTINCT id_unik
					FROM '.$sasaran_parent.'
					WHERE active=%d',
					1)
					,ARRAY_A);

				$rpd_history = array();
				foreach($data_sasaran_rpd_history as $v_rpd_history){
					array_push($rpd_history, "'".$v_rpd_history['id_unik']."'");
				}
				$rpd_history = implode(",",$rpd_history);

				//mencari data program renstra yang terhubung ke rpd/rpjm
				$data_renstra_program = $wpdb->get_results($wpdb->prepare(
					'SELECT
						p.*,
						t.kode_sasaran_rpjm
					FROM data_renstra_program_lokal p
					INNER JOIN data_renstra_tujuan_lokal t
					ON p.kode_tujuan = t.id_unik
					WHERE 
						p.program_lock=%d AND
						p.status=%d AND
						p.active=%d AND
						t.kode_sasaran_rpjm IN ('.$rpd_history.')',
					0,1,1)
					,ARRAY_A);

				$wpdb->query( $wpdb->prepare(
					'UPDATE '.$program_parent.'
						SET active = %d',
						0)
				);

				//update dan insert data program dan indikator ke rpd
				foreach($data_renstra_program as $v_renstra){
					$nama_program = explode(" ", $v_renstra['nama_program']);
					array_shift($nama_program);
					$nama_program = implode(" ", $nama_program);

					$data_kirim = array(
							'kode_sasaran' 	=> $v_renstra['kode_sasaran_rpjm'],
							'nama_program' 	=> $nama_program,
							'id_program'	=> $v_renstra['id_program'],
							'update_at' 	=> current_time('mysql'),
							'active'		=> 1
						);

					//simpan program
					if(empty($v_renstra['id_unik_indikator'])){
						$data_rpd_same_program = $wpdb->get_results($wpdb->prepare(
							'SELECT *
							FROM '.$program_parent.' 
							WHERE 
								nama_program=%s AND 
								kode_sasaran=%s AND
								id_unik_indikator IS NULL',
							$nama_program,$v_renstra['kode_sasaran_rpjm'])
							,ARRAY_A);

						if(!empty($data_rpd_same_program)){
							$wpdb->update($program_parent, $data_kirim, array('id_unik' => $data_rpd_same_program[0]['id_unik'], 'id_unik_indikator' => NULL));
						}else if(empty($data_rpd_same_program) && empty($v_renstra['id_unik_indikator'])){
							$data_kirim['id_unik'] = $this->generateRandomString(5);
							$wpdb->insert($program_parent, $data_kirim);
						}
					}else{
						//simpan indikator
						$data_rpd_same_indikator = $wpdb->get_results($wpdb->prepare(
							'SELECT *
							FROM '.$program_parent.' 
							WHERE 
								nama_program=%s AND 
								kode_sasaran=%s AND
								indikator=%s AND
								kode_skpd=%s',
							$nama_program, $v_renstra['kode_sasaran_rpjm'], $v_renstra['indikator'], $v_renstra['kode_skpd'])
							,ARRAY_A);

						$data_kirim['indikator'] = $v_renstra['indikator'];
						$data_kirim['target_awal'] = $v_renstra['target_awal'];
						$data_kirim['target_1'] = $v_renstra['target_1'];
						$data_kirim['target_2'] = $v_renstra['target_2'];
						$data_kirim['target_3'] = $v_renstra['target_3'];
						$data_kirim['target_4'] = $v_renstra['target_4'];
						$data_kirim['target_5'] = $v_renstra['target_5'];
						$data_kirim['pagu_1'] = $v_renstra['pagu_1'];
						$data_kirim['pagu_2'] = $v_renstra['pagu_2'];
						$data_kirim['pagu_3'] = $v_renstra['pagu_3'];
						$data_kirim['pagu_4'] = $v_renstra['pagu_4'];
						$data_kirim['pagu_5'] = $v_renstra['pagu_5'];
						$data_kirim['target_akhir'] = $v_renstra['target_akhir'];
						$data_kirim['satuan'] = $v_renstra['satuan'];
						$data_kirim['kode_skpd'] = $v_renstra['kode_skpd'];
						$data_kirim['nama_skpd'] = $v_renstra['nama_skpd'];
						$data_kirim['id_unit'] = $v_renstra['id_unit'];

						if(!empty($data_rpd_same_indikator)){
							$data_kirim['id_unik'] = $data_rpd_same_indikator[0]['id_unik'];
							$wpdb->update($program_parent, $data_kirim, array('id_unik_indikator' => $data_rpd_same_indikator[0]['id_unik_indikator']));
						}else{
							//untuk mendapatkan id unik program
							$data_program = $wpdb->get_results($wpdb->prepare(
								'SELECT id_unik
								FROM '.$program_parent.' 
								WHERE 
									nama_program=%s AND 
									kode_sasaran=%s AND
									id_unik_indikator IS NULL',
								$nama_program,$v_renstra['kode_sasaran_rpjm'])
								,ARRAY_A);

							if(!empty($data_program)){
								$data_kirim['id_unik'] = $data_program[0]['id_unik'];
								$data_kirim['id_unik_indikator'] = $this->generateRandomString(5); //id_unik indikator
								$wpdb->insert($program_parent, $data_kirim);
							}
						}
					}
				}
				$ret['data'] = $data_renstra_program;				
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}
		die(json_encode($ret));
	}

	public function view_rekap_rpd(){
		global $wpdb;
        $ret = array(
            'status'    => 'success',
            'message'   => 'Berhasil generate laporan renstra!'
        );

		if (!empty($_POST)) {
            if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( WPSIPD_API_KEY )) {
				if(!empty($_POST['id_jadwal_lokal'])){
					$id_jadwal_lokal 	= $_POST['id_jadwal_lokal'];
					
					$data_jadwal_lokal = $wpdb->get_results($wpdb->prepare(
						'SELECT *
						FROM data_jadwal_lokal
						WHERE id_jadwal_lokal=%d',
						$id_jadwal_lokal),
					ARRAY_A);

					if(empty($data_jadwal_lokal)){
						$ret = array(
							'status' => 'error',
							'message'   => 'Data tidak ditemukan!'
						);

						die(json_encode($ret));
					}

					$namaJadwal = $data_jadwal_lokal[0]['nama'];
					$tahun_anggaran = $data_jadwal_lokal[0]['tahun_anggaran'];
					$lama_pelaksanaan	= $data_jadwal_lokal[0]['lama_pelaksanaan'];
					$awal_rpd = $tahun_anggaran;
					$akhir_rpd = $awal_rpd+$lama_pelaksanaan-1;
					$id_jadwal_rpjpd = $data_jadwal_lokal[0]['relasi_perencanaan'];	
					$nama_pemda = get_option('_crb_daerah');

					$bulan = date('m');
					$body_monev = '';

					$data_all = array(
						'data' => array()
					);

					$tujuan_ids = array();
					$sasaran_ids = array();
					$program_ids = array();
					$skpd_filter = array();

					$where_tabel = '';
					$where = '';
					$where_t = '';
					if($data_jadwal_lokal[0]['status'] == 1){
						$where_tabel = '_history';
						$where = ' AND id_jadwal='.$data_jadwal_lokal[0]['id_jadwal_lokal'];
						$where_t = ' AND t.id_jadwal='.$data_jadwal_lokal[0]['id_jadwal_lokal'];
					}

					$sql = "
						select 
							t.*,
							i.isu_teks 
						from data_rpd_tujuan_lokal".$where_tabel." t
						left join data_rpjpd_isu i on t.id_isu = i.id
						where 
							t.active=1
							".$where_t."
						order by t.no_urut asc
					";
					if(!empty($id_jadwal_rpjpd)){
						$sql = "
							select 
								t.*,
								i.isu_teks 
							from data_rpd_tujuan_lokal".$where_tabel." t
							left join data_rpjpd_isu_history i on t.id_isu = i.id_asli
							where 
								t.active=1
								".$where_t."
							order by t.no_urut asc
						";
					}
					$tujuan_all = $wpdb->get_results($sql, ARRAY_A);
					foreach ($tujuan_all as $tujuan) {
						if(empty($data_all['data'][$tujuan['id_unik']])){
							$data_all['data'][$tujuan['id_unik']] = array(
								'nama' => $tujuan['tujuan_teks'],
								'detail' => array(),
								'data' => array()
							);
							$tujuan_ids[$tujuan['id_unik']] = "'".$tujuan['id_unik']."'";
							$sql = $wpdb->prepare("
								select 
									* 
								from data_rpd_sasaran_lokal".$where_tabel."
								where kode_tujuan=%s
									and active=1
									".$where."
									order by sasaran_no_urut asc
							", $tujuan['id_unik']);
							$sasaran_all = $wpdb->get_results($sql, ARRAY_A);
							foreach ($sasaran_all as $sasaran) {
								if(empty($data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']])){
									$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']] = array(
										'nama' => $sasaran['sasaran_teks'],
										'detail' => array(),
										'data' => array()
									);
									$sasaran_ids[$sasaran['id_unik']] = "'".$sasaran['id_unik']."'";
									$sql = $wpdb->prepare("
										select 
											* 
										from data_rpd_program_lokal".$where_tabel."
										where kode_sasaran=%s
											and active=1
											".$where."
									", $sasaran['id_unik']);
									$program_all = $wpdb->get_results($sql, ARRAY_A);
									foreach ($program_all as $program) {
										$program_ids[$program['id_unik']] = "'".$program['id_unik']."'";
										if(empty($program['kode_skpd']) && empty($program['nama_skpd'])){
											$program['kode_skpd'] = '';
											$program['nama_skpd'] = 'Semua Perangkat Daerah';
										}
										$skpd_filter[$program['kode_skpd']] = $program['nama_skpd'];
										if(empty($data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']])){
											$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']] = array(
												'nama' => $program['nama_program'],
												'kode_skpd' => $program['kode_skpd'],
												'nama_skpd' => $program['nama_skpd'],
												'detail' => array(),
												'data' => array()
											);
										}
										$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['detail'][] = $program;
										if(
											!empty($program['id_unik_indikator']) 
											&& empty($data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['data'][$program['id_unik_indikator']])
										){
											$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['data'][$program['id_unik_indikator']] = array(
												'nama' => $program['indikator'],
												'data' => $program
											);
										}
									}
								}
								$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['detail'][] = $sasaran;
							}
						}
						$data_all['data'][$tujuan['id_unik']]['detail'][] = $tujuan;
					}

					// buat array data kosong
					if(empty($data_all['data']['tujuan_kosong'])){
						$data_all['data']['tujuan_kosong'] = array(
							'nama' => '<span style="color: red">kosong</span>',
							'detail' => array(
								array(
									'id_unik' => 'kosong',
									'isu_teks' => ''
								)
							),
							'data' => array()
						);
					}
					if(empty($data_all['data']['tujuan_kosong']['data']['sasaran_kosong'])){
						$data_all['data']['tujuan_kosong']['data']['sasaran_kosong'] = array(
							'nama' => '<span style="color: red">kosong</span>',
							'detail' => array(
								array(
									'id_unik' => 'kosong',
									'isu_teks' => ''
								)
							),
							'data' => array()
						);
					}

					// select tujuan yang belum terselect
					if(!empty($tujuan_ids)){
						$sql = "
							select 
								t.*,
								i.isu_teks 
							from data_rpd_tujuan_lokal".$where_tabel." t
							left join data_rpjpd_isu i on t.id_isu = i.id
							where t.id_unik not in (".implode(',', $tujuan_ids).")
								and t.active=1
								".$where_t."
						";
						if(!empty($id_jadwal_rpjpd)){
							$sql = "
								select 
									t.*,
									i.isu_teks 
								from data_rpd_tujuan_lokal".$where_tabel." t
								left join data_rpjpd_isu_history i on t.id_isu = i.id_asli
								where t.id_unik not in (".implode(',', $tujuan_ids).")
									and t.active=1
									".$where_t."
							";
						}
					}else{
						$sql = "
							select 
								t.*,
								i.isu_teks 
							from data_rpd_tujuan_lokal".$where_tabel." t
							left join data_rpjpd_isu i on t.id_isu = i.id
							where t.active=1
							".$where_t."
						";
						if(!empty($id_jadwal_rpjpd)){
							$sql = "
								select 
									t.*,
									i.isu_teks 
								from data_rpd_tujuan_lokal".$where_tabel." t
								left join data_rpjpd_isu_history i on t.id_isu = i.id_asli
								where t.active=1
								".$where_t."
							";
						}
					}
					$tujuan_all_kosong = $wpdb->get_results($sql, ARRAY_A);
					foreach ($tujuan_all_kosong as $tujuan) {
						if(empty($data_all['data'][$tujuan['id_unik']])){
							$data_all['data'][$tujuan['id_unik']] = array(
								'nama' => $tujuan['tujuan_teks'],
								'detail' => array(),
								'data' => array()
							);
						}
						$data_all['data'][$tujuan['id_unik']]['detail'][] = $tujuan;
						$sql = $wpdb->prepare("
							select 
								* 
							from data_rpd_sasaran_lokal".$where_tabel."
							where kode_tujuan=%s
								and active=1
								".$where."
						", $tujuan['id_unik']);
						$sasaran_all = $wpdb->get_results($sql, ARRAY_A);
						foreach ($sasaran_all as $sasaran) {
							$sasaran_ids[$sasaran['id_unik']] = "'".$sasaran['id_unik']."'";
							if(empty($data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']])){
								$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']] = array(
									'nama' => $sasaran['sasaran_teks'],
									'detail' => array(),
									'data' => array()
								);
							}
							$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['detail'][] = $sasaran;
							$sql = $wpdb->prepare("
								select 
									* 
								from data_rpd_program_lokal".$where_tabel."
								where kode_sasaran=%s
									and active=1
									".$where."
							", $sasaran['id_unik']);
							$program_all = $wpdb->get_results($sql, ARRAY_A);
							foreach ($program_all as $program) {
								$program_ids[$program['id_unik']] = "'".$program['id_unik']."'";
								if(empty($data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']])){
									$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']] = array(
										'nama' => $program['nama_program'],
										'kode_skpd' => $program['kode_skpd'],
										'nama_skpd' => $program['nama_skpd'],
										'detail' => array(),
										'data' => array()
									);
								}
								$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['detail'][] = $program;
								if(
									!empty($program['id_unik_indikator']) 
									&& empty($data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['data'][$program['id_unik_indikator']])
								){
									$data_all['data'][$tujuan['id_unik']]['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['data'][$program['id_unik_indikator']] = array(
										'nama' => $program['indikator'],
										'data' => $program
									);
								}
							}
						}
					}

					// select sasaran yang belum terselect
					if(!empty($sasaran_ids)){
						$sql = "
							select 
								* 
							from data_rpd_sasaran_lokal".$where_tabel."
							where id_unik not in (".implode(',', $sasaran_ids).")
								and active=1
								".$where."
						";
					}else{
						$sql = "
							select 
								* 
							from data_rpd_sasaran_lokal".$where_tabel."
							where active=1
							".$where."
						";
					}
					$sasaran_all_kosong = $wpdb->get_results($sql, ARRAY_A);
					foreach ($sasaran_all_kosong as $sasaran) {
						if(empty($data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']])){
							$data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']] = array(
								'nama' => $sasaran['sasaran_teks'],
								'detail' => array(),
								'data' => array()
							);
						}
						$data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']]['detail'][] = $sasaran;
						$sql = $wpdb->prepare("
							select 
								* 
							from data_rpd_program_lokal".$where_tabel."
							where kode_sasaran=%s
								and active=1
								".$where."
						", $sasaran['id_unik']);
						$program_all = $wpdb->get_results($sql, ARRAY_A);
						foreach ($program_all as $program) {
							$program_ids[$program['id_unik']] = "'".$program['id_unik']."'";
							if(empty($data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']]['data'][$program['id_unik']])){
								$data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']]['data'][$program['id_unik']] = array(
									'nama' => $program['nama_program'],
									'kode_skpd' => $program['kode_skpd'],
									'nama_skpd' => $program['nama_skpd'],
									'detail' => array(),
									'data' => array()
								);
							}
							$data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['detail'][] = $program;
							if(
								!empty($program['id_unik_indikator']) 
								&& empty($data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['data'][$program['id_unik_indikator']])
							){
								$data_all['data']['tujuan_kosong']['data'][$sasaran['id_unik']]['data'][$program['id_unik']]['data'][$program['id_unik_indikator']] = array(
									'nama' => $program['indikator'],
									'data' => $program
								);
							}
						}
					}

					// select program yang belum terselect
					if(!empty($program_ids)){
						$sql = "
							select 
								* 
							from data_rpd_program_lokal".$where_tabel."
							where id_unik not in (".implode(',', $program_ids).")
								and active=1
								".$where."
						";
					}else{
						$sql = "
							select 
								* 
							from data_rpd_program_lokal".$where_tabel."
							where active=1
							".$where."
						";
					}
					$program_all = $wpdb->get_results($sql, ARRAY_A);
					foreach ($program_all as $program) {
						if(empty($data_all['data']['tujuan_kosong']['data']['sasaran_kosong']['data'][$program['id_unik']])){
							$data_all['data']['tujuan_kosong']['data']['sasaran_kosong']['data'][$program['id_unik']] = array(
								'nama' => $program['nama_program'],
								'kode_skpd' => $program['kode_skpd'],
								'nama_skpd' => $program['nama_skpd'],
								'detail' => array(),
								'data' => array()
							);
						}
						$data_all['data']['tujuan_kosong']['data']['sasaran_kosong']['data'][$program['id_unik']]['detail'][] = $program;
						if(empty($data_all['data']['tujuan_kosong']['data']['sasaran_kosong']['data'][$program['id_unik']]['data'][$program['id_unik_indikator']])){
							$data_all['data']['tujuan_kosong']['data']['sasaran_kosong']['data'][$program['id_unik']]['data'][$program['id_unik_indikator']] = array(
								'nama' => $program['indikator'],
								'data' => $program
							);
						}
					}

					// hapus array jika data dengan key kosong tidak ada datanya
					if(empty($data_all['data']['tujuan_kosong']['data']['sasaran_kosong']['data'])){
						unset($data_all['data']['tujuan_kosong']['data']['sasaran_kosong']);
					}
					if(empty($data_all['data']['tujuan_kosong']['data'])){
						unset($data_all['data']['tujuan_kosong']);
					}

					// // print_r($data_all);

					$body = '';
					$no_tujuan = 0;
					foreach ($data_all['data'] as $tujuan) {
						$no_tujuan++;
						$indikator_tujuan = '';
						$target_awal = '';
						$target_1 = '';
						$target_2 = '';
						$target_3 = '';
						$target_4 = '';
						$target_5 = '';
						$target_akhir = '';
						$satuan = '';
						$indikator_catatan_tujuan = '';
						foreach($tujuan['detail'] as $k => $v){
							if(!empty($v['indikator_teks'])){
								$indikator_tujuan .= '<div class="indikator_program">'.$v['indikator_teks'].$this->button_edit_monev($v['id_unik'].'|'.$v['id_unik_indikator']).'</div>';
								$target_awal .= '<div class="indikator_program">'.$v['target_awal'].'</div>';
								$target_1 .= '<div class="indikator_program">'.$v['target_1'].'</div>';
								$target_2 .= '<div class="indikator_program">'.$v['target_2'].'</div>';
								$target_3 .= '<div class="indikator_program">'.$v['target_3'].'</div>';
								$target_4 .= '<div class="indikator_program">'.$v['target_4'].'</div>';
								$target_5 .= '<div class="indikator_program">'.$v['target_5'].'</div>';
								$target_akhir .= '<div class="indikator_program">'.$v['target_akhir'].'</div>';
								$satuan .= '<div class="indikator_program">'.$v['satuan'].'</div>';
								$indikator_catatan_tujuan .= '<div class="indikator_program">'.$v['indikator_catatan_teks'].'</div>';
							}
						}
						$target_html = "";
						for($i=1; $i<=$lama_pelaksanaan; $i++){
							$target_html .= '<td class="atas kanan bawah text_tengah">'.${'target_'.$i}.'</td>';
						}
						$warning = "";
						if(empty($tujuan['detail'][0]['id_isu'])){
							$warning = "style='background: #80000014;'";
						}
						$no_urut = '';
						if(!empty($tujuan['detail'][0]['no_urut'])){
							$no_urut = $tujuan['detail'][0]['no_urut'];
						}
						$catatan_teks_tujuan = '';
						if(!empty($tujuan['detail'][0]['catatan_teks_tujuan'])){
							$catatan_teks_tujuan = $tujuan['detail'][0]['catatan_teks_tujuan'];
						}
						$body .= '
							<tr class="tr-tujuan" '.$warning.'>
								<td class="kiri atas kanan bawah">'.$no_tujuan.'</td>
								<td class="atas kanan bawah">'.$tujuan['detail'][0]['isu_teks'].'</td>
								<td class="atas kanan bawah">'.$this->parsing_nama_kode($tujuan['nama']).$this->button_edit_monev($tujuan['detail'][0]['id_unik']).'</td>
								<td class="atas kanan bawah"></td>
								<td class="atas kanan bawah"></td>
								<td class="atas kanan bawah">'.$indikator_tujuan.'</td>
								<td class="atas kanan bawah text_tengah">'.$target_awal.'</td>
								'.$target_html.'
								<td class="atas kanan bawah text_tengah">'.$target_akhir.'</td>
								<td class="atas kanan bawah">'.$satuan.'</td>
								<td class="atas kanan bawah"></td>
								<td class="atas kanan bawah">'.$no_urut.'</td>
								<td class="atas kanan bawah">'.$catatan_teks_tujuan.'</td>
								<td class="atas kanan bawah">'.$indikator_catatan_tujuan.'</td>
							</tr>
						';
						$no_sasaran = 0;
						foreach ($tujuan['data'] as $sasaran) {
							$no_sasaran++;
							$indikator_sasaran = '';
							$target_awal = '';
							$target_1 = '';
							$target_2 = '';
							$target_3 = '';
							$target_4 = '';
							$target_5 = '';
							$target_akhir = '';
							$satuan = '';
							$indikator_catatan_sasaran = '';
							foreach($sasaran['detail'] as $k => $v){
								if(!empty($v['indikator_teks'])){
									$indikator_sasaran .= '<div class="indikator_program">'.$v['indikator_teks'].$this->button_edit_monev($tujuan['detail'][0]['id_unik'].'||'.$v['id_unik'].'|'.$v['id_unik_indikator']).'</div>';
									$target_awal .= '<div class="indikator_program">'.$v['target_awal'].'</div>';
									$target_1 .= '<div class="indikator_program">'.$v['target_1'].'</div>';
									$target_2 .= '<div class="indikator_program">'.$v['target_2'].'</div>';
									$target_3 .= '<div class="indikator_program">'.$v['target_3'].'</div>';
									$target_4 .= '<div class="indikator_program">'.$v['target_4'].'</div>';
									$target_5 .= '<div class="indikator_program">'.$v['target_5'].'</div>';
									$target_akhir .= '<div class="indikator_program">'.$v['target_akhir'].'</div>';
									$satuan .= '<div class="indikator_program">'.$v['satuan'].'</div>';
									$indikator_catatan_sasaran .= '<div class="indikator_program">'.$v['indikator_catatan_teks'].'</div>';
								}
							}
							$target_html = "";
							for($i=1; $i<=$lama_pelaksanaan; $i++){
								$target_html .= '<td class="atas kanan bawah text_tengah">'.${'target_'.$i}.'</td>';
							}
							$sasaran_no_urut = '';
							if(!empty($sasaran['detail'][0]['sasaran_no_urut'])){
								$sasaran_no_urut = $sasaran['detail'][0]['sasaran_no_urut'];
							}
							$sasaran_catatan = '';
							if(!empty($sasaran['detail'][0]['sasaran_catatan'])){
								$sasaran_catatan = $sasaran['detail'][0]['sasaran_catatan'];
							}
							$body .= '
								<tr class="tr-sasaran" '.$warning.'>
									<td class="kiri atas kanan bawah">'.$no_tujuan.'.'.$no_sasaran.'</td>
									<td class="atas kanan bawah"><span class="debug-tujuan">'.$tujuan['detail'][0]['isu_teks'].'</span></td>
									<td class="atas kanan bawah"><span class="debug-tujuan">'.$tujuan['nama'].'</span></td>
									<td class="atas kanan bawah">'.$this->parsing_nama_kode($sasaran['nama']).$this->button_edit_monev($tujuan['detail'][0]['id_unik'].'||'.$sasaran['detail'][0]['id_unik']).'</td>
									<td class="atas kanan bawah"></td>
									<td class="atas kanan bawah">'.$indikator_sasaran.'</td>
									<td class="atas kanan bawah text_tengah">'.$target_awal.'</td>
									'.$target_html.'
									<td class="atas kanan bawah text_tengah">'.$target_akhir.'</td>
									<td class="atas kanan bawah">'.$satuan.'</td>
									<td class="atas kanan bawah"></td>
									<td class="atas kanan bawah">'.$sasaran_no_urut.'</td>
									<td class="atas kanan bawah">'.$sasaran_catatan.'</td>
									<td class="atas kanan bawah">'.$indikator_catatan_sasaran.'</td>
								</tr>
							';
							$no_program = 0;
							foreach ($sasaran['data'] as $program) {
								$no_program++;
								$text_indikator = array();
								$target_awal = array();
								$target_1 = array();
								$target_2 = array();
								$target_3 = array();
								$target_4 = array();
								$target_5 = array();
								$target_akhir = array();
								$satuan = array();
								$nama_skpd = array();
								foreach ($program['data'] as $indikator_program) {
									$text_indikator[] = '<div class="indikator_program">'.$indikator_program['nama'].$this->button_edit_monev($tujuan['detail'][0]['id_unik'].'||'.$sasaran['detail'][0]['id_unik'].'||'.$indikator_program['data']['id_unik'].'|'.$indikator_program['data']['id_unik_indikator']).'</div>';
									$target_awal[] = '<div class="indikator_program">'.$indikator_program['data']['target_awal'].'</div>';
									$target_1[] = '<div class="indikator_program">'.$indikator_program['data']['target_1'].'</div>'.number_format($indikator_program['data']['pagu_1'],0,",",".");
									$target_2[] = '<div class="indikator_program">'.$indikator_program['data']['target_2'].'</div>'.number_format($indikator_program['data']['pagu_2'],0,",",".");
									$target_3[] = '<div class="indikator_program">'.$indikator_program['data']['target_3'].'</div>'.number_format($indikator_program['data']['pagu_3'],0,",",".");
									$target_4[] = '<div class="indikator_program">'.$indikator_program['data']['target_4'].'</div>'.number_format($indikator_program['data']['pagu_4'],0,",",".");
									$target_5[] = '<div class="indikator_program">'.$indikator_program['data']['target_5'].'</div>'.number_format($indikator_program['data']['pagu_5'],0,",",".");
									$target_akhir[] = '<div class="indikator_program">'.$indikator_program['data']['target_akhir'].'</div>';
									$satuan[] = '<div class="indikator_program">'.$indikator_program['data']['satuan'].'</div>';
									$nama_skpd[] = '<div class="indikator_program">'.$indikator_program['data']['kode_skpd'].' '.$indikator_program['data']['nama_skpd'].'</div>';
								}
								$text_indikator = implode('', $text_indikator);
								$target_awal = implode('', $target_awal);
								$target_1 = implode('', $target_1);
								$target_2 = implode('', $target_2);
								$target_3 = implode('', $target_3);
								$target_4 = implode('', $target_4);
								$target_5 = implode('', $target_5);
								$target_akhir = implode('', $target_akhir);
								$satuan = implode('', $satuan);
								$nama_skpd = implode('', $nama_skpd);
								$target_html = "";
								for($i=1; $i<=$lama_pelaksanaan; $i++){
									$target_html .= '<td class="atas kanan bawah text_tengah">'.${'target_'.$i}.'</td>';
								}
								$catatan_program = '';
								$catatan_indikator_program = '';
								$body .= '
									<tr class="tr-program" data-kode-skpd="'.$program['kode_skpd'].'" '.$warning.'>
										<td class="kiri atas kanan bawah">'.$no_tujuan.'.'.$no_sasaran.'.'.$no_program.'</td>
										<td class="atas kanan bawah"><span class="debug-tujuan">'.$tujuan['detail'][0]['isu_teks'].'</span></td>
										<td class="atas kanan bawah"><span class="debug-tujuan">'.$tujuan['nama'].'</span></td>
										<td class="atas kanan bawah"><span class="debug-sasaran">'.$sasaran['nama'].'</span></td>
										<td class="atas kanan bawah">'.$this->parsing_nama_kode($program['nama']).$this->button_edit_monev($tujuan['detail'][0]['id_unik'].'||'.$sasaran['detail'][0]['id_unik'].'||'.$program['detail'][0]['id_unik']).'</td>
										<td class="atas kanan bawah">'.$text_indikator.'</td>
										<td class="atas kanan bawah text_tengah">'.$target_awal.'</td>
										'.$target_html.'
										<td class="atas kanan bawah text_tengah">'.$target_akhir.'</td>
										<td class="atas kanan bawah text_tengah">'.$satuan.'</td>
										<td class="atas kanan bawah">'.$nama_skpd.'</td>
										<td class="atas kanan bawah" colspan="2">'.$catatan_program.'</td>
										<td class="atas kanan bawah">'.$catatan_indikator_program.'</td>
									</tr>
								';
							}
						}
					}

					ksort($skpd_filter);
					$skpd_filter_html = '<option value="">Pilih SKPD</option>';
					foreach ($skpd_filter as $kode_skpd => $nama_skpd) {
						$skpd_filter_html .= '<option value="'.$kode_skpd.'">'.$kode_skpd.' '.$nama_skpd.'</option>';
					}

					$html = '';
					$html .='
						<style type="text/css">
							.debug-visi, .debug-misi, .debug-tujuan, .debug-sasaran, .debug-kode { 
								display: none; 
							}
							.indikator_program { 
								min-height: 40px; 
							}
							.aksi button {
								margin: 3px;
							}
						</style>';
					$html .='
					<div id="preview">
						<h4 style="text-align: center; margin: 0; font-weight: bold;">Monitoring dan Evaluasi RPD (Rencana Pembangunan Daerah) <br>'.$nama_pemda .'<br>'. $awal_rpd.' - '.$akhir_rpd .'</h4>
						<div id="cetak" title="Laporan MONEV RENJA" style="padding: 5px; overflow: auto; height: 80vh;">
							<table cellpadding="2" cellspacing="0" style="font-family:\'Open Sans\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif; border-collapse: collapse; font-size: 70%; border: 0; table-layout: fixed;" contenteditable="false">
								<thead>
									<tr>
										<th style="width: 85px;" class="atas kiri kanan bawah text_tengah text_blok">No</th>
										<th style="width: 200px;" class="atas kanan bawah text_tengah text_blok">Isu RPJPD</th>
										<th style="width: 200px;" class="atas kanan bawah text_tengah text_blok">Tujuan</th>
										<th style="width: 200px;" class="atas kanan bawah text_tengah text_blok">Sasaran</th>
										<th style="width: 200px;" class="atas kanan bawah text_tengah text_blok">Program</th>
										<th style="width: 400px;" class="atas kanan bawah text_tengah text_blok">Indikator</th>
										<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">Target Awal</th>';
										for($i=1; $i<=$lama_pelaksanaan; $i++){
											$html .='<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">Target Tahun '.$i.'</th>';
										}
										$html .='
										<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">Target Akhir</th>
										<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">Satuan</th>
										<th style="width: 150px;" class="atas kanan bawah text_tengah text_blok">Keterangan</th>
										<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">No. Urut</th>
										<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">Catatan</th>
										<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">Indikator Catatan</th>
									</tr>
									<tr>
										<th class="atas kiri kanan bawah text_tengah text_blok">0</th>
										<th class="atas kanan bawah text_tengah text_blok">1</th>
										<th class="atas kanan bawah text_tengah text_blok">2</th>
										<th class="atas kanan bawah text_tengah text_blok">3</th>
										<th class="atas kanan bawah text_tengah text_blok">4</th>
										<th class="atas kanan bawah text_tengah text_blok">5</th>
										<th class="atas kanan bawah text_tengah text_blok">6</th>
										<th class="atas kanan bawah text_tengah text_blok">7</th>
										<th class="atas kanan bawah text_tengah text_blok">8</th>
										<th class="atas kanan bawah text_tengah text_blok">9</th>
										<th class="atas kanan bawah text_tengah text_blok">10</th>
										<th class="atas kanan bawah text_tengah text_blok">11</th>
										<th class="atas kanan bawah text_tengah text_blok">12</th>';
										for($i=1; $i<=$lama_pelaksanaan; $i++){
											$no = 12+$i;
											$html .='<th style="width: 100px;" class="atas kanan bawah text_tengah text_blok">'.$no.'</th>';
										}
										$html .='
									</tr>
								</thead>
								<tbody>
								'.$body.'
								</tbody>
							</table>
						</div>
					</div>';

					$ret['html'] = $html;
				}else{
					$ret = array(
						'status'	=> 'error',
						'message'	=> 'Data ada yang kosong, harap diisi semua!'
					);
				}
			}else{
            	$ret = array(
                    'status' => 'error',
                    'message'   => 'Api Key tidak sesuai!'
                );
            }
        }else{
        	 $ret = array(
                'status' => 'error',
                'message'   => 'Format tidak sesuai!'
            );
        }

		die(json_encode($ret));
	}

	function button_edit_monev($class=false){
		$ret = ' <span style="display: none;" data-id="'.$class.'" class="edit-monev"><i class="dashicons dashicons-edit"></i></span>';
		return $ret;
	}

	function get_target($target, $satuan){
		if(empty($satuan)){
			return $target;
		}else{
			$target = explode($satuan, $target);
			return $target[0];
		}
	}

	function parsing_nama_kode($nama_kode){
		$nama_kodes = explode('||', $nama_kode);
		$nama = $nama_kodes[0];
		unset($nama_kodes[0]);
		return $nama.'<span class="debug-kode">||'.implode('||', $nama_kodes).'</span>';
	}

	public function view_pagu_akumulasi_rpd(){
		global $wpdb;
        $ret = array(
            'status'    => 'success',
            'message'   => 'Berhasil generate laporan pagu akumulasi!',
			'html'		=> ''
        );

		if (!empty($_POST)) {
            if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( WPSIPD_API_KEY )) {
				if(!empty($_POST['id_unit']) && !empty($_POST['id_jadwal_lokal'])){

					$nama_pemda = get_option('_crb_daerah');
					$id_unit = $_POST['id_unit'];
					$id_jadwal_lokal = $_POST['id_jadwal_lokal'];

					$jadwal_lokal = $wpdb->get_row($wpdb->prepare("
						SELECT 
							nama AS nama_jadwal,
							tahun_anggaran AS awal_rpd,
							(tahun_anggaran+lama_pelaksanaan-1) AS akhir_rpd,
							lama_pelaksanaan,
							status 
						FROM `data_jadwal_lokal` 
							WHERE id_jadwal_lokal=%d", $id_jadwal_lokal));

					if(empty($jadwal_lokal)){
						$ret = array(
							'status'	=> 'error',
							'message'	=> 'Data tidak ditemukan!'
						);
						
						die(json_encode($ret));
					}
					$tahun_anggaran = $jadwal_lokal->awal_rpd;

					$_suffix='';
					$where='';
					if($jadwal_lokal->status){
						$_suffix='_history';
						$where='AND id_jadwal='.$id_jadwal_lokal;
					}

					$where_skpd = '';
					if(!empty($id_unit)){
						if($id_unit !='all'){
							$where_skpd = "and id_skpd=".$id_unit;
						}
					}

					$sql = $wpdb->prepare("
						SELECT 
							* 
						FROM data_unit 
						WHERE tahun_anggaran=%d
							".$where_skpd."
							AND is_skpd=1
							AND active=1
						ORDER BY id_skpd ASC
					", $tahun_anggaran);

					$units = $wpdb->get_results($sql, ARRAY_A);

					$data_all = array(
						'data' => array(),
						'pagu_1_total' => 0,
						'pagu_2_total' => 0,
						'pagu_3_total' => 0,
						'pagu_4_total' => 0,
						'pagu_5_total' => 0
					);

					foreach ($units as $unit) {
						if(empty($data_all['data'][$unit['id_skpd']])){
							$data_all['data'][$unit['id_skpd']] = [
								'kode_skpd' => $unit['kode_skpd'],
								'nama_skpd' => $unit['nama_skpd'],
								'pagu_1' => 0,
								'pagu_2' => 0,
								'pagu_3' => 0,
								'pagu_4' => 0,
								'pagu_5' => 0,
							];
						}

						$tujuan_all = $wpdb->get_results($wpdb->prepare("
							SELECT 
								DISTINCT id_unik 
							FROM data_rpd_tujuan_lokal".$_suffix." 
							WHERE 
								active=1 $where ORDER BY no_urut
						"), ARRAY_A);

						foreach ($tujuan_all as $keyTujuan => $tujuan_value) {
							$sasaran_all = $wpdb->get_results($wpdb->prepare("
								SELECT 
									DISTINCT id_unik 
								FROM data_rpd_sasaran_lokal".$_suffix." 
								WHERE 
									kode_tujuan=%s AND 
									active=1 $where ORDER BY sasaran_no_urut
							", $tujuan_value['id_unik']), ARRAY_A);

							foreach ($sasaran_all as $keySasaran => $sasaran_value) {
								$program_all = $wpdb->get_results($wpdb->prepare("
								SELECT 
									COALESCE(SUM(pagu_1), 0) AS pagu_1, 
									COALESCE(SUM(pagu_2), 0) AS pagu_2, 
									COALESCE(SUM(pagu_3), 0) AS pagu_3, 
									COALESCE(SUM(pagu_4), 0) AS pagu_4, 
									COALESCE(SUM(pagu_5), 0) AS pagu_5
								FROM data_rpd_program_lokal".$_suffix." 
								WHERE 
									id_unit=%d AND
									kode_sasaran=%s AND 
									active=1 $where ORDER BY id",
									$unit['id_skpd'],$sasaran_value['id_unik']), ARRAY_A);

								foreach ($program_all as $keyProgram => $program_value) {
									$data_all['data'][$unit['id_skpd']]['pagu_1']+=$program_value['pagu_1'];
									$data_all['data'][$unit['id_skpd']]['pagu_2']+=$program_value['pagu_2'];
									$data_all['data'][$unit['id_skpd']]['pagu_3']+=$program_value['pagu_3'];
									$data_all['data'][$unit['id_skpd']]['pagu_4']+=$program_value['pagu_4'];
									$data_all['data'][$unit['id_skpd']]['pagu_5']+=$program_value['pagu_5'];

									$data_all['pagu_1_total']+=$program_value['pagu_1'];
									$data_all['pagu_2_total']+=$program_value['pagu_2'];
									$data_all['pagu_3_total']+=$program_value['pagu_3'];
									$data_all['pagu_4_total']+=$program_value['pagu_4'];
									$data_all['pagu_5_total']+=$program_value['pagu_5'];
								}
							}
						}
					}

					$body = '';
					$no=1;
					foreach ($data_all['data'] as $key => $unit) {
						$body.='<tr>
							<td class="kiri atas kanan bawah text_tengah">'.$no.'.</td>
							<td class="atas kanan bawah">'.$unit['nama_skpd'].'</td>';
							for ($i=1; $i <= $jadwal_lokal->lama_pelaksanaan; $i++) { 
								$body.='<td class="atas kanan bawah text_kanan">'.$this->_number_format($unit['pagu_'.$i]).'</td>';
							}
						$body.='</tr>';
						$no++;
					}
					$body.='<tr>
							<td class="kiri atas kanan bawah text_tengah" colspan="2"><b>TOTAL PAGU</b></td>';
							for ($i=1; $i <= $jadwal_lokal->lama_pelaksanaan; $i++) { 
								$body.='<td class="atas kanan bawah text_kanan"><b>'.$this->_number_format($data_all['pagu_'.$i.'_total']).'</b></td>';
							}
					$body.='</tr>';


					$html ='<div id="preview" style="padding: 5px; overflow: auto; height: 80vh;">
						<h4 style="text-align: center; margin: 0; font-weight: bold;">PAGU AKUMULASI RPD Per Unit Kerja 
						<br>Tahun '.$jadwal_lokal->awal_rpd.' - '.$jadwal_lokal->akhir_rpd.' '.$nama_pemda.'
						<br>Tahapan: '.$jadwal_lokal->nama_jadwal.'
						</h4>
						<table id="table-renstra" cellpadding="2" cellspacing="0" style="font-family:\'Open Sans\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif; border-collapse: collapse; font-size: 70%; border: 0; table-layout: fixed;" contenteditable="false">
							<thead><tr>
									<th style="width: 85px;" class="atas kiri kanan bawah text_tengah text_blok">No</th>
									<th style="width: 200px;" class="atas kanan bawah text_tengah text_blok">Unit Kerja</th>';
									for ($i=1; $i <= $jadwal_lokal->lama_pelaksanaan; $i++) { 
										$html.='<th style="width: 200px;" class="atas kanan bawah text_tengah text_blok">Tahun '.$i.'</th>';
									}
								$html.='
								</tr>';
								$html.='</thead>
							<tbody>'.$body.'</tbody>
						</table>
					</div>';
					$ret['html'] = $html;
				}else{
					$ret = array(
						'status'	=> 'error',
						'message'	=> 'Data ada yang kosong, harap diisi semua!'
					);
				}
			}else{
            	$ret = array(
                    'status' => 'error',
                    'message'   => 'Api Key tidak sesuai!'
                );
            }
        }else{
        	 $ret = array(
                'status' => 'error',
                'message'   => 'Format tidak sesuai!'
            );
        }		

		die(json_encode($ret));
	}

	public function singkron_master_indikator_sub_keg(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil sinkron data master indikator sub kegiatan!',
			'data'		=> array(),
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				$tahun_anggaran = $_POST['tahun_anggaran'];
				$wpdb->update('data_master_indikator_subgiat', array('active' => 0), array(
					'tahun_anggaran' => $tahun_anggaran
				));
				foreach($_POST['data'] as $id_sub => $sub_all){
					foreach($sub_all as $sub){
						$cek = $wpdb->get_var($wpdb->prepare("
							SELECT
								id 
							FROM data_master_indikator_subgiat
							WHERE
								indikator=%s
								AND id_sub_keg=%d
								AND tahun_anggaran=%d
						", trim($sub['indikator']), $id_sub, $tahun_anggaran));

						$opsi = array(
							// 'id_skpd' => null,
							'id_sub_keg' => $id_sub,
							'indikator' => trim($sub['indikator']),
							'satuan' => trim($sub['satuan']),
							'active' => 1,
							'tahun_anggaran' => $tahun_anggaran,
							'updated_at' => current_time('mysql')
						);

						if(!empty($cek)){
							$wpdb->update('data_master_indikator_subgiat', $opsi, array(
								'id' => $cek
							));
						}else{
							$wpdb->insert('data_master_indikator_subgiat',$opsi);
						}
					}
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}

	public function get_sub_keg_parent(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil mendapapatkan data sub kegiatan!',
			'data'		=> array(),
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran']) && !empty($_POST['id_unit'])){
					$tahun_anggaran = $_POST['tahun_anggaran'];
					$id_unit = $_POST['id_unit'];

					$skpd = $this->get_skpd_db();
					$skpd_db = $skpd['skpd'];
					$bidur_db = $skpd['bidur'];

					$data_sub_kegiatan = array();
					foreach($bidur_db as $k_bidur => $v_bidur){
						if(!empty($_POST['kode_giat'])){
							$data = $wpdb->get_results($wpdb->prepare(
								'SELECT id,
									id_bidang_urusan,
									id_sub_giat,
									kode_sub_giat,
									nama_sub_giat
								FROM data_prog_keg
								WHERE id_bidang_urusan=%d
								AND tahun_anggaran=%d
								AND kode_giat=%s
							', $v_bidur['id_bidang_urusan'],$tahun_anggaran, $_POST['kode_giat']),ARRAY_A);
						}else{
							$data = $wpdb->get_results($wpdb->prepare(
								'SELECT id,
									id_bidang_urusan,
									id_sub_giat,
									kode_sub_giat,
									nama_sub_giat
								FROM data_prog_keg
								WHERE id_bidang_urusan=%d
								AND tahun_anggaran=%d
							', $v_bidur['id_bidang_urusan'],$tahun_anggaran),ARRAY_A);
						}

						if(empty($data_sub_kegiatan[$k_bidur])){
							$data_sub_kegiatan[$k_bidur] = $data;
						}
					}
					$ret['data'] = $data_sub_kegiatan;
					// $ret['sql'] = $wpdb->last_query;
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Ada param yang kosong!';	
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}

	public function get_indikator_sub_keg_parent(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil mendapapatkan data indikator sub kegiatan!',
			'data'		=> array(),
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran']) && !empty($_POST['id_sub_keg'])){
					$tahun_anggaran = $_POST['tahun_anggaran'];
					$id_sub_keg = $_POST['id_sub_keg'];

					// get kode sub kegiatan dulu
					$data_sub_giat = $wpdb->get_row($wpdb->prepare('
						SELECT 
							kode_sub_giat
						FROM data_prog_keg p
						WHERE p.id_sub_giat=%s
							AND p.tahun_anggaran=%d
					', $id_sub_keg, $tahun_anggaran), ARRAY_A);

					// select master indikator berdasarkan kode kegiatan karena kalau pakai id sub kegiatan, ditakutkan beda
					$data_sub_kegiatan = $wpdb->get_results($wpdb->prepare('
						SELECT 
							i.*
						FROM data_master_indikator_subgiat i
						LEFT join data_prog_keg p on i.id_sub_keg=p.id_sub_giat
						WHERE p.kode_sub_giat=%s
							AND i.tahun_anggaran=%d
							AND i.active=1
						GROUP BY p.kode_sub_giat
					', $data_sub_giat['kode_sub_giat'], $tahun_anggaran), ARRAY_A);

					if(!empty($data_sub_kegiatan)){
						$ret['data'] = $data_sub_kegiatan;
					}else{
						$ret['sql'] = $wpdb->last_query;
					}
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Ada param yang kosong!';
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}

	public function get_data_sumber_dana_renja(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil mendapapatkan data sumber dana!',
			'data'		=> array(),
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran'])){
					$tahun_anggaran = $_POST['tahun_anggaran'];
	
					$data_sumber_dana = $wpdb->get_results($wpdb->prepare(
						'SELECT *
						FROM data_sumber_dana
						WHERE tahun_anggaran=%d
					', $tahun_anggaran),ARRAY_A);
	
					if(!empty($data_sumber_dana)){
						$ret['data'] = $data_sumber_dana;
					}
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Ada param yang kosong!';
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}

	public function submit_tambah_renja(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil menambahkan data RENJA!',
			'indikator' => array()
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {

				// catatan:
				// user OPD dan user admin harus dibedakan cara kewenangan insertnya
				
				$cek_jadwal = $this->validasi_jadwal_perencanaan('renja');
                if($cek_jadwal['status'] == 'success'){
					$tahun_anggaran = $_POST['tahun_anggaran'];
					$data = json_decode(stripslashes($_POST['data']), true);
					if(empty($data['input_sub_unit'])){
						$ret['status'] = 'error';
						$ret['message'] = 'Sub Unit tidak boleh kosong';
					}elseif(empty($data['input_sub_kegiatan'])){
						$ret['status'] = 'error';
						$ret['message'] = 'Sub kegiatan tidak boleh kosong';
					}elseif(
						!isset($data['input_pagu_sub_keg_usulan']) 
						|| $data['input_pagu_sub_keg_usulan'] == 0
					){
						$ret['status'] = 'error';
						$ret['message'] = 'Pagu usulan tidak boleh kosong';
					}elseif(empty($data['input_indikator_sub_keg_usulan'])){
						$ret['status'] = 'error';
						$ret['message'] = 'Indikator usulan tidak boleh kosong';
					}elseif(empty($data['input_target_usulan'])){
						$ret['status'] = 'error';
						$ret['message'] = 'Target indikator usulan tidak boleh kosong';
					}elseif(empty($data['input_satuan_usulan'])){
						$ret['status'] = 'error';
						$ret['message'] = 'Satuan indikator usulan tidak boleh kosong';
					}
				}else{
					$ret['status'] = 'error';
                    $ret['message'] = 'Jadwal belum dimulai!';
				}

				// print_r($data); die();

				if($ret['status'] != 'error'){
					$data_sub_unit = $wpdb->get_row($wpdb->prepare('
						SELECT 
							nama_skpd,
							kode_skpd,
							id_unit,
							id_skpd,
							is_skpd
						FROM data_unit
						WHERE id_skpd=%d',
					$data['input_sub_unit']));

					$nama_skpd = $data_sub_unit->nama_skpd;
					$kode_skpd = $data_sub_unit->kode_skpd;
					if($data_sub_unit->is_skpd != 1){
						$data_unit = $wpdb->get_row($wpdb->prepare('
							SELECT 
								nama_skpd,
								kode_skpd
							FROM data_unit
							WHERE id_unit=%d
								AND is_skpd=1',
						$data_sub_unit->id_unit));
						$nama_skpd = $data_unit->nama_skpd;
						$kode_skpd = $data_unit->kode_skpd;
					}

					$data_prog_keg = $wpdb->get_row($wpdb->prepare('
						SELECT 
							*
						FROM data_prog_keg
						WHERE id_sub_giat=%d
					', $data['input_sub_kegiatan']));

					$kode_bl = $data_sub_unit->id_unit.".".$data_sub_unit->id_skpd.".".$data_prog_keg->id_program.".".$data_prog_keg->id_giat;
					$kode_sbl = $kode_bl.".".$data_prog_keg->id_sub_giat;

					$opsi_sub_keg_bl = array(
						'id_sub_skpd' => $data['input_sub_unit'],
						'id_sub_giat' => $data['input_sub_kegiatan'],
						'id_skpd' => $data_sub_unit->id_unit,
						'kode_bl' => $kode_bl,
						'kode_sbl' => $kode_sbl,
						'nama_skpd' => $nama_skpd,
						'kode_skpd' => $kode_skpd,
						'nama_sub_skpd' => $data_sub_unit->nama_skpd,
						'kode_sub_skpd' => $data_sub_unit->kode_skpd,
						'pagu' => $data['input_pagu_sub_keg'],
						'pagu_usulan' => $data['input_pagu_sub_keg_usulan'],
						'pagu_n_depan' => $data['input_pagu_sub_keg_1'],
						'pagu_n_depan_usulan' => $data['input_pagu_sub_keg_1_usulan'],
						'kode_urusan' => $data_prog_keg->kode_urusan,
						'id_urusan' => $data_prog_keg->id_urusan,
						'nama_urusan' => $data_prog_keg->nama_urusan,
						'id_bidang_urusan' => $data_prog_keg->id_bidang_urusan,
						'kode_bidang_urusan' => $data_prog_keg->kode_bidang_urusan,
						'nama_bidang_urusan' => $data_prog_keg->nama_bidang_urusan,
						'id_program' => $data_prog_keg->id_program,
						'kode_program' => $data_prog_keg->kode_program,
						'nama_program' => $data_prog_keg->nama_program,
						'kode_giat' => $data_prog_keg->kode_giat,
						'nama_giat' => $data_prog_keg->nama_giat,
						'kode_sub_giat' => $data_prog_keg->kode_sub_giat,
						'nama_sub_giat' => $data_prog_keg->nama_sub_giat,
						'catatan' => $data['input_catatan'],
						'catatan_usulan' => $data['input_catatan_usulan'],
						'waktu_awal' => $data['input_bulan_awal'],
						'waktu_akhir' => $data['input_bulan_akhir'],
						'waktu_awal_usulan' => $data['input_bulan_awal_usulan'],
						'waktu_akhir_usulan' => $data['input_bulan_akhir_usulan'],
						'active' => 1,
						'tahun_anggaran' => $tahun_anggaran,
						'update_at' => current_time('mysql')
					);

					// insert sub kegiatan
					$cek_id = $wpdb->get_var($wpdb->prepare("
						SELECT 
							id 
						from data_sub_keg_bl_lokal 
						where kode_sbl='$kode_sbl' 
							and tahun_anggaran=%d
					", $tahun_anggaran));

					if(!$cek_id){
						$wpdb->insert('data_sub_keg_bl_lokal',$opsi_sub_keg_bl);
					}else{
						$wpdb->update('data_sub_keg_bl_lokal', $opsi_sub_keg_bl, array('id' => $cek_id));
						$ret['message'] = 'Berhasil update data RENJA!';
					
						// $ret['status'] = 'error';
						// $ret['message'] = 'Sub kegiatan '.$data_prog_keg->nama_sub_giat.' untuk sub unit '.$data_sub_unit->nama_skpd.' sudah ada!';
					}
				}

				if($ret['status'] != 'error'){
					// insert sumber dana
					$wpdb->update('data_dana_sub_keg_lokal', array('active' => 0), array(
						'kode_sbl' => $kode_sbl,
						'tahun_anggaran' => $tahun_anggaran
					));
					foreach ($data['input_sumber_dana'] as $k_sumber_dana => $v_sumber_dana) {
						$data_sumber_dana = $wpdb->get_row($wpdb->prepare('
							SELECT 
								nama_dana,
								kode_dana,
								id_dana
							FROM data_sumber_dana
							WHERE id_dana=%d
								AND tahun_anggaran=%d
						', $v_sumber_dana, $tahun_anggaran));

						$data_sumber_dana_usulan = $wpdb->get_row($wpdb->prepare('
							SELECT 
								nama_dana,
								kode_dana,
								id_dana
							FROM data_sumber_dana
							WHERE id_dana=%d
								AND tahun_anggaran=%d
						', $data['input_sumber_dana_usulan'][$k_sumber_dana], $tahun_anggaran));

						$cek_ids = $wpdb->get_results($wpdb->prepare('
							SELECT 
								id
							FROM data_dana_sub_keg_lokal
							WHERE kode_sbl=%s
								AND id_dana_usulan=%d
								AND tahun_anggaran=%d
						', $kode_sbl, $data['input_sumber_dana_usulan'][$k_sumber_dana], $tahun_anggaran), ARRAY_A);
						$opsi_sumber_dana = array(
							'namadana' => $data_sumber_dana->nama_dana,
							'kodedana' => $data_sumber_dana->kode_dana,
							'iddana' => $data_sumber_dana->id_dana,
							'pagudana' => $data['input_pagu_sumber_dana'][$k_sumber_dana],
							'kode_sbl' => $kode_sbl,
							'active' => 1,
							'update_at' => current_time('mysql'),
							'tahun_anggaran' => $tahun_anggaran,
							'nama_dana_usulan' => $data_sumber_dana_usulan->nama_dana,
							'kode_dana_usulan' => $data_sumber_dana_usulan->kode_dana,
							'id_dana_usulan' => $data_sumber_dana_usulan->id_dana,
							'pagu_dana_usulan' => $data['input_pagu_sumber_dana_usulan'][$k_sumber_dana],
						);
						
						if(
							empty($cek_ids)
							|| empty($cek_ids[$k_sumber_dana])
						){
							$wpdb->insert('data_dana_sub_keg_lokal', $opsi_sumber_dana);
						}else{
							$wpdb->update('data_dana_sub_keg_lokal', $opsi_sumber_dana, array('id' => $cek_ids[$k_sumber_dana]['id']));
						}
					}

					// insert indikator sub kegiatan
					$wpdb->update('data_sub_keg_indikator_lokal', array('active' => 0), array(
						'kode_sbl' => $kode_sbl,
						'tahun_anggaran' => $tahun_anggaran
					));
					foreach($data['input_indikator_sub_keg_usulan'] as $k_sub_keg => $v_sub_keg){
						if(empty($v_sub_keg)){
							$v_sub_keg = $data['input_indikator_sub_keg'][$k_sub_keg];
						}
						$indikator_sub_keg = $wpdb->get_row($wpdb->prepare('
							SELECT 
								*
							FROM data_master_indikator_subgiat
							WHERE id_sub_keg=%d
								AND tahun_anggaran=%d
								AND active=1
						', $v_sub_keg, $tahun_anggaran));
			
						if(!empty($indikator_sub_keg)){
							$opsi_sub_keg_indikator = array(
								'outputteks' => $indikator_sub_keg->indikator,
								'outputteks_usulan' => $indikator_sub_keg->indikator,
								'targetoutput' => $data['input_target'][$k_sub_keg],
								'targetoutput_usulan' => $data['input_target_usulan'][$k_sub_keg],
								'satuanoutput' => $indikator_sub_keg->satuan,
								'satuanoutput_usulan' => $indikator_sub_keg->satuan,
								'idoutputbl' => 0,
								'targetoutputteks' => $data['input_target'][$k_sub_keg].' '.$indikator_sub_keg->satuan,
								'targetoutputteks_usulan' => $data['input_target_usulan'][$k_sub_keg].' '.$indikator_sub_keg->satuan,
								'kode_sbl' => $kode_sbl,
								'idsubbl' => 0,
								'active' => 1,
								'update_at' => current_time('mysql'),
								'tahun_anggaran' => $tahun_anggaran,
								'id_indikator_sub_giat' => $indikator_sub_keg->id_sub_keg
							);
							
							$cek_ids = $wpdb->get_results($wpdb->prepare("
								SELECT
									id
								FROM data_sub_keg_indikator_lokal
								WHERE kode_sbl=%s
									AND tahun_anggaran=%d
							", $kode_sbl, $tahun_anggaran), ARRAY_A);
							if(
								empty($cek_ids)
								|| empty($cek_ids[$k_sub_keg])
							){
								$wpdb->insert('data_sub_keg_indikator_lokal', $opsi_sub_keg_indikator);
							}else{
								$wpdb->update('data_sub_keg_indikator_lokal', $opsi_sub_keg_indikator, array(
									"id" => $cek_ids[$k_sub_keg]['id']
								));
							}
							// $ret['indikator'][] = $wpdb->last_query;
						}else{
							$ret['indikator'][] = 'ID sub kegiatan '.$v_sub_keg.' tidak ditemukan di tabel data_master_indikator_subgiat! '.$wpdb->last_query;
						}
					}

					// insert lokasi sub kegiatan
					$wpdb->update('data_lokasi_sub_keg_lokal', array('active' => 0), array(
						'kode_sbl' => $kode_sbl,
						'tahun_anggaran' => $tahun_anggaran
					));
					foreach ($data['input_kabupaten_kota'] as $k_lok => $v_lok) {
						$data_camat = '';
						$data_camat_usulan = '';
						$data_kabkot = '';
						$data_kabkot_usulan = '';
						$data_lurah = '';
						$data_lurah_usulan = '';
		
						$data_kabkot = $wpdb->get_row($wpdb->prepare('
							SELECT 
								*
							FROM data_alamat
							WHERE is_kab=1
								AND id_alamat=%d
								AND tahun=%d
						', $v_lok, $tahun_anggaran), ARRAY_A);
		
						$data_camat = $wpdb->get_row($wpdb->prepare('
							SELECT 
								* 
							FROM data_alamat
							WHERE is_kec=1
								AND id_alamat=%d
								AND tahun=%d
						', $data['input_kecamatan'][$k_lok], $tahun_anggaran), ARRAY_A);
		
						$id_camat = $nama_camat = NULL;
						if(!empty($data_camat)){
							$id_camat = $data_camat['id_alamat'];
							$nama_camat = $data_camat['nama'];
						}
		
						$data_lurah = $wpdb->get_row($wpdb->prepare('
							SELECT 
								* 
							FROM data_alamat
							WHERE is_kel=1
								AND id_alamat=%d
								AND tahun=%d
						', $data['input_desa'][$k_lok], $tahun_anggaran), ARRAY_A);
		
						$id_lurah = $nama_lurah = NULL;
						if(!empty($data_lurah)){
							$id_lurah = $data_lurah['id_alamat'];
							$nama_lurah = $data_lurah['nama'];
						}
		
						$nama_camat = !empty($data_camat['nama']) ? $data_camat['nama'] : null;
						
						$data_kabkot_usulan = $wpdb->get_row($wpdb->prepare('
							SELECT 
								*
							FROM data_alamat
							WHERE is_kab=1
								AND id_alamat=%d
								AND tahun=%d
						', $data['input_kabupaten_kota_usulan'][$k_lok], $tahun_anggaran), ARRAY_A);
		
						$data_camat_usulan = $wpdb->get_row($wpdb->prepare('
							SELECT 
								* 
							FROM data_alamat
							WHERE is_kec=1
								AND id_alamat=%d
								AND tahun=%d
						', $data['input_kecamatan_usulan'][$k_lok], $tahun_anggaran), ARRAY_A);
		
						$id_camat_usulan = $nama_camat_usulan = NULL;
						if(!empty($data_camat_usulan)){
							$id_camat_usulan = $data_camat_usulan['id_alamat'];
							$nama_camat_usulan = $data_camat_usulan['nama'];
						}
		
						$data_lurah_usulan = $wpdb->get_row($wpdb->prepare('
							SELECT 
								* 
							FROM data_alamat
							WHERE is_kel=1
								AND id_alamat=%d
								AND tahun=%d
						', $data['input_desa_usulan'][$k_lok], $tahun_anggaran), ARRAY_A);
		
						$id_lurah_usulan = $nama_lurah_usulan = NULL;
						if(!empty($data_lurah_usulan)){
							$id_lurah_usulan = $data_lurah_usulan['id_alamat'];
							$nama_lurah_usulan = $data_lurah_usulan['nama'];
						}
		
						$opsi_lokasi = array(
							'camatteks' => $nama_camat,
							'daerahteks' => $data_kabkot['nama'],
							'idcamat' => $id_camat,
							'iddetillokasi' => 0,
							'idkabkota' => $data_kabkot['id_alamat'],
							'idlurah' => $id_lurah,
							'lurahteks' => $nama_lurah,
							'camatteks_usulan' => $nama_camat_usulan,
							'daerahteks_usulan' => $data_kabkot_usulan['nama'],
							'idcamat_usulan' => $id_camat_usulan,
							'iddetillokasi_usulan' => 0,
							'idkabkota_usulan' => $data_kabkot_usulan['id_alamat'],
							'idlurah_usulan' => $id_lurah_usulan,
							'lurahteks_usulan' => $nama_lurah_usulan,
							'kode_sbl' => $kode_sbl,
							'idsubbl' => 0,
							'active' => 1,
							'update_at' => current_time('mysql'),
							'tahun_anggaran' => $tahun_anggaran
						);
		
						$cek_ids = $wpdb->get_results($wpdb->prepare('
							SELECT
								id
							FROM data_lokasi_sub_keg_lokal
							WHERE kode_sbl=%s
								AND tahun_anggaran=%d
						', $kode_sbl, $tahun_anggaran), ARRAY_A);
						if(
							empty($cek_ids) 
							|| empty($cek_ids[$k_lok])
						){
							$wpdb->insert('data_lokasi_sub_keg_lokal', $opsi_lokasi);
						}else{
							$wpdb->update('data_lokasi_sub_keg_lokal', $opsi_lokasi, array('id' => $cek_ids[$k_lok]['id']));
						}
					}
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}

	public function edit_renja(){
		global $wpdb;
		$ret = array(
			'status' => 'success',
			'message' => 'Berhasil mendapatkan data!',
			'data' => array()
		);
		
		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran']) && !empty($_POST['kode_sub_giat'])){				
					$tahun_anggaran = $_POST['tahun_anggaran'];
					$kode_sub_giat = $_POST['kode_sub_giat'];

					$data_sub_giat = $wpdb->get_row($wpdb->prepare(
						'SELECT *
						FROM data_sub_keg_bl_lokal sk
						WHERE sk.kode_sub_giat=%s
						AND sk.tahun_anggaran=%d
						AND sk.active=1',
						$kode_sub_giat,$tahun_anggaran
					),ARRAY_A);
					if(!empty($data_sub_giat)){
						$ret['data'] = $data_sub_giat;
					}

					$ret['data']['sumber_dana'] = array();
					$data_sumber_dana = $wpdb->get_results($wpdb->prepare(
						'SELECT *
						FROM data_dana_sub_keg_lokal
						WHERE kode_sbl=%s
						AND tahun_anggaran=%d
						AND active=1',
						$data_sub_giat['kode_sbl'], $tahun_anggaran
					));
					if(!empty($data_sumber_dana)){
						$ret['data']['sumber_dana'] = $data_sumber_dana;
					}

					$ret['data']['lokasi'] = array();
					$data_lokasi = $wpdb->get_results($wpdb->prepare(
						'SELECT *
						FROM data_lokasi_sub_keg_lokal
						WHERE kode_sbl=%s
						AND tahun_anggaran=%d
						AND active=1',
						$data_sub_giat['kode_sbl'],$tahun_anggaran
					));
					if(!empty($data_lokasi)){
						$ret['data']['lokasi'] = $data_lokasi;
					}

					$data_master_kabkot = array();
					$data_master_kec = array();
					$data_master_desa = array();
					foreach ($data_lokasi as $v_lokasi) {
						$data_alamat = $wpdb->get_row($wpdb->prepare(
							'SELECT *
							FROM data_alamat
							WHERE id_alamat=%d
							AND tahun=%d
							AND is_kab=1',
							$v_lokasi->idkabkota, $tahun_anggaran
						));

						$data_master_kec = $wpdb->get_results($wpdb->prepare(
							'SELECT *
							FROM data_alamat
							WHERE id_prov=%d
							AND id_kab=%d
							AND tahun=%d
							AND is_kec=1',
							$data_alamat->id_prov,$v_lokasi->idkabkota,$tahun_anggaran
						));
						$ret['data']['data_master_kec'][$v_lokasi->id] = $data_master_kec;
						
						$data_master_desa = array();
						if(!empty($v_lokasi->idcamat)){
							$data_master_desa = $wpdb->get_results($wpdb->prepare(
								'SELECT *
								FROM data_alamat
								WHERE id_prov=%d
								AND id_kab=%d
								AND id_kec=%d
								AND tahun=%d
								AND is_kel=1',
								$data_alamat->id_prov,$v_lokasi->idkabkota,$v_lokasi->idcamat,$tahun_anggaran
							));
						}
						$ret['data']['data_master_desa'][$v_lokasi->id] = $data_master_desa;
					}
					
					$data_master_sub_keg_indikator = $wpdb->get_results($wpdb->prepare('
						SELECT 
							i.*
						FROM data_master_indikator_subgiat i
						LEFT join data_prog_keg p on i.id_sub_keg=p.id_sub_giat
						WHERE p.kode_sub_giat=%s
							AND i.tahun_anggaran=%d
							AND i.active=1
						GROUP BY p.kode_sub_giat
					', $data_sub_giat['kode_sub_giat'], $tahun_anggaran),ARRAY_A);
					$ret['data']['master_sub_keg_indikator'] = array();
					// $ret['data']['master_sub_keg_indikator_sql'] = $wpdb->last_query;
					if(!empty($data_master_sub_keg_indikator)){
						$ret['data']['master_sub_keg_indikator'] = $data_master_sub_keg_indikator;
					}

					$data_sub_keg_indikator = $wpdb->get_results($wpdb->prepare('
						SELECT 
							*
						FROM data_sub_keg_indikator_lokal
						WHERE kode_sbl=%s
							AND tahun_anggaran=%s
							AND active=1
						',
						$data_sub_giat['kode_sbl'],$tahun_anggaran
					),ARRAY_A);
					$ret['data']['indikator_sub_keg'] = array();
					if(!empty($data_sub_keg_indikator)){
						$ret['data']['indikator_sub_keg'] = $data_sub_keg_indikator;
					}
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Ada param yang kosong!';
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));

	}

	public function submit_edit_renja(){
		$this->submit_tambah_renja();
	}

	public function delete_renja(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil menghapus data RENJA!'
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran']) && !empty($_POST['kode_sub_giat'])){
					$tahun_anggaran = $_POST['tahun_anggaran'];
					$kode_sub_giat = $_POST['kode_sub_giat'];

					$data_sub_keg = $wpdb->get_results($wpdb->prepare(
						'SELECT kode_sub_giat,
							kode_sbl
						FROM data_sub_keg_bl_lokal
						WHERE kode_sub_giat=%s
						AND tahun_anggaran=%d
						AND active=1',
						$kode_sub_giat,$tahun_anggaran
					),ARRAY_A);
	
					$status = $wpdb->update('data_sub_keg_bl_lokal', array('active' => 0), array('kode_sub_giat' => $kode_sub_giat));
					$status_indi_sub_keg = $wpdb->update('data_sub_keg_indikator_lokal', array('active' => 0), array('kode_sbl' => $data_sub_keg[0]['kode_sbl']));
					$status_dana_sub_keg = $wpdb->update('data_dana_sub_keg_lokal', array('active' => 0), array('kode_sbl' => $data_sub_keg[0]['kode_sbl']));
					$status_lokasi_sub_keg = $wpdb->update('data_lokasi_sub_keg_lokal', array('active' => 0), array('kode_sbl' => $data_sub_keg[0]['kode_sbl']));
	
					if($status === false){
						$ret['status'] = 'error';
						$ret['message'] = 'Delete gagal, harap hubungi admin!';
					}
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Ada param yang kosong!';
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}

	public function get_data_lokasi_renja(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil mendapatkan data lokasi!',
			'data'		=> array(),
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran']) && !empty($_POST['jenis_lokasi']) && !empty($_POST['id_alamat'])){
					$tahun_anggaran = $_POST['tahun_anggaran'];
					$jenis_lokasi = $_POST['jenis_lokasi'];
					$id_alamat = $_POST['id_alamat'];
	
					$where = '';
					switch ($jenis_lokasi) {
						case 'kabkot':
							$jenis_lokasi = 'Kecamatan';
							$where = ' AND is_kec=1 AND id_kab='.$id_alamat;
							break;
						case 'kec':
							$jenis_lokasi = 'Desa';
							$where = ' AND is_kel=1 AND id_kec='.$id_alamat;
							break;
						case 'prov':
							$jenis_lokasi = 'Kabupaten/Kota';
							$where = ' AND is_kab=1 AND id_prov='.$id_alamat;
							break;
					}
					$data_lokasi = $wpdb->get_results($wpdb->prepare(
						'SELECT *
						FROM data_alamat
						WHERE tahun=%d'.$where,
						$tahun_anggaran),ARRAY_A);
	
					$ret['jenis_lokasi'] = $jenis_lokasi;
					$ret['data'] = $data_lokasi;
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Ada param yang kosong!';
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}

	public function get_indikator_program_renja(){
		global $wpdb;
		$ret = array(
			'status'	=> 'success',
			'message' 	=> 'Berhasil mendapatkan data indikator!',
			'data'		=> array(),
		);

		if(!empty($_POST)){
			if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option( '_crb_api_key_extension' )) {
				if(!empty($_POST['tahun_anggaran'])){
					$tahun_anggaran = $_POST['tahun_anggaran'];
					$kode_sbl = $_POST['kode_sbl'];
					$data_indikator = $wpdb->get_results($wpdb->prepare('
						SELECT 
							*
						FROM data_capaian_prog_sub_keg_lokal
						WHERE tahun=%d
							AND kode_sbl=%s
					', $tahun_anggaran),ARRAY_A);
					$ret['data'] = array();
					if(!empty($data_indikator)){
						$ret['data'] = $data_indikator;
					}
				}else{
					$ret['status'] = 'error';
					$ret['message'] = 'Ada param yang kosong!';
				}
			}else{
				$ret['status'] = 'error';
				$ret['message'] = 'APIKEY tidak sesuai!';
			}
		}else{
			$ret['status']	= 'error';
			$ret['message']	= 'Format Salah!';
		}

		die(json_encode($ret));
	}
}