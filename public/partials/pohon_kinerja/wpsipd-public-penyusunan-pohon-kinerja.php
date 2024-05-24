<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

global $wpdb;

$data_all = [
	'data' => []
];

// pokin level 1
$pohon_kinerja_level_1 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER BY id", 0, 1, 1), ARRAY_A);
if(!empty($pohon_kinerja_level_1)){
	foreach ($pohon_kinerja_level_1 as $level_1) {
		if(empty($data_all['data'][trim($level_1['label'])])){
			$data_all['data'][trim($level_1['label'])] = [
				'id' => $level_1['id'],
				'label' => $level_1['label'],
				'level' => $level_1['level'],
				'indikator' => [],
				'data' => []
			];
		}

		// indikator pokin level 1
		$indikator_pohon_kinerja_level_1 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER BY id", $level_1['id'], 1, 1), ARRAY_A);
		if(!empty($indikator_pohon_kinerja_level_1)){
			foreach ($indikator_pohon_kinerja_level_1 as $indikator_level_1) {
				if(!empty($indikator_level_1['label_indikator_kinerja'])){
					if(empty($data_all['data'][trim($level_1['label'])]['indikator'][(trim($indikator_level_1['label_indikator_kinerja']))])){
						$data_all['data'][trim($level_1['label'])]['indikator'][(trim($indikator_level_1['label_indikator_kinerja']))] = [
							'id' => $indikator_level_1['id'],
							'parent' => $indikator_level_1['parent'],
							'label_indikator_kinerja' => $indikator_level_1['label_indikator_kinerja'],
							'level' => $indikator_level_1['level']
						];
					}
				}
			}
		}

		// pokin level 2 
		$pohon_kinerja_level_2 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER by id", $level_1['id'], 2, 1), ARRAY_A);
		if(!empty($pohon_kinerja_level_2)){
			foreach ($pohon_kinerja_level_2 as $level_2) {
				if(empty($data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])])){
					$data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])] = [
						'id' => $level_2['id'],
						'label' => $level_2['label'],
						'level' => $level_2['level'],
						'indikator' => [],
						'data' => []
					];
				}

				// indikator pokin level 2
				$indikator_pohon_kinerja_level_2 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER BY id", $level_2['id'], 2, 1), ARRAY_A);
				if(!empty($indikator_pohon_kinerja_level_2)){
					foreach ($indikator_pohon_kinerja_level_2 as $indikator_level_2) {
						if(!empty($indikator_level_2['label_indikator_kinerja'])){
							if(empty($data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['indikator'][(trim($indikator_level_2['label_indikator_kinerja']))])){
								$data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['indikator'][(trim($indikator_level_2['label_indikator_kinerja']))] = [
									'id' => $indikator_level_2['id'],
									'parent' => $indikator_level_2['parent'],
									'label_indikator_kinerja' => $indikator_level_2['label_indikator_kinerja'],
									'level' => $indikator_level_2['level']
								];
							}
						}
					}
				}

				// pokin level 3
				$pohon_kinerja_level_3 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER by id", $level_2['id'], 3, 1), ARRAY_A);
				if(!empty($pohon_kinerja_level_3)){
					foreach ($pohon_kinerja_level_3 as $level_3) {
						if(empty($data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])])){
							$data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])] = [
								'id' => $level_3['id'],
								'label' => $level_3['label'],
								'level' => $level_3['level'],
								'indikator' => [],
								'data' => []
							];
						}

						// indikator pokin level 3
						$indikator_pohon_kinerja_level_3 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER BY id", $level_3['id'], 3, 1), ARRAY_A);
						if(!empty($indikator_pohon_kinerja_level_3)){
							foreach ($indikator_pohon_kinerja_level_3 as $indikator_level_3) {
								if(!empty($indikator_level_3['label_indikator_kinerja'])){
									if(empty($data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])]['indikator'][(trim($indikator_level_3['label_indikator_kinerja']))])){
										$data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])]['indikator'][(trim($indikator_level_3['label_indikator_kinerja']))] = [
											'id' => $indikator_level_3['id'],
											'parent' => $indikator_level_3['parent'],
											'label_indikator_kinerja' => $indikator_level_3['label_indikator_kinerja'],
											'level' => $indikator_level_3['level']
										];
									}
								}
							}
						}

						// pokin level 4
						$pohon_kinerja_level_4 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER by id", $level_3['id'], 4, 1), ARRAY_A);
						if(!empty($pohon_kinerja_level_4)){
							foreach ($pohon_kinerja_level_4 as $level_4) {
								if(empty($data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])]['data'][trim($level_4['label'])])){
									$data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])]['data'][trim($level_4['label'])] = [
										'id' => $level_4['id'],
										'label' => $level_4['label'],
										'level' => $level_4['level'],
										'indikator' => []
									];
								}

								// indikator pokin level 4
								$indikator_pohon_kinerja_level_4 = $wpdb->get_results($wpdb->prepare("SELECT * FROM data_pohon_kinerja WHERE parent=%d AND level=%d AND active=%d ORDER BY id", $level_4['id'], 4, 1), ARRAY_A);
								if(!empty($indikator_pohon_kinerja_level_4)){
									foreach ($indikator_pohon_kinerja_level_4 as $indikator_level_4) {
										if(!empty($indikator_level_4['label_indikator_kinerja'])){
											if(empty($data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])]['data'][trim($level_4['label'])]['indikator'][(trim($indikator_level_4['label_indikator_kinerja']))])){
												$data_all['data'][trim($level_1['label'])]['data'][trim($level_2['label'])]['data'][trim($level_3['label'])]['data'][trim($level_4['label'])]['indikator'][(trim($indikator_level_4['label_indikator_kinerja']))] = [
													'id' => $indikator_level_4['id'],
													'parent' => $indikator_level_4['parent'],
													'label_indikator_kinerja' => $indikator_level_4['label_indikator_kinerja'],
													'level' => $indikator_level_4['level']
												];
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}

// echo '<pre>'; print_r($data_all['data']); echo '</pre>';die();

$html = '';
foreach ($data_all['data'] as $level_1) {
		$html.='<tr><td><a href="'.$this->generatePage('View Pohon Kinerja', false, '[view_pohon_kinerja]').'&id='.$level_1['id'].'" target="_blank">'.$level_1['label'].'</a></td>';
		$indikator=[];
		foreach ($level_1['indikator'] as $indikatorlevel1) {
			$indikator[]=$indikatorlevel1['label_indikator_kinerja'];
		}
		$html.='<td>'.implode("</br>", $indikator).'</td><td colspan="6"></td></tr>';
		foreach ($level_1['data'] as $level_2) {
				$html.='<tr><td colspan="2"></td><td>'.$level_2['label'].'</td>';
				$indikator=[];
				foreach ($level_2['indikator'] as $indikatorlevel2) {
						$indikator[]=$indikatorlevel2['label_indikator_kinerja'];
				}
				$html.='<td>'.implode("</br>", $indikator).'</td><td colspan="4"></td></tr>';
				foreach ($level_2['data'] as $level_3) {
						$html.='<tr><td colspan="4"></td><td>'.$level_3['label'].'</td>';
						$indikator=[];
						foreach ($level_3['indikator'] as $indikatorlevel3) {
							$indikator[]=$indikatorlevel3['label_indikator_kinerja'];
						}
						$html.='<td>'.implode("</br>", $indikator).'</td><td colspan="2"></td></tr>';
						foreach ($level_3['data'] as $level_4) {
								$html.='<tr><td colspan="6"></td><td>'.$level_4['label'].'</td>';
								$indikator=[];
								foreach ($level_4['indikator'] as $indikatorlevel4) {
									$indikator[]=$indikatorlevel4['label_indikator_kinerja'];
								}
								$html.='<td>'.implode("</br>", $indikator).'</td></tr>';
						}
				}
		}
}
?>

<style type="text/css"></style>
<h3 style="text-align: center; margin-top: 10px; font-weight: bold;">Penyusunan Pohon Kinerja</h3><br>
<div id="action" style="text-align: center; margin-top:30px; margin-bottom: 30px;">
		<a style="margin-left: 10px;" id="tambah-pohon-kinerja" onclick="return false;" href="#" class="btn btn-success">Tambah Data</a>
</div>
<div id="cetak" title="Penyusunan Pohon Kinerja" style="padding: 5px; overflow: auto; height: 100vh;">
		<table>
				<thead>
						<tr>
							<th>Level 1</th>
							<th>Indikator Kinerja</th>
							<th>Level 2</th>
							<th>Indikator Kinerja</th>
							<th>Level 3</th>
							<th>Indikator Kinerja</th>
							<th>Level 4</th>
							<th>Indikator Kinerja</th>
						</tr>
				</thead>
				<tbody>
						<?php echo $html; ?>
				</tbody>
		</table>
</div>

<script type="text/javascript"></script>